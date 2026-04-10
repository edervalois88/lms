<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class TrackStudyStreak
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            $now = Carbon::now();
            
            // Actualizar solo una vez cada 15 minutos para optimizar DB
            if ($user->last_study_at && Carbon::parse($user->last_study_at)->diffInMinutes($now) < 15) {
                return $next($request);
            }

            if (!$user->last_study_at) {
                $user->streak_days = 1;
                $user->last_study_at = $now;
                $user->save();
            } else {
                $lastStudy = Carbon::parse($user->last_study_at);
                
                if ($lastStudy->isToday()) {
                    // Ya estudió hoy, solo actualizamos el timestamp
                    $user->last_study_at = $now;
                    $user->save();
                } elseif ($lastStudy->isYesterday()) {
                    // Estudió ayer, incrementamos racha
                    $user->streak_days += 1;
                    $user->last_study_at = $now;
                    $user->save();
                } else {
                    // Pasó más de un día, reseteamos racha
                    $user->streak_days = 1;
                    $user->last_study_at = $now;
                    $user->save();
                }
            }
        }

        return $next($request);
    }
}
