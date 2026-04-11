<?php

namespace App\Http\Controllers\Student;

use App\Enums\SubjectArea;
use App\Models\Major;

class OnboardingController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Student/Onboarding', [
            'areas' => collect(SubjectArea::cases())->map(fn($area) => [
                'id' => $area->value,
                'name' => $area->label(),
            ]),
            'majors' => Major::orderBy('min_score', 'desc')->get(['id', 'name', 'school_name', 'min_score', 'area_id'])
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'area_id' => 'required|integer|between:1,4',
            'major_id' => 'required|exists:majors,id',
            'study_hours_per_day' => 'required|integer|min:1|max:12',
        ]);

        $user = auth()->user();
        $user->update([
            'major_id' => $request->major_id,
            'preferences' => [
                'target_area' => $request->area_id,
                'study_hours' => $request->study_hours_per_day,
            ],
            'onboarded_at' => now(),
        ]);

        return redirect()->route('dashboard');
    }
}
