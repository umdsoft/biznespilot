import { defineAsyncComponent, h } from 'vue';

/**
 * PERFORMANCE: Lazy loading composable for Vue components
 *
 * Usage:
 * const LazyDashboard = useLazyComponent(() => import('@/components/Dashboard.vue'));
 * const LazyChart = useLazyComponent(() => import('@/components/Chart.vue'), {
 *   loadingComponent: LoadingSpinner,
 *   delay: 200,
 *   timeout: 10000
 * });
 */

// Default loading component
const DefaultLoading = {
    render() {
        return h('div', {
            class: 'flex items-center justify-center p-8'
        }, [
            h('div', {
                class: 'animate-spin rounded-full h-8 w-8 border-b-2 border-sky-500'
            })
        ]);
    }
};

// Default error component
const DefaultError = {
    props: ['error'],
    render() {
        return h('div', {
            class: 'flex flex-col items-center justify-center p-8 text-red-500'
        }, [
            h('svg', {
                class: 'w-12 h-12 mb-2',
                fill: 'none',
                viewBox: '0 0 24 24',
                stroke: 'currentColor'
            }, [
                h('path', {
                    'stroke-linecap': 'round',
                    'stroke-linejoin': 'round',
                    'stroke-width': '2',
                    d: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
                })
            ]),
            h('p', { class: 'text-sm' }, 'Komponentni yuklashda xatolik')
        ]);
    }
};

/**
 * Create a lazy-loaded async component
 *
 * @param {Function} loader - Dynamic import function
 * @param {Object} options - Configuration options
 * @returns {Object} Async component definition
 */
export function useLazyComponent(loader, options = {}) {
    const {
        loadingComponent = DefaultLoading,
        errorComponent = DefaultError,
        delay = 200,
        timeout = 30000,
        onError = null
    } = options;

    return defineAsyncComponent({
        loader,
        loadingComponent,
        errorComponent,
        delay,
        timeout,
        onError: onError || ((error, retry, fail, attempts) => {
            // Retry up to 3 times
            if (attempts <= 3) {
                retry();
            } else {
                console.error('Failed to load component after 3 attempts:', error);
                fail();
            }
        })
    });
}

/**
 * Pre-defined lazy components for common heavy components
 */
export const LazyComponents = {
    // Charts
    ApexChart: () => useLazyComponent(() => import('vue3-apexcharts')),

    // Heavy business components (will be created as needed)
    // DiagnosticReport: () => useLazyComponent(() => import('@/Pages/Diagnostic/Report.vue')),
    // StrategyBuilder: () => useLazyComponent(() => import('@/Pages/Strategy/Builder.vue')),
};

/**
 * Intersection Observer based lazy loading
 * Load component only when it's visible in viewport
 */
export function useLazyVisible(loader, options = {}) {
    const { threshold = 0.1, rootMargin = '50px' } = options;

    return {
        mounted(el, binding) {
            const observer = new IntersectionObserver(
                (entries) => {
                    if (entries[0].isIntersecting) {
                        // Load the component
                        loader().then((component) => {
                            if (binding.value && typeof binding.value === 'function') {
                                binding.value(component);
                            }
                        });
                        observer.disconnect();
                    }
                },
                { threshold, rootMargin }
            );
            observer.observe(el);
        }
    };
}

export default useLazyComponent;
