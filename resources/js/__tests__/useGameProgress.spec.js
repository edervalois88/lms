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
    expect(gameProgress.progressPercentage.value).toBe(75);
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
    expect(gameProgress.journeyStage.value).toBe('Aprendiz');
  });

  it('should add XP and trigger level up when threshold reached', () => {
    gameProgress.addXP(200); // 450 + 200 = 650 > 500 (next level)
    expect(gameProgress.hasLeveledUp.value).toBe(true);
  });

  it('should get contextual avatar message based on streak', () => {
    const msg = gameProgress.getAvatarMessage('default');
    expect(msg).toBeTruthy();
  });
});
