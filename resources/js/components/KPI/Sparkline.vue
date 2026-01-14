<template>
    <svg :width="width" :height="height" class="overflow-visible">
        <path
            v-if="points.length > 1"
            :d="linePath"
            fill="none"
            :stroke="color"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
        <circle
            v-if="showDot && points.length > 0"
            :cx="points[points.length - 1].x"
            :cy="points[points.length - 1].y"
            r="3"
            :fill="color"
        />
    </svg>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: {
        type: Array,
        default: () => [],
    },
    width: {
        type: Number,
        default: 80,
    },
    height: {
        type: Number,
        default: 24,
    },
    color: {
        type: String,
        default: '#6366f1', // indigo-500
    },
    showDot: {
        type: Boolean,
        default: true,
    },
});

const points = computed(() => {
    if (!props.data || props.data.length === 0) return [];

    const values = props.data.map((v) => (typeof v === 'object' ? v.value : v) || 0);
    const min = Math.min(...values);
    const max = Math.max(...values);
    const range = max - min || 1;

    const padding = 4;
    const chartWidth = props.width - padding * 2;
    const chartHeight = props.height - padding * 2;

    return values.map((value, index) => ({
        x: padding + (index / (values.length - 1 || 1)) * chartWidth,
        y: padding + chartHeight - ((value - min) / range) * chartHeight,
    }));
});

const linePath = computed(() => {
    if (points.value.length < 2) return '';

    return points.value
        .map((point, index) => {
            const prefix = index === 0 ? 'M' : 'L';
            return `${prefix}${point.x},${point.y}`;
        })
        .join(' ');
});
</script>
