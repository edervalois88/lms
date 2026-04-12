<?php

namespace App\Http\Middleware;

use App\Services\Learning\GamificationService;
use App\Services\Rewards\RewardInventoryService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        $manifestPath = public_path('build/manifest.json');
        return file_exists($manifestPath) ? md5_file($manifestPath) : parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $gamificationData = null;
        $cosmeticsData = null;
        if ($user = $request->user()) {
            $gamificationData = app(GamificationService::class)->getLevel($user);
            $cosmeticsData = app(RewardInventoryService::class)->getUiCosmetics($user);
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
                'is_admin' => (bool) ($request->user()?->hasRole('admin')),
                'gamification' => $gamificationData,
                'cosmetics' => $cosmeticsData,
            ],
            'ziggy' => function () use ($request) {
                return array_merge((new \Tighten\Ziggy\Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
        ]);
    }
}
