<script setup>
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatFullCurrency } from '@/utils/formatting';
import {
    ArrowLeftIcon,
    ArrowUpIcon,
    ArrowDownIcon,
    DocumentArrowDownIcon,
    BanknotesIcon,
    BuildingOfficeIcon,
    CreditCardIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({
            operating: { inflow: 0, outflow: 0, net: 0 },
            investing: { inflow: 0, outflow: 0, net: 0 },
            financing: { inflow: 0, outflow: 0, net: 0 },
            summary: { opening_balance: 0, net_change: 0, closing_balance: 0 },
            monthly_trend: [],
        }),
    },
});

const flowCategories = [
    { key: 'operating', name: 'Operatsion faoliyat', icon: BanknotesIcon, color: 'blue' },
    { key: 'investing', name: 'Investitsiya faoliyati', icon: BuildingOfficeIcon, color: 'purple' },
    { key: 'financing', name: 'Moliyaviy faoliyat', icon: CreditCardIcon, color: 'orange' },
];
</script>

<template>
    <FinanceLayout title="Pul Oqimi">
        <Head title="Pul Oqimi Hisoboti" />

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
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Pul Oqimi Hisoboti</h1>
                        <p class="mt-1 text-gray-500 dark:text-gray-400">Kiruvchi va chiquvchi pul oqimlari</p>
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
                    <p class="text-sm text-gray-500 dark:text-gray-400">Boshlang'ich Balans</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ formatFullCurrency(data.summary?.opening_balance) }}
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sof O'zgarish</p>
                    <p class="text-2xl font-bold mt-1" :class="data.summary?.net_change >= 0 ? 'text-green-600' : 'text-red-600'">
                        {{ data.summary?.net_change >= 0 ? '+' : '' }}{{ formatFullCurrency(data.summary?.net_change) }}
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Yakuniy Balans</p>
                    <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">
                        {{ formatFullCurrency(data.summary?.closing_balance) }}
                    </p>
                </div>
            </div>

            <!-- Cash Flow Categories -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div
                    v-for="category in flowCategories"
                    :key="category.key"
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700"
                >
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-lg flex items-center justify-center"
                            :class="{
                                'bg-blue-100 dark:bg-blue-900/30': category.color === 'blue',
                                'bg-purple-100 dark:bg-purple-900/30': category.color === 'purple',
                                'bg-orange-100 dark:bg-orange-900/30': category.color === 'orange',
                            }"
                        >
                            <component
                                :is="category.icon"
                                class="w-5 h-5"
                                :class="{
                                    'text-blue-600 dark:text-blue-400': category.color === 'blue',
                                    'text-purple-600 dark:text-purple-400': category.color === 'purple',
                                    'text-orange-600 dark:text-orange-400': category.color === 'orange',
                                }"
                            />
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ category.name }}</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <ArrowDownIcon class="w-4 h-4 text-green-500" />
                                <span class="text-gray-600 dark:text-gray-400">Kiruvchi</span>
                            </div>
                            <span class="font-medium text-green-600">
                                {{ formatFullCurrency(data[category.key]?.inflow) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <ArrowUpIcon class="w-4 h-4 text-red-500" />
                                <span class="text-gray-600 dark:text-gray-400">Chiquvchi</span>
                            </div>
                            <span class="font-medium text-red-600">
                                {{ formatFullCurrency(data[category.key]?.outflow) }}
                            </span>
                        </div>
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <span class="font-semibold text-gray-900 dark:text-white">Sof</span>
                            <span
                                class="font-bold"
                                :class="data[category.key]?.net >= 0 ? 'text-green-600' : 'text-red-600'"
                            >
                                {{ data[category.key]?.net >= 0 ? '+' : '' }}{{ formatFullCurrency(data[category.key]?.net) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Trend -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Oylik Trend</h3>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-sm text-gray-500 dark:text-gray-400">
                                    <th class="pb-3 font-medium">Oy</th>
                                    <th class="pb-3 font-medium text-right">Kiruvchi</th>
                                    <th class="pb-3 font-medium text-right">Chiquvchi</th>
                                    <th class="pb-3 font-medium text-right">Sof</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="month in data.monthly_trend" :key="month.month">
                                    <td class="py-3 text-gray-900 dark:text-white font-medium">{{ month.month }}</td>
                                    <td class="py-3 text-right text-green-600">{{ formatFullCurrency(month.inflow) }}</td>
                                    <td class="py-3 text-right text-red-600">{{ formatFullCurrency(month.outflow) }}</td>
                                    <td class="py-3 text-right font-medium" :class="month.inflow - month.outflow >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ formatFullCurrency(month.inflow - month.outflow) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </FinanceLayout>
</template>
