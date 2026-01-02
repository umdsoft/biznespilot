import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Global axios error handler
window.axios.interceptors.response.use(
  response => response,
  error => {
    // Only log actual errors, not validation errors
    if (error.response?.status >= 500) {
      console.error('Server error:', error.response?.status, error.response?.data);
    }
    return Promise.reject(error);
  }
);
