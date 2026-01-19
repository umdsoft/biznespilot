<script setup>
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatFullCurrency, formatPercent } from '@/utils/formatting';
import {
    ArrowLeftIcon,
    ArrowUpIcon,
    ArrowDownIcon,
    DocumentArrowDownIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({
            revenue: [],
            expenses: [],
            summary: {},
        }),
    },
    period: {
        type: String,
        default: 'month',
    },
});

const totalRevenue = computed(() => {
    return props.data.revenue?.reduce((sum, item) => sum + item.amount, 0) || 0;
});

const totalExpenses = computed(() => {
    return props.data.expenses?.reduce((sum, item) => sum + item.amount, 0) || 0;
});

const netProfit = computed(() => totalRevenue.value - totalExpenses.value);

const profitMargin = computed(() => {
    if (totalRevenue.value === 0) return 0;
    return (netProfit.value / totalRevenue.value) * 100;
});
</script>

<template>
    <FinanceLayout title="Foyda va Zarar">
        <Head title="Foyda va Zarar Hisoboti" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        href="/finance/reports"
                        class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                    >
                        <ArrowLeftIcon class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Foyda va Zarar Hisoboti</h1>
                        <p class="mt-1 text-gray-500 dark:text-gray-400">Daromad va xarajatlar tahlili</p>
                    </div>
                </div>
                <button
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors"
                >
                    <DocumentArrowDownIcon class="w-5 h-5" />
                    Yuklab olish
                </button>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Jami Daromad</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ formatFullCurrency(totalRevenue) }}</p>
                    <div class="flex items-center gap-1 mt-2 text-green-600">
                        <ArrowUpIcon class="w-4 h-4" />
                        <span class="text-sm">+12.5%</span>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Jami Xarajatlar</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ formatFullCurrency(totalExpenses) }}</p>
                    <div class="flex items-center gap-1 mt-2 text-red-600">
                        <ArrowUpIcon class="w-4 h-4" />
                        <span class="text-sm">+8.3%</span>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sof Foyda</p>
                    <p class="text-2xl font-bold mt-1" :class="netProfit >= 0 ? 'text-green-600' : 'text-red-600'">
                        {{ formatFullCurrency(netProfit) }}
                    </p>
                    <div class="flex items-center gap-1 mt-2 text-green-600">
                        <ArrowUpIcon class="w-4 h-4" />
                        <span class="text-sm">+18.2%</span>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Foyda Marjasi</p>
                    <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ formatPercent(profitMargin) }}</p>
                    <div class="flex items-center gap-1 mt-2 text-green-600">
                        <ArrowUpIcon class="w-4 h-4" />
                        <span class="text-sm">+2.1%</span>
                    </div>
                </div>
            </div>

            <!-- Revenue and Expenses Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Revenue -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daromadlar</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div
                                v-for="item in data.revenue"
                                :key="item.category"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                                    <span class="text-gray-700 dark:text-gray-300">{{ item.category }}</span>
                                </div>
                                <span class="font-medium text-gray-900 dark:text-white">{{ formatFullCurrency(item.amount) }}</span>
                            </div>
                        </div>
                        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                            <span class="font-semibold text-gray-900 dark:text-white">Jami</span>
                            <span class="font-bold text-green-600">{{ formatFullCurrency(totalRevenue) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Expenses -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Xarajatlar</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div
                                v-for="item in data.expenses"
                                :key="item.category"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                    <span class="text-gray-700 dark:text-gray-300">{{ item.category }}</span>
                                </div>
                                <span class="font-medium text-gray-900 dark:text-white">{{ formatFullCurrency(item.amount) }}</span>
                            </div>
                        </div>
                        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                            <span class="font-semibold text-gray-900 dark:text-white">Jami</span>
                            <span class="font-bold text-red-600">{{ formatFullCurrency(totalExpenses) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profit Summary -->
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h3 class="text-lg font-semibold mb-1">Sof Foyda</h3>
                        <p class="text-3xl font-bold">{{ formatFullCurrency(netProfit) }}</p>
                        <p class="text-green-100 text-sm mt-2">O'tgan oyga nisbatan +18.2% o'sish</p>
                    </div>
                    <div class="text-right">
                        <p class="text-green-100 text-sm">Foyda marjasi</p>
                        <p class="text-2xl font-bold">{{ formatPercent(profitMargin) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </FinanceLayout>
</template>
