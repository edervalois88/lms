<?php

namespace App\Services\RAG;

use App\Models\Question;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VectorSearchService
{
    public function technicalContextForQuestion(Question $question): string
    {
        $remote = $this->queryRemoteVectorStore($question);
        if ($remote !== null) {
            return $remote;
        }

        $topic = $question->topic;
        $subject = $topic?->subject;

        $parts = array_filter([
            $subject?->description,
            $topic?->description,
            $question->explanation,
        ]);

        if (empty($parts)) {
            return 'No hay contexto técnico indexado para esta pregunta. Usa principios base del tema para orientar al alumno.';
        }

        return implode("\n\n", $parts);
    }

    private function queryRemoteVectorStore(Question $question): ?string
    {
        $url = (string) config('services.vector.url');
        $collection = (string) config('services.vector.collection', 'exam_context');

        if ($url === '') {
            return null;
        }

        // Implementacion best-effort para Qdrant scroll por metadata del tema.
        try {
            $endpoint = rtrim($url, '/') . '/collections/' . $collection . '/points/scroll';
            $response = Http::withHeaders(array_filter([
                'api-key' => (string) config('services.vector.api_key'),
            ]))->post($endpoint, [
                'limit' => 3,
                'with_payload' => true,
                'filter' => [
                    'must' => [
                        [
                            'key' => 'topic_slug',
                            'match' => [
                                'value' => (string) optional($question->topic)->slug,
                            ],
                        ],
                    ],
                ],
            ]);

            if (! $response->ok()) {
                return null;
            }

            $points = (array) data_get($response->json(), 'result.points', []);
            if ($points === []) {
                return null;
            }

            $chunks = [];
            foreach ($points as $point) {
                $text = trim((string) data_get($point, 'payload.text', ''));
                if ($text !== '') {
                    $chunks[] = $text;
                }
            }

            return $chunks !== [] ? implode("\n\n", $chunks) : null;
        } catch (\Throwable $exception) {
            Log::warning('Vector search unavailable', ['error' => $exception->getMessage()]);
            return null;
        }
    }
}
