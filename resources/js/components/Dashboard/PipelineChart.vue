<script setup>
import { computed } from 'vue';

const props = defineProps({
    pipeline: { type: Object, default: () => ({}) },
    stages: {
        type: Object,
        default: () => ({
            new: { label: 'Yangi', color: 'bg-blue-500' },
            contacted: { label: "Bog'lanildi", color: 'bg-yellow-500' },
            qualified: { label: 'Kvalifikatsiya', color: 'bg-purple-500' },
            proposal: { label: 'Taklif', color: 'bg-orange-500' },
            negotiation: { label: 'Muzokara', color: 'bg-pink-500' },
        }),
    },
});

const total = computed(() => {
    return Object.values(props.pipeline).reduce((sum, s) => sum + (s?.count || 0), 0);
});

const getStageWidth = (stage) => {
    if (total.value === 0) return 0;
    return Math.round(((props.pipeline[stage]?.count || 0) / total.value) * 100);
};
</script>

<template>
    <div class="space-y-4">
        <div v-for="(stage, key) in stages" :key="key" class="flex items-center">
            <div class="w-32 text-sm text-gray-600 dark:text-gray-400">{{ stage.label }}</div>
            <div class="flex-1 mx-4">
                <div class="h-8 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
                    <div
                        :class="stage.color"
                        class="h-full rounded-lg transition-all duration-500"
                        :style="{ width: getStageWidth(key) + '%' }"
                    ></div>
                </div>
            </div>
            <div class="w-20 text-right">
                <span class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ pipeline[key]?.count || 0 }}
                </span>
                <span class="text-xs text-gray-500 ml-1">lead</span>
            </div>
        </div>
    </div>
</template>
