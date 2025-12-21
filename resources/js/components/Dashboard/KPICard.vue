<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ label }}</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ formatted }}</p>

                <div v-if="changeDay" class="mt-2 flex items-center space-x-2">
                    <span
                        :class="[
                            'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                            changeDay.is_positive
                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                        ]"
                    >
                        <svg
                            :class="[
                                'w-3 h-3 mr-1',
                                changeDay.direction === 'up' ? 'rotate-0' : 'rotate-180'
                            ]"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        {{ changeDay.value }}%
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">kecha</span>
                </div>

                <div v-if="changeWeek" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    <span
                        :class="changeWeek.is_positive ? 'text-green-600' : 'text-red-600'"
                    >
                        {{ changeWeek.direction === 'up' ? '+' : '-' }}{{ changeWeek.value }}%
                    </span>
                    hafta davomida
                </div>
            </div>

            <div
                :class="[
                    'p-3 rounded-lg',
                    colorClasses.bg
                ]"
            >
                <component
                    :is="iconComponent"
                    :class="['w-6 h-6', colorClasses.icon]"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
    CurrencyDollarIcon,
    UserGroupIcon,
    BanknotesIcon,
    ChartBarIcon,
    HeartIcon,
    ArrowTrendingUpIcon,
} from '@heroicons/vue/24/outline';

interface Change {
    value: number;
    direction: 'up' | 'down';
    is_positive: boolean;
}

const props = defineProps<{
    label: string;
    value: number;
    formatted: string;
    changeDay?: Change | null;
    changeWeek?: Change | null;
    icon: string;
    color: string;
}>();

const iconComponents: Record<string, any> = {
    'currency-dollar': CurrencyDollarIcon,
    'user-group': UserGroupIcon,
    'banknotes': BanknotesIcon,
    'chart-bar': ChartBarIcon,
    'heart': HeartIcon,
    'arrow-trending-up': ArrowTrendingUpIcon,
};

const iconComponent = computed(() => iconComponents[props.icon] || ChartBarIcon);

const colorClasses = computed(() => {
    const colors: Record<string, { bg: string; icon: string }> = {
        green: {
            bg: 'bg-green-100 dark:bg-green-900',
            icon: 'text-green-600 dark:text-green-400',
        },
        blue: {
            bg: 'bg-blue-100 dark:bg-blue-900',
            icon: 'text-blue-600 dark:text-blue-400',
        },
        purple: {
            bg: 'bg-purple-100 dark:bg-purple-900',
            icon: 'text-purple-600 dark:text-purple-400',
        },
        amber: {
            bg: 'bg-amber-100 dark:bg-amber-900',
            icon: 'text-amber-600 dark:text-amber-400',
        },
        pink: {
            bg: 'bg-pink-100 dark:bg-pink-900',
            icon: 'text-pink-600 dark:text-pink-400',
        },
        indigo: {
            bg: 'bg-indigo-100 dark:bg-indigo-900',
            icon: 'text-indigo-600 dark:text-indigo-400',
        },
    };
    return colors[props.color] || colors.blue;
});
</script>
