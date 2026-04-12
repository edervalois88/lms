<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
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
}
