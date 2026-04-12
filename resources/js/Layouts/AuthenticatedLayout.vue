<script setup>
import { computed, onMounted, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { playSound } from '@/Utils/SoundService';
import { useTheme } from '@/Composables/useTheme';

const showingNavigationDropdown = ref(false);
const { theme, initializeTheme, toggleTheme } = useTheme();
const page = usePage();

onMounted(() => {
    initializeTheme();
});

const baseNavItems = [
    { name: 'NODO CENTRAL', route: 'dashboard', icon: 'fa-solid fa-house' },
    { name: 'SIMULACRO', route: 'simulator.index', icon: 'fa-solid fa-graduation-cap' },
    { name: 'ENTRENAMIENTO', route: 'quiz.index', icon: 'fa-solid fa-bolt-lightning' },
    { name: 'DAILY XP', route: 'practice.daily', icon: 'fa-solid fa-fire' },
    { name: 'ANALÍTICA', route: 'progress.index', icon: 'fa-solid fa-chart-line' },
    { name: 'REPETICIÓN', route: 'review.index', icon: 'fa-solid fa-repeat' },
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
    <div class="min-h-screen bg-midnight text-white selection:bg-orange-500/30">
        
        <!-- Premium Cyber Nav -->
        <nav class="bg-cyber-gray/80 backdrop-blur-xl border-b border-white/5 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-12">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('dashboard')" class="flex items-center gap-3 group" @click="playSound('pop')">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-black text-2xl shadow-orange-glow transform group-hover:rotate-12 transition-transform" :style="{ background: `linear-gradient(135deg, ${themePalette.primary}, ${themePalette.secondary})` }">N</div>
                                <span class="text-2xl font-black tracking-tighter uppercase italic hidden md:block">Nexus<span :style="{ color: themePalette.primary }">Edu</span></span>
                            </Link>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-8 md:-my-px md:flex h-full">
                            <Link 
                                v-for="item in navItems" 
                                :key="item.name"
                                :href="route(item.route)"
                                @click="playSound('click')"
                                class="inline-flex items-center px-1 pt-1 min-h-11 border-b-4 text-[10px] font-black uppercase tracking-[0.2em] transition-all duration-300"
                                :class="route().current(item.route + '*') ? 'border-orange-500 text-white glow-text' : 'border-transparent text-gray-500 hover:text-gray-300 hover:border-white/10'"
                            >
                                <i :class="item.icon" class="mr-3 text-[10px]"></i>
                                {{ item.name }}
                            </Link>
                        </div>
                    </div>

                    <div class="hidden md:flex md:items-center md:ms-6 gap-6">
                        <button
                            type="button"
                            @click="toggleTheme()"
                            class="w-12 h-12 min-h-11 rounded-2xl bg-white/5 flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/10 transition-all border border-white/5"
                            :title="theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
                        >
                            <i :class="theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon'"></i>
                        </button>

                        <!-- User Sector -->
                        <div class="flex items-center gap-4 bg-white/5 px-6 py-2 rounded-2xl border border-white/5">
                            <div class="text-right hidden lg:block">
                                <p class="text-[10px] font-black text-white uppercase">{{ $page.props.auth.user.name }}</p>
                                <p class="text-[8px] font-black uppercase tracking-widest" :style="{ color: themePalette.primary }">{{ profileTitle || $page.props.auth.gamification?.rank || 'OPERADOR' }}</p>
                            </div>
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center border" :style="{ backgroundColor: themePalette.soft, color: themePalette.primary, borderColor: themePalette.primary + '55' }">
                                <i :class="avatarIcon"></i>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <Link :href="route('profile.edit')" class="w-12 h-12 min-h-11 rounded-2xl bg-white/5 flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/10 transition-all border border-white/5">
                                <i class="fa-solid fa-gear"></i>
                            </Link>
                            <Link :href="route('logout')" method="post" as="button" class="w-12 h-12 min-h-11 rounded-2xl bg-red-500/10 text-red-400 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all border border-red-500/20">
                                <i class="fa-solid fa-power-off"></i>
                            </Link>
                        </div>
                    </div>

                    <!-- Mobile Trigger -->
                    <div class="-me-2 flex items-center gap-2 md:hidden">
                        <button
                            type="button"
                            @click="toggleTheme()"
                            class="inline-flex items-center justify-center min-h-11 min-w-11 p-3 rounded-xl text-gray-400 hover:text-white hover:bg-white/5 transition duration-150 ease-in-out"
                            :title="theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
                        >
                            <i :class="theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon'"></i>
                        </button>
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center min-h-11 min-w-11 p-3 rounded-xl text-gray-400 hover:text-white hover:bg-white/5 transition duration-150 ease-in-out">
                            <i :class="showingNavigationDropdown ? 'fa-solid fa-xmark' : 'fa-solid fa-bars-staggered'"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Menu -->
            <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="md:hidden bg-cyber-gray border-t border-white/5">
                <div class="pt-4 pb-6 space-y-2 px-4 text-center">
                    <Link 
                        v-for="item in navItems" 
                        :key="item.name"
                        :href="route(item.route)"
                        @click="playSound('click')"
                        class="block w-full py-4 min-h-11 rounded-2xl text-[10px] font-black uppercase tracking-[0.3em] transition-all"
                        :class="route().current(item.route + '*') ? 'bg-orange-500 text-white shadow-orange-glow' : 'text-gray-500 hover:bg-white/5'"
                    >
                        {{ item.name }}
                    </Link>

                    <div class="pt-3 grid grid-cols-3 gap-3">
                        <Link
                            :href="route('profile.edit')"
                            class="py-3 min-h-11 rounded-xl bg-white/5 border border-white/10 text-xs font-black uppercase tracking-widest text-gray-400 hover:text-white"
                        >
                            Perfil
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="py-3 min-h-11 rounded-xl bg-red-500/10 border border-red-500/30 text-xs font-black uppercase tracking-widest text-red-400"
                        >
                            Salir
                        </Link>
                        <button
                            type="button"
                            @click="toggleTheme()"
                            class="py-3 min-h-11 rounded-xl bg-white/5 border border-white/10 text-xs font-black uppercase tracking-widest text-gray-400 hover:text-white"
                        >
                            {{ theme === 'dark' ? 'Claro' : 'Oscuro' }}
                        </button>
                    </div>
                </div>
            </div>
        </nav>

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
        <nav class="fixed bottom-0 inset-x-0 z-50 md:hidden border-t border-white/10 bg-cyber-gray/95 backdrop-blur-xl" style="padding-bottom: env(safe-area-inset-bottom);">
            <div class="grid grid-cols-5 gap-1 px-2 py-2">
                <Link
                    v-for="item in mobileQuickNav"
                    :key="item.name"
                    :href="route(item.route)"
                    @click="playSound('click')"
                    class="min-h-11 rounded-xl flex flex-col items-center justify-center text-[10px] font-black uppercase tracking-wide"
                    :class="route().current(item.route + '*') ? 'bg-orange-500/20 text-orange-300 border border-orange-500/40' : 'text-gray-400 hover:bg-white/5'"
                >
                    <i :class="item.icon"></i>
                    <span class="mt-1 leading-none">{{ item.name }}</span>
                </Link>
            </div>
        </nav>

        <!-- Global Footer -->
        <footer class="bg-cyber-gray/50 border-t border-white/5 py-16 relative z-10">
            <div class="max-w-7xl mx-auto px-6 text-center space-y-4">
                <div class="flex items-center justify-center gap-3 opacity-30 grayscale hover:grayscale-0 transition-all cursor-crosshair">
                   <div class="w-6 h-6 bg-orange-600 rounded-lg flex items-center justify-center text-white text-[10px] font-black">N</div>
                   <span class="text-xs font-black tracking-tighter uppercase italic">NexusEdu Terminal</span>
                </div>
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.4em]">© 2026 CODEX PROTOCOL. ALL RIGHTS RESERVED.</p>
            </div>
        </footer>

        <!-- Background Grid Decoration -->
        <div class="fixed inset-0 pointer-events-none opacity-[0.02] bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
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

