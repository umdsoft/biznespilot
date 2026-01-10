<script setup>
import { computed } from 'vue';

const props = defineProps({
    funnelData: {
        type: Array,
        default: () => [],
    },
});

// Filter out 'lost' for funnel visualization
const funnelStages = computed(() => {
    return props.funnelData.filter(s => s.key !== 'lost');
});

const lostStage = computed(() => {
    return props.funnelData.find(s => s.key === 'lost');
});

// Calculate totals
const totalLeads = computed(() => {
    return props.funnelData.reduce((sum, s) => sum + (s.count || 0), 0);
});

const wonCount = computed(() => {
    const won = props.funnelData.find(s => s.key === 'won');
    return won?.count || 0;
});

const firstStageCount = computed(() => {
    return funnelStages.value[0]?.count || 1;
});

const overallConversion = computed(() => {
    return firstStageCount.value > 0 ? Math.round((wonCount.value / firstStageCount.value) * 100) : 0;
});

// Calculate width percentage based on first stage (100%)
const getWidthPercent = (count) => {
    if (firstStageCount.value === 0) return 100;
    return Math.max((count / firstStageCount.value) * 100, 8);
};

// Get conversion rate from previous stage
const getConversionFromPrevious = (index) => {
    if (index === 0) return 100;
    const current = funnelStages.value[index]?.count || 0;
    const previous = funnelStages.value[index - 1]?.count || 0;
    if (previous === 0) return 0;
    return Math.round((current / previous) * 100);
};

// Funnel colors - gradient from blue to green
const funnelColors = [
    '#3B82F6', // blue
    '#6366F1', // indigo
    '#8B5CF6', // violet
    '#A855F7', // purple
    '#D946EF', // fuchsia
    '#10B981', // emerald (won)
];

const getColor = (index) => funnelColors[index] || funnelColors[funnelColors.length - 1];
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Sotuv Voronkasi</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Konversiya tahlili</p>
                </div>
                <div class="flex items-center gap-6">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ totalLeads }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Jami lidlar</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ overallConversion }}%</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Umumiy konversiya</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Funnel Chart -->
        <div class="p-6">
            <div v-if="funnelStages.length > 0" class="relative">
                <!-- Funnel segments -->
                <div class="funnel-container">
                    <div
                        v-for="(stage, index) in funnelStages"
                        :key="stage.key"
                        class="funnel-segment-wrapper"
                    >
                        <!-- The funnel segment -->
                        <div
                            class="funnel-segment"
                            :style="{
                                '--width-top': getWidthPercent(stage.count) + '%',
                                '--width-bottom': index < funnelStages.length - 1 ? getWidthPercent(funnelStages[index + 1]?.count || 0) + '%' : getWidthPercent(stage.count) + '%',
                                '--color': getColor(index),
                                '--color-dark': getColor(index) + 'dd',
                            }"
                        >
                            <!-- Content inside segment -->
                            <div class="funnel-content">
                                <span class="funnel-label">{{ stage.label }}</span>
                                <div class="funnel-stats">
                                    <span class="funnel-count">{{ stage.count }}</span>
                                    <span class="funnel-percent">{{ stage.percentage }}%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Conversion arrow between segments -->
                        <div
                            v-if="index < funnelStages.length - 1"
                            class="conversion-indicator"
                        >
                            <span
                                class="conversion-badge"
                                :class="{
                                    'bg-emerald-500': getConversionFromPrevious(index + 1) >= 50,
                                    'bg-amber-500': getConversionFromPrevious(index + 1) >= 25 && getConversionFromPrevious(index + 1) < 50,
                                    'bg-red-500': getConversionFromPrevious(index + 1) < 25,
                                }"
                            >
                                {{ getConversionFromPrevious(index + 1) }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-col items-center justify-center py-16 text-gray-400 dark:text-gray-500">
                <svg class="w-16 h-16 mb-4 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 4h18l-3 8h-12L3 4zM6 12l-2 8h16l-2-8" />
                </svg>
                <p class="text-sm font-medium">Ma'lumot yo'q</p>
            </div>
        </div>

        <!-- Bottom stats -->
        <div class="px-6 pb-6">
            <!-- Lost leads -->
            <div v-if="lostStage && lostStage.count > 0" class="mb-4 flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-100 dark:border-red-800/50">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Yo'qotilgan lidlar</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ lostStage.percentage }}%</span>
                    <span class="text-lg font-bold text-red-600 dark:text-red-400">{{ lostStage.count }}</span>
                </div>
            </div>

            <!-- Summary row -->
            <div class="grid grid-cols-3 gap-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                <div class="text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Kirish</p>
                    <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ firstStageCount }}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Yutilgan</p>
                    <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ wonCount }}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Yo'qotilgan</p>
                    <p class="text-xl font-bold text-red-500 dark:text-red-400">{{ lostStage?.count || 0 }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.funnel-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0;
}

.funnel-segment-wrapper {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
}

.funnel-segment {
    width: 100%;
    height: 56px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    clip-path: polygon(
        calc(50% - var(--width-top) / 2) 0%,
        calc(50% + var(--width-top) / 2) 0%,
        calc(50% + var(--width-bottom) / 2) 100%,
        calc(50% - var(--width-bottom) / 2) 100%
    );
    background: linear-gradient(180deg, var(--color) 0%, var(--color-dark) 100%);
    transition: all 0.3s ease;
}

.funnel-segment:hover {
    filter: brightness(1.1);
    transform: scaleX(1.02);
}

.funnel-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 80%;
    max-width: 400px;
    padding: 0 16px;
    z-index: 1;
}

.funnel-label {
    font-size: 14px;
    font-weight: 600;
    color: white;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.funnel-stats {
    display: flex;
    align-items: center;
    gap: 12px;
}

.funnel-count {
    font-size: 20px;
    font-weight: 700;
    color: white;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.funnel-percent {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.8);
    background: rgba(255, 255, 255, 0.2);
    padding: 2px 8px;
    border-radius: 10px;
}

.conversion-indicator {
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10;
}

.conversion-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 700;
    color: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}

/* Dark mode adjustments */
:deep(.dark) .funnel-segment {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}
</style>
