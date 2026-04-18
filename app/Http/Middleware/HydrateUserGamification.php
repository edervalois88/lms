<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
        // Middleware just passes through. Gamification is now computed on-demand
        // via the User model's gamification accessor to prevent issues with
        // Eloquent trying to persist non-existent columns during save().
        return $next($request);
    }
}
