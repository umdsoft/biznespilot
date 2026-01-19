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
            by_category: [],
            monthly_comparison: [],
            summary: {
                total: 0,
                average_monthly: 0,
                highest_category: '',
            },
        }),
    },
});

const categoryColors = ['bg-blue-500', 'bg-purple-500', 'bg-green-500', 'bg-orange-500', 'bg-pink-500', 'bg-indigo-500'];

const getCategoryColor = (index) => categoryColors[index % categoryColors.length];

const maxCategoryAmount = computed(() => {
    return Math.max(...(props.data.by_category?.map(c => c.amount) || [1]));
});
</script>

<template>
    <FinanceLayout title="Xarajatlar">
        <Head title="Xarajatlar Hisoboti" />

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
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Xarajatlar Hisoboti</h1>
                        <p class="mt-1 text-gray-500 dark:text-gray-400">Kategoriyalar bo'yicha xarajatlar tahlili</p>
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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Jami Xarajatlar</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ formatFullCurrency(data.summary?.total) }}
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Oylik O'rtacha</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ formatFullCurrency(data.summary?.average_monthly) }}
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Eng Katta Kategoriya</p>
                    <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">
                        {{ data.summary?.highest_category || '-' }}
                    </p>
                </div>
            </div>

            <!-- Categories Breakdown -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Kategoriyalar Bo'yicha</h3>
                </div>
                <div class="p-6 space-y-5">
                    <div
                        v-for="(category, index) in data.by_category"
                        :key="category.category"
                        class="space-y-2"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full" :class="getCategoryColor(index)"></div>
                                <span class="text-gray-700 dark:text-gray-300 font-medium">{{ category.category }}</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ formatFullCurrency(category.amount) }}
                                </span>
                                <span class="text-sm text-gray-500 dark:text-gray-400 w-12 text-right">
                                    {{ formatPercent(category.percentage) }}
                                </span>
                                <div class="flex items-center gap-1 w-16">
                                    <component
                                        :is="category.trend >= 0 ? ArrowUpIcon : ArrowDownIcon"
                                        class="w-3 h-3"
                                        :class="category.trend >= 0 ? 'text-red-500' : 'text-green-500'"
                                    />
                                    <span
                                        class="text-xs"
                                        :class="category.trend >= 0 ? 'text-red-500' : 'text-green-500'"
                                    >
                                        {{ Math.abs(category.trend) }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-300"
                                :class="getCategoryColor(index)"
                                :style="{ width: `${(category.amount / maxCategoryAmount) * 100}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Comparison -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Oylik Taqqoslash</h3>
                </div>
                <div class="p-6">
                    <div class="flex items-end justify-between gap-4 h-48">
                        <div
                            v-for="month in data.monthly_comparison"
                            :key="month.month"
                            class="flex-1 flex flex-col items-center"
                        >
                            <div class="flex-1 w-full flex items-end justify-center">
                                <div
                                    class="w-full max-w-16 bg-gradient-to-t from-indigo-500 to-indigo-400 rounded-t-lg transition-all duration-300"
                                    :style="{
                                        height: `${(month.amount / Math.max(...data.monthly_comparison.map(m => m.amount))) * 100}%`,
                                        minHeight: '20px'
                                    }"
                                ></div>
                            </div>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ month.month }}</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ formatFullCurrency(month.amount).replace(" so'm", '') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </FinanceLayout>
</template>
