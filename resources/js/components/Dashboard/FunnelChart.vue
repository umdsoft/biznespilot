<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Savdo Funneli</h3>

        <div class="space-y-3">
            <div
                v-for="(stage, index) in stages"
                :key="stage.stage"
                class="relative"
            >
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ stage.label }}
                    </span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">
                        {{ formatNumber(stage.value) }}
                    </span>
                </div>

                <div class="relative h-10 flex items-center">
                    <!-- Funnel bar -->
                    <div
                        :style="{
                            width: `${getWidth(index)}%`,
                            marginLeft: `${getMargin(index)}%`,
                        }"
                        :class="[
                            'h-full rounded transition-all duration-500',
                            stageColors[index % stageColors.length]
                        ]"
                    >
                        <!-- Conversion rate -->
                        <div
                            v-if="index > 0 && conversionRates[index - 1] !== null"
                            class="absolute -top-1 right-0 transform translate-x-full ml-2 text-xs text-gray-500 dark:text-gray-400"
                        >
                            {{ conversionRates[index - 1] }}%
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Umumiy konversiya:</span>
                <span class="font-bold text-gray-900 dark:text-white">
                    {{ overallConversion }}%
                </span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface FunnelStage {
    stage: string;
    label: string;
    value: number;
}

const props = withDefaults(defineProps<{
    stages: FunnelStage[];
}>(), {
    stages: () => [],
});

const stageColors = [
    'bg-blue-500',
    'bg-blue-400',
    'bg-purple-400',
    'bg-purple-500',
    'bg-green-500',
];

const maxValue = computed(() => {
    return Math.max(...props.stages.map(s => s.value), 1);
});

function getWidth(index: number): number {
    const baseWidth = 100 - (index * 8);
    const value = props.stages[index]?.value || 0;
    const valueRatio = value / maxValue.value;
    return Math.max(baseWidth * valueRatio, 10);
}

function getMargin(index: number): number {
    return index * 4;
}

const conversionRates = computed(() => {
    return props.stages.map((stage, index) => {
        if (index === 0) return null;
        const prevValue = props.stages[index - 1]?.value || 0;
        if (prevValue === 0) return null;
        return Math.round((stage.value / prevValue) * 100);
    });
});

const overallConversion = computed(() => {
    if (props.stages.length < 2) return 0;
    const first = props.stages[0]?.value || 0;
    const last = props.stages[props.stages.length - 1]?.value || 0;
    if (first === 0) return 0;
    return Math.round((last / first) * 100 * 10) / 10;
});

function formatNumber(value: number): string {
    if (value >= 1000000) {
        return (value / 1000000).toFixed(1) + 'M';
    }
    if (value >= 1000) {
        return (value / 1000).toFixed(1) + 'K';
    }
    return value.toLocaleString();
}
</script>
