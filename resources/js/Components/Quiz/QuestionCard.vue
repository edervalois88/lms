<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    question: Object,
    timeLimit: {
        type: Number,
        default: 60
    },
    disabled: Boolean
});

const emit = defineEmits(['answered', 'timeExpired']);

const timeLeft = ref(props.timeLimit);
const selectedIndex = ref(null);
const timerInterval = ref(null);

const startTimer = () => {
    clearInterval(timerInterval.value);
    timeLeft.value = props.timeLimit;
    timerInterval.value = setInterval(() => {
        if (timeLeft.value > 0 && !props.disabled) {
            timeLeft.value--;
        } else if (timeLeft.value === 0 && !props.disabled) {
            clearInterval(timerInterval.value);
            emit('timeExpired');
        }
    }, 1000);
};

const stopTimer = () => {
    clearInterval(timerInterval.value);
};

const selectOption = (index) => {
    if (props.disabled) return;
    selectedIndex.value = index;
    stopTimer();
    emit('answered', index);
};

onMounted(() => {
    startTimer();
});

onUnmounted(() => {
    stopTimer();
});

// Restart timer if question changes
watch(() => props.question?.id, () => {
    selectedIndex.value = null;
    startTimer();
});

// Progress circle calculations
const strokeDasharray = 283; // 2 * PI * r (r=45)
</script>

<template>
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
        <!-- Skeleton Loader -->
        <div v-if="!question" class="p-8 animate-pulse">
            <div class="h-6 bg-gray-200 rounded w-3/4 mb-8"></div>
            <div class="space-y-4">
                <div v-for="i in 4" :key="i" class="h-14 bg-gray-100 rounded-2xl"></div>
            </div>
        </div>

        <div v-else>
            <!-- Progress Header -->
            <div class="bg-gray-50 px-8 py-4 flex items-center justify-between border-b border-gray-100">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Pregunta</span>
                
                <!-- Circular Timer -->
                <div class="relative w-12 h-12">
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                        <circle
                            class="text-gray-200 stroke-current"
                            stroke-width="8"
                            fill="transparent"
                            r="45"
                            cx="50"
                            cy="50"
                        />
                        <circle
                            class="text-orange-500 stroke-current transition-all duration-1000 ease-linear"
                            stroke-width="8"
                            :stroke-dasharray="strokeDasharray"
                            :stroke-dashoffset="283 - (timeLeft / timeLimit) * 283"
                            stroke-linecap="round"
                            fill="transparent"
                            r="45"
                            cx="50"
                            cy="50"
                        />
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center text-xs font-black text-gray-700">
                        {{ timeLeft }}
                    </span>
                </div>
            </div>

            <!-- Content -->
            <div class="p-8 md:p-12">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 leading-tight mb-10">
                    {{ question.body }}
                </h2>

                <div class="grid grid-cols-1 gap-4">
                    <button
                        v-for="(option, index) in question.options"
                        :key="index"
                        @click="selectOption(index)"
                        :disabled="disabled"
                        class="relative flex items-center p-5 text-left rounded-2xl border-2 transition-all duration-200 group"
                        :class="[
                            disabled 
                                ? (index === question.correct_index 
                                    ? 'border-green-500 bg-green-50 text-green-900' 
                                    : (index === selectedIndex ? 'border-red-500 bg-red-50 text-red-900' : 'border-gray-100 bg-gray-50 opacity-50'))
                                : 'border-gray-100 hover:border-orange-500 hover:bg-orange-50 text-gray-700'
                        ]"
                    >
                        <span 
                            class="flex-shrink-0 w-8 h-8 rounded-full border-2 flex items-center justify-center mr-4 font-bold text-sm transition-colors"
                            :class="[
                                disabled && index === question.correct_index ? 'bg-green-500 border-green-500 text-white' :
                                disabled && index === selectedIndex ? 'bg-red-500 border-red-500 text-white' :
                                'border-gray-200 text-gray-400 group-hover:border-orange-500 group-hover:text-orange-600'
                            ]"
                        >
                            {{ String.fromCharCode(65 + index) }}
                        </span>
                        
                        <span class="text-lg font-medium">{{ option }}</span>

                        <!-- Icon State -->
                        <div v-if="disabled" class="ml-auto">
                            <i v-if="index === question.correct_index" class="fa-solid fa-circle-check text-green-500 text-xl"></i>
                            <i v-else-if="index === selectedIndex" class="fa-solid fa-circle-xmark text-red-500 text-xl"></i>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
button:disabled {
    cursor: default;
}
</style>
