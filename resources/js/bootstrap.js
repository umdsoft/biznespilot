import axios from 'axios';
window.axios = axios;

// Configure axios defaults
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

// Helper function to get CSRF token from cookie
const getXsrfTokenFromCookie = () => {
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
  return match ? decodeURIComponent(match[1]) : null;
};

// Helper function to get CSRF token from meta tag
const getCsrfTokenFromMeta = () => {
  const meta = document.head.querySelector('meta[name="csrf-token"]');
  return meta ? meta.content : null;
};

// Set initial CSRF token
const initialCsrfToken = getCsrfTokenFromMeta();
if (initialCsrfToken) {
  window.axios.defaults.headers.common['X-CSRF-TOKEN'] = initialCsrfToken;
}

// Track retry attempts to prevent infinite loops
const retryAttempts = new WeakMap();
const MAX_RETRIES = 1;

// Global axios error handler
window.axios.interceptors.response.use(
  response => response,
  async error => {
    const originalRequest = error.config;

    // Handle 419 (CSRF token expired) - refresh CSRF token and retry once
    if (error.response?.status === 419 && originalRequest) {
      // Check retry count
      const attempts = retryAttempts.get(originalRequest) || 0;
      if (attempts >= MAX_RETRIES) {
        console.error('CSRF token refresh failed after retry, reloading page...');
        window.location.reload();
        return Promise.reject(error);
      }

      // Mark as retried
      retryAttempts.set(originalRequest, attempts + 1);
      console.warn('CSRF token expired, refreshing...');

      try {
        // Fetch fresh CSRF cookie from Sanctum
        await axios.get('/sanctum/csrf-cookie', {
          withCredentials: true,
          headers: {
            'Accept': 'application/json',
          }
        });

        // Get fresh token from cookie
        const xsrfToken = getXsrfTokenFromCookie();
        if (xsrfToken) {
          // Update default header
          window.axios.defaults.headers.common['X-XSRF-TOKEN'] = xsrfToken;
          // Update the original request header
          originalRequest.headers['X-XSRF-TOKEN'] = xsrfToken;
        }

        // Also update X-CSRF-TOKEN if meta tag exists
        const csrfToken = getCsrfTokenFromMeta();
        if (csrfToken) {
          window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
          originalRequest.headers['X-CSRF-TOKEN'] = csrfToken;
        }

        // Retry the original request with fresh tokens
        return window.axios.request(originalRequest);
      } catch (refreshError) {
        console.error('Failed to refresh CSRF token:', refreshError);
        window.location.reload();
        return Promise.reject(error);
      }
    }

    // Only log actual server errors, not validation errors
    if (error.response?.status >= 500) {
      console.error('Server error:', error.response?.status, error.response?.data);
    }

    return Promise.reject(error);
  }
);
