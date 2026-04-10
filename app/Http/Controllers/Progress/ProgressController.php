<?php

namespace App\Http\Controllers\Progress;

use App\Http\Controllers\Controller;
use App\Services\Learning\ProgressCalculatorService;
use Inertia\Inertia;
use Inertia\Response;

class ProgressController extends Controller
{
    public function __construct(
        protected ProgressCalculatorService $calculator
    ) {}

    public function index(): Response
    {
        $user = auth()->user();

        return Inertia::render('Progress/Index', [
            'mastery' => $this->calculator->getSubjectMastery($user),
            'projection' => $this->calculator->getScoreProjection($user),
            'exams_history' => $user->exams()
                ->withCount('examAnswers')
                ->latest()
                ->paginate(10),
            'weekly_stats' => $this->calculator->getWeeklyStats($user)
        ]);
    }
}
