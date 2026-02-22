<template>
    <div class="flex items-center justify-center" :class="containerClass">
        <svg
            :class="spinnerClass"
            style="color: var(--tg-theme-button-color)"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            />
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            />
        </svg>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    size: {
        type: String,
        default: 'md',
        validator: (val) => ['sm', 'md', 'lg'].includes(val),
    },
})

const spinnerClass = computed(() => {
    const sizes = {
        sm: 'h-5 w-5',
        md: 'h-8 w-8',
        lg: 'h-12 w-12',
    }
    return `animate-spin ${sizes[props.size]}`
})

const containerClass = computed(() => {
    const paddings = {
        sm: 'p-1',
        md: 'p-2',
        lg: 'p-4',
    }
    return paddings[props.size]
})
</script>
