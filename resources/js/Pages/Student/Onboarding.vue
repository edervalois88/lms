<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { animate, spring, stagger } from 'motion';
import { playSound } from '@/Utils/SoundService';
import MajorTrendsModal from '@/Components/Modals/MajorTrendsModal.vue';

const props = defineProps({
    universities: Array,
    vocational_questions: Array,
    vocational_result: Object,
    recommendations: Array,
    step: { type: String, default: 'welcome' }
});

// Wizard State
const currentStep = ref(props.step); // welcome, test, university, campus, major, summary
const testAnswers = ref({});
const selectedUni = ref(null);
const selectedCampus = ref(null);
const searchQuery = ref('');
const showTrendsModal = ref(false);

const form = useForm({
    major_id: null,
    study_hours_per_day: 4,
    gpa: 8.5,
});

// Logic
const goToStep = (step) => {
    currentStep.value = step;
    playSound('pop');
    animate(".step-content", { opacity: [0, 1], x: [20, 0] }, { duration: 0.5, easing: spring() });
};

const selectUni = (uni) => {
    selectedUni.value = uni;
    selectedCampus.value = null;
    form.major_id = null;
    goToStep('campus');
};

const selectCampus = (campus) => {
    selectedCampus.value = campus;
    form.major_id = null;
    goToStep('major');
};

const selectMajor = (id) => {
    form.major_id = id;
    playSound('click');
};

const submitTest = () => {
    playSound('success');
    router.post(route('onboarding.vocational.submit'), {
        answers: testAnswers.value
    }, {
        onSuccess: () => currentStep.value = 'results'
    });
};

const finishOnboarding = () => {
    playSound('success');
    form.post(route('onboarding.store'));
};

// Computed
const filteredMajors = computed(() => {
    if (!selectedCampus.value) return [];
    let list = selectedCampus.value.majors;
    if (searchQuery.value) {
        list = list.filter(m => m.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
    }
    return list;
});

const selectedMajorData = computed(() => {
    if (!form.major_id || !selectedCampus.value) return null;
    return selectedCampus.value.majors.find(m => m.id === form.major_id);
});

onMounted(() => {
    animate(".onboarding-container", { opacity: [0, 1], scale: [0.98, 1] }, { duration: 0.8 });
});
</script>

<template>
    <Head title="Misión: Onboarding - NexusEdu" />

    <div class="min-h-screen bg-midnight text-white selection:bg-orange-500/30 flex items-center justify-center p-6 font-sans">
        <div class="max-w-4xl w-full onboarding-container">
            
            <!-- Welcome Step -->
            <div v-if="currentStep === 'welcome'" class="text-center space-y-12 step-content">
                <div class="w-20 h-20 bg-orange-600 rounded-[2rem] mx-auto flex items-center justify-center text-4xl font-black shadow-orange-glow logo-bounce">N</div>
                <div class="space-y-4">
                    <h1 class="text-6xl font-black italic tracking-tighter uppercase glow-text">Iniciando Protocolo</h1>
                    <p class="text-gray-400 font-bold uppercase tracking-[0.4em] text-xs">Identificación de Metas de Aspirante</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <button 
                        @click="goToStep('university')"
                        class="glass-morphism-dark p-10 rounded-[3rem] border border-white/5 hover:border-orange-500/40 transition-all group text-left relative overflow-hidden"
                    >
                        <div class="relative z-10">
                            <i class="fa-solid fa-crosshairs text-3xl text-orange-500 mb-6 group-hover:scale-110 transition-transform"></i>
                            <h3 class="text-2xl font-black uppercase italic mb-2">Tengo una Meta</h3>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-widest leading-relaxed">Configuración directa de institución y carrera objetivo.</p>
                        </div>
                        <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-orange-500/5 rounded-full blur-3xl"></div>
                    </button>

                    <button 
                        @click="goToStep('test')"
                        class="glass-morphism-dark p-10 rounded-[3rem] border border-white/5 hover:border-blue-500/40 transition-all group text-left relative overflow-hidden"
                    >
                        <div class="relative z-10">
                            <i class="fa-solid fa-brain text-3xl text-blue-400 mb-6 group-hover:scale-110 transition-transform"></i>
                            <h3 class="text-2xl font-black uppercase italic mb-2">Necesito Orientación</h3>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-widest leading-relaxed">Ejecutar diagnóstico RIASEC para identificar compatibilidad vocacional.</p>
                        </div>
                        <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-blue-400/5 rounded-full blur-3xl"></div>
                    </button>
                </div>
            </div>

            <!-- Vocational Test Step -->
            <div v-if="currentStep === 'test'" class="space-y-10 step-content">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-black italic uppercase tracking-tighter">Diagnóstico Vocacional</h2>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Protocolo de 30 preguntas RIASEC</p>
                    </div>
                </div>

                <div class="glass-morphism-dark p-10 rounded-[3rem] border border-white/5 max-h-[60vh] overflow-y-auto space-y-6 custom-scrollbar">
                    <div v-for="q in vocational_questions" :key="q.id" class="p-6 bg-white/5 rounded-2xl border border-white/5 space-y-4">
                        <p class="text-lg font-bold">{{ q.order }}. {{ q.text }}</p>
                        <div class="flex gap-4">
                            <button 
                                v-for="score in [1, 2, 3, 4, 5]" 
                                :key="score"
                                @click="testAnswers[q.id] = score"
                                :class="['w-10 h-10 rounded-xl font-black transition-all border', 
                                    testAnswers[q.id] === score ? 'bg-blue-500 border-blue-400 shadow-blue-glow' : 'bg-white/5 border-white/10 hover:border-blue-500/40']"
                            >
                                {{ score }}
                            </button>
                        </div>
                    </div>
                </div>

                <button @click="submitTest" class="w-full bg-blue-600 py-6 rounded-2xl font-black text-xl italic uppercase shadow-blue-glow">Procesar Diagnóstico</button>
            </div>

            <!-- University Step -->
            <div v-if="currentStep === 'university'" class="space-y-10 step-content">
                <div class="text-center space-y-4">
                    <h2 class="text-4xl font-black italic uppercase tracking-tighter">Selecciona tu Objetivo</h2>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Institución de Nivel Superior</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <button 
                        v-for="uni in universities" 
                        :key="uni.id"
                        @click="selectUni(uni)"
                        class="glass-morphism-dark p-8 rounded-[2.5rem] border border-white/5 hover:border-orange-500/40 transition-all group flex flex-col items-center gap-6"
                    >
                        <div class="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center text-3xl font-black group-hover:scale-110 transition-transform">
                            {{ uni.acronym[0] }}
                        </div>
                        <div class="text-center">
                            <h4 class="text-xl font-black">{{ uni.acronym }}</h4>
                            <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-1">{{ uni.exam_config.total_questions }} Preguntas</p>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Campus/Unit Step -->
            <div v-if="currentStep === 'campus' && selectedUni" class="space-y-10 step-content">
                <h2 class="text-3xl font-black italic uppercase tracking-tighter text-center">Selecciona Plantel / Unidad</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <button 
                        v-for="campus in selectedUni.campuses" 
                        :key="campus.id"
                        @click="selectCampus(campus)"
                        class="p-6 rounded-2xl border border-white/5 bg-white/5 hover:bg-white/10 transition-all font-bold text-center"
                    >
                        {{ campus.name }}
                    </button>
                </div>
            </div>

            <!-- Major Step -->
            <div v-if="currentStep === 'major' && selectedCampus" class="space-y-10 step-content">
                <div class="flex items-center justify-between">
                     <h2 class="text-3xl font-black italic uppercase tracking-tighter">Carrera de Destino</h2>
                     <div class="relative">
                        <input v-model="searchQuery" type="text" placeholder="Filtrar..." class="bg-white/5 border border-white/5 rounded-xl px-4 py-2 text-sm focus:ring-1 focus:ring-orange-500"/>
                     </div>
                </div>

                <div class="grid grid-cols-1 gap-4 max-h-[50vh] overflow-y-auto pr-2 custom-scrollbar">
                    <button 
                        v-for="major in filteredMajors" 
                        :key="major.id"
                        @click="selectMajor(major.id)"
                        :class="['flex items-center justify-between p-6 rounded-2xl border transition-all text-left', 
                            form.major_id === major.id ? 'border-orange-500 bg-orange-500/10' : 'border-white/5 bg-white/5 hover:bg-white/10']"
                    >
                        <div>
                            <p class="font-black text-lg">{{ major.name }}</p>
                            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">{{ major.division_name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-black text-orange-500">{{ major.min_score }}</p>
                            <p class="text-[9px] text-gray-500 uppercase font-black">Meta Aciertos</p>
                        </div>
                    </button>
                </div>

            <!-- Final Config (GPA & Confirm) -->
                <div v-if="form.major_id" class="glass-morphism p-8 rounded-[3rem] border border-orange-500/20 space-y-8 animate-pop-in">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                        <div class="space-y-4">
                            <h4 class="text-sm font-black text-orange-500 uppercase tracking-widest">Configuración de Red</h4>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Promedio Actual (GPA)</label>
                                <div class="flex items-center gap-4">
                                    <input type="range" v-model="form.gpa" min="6" max="10" step="0.1" class="flex-grow accent-orange-500" />
                                    <span class="text-2xl font-black">{{ form.gpa }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <p class="text-[9px] text-gray-500 italic">* Requerido para el cálculo de precisión de la UAM (30% meta).</p>
                                    <button @click="showTrendsModal = true" class="text-[10px] font-black text-orange-400 hover:text-orange-300 underline uppercase tracking-widest">Ver Tendencia</button>
                                </div>
                            </div>
                        </div>
                        <div class="text-right space-y-2">
                             <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Nivel de Amenaza</p>
                             <div class="px-4 py-1 rounded bg-red-500/20 border border-red-500/40 inline-block text-[10px] font-black text-red-400 uppercase tracking-widest">
                                {{ selectedMajorData?.difficulty_category || 'EXTREMA' }} ({{ selectedMajorData?.difficulty_index || '2.4' }}%)
                             </div>
                        </div>
                    </div>
                    <button @click="finishOnboarding" class="w-full bg-orange-600 py-6 rounded-2xl font-black text-2xl italic uppercase shadow-orange-glow">Establecer Meta de Misión</button>
                </div>
            </div>

            <!-- Results Step -->
            <div v-if="currentStep === 'results'" class="space-y-10 step-content">
                <div class="text-center space-y-4">
                    <h2 class="text-5xl font-black italic uppercase tracking-tighter">Perfil RIASEC Identificado</h2>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Diagnóstico Completado</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="glass-morphism-dark p-10 rounded-[3rem] border border-blue-500/20 space-y-6">
                        <div class="w-20 h-20 bg-blue-500/10 rounded-3xl flex items-center justify-center text-4xl text-blue-400 shadow-blue-glow mb-4">
                            <i class="fa-solid fa-dna"></i>
                        </div>
                        <h3 class="text-4xl font-black text-blue-400">{{ vocational_result?.primary_type }}</h3>
                        <p class="text-sm font-bold text-gray-300 leading-relaxed">{{ vocational_result?.recommendation }}</p>
                    </div>

                    <div class="space-y-4 h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                        <h4 class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-4 sticky top-0 bg-midnight py-2">Sugerencias de Infiltración</h4>
                        
                        <div v-if="!recommendations || recommendations.length === 0" class="p-6 bg-white/5 rounded-2xl text-center text-gray-400 font-bold border border-white/5">
                            No se encontraron carreras exactamente coincidentes.
                        </div>

                        <button 
                            v-for="rec in recommendations" 
                            :key="rec.id"
                            @click="selectUni(rec.campus.university); selectCampus(rec.campus); selectMajor(rec.id); showTrendsModal = false"
                            class="w-full flex items-center justify-between p-6 rounded-2xl border border-white/5 bg-white/5 hover:border-blue-500/40 transition-all text-left group"
                        >
                            <div>
                                <p class="font-black text-lg">{{ rec.name }}</p>
                                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">{{ rec.campus.university.acronym }} - {{ rec.campus.name }}</p>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center group-hover:bg-blue-500/20 group-hover:text-blue-400 transition-colors">
                                <i class="fa-solid fa-arrow-right"></i>
                            </div>
                        </button>
                    </div>
                </div>
                
                <div class="text-center mt-8">
                    <button @click="goToStep('university')" class="text-xs font-black text-gray-400 hover:text-white uppercase tracking-widest underline">Ignorar recomendaciones y elegir manualmente</button>
                </div>
            </div>

        </div>


        <!-- Modals -->
        <MajorTrendsModal 
            :show="showTrendsModal" 
            :major="selectedMajorData" 
            @close="showTrendsModal = false" 
        />
    </div>
</template>

<style scoped>
.glass-morphism-dark {
    background: rgba(255, 255, 255, 0.02);
    backdrop-filter: blur(20px);
}

.shadow-orange-glow {
    box-shadow: 0 0 50px rgba(255, 107, 0, 0.2);
}

.shadow-blue-glow {
    box-shadow: 0 0 50px rgba(0, 209, 255, 0.2);
}

.glow-text {
    text-shadow: 0 0 30px rgba(255, 255, 255, 0.1);
}

.logo-bounce {
    animation: bounce 3s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.animate-pop-in {
    animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes popIn {
    from { opacity: 0; scale: 0.9; }
    to { opacity: 1; scale: 1; }
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}
</style>
