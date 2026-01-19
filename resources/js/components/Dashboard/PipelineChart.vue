<template>
    <div class="space-y-4">
        <div v-for="(stage, index) in safeStages" :key="stage.id || index" class="relative">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <div :class="[
                        'w-2.5 h-2.5 rounded-full',
                        stageDotColor(index)
                    ]"></div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ stage.label }}</span>
                </div>
                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ stage.count }} ta</span>
            </div>
            <div class="h-10 bg-gray-100 dark:bg-gray-700 rounded-xl overflow-hidden relative">
                <div
                    :class="[stageColor(index), 'h-full transition-all duration-500 flex items-center justify-end pr-3 rounded-xl']"
                    :style="{ width: `${getPercentage(stage.count)}%` }"
                >
                    <span v-if="stage.count > 0" class="text-white text-xs font-bold drop-shadow-sm">
                        {{ formatCurrency(stage.value) }}
                    </span>
                </div>
                <!-- Percentage indicator -->
                <div class="absolute inset-y-0 right-3 flex items-center">
                    <span v-if="stage.count > 0 && getPercentage(stage.count) < 40" class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ Math.round(getPercentage(stage.count)) }}%
                    </span>
                </div>
            </div>
        </div>
        <div v-if="safeStages.length === 0" class="text-center py-8">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <p class="text-gray-500 dark:text-gray-400">Ma'lumot yo'q</p>
        </div>

        <!-- Total Summary -->
        <div v-if="safeStages.length > 0" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Jami</span>
                <div class="text-right">
                    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ totalCount }} ta lead</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">{{ formatCurrency(totalValue) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    stages: {
        type: Array,
        default: () => [],
    },
});

// Ensure stages is always an array
const safeStages = computed(() => {
    return Array.isArray(props.stages) ? props.stages : [];
});

const totalCount = computed(() => {
    return safeStages.value.reduce((sum, stage) => sum + (stage.count || 0), 0);
});

const totalValue = computed(() => {
    return safeStages.value.reduce((sum, stage) => sum + (stage.value || 0), 0);
});

const getPercentage = (count) => {
    if (totalCount.value === 0) return 0;
    return Math.max((count / totalCount.value) * 100, 8); // Minimum 8% for visibility
};

const stageColor = (index) => {
    const colors = [
        'bg-gradient-to-r from-blue-500 to-blue-600',
        'bg-gradient-to-r from-yellow-500 to-yellow-600',
        'bg-gradient-to-r from-purple-500 to-purple-600',
        'bg-gradient-to-r from-indigo-500 to-indigo-600',
        'bg-gradient-to-r from-orange-500 to-orange-600',
        'bg-gradient-to-r from-emerald-500 to-emerald-600',
        'bg-gradient-to-r from-red-500 to-red-600',
    ];
    return colors[index % colors.length];
};

const stageDotColor = (index) => {
    const colors = [
        'bg-blue-500',
        'bg-yellow-500',
        'bg-purple-500',
        'bg-indigo-500',
        'bg-orange-500',
        'bg-emerald-500',
        'bg-red-500',
    ];
    return colors[index % colors.length];
};

const formatCurrency = (value) => {
    if (!value) return '';
    if (value >= 1000000) {
        return (value / 1000000).toFixed(1) + 'M';
    }
    if (value >= 1000) {
        return (value / 1000).toFixed(0) + 'K';
    }
    return new Intl.NumberFormat('uz-UZ').format(value);
};
</script>
