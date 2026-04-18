<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Hydrate authenticated users with gamification state.
 *
 * Computes and attaches gamification data (gold, xp, level, achievements)
 * to the user object so it's available throughout the request lifecycle
 * and can be shared with the frontend via Inertia.
 */
class HydrateUserGamification
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            // Hydrate user with gamification state
            // This includes: gold, xp, current_level, achievements_unlocked
            $user = $request->user();
            $user->gamification = $user->getGamificationStateAttribute();
        }

        return $next($request);
    }
}
