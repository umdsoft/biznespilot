import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { refreshCsrfToken, handleCsrfError } from '@/utils/csrf';
import { i18nPlugin, loadTranslations, getCurrentLocale } from '@/i18n';

const pinia = createPinia();

// Track if we're already handling a 419 error (prevent multiple reloads)
let isHandling419 = false;

// Global Inertia error handler - handle 419 CSRF errors
router.on('invalid', async (event) => {
    const response = event.detail?.response;

    if (response?.status === 419 && !isHandling419) {
        event.preventDefault();
        isHandling419 = true;

        console.warn('Inertia: CSRF token expired, refreshing...');
        await handleCsrfError(true); // Refresh and reload
    }
});

// Handle Inertia navigation errors (session expired, etc.)
router.on('error', async (errors) => {
    if ((errors.response?.status === 419 || errors.response?.status === 401) && !isHandling419) {
        isHandling419 = true;
        await handleCsrfError(true);
    }
});

// Reset 419 handling flag on successful navigation
router.on('success', () => {
    isHandling419 = false;
});

// Dark mode initialization
if (typeof window !== 'undefined') {
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (savedTheme === 'dark' || (savedTheme === 'auto' && prefersDark) || (!savedTheme && prefersDark)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
}

// Suppress browser extension errors (Chrome extensions cause console noise)
const originalError = console.error;
console.error = (...args) => {
    const errorString = args.join(' ');
    if (
        errorString.includes('runtime.lastError') ||
        errorString.includes('message channel closed') ||
        errorString.includes('Extension context invalidated')
    ) {
        return;
    }
    originalError.apply(console, args);
};

// Set locale on HTML element
if (typeof window !== 'undefined') {
    document.documentElement.lang = getCurrentLocale();
}

// Create Inertia app - translations are loaded synchronously now
createInertiaApp({
    title: (title) => title ? `${title} - BiznesPilot AI` : 'BiznesPilot AI',
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue)
            .use(i18nPlugin);

        // Load extended translations from API (non-blocking)
        loadTranslations();

        return app.mount(el);
    },
    progress: {
        color: '#0ea5e9',
    },
});
