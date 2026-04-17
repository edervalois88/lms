import { setActivePinia, createPinia } from 'pinia';
import { useCurrencyStore } from '@/Stores/gamification/currencyStore';
import { useAchievementsStore } from '@/Stores/gamification/achievementsStore';
import { processRewards } from '@/Utils/processRewards';
import { describe, it, expect, beforeEach } from 'vitest';

describe('processRewards', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('adds gold when gold_earned is present', () => {
    processRewards({ gold_earned: 50 });
    expect(useCurrencyStore().gold).toBe(50);
  });

  it('adds xp when xp_earned is present', () => {
    processRewards({ xp_earned: 100 });
    expect(useCurrencyStore().xp).toBe(100);
  });

  it('unlocks achievements when achievements_unlocked is present', () => {
    processRewards({ achievements_unlocked: ['first_quiz'] });
    expect(useAchievementsStore().isCompleted('first_quiz')).toBe(true);
  });

  it('sets pendingToast when achievement is unlocked', () => {
    processRewards({ achievements_unlocked: ['first_quiz'] });
    expect(useAchievementsStore().pendingToast).not.toBeNull();
    expect(useAchievementsStore().pendingToast.label).toBe('Primera Pregunta');
  });

  it('handles empty or undefined achievements_unlocked gracefully', () => {
    expect(() => processRewards({})).not.toThrow();
    expect(() => processRewards({ achievements_unlocked: [] })).not.toThrow();
    expect(() => processRewards({ achievements_unlocked: null })).not.toThrow();
  });

  it('handles unknown achievement IDs with fallback label', () => {
    processRewards({ achievements_unlocked: ['unknown_achievement'] });
    const store = useAchievementsStore();
    expect(store.isCompleted('unknown_achievement')).toBe(true);
    expect(store.pendingToast.label).toBe('unknown_achievement');
  });
});
