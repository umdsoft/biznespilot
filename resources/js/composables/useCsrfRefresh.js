import { onMounted, onUnmounted, ref } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

/**
 * Composable for automatic CSRF token management
 *
 * Features:
 * - Refreshes CSRF token on mount
 * - Handles 419 errors by refreshing token and retrying
 * - Provides manual refresh function
 * - Works with both Inertia and axios requests
 */
export function useCsrfRefresh(options = {}) {
    const { refreshOnMount = true, refreshInterval = null } = options;
    const isRefreshing = ref(false);
    const lastRefresh = ref(null);
    let intervalId = null;

    /**
     * Get CSRF token from meta tag
     */
    const getCsrfTokenFromMeta = () => {
        const meta = document.head.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : null;
    };

    /**
     * Get XSRF token from cookie
     */
    const getXsrfTokenFromCookie = () => {
        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : null;
    };

    /**
     * Update CSRF token in meta tag
     */
    const updateMetaToken = (token) => {
        let meta = document.head.querySelector('meta[name="csrf-token"]');
        if (!meta) {
            meta = document.createElement('meta');
            meta.name = 'csrf-token';
            document.head.appendChild(meta);
        }
        meta.content = token;
    };

    /**
     * Refresh CSRF token from server
     */
    const refreshCsrfToken = async () => {
        if (isRefreshing.value) return false;

        isRefreshing.value = true;

        try {
            // Get fresh CSRF cookie from Sanctum
            await axios.get('/sanctum/csrf-cookie', {
                withCredentials: true,
                headers: {
                    'Accept': 'application/json',
                }
            });

            // Get fresh tokens
            const xsrfToken = getXsrfTokenFromCookie();

            if (xsrfToken) {
                // Update axios defaults
                axios.defaults.headers.common['X-XSRF-TOKEN'] = xsrfToken;
                window.axios.defaults.headers.common['X-XSRF-TOKEN'] = xsrfToken;

                // Also update meta tag for Inertia forms
                updateMetaToken(xsrfToken);
                axios.defaults.headers.common['X-CSRF-TOKEN'] = xsrfToken;
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = xsrfToken;
            }

            lastRefresh.value = new Date();
            console.debug('CSRF token refreshed successfully');
            return true;
        } catch (error) {
            console.error('Failed to refresh CSRF token:', error);
            return false;
        } finally {
            isRefreshing.value = false;
        }
    };

    /**
     * Setup Inertia error handlers
     */
    const setupInertiaHandlers = () => {
        // Handle 419 errors in Inertia by refreshing and retrying
        const removeInvalidHandler = router.on('invalid', async (event) => {
            const response = event.detail.response;

            if (response?.status === 419) {
                event.preventDefault();

                console.warn('Inertia 419 error detected, refreshing CSRF token...');

                const refreshed = await refreshCsrfToken();

                if (refreshed) {
                    // Retry by reloading the page to get fresh state
                    window.location.reload();
                } else {
                    // If refresh failed, still reload to reset state
                    window.location.reload();
                }
            }
        });

        // Handle navigation errors
        const removeErrorHandler = router.on('error', async (errors) => {
            if (errors.response?.status === 419) {
                console.warn('Inertia navigation error 419, refreshing...');
                const refreshed = await refreshCsrfToken();
                if (!refreshed) {
                    window.location.reload();
                }
            }
        });

        return () => {
            removeInvalidHandler();
            removeErrorHandler();
        };
    };

    onMounted(async () => {
        // Refresh token on mount to ensure fresh state
        if (refreshOnMount) {
            await refreshCsrfToken();
        }

        // Setup periodic refresh if interval is specified
        if (refreshInterval && refreshInterval > 0) {
            intervalId = setInterval(refreshCsrfToken, refreshInterval);
        }
    });

    onUnmounted(() => {
        if (intervalId) {
            clearInterval(intervalId);
        }
    });

    return {
        refreshCsrfToken,
        isRefreshing,
        lastRefresh,
        setupInertiaHandlers,
    };
}

/**
 * Initialize global CSRF handling
 * Call this once in app.js
 */
export function initGlobalCsrfHandler() {
    let isRetrying = false;

    // Setup Inertia handlers
    router.on('invalid', async (event) => {
        const response = event.detail?.response;

        if (response?.status === 419 && !isRetrying) {
            event.preventDefault();
            isRetrying = true;

            console.warn('Global: CSRF token expired, refreshing...');

            try {
                await axios.get('/sanctum/csrf-cookie', {
                    withCredentials: true,
                });

                // Reload to get fresh state
                window.location.reload();
            } catch (error) {
                console.error('Failed to refresh CSRF token:', error);
                window.location.reload();
            } finally {
                isRetrying = false;
            }
        }
    });

    // Also handle exception events
    router.on('exception', (event) => {
        console.error('Inertia exception:', event.detail.exception);
    });
}
