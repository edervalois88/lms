<script setup>
import { computed, onMounted, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { playSound } from '@/Utils/SoundService';
import { useTheme } from '@/Composables/useTheme';
import CurrencyDisplay from '@/Components/Gamification/Currency/CurrencyDisplay.vue';
import { useCurrencyStore } from '@/Stores/gamification/currencyStore';
import AchievementUnlock from '@/Components/Gamification/Achievements/AchievementUnlock.vue';

const { theme, initializeTheme, toggleTheme } = useTheme();
const page = usePage();
const currencyStore = useCurrencyStore();
const sidebarCollapsed = ref(false);
const mobileSidebarOpen = ref(false);

onMounted(() => {
    initializeTheme();

    if (typeof window !== 'undefined') {
        sidebarCollapsed.value = window.localStorage.getItem('nexus.sidebar.collapsed') === 'true';
    }

    // Hydrate currency store from user gamification data
    const gamification = page.props?.auth?.user?.gamification ?? {};
    currencyStore.hydrate({
        gold: gamification.gold ?? 0,
        xp: gamification.xp ?? 0,
        current_level: gamification.current_level ?? 1,
    });
});

const toggleSidebarCollapse = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;

    if (typeof window !== 'undefined') {
        window.localStorage.setItem('nexus.sidebar.collapsed', String(sidebarCollapsed.value));
    }
};

const toggleMobileSidebar = () => {
    mobileSidebarOpen.value = !mobileSidebarOpen.value;
};

const closeMobileSidebar = () => {
    mobileSidebarOpen.value = false;
};

const baseNavItems = [
    { name: 'NODO CENTRAL', route: 'dashboard', icon: 'fa-solid fa-house' },
    { name: 'SIMULACRO', route: 'simulator.index', icon: 'fa-solid fa-graduation-cap' },
    { name: 'ENTRENAMIENTO', route: 'quiz.index', icon: 'fa-solid fa-bolt-lightning' },
    { name: 'DAILY XP', route: 'practice.daily', icon: 'fa-solid fa-fire' },
    { name: 'ANALÍTICA', route: 'progress.index', icon: 'fa-solid fa-chart-line' },
    { name: 'REPETICIÓN', route: 'review.index', icon: 'fa-solid fa-repeat' },
    { name: 'PERFIL', route: 'profile.edit', icon: 'fa-solid fa-gear' },
];

const navItems = computed(() => {
    const isAdmin = Boolean(page.props?.auth?.is_admin);

    if (isAdmin) {
        return [
            ...baseNavItems,
            { name: 'ADMIN', route: 'admin.index', icon: 'fa-solid fa-shield-halved' },
            { name: 'IA DASH', route: 'admin.dashboard', icon: 'fa-solid fa-chart-column' },
        ];
    }

    return baseNavItems;
});

const equippedCosmetics = computed(() => page.props?.auth?.cosmetics?.equipped || {});

const themePalette = computed(() => {
    const metadata = equippedCosmetics.value?.ui_theme?.metadata || {};

    return {
        primary: metadata.primary_color || '#ff6b00',
        secondary: metadata.secondary_color || '#f97316',
        soft: metadata.soft_color || 'rgba(255, 107, 0, 0.18)',
    };
});

const avatarMetadata = computed(() => equippedCosmetics.value?.avatar?.metadata || {});
const avatarIcon = computed(() => avatarMetadata.value.icon_class || 'fa-solid fa-user');
const profileTitle = computed(() => equippedCosmetics.value?.profile_title?.metadata?.label || null);

const gamification = computed(() => page.props?.auth?.gamification || { current: 1, xp: 0, progress: 0 });
const userName = computed(() => page.props?.auth?.user?.name || 'Usuario');

const mobileQuickNav = computed(() => {
    const items = [
        { name: 'Inicio', route: 'dashboard', icon: 'fa-solid fa-house' },
        { name: 'Simulacro', route: 'simulator.index', icon: 'fa-solid fa-graduation-cap' },
        { name: 'Entrena', route: 'quiz.index', icon: 'fa-solid fa-bolt-lightning' },
        { name: 'Progreso', route: 'progress.index', icon: 'fa-solid fa-chart-line' },
        { name: 'Perfil', route: 'profile.edit', icon: 'fa-solid fa-user-gear' },
    ];

    if (Boolean(page.props?.auth?.is_admin)) {
        items[3] = { name: 'Admin', route: 'admin.index', icon: 'fa-solid fa-shield-halved' };
    }

    return items;
});
</script>

<template>
    <div class="app-shell min-h-screen bg-midnight text-white selection:bg-orange-500/30">
        
        <!-- Premium Cyber Nav (Sleek Top Bar) -->
        <nav class="app-nav bg-cyber-gray/80 backdrop-blur-xl border-b border-white/5 sticky top-0 z-50">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Spacer (Desktop) -->
                    <div class="hidden md:block flex-1"></div>

                    <!-- Desktop Currency Display + Theme Toggle -->
                    <div class="hidden md:flex items-center gap-6">
                        <CurrencyDisplay />
                        <button
                            type="button"
                            @click="toggleTheme()"
                            class="app-interactive w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/10 transition-all border border-white/5"
                            :title="theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
                        >
                            <i :class="theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon'"></i>
                        </button>
                    </div>

                    <!-- Mobile Controls -->
                    <div class="md:hidden flex items-center gap-2">
                        <button
                            type="button"
                            @click="toggleTheme()"
                            class="app-interactive w-10 h-10 rounded-xl text-gray-400 hover:text-white hover:bg-white/5 transition"
                            :title="theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
                        >
                            <i :class="theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon'"></i>
                        </button>
                        <button @click="toggleMobileSidebar" class="app-interactive w-10 h-10 rounded-xl text-gray-400 hover:text-white hover:bg-white/5 transition">
                            <i :class="mobileSidebarOpen ? 'fa-solid fa-xmark' : 'fa-solid fa-bars-staggered'"></i>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Desktop Sidebar (Glassmorphism + Glow) -->
        <aside
            class="hidden md:flex fixed top-16 bottom-0 left-0 z-40 flex-col bg-[rgba(10,16,26,0.92)] backdrop-blur-2xl border-r border-white/8 transition-all duration-300"
            :class="sidebarCollapsed ? 'w-24' : 'w-80'"
        >
            <!-- Sidebar Header -->
            <div class="px-4 py-4 border-b border-white/8">
                <div class="flex items-center justify-between">
                    <Link v-if="!sidebarCollapsed" :href="route('dashboard')" class="flex items-center gap-3 group" @click="playSound('pop')">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-black text-xl shadow-orange-glow" :style="{ background: `linear-gradient(135deg, ${themePalette.primary}, ${themePalette.secondary})` }">N</div>
                        <div class="flex-1">
                            <p class="text-sm font-black tracking-tighter uppercase italic">Nexus<span :style="{ color: themePalette.primary }">Edu</span></p>
                        </div>
                    </Link>
                    <Link v-else :href="route('dashboard')" class="w-full flex justify-center group">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-black text-xl shadow-orange-glow" :style="{ background: `linear-gradient(135deg, ${themePalette.primary}, ${themePalette.secondary})` }">N</div>
                    </Link>

                    <button
                        @click="toggleSidebarCollapse"
                        class="app-interactive w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center text-gray-400 hover:text-orange-300 hover:bg-orange-500/10 transition-all"
                        :title="sidebarCollapsed ? 'Expandir' : 'Colapsar'"
                    >
                        <i :class="sidebarCollapsed ? 'fa-solid fa-chevron-right' : 'fa-solid fa-chevron-left'"></i>
                    </button>
                </div>
            </div>

            <!-- Nav Items Scrollable Area -->
            <div class="flex-1 overflow-y-auto px-2 py-4 space-y-2">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="route(item.route)"
                    @click="playSound('click')"
                    class="relative group min-h-12 w-full rounded-2xl px-3 py-3 flex items-center justify-center gap-3 text-xs font-black uppercase tracking-[0.1em] transition-all duration-200 overflow-hidden"
                    :class="route().current(item.route + '*')
                        ? 'bg-orange-500/15 border border-orange-500/30 text-orange-300'
                        : 'text-gray-400 hover:bg-white/6 hover:text-white border border-transparent'"
                    :title="item.name"
                >
                    <!-- Active Indicator Bar (Left) -->
                    <div v-if="route().current(item.route + '*')" class="absolute left-0 top-0 h-full w-1 bg-orange-500 rounded-r-sm transition-all duration-200"></div>

                    <!-- Glow Effect (Active) -->
                    <div v-if="route().current(item.route + '*')" class="absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-200" :style="{ boxShadow: `inset 0 0 20px rgba(255,107,0,0.1)` }"></div>

                    <!-- Content -->
                    <div class="relative z-10 flex items-center gap-3 w-full" :class="sidebarCollapsed && 'justify-center'">
                        <i :class="item.icon" class="text-lg w-6 text-center flex-shrink-0"></i>
                        <span v-if="!sidebarCollapsed" class="flex-1 truncate text-sm">{{ item.name }}</span>
                    </div>
                </Link>
            </div>

            <!-- User Info Card (Bottom) -->
            <div class="border-t border-white/8 p-3 space-y-3">
                <!-- User Profile Card -->
                <div class="bg-white/4 border border-white/8 rounded-2xl p-3 space-y-2">
                    <!-- Avatar and Name -->
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center border flex-shrink-0" :style="{ backgroundColor: themePalette.soft, color: themePalette.primary, borderColor: themePalette.primary + '55' }">
                            <i :class="avatarIcon" class="text-sm"></i>
                        </div>
                        <div v-if="!sidebarCollapsed" class="flex-1 min-w-0">
                            <p class="text-xs font-black text-white truncate">{{ userName }}</p>
                            <p class="text-[10px] font-black text-gray-400 uppercase">{{ profileTitle || gamification.rank || 'NOVATO' }}</p>
                        </div>
                    </div>

                    <!-- XP Bar -->
                    <div v-if="!sidebarCollapsed" class="space-y-1">
                        <div class="flex items-center justify-between text-[9px]">
                            <span class="font-black text-gray-400">NIVEL {{ gamification.current }}</span>
                            <span class="text-gray-500">{{ gamification.xp }} XP</span>
                        </div>
                        <div class="h-1.5 bg-white/5 rounded-full overflow-hidden border border-white/10">
                            <div class="h-full transition-all duration-500" :style="{ width: `${Math.min(100, (gamification.progress || 0))}%`, background: `linear-gradient(90deg, ${themePalette.primary}, ${themePalette.secondary})` }"></div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('profile.edit')"
                        class="app-interactive flex-1 min-h-10 rounded-xl border border-white/8 bg-white/4 text-gray-300 hover:bg-white/6 hover:text-white px-3 py-2 flex items-center justify-center gap-2 text-xs font-black uppercase tracking-wider transition-all"
                    >
                        <i class="fa-solid fa-gear text-sm"></i>
                        <span v-if="!sidebarCollapsed">Perfil</span>
                    </Link>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="app-interactive flex-1 min-h-10 rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 hover:bg-red-500 hover:text-white px-3 py-2 flex items-center justify-center gap-2 text-xs font-black uppercase tracking-wider transition-all"
                    >
                        <i class="fa-solid fa-power-off text-sm"></i>
                        <span v-if="!sidebarCollapsed">Salir</span>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Mobile Sidebar Drawer -->
        <div v-if="mobileSidebarOpen" class="md:hidden fixed inset-0 z-50">
            <div class="absolute inset-0 bg-black/60" @click="closeMobileSidebar"></div>
            <aside class="absolute left-0 top-0 h-full w-80 max-w-[85vw] app-nav border-r border-white/10 p-4 overflow-y-auto">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/10">
                    <p class="text-sm font-black uppercase tracking-[0.2em]">Navegación</p>
                    <button @click="closeMobileSidebar" class="app-interactive min-h-11 min-w-11 rounded-xl bg-white/5 text-gray-300 hover:text-white">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="space-y-2">
                    <Link
                        v-for="item in navItems"
                        :key="'mobile-' + item.name"
                        :href="route(item.route)"
                        @click="playSound('click'); closeMobileSidebar()"
                        class="app-interactive min-h-11 w-full rounded-xl border px-3 py-3 flex items-center gap-3 text-xs font-black uppercase tracking-[0.15em] transition-all"
                        :class="route().current(item.route + '*')
                            ? 'border-orange-400/40 bg-orange-500/15 text-white'
                            : 'border-white/10 bg-white/3 text-gray-300 hover:bg-white/8 hover:text-white'"
                    >
                        <i :class="item.icon" class="text-sm w-4 text-center"></i>
                        <span>{{ item.name }}</span>
                    </Link>
                </div>

                <div class="mt-4 pt-4 border-t border-white/10 grid grid-cols-2 gap-2">
                    <Link
                        :href="route('profile.edit')"
                        @click="closeMobileSidebar"
                        class="app-interactive min-h-11 rounded-xl bg-white/5 border border-white/10 text-xs font-black uppercase tracking-widest text-gray-300 hover:text-white flex items-center justify-center"
                    >
                        Perfil
                    </Link>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="app-interactive min-h-11 rounded-xl bg-red-500/10 border border-red-500/30 text-xs font-black uppercase tracking-widest text-red-300"
                    >
                        Salir
                    </Link>
                </div>
            </aside>
        </div>

        <div :class="['transition-all duration-300', sidebarCollapsed ? 'md:pl-24' : 'md:pl-80']">

        <!-- Dynamic Page Heading -->
        <header class="bg-midnight relative z-10 pt-10" v-if="$slots.header">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Main Workspace -->
        <main class="relative z-10 pb-[calc(8rem+env(safe-area-inset-bottom))] md:pb-32">
            <slot />
        </main>

        <!-- Mobile Bottom Navigation -->
        <nav class="app-nav fixed bottom-0 inset-x-0 z-50 md:hidden border-t border-white/10 bg-cyber-gray/95 backdrop-blur-xl" style="padding-bottom: env(safe-area-inset-bottom);">
            <div class="grid grid-cols-5 gap-1 px-2 py-2">
                <Link
                    v-for="item in mobileQuickNav"
                    :key="item.name"
                    :href="route(item.route)"
                    @click="playSound('click')"
                    class="app-interactive min-h-11 rounded-xl flex flex-col items-center justify-center text-[10px] font-black uppercase tracking-wide"
                    :class="route().current(item.route + '*') ? 'bg-orange-500/20 text-orange-300 border border-orange-500/40' : 'text-gray-400 hover:bg-white/5'"
                >
                    <i :class="item.icon"></i>
                    <span class="mt-1 leading-none">{{ item.name }}</span>
                </Link>
            </div>
        </nav>

        <!-- Global Footer -->
        <footer class="app-footer bg-cyber-gray/50 border-t border-white/5 py-16 relative z-10">
            <div class="max-w-7xl mx-auto px-6 text-center space-y-4">
                <div class="flex items-center justify-center gap-3 opacity-30 grayscale hover:grayscale-0 transition-all cursor-crosshair">
                   <div class="w-6 h-6 bg-orange-600 rounded-lg flex items-center justify-center text-white text-[10px] font-black">N</div>
                   <span class="text-xs font-black tracking-tighter uppercase italic">NexusEdu Terminal</span>
                </div>
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.4em]">© 2026 CODEX PROTOCOL. ALL RIGHTS RESERVED.</p>
            </div>
        </footer>

        </div>

        <!-- Background Grid Decoration -->
        <div class="fixed inset-0 pointer-events-none opacity-[0.02] bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>

        <AchievementUnlock />
    </div>
</template>

<style scoped>
.shadow-orange-glow {
    box-shadow: 0 0 30px rgba(255, 107, 0, 0.3);
}

.glow-text {
    text-shadow: 0 0 10px rgba(255, 255, 255, 0.4);
}
</style>

