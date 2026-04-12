<?php

namespace App\Services\Learning;

use App\Models\User;
use App\Models\Question;
use App\Models\SpacedRepetitionCard;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SpacedRepetitionService
{
    public function processAnswer(User $user, Question $question, bool $correct, int $quality): void
    {
        $card = SpacedRepetitionCard::firstOrNew([
            'user_id' => $user->id,
            'question_id' => $question->id,
        ]);

        $ef = $card->ease_factor ?? 2.5;
        $card->repetitions = (int) ($card->repetitions ?? 0);
        $card->interval = (int) ($card->interval ?? 0);

        $quality = max(0, min(5, $quality));

        // SM-2 formula for Ease Factor
        $newEf = $ef + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02));
        $card->ease_factor = max(1.3, $newEf);

        if ($correct && $quality >= 3) {
            $card->repetitions += 1;
            
            if ($card->repetitions == 1) {
                $card->interval = 1;
            } elseif ($card->repetitions == 2) {
                $card->interval = 6;
            } else {
                $card->interval = (int) ceil($card->interval * $card->ease_factor);
            }
        } else {
            $card->repetitions = 0;
            $card->interval = 1;
        }

        $card->next_review_at = Carbon::now()->addDays($card->interval);
        $card->save();
    }

    public function getDueCards(User $user, int $limit = 20): Collection
    {
        return SpacedRepetitionCard::where('user_id', $user->id)
            ->where('next_review_at', '<=', Carbon::now())
            ->with('question.topic.subject')
            ->orderBy('next_review_at', 'asc')
            ->limit($limit)
            ->get();
    }
}
