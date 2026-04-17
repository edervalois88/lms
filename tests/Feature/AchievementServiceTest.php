<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Learning\AchievementService;
use Tests\TestCase;

class AchievementServiceTest extends TestCase
{
    protected AchievementService $achievementService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->achievementService = app(AchievementService::class);
    }

    public function test_first_quiz_unlocked_on_first_activity()
    {
        $user = User::factory()->create();

        $unlocked = $this->achievementService->evaluateAchievements(
            $user,
            'quiz_complete',
            ['score' => 80, 'questions_answered' => 1]
        );

        $this->assertContains('first_quiz', $unlocked);
        $this->assertTrue($user->achievements()->where('achievement_id', 'first_quiz')->exists());
    }

    public function test_already_unlocked_achievement_not_repeated()
    {
        $user = User::factory()->create();
        $user->achievements()->create([
            'achievement_id' => 'first_quiz',
            'unlocked_at' => now(),
        ]);

        $unlocked = $this->achievementService->evaluateAchievements(
            $user,
            'quiz_complete',
            ['score' => 80, 'questions_answered' => 1]
        );

        $this->assertNotContains('first_quiz', $unlocked);
    }

    public function test_no_achievements_unlocked_for_incomplete_activity()
    {
        $user = User::factory()->create();

        $unlocked = $this->achievementService->evaluateAchievements(
            $user,
            'unknown_activity',
            []
        );

        $this->assertEmpty($unlocked);
    }
}
