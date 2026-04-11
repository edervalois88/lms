<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { animate, spring } from 'motion';
import { playSound } from '@/Utils/SoundService';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    playSound('success');
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

onMounted(() => {
    animate(".register-card", { opacity: [0, 1], y: [50, 0], scale: [0.95, 1] }, { duration: 0.8, easing: spring() });
});
</script>

<template>
    <Head title="Registro - NexusEdu" />

    <div class="min-h-screen bg-white flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full mx-auto register-card">
            
            <div class="text-center mb-10">
                <Link href="/" class="inline-flex items-center gap-3 mb-8 group">
                    <div class="w-12 h-12 bg-gray-900 rounded-2xl flex items-center justify-center text-white text-2xl font-black shadow-lg transform group-hover:rotate-12 transition-transform">N</div>
                    <span class="text-2xl font-black tracking-tight text-gray-900">Nexus<span class="text-orange-600">Edu</span></span>
                </Link>
                <h2 class="text-3xl font-black text-gray-900">Comienza tu viaje</h2>
                <p class="text-gray-500 font-medium mt-2">Únete a cientos de aspirantes a la UNAM.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6 bg-gray-50 p-10 rounded-[2.5rem] shadow-sm border border-gray-100">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 pl-1">Nombre Completo</label>
                    <input 
                        type="text" 
                        v-model="form.name" 
                        required 
                        class="w-full bg-white border-2 border-gray-100 rounded-2xl p-4 text-gray-900 focus:ring-4 focus:ring-orange-100 focus:border-orange-500 transition-all outline-none"
                    />
                    <p v-if="form.errors.name" class="text-red-500 text-xs font-bold mt-2 ml-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 pl-1">Correo Electrónico</label>
                    <input 
                        type="email" 
                        v-model="form.email" 
                        required 
                        class="w-full bg-white border-2 border-gray-100 rounded-2xl p-4 text-gray-900 focus:ring-4 focus:ring-orange-100 focus:border-orange-500 transition-all outline-none"
                    />
                    <p v-if="form.errors.email" class="text-red-500 text-xs font-bold mt-2 ml-1">{{ form.errors.email }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 pl-1">Contraseña</label>
                        <input 
                            type="password" 
                            v-model="form.password" 
                            required 
                            class="w-full bg-white border-2 border-gray-100 rounded-2xl p-4 text-gray-900 focus:ring-4 focus:ring-orange-100 focus:border-orange-500 transition-all outline-none"
                        />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 pl-1">Confirmar</label>
                        <input 
                            type="password" 
                            v-model="form.password_confirmation" 
                            required 
                            class="w-full bg-white border-2 border-gray-100 rounded-2xl p-4 text-gray-900 focus:ring-4 focus:ring-orange-100 focus:border-orange-500 transition-all outline-none"
                        />
                    </div>
                    <p v-if="form.errors.password" class="col-span-2 text-red-500 text-xs font-bold mt-2 ml-1">{{ form.errors.password }}</p>
                </div>

                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="w-full bg-orange-500 text-white py-5 rounded-2xl font-black text-xl hover:bg-orange-600 transition-all shadow-xl shadow-orange-500/10 disabled:opacity-50 transform active:scale-95 group"
                >
                    {{ form.processing ? 'Creando cuenta...' : 'Crear mi cuenta' }}
                    <i class="fa-solid fa-graduation-cap ml-3 text-sm group-hover:rotate-12 transition-transform"></i>
                </button>
            </form>

            <p class="text-center mt-8 text-sm font-bold text-gray-400">
                ¿Ya tienes cuenta? 
                <Link :href="route('login')" class="text-orange-600 hover:underline">Entrar aquí</Link>
            </p>
        </div>
    </div>
</template>
