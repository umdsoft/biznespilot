<template>
    <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">{{ title }}</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ formattedValue }}</p>
                <div v-if="change !== undefined" class="flex items-center mt-2">
                    <span
                        :class="changeColor"
                        class="text-sm font-medium flex items-center"
                    >
                        <svg v-if="change >= 0" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                        <svg v-else class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                        {{ Math.abs(change) }}%
                    </span>
                    <span class="text-sm text-gray-500 ml-2">{{ changeLabel }}</span>
                </div>
            </div>
            <div
                :class="iconBgColor"
                class="w-12 h-12 rounded-xl flex items-center justify-center"
            >
                <slot name="icon">
                    <svg class="w-6 h-6" :class="iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </slot>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [Number, String],
        required: true,
    },
    change: {
        type: Number,
        default: undefined,
    },
    changeLabel: {
        type: String,
        default: 'vs o\'tgan oy',
    },
    type: {
        type: String,
        default: 'default', // default, success, warning, danger, info
    },
    format: {
        type: String,
        default: 'number', // number, currency, percent
    },
});

const formattedValue = computed(() => {
    if (typeof props.value === 'string') return props.value;

    if (props.format === 'currency') {
        return new Intl.NumberFormat('uz-UZ').format(props.value) + ' so\'m';
    }
    if (props.format === 'percent') {
        return props.value + '%';
    }
    return new Intl.NumberFormat('uz-UZ').format(props.value);
});

const changeColor = computed(() => {
    if (props.change >= 0) return 'text-green-600';
    return 'text-red-600';
});

const iconBgColor = computed(() => {
    const colors = {
        default: 'bg-gray-100',
        success: 'bg-green-100',
        warning: 'bg-yellow-100',
        danger: 'bg-red-100',
        info: 'bg-blue-100',
    };
    return colors[props.type] || 'bg-gray-100';
});

const iconColor = computed(() => {
    const colors = {
        default: 'text-gray-600',
        success: 'text-green-600',
        warning: 'text-yellow-600',
        danger: 'text-red-600',
        info: 'text-blue-600',
    };
    return colors[props.type] || 'text-gray-600';
});
</script>
