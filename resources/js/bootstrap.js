import axios from 'axios';
window.axios = axios;

// Configure axios defaults
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

// Get CSRF token from meta tag and set as default header
const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
  window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
}

// Global axios error handler
window.axios.interceptors.response.use(
  response => response,
  error => {
    // Handle 419 (CSRF token expired) - log warning
    if (error.response?.status === 419) {
      console.warn('CSRF token expired, consider refreshing the page');
    }
    // Only log actual errors, not validation errors
    if (error.response?.status >= 500) {
      console.error('Server error:', error.response?.status, error.response?.data);
    }
    return Promise.reject(error);
  }
);
