<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const isSidebarOpen = ref(true);

const adminNavItems = [
    { label: 'Dashboard', route: 'admin.dashboard', icon: '📊' },
    { label: 'Usuarios', route: 'admin.users.index', icon: '👥' },
    { label: 'Preguntas', route: 'admin.questions.index', icon: '❓' },
    { label: 'Curación', route: 'admin.curation.index', icon: '✂️' },
    { label: 'Analíticas', route: 'admin.analytics', icon: '📈' },
    { label: 'Métricas IA', route: 'admin.ai-metrics', icon: '🤖' },
    { label: 'Configuración', route: 'admin.settings', icon: '⚙️' },
];

const isActive = (routeName) => {
    return route().current(routeName) || route().current(`${routeName}*`);
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <Head>
        <title>Admin - NexusEdu</title>
        <meta name="theme-color" content="#ff6b00" />
    </Head>

    <div class="min-h-screen bg-gradient-to-br from-slate-950 via-blue-950 to-slate-900 flex">
        <!-- Sidebar -->
        <aside class="w-80 border-r border-white/10 backdrop-blur-xl bg-[rgba(10,16,26,0.92)] flex flex-col transition-all duration-300 ease-in-out" :class="{ 'w-24': !isSidebarOpen }">
            <!-- Logo Section -->
            <div class="h-20 flex items-center px-6 border-b border-white/10 gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center flex-shrink-0">
                    <span class="text-xl font-black text-white">⚡</span>
                </div>
                <div v-if="isSidebarOpen" class="flex-1 min-w-0">
                    <h1 class="text-lg font-black text-white truncate">NexusEdu</h1>
                    <p class="text-xs text-orange-400 font-bold">PANEL ADMIN</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto px-3 py-6 space-y-2">
                <component
                    v-for="item in adminNavItems"
                    :key="item.route"
                    :is="Link"
                    :href="route(item.route)"
                    :class="{
                        'bg-orange-500/15 border-orange-500/30 text-orange-300 shadow-lg shadow-orange-500/20': isActive(item.route),
                        'text-gray-400 hover:bg-white/6 hover:text-white': !isActive(item.route),
                    }"
                    class="flex items-center gap-4 px-4 py-3 rounded-2xl border border-transparent transition-all duration-200 min-h-12 group"
                >
                    <span class="text-2xl flex-shrink-0">{{ item.icon }}</span>
                    <span v-if="isSidebarOpen" class="text-sm font-semibold whitespace-nowrap">{{ item.label }}</span>
                </component>
            </nav>

            <!-- Bottom Section -->
            <div class="border-t border-white/10 p-4 space-y-3">
                <!-- Collapse Toggle -->
                <button
                    @click="isSidebarOpen = !isSidebarOpen"
                    class="w-full flex items-center justify-center px-4 py-2 rounded-xl border border-white/10 bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white transition-all duration-200"
                >
                    <span class="text-lg">{{ isSidebarOpen ? '◀' : '▶' }}</span>
                </button>

                <!-- User Info Card -->
                <div v-if="isSidebarOpen" class="rounded-2xl bg-white/4 border border-white/8 p-3 space-y-2">
                    <p class="text-xs font-black text-gray-400 uppercase tracking-wider">Admin</p>
                    <p class="text-sm font-bold text-white truncate">{{ page.props.auth.user?.name }}</p>
                    <p class="text-xs text-gray-500">{{ page.props.auth.user?.email }}</p>
                </div>

                <!-- Logout -->
                <button
                    @click="logout"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 hover:bg-red-500/20 transition-all duration-200 text-sm font-semibold"
                >
                    <span>🔐</span>
                    <span v-if="isSidebarOpen">Salir</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Top Navigation Bar -->
            <header class="h-16 border-b border-white/10 backdrop-blur-xl bg-[rgba(10,16,26,0.5)] flex items-center px-6 justify-between">
                <div class="flex items-center gap-4">
                    <h2 class="text-xl font-black text-white uppercase tracking-tight">Panel de Administración</h2>
                </div>
                <div class="flex items-center gap-4">
                    <Link href="/" class="px-4 py-2 rounded-xl border border-white/10 bg-white/5 text-xs font-semibold text-gray-300 hover:text-white hover:bg-white/10 transition-all duration-200">
                        ← Volver
                    </Link>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-auto">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
aside {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 107, 0, 0.3) transparent;
}

aside::-webkit-scrollbar {
    width: 6px;
}

aside::-webkit-scrollbar-track {
    background: transparent;
}

aside::-webkit-scrollbar-thumb {
    background: rgba(255, 107, 0, 0.3);
    border-radius: 3px;
}

aside::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 107, 0, 0.5);
}

main {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 107, 0, 0.2) transparent;
}

main::-webkit-scrollbar {
    width: 8px;
}

main::-webkit-scrollbar-track {
    background: transparent;
}

main::-webkit-scrollbar-thumb {
    background: rgba(255, 107, 0, 0.2);
    border-radius: 4px;
}

main::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 107, 0, 0.4);
}
</style>
