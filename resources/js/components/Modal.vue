<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    modelValue: {
        type: Boolean,
        default: undefined,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    size: {
        type: String,
        default: null,
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    title: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close', 'update:modelValue']);
const dialog = ref();

// Support both show prop and v-model
const isVisible = computed(() => props.modelValue !== undefined ? props.modelValue : props.show);
const showSlot = ref(isVisible.value);

watch(
    () => isVisible.value,
    (newVal) => {
        if (newVal) {
            document.body.style.overflow = 'hidden';
            showSlot.value = true;
            dialog.value?.showModal();
        } else {
            document.body.style.overflow = '';
            setTimeout(() => {
                dialog.value?.close();
                showSlot.value = false;
            }, 200);
        }
    },
);

const close = () => {
    if (props.closeable) {
        emit('close');
        emit('update:modelValue', false);
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        e.preventDefault();
        if (isVisible.value) {
            close();
        }
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});

// Support both maxWidth and size props
const maxWidthClass = computed(() => {
    const sizeMap = {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
        '3xl': 'sm:max-w-3xl',
        '4xl': 'sm:max-w-4xl',
        '5xl': 'sm:max-w-5xl',
        full: 'sm:max-w-full',
    };
    return sizeMap[props.size || props.maxWidth] || sizeMap['2xl'];
});
</script>

<template>
    <dialog
        class="z-50 m-0 p-0 border-none min-h-full min-w-full overflow-y-auto bg-transparent backdrop:bg-transparent"
        ref="dialog"
    >
        <div
            class="fixed inset-0 z-50 overflow-y-auto"
            scroll-region
        >
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-show="isVisible"
                    class="fixed inset-0 transform transition-all"
                    @click="close"
                >
                    <div class="absolute inset-0 bg-gray-500/75 dark:bg-black/60" />
                </div>
            </Transition>

            <div class="relative z-10 flex min-h-full items-center justify-center p-4 sm:p-6" @click.self="close">
                <Transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div
                        v-show="isVisible"
                        class="w-full transform overflow-hidden rounded-xl bg-white dark:bg-slate-800 shadow-2xl transition-all sm:mx-auto"
                        :class="maxWidthClass"
                    >
                    <!-- Header with title -->
                    <div v-if="title" class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ title }}</h3>
                            <button
                                v-if="closeable"
                                type="button"
                                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors"
                                @click="close"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div v-if="showSlot" class="px-6 py-4">
                        <slot />
                    </div>

                    <!-- Footer slot -->
                    <div v-if="$slots.footer" class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                        <slot name="footer" />
                    </div>
                    </div>
                </Transition>
            </div>
        </div>
    </dialog>
</template>
