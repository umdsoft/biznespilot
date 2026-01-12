import axios from 'axios';

/**
 * Global CSRF token refresh utility
 * Use this before making POST/PUT/DELETE requests in modals or forms
 */
export const refreshCsrfToken = async () => {
    try {
        await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        if (match) {
            const token = decodeURIComponent(match[1]);
            axios.defaults.headers.common['X-XSRF-TOKEN'] = token;
            if (window.axios) {
                window.axios.defaults.headers.common['X-XSRF-TOKEN'] = token;
            }
            let meta = document.head.querySelector('meta[name="csrf-token"]');
            if (meta) meta.content = token;
        }
        return true;
    } catch (e) {
        console.error('CSRF refresh failed:', e);
        return false;
    }
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
    await refreshCsrfToken();
    if (shouldReload) {
        window.location.reload();
    }
};

export default {
    refreshCsrfToken,
    safePost,
    safePut,
    safeDelete,
    isCsrfError,
    handleCsrfError,
};
