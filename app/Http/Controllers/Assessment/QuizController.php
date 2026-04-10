<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Topic;
use App\Services\Learning\AdaptiveDifficultyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    public function __construct(
        protected AdaptiveDifficultyService $difficultyService
    ) {}

    public function index(): Response
    {
        $user = auth()->user();
        // Obtener área del último examen o por defecto Área 1
        $area = $user->exams()->latest()->value('exam_area') ?? 1;

        $subjects = Subject::byArea($area)
            ->withCount('topics')
            ->get()
            ->map(function ($subject) use ($user) {
                return array_merge($subject->toArray(), [
                    'current_difficulty' => $this->difficultyService->getCurrentDifficulty($user, $subject)
                ]);
            });

        return Inertia::render('Quiz/Index', [
            'subjects' => $subjects
        ]);
    }

    public function show(Subject $subject): Response
    {
        $user = auth()->user();
        $topics = $subject->topics()
            ->withCount('questions')
            ->get();

        return Inertia::render('Quiz/Session', [
            'subject' => $subject,
            'topics' => $topics,
            'initial_difficulty' => $this->difficultyService->getCurrentDifficulty($user, $subject)
        ]);
    }
}
