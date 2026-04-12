<script setup>
import { ref } from 'vue';

const props = defineProps({
    enabled: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    response: {
        type: String,
        default: '',
    },
    blocked: {
        type: Boolean,
        default: false,
    },
    fromCache: {
        type: Boolean,
        default: false,
    },
    tokensSaved: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['ask']);
const message = ref('');

const submit = () => {
    if (!props.enabled || props.loading) {
        return;
    }

    emit('ask', message.value.trim());
    message.value = '';
};
</script>

<template>
    <div class="rounded-3xl border border-blue-200 bg-blue-50 p-5">
        <div class="flex items-center justify-between gap-3 mb-3">
            <h4 class="text-sm font-black uppercase tracking-widest text-blue-700">Tutor Chat</h4>
            <span class="text-[11px] font-semibold" :class="loading ? 'text-amber-600' : (enabled ? 'text-blue-600' : 'text-gray-500')">
                {{ loading ? 'Generando respuesta...' : (enabled ? 'Activo' : 'Responde primero para activarlo') }}
            </span>
        </div>

        <div class="rounded-2xl bg-white border border-blue-100 p-4 min-h-20">
            <p class="text-sm text-gray-700 whitespace-pre-line">
                {{ response || 'Haz una pregunta sobre el reactivo para profundizar en el tema.' }}
            </p>
            <p v-if="blocked" class="text-xs font-bold text-red-600 mt-2">Solo puedo asesorarte sobre el tema del examen actual.</p>
            <p v-if="fromCache" class="text-xs font-bold text-emerald-700 mt-2">
                Respuesta instantánea desde caché. Tokens ahorrados: {{ tokensSaved }}
            </p>
        </div>

        <form class="mt-3 flex gap-2" @submit.prevent="submit">
            <input
                v-model="message"
                type="text"
                :disabled="!enabled || loading"
                placeholder="Pregunta algo del tema..."
                class="flex-1 rounded-xl border border-blue-200 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 disabled:opacity-50 disabled:cursor-not-allowed"
            />
            <button
                type="submit"
                :disabled="!enabled || loading"
                class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-bold disabled:opacity-50"
            >
                {{ loading ? '...' : 'Enviar' }}
            </button>
        </form>
    </div>
</template>
