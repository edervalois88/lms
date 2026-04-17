import { describe, it, expect, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useAchievementsStore } from '@/Stores/gamification/achievementsStore';

describe('achievementsStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('initializes with empty completed and null pendingToast', () => {
    const store = useAchievementsStore();
    expect(store.completed).toEqual([]);
    expect(store.pendingToast).toBeNull();
  });

  it('unlock adds achievement to completed and sets pendingToast', () => {
    const store = useAchievementsStore();
    const achievement = { id: 'first_quiz', label: 'Primera Pregunta', cosmeticUnlocked: 'accessory_badge' };

    store.unlock(achievement);

    expect(store.completed).toHaveLength(1);
    expect(store.completed[0]).toEqual(achievement);
    expect(store.pendingToast).toEqual(achievement);
  });

  it('unlock does not add duplicate achievements', () => {
    const store = useAchievementsStore();
    const achievement = { id: 'first_quiz', label: 'Primera Pregunta', cosmeticUnlocked: null };

    store.unlock(achievement);
    store.unlock(achievement);

    expect(store.completed).toHaveLength(1);
  });

  it('clearToast sets pendingToast to null', () => {
    const store = useAchievementsStore();
    const achievement = { id: 'explorer', label: 'Explorador', cosmeticUnlocked: 'pet_phoenix' };

    store.unlock(achievement);
    expect(store.pendingToast).not.toBeNull();

    store.clearToast();
    expect(store.pendingToast).toBeNull();
  });

  it('isCompleted returns true for unlocked achievement and false for unknown', () => {
    const store = useAchievementsStore();
    const achievement = { id: 'streak_7_days', label: 'Racha de 7 días', cosmeticUnlocked: 'accessory_blue_flame' };

    expect(store.isCompleted('streak_7_days')).toBe(false);

    store.unlock(achievement);

    expect(store.isCompleted('streak_7_days')).toBe(true);
    expect(store.isCompleted('unknown_achievement')).toBe(false);
  });

  it('hydrateFromServer pushes achievement ids not already in completed', () => {
    const store = useAchievementsStore();

    store.hydrateFromServer(['first_quiz', 'explorer', 'unstoppable']);

    expect(store.completed).toHaveLength(3);
    expect(store.completed.map(a => a.id)).toEqual(['first_quiz', 'explorer', 'unstoppable']);
  });

  it('hydrateFromServer does not add duplicates if already unlocked', () => {
    const store = useAchievementsStore();
    const achievement = { id: 'first_quiz', label: 'Primera Pregunta', cosmeticUnlocked: 'accessory_badge' };

    store.unlock(achievement);
    store.hydrateFromServer(['first_quiz', 'explorer']);

    const ids = store.completed.map(a => a.id);
    expect(ids.filter(id => id === 'first_quiz')).toHaveLength(1);
    expect(ids).toContain('explorer');
  });
});
