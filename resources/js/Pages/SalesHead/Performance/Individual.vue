<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { formatFullCurrency, formatPercent, getInitials, getAvatarColor } from '@/utils/formatting';
import { ArrowLeftIcon, PhoneIcon, CurrencyDollarIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    employee: {
        type: Object,
        default: () => ({
            id: 1,
            name: 'Jasur Aliyev',
            role: 'Senior Sales Manager',
            performance: 95,
            calls: 156,
            deals: 18,
            revenue: 75000000,
            target: 80000000,
        }),
    },
    monthlyData: {
        type: Array,
        default: () => [
            { month: 'Okt', revenue: 22000000 },
            { month: 'Noy', revenue: 28000000 },
            { month: 'Dek', revenue: 25000000 },
        ],
    },
});

const targetProgress = (props.employee.revenue / props.employee.target) * 100;
</script>

<template>
    <SalesHeadLayout title="Individual Samaradorlik">
        <Head title="Individual Samaradorlik" />

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
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Individual Samaradorlik</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Xodim bo'yicha batafsil tahlil</p>
                </div>
            </div>

            <!-- Employee Card -->
            <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl p-6 text-white">
                <div class="flex items-center gap-4">
                    <div
                        class="w-16 h-16 rounded-full flex items-center justify-center text-xl font-bold bg-white/20"
                    >
                        {{ getInitials(employee.name) }}
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold">{{ employee.name }}</h2>
                        <p class="text-emerald-100">{{ employee.role }}</p>
                    </div>
                    <div class="ml-auto text-right">
                        <p class="text-4xl font-bold">{{ employee.performance }}%</p>
                        <p class="text-emerald-100">Samaradorlik</p>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <PhoneIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ employee.calls }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Qo'ng'iroqlar</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <CheckCircleIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ employee.deals }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Bitimlar</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                            <CurrencyDollarIcon class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatFullCurrency(employee.revenue) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Daromad</p>
                </div>
            </div>

            <!-- Target Progress -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Maqsad bajarilishi</h3>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ formatFullCurrency(employee.target) }}</span>
                </div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div
                        class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full"
                        :style="{ width: `${Math.min(targetProgress, 100)}%` }"
                    ></div>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ formatPercent(targetProgress) }} bajarildi</p>
            </div>
        </div>
    </SalesHeadLayout>
</template>
