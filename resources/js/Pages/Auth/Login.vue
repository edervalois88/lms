<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { animate, spring } from 'motion';
import { playSound } from '@/Utils/SoundService';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    playSound('click');
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

onMounted(() => {
    animate(".login-portal", { opacity: [0, 1], y: [100, 0], scale: [0.9, 1] }, { duration: 1, easing: spring() });
});
</script>

<template>
    <Head title="Acceso al Nódulo - NexusEdu" />

    <div class="min-h-screen bg-midnight text-white flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
        
        <!-- Background Decorations -->
        <div class="fixed inset-0 pointer-events-none">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[60rem] h-[60rem] bg-orange-600/5 rounded-full blur-[200px]"></div>
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-[0.02]"></div>
        </div>

        <div class="max-w-md w-full mx-auto relative z-10 login-portal">
            
            <div class="text-center mb-10">
                <Link href="/" class="inline-flex items-center gap-4 mb-8 group" @click="playSound('pop')">
                    <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-orange-700 rounded-2xl flex items-center justify-center text-white text-3xl font-black shadow-orange-glow transform group-hover:rotate-12 transition-transform duration-500">N</div>
                    <span class="text-3xl font-black tracking-tighter uppercase italic">Nexus<span class="text-orange-500">Edu</span></span>
                </Link>
                <div class="flex items-center justify-center gap-3 text-orange-500 mb-2">
                    <span class="w-1 h-3 bg-orange-500"></span>
                    <h2 class="text-sm font-black uppercase tracking-[0.4em]">PORTAL DE ACCESO</h2>
                    <span class="w-1 h-3 bg-orange-500"></span>
                </div>
                <p class="text-gray-500 font-bold uppercase tracking-widest text-[10px]">Identificación Biométrica Requerida</p>
            </div>

            <div v-if="status" class="mb-6 font-bold text-xs text-orange-400 bg-orange-400/5 p-4 rounded-xl border border-orange-400/20 italic text-center">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-8 glass-morphism-dark p-10 rounded-[3rem] border border-white/5 shadow-2xl">
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-[0.3em] ml-1">Identificador de Usuario</label>
                    <div class="relative group">
                        <input 
                            type="email" 
                            v-model="form.email" 
                            placeholder="OPERADOR@NEXUS"
                            required 
                            autofocus
                            class="w-full bg-white/5 border-2 border-white/5 rounded-2xl p-5 text-white placeholder:text-white/10 focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all outline-none font-bold"
                        />
                        <i class="fa-solid fa-user absolute right-5 top-1/2 -translate-y-1/2 text-white/10 group-focus-within:text-orange-500 transition-colors"></i>
                    </div>
                    <p v-if="form.errors.email" class="text-orange-500 text-[10px] font-black uppercase tracking-widest mt-2 ml-1 animate-pulse">{{ form.errors.email }}</p>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between ml-1">
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-[0.3em]">Protocolo de Seguridad</label>
                        <Link v-if="canResetPassword" :href="route('password.request')" class="text-[10px] font-bold text-orange-600 uppercase hover:underline tracking-widest">¿Clave Perdida?</Link>
                    </div>
                    <div class="relative group">
                        <input 
                            type="password" 
                            v-model="form.password" 
                            placeholder="********"
                            required 
                            class="w-full bg-white/5 border-2 border-white/5 rounded-2xl p-5 text-white placeholder:text-white/10 focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all outline-none font-bold"
                        />
                        <i class="fa-solid fa-lock-keyhole absolute right-5 top-1/2 -translate-y-1/2 text-white/10 group-focus-within:text-orange-500 transition-colors"></i>
                    </div>
                    <p v-if="form.errors.password" class="text-orange-500 text-[10px] font-black uppercase tracking-widest mt-2 ml-1 animate-pulse">{{ form.errors.password }}</p>
                </div>

                <div class="flex items-center px-1">
                    <label class="flex items-center group cursor-pointer">
                        <input type="checkbox" v-model="form.remember" class="hidden" />
                        <div class="w-6 h-6 border-2 border-white/10 rounded-lg mr-4 flex items-center justify-center transition-all group-hover:border-orange-500" :class="{ 'bg-orange-500 border-orange-500 shadow-orange-glow': form.remember }">
                            <i v-if="form.remember" class="fa-solid fa-check text-white text-[10px]"></i>
                        </div>
                        <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Mantener Enlace Activo</span>
                    </label>
                </div>

                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="w-full bg-orange-600 text-white py-6 rounded-2xl font-black text-xl hover:bg-orange-500 transition-all shadow-orange-glow disabled:opacity-50 transform active:scale-95 group uppercase italic tracking-tighter"
                >
                    {{ form.processing ? 'Sincronizando...' : 'Autorizar Acceso' }}
                    <i class="fa-solid fa-chevron-right ml-4 group-hover:translate-x-2 transition-transform"></i>
                </button>
            </form>

            <div class="text-center mt-12 bg-white/5 p-6 rounded-3xl border border-white/5">
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-4">¿Nuevo en el programa?</p>
                <Link :href="route('register')" class="text-orange-500 hover:text-white font-black text-sm uppercase tracking-widest transition-colors flex items-center justify-center gap-3">
                    Inscribirme ahora
                    <i class="fa-solid fa-rocket"></i>
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.shadow-orange-glow {
    box-shadow: 0 0 30px rgba(255, 107, 0, 0.2);
}
</style>
