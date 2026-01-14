<template>
    <div class="space-y-3">
        <div v-for="(stage, index) in stages" :key="stage.id || index" class="relative">
            <div class="flex items-center justify-between mb-1">
                <span class="text-sm font-medium text-gray-700">{{ stage.label }}</span>
                <span class="text-sm text-gray-500">{{ stage.count }} ta</span>
            </div>
            <div class="h-8 bg-gray-100 rounded-lg overflow-hidden relative">
                <div
                    :class="stageColor(index)"
                    class="h-full transition-all duration-500 flex items-center justify-end pr-2"
                    :style="{ width: `${getPercentage(stage.count)}%` }"
                >
                    <span v-if="stage.count > 0" class="text-white text-xs font-medium">
                        {{ formatCurrency(stage.value) }}
                    </span>
                </div>
            </div>
        </div>
        <div v-if="stages.length === 0" class="text-center py-8 text-gray-500">
            Ma'lumot yo'q
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

const totalCount = computed(() => {
    return props.stages.reduce((sum, stage) => sum + (stage.count || 0), 0);
});

const getPercentage = (count) => {
    if (totalCount.value === 0) return 0;
    return Math.max((count / totalCount.value) * 100, 5); // Minimum 5% for visibility
};

const stageColor = (index) => {
    const colors = [
        'bg-blue-500',
        'bg-yellow-500',
        'bg-purple-500',
        'bg-indigo-500',
        'bg-orange-500',
        'bg-green-500',
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
