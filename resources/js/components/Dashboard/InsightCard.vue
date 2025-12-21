<template>
    <div
        :class="[
            'rounded-lg border p-4 transition-all duration-200',
            'border-gray-200 dark:border-gray-700',
            'bg-white dark:bg-gray-800',
            !insight.is_viewed ? 'ring-2 ring-blue-500 ring-opacity-50' : '',
        ]"
    >
        <div class="flex items-start justify-between">
            <div class="flex items-start space-x-3">
                <div :class="['p-2 rounded-lg', typeClasses.bg]">
                    <component
                        :is="typeIcon"
                        :class="['w-5 h-5', typeClasses.icon]"
                    />
                </div>

                <div class="flex-1">
                    <div class="flex items-center space-x-2">
                        <span
                            :class="[
                                'px-2 py-0.5 rounded text-xs font-medium',
                                typeClasses.badge
                            ]"
                        >
                            {{ typeLabel }}
                        </span>
                        <span
                            v-if="insight.category"
                            class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                        >
                            {{ insight.category }}
                        </span>
                        <span
                            v-if="insight.confidence_score"
                            class="text-xs text-gray-400"
                        >
                            {{ Math.round(insight.confidence_score * 100) }}% ishonch
                        </span>
                    </div>

                    <h4 class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                        {{ insight.title }}
                    </h4>

                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ insight.summary }}
                    </p>

                    <!-- Recommendations -->
                    <div
                        v-if="insight.recommendations && insight.recommendations.length > 0"
                        class="mt-3 space-y-1"
                    >
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Tavsiyalar:</p>
                        <ul class="space-y-1">
                            <li
                                v-for="(rec, index) in insight.recommendations.slice(0, 3)"
                                :key="index"
                                class="flex items-start text-xs text-gray-600 dark:text-gray-400"
                            >
                                <span class="mr-2 text-blue-500">•</span>
                                {{ rec }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="flex flex-col items-end space-y-2">
                <span
                    :class="[
                        'px-2 py-0.5 rounded text-xs font-medium',
                        priorityClasses
                    ]"
                >
                    {{ priorityLabel }}
                </span>

                <div class="flex items-center space-x-1">
                    <button
                        v-if="!insight.is_acted"
                        @click="$emit('act', insight.id)"
                        class="p-1.5 text-gray-400 hover:text-green-600 dark:hover:text-green-400 rounded"
                        title="Choralar ko'rildi"
                    >
                        <CheckCircleIcon class="w-4 h-4" />
                    </button>

                    <button
                        @click="$emit('dismiss', insight.id)"
                        class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded"
                        title="Yopish"
                    >
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Action taken badge -->
        <div
            v-if="insight.is_acted"
            class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700"
        >
            <div class="flex items-center text-xs text-green-600 dark:text-green-400">
                <CheckCircleIcon class="w-4 h-4 mr-1" />
                Choralar ko'rildi
                <span v-if="insight.action_taken" class="ml-1 text-gray-500">
                    - {{ insight.action_taken }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
    ChartBarIcon,
    ExclamationTriangleIcon,
    LightBulbIcon,
    RocketLaunchIcon,
    ShieldExclamationIcon,
    TrophyIcon,
    CheckCircleIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

interface Insight {
    id: string;
    type: 'trend' | 'anomaly' | 'recommendation' | 'opportunity' | 'warning' | 'celebration';
    category: string | null;
    priority: 'critical' | 'high' | 'medium' | 'low';
    title: string;
    summary: string;
    recommendations: string[] | null;
    confidence_score: number;
    is_viewed: boolean;
    is_acted: boolean;
    action_taken: string | null;
}

const props = defineProps<{
    insight: Insight;
}>();

defineEmits<{
    act: [id: string];
    dismiss: [id: string];
}>();

const typeLabel = computed(() => {
    const labels: Record<string, string> = {
        trend: 'Trend',
        anomaly: 'Anomaliya',
        recommendation: 'Tavsiya',
        opportunity: 'Imkoniyat',
        warning: 'Ogohlantirish',
        celebration: 'Muvaffaqiyat',
    };
    return labels[props.insight.type] || props.insight.type;
});

const typeIcon = computed(() => {
    const icons: Record<string, any> = {
        trend: ChartBarIcon,
        anomaly: ExclamationTriangleIcon,
        recommendation: LightBulbIcon,
        opportunity: RocketLaunchIcon,
        warning: ShieldExclamationIcon,
        celebration: TrophyIcon,
    };
    return icons[props.insight.type] || LightBulbIcon;
});

const typeClasses = computed(() => {
    const classes: Record<string, { bg: string; icon: string; badge: string }> = {
        trend: {
            bg: 'bg-blue-100 dark:bg-blue-900',
            icon: 'text-blue-600 dark:text-blue-400',
            badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        },
        anomaly: {
            bg: 'bg-orange-100 dark:bg-orange-900',
            icon: 'text-orange-600 dark:text-orange-400',
            badge: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        },
        recommendation: {
            bg: 'bg-purple-100 dark:bg-purple-900',
            icon: 'text-purple-600 dark:text-purple-400',
            badge: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        },
        opportunity: {
            bg: 'bg-green-100 dark:bg-green-900',
            icon: 'text-green-600 dark:text-green-400',
            badge: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        },
        warning: {
            bg: 'bg-red-100 dark:bg-red-900',
            icon: 'text-red-600 dark:text-red-400',
            badge: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        },
        celebration: {
            bg: 'bg-yellow-100 dark:bg-yellow-900',
            icon: 'text-yellow-600 dark:text-yellow-400',
            badge: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        },
    };
    return classes[props.insight.type] || classes.recommendation;
});

const priorityLabel = computed(() => {
    const labels: Record<string, string> = {
        critical: 'Kritik',
        high: 'Muhim',
        medium: 'O\'rta',
        low: 'Past',
    };
    return labels[props.insight.priority] || props.insight.priority;
});

const priorityClasses = computed(() => {
    const classes: Record<string, string> = {
        critical: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        high: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        low: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    };
    return classes[props.insight.priority] || classes.low;
});
</script>
