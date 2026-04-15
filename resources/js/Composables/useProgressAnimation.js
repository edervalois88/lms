// resources/js/Composables/useProgressAnimation.js
import { animate } from 'motion';
import { ref } from 'vue';

export function useProgressAnimation() {
  const animationInProgress = ref(false);

  // Animation: Progress Bar Fill
  const animateProgressBar = (element, fromPercent, toPercent, duration = 0.8) => {
    if (!element) return;
    animate(
      element,
      { width: [`${fromPercent}%`, `${toPercent}%`] },
      { duration, easing: 'ease-out' }
    );
  };

  // Animation: Radial Arc Draw
  const animateRadialArc = (element, duration = 1.2) => {
    if (!element) return;
    animate(
      element,
      { pathLength: [0, 1] },
      { duration, easing: 'ease-in-out' }
    );
  };

  // Animation: Avatar Wave (click)
  const animateAvatarWave = (element, duration = 0.6) => {
    if (!element) return;
    animate(
      element,
      {
        rotate: [0, 10, -10, 0],
        scale: [1, 1.1, 1],
      },
      { duration, easing: 'ease-in-out' }
    );
  };

  // Animation: XP Floating
  const animateXpFloat = (element, duration = 0.8) => {
    if (!element) return;
    animate(
      element,
      {
        y: [0, -30],
        opacity: [1, 0],
      },
      { duration, easing: 'ease-out' }
    );
  };

  // Animation: Level Up Evolution
  const animateLevelUp = (element, duration = 1.5) => {
    if (!element) return;
    animate(
      element,
      {
        scale: [1, 1.3, 1],
        rotate: [0, 360, 0],
        filter: ['brightness(1)', 'brightness(1.5)', 'brightness(1)'],
      },
      { duration, type: 'spring', stiffness: 200, damping: 10 }
    );
  };

  // Animation: Journey Avatar Slide
  const animateJourneySlide = (element, fromX, toX, duration = 1.2) => {
    if (!element) return;
    animate(
      element,
      { x: [fromX, toX] },
      { duration, easing: 'ease-out' }
    );
  };

  // Animation: Streak Pulse
  const animateStreakPulse = (element) => {
    if (!element) return;
    animate(
      element,
      { scale: [1, 1.05, 1] },
      { duration: 0.5, repeat: Infinity, repeatDelay: 2 }
    );
  };

  return {
    animationInProgress,
    animateProgressBar,
    animateRadialArc,
    animateAvatarWave,
    animateXpFloat,
    animateLevelUp,
    animateJourneySlide,
    animateStreakPulse,
  };
}
