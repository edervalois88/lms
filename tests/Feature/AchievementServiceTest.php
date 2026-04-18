<?php

namespace Tests\Feature;

use App\Enums\AchievementId;
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

        $this->assertContains(AchievementId::FIRST_QUIZ, $unlocked);
        $this->assertTrue($user->achievements()->where('achievement_id', AchievementId::FIRST_QUIZ)->exists());
        $achievement = $user->achievements()->where('achievement_id', AchievementId::FIRST_QUIZ)->first();
        $this->assertEquals('accessory_badge', $achievement->cosmetic_unlocked);
    }

    public function test_already_unlocked_achievement_not_repeated()
    {
        $user = User::factory()->create();
        $user->achievements()->create([
            'achievement_id' => AchievementId::FIRST_QUIZ,
            'cosmetic_unlocked' => 'accessory_badge',
            'unlocked_at' => now(),
        ]);

        $unlocked = $this->achievementService->evaluateAchievements(
            $user,
            'quiz_complete',
            ['score' => 80, 'questions_answered' => 1]
        );

        $this->assertNotContains(AchievementId::FIRST_QUIZ, $unlocked);
        $achievement = $user->achievements()->where('achievement_id', AchievementId::FIRST_QUIZ)->first();
        $this->assertEquals('accessory_badge', $achievement->cosmetic_unlocked);
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
