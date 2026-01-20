<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { useI18n } from '@/i18n';
import { formatFullCurrency, formatPercent, getInitials, getAvatarColor } from '@/utils/formatting';
import {
    ArrowLeftIcon,
    TrophyIcon,
    ArrowUpIcon,
    ArrowDownIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    teamMembers: {
        type: Array,
        default: () => [
            { id: 1, name: 'Jasur Aliyev', role: 'Senior Sales', calls: 85, deals: 12, revenue: 45000000, target: 50000000 },
            { id: 2, name: 'Dilnoza Karimova', role: 'Sales Rep', calls: 72, deals: 9, revenue: 32000000, target: 35000000 },
            { id: 3, name: 'Bobur Tursunov', role: 'Sales Rep', calls: 68, deals: 8, revenue: 28000000, target: 30000000 },
            { id: 4, name: 'Malika Rahimova', role: 'Junior Sales', calls: 55, deals: 5, revenue: 18000000, target: 20000000 },
        ],
    },
});

const getProgress = (current, target) => Math.min((current / target) * 100, 100);
</script>

<template>
    <SalesHeadLayout :title="t('saleshead.team_performance')">
        <Head :title="t('saleshead.team_performance')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    href="/sales-head/team"
                    class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                >
                    <ArrowLeftIcon class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('saleshead.team_performance') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('saleshead.employee_analysis') }}</p>
                </div>
            </div>

            <!-- Top Performer -->
            <div class="bg-gradient-to-r from-amber-500 to-orange-500 rounded-xl p-6 text-white">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                        <TrophyIcon class="w-8 h-8" />
                    </div>
                    <div>
                        <p class="text-amber-100 text-sm">{{ t('saleshead.best_seller_of_month') }}</p>
                        <p class="text-2xl font-bold">{{ teamMembers[0]?.name || 'N/A' }}</p>
                        <p class="text-amber-100">{{ formatFullCurrency(teamMembers[0]?.revenue || 0) }} {{ t('saleshead.revenue') }}</p>
                    </div>
                </div>
            </div>

            <!-- Team Performance Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ t('saleshead.employee_rating') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr class="text-left text-sm text-gray-500 dark:text-gray-400">
                                <th class="px-6 py-3 font-medium">#</th>
                                <th class="px-6 py-3 font-medium">{{ t('saleshead.employee') }}</th>
                                <th class="px-6 py-3 font-medium text-center">{{ t('nav.calls') }}</th>
                                <th class="px-6 py-3 font-medium text-center">{{ t('sales.deals') }}</th>
                                <th class="px-6 py-3 font-medium text-right">{{ t('saleshead.revenue') }}</th>
                                <th class="px-6 py-3 font-medium">{{ t('saleshead.target') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="(member, index) in teamMembers"
                                :key="member.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            >
                                <td class="px-6 py-4">
                                    <span
                                        class="w-6 h-6 rounded-full flex items-center justify-center text-sm font-bold"
                                        :class="{
                                            'bg-amber-100 text-amber-700': index === 0,
                                            'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300': index > 0,
                                        }"
                                    >
                                        {{ index + 1 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold bg-gradient-to-br"
                                            :class="getAvatarColor(member.name)"
                                        >
                                            {{ getInitials(member.name) }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ member.name }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ member.role }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center font-medium text-gray-900 dark:text-white">{{ member.calls }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-full text-sm font-medium">
                                        {{ member.deals }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-gray-900 dark:text-white">
                                    {{ formatFullCurrency(member.revenue) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="w-32">
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="text-gray-500 dark:text-gray-400">{{ formatPercent(getProgress(member.revenue, member.target)) }}</span>
                                        </div>
                                        <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                            <div
                                                class="h-full rounded-full transition-all"
                                                :class="getProgress(member.revenue, member.target) >= 100 ? 'bg-emerald-500' : 'bg-blue-500'"
                                                :style="{ width: `${getProgress(member.revenue, member.target)}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </SalesHeadLayout>
</template>
