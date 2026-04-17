import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useAchievementsStore = defineStore('achievements', () => {
  // State
  const completed = ref([]);
  const pendingToast = ref(null);

  // Computed
  const isCompleted = computed(() => {
    return (id) => completed.value.some((a) => a.id === id);
  });

  // Actions
  const unlock = (achievement) => {
    if (isCompleted.value(achievement.id)) return;
    completed.value.push(achievement);
    pendingToast.value = achievement;
  };

  const clearToast = () => {
    pendingToast.value = null;
  };

  const hydrateFromServer = (achievementIds) => {
    if (!Array.isArray(achievementIds)) return;
    achievementIds.forEach((id) => {
      if (id && !isCompleted.value(id)) {
        completed.value.push({ id, unlockedAt: null });
      }
    });
  };

  return {
    completed,
    pendingToast,
    isCompleted,
    unlock,
    clearToast,
    hydrateFromServer,
  };
});
