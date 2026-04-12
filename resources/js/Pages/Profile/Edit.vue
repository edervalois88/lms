<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: props.user.name ?? '',
    email: props.user.email ?? '',
});

const passwordForm = useForm({
    password: '',
});

const submit = () => {
    form.patch(route('profile.update'));
};

const destroyAccount = () => {
    if (!confirm('Esta accion eliminara tu cuenta. Deseas continuar?')) return;
    passwordForm.delete(route('profile.destroy'));
};
</script>

<template>
    <Head title="Perfil" />

    <div class="min-h-screen bg-slate-950 text-slate-100 p-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-black">Perfil</h1>
            <p class="text-slate-400 mt-1">Actualiza tu informacion personal.</p>

            <form @submit.prevent="submit" class="mt-8 space-y-4 rounded-2xl border border-slate-800 bg-slate-900 p-6">
                <div>
                    <label class="text-sm text-slate-400">Nombre</label>
                    <input v-model="form.name" type="text" class="w-full mt-1 rounded-xl bg-slate-800 border border-slate-700 px-3 py-2" />
                    <p v-if="form.errors.name" class="text-sm text-rose-400 mt-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="text-sm text-slate-400">Email</label>
                    <input v-model="form.email" type="email" class="w-full mt-1 rounded-xl bg-slate-800 border border-slate-700 px-3 py-2" />
                    <p v-if="form.errors.email" class="text-sm text-rose-400 mt-1">{{ form.errors.email }}</p>
                </div>

                <button type="submit" class="rounded-xl bg-orange-600 px-4 py-2 font-bold hover:bg-orange-500">Guardar cambios</button>
            </form>

            <form @submit.prevent="destroyAccount" class="mt-6 space-y-3 rounded-2xl border border-rose-900/50 bg-rose-950/20 p-6">
                <h2 class="font-bold text-rose-300">Eliminar cuenta</h2>
                <p class="text-sm text-rose-200/80">Confirma tu password para eliminar la cuenta.</p>
                <input v-model="passwordForm.password" type="password" class="w-full mt-1 rounded-xl bg-slate-800 border border-slate-700 px-3 py-2" placeholder="Password actual" />
                <p v-if="passwordForm.errors.password" class="text-sm text-rose-400">{{ passwordForm.errors.password }}</p>
                <button type="submit" class="rounded-xl bg-rose-700 px-4 py-2 font-bold hover:bg-rose-600">Eliminar cuenta</button>
            </form>
        </div>
    </div>
</template>
