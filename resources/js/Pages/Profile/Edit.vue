<script setup>
import { computed, watch, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MajorTrendsModal from '@/Components/Modals/MajorTrendsModal.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    universities: {
        type: Array,
        default: () => [],
    },
});

const showTrendsModal = ref(false);

const initialUniversityId = props.user?.major?.campus?.university_id ?? null;
const initialCampusId = props.user?.major?.campus_id ?? null;

const form = useForm({
    name: props.user.name ?? '',
    email: props.user.email ?? '',
    university_id: initialUniversityId,
    campus_id: initialCampusId,
    major_id: props.user.major_id ?? null,
});

const passwordForm = useForm({
    password: '',
});

const selectedUniversity = computed(() => {
    return props.universities.find((uni) => uni.id === Number(form.university_id)) ?? null;
});

const availableCampuses = computed(() => selectedUniversity.value?.campuses ?? []);

const selectedCampus = computed(() => {
    return availableCampuses.value.find((campus) => campus.id === Number(form.campus_id)) ?? null;
});

const availableMajors = computed(() => selectedCampus.value?.majors ?? []);

const selectedMajor = computed(() => {
    return availableMajors.value.find((major) => major.id === Number(form.major_id)) ?? null;
});

const difficultyIndex = computed(() => {
    if (!selectedMajor.value) return null;
    const applicants = Number(selectedMajor.value.applicants ?? 0);
    const places = Number(selectedMajor.value.places ?? 0);
    if (!Number.isFinite(applicants) || applicants <= 0) return null;
    return Math.round((places / applicants) * 100 * 100) / 100;
});

const difficultyCategory = computed(() => {
    const index = difficultyIndex.value;
    if (index === null) return 'N/A';
    if (index <= 5) return 'EXTREMA';
    if (index <= 15) return 'ALTA';
    if (index <= 30) return 'MEDIA';
    return 'NORMAL';
});

watch(() => form.university_id, () => {
    const hasCampus = availableCampuses.value.some((campus) => campus.id === Number(form.campus_id));
    if (!hasCampus) {
        form.campus_id = null;
        form.major_id = null;
    }
});

watch(() => form.campus_id, () => {
    const hasMajor = availableMajors.value.some((major) => major.id === Number(form.major_id));
    if (!hasMajor) {
        form.major_id = null;
    }
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};

const destroyAccount = () => {
    if (!confirm('Esta accion eliminara tu cuenta. Deseas continuar?')) return;
    passwordForm.delete(route('profile.destroy'));
};
</script>

<template>
    <Head title="Perfil" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
            <div>
                <h1 class="text-3xl font-black">Perfil</h1>
                <p class="text-gray-500 mt-1">Actualiza tus datos personales y tu escuela/carrera objetivo.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <p class="text-xs font-black uppercase tracking-wider text-gray-500">Universidad actual</p>
                    <p class="mt-2 font-bold">{{ props.user.major?.campus?.university?.acronym || 'Sin definir' }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <p class="text-xs font-black uppercase tracking-wider text-gray-500">Escuela / Campus actual</p>
                    <p class="mt-2 font-bold">{{ props.user.major?.campus?.name || 'Sin definir' }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <p class="text-xs font-black uppercase tracking-wider text-gray-500">Carrera actual</p>
                    <p class="mt-2 font-bold">{{ props.user.major?.name || 'Sin definir' }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5 rounded-2xl border border-white/10 bg-white/5 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-500">Nombre</label>
                        <input v-model="form.name" type="text" class="w-full mt-1 rounded-xl bg-white/5 border border-white/10 px-3 py-2" />
                        <p v-if="form.errors.name" class="text-sm text-rose-400 mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="text-sm text-gray-500">Email</label>
                        <input v-model="form.email" type="email" class="w-full mt-1 rounded-xl bg-white/5 border border-white/10 px-3 py-2" />
                        <p v-if="form.errors.email" class="text-sm text-rose-400 mt-1">{{ form.errors.email }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm text-gray-500">Universidad</label>
                        <select v-model="form.university_id" class="w-full mt-1 rounded-xl bg-white/5 border border-white/10 px-3 py-2">
                            <option :value="null">Selecciona universidad</option>
                            <option v-for="uni in universities" :key="uni.id" :value="uni.id">{{ uni.acronym }} - {{ uni.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm text-gray-500">Escuela / Campus</label>
                        <select v-model="form.campus_id" class="w-full mt-1 rounded-xl bg-white/5 border border-white/10 px-3 py-2" :disabled="!form.university_id">
                            <option :value="null">Selecciona campus</option>
                            <option v-for="campus in availableCampuses" :key="campus.id" :value="campus.id">{{ campus.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm text-gray-500">Carrera</label>
                        <select v-model="form.major_id" class="w-full mt-1 rounded-xl bg-white/5 border border-white/10 px-3 py-2" :disabled="!form.campus_id">
                            <option :value="null">Selecciona carrera</option>
                            <option v-for="major in availableMajors" :key="major.id" :value="major.id">{{ major.name }}</option>
                        </select>
                        <p v-if="form.errors.major_id" class="text-sm text-rose-400 mt-1">{{ form.errors.major_id }}</p>
                    </div>
                </div>

                <p v-if="selectedMajor" class="text-xs text-gray-500">
                    Selección nueva: {{ selectedUniversity?.acronym }} / {{ selectedCampus?.name }} / {{ selectedMajor?.name }}
                </p>

                <div v-if="selectedMajor" class="rounded-2xl border border-orange-500/20 bg-orange-500/5 p-4 md:p-5 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs font-black uppercase tracking-wider text-orange-300">Análisis de la Carrera Seleccionada</p>
                        <button
                            type="button"
                            @click="showTrendsModal = true"
                            class="rounded-lg border border-orange-500/40 bg-orange-500/10 px-3 py-1.5 text-[11px] font-black uppercase tracking-wider text-orange-200 hover:bg-orange-500/20"
                        >
                            Ver Tendencia
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="rounded-xl border border-white/10 bg-white/5 p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest">Meta Aciertos</p>
                            <p class="text-lg font-black text-orange-300">{{ selectedMajor.min_score ?? 'N/A' }}</p>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/5 p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest">Aspirantes</p>
                            <p class="text-lg font-black">{{ selectedMajor.applicants ?? 'N/A' }}</p>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/5 p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest">Lugares</p>
                            <p class="text-lg font-black">{{ selectedMajor.places ?? 'N/A' }}</p>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/5 p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest">Dificultad</p>
                            <p class="text-lg font-black" :class="difficultyCategory === 'EXTREMA' ? 'text-rose-300' : 'text-amber-300'">
                                {{ difficultyCategory }}
                                <span v-if="difficultyIndex !== null" class="text-sm text-gray-300">({{ difficultyIndex }}%)</span>
                            </p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="rounded-xl bg-orange-600 px-4 py-2 font-bold hover:bg-orange-500">Guardar cambios</button>
            </form>

            <form @submit.prevent="destroyAccount" class="space-y-3 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-6">
                <h2 class="font-bold text-rose-300">Eliminar cuenta</h2>
                <p class="text-sm text-rose-200/80">Confirma tu password para eliminar la cuenta.</p>
                <input v-model="passwordForm.password" type="password" class="w-full mt-1 rounded-xl bg-white/5 border border-white/10 px-3 py-2" placeholder="Password actual" />
                <p v-if="passwordForm.errors.password" class="text-sm text-rose-400">{{ passwordForm.errors.password }}</p>
                <button type="submit" class="rounded-xl bg-rose-700 px-4 py-2 font-bold hover:bg-rose-600">Eliminar cuenta</button>
            </form>
        </div>

        <MajorTrendsModal
            :show="showTrendsModal"
            :major="selectedMajor"
            @close="showTrendsModal = false"
        />
    </AuthenticatedLayout>
</template>
