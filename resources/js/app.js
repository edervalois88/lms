import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createPinia } from 'pinia'
import { useTheme } from './Composables/useTheme'
import { registerSW } from 'virtual:pwa-register'
import axios from 'axios'
import '../css/app.css'

registerSW({
    immediate: true,
})

// Configurar axios con el token CSRF
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

createInertiaApp({
    title: (title) => title ? `${title} — NexusEdu` : 'NexusEdu — AI Powered Education',
    resolve: (name) => resolvePageComponent(
        `./Pages/${name}.vue`,
        import.meta.glob('./Pages/**/*.vue')
    ),
    setup({ el, App, props, plugin }) {
        const { initializeTheme } = useTheme()
        initializeTheme()

        const pinia = createPinia()

        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)

        // Inyectar axios globalmente
        app.config.globalProperties.$axios = axios;

        // MANIOBRA DE FUERZA: Inyectamos la función de rutas globalmente
        // Esto soluciona definitivamente el error "route is not a function"
        app.config.globalProperties.route = window.route;

        app.mount(el)
    },
    progress: {
        color: '#F97316',
        showSpinner: true,
    },
})
