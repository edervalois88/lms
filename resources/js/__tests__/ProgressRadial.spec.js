// resources/js/__tests__/ProgressRadial.spec.js
import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ProgressRadial from '../Components/Progress/ProgressRadial.vue';

describe('ProgressRadial.vue', () => {
  it('renders SVG with 3 ring groups', () => {
    const wrapper = mount(ProgressRadial, {
      props: {
        gpaActual: 10,
        gpaMeta: 20,
        strongSubjects: 3,
        totalSubjects: 5,
        streakDays: 7,
        level: 2,
        rank: 'Aprendiz',
      }
    });
    const svg = wrapper.find('svg');
    expect(svg.exists()).toBe(true);
    // 3 background rings + 3 progress rings = 6 circle elements minimum
    expect(wrapper.findAll('circle').length).toBeGreaterThanOrEqual(6);
  });

  it('displays level and rank in center text', () => {
    const wrapper = mount(ProgressRadial, {
      props: {
        gpaActual: 15,
        gpaMeta: 20,
        strongSubjects: 2,
        totalSubjects: 4,
        streakDays: 5,
        level: 3,
        rank: 'Adept',
      }
    });
    expect(wrapper.text()).toContain('3');
    expect(wrapper.text()).toContain('Adept');
  });

  it('renders legend with GPA, Materias, Racha labels', () => {
    const wrapper = mount(ProgressRadial, {
      props: {
        gpaActual: 8,
        gpaMeta: 20,
        strongSubjects: 1,
        totalSubjects: 3,
        streakDays: 0,
        level: 1,
        rank: 'Novato',
      }
    });
    expect(wrapper.text()).toContain('GPA');
    expect(wrapper.text()).toContain('Materias');
    expect(wrapper.text()).toContain('Racha');
  });

  it('clamps gpaPercent to 100% when over goal', () => {
    const wrapper = mount(ProgressRadial, {
      props: {
        gpaActual: 25,
        gpaMeta: 20,
        strongSubjects: 5,
        totalSubjects: 5,
        streakDays: 30,
        level: 5,
        rank: 'Maestro',
      }
    });
    // gpaPercent computed should clamp at 1.0
    expect(wrapper.vm.gpaPercent).toBe(1);
  });

  it('shows green gpa ring when score >= 80% of goal', () => {
    const wrapper = mount(ProgressRadial, {
      props: {
        gpaActual: 18,
        gpaMeta: 20,
        strongSubjects: 3,
        totalSubjects: 5,
        streakDays: 10,
        level: 4,
        rank: 'Experto',
      }
    });
    expect(wrapper.vm.gpaStrokeColor).toBe('#10b981');
  });

  it('shows red gpa ring when score < 50% of goal', () => {
    const wrapper = mount(ProgressRadial, {
      props: {
        gpaActual: 5,
        gpaMeta: 20,
        strongSubjects: 0,
        totalSubjects: 5,
        streakDays: 0,
        level: 1,
        rank: 'Novato',
      }
    });
    expect(wrapper.vm.gpaStrokeColor).toBe('#ef4444');
  });
});
