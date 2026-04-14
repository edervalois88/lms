<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref } from 'vue';

const activeTab = ref('general');

const settings = [
    {
        id: 'general',
        label: 'General',
        icon: '⚙️',
        sections: [
            {
                title: 'Nombre de la Aplicación',
                description: 'El nombre que ven los usuarios en toda la plataforma',
                current: 'NexusEdu',
            },
            {
                title: 'Modo Mantenimiento',
                description: 'Desactiva el acceso para todos excepto admins',
                current: 'Desactivado',
            },
        ],
    },
    {
        id: 'system',
        label: 'Sistema',
        icon: '🔧',
        sections: [
            {
                title: 'Debug Mode',
                description: 'Mostrar errores detallados',
                current: 'Activado (Local)',
            },
            {
                title: 'Cache',
                description: 'Gestiona el almacenamiento en caché',
                current: 'Habilitado',
            },
            {
                title: 'Queue Worker',
                description: 'Procesa tareas en segundo plano',
                current: 'Desactivado',
            },
        ],
    },
    {
        id: 'api',
        label: 'APIs',
        icon: '🤖',
        sections: [
            {
                title: 'Anthropic API',
                description: 'Clave y modelo para Claude',
                current: 'Configurado',
            },
            {
                title: 'Groq API',
                description: 'Clave para Groq LLM',
                current: 'Configurado',
            },
            {
                title: 'Stripe',
                description: 'Claves de pagos',
                current: 'Configurado',
            },
        ],
    },
    {
        id: 'security',
        label: 'Seguridad',
        icon: '🔐',
        sections: [
            {
                title: 'HTTPS Enforcement',
                description: 'Requiere conexión segura en producción',
                current: 'No configurado',
            },
            {
                title: 'CSRF Protection',
                description: 'Protección contra ataques CSRF',
                current: 'Habilitado',
            },
            {
                title: 'Rate Limiting',
                description: 'Limita intentos de login y API',
                current: 'Habilitado',
            },
            {
                title: 'Session Timeout',
                description: 'Tiempo antes de cerrar sesión',
                current: '120 minutos',
            },
        ],
    },
];
</script>

<template>
    <Head title="Configuración" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            <!-- Header -->
            <section class="space-y-2">
                <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tight">⚙️ Configuración del Sistema</h1>
                <p class="text-sm text-gray-400">Gestiona parámetros, integraciones y seguridad del sistema.</p>
            </section>

            <!-- Settings Tabs -->
            <section class="space-y-6">
                <!-- Tab Navigation -->
                <div class="glass-morphism rounded-2xl border border-white/10 p-2 flex flex-wrap gap-2 overflow-x-auto">
                    <button
                        v-for="tab in settings"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="{
                            'bg-orange-500/20 border-orange-500/40 text-orange-300': activeTab === tab.id,
                            'border-transparent text-gray-400 hover:text-white': activeTab !== tab.id,
                        }"
                        class="px-4 py-2 rounded-xl border transition-all duration-200 font-semibold text-sm whitespace-nowrap"
                    >
                        <span>{{ tab.icon }}</span>
                        <span class="ml-1">{{ tab.label }}</span>
                    </button>
                </div>

                <!-- Tab Content -->
                <div v-for="tab in settings" :key="tab.id" v-show="activeTab === tab.id" class="space-y-4">
                    <article
                        v-for="(section, idx) in tab.sections"
                        :key="idx"
                        class="glass-morphism rounded-2xl border border-white/10 p-6 space-y-3 hover:border-white/20 transition-all"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="text-lg font-black text-white uppercase tracking-tight">{{ section.title }}</h4>
                                <p class="text-sm text-gray-400 mt-1">{{ section.description }}</p>
                            </div>
                            <div class="text-right ml-4">
                                <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Estado</p>
                                <p class="text-sm font-semibold text-orange-400 mt-1">{{ section.current }}</p>
                            </div>
                        </div>
                        <div class="pt-3 border-t border-white/10 flex gap-2">
                            <button class="px-4 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold transition-all">
                                Configurar
                            </button>
                            <button class="px-4 py-2 rounded-lg border border-white/10 hover:border-white/20 text-gray-300 hover:text-white text-sm font-semibold transition-all">
                                Docs
                            </button>
                        </div>
                    </article>
                </div>
            </section>

            <!-- Danger Zone -->
            <section class="glass-morphism rounded-3xl border-2 border-red-500/30 p-6 space-y-4 bg-red-500/5">
                <h3 class="text-xl font-black uppercase tracking-tight text-red-400">⚠️ Zona de Peligro</h3>
                <p class="text-sm text-gray-300">Estas acciones son irreversibles. Procede con precaución.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <button class="px-6 py-3 rounded-xl border border-red-500/30 bg-red-500/10 text-red-400 hover:bg-red-500/20 font-semibold transition-all">
                        🗑️ Limpiar Caché
                    </button>
                    <button class="px-6 py-3 rounded-xl border border-red-500/30 bg-red-500/10 text-red-400 hover:bg-red-500/20 font-semibold transition-all">
                        🔄 Resetear Base de Datos
                    </button>
                    <button class="px-6 py-3 rounded-xl border border-red-500/30 bg-red-500/10 text-red-400 hover:bg-red-500/20 font-semibold transition-all">
                        📋 Exportar Logs
                    </button>
                    <button class="px-6 py-3 rounded-xl border border-red-500/30 bg-red-500/10 text-red-400 hover:bg-red-500/20 font-semibold transition-all">
                        🗑️ Eliminar Archivos Temporales
                    </button>
                </div>
            </section>

            <!-- Documentation Links -->
            <section class="glass-morphism rounded-3xl border border-white/10 p-6 space-y-4">
                <h3 class="text-xl font-black uppercase tracking-tight">📚 Recursos & Documentación</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="#" class="p-4 rounded-xl border border-white/10 hover:border-orange-500/50 hover:bg-orange-500/5 transition-all">
                        <p class="font-semibold text-white">📖 Documentación Técnica</p>
                        <p class="text-xs text-gray-500 mt-1">Guías de configuración y APIs</p>
                    </a>
                    <a href="#" class="p-4 rounded-xl border border-white/10 hover:border-orange-500/50 hover:bg-orange-500/5 transition-all">
                        <p class="font-semibold text-white">🆘 Centro de Ayuda</p>
                        <p class="text-xs text-gray-500 mt-1">Preguntas frecuentes y troubleshooting</p>
                    </a>
                    <a href="#" class="p-4 rounded-xl border border-white/10 hover:border-orange-500/50 hover:bg-orange-500/5 transition-all">
                        <p class="font-semibold text-white">🐛 Reportar Problemas</p>
                        <p class="text-xs text-gray-500 mt-1">Issues y bugs tracker</p>
                    </a>
                    <a href="#" class="p-4 rounded-xl border border-white/10 hover:border-orange-500/50 hover:bg-orange-500/5 transition-all">
                        <p class="font-semibold text-white">💬 Contactar Soporte</p>
                        <p class="text-xs text-gray-500 mt-1">Team support y consultas</p>
                    </a>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>

<style scoped>
.glass-morphism {
    background: rgba(255, 255, 255, 0.04);
    backdrop-filter: blur(10px);
}
</style>
