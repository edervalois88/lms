<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class AiMetricsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $day = now()->format('Ymd');
        $minute = now()->format('YmdHi');

        $requestsToday = (int) Cache::get("ai:metrics:{$day}:requests_total", 0);
        $tokensToday = (int) Cache::get("ai:metrics:{$day}:tokens_total", 0);
        $errorsToday = (int) Cache::get("ai:metrics:{$day}:errors_total", 0);
        $cacheHits = (int) Cache::get("ai:metrics:{$day}:cache_hits", 0) + (int) Cache::get("ai:metrics:{$day}:tutor:cache_hits", 0);
        $cacheMisses = (int) Cache::get("ai:metrics:{$day}:cache_misses", 0) + (int) Cache::get("ai:metrics:{$day}:tutor:cache_misses", 0);

        $requestsCurrentMinute = (int) Cache::get("ai:metrics:minute:{$minute}:requests_total", 0);
        $tokensCurrentMinute = (int) Cache::get("ai:metrics:minute:{$minute}:tokens_total", 0);

        $hitRate = ($cacheHits + $cacheMisses) > 0
            ? round(($cacheHits / ($cacheHits + $cacheMisses)) * 100, 2)
            : 0.0;

        return response()->json([
            'date' => now()->toDateString(),
            'window' => [
                'current_minute' => now()->format('Y-m-d H:i'),
            ],
            'totals_today' => [
                'requests' => $requestsToday,
                'tokens' => $tokensToday,
                'errors' => $errorsToday,
                'cache_hits' => $cacheHits,
                'cache_misses' => $cacheMisses,
                'cache_hit_rate_percent' => $hitRate,
            ],
            'current_minute' => [
                'requests' => $requestsCurrentMinute,
                'tokens' => $tokensCurrentMinute,
            ],
            'operations' => [
                'tutor' => [
                    'requests' => (int) Cache::get("ai:metrics:{$day}:tutor:requests_total", 0),
                    'tokens' => (int) Cache::get("ai:metrics:{$day}:tutor:tokens_total", 0),
                    'errors' => (int) Cache::get("ai:metrics:{$day}:tutor:errors_total", 0),
                    'cache_hits' => (int) Cache::get("ai:metrics:{$day}:tutor:cache_hits", 0),
                    'cache_misses' => (int) Cache::get("ai:metrics:{$day}:tutor:cache_misses", 0),
                ],
                'analyze_profile' => [
                    'requests' => (int) Cache::get("ai:metrics:{$day}:analyze_profile:requests_total", 0),
                    'tokens' => (int) Cache::get("ai:metrics:{$day}:analyze_profile:tokens_total", 0),
                    'errors' => (int) Cache::get("ai:metrics:{$day}:analyze_profile:errors_total", 0),
                    'cache_hits' => (int) Cache::get("ai:metrics:{$day}:analyze_profile:cache_hits", 0),
                    'cache_misses' => (int) Cache::get("ai:metrics:{$day}:analyze_profile:cache_misses", 0),
                ],
                'weekly_recommendation' => [
                    'requests' => (int) Cache::get("ai:metrics:{$day}:weekly_recommendation:requests_total", 0),
                    'tokens' => (int) Cache::get("ai:metrics:{$day}:weekly_recommendation:tokens_total", 0),
                    'errors' => (int) Cache::get("ai:metrics:{$day}:weekly_recommendation:errors_total", 0),
                    'cache_hits' => (int) Cache::get("ai:metrics:{$day}:weekly_recommendation:cache_hits", 0),
                    'cache_misses' => (int) Cache::get("ai:metrics:{$day}:weekly_recommendation:cache_misses", 0),
                ],
                'weak_topic_priorities' => [
                    'requests' => (int) Cache::get("ai:metrics:{$day}:weak_topic_priorities:requests_total", 0),
                    'tokens' => (int) Cache::get("ai:metrics:{$day}:weak_topic_priorities:tokens_total", 0),
                    'errors' => (int) Cache::get("ai:metrics:{$day}:weak_topic_priorities:errors_total", 0),
                    'cache_hits' => (int) Cache::get("ai:metrics:{$day}:weak_topic_priorities:cache_hits", 0),
                    'cache_misses' => (int) Cache::get("ai:metrics:{$day}:weak_topic_priorities:cache_misses", 0),
                ],
                'alternatives' => [
                    'requests' => (int) Cache::get("ai:metrics:{$day}:alternatives:requests_total", 0),
                    'tokens' => (int) Cache::get("ai:metrics:{$day}:alternatives:tokens_total", 0),
                    'errors' => (int) Cache::get("ai:metrics:{$day}:alternatives:errors_total", 0),
                    'cache_hits' => (int) Cache::get("ai:metrics:{$day}:alternatives:cache_hits", 0),
                    'cache_misses' => (int) Cache::get("ai:metrics:{$day}:alternatives:cache_misses", 0),
                ],
            ],
            'limits_reference' => [
                'requests_per_minute' => 30,
                'tokens_per_minute' => 6000,
                'requests_per_day' => 14400,
                'tokens_per_day' => 500000,
            ],
        ]);
    }
}
