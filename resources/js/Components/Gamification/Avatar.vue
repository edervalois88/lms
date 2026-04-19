<template>
  <svg
    :style="{ width: sizeConfig.width, height: sizeConfig.height }"
    :class="['avatar', `avatar-${state}`]"
    viewBox="0 0 140 160"
    xmlns="http://www.w3.org/2000/svg"
    :data-color="equipped.color"
    :data-outfit="equipped.outfit"
    :data-pet="equipped.pet"
  >
    <!-- SVG rendering to be implemented in Task 3 -->
    <!-- Body base group placeholder -->
    <g id="avatar-body">
      <!-- Layers: base → outfit → accessories → pet (Task 3) -->
    </g>
  </svg>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  equipped: {
    color: string;
    outfit: string;
    accessories: string[];
    pet: string;
    background?: string;
  };
  state?: 'idle' | 'happy' | 'tired' | 'thinking';
  size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
  state: 'idle',
  size: 'md'
});

const sizeConfig = computed(() => {
  const sizes = {
    sm: { width: '60px', height: '80px' },
    md: { width: '120px', height: '140px' },
    lg: { width: '200px', height: '240px' }
  };
  return sizes[props.size] || sizes.md;
});
</script>

<style scoped>
/* Animation states - actual keyframes to be added in Task 4 */
.avatar-idle {
  /* idle-float animation (Task 4) */
}

.avatar-happy {
  /* happy-bounce animation (Task 4) */
}

.avatar-tired {
  /* tired state styling (Task 4) */
}

.avatar-thinking {
  /* thinking-sway animation (Task 4) */
}
</style>
