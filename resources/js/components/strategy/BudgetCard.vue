<template>
    <div class="bg-white border rounded-lg p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">{{ title }}</h3>
            <span v-if="showPercentage" class="text-lg font-bold" :class="percentageColor">
                {{ percentage }}%
            </span>
        </div>
        <div class="space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Umumiy byudjet</span>
                <span class="font-medium text-gray-900">{{ formatCurrency(total) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Sarflangan</span>
                <span class="font-medium text-red-600">{{ formatCurrency(spent) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Qolgan</span>
                <span class="font-medium text-green-600">{{ formatCurrency(remaining) }}</span>
            </div>
        </div>
        <div class="mt-4">
            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                <div
                    class="h-full rounded-full transition-all duration-300"
                    :class="progressColor"
                    :style="{ width: `${Math.min(percentage, 100)}%` }"
                ></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Byudjet',
    },
    total: {
        type: Number,
        default: 0,
    },
    spent: {
        type: Number,
        default: 0,
    },
    showPercentage: {
        type: Boolean,
        default: true,
    },
});

const remaining = computed(() => props.total - props.spent);

const percentage = computed(() => {
    if (props.total === 0) return 0;
    return Math.round((props.spent / props.total) * 100);
});

const percentageColor = computed(() => {
    if (percentage.value >= 90) return 'text-red-600';
    if (percentage.value >= 70) return 'text-yellow-600';
    return 'text-green-600';
});

const progressColor = computed(() => {
    if (percentage.value >= 90) return 'bg-red-500';
    if (percentage.value >= 70) return 'bg-yellow-500';
    return 'bg-green-500';
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('uz-UZ').format(value || 0) + ' so\'m';
};
</script>
