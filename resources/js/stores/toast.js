import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useToastStore = defineStore('toast', () => {
  const toasts = ref([]);
  let nextId = 1;

  /**
   * Add a toast notification
   * @param {Object} options - Toast options
   * @param {string} options.type - Toast type: 'success' | 'error' | 'warning' | 'info'
   * @param {string} options.title - Toast title
   * @param {string} options.message - Toast message
   * @param {number} options.duration - Duration in ms (default: 5000)
   */
  function addToast({ type = 'info', title, message, duration = 5000 }) {
    const id = nextId++;

    toasts.value.push({
      id,
      type,
      title,
      message,
      duration,
    });

    // Auto remove after duration
    if (duration > 0) {
      setTimeout(() => {
        removeToast(id);
      }, duration);
    }

    return id;
  }

  function removeToast(id) {
    const index = toasts.value.findIndex(t => t.id === id);
    if (index !== -1) {
      toasts.value.splice(index, 1);
    }
  }

  function clearAll() {
    toasts.value = [];
  }

  // Convenience methods
  function success(title, message = '', duration = 5000) {
    return addToast({ type: 'success', title, message, duration });
  }

  function error(title, message = '', duration = 7000) {
    return addToast({ type: 'error', title, message, duration });
  }

  function warning(title, message = '', duration = 6000) {
    return addToast({ type: 'warning', title, message, duration });
  }

  function info(title, message = '', duration = 5000) {
    return addToast({ type: 'info', title, message, duration });
  }

  return {
    toasts,
    addToast,
    removeToast,
    clearAll,
    success,
    error,
    warning,
    info,
  };
});
