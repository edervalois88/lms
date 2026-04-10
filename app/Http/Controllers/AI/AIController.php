<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Services\AI\ClaudeService;
use App\Services\Learning\ProgressCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AIController extends Controller
{
    public function __construct(
        protected ClaudeService $claude,
        protected ProgressCalculatorService $progress
    ) {}

    public function explain(Request $request): JsonResponse
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'selected_index' => 'required|integer',
        ]);

        $question = Question::findOrFail($request->question_id);
        
        $explanation = $this->claude->explainAnswer(
            $question->stem,
            $question->options,
            array_search($question->correct_answer, $question->options),
            $request->selected_index
        );

        return response()->json([
            'explanation' => $explanation
        ]);
    }

    public function recommendation(): JsonResponse
    {
        $stats = $this->progress->getWeeklyStats(auth()->user());
        $recommendation = $this->claude->getWeeklyRecommendation($stats);

        return response()->json([
            'stats' => $stats,
            'recommendation' => $recommendation
        ]);
    }
}
