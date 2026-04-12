<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    correct: Boolean,
    explanation: String,
    concept: String,
    topicDetail: {
        type: String,
        default: '',
    },
});

defineEmits(['next']);

const showExplanation = ref(!props.correct);
const showTopicDetail = ref(false);

const briefExplanation = computed(() => {
    const raw = (props.explanation || '').trim();
    if (!raw) {
        return 'Revisa la idea central del tema y vuelve a intentarlo con calma.';
    }

    const firstSentence = raw.split(/[.!?]\s/)[0]?.trim() || raw;
    return firstSentence.length > 180 ? `${firstSentence.slice(0, 177)}...` : firstSentence;
});

const motivationalMessages = [
    "¡Excelente trabajo! Sigue así.",
    "¡Exacto! Tienes una buena base en este tema.",
    "¡Correcto! Estás dominando esta área.",
    "¡Muy bien! Un paso más cerca de la UNAM.",
    "¡Impresionante! Tu esfuerzo está dando frutos."
];

const randomMessage = motivationalMessages[Math.floor(Math.random() * motivationalMessages.length)];
</script>

<template>
    <div 
        class="rounded-3xl p-6 md:p-8 transition-all duration-500 shadow-lg"
        :class="correct ? 'bg-green-50 border-2 border-green-100' : 'bg-red-50 border-2 border-red-100'"
    >
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-start md:items-center">
                <div 
                    class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4 shrink-0 animate-bounce"
                    :class="correct ? 'bg-green-500 text-white' : 'bg-red-500 text-white'"
                >
                    <i :class="correct ? 'fa-solid fa-star' : 'fa-solid fa-lightbulb'"></i>
                </div>
                <div>
                    <h3 
                        class="text-xl font-black"
                        :class="correct ? 'text-green-800' : 'text-red-800'"
                    >
                        {{ correct ? randomMessage : '¡No te desanimes! Aprende del error.' }}
                    </h3>
                    <p class="text-sm font-bold uppercase tracking-widest mt-1" :class="correct ? 'text-green-600/70' : 'text-red-600/70'">
                        CONCEPTO: {{ concept }}
                    </p>
                </div>
            </div>

            <button 
                @click="$emit('next')"
                class="px-8 py-4 rounded-2xl font-black text-lg transition-all transform hover:scale-105 active:scale-95 shadow-md flex items-center justify-center"
                :class="correct ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-red-600 text-white hover:bg-red-700'"
            >
                Siguiente
                <i class="fa-solid fa-arrow-right ml-3"></i>
            </button>
        </div>

        <!-- Explanation Panel -->
        <div 
            v-if="!correct || showExplanation"
            class="mt-6 pt-6 border-t border-opacity-20"
            :class="correct ? 'border-green-300' : 'border-red-300'"
        >
            <button 
                v-if="correct"
                @click="showExplanation = !showExplanation"
                class="text-sm font-bold underline mb-4 inline-block"
                :class="correct ? 'text-green-700' : 'text-red-700'"
            >
                {{ showExplanation ? 'Ocultar explicación' : '¿Por qué es correcto?' }}
            </button>

            <div class="animate-fade-in space-y-4">
                <div>
                    <h4 class="font-black text-sm uppercase tracking-wider mb-2 opacity-60">Explicación Breve</h4>
                    <p class="text-base leading-relaxed font-semibold" :class="correct ? 'text-green-900' : 'text-red-900'">
                        {{ briefExplanation }}
                    </p>
                </div>

                <button
                    type="button"
                    class="font-black text-sm underline"
                    :class="correct ? 'text-green-700' : 'text-red-700'"
                    @click="showTopicDetail = !showTopicDetail"
                >
                    {{ showTopicDetail ? 'Ocultar detalle del tema' : 'Ver detalle del tema' }}
                </button>

                <div v-if="showTopicDetail" class="rounded-2xl p-4 border" :class="correct ? 'border-green-200 bg-green-100/50' : 'border-red-200 bg-red-100/50'">
                    <h5 class="font-black text-xs uppercase tracking-widest mb-2 opacity-70">Detalle</h5>
                    <p class="text-sm leading-relaxed" :class="correct ? 'text-green-900' : 'text-red-900'">
                        {{ explanation }}
                    </p>
                    <p v-if="topicDetail" class="text-sm leading-relaxed mt-3" :class="correct ? 'text-green-900' : 'text-red-900'">
                        <span class="font-black uppercase text-xs tracking-wider opacity-70">Guía del tema:</span>
                        {{ topicDetail }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fade-in 0.3s ease-out; }
</style>
