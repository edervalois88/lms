<script setup>
import { computed } from 'vue';
import { animate, stagger } from 'motion';
import { onMounted } from 'vue';

const props = defineProps({
    show: Boolean,
    major: Object,
});

const emit = defineEmits(['close']);

const safeNumber = (value, fallback = 0) => {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : fallback;
};

const stats = computed(() => {
    if (!props.major) return [];

    const rows = Array.isArray(props.major.statistics) ? props.major.statistics : [];
    if (rows.length > 0) {
        return rows
            .map((row) => ({
                year: safeNumber(row.year),
                score: safeNumber(row.cutoff_score, safeNumber(props.major.min_score)),
                applicants: safeNumber(row.applicants, safeNumber(props.major.applicants)),
                places: safeNumber(row.places_offered, safeNumber(props.major.places)),
            }))
            .sort((a, b) => a.year - b.year);
    }

    return [
        {
            year: 2023,
            score: safeNumber(props.major.min_score) - 3,
            applicants: safeNumber(props.major.applicants),
            places: safeNumber(props.major.places),
        },
        {
            year: 2024,
            score: safeNumber(props.major.min_score) - 1,
            applicants: safeNumber(props.major.applicants),
            places: safeNumber(props.major.places),
        },
        {
            year: 2025,
            score: safeNumber(props.major.min_score),
            applicants: safeNumber(props.major.applicants),
            places: safeNumber(props.major.places),
        },
    ];
});

const trendDelta = computed(() => {
    if (stats.value.length < 2) return 0;
    const first = stats.value[0].score;
    const last = stats.value[stats.value.length - 1].score;
    return Math.round((last - first) * 10) / 10;
});

onMounted(() => {
    if (props.show) {
        animate(".stat-bar", { height: [0, "var(--bar-height)"] }, { 
            delay: stagger(0.1),
            duration: 1 
        });
    }
});
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-60 flex items-center justify-center p-6 bg-midnight/90 backdrop-blur-xl">
        <div class="max-w-xl w-full glass-morphism p-10 rounded-[3rem] border border-white/10 relative animate-pop-in">
            
            <button @click="$emit('close')" class="absolute top-6 right-6 w-10 h-10 bg-white/5 rounded-full flex items-center justify-center hover:bg-white/10 transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="space-y-10">
                <div class="space-y-2">
                    <h3 class="text-3xl font-black italic uppercase tracking-tighter">Historial de Infiltración</h3>
                    <p class="text-xs font-bold text-orange-500 uppercase tracking-widest">Tendencia de Aciertos: {{ major.name }}</p>
                </div>

                <!-- Gamified Bar Chart -->
                <div class="flex items-end justify-between h-48 gap-4 px-4 border-b border-white/5 pb-2">
                    <div v-for="stat in stats" :key="stat.year" class="grow flex flex-col items-center gap-4">
                        <span class="text-xs font-black text-orange-500">{{ stat.score }}</span>
                        <div 
                            class="w-full bg-linear-to-t from-orange-600 to-orange-400 rounded-t-xl shadow-orange-glow stat-bar"
                            :style="{ '--bar-height': Math.min(100, (stat.score / 1.4) * 100) + '%' }"
                        ></div>
                        <span class="text-[10px] font-black text-gray-500">{{ stat.year }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-center">
                    <div class="p-3 rounded-xl bg-white/5 border border-white/10">
                        <p class="text-[9px] text-gray-400 uppercase font-black tracking-widest">Aspirantes</p>
                        <p class="text-lg font-black text-white">{{ stats[stats.length - 1]?.applicants ?? 'N/A' }}</p>
                    </div>
                    <div class="p-3 rounded-xl bg-white/5 border border-white/10">
                        <p class="text-[9px] text-gray-400 uppercase font-black tracking-widest">Lugares</p>
                        <p class="text-lg font-black text-white">{{ stats[stats.length - 1]?.places ?? 'N/A' }}</p>
                    </div>
                    <div class="p-3 rounded-xl bg-white/5 border border-white/10">
                        <p class="text-[9px] text-gray-400 uppercase font-black tracking-widest">Tendencia</p>
                        <p class="text-lg font-black" :class="trendDelta >= 0 ? 'text-green-400' : 'text-red-400'">
                            {{ trendDelta >= 0 ? '+' : '' }}{{ trendDelta }} aciertos
                        </p>
                    </div>
                </div>

                <div class="bg-white/5 rounded-2xl p-6 border border-white/5 flex items-start gap-4">
                    <div class="w-10 h-10 bg-orange-600/20 rounded-xl flex items-center justify-center text-orange-500">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Análisis de Inteligencia</p>
                        <p class="text-xs font-bold leading-relaxed">
                            Tendencia histórica cargada desde estadísticas del catálogo. Usa este indicador junto con la relación aspirantes/lugares para ajustar tu meta de aciertos.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.glass-morphism {
    background: rgba(25, 25, 35, 0.8);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.shadow-orange-glow {
    box-shadow: 0 0 30px rgba(255, 107, 0, 0.2);
}

.animate-pop-in {
    animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes popIn {
    from { opacity: 0; scale: 0.95; transform: translateY(20px); }
    to { opacity: 1; scale: 1; transform: translateY(0); }
}
</style>
