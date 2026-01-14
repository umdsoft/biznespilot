<template>
    <div class="bg-white border rounded-lg p-4 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h3 class="font-semibold text-gray-900">{{ goal.title }}</h3>
                <p v-if="goal.description" class="text-sm text-gray-500 mt-1">{{ goal.description }}</p>
                <div class="flex items-center gap-4 mt-2 text-sm">
                    <span v-if="goal.target_value" class="text-gray-600">
                        Maqsad: {{ formatNumber(goal.target_value) }}
                    </span>
                    <span v-if="goal.current_value !== undefined" class="text-gray-600">
                        Hozirgi: {{ formatNumber(goal.current_value) }}
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span :class="statusClass" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ statusLabel }}
                </span>
                <span class="text-lg font-bold" :class="progressColor">{{ goal.progress || 0 }}%</span>
            </div>
        </div>
        <div class="mt-3">
            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                <div
                    class="h-full rounded-full transition-all duration-300"
                    :class="progressBarColor"
                    :style="{ width: `${Math.min(goal.progress || 0, 100)}%` }"
                ></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    goal: {
        type: Object,
        required: true,
    },
});

const formatNumber = (num) => {
    return new Intl.NumberFormat('uz-UZ').format(num || 0);
};

const statusClass = computed(() => {
    const status = props.goal.status;
    const classes = {
        completed: 'bg-green-100 text-green-800',
        in_progress: 'bg-blue-100 text-blue-800',
        pending: 'bg-gray-100 text-gray-800',
        delayed: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
});

const statusLabel = computed(() => {
    const status = props.goal.status;
    const labels = {
        completed: 'Bajarildi',
        in_progress: 'Jarayonda',
        pending: 'Kutilmoqda',
        delayed: 'Kechikkan',
    };
    return labels[status] || status;
});

const progressColor = computed(() => {
    const progress = props.goal.progress || 0;
    if (progress >= 100) return 'text-green-600';
    if (progress >= 70) return 'text-blue-600';
    if (progress >= 40) return 'text-yellow-600';
    return 'text-gray-600';
});

const progressBarColor = computed(() => {
    const progress = props.goal.progress || 0;
    if (progress >= 100) return 'bg-green-500';
    if (progress >= 70) return 'bg-blue-500';
    if (progress >= 40) return 'bg-yellow-500';
    return 'bg-gray-400';
});
</script>
