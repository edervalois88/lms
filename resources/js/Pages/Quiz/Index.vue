<script setup>
import { Link, Head } from '@inertiajs/vue3';

defineProps({
    subjects: Array,
});
</script>

<template>
    <Head title="Cuestionarios - NexusEdu" />

    <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <header class="mb-12">
                <h1 class="text-4xl font-extrabold text-gray-900 mb-4">
                    Domina tu <span class="text-orange-600">Conocimiento</span>
                </h1>
                <p class="text-xl text-gray-600 max-w-2xl">
                    Selecciona una materia para comenzar tu práctica personalizada. Nuestra IA adaptará las preguntas a tu nivel actual.
                </p>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <Link
                    v-for="subject in subjects"
                    :key="subject.id"
                    :href="route('quiz.show', subject.slug)"
                    class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden transform hover:-translate-y-1"
                >
                    <div class="p-8">
                        <div 
                            class="w-16 h-16 rounded-xl flex items-center justify-center mb-6 text-white group-hover:scale-110 transition-transform duration-300"
                            :style="{ backgroundColor: subject.color }"
                        >
                            <i :class="`fa-solid fa-${subject.icon || 'book'} text-2xl`"></i>
                        </div>
                        
                        <h2 class="text-2xl font-bold text-gray-900 mb-2 truncate group-hover:text-orange-600 transition-colors">
                            {{ subject.name }}
                        </h2>
                        
                        <p class="text-gray-500 mb-6 line-clamp-2">
                            {{ subject.description || 'Practica los temas fundamentales para tu examen de ingreso.' }}
                        </p>

                        <div class="flex items-center justify-between mt-auto">
                            <span class="text-sm font-medium text-gray-400">
                                <i class="fa-regular fa-list-alt mr-2"></i>
                                {{ subject.topics_count || 5 }} Temas
                            </span>
                            <div class="flex items-center text-orange-600 font-bold">
                                Practicar 
                                <i class="fa-solid fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress Bar (Simple mastery mock) -->
                    <div class="h-1.5 w-full bg-gray-100">
                        <div 
                            class="h-full transition-all duration-1000"
                            :style="{ 
                                backgroundColor: subject.color, 
                                width: (subject.user_mastery?.score || 0) * 10 + '%' 
                            }"
                        ></div>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
