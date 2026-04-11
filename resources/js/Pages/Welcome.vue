<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { animate, spring, stagger } from 'motion';
import { playSound } from '@/Utils/SoundService';

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
});

onMounted(() => {
    animate(".hero-glow", { opacity: [0, 0.4], scale: [0.8, 1.2] }, { duration: 2, repeat: Infinity, direction: "alternate" });
    animate(".hero-title", { opacity: [0, 1], y: [100, 0] }, { duration: 1, easing: spring() });
    animate(".hero-subtitle", { opacity: [0, 1] }, { delay: 0.5, duration: 1 });
    animate(".cta-launch", { scale: [0.9, 1], opacity: [0, 1] }, { delay: 0.8, duration: 0.8, easing: spring() });
});
</script>

<template>
    <Head title="NexusEdu - Mission: UNAM" />

    <div class="min-h-screen bg-midnight text-white selection:bg-orange-500 selection:text-white overflow-hidden font-sans">
        
        <!-- Background Decorations -->
        <div class="fixed inset-0 pointer-events-none">
            <div class="absolute top-1/4 -left-20 w-96 h-96 bg-orange-600/10 rounded-full blur-[120px] hero-glow"></div>
            <div class="absolute bottom-1/4 -right-20 w-[30rem] h-[30rem] bg-blue-600/5 rounded-full blur-[150px]"></div>
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-[0.03]"></div>
        </div>

        <!-- Navigation -->
        <nav class="max-w-7xl mx-auto px-8 py-10 flex items-center justify-between relative z-50">
            <div class="flex items-center gap-4 group cursor-pointer" @click="playSound('pop')">
                <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-orange-700 rounded-2xl flex items-center justify-center text-white text-3xl font-black shadow-orange-glow transform group-hover:rotate-12 transition-transform duration-500">N</div>
                <span class="text-3xl font-black tracking-tighter uppercase italic">Nexus<span class="text-orange-500">Edu</span></span>
            </div>
            
            <div class="flex items-center gap-10">
                <Link v-if="canLogin" :href="route('login')" class="font-black text-xs uppercase tracking-[0.2em] text-gray-400 hover:text-white transition-colors">Port de Acceso</Link>
                <Link 
                    v-if="canRegister"
                    :href="route('register')"
                    @click="playSound('success')"
                    class="bg-white text-black px-10 py-4 rounded-full font-black text-xs uppercase tracking-widest hover:bg-orange-500 hover:text-white transition-all transform hover:scale-110 active:scale-95 shadow-xl shadow-white/5"
                >
                    Iniciar Misión
                </Link>
            </div>
        </nav>

        <!-- Hero Section -->
        <main class="relative z-10 pt-24 pb-48">
            <div class="max-w-7xl mx-auto px-8 grid lg:grid-cols-2 gap-24 items-center">
                
                <div class="space-y-12 text-center lg:text-left">
                    <div class="inline-flex items-center gap-3 bg-white/5 border border-white/10 px-6 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.3em] text-orange-400">
                        <span class="w-2 h-2 bg-orange-500 rounded-full animate-pulse shadow-orange-glow"></span>
                        Status: Sistema Operativo
                    </div>
                    
                    <h1 class="text-7xl md:text-[10rem] font-black leading-[0.8] hero-title uppercase italic italic-glow">
                        DOMINA<br/><span class="text-orange-600">LA META</span>
                    </h1>
                    
                    <p class="text-xl text-gray-400 font-medium max-w-xl mx-auto lg:mx-0 hero-subtitle leading-relaxed">
                        No solo estudies. Hackeamos el examen de la UNAM con IA para decirte exactamente <span class="text-white border-b-2 border-orange-500">cuántos aciertos te faltan</span> para ganar.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center gap-8 cta-launch justify-center lg:justify-start">
                        <Link 
                            :href="route('register')"
                            @click="playSound('success')"
                            class="w-full sm:w-auto bg-orange-600 text-white px-12 py-7 rounded-3xl font-black text-2xl shadow-orange-glow hover:bg-orange-500 transition-all transform hover:-translate-y-2 active:scale-95 group flex items-center justify-center"
                        >
                            Comenzar ahora
                            <i class="fa-solid fa-bolt-lightning ml-4 group-hover:animate-bounce"></i>
                        </Link>
                        <div class="flex items-center gap-6">
                            <div class="w-12 h-12 rounded-full border border-white/10 flex items-center justify-center text-orange-500 hover:bg-white/5 cursor-pointer transition-colors" @click="playSound('click')">
                                <i class="fa-solid fa-play ml-1"></i>
                            </div>
                            <span class="text-xs font-black uppercase tracking-widest text-gray-500">Ver Trailer</span>
                        </div>
                    </div>
                </div>

                <!-- 3D Visual Mockup -->
                <div class="relative hidden lg:block animate-float">
                    <div class="glass-morphism-dark p-1 rounded-[4rem] relative z-10 rotate-3 group hover:rotate-0 transition-all duration-700">
                        <div class="bg-midnight rounded-[3.8rem] overflow-hidden border border-white/5 aspect-square flex flex-col items-center justify-center p-12 text-center">
                            <div class="w-40 h-40 bg-orange-600/10 rounded-full flex items-center justify-center mb-8 shadow-orange-glow border border-orange-500/20">
                                <i class="fa-solid fa-brain text-7xl text-orange-500"></i>
                            </div>
                            <h3 class="text-4xl font-black mb-4 uppercase italic">Cerebro AI</h3>
                            <p class="text-gray-500 font-bold uppercase tracking-widest text-xs">Analizando 12.4k respuestas...</p>
                            
                            <div class="mt-12 w-full space-y-4">
                                <div class="h-2 bg-white/5 rounded-full overflow-hidden">
                                    <div class="w-3/4 h-full bg-orange-500 shadow-orange-glow"></div>
                                </div>
                                <div class="flex justify-between text-[10px] font-black text-orange-400">
                                    <span>NIVEL 42</span>
                                    <span>CALIBRANDO...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Glow orbit -->
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[120%] h-[120%] border border-white/5 rounded-full animate-[spin_20s_linear_infinite]"></div>
                </div>
            </div>
        </main>

        <!-- Dynamic Feature Ticker -->
        <div class="bg-orange-600/10 py-10 border-y border-white/5 overflow-hidden whitespace-nowrap">
            <div class="inline-block animate-[scroll_30s_linear_infinite] px-4 space-x-12">
                <span v-for="i in 10" :key="i" class="text-2xl font-black uppercase italic text-orange-500/40 tracking-tighter">
                    • ANALÍTICA IA • GAMIFICACIÓN TOTAL • PREGUNTAS REALES • SIMULACROS • MODO NOCHE •
                </span>
            </div>
        </div>

    </div>
</template>

<style scoped>
@keyframes scroll {
    from { transform: translateX(0); }
    to { transform: translateX(-50%); }
}

.italic-glow {
    text-shadow: 0 0 30px rgba(255, 107, 0, 0.2);
}

.shadow-orange-glow {
    box-shadow: 0 0 40px rgba(255, 107, 0, 0.3);
}
</style>
