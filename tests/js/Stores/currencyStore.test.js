import { describe, it, expect, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useCurrencyStore } from '@/Stores/gamification/currencyStore';

describe('currencyStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('initializes with zero values by default', () => {
    const store = useCurrencyStore();
    expect(store.gold).toBe(0);
    expect(store.xp).toBe(0);
    expect(store.currentLevel).toBe(1);
  });

  it('addGold increases the balance', () => {
    const store = useCurrencyStore();
    store.addGold(50);
    expect(store.gold).toBe(50);
    store.addGold(30);
    expect(store.gold).toBe(80);
  });

  it('spendGold decreases the balance and returns true', () => {
    const store = useCurrencyStore();
    store.addGold(100);
    const result = store.spendGold(40);
    expect(result).toBe(true);
    expect(store.gold).toBe(60);
  });

  it('spendGold returns false when insufficient funds', () => {
    const store = useCurrencyStore();
    store.addGold(50);
    const result = store.spendGold(100);
    expect(result).toBe(false);
    expect(store.gold).toBe(50); // balance unchanged
  });

  it('canAfford returns correct boolean', () => {
    const store = useCurrencyStore();
    expect(store.canAfford(100)).toBe(false);
    store.addGold(150);
    expect(store.canAfford(100)).toBe(true);
    expect(store.canAfford(150)).toBe(true);
    expect(store.canAfford(151)).toBe(false);
  });

  it('hydrate sets all values from server data', () => {
    const store = useCurrencyStore();
    store.hydrate({
      gold: 250,
      xp: 1500,
      current_level: 5,
    });
    expect(store.gold).toBe(250);
    expect(store.xp).toBe(1500);
    expect(store.currentLevel).toBe(5);
  });
});
