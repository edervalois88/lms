<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));

        $users = User::query()
            ->with(['major.campus.university'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(function (User $user) {
                $major = $user->major;
                $campus = $major?->campus;
                $university = $campus?->university;

                $spatieRole = $user->getRoleNames()->first();
                $attributeRole = $user->role;
                $role = $spatieRole
                    ?: (is_object($attributeRole) && method_exists($attributeRole, 'value') ? $attributeRole->value : (string) $attributeRole);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => Str::upper($role ?: 'student'),
                    'major' => $major?->name,
                    'campus' => $campus?->name,
                    'university' => $university?->acronym,
                    'gpa' => $user->gpa,
                    'streak_days' => $user->streak_days,
                    'onboarded_at' => optional($user->onboarded_at)?->format('Y-m-d H:i'),
                    'last_study_at' => optional($user->last_study_at)?->format('Y-m-d H:i'),
                    'created_at' => optional($user->created_at)?->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('Admin/Index', [
            'stats' => [
                'users' => User::count(),
                'active_today' => User::whereDate('last_study_at', now()->toDateString())->count(),
                'completed_exams' => Exam::completed()->count(),
            ],
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function usersIndex(Request $request): Response
    {
        $search = trim((string) $request->string('search'));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => ['search' => $search],
        ]);
    }

    public function usersShow(User $user): Response
    {
        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
        ]);
    }

    public function usersUpdate(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|max:255|unique:users,email,' . $user->id,
            'is_premium' => 'boolean',
        ]);

        $user->update($validated);

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function questionsIndex(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $subjectId = $request->integer('subject_id', 0);

        $questions = Question::query()
            ->with(['topic.subject'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('stem', 'like', "%{$search}%")
                    ->orWhere('correct_answer', 'like', "%{$search}%");
            })
            ->when($subjectId > 0, function ($query) use ($subjectId) {
                $query->whereHas('topic', function ($builder) use ($subjectId) {
                    $builder->where('subject_id', $subjectId);
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $subjects = Subject::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Questions/Index', [
            'questions' => $questions,
            'subjects' => $subjects,
            'filters' => [
                'search' => $search,
                'subject_id' => $subjectId,
            ],
        ]);
    }

    public function questionsShow(Question $question): Response
    {
        return Inertia::render('Admin/Questions/Show', [
            'question' => $question,
        ]);
    }

    public function questionsUpdate(Request $request, Question $question)
    {
        $validated = $request->validate([
            'stem' => 'string',
            'options' => 'array',
            'correct_answer' => 'string',
            'difficulty' => 'integer|min:1|max:5',
            'is_active' => 'boolean',
        ]);

        $question->update($validated);

        return back()->with('success', 'Pregunta actualizada correctamente.');
    }

    public function analytics(): Response
    {
        return Inertia::render('Admin/Analytics', [
            'metrics' => [
                'total_users' => User::count(),
                'total_exams' => Exam::count(),
                'total_questions' => Question::count(),
            ],
        ]);
    }

    public function settings(): Response
    {
        return Inertia::render('Admin/Settings');
    }
}
