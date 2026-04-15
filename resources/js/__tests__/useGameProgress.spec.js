import { describe, it, expect, beforeEach } from 'vitest';
import { useGameProgress } from '@/Composables/useGameProgress';
import { ref } from 'vue';

describe('useGameProgress', () => {
  let gameProgress;

  beforeEach(() => {
    const mockUser = ref({
      gamification: { current_level: 5, current_xp: 450, rank: 'Aprendiz', streak_days: 7 },
      gpa: 3.5,
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

  it('should calculate progress percentage correctly', () => {
    // With projected=75, gap=15: 75/(75+15)*100 = 83%
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
    // Level 5: level < 6 returns 'Adept'
    expect(gameProgress.journeyStage.value).toBe('Adept');
  });

  it('should add XP and trigger level up when threshold reached', () => {
    // At level 5, next threshold is LEVEL_THRESHOLDS[5] (undefined - end of array)
    // Use level 1 instead: current_xp: 450, next threshold: LEVEL_THRESHOLDS[1] = 500
    // So 450 + 200 = 650 > 500 should trigger level up
    const mockUser2 = ref({
      gamification: { current_level: 1, current_xp: 450, rank: 'Novato', streak_days: 7 },
      gpa: 3.5,
    });
    const pg = useGameProgress(mockUser2, ref({
      projection: { projected_score: 75, gap_to_goal: 15, goal_name: 'Ingeniería' },
    }));
    pg.addXP(200);
    expect(pg.hasLeveledUp.value).toBe(true);
  });

  it('should get contextual avatar message based on streak', () => {
    const msg = gameProgress.getAvatarMessage('default');
    expect(msg).toBeTruthy();
  });
});
