<script setup>
import { computed } from 'vue';
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    feature: {
        type: String,
        default: 'premium',
    },
});

const emit = defineEmits(['close']);
const loading = ref(false);

const title = computed(() => {
    if (props.feature === 'ai_tutor') {
        return 'Límite diario del Tutor IA alcanzado';
    }

    if (props.feature === 'simulation') {
        return 'Límite semanal de Simulacros alcanzado';
    }

    return 'Límite gratuito alcanzado';
});

const startCheckout = async () => {
    if (loading.value) {
        return;
    }

    loading.value = true;

    try {
        const response = await axios.post(route('checkout.create'), {}, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
        });

        const checkoutUrl = response?.data?.url;
        if (typeof checkoutUrl === 'string' && checkoutUrl !== '') {
            window.location.href = checkoutUrl;
            return;
        }
    } catch (_error) {
        // Fail-open UX: keep modal visible if checkout fails.
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-70 flex items-center justify-center p-4">
        <button type="button" class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="emit('close')" />

        <div class="relative w-full max-w-xl rounded-3xl border border-fuchsia-400/40 bg-[radial-gradient(circle_at_20%_10%,rgba(217,70,239,0.22),rgba(17,24,39,0.96)_38%)] p-7 md:p-9 shadow-[0_0_45px_rgba(217,70,239,0.35)]">
            <div class="absolute -top-10 -right-8 h-32 w-32 rounded-full bg-amber-400/20 blur-2xl"></div>
            <div class="absolute -bottom-10 -left-8 h-32 w-32 rounded-full bg-fuchsia-400/25 blur-2xl"></div>

            <p class="text-[11px] uppercase tracking-[0.25em] font-black text-amber-300">Nivel Élite</p>
            <h3 class="mt-2 text-2xl md:text-3xl font-black text-white leading-tight">{{ title }}</h3>
            <p class="mt-4 text-sm md:text-base text-fuchsia-100/90 font-semibold">
                Has alcanzado tu límite gratuito. Desbloquea el Nivel Élite para dominar tu examen sin restricciones.
            </p>

            <div class="mt-6 rounded-2xl border border-white/15 bg-white/5 p-4">
                <p class="text-[11px] uppercase tracking-widest font-black text-amber-300">Beneficios premium</p>
                <ul class="mt-3 space-y-2 text-sm text-gray-100 font-semibold">
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check text-amber-300"></i> Simulacros ilimitados cada semana</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check text-amber-300"></i> Tutor IA sin límites diarios</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check text-amber-300"></i> Progreso acelerado y acompañamiento continuo</li>
                </ul>
            </div>

            <div class="mt-7 flex flex-col sm:flex-row gap-3">
                <button
                    type="button"
                    class="grow rounded-xl bg-linear-to-r from-amber-400 to-fuchsia-500 text-black font-black uppercase tracking-wider py-3 shadow-[0_0_18px_rgba(251,191,36,0.45)] hover:brightness-110 disabled:opacity-60"
                    :disabled="loading"
                    @click="startCheckout"
                >
                    <span v-if="loading" class="inline-flex items-center gap-2">
                        <span class="inline-block h-4 w-4 rounded-full border-2 border-black/70 border-t-transparent animate-spin" />
                        Redirigiendo...
                    </span>
                    <span v-else>Desbloquear Nivel Élite</span>
                </button>
                <button
                    type="button"
                    class="grow rounded-xl border border-white/20 bg-white/5 text-white font-bold py-3 hover:bg-white/10"
                    @click="emit('close')"
                >
                    Ahora no
                </button>
            </div>
        </div>
    </div>
</template>
