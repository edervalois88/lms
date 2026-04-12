<script setup>
defineProps({
    stats: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 p-8">
        <div class="max-w-5xl mx-auto">
            <h1 class="text-3xl font-black tracking-tight">Admin NexusEdu</h1>
            <p class="text-slate-400 mt-2">Panel base para monitoreo operativo.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
                <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                    <p class="text-xs uppercase tracking-wider text-slate-400">Usuarios</p>
                    <p class="text-3xl font-bold mt-2">{{ stats.users }}</p>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                    <p class="text-xs uppercase tracking-wider text-slate-400">Activos Hoy</p>
                    <p class="text-3xl font-bold mt-2">{{ stats.active_today }}</p>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                    <p class="text-xs uppercase tracking-wider text-slate-400">Examenes Completados</p>
                    <p class="text-3xl font-bold mt-2">{{ stats.completed_exams }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
