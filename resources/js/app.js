import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import axios from 'axios';

const pinia = createPinia();

// CSRF token refresh helper
const refreshCsrfToken = async () => {
  try {
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    if (match) {
      const token = decodeURIComponent(match[1]);
      axios.defaults.headers.common['X-XSRF-TOKEN'] = token;
      window.axios.defaults.headers.common['X-XSRF-TOKEN'] = token;
      // Update meta tag for Inertia
      let meta = document.head.querySelector('meta[name="csrf-token"]');
      if (meta) meta.content = token;
    }
    return true;
  } catch (e) {
    console.error('CSRF refresh failed:', e);
    return false;
  }
};

// Track if we're already handling a 419 error
let isHandling419 = false;

// Global Inertia error handler - handle 419 CSRF errors
router.on('invalid', async (event) => {
  const response = event.detail?.response;

  if (response?.status === 419 && !isHandling419) {
    event.preventDefault();
    isHandling419 = true;

    console.warn('CSRF token expired, refreshing and reloading...');

    // Refresh token then reload
    await refreshCsrfToken();
    window.location.reload();
  }
});

// Handle navigation errors (session expired, etc.)
router.on('error', async (errors) => {
  if ((errors.response?.status === 419 || errors.response?.status === 401) && !isHandling419) {
    isHandling419 = true;
    await refreshCsrfToken();
    window.location.reload();
  }
});

// Dark mode initialization
if (typeof window !== 'undefined') {
  // Check user preference from settings or localStorage
  const savedTheme = localStorage.getItem('theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

  if (savedTheme === 'dark' || (savedTheme === 'auto' && prefersDark) || (!savedTheme && prefersDark)) {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }
}

// Suppress browser extension errors
const originalError = console.error;
console.error = (...args) => {
  // Filter out Chrome extension errors
  const errorString = args.join(' ');
  if (
    errorString.includes('runtime.lastError') ||
    errorString.includes('message channel closed') ||
    errorString.includes('Extension context invalidated')
  ) {
    return; // Suppress these errors
  }
  originalError.apply(console, args);
};

createInertiaApp({
  title: (title) => title ? `${title} - BiznesPilot AI` : 'BiznesPilot AI',
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    return createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(pinia)
      .use(ZiggyVue)
      .mount(el);
  },
  progress: {
    color: '#0ea5e9',
  },
});
