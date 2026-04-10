<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    exam: Object,
    correct: Number,
    total: Number,
    percentage: Number,
    message: String
});

const scoreCategory = computed(() => {
    if (props.percentage >= 80) return { color: 'text-green-500', bg: 'bg-green-50', icon: 'fa-solid fa-award' };
    if (props.percentage >= 60) return { color: 'text-blue-500', bg: 'bg-blue-50', icon: 'fa-solid fa-chart-line' };
    if (props.percentage >= 40) return { color: 'text-orange-500', bg: 'bg-orange-50', icon: 'fa-solid fa-fire' };
    return { color: 'text-red-500', bg: 'bg-red-50', icon: 'fa-solid fa-book-open' };
});
</script>

<template>
    <Head title="Resultados del Simulacro - NexusEdu" />

    <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8 flex items-center justify-center">
        <div class="max-w-2xl w-full">
            
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100 text-center">
                
                <!-- Confetti/Celebration Header -->
                <div class="p-10" :class="scoreCategory.bg">
                    <div 
                        class="w-20 h-20 rounded-2xl mx-auto flex items-center justify-center mb-6 text-3xl shadow-lg transform rotate-12"
                        :class="scoreCategory.color.replace('text', 'bg').replace('500', '600') + ' text-white'"
                    >
                        <i :class="scoreCategory.icon"></i>
                    </div>
                    <h1 class="text-3xl font-black text-gray-900 mb-2">¡Examen Finalizado!</h1>
                    <p class="text-lg font-bold" :class="scoreCategory.color">{{ message }}</p>
                </div>

                <div class="p-10 space-y-10">
                    <!-- Score Circle/Bar -->
                    <div class="relative">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-bold text-gray-400 uppercase tracking-widest">Resultado Final</span>
                            <span class="text-2xl font-black text-gray-900">{{ percentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 h-6 rounded-full overflow-hidden border border-gray-100 p-1">
                            <div 
                                class="h-full bg-orange-500 rounded-full transition-all duration-1000 ease-out shadow-lg"
                                :style="{ width: percentage + '%' }"
                            ></div>
                        </div>
                    </div>

                    <!-- Statistics Grid -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Aciertos</p>
                            <p class="text-3xl font-black text-gray-900">{{ correct }} / {{ total }}</p>
                        </div>
                        <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Tiempo</p>
                            <p class="text-3xl font-black text-gray-900">1:42:15</p> 
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <Link 
                            :href="route('dashboard')"
                            class="flex-grow order-2 sm:order-1 bg-gray-100 text-gray-700 px-8 py-4 rounded-2xl font-black text-lg hover:bg-gray-200 transition-colors"
                        >
                            Ir al Panel
                        </Link>
                        <Link 
                            :href="route('simulator.index')"
                            class="flex-grow order-1 sm:order-2 bg-orange-500 text-white px-8 py-4 rounded-2xl font-black text-lg hover:bg-orange-600 transition-all shadow-lg shadow-orange-500/20 transform hover:scale-105 active:scale-95 flex items-center justify-center"
                        >
                            Nuevo Simulacro
                            <i class="fa-solid fa-redo ml-3 text-sm"></i>
                        </Link>
                    </div>
                </div>

                <!-- Footer tip -->
                <div class="bg-gray-900 p-6 text-white text-sm">
                    <p class="flex items-center justify-center opacity-80">
                        <i class="fa-solid fa-lightbulb text-orange-400 mr-2"></i>
                        NexusEdu sugiere enfocarte en <span class="font-bold mx-1">Matemáticas</span> según tus últimos errores.
                    </p>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
</style>
