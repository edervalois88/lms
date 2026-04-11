<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { animate, spring } from 'motion';
import { playSound } from '@/Utils/SoundService';

const props = defineProps({
    areas: Array,
    majors: Array,
});

const form = useForm({
    area_id: null,
    major_id: null,
    study_hours_per_day: 4,
});

const searchQuery = ref('');

const filteredMajors = computed(() => {
    if (!form.area_id) return [];
    let list = props.majors.filter(m => m.area_id === form.area_id);
    if (searchQuery.value) {
        list = list.filter(m => m.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
    }
    return list;
});

const selectedMajor = computed(() => props.majors.find(m => m.id === form.major_id));

const selectArea = (id) => {
    form.area_id = id;
    form.major_id = null;
    playSound('pop');
    
    // Animación de entrada para la sección de carreras
    setTimeout(() => {
        animate(".career-section", { opacity: [0, 1], y: [20, 0] }, { duration: 0.5, easing: spring() });
    }, 50);
};

const selectMajor = (id) => {
    form.major_id = id;
    playSound('click');
};

const submit = () => {
    playSound('success');
    form.post(route('onboarding.store'));
};

onMounted(() => {
    animate(".onboarding-container", { opacity: [0, 1], scale: [0.95, 1] }, { duration: 0.8, easing: spring() });
});
</script>

<template>
    <Head title="Bienvenido a NexusEdu" />

    <div class="min-h-screen bg-white flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8 onboarding-container">
        <div class="max-w-xl w-full mx-auto space-y-12">
            
            <div class="text-center">
                <div class="w-16 h-16 bg-orange-500 rounded-2xl mx-auto flex items-center justify-center text-white text-3xl font-black shadow-lg mb-8 logo-bounce">N</div>
                <h1 class="text-4xl font-black text-gray-900 mb-4">Bienvenido a <span class="text-orange-600">NexusEdu</span></h1>
                <p class="text-lg text-gray-500">Configura tu perfil para comenzar tu camino a la UNAM.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-12">
                <!-- Select Area -->
                <div class="space-y-6">
                    <h2 class="text-xl font-bold text-gray-900 border-l-4 border-orange-500 pl-4">1. ¿A qué área perteneces?</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <button
                            v-for="area in areas"
                            :key="area.id"
                            type="button"
                            @click="selectArea(area.id)"
                            class="flex flex-col items-center p-4 rounded-2xl border-2 transition-all text-center transform active:scale-95"
                            :class="form.area_id === area.id ? 'border-orange-500 bg-orange-50 ring-4 ring-orange-100' : 'border-gray-100 hover:border-orange-200'"
                        >
                            <span class="text-2xl font-black mb-1" :class="form.area_id === area.id ? 'text-orange-600' : 'text-gray-300'">
                                {{ area.id }}
                            </span>
                            <span class="text-xs font-bold text-gray-800 leading-tight">{{ area.name.split(' ')[0] }}...</span>
                        </button>
                    </div>
                </div>

                <!-- Select Major -->
                <div v-if="form.area_id" class="space-y-6 career-section">
                    <h2 class="text-xl font-bold text-gray-900 border-l-4 border-orange-500 pl-4">2. Selecciona tu Carrera Meta</h2>
                    
                    <div class="relative">
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Buscar carrera (ej: Medicina, Derecho...)"
                            class="w-full bg-gray-50 border-0 rounded-2xl p-4 pl-12 text-gray-900 focus:ring-2 focus:ring-orange-500 shadow-inner"
                        />
                        <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>

                    <div class="max-h-64 overflow-y-auto space-y-2 pr-2 custom-scrollbar">
                        <button
                            v-for="major in filteredMajors"
                            :key="major.id"
                            type="button"
                            @click="selectMajor(major.id)"
                            class="w-full flex items-center justify-between p-4 rounded-xl border-2 transition-all text-left transform active:scale-98"
                            :class="form.major_id === major.id ? 'border-orange-500 bg-orange-50' : 'border-gray-50 bg-gray-50 hover:bg-white'"
                        >
                            <div>
                                <p class="font-bold text-gray-900">{{ major.name }}</p>
                                <p class="text-xs text-gray-400">{{ major.school_name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-orange-600">{{ major.min_score }}</p>
                                <p class="text-[10px] text-gray-400 uppercase font-black">Aciertos</p>
                            </div>
                        </button>
                    </div>

                    <div v-if="selectedMajor" class="bg-gray-900 p-6 rounded-2xl text-white shadow-xl animate-pop-in">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-orange-400 uppercase tracking-widest mb-1">Tu Objetivo Seleccionado</p>
                                <p class="text-xl font-black">{{ selectedMajor.name }}</p>
                                <p class="text-sm text-gray-400">{{ selectedMajor.school_name }}</p>
                            </div>
                            <div class="text-center bg-white/10 px-4 py-2 rounded-xl border border-white/10">
                                <p class="text-2xl font-black text-orange-500">{{ selectedMajor.min_score }}</p>
                                <p class="text-[10px] uppercase font-bold text-gray-300">Meta</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Study Hours -->
                <div v-if="form.major_id" class="space-y-6 study-hours-section">
                    <h2 class="text-xl font-bold text-gray-900 border-l-4 border-orange-500 pl-4">3. Plan de estudio diario</h2>
                    <div class="bg-gray-50 p-8 rounded-3xl">
                        <input 
                            type="range" 
                            v-model="form.study_hours_per_day" 
                            min="1" 
                            max="12" 
                            class="w-full h-3 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-orange-500"
                        />
                        <div class="flex justify-between mt-4 text-gray-400 font-bold uppercase tracking-widest text-[10px]">
                            <span>1 hora</span>
                            <span class="text-orange-600 text-lg font-black">{{ form.study_hours_per_day }} horas</span>
                            <span>12 horas</span>
                        </div>
                    </div>
                </div>

                <div class="pt-6">
                    <button 
                        type="submit"
                        :disabled="!form.major_id || form.processing"
                        class="w-full bg-gray-900 text-white py-5 rounded-2xl font-black text-xl hover:bg-gray-800 transition-all shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transform active:scale-95 hover:shadow-orange-500/20"
                    >
                        {{ form.processing ? 'Configurando...' : '¡Comenzar mi Preparación!' }}
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>

<style scoped>
.logo-bounce {
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0) rotate(0); }
    50% { transform: translateY(-10px) rotate(5deg); }
}

.animate-pop-in {
    animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes popIn {
    from { opacity: 0; transform: scale(0.8); }
    to { opacity: 1; transform: scale(1); }
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
</style>
