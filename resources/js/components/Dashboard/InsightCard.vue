<template>
    <div
        class="bg-white rounded-lg border p-5 hover:shadow-md transition-shadow"
        :class="{ 'cursor-pointer': clickable }"
        @click="clickable && $emit('click', insight)"
    >
        <div class="flex items-start gap-4">
            <div
                :class="iconBgColor"
                class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
            >
                <svg v-if="insight.type === 'growth'" class="w-6 h-6" :class="iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                <svg v-else-if="insight.type === 'decline'" class="w-6 h-6" :class="iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                </svg>
                <svg v-else-if="insight.type === 'opportunity'" class="w-6 h-6" :class="iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                <svg v-else class="w-6 h-6" :class="iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span :class="typeClass" class="px-2 py-0.5 rounded-full text-xs font-medium">
                        {{ typeLabel }}
                    </span>
                    <span v-if="insight.priority === 'high'" class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-medium">
                        {{ t('dashboard.insight.important') }}
                    </span>
                </div>
                <h3 class="font-semibold text-gray-900">{{ insight.title }}</h3>
                <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ insight.description }}</p>
                <div v-if="insight.metric_value" class="mt-2">
                    <span class="text-lg font-bold" :class="metricColor">
                        {{ insight.metric_change > 0 ? '+' : '' }}{{ insight.metric_change }}%
                    </span>
                    <span class="text-sm text-gray-500 ml-2">{{ insight.metric_label }}</span>
                </div>
                <div v-if="insight.action_text" class="mt-3">
                    <button class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        {{ insight.action_text }} →
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    insight: {
        type: Object,
        required: true,
    },
    clickable: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['click']);

const iconBgColor = computed(() => {
    const type = props.insight.type;
    const colors = {
        growth: 'bg-green-100',
        decline: 'bg-red-100',
        opportunity: 'bg-yellow-100',
        info: 'bg-blue-100',
    };
    return colors[type] || 'bg-gray-100';
});

const iconColor = computed(() => {
    const type = props.insight.type;
    const colors = {
        growth: 'text-green-600',
        decline: 'text-red-600',
        opportunity: 'text-yellow-600',
        info: 'text-blue-600',
    };
    return colors[type] || 'text-gray-600';
});

const typeClass = computed(() => {
    const type = props.insight.type;
    const classes = {
        growth: 'bg-green-100 text-green-700',
        decline: 'bg-red-100 text-red-700',
        opportunity: 'bg-yellow-100 text-yellow-700',
        info: 'bg-blue-100 text-blue-700',
    };
    return classes[type] || 'bg-gray-100 text-gray-700';
});

const typeLabel = computed(() => {
    const type = props.insight.type;
    const labels = {
        growth: t('dashboard.insight.type_growth'),
        decline: t('dashboard.insight.type_decline'),
        opportunity: t('dashboard.insight.type_opportunity'),
        info: t('dashboard.insight.type_info'),
    };
    return labels[type] || type;
});

const metricColor = computed(() => {
    const change = props.insight.metric_change;
    if (change > 0) return 'text-green-600';
    if (change < 0) return 'text-red-600';
    return 'text-gray-600';
});
</script>
