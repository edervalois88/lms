// resources/js/Composables/useProgressAnimation.js
import { animate } from 'motion';
import { ref } from 'vue';

export function useProgressAnimation() {
  /**
   * Animates a progress bar fill from one percentage to another
   * @param {Element} element - DOM element to animate
   * @param {number} fromPercent - Starting percentage (0-100)
   * @param {number} toPercent - Ending percentage (0-100)
   * @param {number} duration - Animation duration in seconds (default 0.8)
   */
  const animateProgressBar = (element, fromPercent, toPercent, duration = 0.8) => {
    if (!element) return;
    animate(
      element,
      { width: [`${fromPercent}%`, `${toPercent}%`] },
      { duration, easing: 'ease-out' }
    );
  };

  /**
   * Animates a radial arc stroke to draw from 0 to full length
   * @param {Element} element - SVG path element to animate
   * @param {number} duration - Animation duration in seconds (default 1.2)
   */
  const animateRadialArc = (element, duration = 1.2) => {
    if (!element) return;
    animate(
      element,
      { pathLength: [0, 1] },
      { duration, easing: 'ease-in-out' }
    );
  };

  /**
   * Animates an avatar wave effect with rotation and scale
   * @param {Element} element - DOM element to animate (typically an avatar)
   * @param {number} duration - Animation duration in seconds (default 0.6)
   */
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

  /**
   * Animates floating upward XP text with fade out effect
   * @param {Element} element - DOM element to animate (typically text showing XP gain)
   * @param {number} duration - Animation duration in seconds (default 0.8)
   */
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

  /**
   * Animates a level-up celebration effect with scale, rotation, and brightness
   * Uses spring physics for natural, elastic motion rather than linear timing
   * @param {Element} element - DOM element to animate (typically an icon/avatar)
   */
  const animateLevelUp = (element) => {
    if (!element) return;
    animate(
      element,
      {
        scale: [1, 1.3, 1],
        rotate: [0, 360, 0],
        filter: ['brightness(1)', 'brightness(1.5)', 'brightness(1)'],
      },
      { type: 'spring', stiffness: 200, damping: 10 }
    );
  };

  /**
   * Animates an avatar sliding horizontally along a journey path
   * @param {Element} element - DOM element to animate
   * @param {number} fromX - Starting X position in pixels
   * @param {number} toX - Ending X position in pixels
   * @param {number} duration - Animation duration in seconds (default 1.2)
   */
  const animateJourneySlide = (element, fromX, toX, duration = 1.2) => {
    if (!element) return;
    animate(
      element,
      { x: [fromX, toX] },
      { duration, easing: 'ease-out' }
    );
  };

  /**
   * Animates a pulsing scale effect for streak indicators
   * Continuously loops with a 2-second delay between pulses
   * @param {Element} element - DOM element to animate (typically a streak counter)
   */
  const animateStreakPulse = (element) => {
    if (!element) return;
    animate(
      element,
      { scale: [1, 1.05, 1] },
      { duration: 0.5, repeat: Infinity, repeatDelay: 2 }
    );
  };

  return {
    animateProgressBar,
    animateRadialArc,
    animateAvatarWave,
    animateXpFloat,
    animateLevelUp,
    animateJourneySlide,
    animateStreakPulse,
  };
}
