<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { formatFullCurrency, formatPercent, getInitials, getAvatarColor } from '@/utils/formatting';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    teamStats: {
        type: Object,
        default: () => ({
            total_members: 8,
            avg_performance: 87.5,
            total_revenue: 320000000,
            target_completion: 92,
        }),
    },
    members: {
        type: Array,
        default: () => [
            { id: 1, name: 'Jasur Aliyev', performance: 95, revenue: 52000000 },
            { id: 2, name: 'Dilnoza Karimova', performance: 88, revenue: 45000000 },
            { id: 3, name: 'Bobur Tursunov', performance: 82, revenue: 38000000 },
        ],
    },
});
</script>

<template>
    <SalesHeadLayout title="Jamoa Samaradorligi">
        <Head title="Jamoa Samaradorligi" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    href="/sales-head/performance"
                    class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                >
                    <ArrowLeftIcon class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Jamoa Samaradorligi</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Umumiy jamoa ko'rsatkichlari</p>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Jami xodimlar</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ teamStats.total_members }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">O'rtacha samaradorlik</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ teamStats.avg_performance }}%</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Jami daromad</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ formatFullCurrency(teamStats.total_revenue) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Maqsad bajarilishi</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ teamStats.target_completion }}%</p>
                </div>
            </div>

            <!-- Members List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Xodimlar</h2>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="member in members"
                        :key="member.id"
                        class="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold bg-gradient-to-br"
                                :class="getAvatarColor(member.name)"
                            >
                                {{ getInitials(member.name) }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ member.name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatFullCurrency(member.revenue) }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-24 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-emerald-500 rounded-full"
                                    :style="{ width: `${member.performance}%` }"
                                ></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white w-12 text-right">{{ member.performance }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SalesHeadLayout>
</template>
