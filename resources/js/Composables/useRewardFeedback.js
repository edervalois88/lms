// resources/js/Composables/useRewardFeedback.js
import { ref, computed } from 'vue';
import { getContextualMessage } from '../Utils/avatarMessages';

// Audio configuration
const SOUND_CONFIG = {
  reward: { frequency: 800, duration: 0.15 },
  levelup: { frequency: 1200, duration: 0.3 },
  correct: { frequency: 600, duration: 0.1 },
  incorrect: { frequency: 400, duration: 0.15 },
};

// Global mutable state (shared across all composable instances)
const rewardState = ref({ xp: 0, type: null });
const audioEnabledState = ref(true);
let audioContext = null;

/**
 * Initialize AudioContext lazily on first use
 */
function initAudioContext() {
  if (audioContext) {
    return audioContext;
  }

  try {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    if (!AudioContextClass) {
      audioEnabledState.value = false;
      return null;
    }
    audioContext = new AudioContextClass();
    return audioContext;
  } catch (error) {
    audioEnabledState.value = false;
    return null;
  }
}

/**
 * Reset audio context (for testing)
 * @private
 */
export function _resetAudioContext() {
  audioContext = null;
}

/**
 * Reset all state (for testing)
 * @private
 */
export function _resetState() {
  audioContext = null;
  rewardState.value = { xp: 0, type: null };
  audioEnabledState.value = true;
}

/**
 * Play a sound using Web Audio API
 * @param {string} type - Type of sound (reward, levelup, correct, incorrect)
 */
function playSoundInternal(type) {
  if (!audioEnabledState.value) {
    return;
  }

  try {
    const ctx = initAudioContext();
    if (!ctx) {
      return;
    }

    const config = SOUND_CONFIG[type];
    if (!config) {
      return;
    }

    const oscillator = ctx.createOscillator();
    const gainNode = ctx.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(ctx.destination);

    oscillator.frequency.setValueAtTime(config.frequency, ctx.currentTime);
    gainNode.gain.setValueAtTime(0.3, ctx.currentTime);

    // Exponential ramp for fade out
    gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + config.duration);

    oscillator.start(ctx.currentTime);
    oscillator.stop(ctx.currentTime + config.duration);
  } catch (error) {
    // Silently fail - never throw errors
    audioEnabledState.value = false;
  }
}

export function useRewardFeedback() {
  /**
   * Show a reward with XP amount and type
   * @param {number} xpAmount - Amount of XP earned
   * @param {string} type - Type of reward (reward, levelup, correct, incorrect)
   */
  const showReward = (xpAmount, type) => {
    rewardState.value = {
      xp: xpAmount,
      type: type,
    };
  };

  /**
   * Get a contextual message for the reward
   * @param {string} context - Context for the message (e.g., quiz, dashboard)
   * @param {string} sentiment - Sentiment of the message (e.g., correct, incorrect, motivation)
   * @returns {string} Contextual message
   */
  const getRewardMessage = (context, sentiment) => {
    return getContextualMessage(context, sentiment);
  };

  /**
   * Play a sound for the reward
   * @param {string} type - Type of sound to play
   */
  const playSound = (type) => {
    playSoundInternal(type);
  };

  /**
   * Reset the reward state
   */
  const resetReward = () => {
    rewardState.value = {
      xp: 0,
      type: null,
    };
  };

  /**
   * Toggle audio on/off
   */
  const toggleAudio = () => {
    audioEnabledState.value = !audioEnabledState.value;
  };

  // Return reactive computed refs for state and methods
  return {
    rewardShown: computed(() => rewardState.value),
    audioEnabled: computed(() => audioEnabledState.value),
    showReward,
    getRewardMessage,
    playSound,
    resetReward,
    toggleAudio,
  };
}
