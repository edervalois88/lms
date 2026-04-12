import { ref } from 'vue';

const STORAGE_KEY = 'nexusedu-theme';
const theme = ref('dark');

function applyThemeClass(value) {
    const root = document.documentElement;
    root.classList.toggle('theme-light', value === 'light');
}

export function useTheme() {
    const initializeTheme = () => {
        const saved = localStorage.getItem(STORAGE_KEY);
        const preferred = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
        theme.value = saved || preferred;
        applyThemeClass(theme.value);
    };

    const toggleTheme = () => {
        theme.value = theme.value === 'dark' ? 'light' : 'dark';
        localStorage.setItem(STORAGE_KEY, theme.value);
        applyThemeClass(theme.value);
    };

    return {
        theme,
        initializeTheme,
        toggleTheme,
    };
}
