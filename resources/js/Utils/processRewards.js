import { useCurrencyStore } from '@/Stores/gamification/currencyStore';
import { useAchievementsStore } from '@/Stores/gamification/achievementsStore';

const ACHIEVEMENT_LABELS = {
  first_quiz: { label: 'Primera Pregunta', cosmeticUnlocked: 'accessory_badge' },
  streak_7_days: { label: 'Racha de 7 días', cosmeticUnlocked: 'accessory_blue_flame' },
  streak_30_days: { label: 'Racha de 30 días', cosmeticUnlocked: 'pet_golden_dragon' },
  mastery_math_8: { label: 'Maestro de Matemáticas', cosmeticUnlocked: 'outfit_math_master' },
  simulator_perfect: { label: 'Simulacro Perfecto', cosmeticUnlocked: 'accessory_crown' },
  explorer: { label: 'Explorador', cosmeticUnlocked: 'pet_phoenix' },
  unstoppable: { label: 'Incansable', cosmeticUnlocked: 'background_starfield' },
};

export function processRewards(data) {
  const currency = useCurrencyStore();
  const achievementsStore = useAchievementsStore();

  if (data.gold_earned) currency.addGold(data.gold_earned);
  if (data.xp_earned) currency.addXP(data.xp_earned);

  (data.achievements_unlocked ?? []).forEach((achievementId) => {
    const meta = ACHIEVEMENT_LABELS[achievementId] ?? { label: achievementId, cosmeticUnlocked: null };
    achievementsStore.unlock({ id: achievementId, ...meta });
  });
}
