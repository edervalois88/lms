import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { useGameProgress } from '@/Composables/useGameProgress';
import { ref } from 'vue';

describe('useGameProgress', () => {
  let gameProgress;

  beforeEach(() => {
    vi.useFakeTimers();  // Enable fake timers for setTimeout

    const mockUser = ref({
      gamification: { current_level: 1, current_xp: 0, rank: 'Novato', streak_days: 0 },
      gpa: 2.5,
    });
    const mockStats = ref({
      projection: { projected_score: 75, gap_to_goal: 15, goal_name: 'Ingeniería' },
      subject_mastery: [
        { name: 'Matemáticas', progress: 85, gap: 15 },
        { name: 'Física', progress: 60, gap: 40 },
      ],
    });
    gameProgress = useGameProgress(mockUser, mockStats);
  });

  afterEach(() => {
    vi.clearAllTimers();  // Clear timers after each test
    vi.useRealTimers();   // Reset to real timers
  });

  it('should calculate progress percentage correctly', () => {
    expect(gameProgress.progressPercentage.value).toBe(83);
  });

  it('should determine gap status as PRÓXIMA when gap <= 10', () => {
    const closeGap = ref({
      projection: { gap_to_goal: 8 },
    });
    const pg = useGameProgress(ref({}), closeGap);
    expect(pg.gapStatus.value.text).toBe('META PRÓXIMA');
    expect(pg.gapStatus.value.color).toBe('text-orange-400');
  });

  it('should return journey stage based on level', () => {
    expect(gameProgress.journeyStage.value).toBe('Novato');
  });

  it('should add XP and trigger level up when threshold reached', () => {
    // Manually set current_xp to 400 to test level-up at 500 threshold
    gameProgress.currentXP.value = 400;
    gameProgress.addXP(200); // 400 + 200 = 600 > 500 threshold

    // Immediately after addXP, hasLeveledUp should be true
    expect(gameProgress.hasLeveledUp.value).toBe(true);
    expect(gameProgress.currentLevel.value).toBe(2);

    // Advance timers to trigger the setTimeout callback
    vi.advanceTimersByTime(2000);
    expect(gameProgress.hasLeveledUp.value).toBe(false);
  });

  it('should reset hasLeveledUp flag after timeout', () => {
    gameProgress.currentXP.value = 400;
    gameProgress.addXP(200);
    expect(gameProgress.hasLeveledUp.value).toBe(true);

    // Before timeout: still true
    vi.advanceTimersByTime(1000);
    expect(gameProgress.hasLeveledUp.value).toBe(true);

    // After timeout: reset to false
    vi.advanceTimersByTime(1000);
    expect(gameProgress.hasLeveledUp.value).toBe(false);
  });

  it('should get contextual avatar message based on streak', () => {
    const msg = gameProgress.getAvatarMessage('default');
    expect(msg).toBeTruthy();
    expect(typeof msg).toBe('string');
  });
});
