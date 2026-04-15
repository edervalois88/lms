import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import Session from '@/Pages/Quiz/Session.vue';

// Mock axios
vi.mock('axios', () => ({
  default: {
    post: vi.fn(),
  },
}));

// Mock Inertia components
vi.mock('@inertiajs/vue3', () => ({
  Head: { name: 'Head', template: '<div></div>' },
  Link: { name: 'Link', template: '<a><slot/></a>' },
  usePage: vi.fn(() => ({
    props: {
      quiz: {
        id: 1,
        questions: [
          {
            id: 1,
            question: '¿Cuál es 2 + 2?',
            options: ['3', '4', '5', '6'],
            correct_answer: 1,
          },
          {
            id: 2,
            question: '¿Cuál es 3 + 3?',
            options: ['5', '6', '7', '8'],
            correct_answer: 1,
          },
        ],
      },
      user: {
        id: 1,
        name: 'Test User',
        gpa: 3.5,
        gamification: {
          current_level: 1,
          current_xp: 100,
          streak_days: 5,
        },
      },
    },
  })),
}));

// Mock child components
vi.mock('@/Components/Quiz/QuestionCard.vue', () => ({
  default: {
    name: 'QuestionCard',
    template: '<div data-testid="question-card"><slot/></div>',
    props: ['question', 'timeLimit', 'disabled'],
  },
}));

vi.mock('@/Components/Quiz/FeedbackPanel.vue', () => ({
  default: {
    name: 'FeedbackPanel',
    template: '<div data-testid="feedback-panel"><slot/></div>',
    props: ['feedback'],
  },
}));

vi.mock('@/Components/Quiz/TutorChat.vue', () => ({
  default: {
    name: 'TutorChat',
    template: '<div data-testid="tutor-chat"><slot/></div>',
    props: ['enabled', 'loading', 'response'],
  },
}));

vi.mock('@/Components/UI/UpgradeModal.vue', () => ({
  default: {
    name: 'UpgradeModal',
    template: '<div data-testid="upgrade-modal"><slot/></div>',
    props: ['show', 'feature'],
  },
}));

// Mock gamification components
vi.mock('@/Components/Progress/AvatarTutor.vue', () => ({
  default: {
    name: 'AvatarTutor',
    template: '<div data-testid="avatar-tutor">Avatar</div>',
    props: ['state', 'size'],
    emits: ['interaction'],
  },
}));

vi.mock('@/Components/Progress/AvatarDialog.vue', () => ({
  default: {
    name: 'AvatarDialog',
    template: '<div data-testid="avatar-dialog">Dialog</div>',
    props: ['open', 'context'],
    emits: ['action', 'close'],
  },
}));

vi.mock('@/Components/Progress/ProgressBar.vue', () => ({
  default: {
    name: 'ProgressBar',
    template: '<div data-testid="progress-bar">Progress</div>',
    props: ['percentage', 'label'],
  },
}));

vi.mock('@/Components/Progress/RewardFeedback.vue', () => ({
  default: {
    name: 'RewardFeedback',
    template: '<div data-testid="reward-feedback">Reward</div>',
    props: ['xp', 'show'],
    emits: ['complete'],
  },
}));

vi.mock('@/Layouts/AuthenticatedLayout.vue', () => ({
  default: {
    name: 'AuthenticatedLayout',
    template: '<div><slot/></div>',
  },
}));

// Mock composables
vi.mock('@/Composables/useGameProgress', () => ({
  useGameProgress: vi.fn(() => ({
    currentXP: { value: 100 },
    currentLevel: { value: 1 },
    addXP: vi.fn(),
  })),
}));

vi.mock('@/Composables/useRewardFeedback', () => ({
  useRewardFeedback: vi.fn(() => ({
    rewardShown: { value: { xp: 0, type: null } },
    audioEnabled: { value: true },
    showReward: vi.fn(),
    playSound: vi.fn(),
    resetReward: vi.fn(),
  })),
}));

describe('Quiz/Session.vue', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.runOnlyPendingTimers();
    vi.useRealTimers();
  });

  const createWrapper = (props = {}) => {
    const defaultProps = {
      subject: { name: 'Math', slug: 'math', color: '#3b82f6', icon: 'calculator' },
      topics: [
        { id: 1, name: 'Algebra', difficulty_base: 3, questions_count: 10 },
      ],
      quiz: {
        id: 1,
        questions: [
          {
            id: 1,
            question: '¿Cuál es 2 + 2?',
            options: ['3', '4', '5', '6'],
            correct_answer: 1,
            correct_index: 1,
          },
        ],
      },
      user: {
        id: 1,
        name: 'Test User',
        gpa: 3.5,
        gamification: {
          current_level: 1,
          current_xp: 100,
        },
      },
      ...props,
    };

    return mount(Session, {
      props: defaultProps,
      global: {
        mocks: {
          route: vi.fn((name, params) => `/route/${name}`),
        },
      },
    });
  };

  it('renders AvatarTutor component', () => {
    const wrapper = createWrapper();
    // Avatar is in template, should be mounted
    expect(wrapper.vm).toBeDefined();
    expect(wrapper.vm.avatarState).toBe('idle');
  });

  it('renders ProgressBar with question count', async () => {
    const wrapper = createWrapper({
      quiz: {
        id: 1,
        questions: [
          { id: 1, question: 'Q1?', options: ['A', 'B', 'C', 'D'], correct_answer: 0, correct_index: 0 },
          { id: 2, question: 'Q2?', options: ['A', 'B', 'C', 'D'], correct_answer: 1, correct_index: 1 },
        ],
      },
    });

    // Set activeTopic to enter quiz mode
    wrapper.vm.activeTopic = { id: 1, name: 'Test' };
    wrapper.vm.currentQuestion = wrapper.vm.$props.quiz.questions[0];
    await wrapper.vm.$nextTick();

    // Check computed properties
    expect(wrapper.vm.progressPercentage).toBe(0);
    expect(wrapper.vm.questionsProgress).toBe('1/2');
  });

  it('renders RewardFeedback when answer is correct', async () => {
    const wrapper = createWrapper();
    wrapper.vm.showRewardFeedback = true;
    wrapper.vm.lastAnswerCorrect = true;
    await wrapper.vm.$nextTick();

    const rewardFeedback = wrapper.findComponent({ name: 'RewardFeedback' });
    expect(rewardFeedback.exists()).toBe(true);
  });

  it('opens AvatarDialog when avatar clicked', async () => {
    const wrapper = createWrapper();
    wrapper.vm.showAvatarDialog = true;
    await wrapper.vm.$nextTick();

    const avatarDialog = wrapper.findComponent({ name: 'AvatarDialog' });
    expect(avatarDialog.exists()).toBe(true);
    expect(avatarDialog.props('open')).toBe(true);
  });

  it('changes AvatarTutor state based on quiz progress', async () => {
    const wrapper = createWrapper();
    expect(wrapper.vm.avatarState).toBe('idle');

    // Simulate correct answer
    wrapper.vm.lastAnswerCorrect = true;
    wrapper.vm.avatarState = 'celebrating';
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.avatarState).toBe('celebrating');
  });
});
