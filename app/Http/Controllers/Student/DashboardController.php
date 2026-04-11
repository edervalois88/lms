<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\Learning\ProgressCalculatorService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected ProgressCalculatorService $progress
    ) {}

    public function index(): Response
    {
        $user = auth()->user()->load('major.campus.university');
        
        return Inertia::render('Dashboard', [
            'major' => $user->major,
            'user_gpa' => $user->gpa,
            'stats' => [
                'streak' => $user->streak_days,
                'total_exams' => $user->exams()->completed()->count(),
                'accuracy' => $this->progress->getWeeklyStats($user)['avg_accuracy'],
                'projection' => $this->progress->getScoreProjection($user),
            ],
            'recent_exams' => $user->exams()
                ->latest()
                ->take(5)
                ->get(),
            'subject_mastery' => $this->progress->getSubjectMastery($user),
        ]);
    }
}
