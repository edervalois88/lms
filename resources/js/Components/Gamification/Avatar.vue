<template>
  <svg
    :style="{ width: sizeConfig.width, height: sizeConfig.height }"
    :class="['avatar', `avatar-${state}`]"
    viewBox="0 0 140 160"
    xmlns="http://www.w3.org/2000/svg"
    :data-color="equipped.color"
    :data-outfit="equipped.outfit"
    :data-accessories="equipped.accessories.join(',')"
    :data-pet="equipped.pet"
  >
    <!-- Body Base Layer -->
    <g id="avatar-body">
      <!-- Head -->
      <polygon
        points="70,15 95,32 95,65 70,82 45,65 45,32"
        :fill="colorStyle.color"
        stroke="#5a3d2a"
        stroke-width="1"
      />
      <!-- Eyes -->
      <circle cx="60" cy="45" r="3" :fill="colorStyle.eyeColor || '#2c1810'" />
      <circle cx="80" cy="45" r="3" :fill="colorStyle.eyeColor || '#2c1810'" />
      <!-- Eye shine -->
      <circle cx="61" cy="44" r="1" fill="#ffffff" opacity="0.8" />
      <circle cx="81" cy="44" r="1" fill="#ffffff" opacity="0.8" />
      <!-- Mouth -->
      <path d="M 65 58 Q 70 62 75 58" stroke="#5a3d2a" stroke-width="1" fill="none" />

      <!-- Torso (base) -->
      <polygon
        points="45,82 70,100 95,82 95,115 70,133 45,115"
        :fill="colorStyle.color"
        stroke="#5a3d2a"
        stroke-width="1"
      />

      <!-- Left Arm -->
      <polygon
        points="20,95 45,108 45,125 20,112"
        :fill="colorStyle.color"
        stroke="#5a3d2a"
        stroke-width="1"
      />

      <!-- Right Arm -->
      <polygon
        points="95,108 120,95 120,112 95,125"
        :fill="colorStyle.color"
        stroke="#5a3d2a"
        stroke-width="1"
      />

      <!-- Left Leg -->
      <polygon
        points="55,115 70,125 70,150 55,140"
        fill="#1a1a2e"
        stroke="#000000"
        stroke-width="1"
      />

      <!-- Right Leg -->
      <polygon
        points="70,133 85,125 85,150 70,158"
        fill="#1a1a2e"
        stroke="#000000"
        stroke-width="1"
      />
    </g>

    <!-- Outfit Layer -->
    <g v-if="outfitStyle" id="avatar-outfit">
      <!-- Outfit torso covering -->
      <polygon
        points="45,82 70,100 95,82 95,115 70,133 45,115"
        :fill="outfitStyle.color"
        stroke="none"
        opacity="0.95"
      />
      <!-- Outfit detail line (middle seam) -->
      <line x1="70" y1="100" x2="70" y2="133" :stroke="outfitStyle.color" stroke-width="1" opacity="0.7" />

      <!-- Outfit-specific details -->
      <!-- Emblem (if hasStripes or emblem) -->
      <circle
        v-if="outfitStyle.details?.emblem"
        cx="70"
        cy="105"
        r="4"
        fill="#ffd700"
        opacity="0.8"
      />
      <!-- Stars (if hasStars) -->
      <g v-if="outfitStyle.details?.hasStars">
        <circle cx="60" cy="95" r="2" fill="#ffd700" opacity="0.7" />
        <circle cx="80" cy="95" r="2" fill="#ffd700" opacity="0.7" />
        <circle cx="70" cy="115" r="2" fill="#ffd700" opacity="0.7" />
      </g>
    </g>

    <!-- Accessories Layer -->
    <g v-for="(acc, idx) in accessoryStyles" :key="`acc-${idx}`" id="avatar-accessory">
      <!-- Glasses -->
      <g v-if="acc.type === 'glasses'">
        <rect x="52" y="40" width="6" height="4" :fill="acc.color" stroke="#1f2937" stroke-width="0.5" />
        <rect x="82" y="40" width="6" height="4" :fill="acc.color" stroke="#1f2937" stroke-width="0.5" />
        <line x1="58" y1="42" x2="82" y2="42" stroke="#1f2937" stroke-width="0.5" />
      </g>
      <!-- Crown -->
      <g v-if="acc.type === 'crown'">
        <polygon points="45,10 70,-5 95,10 85,20 60,12 45,20" :fill="acc.color" stroke="#daa520" stroke-width="1" />
        <circle cx="70" cy="5" r="3" fill="#ffd700" />
      </g>
      <!-- Backpack -->
      <g v-if="acc.type === 'backpack'">
        <rect x="10" y="100" width="15" height="20" :fill="acc.color" stroke="#333333" stroke-width="1" />
        <line x1="15" y1="100" x2="15" y2="120" stroke="#666666" stroke-width="0.5" />
      </g>
      <!-- Scarf -->
      <g v-if="acc.type === 'scarf'">
        <path d="M 50 75 Q 45 85 50 95" :stroke="acc.color" stroke-width="3" fill="none" />
        <path d="M 90 75 Q 95 85 90 95" :stroke="acc.color" stroke-width="3" fill="none" />
      </g>
    </g>

    <!-- Pet Layer -->
    <g v-if="petStyle" id="avatar-pet">
      <circle
        cx="100"
        cy="85"
        r="8"
        :fill="petStyle.color"
        :stroke="petStyle.color"
        stroke-width="1"
        opacity="0.9"
      />
      <!-- Pet eye -->
      <circle cx="103" cy="82" r="2" fill="#000000" opacity="0.8" />
    </g>
  </svg>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { COSMETIC_DEFINITIONS, getDefaultCosmetic } from '@/Data/cosmetics.js';

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

const colorStyle = computed(() => {
  const code = props.equipped.color || getDefaultCosmetic('color');
  return COSMETIC_DEFINITIONS.colors[code] || COSMETIC_DEFINITIONS.colors[getDefaultCosmetic('color')];
});

const outfitStyle = computed(() => {
  const code = props.equipped.outfit || getDefaultCosmetic('outfit');
  return COSMETIC_DEFINITIONS.outfits[code] || COSMETIC_DEFINITIONS.outfits[getDefaultCosmetic('outfit')];
});

const accessoryStyles = computed(() => {
  return (props.equipped.accessories || [])
    .map(code => COSMETIC_DEFINITIONS.accessories[code])
    .filter(Boolean)
    .slice(0, 2);
});

const petStyle = computed(() => {
  const code = props.equipped.pet || getDefaultCosmetic('pet');
  return COSMETIC_DEFINITIONS.pets[code] || COSMETIC_DEFINITIONS.pets[getDefaultCosmetic('pet')];
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
