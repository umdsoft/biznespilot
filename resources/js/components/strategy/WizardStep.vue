<template>
    <div class="flex items-center">
        <div
            :class="[
                'w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-colors',
                isCompleted
                    ? 'bg-green-500 text-white'
                    : isActive
                        ? 'bg-indigo-600 text-white'
                        : 'bg-gray-200 text-gray-600'
            ]"
        >
            <svg v-if="isCompleted" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span v-else>{{ step }}</span>
        </div>
        <span
            :class="[
                'ml-2 text-sm font-medium transition-colors',
                isActive ? 'text-indigo-600' : isCompleted ? 'text-green-600' : 'text-gray-500'
            ]"
        >
            {{ label }}
        </span>
    </div>
</template>

<script setup>
defineProps({
    step: {
        type: Number,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    isActive: {
        type: Boolean,
        default: false,
    },
    isCompleted: {
        type: Boolean,
        default: false,
    },
});
</script>
