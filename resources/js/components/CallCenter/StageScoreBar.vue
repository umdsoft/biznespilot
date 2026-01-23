<script setup>
import { computed } from 'vue';

const props = defineProps({
    score: {
        type: [Number, null],
        default: null
    },
    label: {
        type: String,
        default: ''
    },
    showWarning: {
        type: Boolean,
        default: false
    },
    size: {
        type: String,
        default: 'md',
        validator: (val) => ['sm', 'md', 'lg'].includes(val)
    }
});

// Get progress bar color based on score
const progressColor = computed(() => {
    if (props.score === null) return 'bg-gray-200';
    if (props.score >= 80) return 'bg-green-500';
    if (props.score >= 60) return 'bg-yellow-500';
    if (props.score >= 40) return 'bg-orange-500';
    return 'bg-red-500';
});

// Get text color based on score
const textColor = computed(() => {
    if (props.score === null) return 'text-gray-400';
    if (props.score >= 80) return 'text-green-600';
    if (props.score >= 60) return 'text-yellow-600';
    if (props.score >= 40) return 'text-orange-600';
    return 'text-red-600';
});

// Height class based on size
const heightClass = computed(() => {
    switch (props.size) {
        case 'sm': return 'h-1.5';
        case 'lg': return 'h-3';
        default: return 'h-2';
    }
});
</script>

<template>
    <div class="flex items-center gap-3">
        <!-- Label -->
        <div v-if="label" class="w-32 flex-shrink-0 flex items-center gap-1">
            <span class="text-sm text-gray-600 truncate">{{ label }}</span>
            <svg
                v-if="showWarning && score !== null && score < 60"
                class="w-4 h-4 text-orange-500 flex-shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
            </svg>
        </div>

        <!-- Progress bar -->
        <div class="flex-1 bg-gray-100 rounded-full overflow-hidden" :class="heightClass">
            <div
                class="h-full rounded-full transition-all duration-500 ease-out"
                :class="progressColor"
                :style="{ width: score !== null ? `${score}%` : '0%' }"
            />
        </div>

        <!-- Score value -->
        <div class="w-10 text-right">
            <span class="text-sm font-semibold" :class="textColor">
                {{ score !== null ? score : '-' }}
            </span>
        </div>
    </div>
</template>
