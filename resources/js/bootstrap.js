import axios from 'axios';
import { setupCsrfInterceptor, getCsrfTokenFromMeta, getXsrfTokenFromCookie } from '@/utils/csrf';

window.axios = axios;

// Configure axios defaults
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

// Set initial CSRF tokens from both sources
const metaToken = getCsrfTokenFromMeta();
const cookieToken = getXsrfTokenFromCookie();

if (metaToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = metaToken;
}

if (cookieToken) {
    window.axios.defaults.headers.common['X-XSRF-TOKEN'] = cookieToken;
}

// Setup global CSRF interceptor for automatic 419 retry
setupCsrfInterceptor(window.axios);

// Log server errors (not validation errors)
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status >= 500) {
            console.error('Server error:', error.response?.status, error.response?.data);
        }
        return Promise.reject(error);
    }
);
