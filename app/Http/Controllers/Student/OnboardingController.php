<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\University;
use App\Models\Major;
use App\Models\VocationalQuestion;
use App\Services\Vocational\VocationalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function __construct(
        protected VocationalService $vocational
    ) {}

    public function show(): Response
    {
        return Inertia::render('Student/Onboarding', [
            'universities' => University::with('campuses.majors')->get(),
            'vocational_questions' => VocationalQuestion::orderBy('order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'major_id' => 'required|exists:majors,id',
            'study_hours_per_day' => 'required|integer|min:1|max:12',
            'gpa' => 'nullable|numeric|between:6,10',
        ]);

        $user = auth()->user();
        $user->update([
            'major_id' => $request->major_id,
            'gpa' => $request->gpa,
            'preferences' => array_merge($user->preferences ?? [], [
                'study_hours' => $request->study_hours_per_day,
            ]),
            'onboarded_at' => now(),
        ]);

        return redirect()->route('dashboard');
    }

    /**
     * Procesa el test vocacional y retorna recomendaciones
     */
    public function submitVocationalTest(Request $request): Response
    {
        $request->validate([
            'answers' => 'required|array',
        ]);

        $result = $this->vocational->processResults(auth()->user(), $request->answers);
        $recommendations = $this->vocational->getRecommendedMajors($result->primary_type);

        return Inertia::render('Student/Onboarding', [
            'vocational_result' => $result,
            'recommendations' => $recommendations,
            'step' => 'results'
        ]);
    }
}
