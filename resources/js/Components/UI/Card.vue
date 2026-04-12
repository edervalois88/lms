<script setup>
const props = defineProps({
    as: {
        type: String,
        default: 'div',
    },
    glow: {
        type: String,
        default: '', // orange | green | red
    },
    padded: {
        type: Boolean,
        default: true,
    },
});

const glowClass = {
    orange: 'shadow-[0_0_24px_rgba(255,165,0,0.25)] border-orange-500/40',
    green: 'shadow-[0_0_24px_rgba(34,197,94,0.25)] border-emerald-500/40',
    red: 'shadow-[0_0_24px_rgba(248,113,113,0.22)] border-rose-500/40',
};
</script>

<template>
    <component
        :is="as"
        class="rounded-3xl border bg-gray-900/80 backdrop-blur-sm"
        :class="[
            padded ? 'p-6 md:p-8' : '',
            glowClass[glow] || 'border-white/10 shadow-[0_0_20px_rgba(15,23,42,0.2)]',
        ]"
    >
        <slot />
    </component>
</template>
