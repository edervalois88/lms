<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { playSound } from '@/Utils/SoundService';

const showingNavigationDropdown = ref(false);

const navItems = [
    { name: 'NODO CENTRAL', route: 'dashboard', icon: 'fa-solid fa-house' },
    { name: 'SIMULACRO', route: 'simulator.index', icon: 'fa-solid fa-graduation-cap' },
    { name: 'ENTRENAMIENTO', route: 'quiz.index', icon: 'fa-solid fa-bolt-lightning' },
    { name: 'ANALÍTICA', route: 'progress.index', icon: 'fa-solid fa-chart-line' },
    { name: 'REPETICIÓN', route: 'review.index', icon: 'fa-solid fa-repeat' },
];
</script>

<template>
    <div class="min-h-screen bg-midnight text-white selection:bg-orange-500/30">
        
        <!-- Premium Cyber Nav -->
        <nav class="bg-cyber-gray/80 backdrop-blur-xl border-b border-white/5 sticky top-0 z-[100]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-12">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('dashboard')" class="flex items-center gap-3 group" @click="playSound('pop')">
                                <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-orange-700 rounded-xl flex items-center justify-center text-white font-black text-2xl shadow-orange-glow transform group-hover:rotate-12 transition-transform">N</div>
                                <span class="text-2xl font-black tracking-tighter uppercase italic hidden md:block">Nexus<span class="text-orange-500">Edu</span></span>
                            </Link>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:flex h-full">
                            <Link 
                                v-for="item in navItems" 
                                :key="item.name"
                                :href="route(item.route)"
                                @click="playSound('click')"
                                class="inline-flex items-center px-1 pt-1 border-b-4 text-[10px] font-black uppercase tracking-[0.2em] transition-all duration-300"
                                :class="route().current(item.route + '*') ? 'border-orange-500 text-white glow-text' : 'border-transparent text-gray-500 hover:text-gray-300 hover:border-white/10'"
                            >
                                <i :class="item.icon" class="mr-3 text-[10px]"></i>
                                {{ item.name }}
                            </Link>
                        </div>
                    </div>

                    <div class="hidden sm:flex sm:items-center sm:ms-6 gap-6">
                        <!-- User Sector -->
                        <div class="flex items-center gap-4 bg-white/5 px-6 py-2 rounded-2xl border border-white/5">
                            <div class="text-right hidden lg:block">
                                <p class="text-[10px] font-black text-white uppercase">{{ $page.props.auth.user.name }}</p>
                                <p class="text-[8px] text-orange-500 font-black uppercase tracking-widest">{{ $page.props.auth.gamification?.rank || 'OPERADOR' }}</p>
                            </div>
                            <div class="w-10 h-10 bg-orange-500/20 rounded-xl flex items-center justify-center text-orange-500 border border-orange-500/30">
                                {{ $page.props.auth.gamification?.current || 1 }}
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <Link :href="route('profile.edit')" class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/10 transition-all border border-white/5">
                                <i class="fa-solid fa-gear"></i>
                            </Link>
                            <Link :href="route('logout')" method="post" as="button" class="w-12 h-12 rounded-2xl bg-red-500/10 text-red-400 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all border border-red-500/20">
                                <i class="fa-solid fa-power-off"></i>
                            </Link>
                        </div>
                    </div>

                    <!-- Mobile Trigger -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center p-3 rounded-xl text-gray-400 hover:text-white hover:bg-white/5 transition duration-150 ease-in-out">
                            <i :class="showingNavigationDropdown ? 'fa-solid fa-xmark' : 'fa-solid fa-bars-staggered'"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Menu -->
            <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden bg-cyber-gray border-t border-white/5">
                <div class="pt-4 pb-6 space-y-2 px-4 text-center">
                    <Link 
                        v-for="item in navItems" 
                        :key="item.name"
                        :href="route(item.route)"
                        @click="playSound('click')"
                        class="block w-full py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.3em] transition-all"
                        :class="route().current(item.route + '*') ? 'bg-orange-500 text-white shadow-orange-glow' : 'text-gray-500 hover:bg-white/5'"
                    >
                        {{ item.name }}
                    </Link>
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
        <main class="relative z-10 pb-32">
            <slot />
        </main>

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
