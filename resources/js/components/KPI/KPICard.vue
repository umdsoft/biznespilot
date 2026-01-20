<script setup>
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    title: { type: String, required: true },
    value: { type: [String, Number], required: true },
    target: { type: [String, Number], default: null },
    badge: { type: String, default: null },
    badgeType: { type: String, default: 'neutral' }, // 'success', 'warning', 'danger', 'neutral'
    icon: { type: [Object, Function], default: null },
    iconBgColor: { type: String, default: 'blue' },
    suffix: { type: String, default: '' },
    showProgress: { type: Boolean, default: true },
    subtitle: { type: String, default: null },
});

const colorMap = {
    green: 'from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30',
    blue: 'from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30',
    purple: 'from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30',
    orange: 'from-orange-100 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/30',
    red: 'from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30',
    indigo: 'from-indigo-100 to-violet-100 dark:from-indigo-900/30 dark:to-violet-900/30',
    emerald: 'from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30',
};

const iconColorMap = {
    green: 'text-green-600 dark:text-green-400',
    blue: 'text-blue-600 dark:text-blue-400',
    purple: 'text-purple-600 dark:text-purple-400',
    orange: 'text-orange-600 dark:text-orange-400',
    red: 'text-red-600 dark:text-red-400',
    indigo: 'text-indigo-600 dark:text-indigo-400',
    emerald: 'text-emerald-600 dark:text-emerald-400',
};

const badgeColorMap = {
    success: 'text-green-600 dark:text-green-400',
    warning: 'text-yellow-600 dark:text-yellow-400',
    danger: 'text-red-600 dark:text-red-400',
    neutral: 'text-gray-500 dark:text-gray-400',
};

const getProgressPercentage = () => {
    if (!props.target) return 0;
    return Math.min(100, Math.round((Number(props.value) / Number(props.target)) * 100));
};

const getProgressColor = () => {
    const percentage = getProgressPercentage();
    if (percentage >= 100) return 'bg-green-500';
    if (percentage >= 70) return 'bg-yellow-500';
    return 'bg-red-500';
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div
                v-if="icon"
                :class="['w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center', colorMap[iconBgColor] || colorMap.blue]"
            >
                <component :is="icon" :class="['w-6 h-6', iconColorMap[iconBgColor] || iconColorMap.blue]" />
            </div>
            <span v-if="badge" :class="['text-sm font-medium', badgeColorMap[badgeType] || badgeColorMap.neutral]">
                {{ badge }}
            </span>
        </div>
        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ value }}{{ suffix }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ title }}</p>
        <p v-if="subtitle" class="text-xs text-gray-400 mt-1">{{ subtitle }}</p>
        <div v-if="showProgress && target" class="mt-3">
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div
                    :class="['h-2 rounded-full transition-all', getProgressColor()]"
                    :style="{ width: getProgressPercentage() + '%' }"
                ></div>
            </div>
            <p class="text-xs text-gray-400 mt-1">{{ t('kpi.target') }}: {{ target }}{{ suffix }}</p>
        </div>
    </div>
</template>
