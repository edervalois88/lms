<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    users: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
});

const links = computed(() => props.users?.links || []);

const onSearch = (event) => {
    const search = event.target.value;
    router.get(route('admin.index'), { search }, {
        preserveState: true,
        replace: true,
    });
};
</script>

<template>
    <Head title="Panel de Administración" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            <!-- Header -->
            <section class="space-y-2">
                <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tight">👥 Bienvenido al Panel Admin</h1>
                <p class="text-sm text-gray-400">Gestiona usuarios, preguntas, analíticas y la seguridad del sistema.</p>
            </section>

            <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <article class="glass-morphism rounded-2xl border border-white/10 p-5">
                    <p class="text-xs text-gray-500 font-black uppercase tracking-wider">Usuarios</p>
                    <p class="text-3xl font-black mt-2">{{ stats.users }}</p>
                </article>
                <article class="glass-morphism rounded-2xl border border-white/10 p-5">
                    <p class="text-xs text-gray-500 font-black uppercase tracking-wider">Activos Hoy</p>
                    <p class="text-3xl font-black mt-2">{{ stats.active_today }}</p>
                </article>
                <article class="glass-morphism rounded-2xl border border-white/10 p-5">
                    <p class="text-xs text-gray-500 font-black uppercase tracking-wider">Exámenes Completados</p>
                    <p class="text-3xl font-black mt-2">{{ stats.completed_exams }}</p>
                </article>
            </section>

            <section class="glass-morphism rounded-3xl border border-white/10 p-4 md:p-6 space-y-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <h2 class="text-xl font-black uppercase italic tracking-tight">Datos de Usuario</h2>
                    <input
                        type="text"
                        :value="filters.search || ''"
                        @input="onSearch"
                        placeholder="Buscar por nombre o correo..."
                        class="w-full md:w-80 rounded-xl bg-white/5 border border-white/10 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                    />
                </div>

                <div class="overflow-x-auto rounded-2xl border border-white/10">
                    <table class="w-full min-w-[1100px] text-sm">
                        <thead class="bg-white/5 text-gray-500 uppercase text-[11px] tracking-wider">
                            <tr>
                                <th class="px-4 py-3 text-left">Nombre</th>
                                <th class="px-4 py-3 text-left">Correo</th>
                                <th class="px-4 py-3 text-left">Rol</th>
                                <th class="px-4 py-3 text-left">Carrera</th>
                                <th class="px-4 py-3 text-left">Campus</th>
                                <th class="px-4 py-3 text-left">Univ.</th>
                                <th class="px-4 py-3 text-left">GPA</th>
                                <th class="px-4 py-3 text-left">Racha</th>
                                <th class="px-4 py-3 text-left">Onboarding</th>
                                <th class="px-4 py-3 text-left">Último estudio</th>
                                <th class="px-4 py-3 text-left">Registro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users.data" :key="user.id" class="border-t border-white/10 hover:bg-white/5">
                                <td class="px-4 py-3 font-bold">{{ user.name }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ user.email }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-lg bg-orange-500/10 border border-orange-500/20 text-orange-500 text-xs font-black">{{ user.role }}</span>
                                </td>
                                <td class="px-4 py-3">{{ user.major || 'Sin definir' }}</td>
                                <td class="px-4 py-3">{{ user.campus || '-' }}</td>
                                <td class="px-4 py-3">{{ user.university || '-' }}</td>
                                <td class="px-4 py-3">{{ user.gpa ?? '-' }}</td>
                                <td class="px-4 py-3">{{ user.streak_days }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ user.onboarded_at || '-' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ user.last_study_at || '-' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ user.created_at || '-' }}</td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="11" class="px-4 py-10 text-center text-gray-500 font-bold">Sin resultados para el filtro actual.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <nav class="flex flex-wrap gap-2 justify-end">
                    <component
                        v-for="link in links"
                        :key="link.label"
                        :is="link.url ? Link : 'span'"
                        :href="link.url || undefined"
                        class="px-3 py-1.5 rounded-lg text-xs border"
                        :class="link.active ? 'bg-orange-500 text-white border-orange-500' : 'bg-white/5 border-white/10 text-gray-400'
                        "
                        v-html="link.label"
                    />
                </nav>
            </section>
        </div>
    </AdminLayout>
</template>
