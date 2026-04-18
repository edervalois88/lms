<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;

class HydrateUserGamification
{
    public function handle(Request $request, $next)
    {
        if ($request->user()) {
            $user = $request->user();
            // Use the getGamificationStateAttribute method from the User model
            $user->gamification = $user->getGamificationStateAttribute();
        }

        return $next($request);
    }
}
