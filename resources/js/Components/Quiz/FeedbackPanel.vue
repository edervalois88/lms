<script setup>
const props = defineProps({
    feedback: {
        type: Object,
        required: true,
    },
});

defineEmits(['next']);
</script>

<template>
    <div class="rounded-3xl border p-6 md:p-8 border-orange-200 bg-orange-50">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-orange-600">Feedback IA</p>
                <h3 class="text-xl font-black text-gray-900 mt-1">Análisis adaptativo</h3>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-widest" :class="feedback.metadatos?.nivel_sugerido === 'subir' ? 'bg-green-100 text-green-700' : (feedback.metadatos?.nivel_sugerido === 'bajar' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700')">
                {{ feedback.metadatos?.nivel_sugerido || 'mantener' }}
            </span>
        </div>

        <p class="mt-4 text-gray-800 font-medium leading-relaxed">
            {{ feedback.evaluacion?.feedback_especifico }}
        </p>

        <div class="mt-5 rounded-2xl bg-white/80 border border-gray-100 p-4">
            <h4 class="text-xs font-black uppercase tracking-widest text-gray-500 mb-2">Semblanza del tema</h4>
            <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">
                {{ feedback.evaluacion?.semblanza_tema }}
            </p>
        </div>

        <div class="mt-6 flex justify-end">
            <button
                type="button"
                @click="$emit('next')"
                class="px-6 py-3 rounded-xl bg-gray-900 text-white font-bold hover:bg-black transition-colors"
            >
                Siguiente
            </button>
        </div>
    </div>
</template>
