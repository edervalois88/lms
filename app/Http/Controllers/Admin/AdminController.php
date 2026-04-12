<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Index', [
            'stats' => [
                'users' => User::count(),
                'active_today' => User::whereDate('last_study_at', now()->toDateString())->count(),
                'completed_exams' => Exam::completed()->count(),
            ],
        ]);
    }
}
