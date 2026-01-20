<template>
    <div class="space-y-3">
        <div v-if="stats.length === 0" class="text-center py-6 text-gray-500">
            {{ t('kpi.no_statistics') }}
        </div>
        <div
            v-for="(stat, index) in stats"
            :key="stat.id || index"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
        >
            <div class="flex items-center gap-3">
                <div :class="stat.iconBg || 'bg-indigo-100'" class="w-10 h-10 rounded-lg flex items-center justify-center">
                    <svg :class="stat.iconColor || 'text-indigo-600'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900">{{ stat.label }}</p>
                    <p v-if="stat.description" class="text-xs text-gray-500">{{ stat.description }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-lg font-bold text-gray-900">{{ formatValue(stat.value, stat.format) }}</p>
                <p v-if="stat.target" class="text-xs text-gray-500">
                    {{ t('kpi.target') }}: {{ formatValue(stat.target, stat.format) }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    stats: {
        type: Array,
        default: () => [],
    },
});

const formatValue = (value, format) => {
    if (format === 'currency') {
        return new Intl.NumberFormat('uz-UZ').format(value || 0) + ' so\'m';
    }
    if (format === 'percent') {
        return (value || 0) + '%';
    }
    return new Intl.NumberFormat('uz-UZ').format(value || 0);
};
</script>
