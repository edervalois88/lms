import { computed, ref } from 'vue';

export function useGameProgress(userProps, statsProps) {
  // Reactive refs
  const currentXP = ref(userProps.value?.gamification?.current_xp ?? 0);
  const currentLevel = ref(userProps.value?.gamification?.current_level ?? 1);
  const streakDays = ref(userProps.value?.gamification?.streak_days ?? 0);
  const gpaActual = ref(userProps.value?.gpa ?? 0);
  const rank = ref(userProps.value?.gamification?.rank ?? 'Novato');
  const hasLeveledUp = ref(false);

  // Constants
  const XP_PER_LEVEL = 500; // XP needed per level
  const LEVELS = ['Novato', 'Aprendiz', 'Adept', 'Experto', 'Maestro'];
  const LEVEL_THRESHOLDS = [0, 500, 1500, 3500, 7000]; // Cumulative thresholds
  const LEVELUP_FEEDBACK_DURATION = 2000; // Duration for level-up visual feedback

  // Computed: Progress Percentage
  const progressPercentage = computed(() => {
    const gap = statsProps.value?.projection?.gap_to_goal ?? 0;
    const projected = statsProps.value?.projection?.projected_score ?? 0;
    if (gap <= 0) return 100;
    return Math.min(100, Math.round((projected / (projected + gap)) * 100));
  });

  // Computed: Gap Status
  const gapStatus = computed(() => {
    const gap = statsProps.value?.projection?.gap_to_goal;
    if (gap === null || gap === undefined) {
      return { text: 'NODO INACTIVO', color: 'text-gray-500', bg: 'bg-white/5' };
    }
    if (gap <= 0) {
      return { text: 'ZONA DE INGRESO', color: 'text-green-400', bg: 'bg-green-400/10' };
    }
    if (gap <= 10) {
      return { text: 'META PRÓXIMA', color: 'text-orange-400', bg: 'bg-orange-400/10' };
    }
    return { text: 'BRECHA CRÍTICA', color: 'text-red-400', bg: 'bg-red-400/10' };
  });

  // Computed: Journey Stage
  const journeyStage = computed(() => {
    const level = currentLevel.value;
    if (level < 2) return 'Novato';
    if (level < 4) return 'Aprendiz';
    if (level < 6) return 'Adept';
    if (level < 8) return 'Experto';
    return 'Maestro';
  });

  // Computed: Next Level XP Required
  const nextLevelXp = computed(() => {
    const nextThreshold = LEVEL_THRESHOLDS[currentLevel.value] ?? XP_PER_LEVEL * currentLevel.value;
    return Math.max(0, nextThreshold - currentXP.value);
  });

  // Method: Add XP
  const addXP = (amount) => {
    currentXP.value += amount;
    const nextThreshold = LEVEL_THRESHOLDS[currentLevel.value];
    if (currentXP.value >= nextThreshold) {
      currentLevel.value += 1;
      hasLeveledUp.value = true;
      setTimeout(() => { hasLeveledUp.value = false; }, LEVELUP_FEEDBACK_DURATION);
    }
  };

  // Method: Get Avatar Message
  const getAvatarMessage = (context) => {
    if (streakDays.value > 5) return '¡Vamos, estás en fuego! 🔥';
    if (streakDays.value === 0 && context === 'default') return 'Te echo de menos... 😢';
    if (gapStatus.value.text === 'META PRÓXIMA') return '¡Casi ahí! 💪';
    return '¡Tú puedes! 🚀';
  };

  return {
    currentXP,
    currentLevel,
    streakDays,
    rank,
    progressPercentage,
    gapStatus,
    journeyStage,
    nextLevelXp,
    hasLeveledUp,
    addXP,
    getAvatarMessage,
  };
}
