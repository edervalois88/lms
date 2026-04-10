<?php

namespace App\Services\AI;

use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class QuestionGeneratorService
{
    public function __construct(protected ClaudeService $claude) {}

    public function generateForTopic(Topic $topic, int $difficulty, User $user): ?Question
    {
        try {
            $data = $this->claude.generateQuestion($topic->subject->name, $topic->name, $difficulty);

            return Question::create([
                'topic_id' => $topic->id,
                'created_by' => $user->id,
                'type' => 'multiple_choice',
                'stem' => $data['body'],
                'options' => $data['options'],
                'correct_answer' => $data['options'][$data['correct_index']],
                'explanation' => $data['explanation'],
                'difficulty' => $difficulty,
                'is_ai_generated' => true,
            ]);
        } catch (\Exception $e) {
            Log::error("Question Generation Failed: " . $e->getMessage());
            return null;
        }
    }

    public function generateBatch(Topic $topic, int $difficulty, User $user, int $count = 5): Collection
    {
        $questions = collect();

        for ($i = 0; $i < $count; $i++) {
            $question = $this->generateForTopic($topic, $difficulty, $user);
            if ($question) {
                $questions->push($question);
            }
        }

        return $questions;
    }
}
