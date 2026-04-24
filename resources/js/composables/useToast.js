/**
 * useToast - Inline toast notification composable.
 *
 * Used by TelegramFunnelBuilder (and reusable elsewhere) to replace
 * blocking alert() calls. Stacks up to 3 toasts in the bottom-right,
 * auto-dismisses each after 3s.
 *
 * Usage:
 *   import { useToast } from '@/composables/useToast'
 *   const { showToast } = useToast()
 *   showToast("Saqlandi", 'success')
 */
import { reactive } from 'vue';

const MAX_TOASTS = 3;
const DEFAULT_DURATION = 3000;

const state = reactive({
    toasts: [], // { id, message, type, timer }
});

let nextId = 1;

function removeToast(id) {
    const idx = state.toasts.findIndex((t) => t.id === id);
    if (idx !== -1) {
        const t = state.toasts[idx];
        if (t.timer) clearTimeout(t.timer);
        state.toasts.splice(idx, 1);
    }
}

/**
 * @param {string} message
 * @param {'success'|'error'|'info'|'warning'} type
 * @param {number} duration  ms before auto-dismiss
 */
function showToast(message, type = 'info', duration = DEFAULT_DURATION) {
    if (!message) return;

    // Cap stacked toasts to MAX_TOASTS (drop oldest)
    while (state.toasts.length >= MAX_TOASTS) {
        const oldest = state.toasts[0];
        if (oldest?.timer) clearTimeout(oldest.timer);
        state.toasts.shift();
    }

    const id = nextId++;
    const toast = { id, message, type, timer: null };
    toast.timer = setTimeout(() => removeToast(id), duration);
    state.toasts.push(toast);
    return id;
}

export function useToast() {
    return {
        toasts: state.toasts,
        showToast,
        removeToast,
    };
}

export default useToast;
