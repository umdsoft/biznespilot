<template>
    <Teleport to="body">
        <div class="fixed top-0 left-0 right-0 z-[100] flex flex-col items-center gap-2 px-4 pt-3 pointer-events-none">
            <TransitionGroup name="toast">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto flex items-center gap-2 rounded-xl px-4 py-2.5 shadow-lg"
                    :style="toastStyle(toast)"
                >
                    <!-- Icon -->
                    <svg v-if="toast.type === 'success'" class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    <svg v-else-if="toast.type === 'error'" class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <svg v-else class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                    <span class="text-sm font-medium">{{ toast.message }}</span>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import { useToast } from '../composables/useToast'
const { toasts } = useToast()

function toastStyle(toast) {
    if (toast.type === 'success') {
        return { backgroundColor: '#059669', color: '#fff' }
    }
    if (toast.type === 'error') {
        return { backgroundColor: '#dc2626', color: '#fff' }
    }
    return { backgroundColor: 'var(--tg-theme-text-color)', color: 'var(--tg-theme-bg-color)' }
}
</script>

<style scoped>
.toast-enter-active {
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-leave-active {
    transition: all 0.2s ease;
}
.toast-enter-from {
    transform: translateY(-20px) scale(0.9);
    opacity: 0;
}
.toast-leave-to {
    transform: translateY(-10px) scale(0.95);
    opacity: 0;
}
.toast-move {
    transition: transform 0.2s ease;
}
</style>
