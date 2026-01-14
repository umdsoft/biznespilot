import axios from 'axios';

// Track refresh state to prevent concurrent refreshes
let isRefreshing = false;
let refreshPromise = null;

/**
 * Get CSRF token from cookie
 */
export const getXsrfTokenFromCookie = () => {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : null;
};

/**
 * Get CSRF token from meta tag
 */
export const getCsrfTokenFromMeta = () => {
    const meta = document.head.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : null;
};

/**
 * Update all CSRF token references (headers, meta tag)
 */
const updateAllTokens = (token) => {
    if (!token) return;

    // Update axios defaults
    axios.defaults.headers.common['X-XSRF-TOKEN'] = token;
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token;

    // Update window.axios if exists
    if (window.axios) {
        window.axios.defaults.headers.common['X-XSRF-TOKEN'] = token;
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    }

    // Update meta tag for Inertia
    const meta = document.head.querySelector('meta[name="csrf-token"]');
    if (meta) {
        meta.content = token;
    }
};

/**
 * Global CSRF token refresh utility
 * Prevents concurrent refreshes using singleton pattern
 */
export const refreshCsrfToken = async (force = false) => {
    // Return existing promise if already refreshing
    if (isRefreshing && !force) {
        return refreshPromise;
    }

    isRefreshing = true;
    refreshPromise = (async () => {
        try {
            await axios.get('/sanctum/csrf-cookie', {
                withCredentials: true,
                headers: { 'Accept': 'application/json' }
            });

            const token = getXsrfTokenFromCookie();
            if (token) {
                updateAllTokens(token);
                return true;
            }
            return false;
        } catch (e) {
            console.error('CSRF refresh failed:', e);
            return false;
        } finally {
            isRefreshing = false;
        }
    })();

    return refreshPromise;
};

/**
 * Wrapper for axios POST with automatic CSRF refresh
 */
export const safePost = async (url, data = {}, config = {}) => {
    await refreshCsrfToken();
    return window.axios.post(url, data, config);
};

/**
 * Wrapper for axios PUT with automatic CSRF refresh
 */
export const safePut = async (url, data = {}, config = {}) => {
    await refreshCsrfToken();
    return window.axios.put(url, data, config);
};

/**
 * Wrapper for axios DELETE with automatic CSRF refresh
 */
export const safeDelete = async (url, config = {}) => {
    await refreshCsrfToken();
    return window.axios.delete(url, config);
};

/**
 * Check if error is a CSRF error (419)
 */
export const isCsrfError = (error) => {
    return error?.response?.status === 419;
};

/**
 * Handle CSRF error - refresh token and optionally reload
 */
export const handleCsrfError = async (shouldReload = false) => {
    const success = await refreshCsrfToken(true); // Force refresh
    if (shouldReload || !success) {
        window.location.reload();
    }
    return success;
};

/**
 * Create axios interceptor for automatic CSRF retry
 * Call this once during app initialization
 */
export const setupCsrfInterceptor = (axiosInstance = null) => {
    const instance = axiosInstance || window.axios || axios;
    const retryAttempts = new WeakMap();
    const MAX_RETRIES = 1;

    instance.interceptors.response.use(
        response => response,
        async error => {
            const originalRequest = error.config;

            // Handle 419 CSRF error
            if (error.response?.status === 419 && originalRequest) {
                const attempts = retryAttempts.get(originalRequest) || 0;

                if (attempts >= MAX_RETRIES) {
                    console.error('CSRF refresh failed after retry');
                    // Don't reload automatically - let the component handle it
                    return Promise.reject(error);
                }

                retryAttempts.set(originalRequest, attempts + 1);

                try {
                    const success = await refreshCsrfToken(true);
                    if (success) {
                        const token = getXsrfTokenFromCookie();
                        if (token) {
                            originalRequest.headers['X-XSRF-TOKEN'] = token;
                            originalRequest.headers['X-CSRF-TOKEN'] = token;
                        }
                        return instance.request(originalRequest);
                    }
                } catch (refreshError) {
                    console.error('CSRF retry failed:', refreshError);
                }
            }

            return Promise.reject(error);
        }
    );
};

/**
 * Retry a failed request with fresh CSRF token
 */
export const retryWithFreshToken = async (requestFn) => {
    try {
        return await requestFn();
    } catch (error) {
        if (isCsrfError(error)) {
            await refreshCsrfToken(true);
            return await requestFn();
        }
        throw error;
    }
};

export default {
    refreshCsrfToken,
    safePost,
    safePut,
    safeDelete,
    isCsrfError,
    handleCsrfError,
    setupCsrfInterceptor,
    retryWithFreshToken,
    getXsrfTokenFromCookie,
    getCsrfTokenFromMeta,
};
