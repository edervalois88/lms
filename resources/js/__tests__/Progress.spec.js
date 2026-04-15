// resources/js/__tests__/Progress.spec.js
import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import ProgressIndex from '../Pages/Progress/Index.vue';

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
  Head: { name: 'Head', template: '<div></div>' },
  Link: { name: 'Link', template: '<a><slot /></a>' },
  usePage: vi.fn(() => ({
    props: {
      auth: {
        user: {
          gamification: {
            current_level: 1,
            rank: 'Novato',
            avatar_icon: '🎓',
          },
        },
      },
    },
  })),
}));

// Mock AuthenticatedLayout to render its default slot
vi.mock('@/Layouts/AuthenticatedLayout.vue', () => ({
  default: {
    name: 'AuthenticatedLayout',
    template: '<div><slot /></div>',
  },
}));

// Mock AvatarShowcase
vi.mock('@/Components/Progress/AvatarShowcase.vue', () => ({
  default: {
    name: 'AvatarShowcase',
    template: '<div data-testid="avatar-showcase"></div>',
    props: ['icon', 'cosmetics'],
  },
}));

// Mock ProgressRadial
vi.mock('@/Components/Progress/ProgressRadial.vue', () => ({
  default: {
    name: 'ProgressRadial',
    template: '<div data-testid="progress-radial"></div>',
    props: ['gpaActual', 'gpaMeta', 'strongSubjects', 'totalSubjects', 'streakDays', 'level', 'rank', 'size'],
  },
}));

const defaultProps = {
  mastery: [
    { subject: 'Matemáticas', mastery_score: 7.5, total_attempts: 20, correct_attempts: 15, trend: 'up', subject_color: '#3b82f6' },
    { subject: 'Historia', mastery_score: 4.0, total_attempts: 10, correct_attempts: 4, trend: 'stable', subject_color: '#f59e0b' },
  ],
  exams_history: [
    { id: 1, type: 'UNAM', score: 85, created_at: '2026-04-01T00:00:00Z' },
  ],
  exams_pagination: { current_page: 1, last_page: 1, per_page: 10, total: 1 },
  projection: { projected_score: 14, confidence: 'Media', gap_to_goal: 6 },
  streak_days: 5,
  weekly_stats: { questions_answered: 30 },
};

// Shared mount options factory
const mountOptions = () => ({
  props: defaultProps,
  global: {
    stubs: {
      Head: true,
      Link: true,
    },
    mocks: {
      route: vi.fn((name, params) => `/${name}/${params ?? ''}`),
    },
  },
});

describe('Progress/Index.vue', () => {
  it('renders AvatarShowcase component', () => {
    const wrapper = mount(ProgressIndex, mountOptions());
    expect(wrapper.findComponent({ name: 'AvatarShowcase' }).exists()).toBe(true);
  });

  it('renders ProgressRadial component', () => {
    const wrapper = mount(ProgressIndex, mountOptions());
    expect(wrapper.findComponent({ name: 'ProgressRadial' }).exists()).toBe(true);
  });

  it('passes correct strongSubjects count to ProgressRadial', () => {
    const wrapper = mount(ProgressIndex, mountOptions());
    // mastery_score 7.5 >= 7 (strong), 4.0 < 7 (weak) → 1 strong
    expect(wrapper.vm.strongSubjects).toBe(1);
  });

  it('computes projected score correctly for ProgressRadial', () => {
    const wrapper = mount(ProgressIndex, mountOptions());
    expect(wrapper.vm.projectedScore).toBe(14);
  });

  it('renders achievement timeline section', () => {
    const wrapper = mount(ProgressIndex, mountOptions());
    expect(wrapper.find('.achievement-timeline').exists()).toBe(true);
  });

  it('generates achievements from mastery and streak data', () => {
    const wrapper = mount(ProgressIndex, mountOptions());
    // streak_days = 5 → should generate streak achievement
    expect(wrapper.vm.achievements.some(a => a.type === 'streak')).toBe(true);
  });
});
