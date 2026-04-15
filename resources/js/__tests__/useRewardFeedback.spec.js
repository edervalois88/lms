// resources/js/__tests__/useRewardFeedback.spec.js
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { useRewardFeedback, _resetState } from '@/Composables/useRewardFeedback';
import * as avatarMessages from '@/Utils/avatarMessages';

// Mock the avatarMessages module
vi.mock('@/Utils/avatarMessages', () => ({
  getContextualMessage: vi.fn((context, sentiment) => `Message: ${context}-${sentiment}`),
}));

describe('useRewardFeedback', () => {
  beforeEach(() => {
    // Reset mocks and state before each test
    vi.clearAllMocks();
    _resetState();
  });

  afterEach(() => {
    vi.clearAllMocks();
    _resetState();
  });

  it('should export a useRewardFeedback function that returns an object with showReward method', () => {
    const composable = useRewardFeedback();
    expect(typeof composable.showReward).toBe('function');
  });

  it('should export a useRewardFeedback function that returns an object with getRewardMessage method', () => {
    const composable = useRewardFeedback();
    expect(typeof composable.getRewardMessage).toBe('function');
  });

  it('should export a useRewardFeedback function that returns an object with playSound method', () => {
    const composable = useRewardFeedback();
    expect(typeof composable.playSound).toBe('function');
  });

  it('showReward should set reward state with correct xp amount and type', () => {
    const composable = useRewardFeedback();

    composable.showReward(100, 'reward');
    expect(composable.rewardShown.value.xp).toBe(100);
    expect(composable.rewardShown.value.type).toBe('reward');

    composable.showReward(50, 'correct');
    expect(composable.rewardShown.value.xp).toBe(50);
    expect(composable.rewardShown.value.type).toBe('correct');
  });

  it('getRewardMessage should return message from getContextualMessage utility', () => {
    const composable = useRewardFeedback();

    const message = composable.getRewardMessage('quiz', 'correct');
    expect(avatarMessages.getContextualMessage).toHaveBeenCalledWith('quiz', 'correct');
    expect(message).toBe('Message: quiz-correct');
  });

  it('playSound should call Web Audio API when audioEnabled is true', () => {
    // Create a mock AudioContext
    const mockGainNode = {
      gain: {
        setValueAtTime: vi.fn(),
        exponentialRampToValueAtTime: vi.fn(),
      },
      connect: vi.fn(),
    };

    const mockOscillator = {
      frequency: { setValueAtTime: vi.fn() },
      connect: vi.fn(),
      start: vi.fn(),
      stop: vi.fn(),
    };

    const mockAudioContext = {
      createGain: vi.fn().mockReturnValue(mockGainNode),
      createOscillator: vi.fn().mockReturnValue(mockOscillator),
      destination: {},
      currentTime: 0,
    };

    // Mock window.AudioContext BEFORE creating composable
    const originalAudioContext = window.AudioContext;
    window.AudioContext = vi.fn(function() {
      return mockAudioContext;
    });

    try {
      const composable = useRewardFeedback();
      composable.playSound('reward');

      expect(mockAudioContext.createOscillator).toHaveBeenCalled();
      expect(mockAudioContext.createGain).toHaveBeenCalled();
    } finally {
      window.AudioContext = originalAudioContext;
    }
  });

  it('playSound should not play sound when audioEnabled is false', () => {
    const composable = useRewardFeedback();

    // Create a mock AudioContext
    const mockGainNode = {
      gain: { setTargetAtTime: vi.fn(), exponentialRampToValueAtTime: vi.fn() },
      connect: vi.fn(),
    };

    const mockOscillator = {
      frequency: { setValueAtTime: vi.fn() },
      connect: vi.fn(),
      start: vi.fn(),
      stop: vi.fn(),
    };

    const mockAudioContext = {
      createGain: vi.fn().mockReturnValue(mockGainNode),
      createOscillator: vi.fn().mockReturnValue(mockOscillator),
      destination: {},
      currentTime: 0,
    };

    // Mock window.AudioContext
    const originalAudioContext = window.AudioContext;
    window.AudioContext = vi.fn().mockReturnValue(mockAudioContext);

    try {
      // Disable audio before playing sound
      composable.toggleAudio();
      expect(composable.audioEnabled.value).toBe(false);

      // Try to play sound
      composable.playSound('reward');

      // Should not call createOscillator
      expect(mockAudioContext.createOscillator).not.toHaveBeenCalled();
    } finally {
      window.AudioContext = originalAudioContext;
    }
  });

  it('resetReward should clear reward state', () => {
    const composable = useRewardFeedback();

    // Set a reward
    composable.showReward(100, 'reward');
    expect(composable.rewardShown.value.xp).toBe(100);

    // Reset it
    composable.resetReward();
    expect(composable.rewardShown.value.xp).toBe(0);
    expect(composable.rewardShown.value.type).toBeNull();
  });

  it('toggleAudio should toggle audioEnabled state', () => {
    const composable = useRewardFeedback();

    const initialValue = composable.audioEnabled.value;
    composable.toggleAudio();
    expect(composable.audioEnabled.value).toBe(!initialValue);

    composable.toggleAudio();
    expect(composable.audioEnabled.value).toBe(initialValue);
  });
});
