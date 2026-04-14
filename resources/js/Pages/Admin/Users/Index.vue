<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed } from 'vue';

const props = defineProps({
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
    router.get(route('admin.users.index'), { search }, {
        preserveState: true,
        replace: true,
    });
};

const getUserStatus = (user) => {
    if (user.is_premium) return { label: 'Premium', color: 'gold' };
    return { label: 'Gratuito', color: 'gray' };
};
</script>

<template>
    <Head title="Gestión de Usuarios" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            <!-- Header -->
            <section class="space-y-2">
                <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tight">👥 Gestión de Usuarios</h1>
                <p class="text-sm text-gray-400">Administra usuarios, roles, membresía y actividad.</p>
            </section>

            <!-- Stats -->
            <section class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <article class="glass-morphism rounded-2xl border border-white/10 p-5">
                    <p class="text-xs text-gray-500 font-black uppercase tracking-wider">Total Usuarios</p>
                    <p class="text-3xl font-black mt-2 text-orange-400">{{ users.total || 0 }}</p>
                </article>
                <article class="glass-morphism rounded-2xl border border-white/10 p-5">
                    <p class="text-xs text-gray-500 font-black uppercase tracking-wider">Activos Hoy</p>
                    <p class="text-3xl font-black mt-2 text-blue-400">0</p>
                </article>
                <article class="glass-morphism rounded-2xl border border-white/10 p-5">
                    <p class="text-xs text-gray-500 font-black uppercase tracking-wider">Premium</p>
                    <p class="text-3xl font-black mt-2 text-yellow-400">0</p>
                </article>
                <article class="glass-morphism rounded-2xl border border-white/10 p-5">
                    <p class="text-xs text-gray-500 font-black uppercase tracking-wider">Verificados</p>
                    <p class="text-3xl font-black mt-2 text-green-400">0</p>
                </article>
            </section>

            <!-- Search -->
            <section class="glass-morphism rounded-3xl border border-white/10 p-6 space-y-4">
                <div class="flex items-center gap-4">
                    <input
                        type="text"
                        :value="filters.search || ''"
                        @input="onSearch"
                        placeholder="Buscar por email o nombre..."
                        class="flex-1 px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:outline-none focus:border-orange-500/50 focus:ring-2 focus:ring-orange-500/20 transition-all"
                    />
                </div>

                <!-- Users Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="text-left px-4 py-3 font-black text-xs text-gray-400 uppercase tracking-wider">Usuario</th>
                                <th class="text-left px-4 py-3 font-black text-xs text-gray-400 uppercase tracking-wider">Email</th>
                                <th class="text-center px-4 py-3 font-black text-xs text-gray-400 uppercase tracking-wider">Estado</th>
                                <th class="text-center px-4 py-3 font-black text-xs text-gray-400 uppercase tracking-wider">Rol</th>
                                <th class="text-right px-4 py-3 font-black text-xs text-gray-400 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users.data" :key="user.id" class="border-b border-white/5 hover:bg-white/3 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-white">{{ user.name }}</div>
                                    <div class="text-xs text-gray-500">ID: {{ user.id }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-300">{{ user.email }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        :class="{
                                            'bg-yellow-500/20 text-yellow-300': getUserStatus(user).color === 'gold',
                                            'bg-gray-500/20 text-gray-300': getUserStatus(user).color === 'gray',
                                        }"
                                        class="px-3 py-1 rounded-full text-xs font-bold"
                                    >
                                        {{ getUserStatus(user).label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 rounded text-xs font-bold" :class="{
                                        'bg-red-500/20 text-red-300': user.role === 'admin',
                                        'bg-blue-500/20 text-blue-300': user.role !== 'admin',
                                    }">
                                        {{ user.role === 'admin' ? 'Admin' : 'Usuario' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button class="text-orange-400 hover:text-orange-300 font-semibold text-sm">Ver</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="links.length > 3" class="flex items-center justify-center gap-2 mt-4 pt-4 border-t border-white/10">
                    <Link
                        v-for="link in links"
                        :key="link.label"
                        :href="link.url"
                        v-html="link.label"
                        :class="{
                            'bg-orange-500/20 border-orange-500/50 text-orange-300': link.active,
                            'border-white/10 text-gray-400 hover:text-white': !link.active && link.url,
                            'border-white/5 text-gray-600 cursor-not-allowed': !link.url,
                        }"
                        class="px-3 py-2 rounded border text-xs font-semibold"
                    />
                </div>
            </section>
        </div>
    </AdminLayout>
</template>

<style scoped>
.glass-morphism {
    background: rgba(255, 255, 255, 0.04);
    backdrop-filter: blur(10px);
}
</style>
