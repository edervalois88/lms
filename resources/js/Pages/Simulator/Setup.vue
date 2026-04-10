<script setup>
import { computed } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

defineProps({ areas: Array })

const form = useForm({
    area: 1,
    type: 'simulation',
})

const examTypes = [
    { id: 'diagnostic', name: 'Diagnóstico Rápido',    questions: 30,  time: 45,  icon: 'fa-solid fa-stethoscope',   description: 'Mide tu nivel inicial rápidamente.' },
    { id: 'practice',   name: 'Sesión de Práctica',    questions: 60,  time: 90,  icon: 'fa-solid fa-dumbbell',      description: 'Práctica intensiva con tiempo controlado.' },
    { id: 'simulation', name: 'Simulacro Completo',    questions: 120, time: 180, icon: 'fa-solid fa-graduation-cap', description: 'Experiencia real del examen UNAM.' },
]

const selectedType = computed(() => examTypes.find(t => t.id === form.type))

const submit = () => form.post(route('simulator.store'))
</script>

<template>
    <Head title="Configurar Simulacro — NexusEdu" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Simulacro UNAM</h2>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

                <div class="text-center mb-10">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        Prepara tu <span class="text-orange-500">simulacro</span>
                    </h1>
                    <p class="text-gray-500">Configura las condiciones de tu examen para obtener resultados precisos.</p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">

                    <div class="bg-white p-6 rounded-2xl border border-gray-200">
                        <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="w-6 h-6 bg-orange-100 text-orange-600 rounded-md flex items-center justify-center text-xs font-bold">1</span>
                            Selecciona tu área UNAM
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button
                                v-for="area in areas"
                                :key="area.value"
                                type="button"
                                @click="form.area = area.value"
                                class="p-4 rounded-xl border-2 text-left transition-all"
                                :class="form.area === area.value ? 'border-orange-500 bg-orange-50' : 'border-gray-100 hover:border-orange-200'"
                            >
                                <p class="font-semibold text-sm" :class="form.area === area.value ? 'text-orange-700' : 'text-gray-900'">
                                    {{ area.label }}
                                </p>
                            </button>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-2xl border border-gray-200">
                        <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="w-6 h-6 bg-orange-100 text-orange-600 rounded-md flex items-center justify-center text-xs font-bold">2</span>
                            Tipo de examen
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <button
                                v-for="type in examTypes"
                                :key="type.id"
                                type="button"
                                @click="form.type = type.id"
                                class="p-5 rounded-xl border-2 text-center transition-all flex flex-col items-center gap-2"
                                :class="form.type === type.id ? 'border-orange-500 bg-orange-50' : 'border-gray-100 hover:border-orange-200'"
                            >
                                <div
                                    class="w-10 h-10 rounded-full flex items-center justify-center"
                                    :class="form.type === type.id ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-400'"
                                >
                                    <i :class="type.icon + ' text-sm'"></i>
                                </div>
                                <p class="font-semibold text-sm text-gray-900">{{ type.name }}</p>
                                <p class="text-xs text-gray-400">{{ type.questions }} reactivos · {{ type.time }} min</p>
                            </button>
                        </div>
                    </div>

                    <div class="bg-gray-900 p-6 rounded-2xl text-white flex flex-col sm:flex-row items-center justify-between gap-6">
                        <div>
                            <p class="font-semibold text-lg">Resumen del examen</p>
                            <p class="text-gray-400 text-sm mt-0.5">{{ selectedType?.name }}</p>
                        </div>
                        <div class="flex gap-8 text-center">
                            <div>
                                <p class="text-orange-400 font-bold text-2xl">{{ selectedType?.questions }}</p>
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Preguntas</p>
                            </div>
                            <div>
                                <p class="text-orange-400 font-bold text-2xl">{{ selectedType?.time }}</p>
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Minutos</p>
                            </div>
                        </div>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-orange-500 hover:bg-orange-600 disabled:opacity-50 text-white px-8 py-3 rounded-xl font-semibold transition-colors"
                        >
                            {{ form.processing ? 'Iniciando...' : 'Iniciar examen →' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
