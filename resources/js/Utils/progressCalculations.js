// resources/js/Utils/progressCalculations.js
export const LEVEL_THRESHOLDS = [0, 500, 1500, 3500, 7000];
export const XP_PER_LEVEL = 500;
export const JOURNEY_STAGES = ['Novato', 'Aprendiz', 'Adept', 'Experto', 'Maestro'];

export function calculateProgressPercentage(projected, gap) {
  if (gap <= 0) return 100;
  return Math.min(100, Math.round((projected / (projected + gap)) * 100));
}

export function getGapStatus(gap) {
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
}

export function getJourneyStage(level) {
  if (level < 2) return 'Novato';
  if (level < 4) return 'Aprendiz';
  if (level < 6) return 'Adept';
  if (level < 8) return 'Experto';
  return 'Maestro';
}

export function getNextLevelThreshold(currentLevel) {
  return LEVEL_THRESHOLDS[currentLevel] ?? XP_PER_LEVEL * currentLevel;
}
