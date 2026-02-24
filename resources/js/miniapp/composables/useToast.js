import { ref } from 'vue'

const toasts = ref([])
let toastId = 0

export function useToast() {
    function show(message, options = {}) {
        const id = ++toastId
        const toast = {
            id,
            message,
            type: options.type || 'success', // success | error | info
            duration: options.duration || 2000,
        }
        toasts.value.push(toast)

        setTimeout(() => {
            remove(id)
        }, toast.duration)

        return id
    }

    function success(message, duration = 2000) {
        return show(message, { type: 'success', duration })
    }

    function error(message, duration = 2500) {
        return show(message, { type: 'error', duration })
    }

    function info(message, duration = 2000) {
        return show(message, { type: 'info', duration })
    }

    function remove(id) {
        const idx = toasts.value.findIndex((t) => t.id === id)
        if (idx > -1) toasts.value.splice(idx, 1)
    }

    return { toasts, show, success, error, info, remove }
}
