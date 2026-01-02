import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';

const pinia = createPinia();

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
