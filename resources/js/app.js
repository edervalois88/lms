import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createPinia } from 'pinia'
import '../css/app.css'

import { ZiggyVue } from '../../vendor/tightenco/ziggy'

createInertiaApp({
    title: (title) => title ? `${title} — NexusEdu` : 'NexusEdu — AI Powered Education',
    resolve: (name) => resolvePageComponent(
        `./Pages/${name}.vue`,
        import.meta.glob('./Pages/**/*.vue')
    ),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia()
        
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue)
            .mount(el)
    },
    progress: {
        color: '#F97316',
        showSpinner: true,
    },
})
