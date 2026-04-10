<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

const navItems = [
    { name: 'Panel', route: 'dashboard', icon: 'fa-solid fa-house' },
    { name: 'Simulacro', route: 'simulator.index', icon: 'fa-solid fa-graduation-cap' },
    { name: 'Quizzes', route: 'quiz.index', icon: 'fa-solid fa-lightbulb' },
    { name: 'Mi Progreso', route: 'progress.index', icon: 'fa-solid fa-chart-line' },
    { name: 'Repasar', route: 'review.index', icon: 'fa-solid fa-repeat' },
];
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Sidebar and Nav combined for premium look -->
        <nav class="bg-white border-b border-gray-100 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-8">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('dashboard')" class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center text-white font-black text-xl">N</div>
                                <span class="text-xl font-black tracking-tighter text-gray-900">NexusEdu</span>
                            </Link>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-6 sm:-my-px sm:flex h-full">
                            <Link 
                                v-for="item in navItems" 
                                :key="item.name"
                                :href="route(item.route)"
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-bold leading-5 transition duration-150 ease-in-out"
                                :class="route().current(item.route + '*') ? 'border-orange-500 text-gray-900' : 'border-transparent text-gray-400 hover:text-gray-700 hover:border-gray-300'"
                            >
                                <i :class="item.icon" class="mr-2 text-xs"></i>
                                {{ item.name }}
                            </Link>
                        </div>
                    </div>

                    <div class="hidden sm:flex sm:items-center sm:ms-6">
                        <!-- User Dropdown -->
                        <div class="relative flex items-center gap-4">
                            <div class="text-right hidden lg:block">
                                <p class="text-xs font-bold text-gray-900">{{ $page.props.auth.user.name }}</p>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Aspirante Pro</p>
                            </div>
                            <Link :href="route('profile.edit')" class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-orange-50 hover:text-orange-600 transition-all">
                                <i class="fa-solid fa-user"></i>
                            </Link>
                            <Link :href="route('logout')" method="post" as="button" class="w-10 h-10 rounded-xl bg-gray-50 text-gray-400 flex items-center justify-center hover:bg-red-50 hover:text-red-500 transition-all">
                                <i class="fa-solid fa-power-off"></i>
                            </Link>
                        </div>
                    </div>

                    <!-- Hamburger -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                            <i :class="showingNavigationDropdown ? 'fa-solid fa-xmark' : 'fa-solid fa-bars'"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu -->
            <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden bg-white border-t border-gray-100">
                <div class="pt-2 pb-3 space-y-1">
                    <Link 
                        v-for="item in navItems" 
                        :key="item.name"
                        :href="route(item.route)"
                        class="block w-full ps-3 pe-4 py-3 border-s-4 text-left text-base font-bold transition duration-150 ease-in-out"
                        :class="route().current(item.route + '*') ? 'border-orange-500 text-orange-700 bg-orange-50 font-black' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300'"
                    >
                        <i :class="item.icon" class="mr-3 w-4"></i>
                        {{ item.name }}
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Page Heading -->
        <header class="bg-white border-b border-gray-100 shadow-sm" v-if="$slots.header">
            <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Page Content -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-100 py-12 mt-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">&copy; 2024 NexusEdu Platform. Construido para la excelencia académica.</p>
            </div>
        </footer>
    </div>
</template>
