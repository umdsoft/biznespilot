<script setup>
import { Head } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import {
    ChartBarIcon,
    TrophyIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    performance: {
        type: Object,
        default: () => ({
            overall: { leads: 0, won: 0, lost: 0, conversion_rate: 0 },
            trend: 'up',
            top_performers: [],
        }),
    },
    period: {
        type: String,
        default: 'month',
    },
});

const formatPercent = (value) => {
    return (value || 0).toFixed(1) + '%';
};

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>

<template>
    <SalesHeadLayout title="Samaradorlik">
        <Head title="Samaradorlik" />

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Samaradorlik</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Jamoa va individual samaradorlik ko'rsatkichlari</p>
            </div>

            <!-- Overall Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Jami Leadlar</p>
                        <ChartBarIcon class="w-5 h-5 text-blue-500" />
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ performance.overall.leads }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Yutilgan</p>
                        <TrophyIcon class="w-5 h-5 text-green-500" />
                    </div>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ performance.overall.won }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Yo'qotilgan</p>
                        <ArrowTrendingDownIcon class="w-5 h-5 text-red-500" />
                    </div>
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ performance.overall.lost }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Konversiya</p>
                        <component :is="performance.trend === 'up' ? ArrowTrendingUpIcon : ArrowTrendingDownIcon" :class="performance.trend === 'up' ? 'text-green-500' : 'text-red-500'" class="w-5 h-5" />
                    </div>
                    <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatPercent(performance.overall.conversion_rate) }}</p>
                </div>
            </div>

            <!-- Top Performers -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Top Performerlar</h2>
                    <TrophyIcon class="w-6 h-6 text-yellow-500" />
                </div>

                <div v-if="performance.top_performers.length === 0" class="text-center py-8">
                    <UsersIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                    <p class="text-gray-500 dark:text-gray-400">Hali ma'lumotlar yo'q</p>
                </div>

                <div v-else class="space-y-4">
                    <div
                        v-for="(performer, index) in performance.top_performers"
                        :key="performer.id"
                        class="flex items-center gap-4 p-4 rounded-lg bg-gray-50 dark:bg-gray-900/50"
                    >
                        <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center">
                            <span v-if="index === 0" class="text-2xl">🥇</span>
                            <span v-else-if="index === 1" class="text-2xl">🥈</span>
                            <span v-else-if="index === 2" class="text-2xl">🥉</span>
                            <span v-else class="text-lg font-bold text-gray-400">{{ index + 1 }}</span>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white font-bold">
                            {{ getInitials(performer.name) }}
                        </div>
                        <div class="flex-1">
                            <h3 class="font-medium text-gray-900 dark:text-white">{{ performer.name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ performer.deals_won || 0 }} bitim yutildi</p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ formatPercent(performer.conversion_rate) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">konversiya</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Period Stats -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Davriy Tahlil</h2>
                <p class="text-gray-500 dark:text-gray-400 text-center py-12">
                    Grafiklar tez orada qo'shiladi...
                </p>
            </div>
        </div>
    </SalesHeadLayout>
</template>
