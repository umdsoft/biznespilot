<script setup>
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatFullCurrency, formatPercent } from '@/utils/formatting';
import {
    ArrowLeftIcon,
    DocumentArrowDownIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({
            summary: {
                total: 0,
                current: 0,
                overdue_1_30: 0,
                overdue_31_60: 0,
                overdue_60_plus: 0,
            },
            clients: [],
        }),
    },
});

const agingCategories = computed(() => [
    { label: 'Joriy', amount: props.data.summary?.current || 0, color: 'green' },
    { label: '1-30 kun', amount: props.data.summary?.overdue_1_30 || 0, color: 'yellow' },
    { label: '31-60 kun', amount: props.data.summary?.overdue_31_60 || 0, color: 'orange' },
    { label: '60+ kun', amount: props.data.summary?.overdue_60_plus || 0, color: 'red' },
]);

const getStatusColor = (daysOverdue) => {
    if (daysOverdue === 0) return 'green';
    if (daysOverdue <= 30) return 'yellow';
    if (daysOverdue <= 60) return 'orange';
    return 'red';
};

const getStatusIcon = (daysOverdue) => {
    if (daysOverdue === 0) return CheckCircleIcon;
    if (daysOverdue <= 30) return ClockIcon;
    return ExclamationTriangleIcon;
};

const getStatusText = (daysOverdue) => {
    if (daysOverdue === 0) return 'Joriy';
    return `${daysOverdue} kun kechikkan`;
};
</script>

<template>
    <FinanceLayout title="Debitorlik Qarzi">
        <Head title="Debitorlik Qarzi Hisoboti" />

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
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Debitorlik Qarzi</h1>
                        <p class="mt-1 text-gray-500 dark:text-gray-400">Mijozlardan olinadigan to'lovlar</p>
                    </div>
                </div>
                <button
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors"
                >
                    <DocumentArrowDownIcon class="w-5 h-5" />
                    Yuklab olish
                </button>
            </div>

            <!-- Total Summary -->
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <p class="text-blue-100">Jami Debitorlik Qarzi</p>
                        <p class="text-3xl font-bold mt-1">{{ formatFullCurrency(data.summary?.total) }}</p>
                    </div>
                    <div class="flex items-center gap-2 bg-white/20 rounded-lg px-4 py-2">
                        <ExclamationTriangleIcon class="w-5 h-5" />
                        <span>{{ data.clients?.filter(c => c.days_overdue > 0).length || 0 }} ta kechikkan</span>
                    </div>
                </div>
            </div>

            <!-- Aging Summary -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div
                    v-for="category in agingCategories"
                    :key="category.label"
                    class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700"
                >
                    <div class="flex items-center gap-2 mb-2">
                        <div
                            class="w-3 h-3 rounded-full"
                            :class="{
                                'bg-green-500': category.color === 'green',
                                'bg-yellow-500': category.color === 'yellow',
                                'bg-orange-500': category.color === 'orange',
                                'bg-red-500': category.color === 'red',
                            }"
                        ></div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ category.label }}</span>
                    </div>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ formatFullCurrency(category.amount) }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ data.summary?.total > 0 ? formatPercent((category.amount / data.summary.total) * 100) : '0%' }}
                    </p>
                </div>
            </div>

            <!-- Clients Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Mijozlar Bo'yicha</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr class="text-left text-sm text-gray-500 dark:text-gray-400">
                                <th class="px-6 py-3 font-medium">Mijoz</th>
                                <th class="px-6 py-3 font-medium text-right">Summa</th>
                                <th class="px-6 py-3 font-medium text-center">Holat</th>
                                <th class="px-6 py-3 font-medium text-right">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="client in data.clients" :key="client.name" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ client.name }}</span>
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-gray-900 dark:text-white">
                                    {{ formatFullCurrency(client.amount) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': getStatusColor(client.days_overdue) === 'green',
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': getStatusColor(client.days_overdue) === 'yellow',
                                            'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400': getStatusColor(client.days_overdue) === 'orange',
                                            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': getStatusColor(client.days_overdue) === 'red',
                                        }"
                                    >
                                        <component :is="getStatusIcon(client.days_overdue)" class="w-3 h-3" />
                                        {{ getStatusText(client.days_overdue) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm">
                                        Eslatma yuborish
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </FinanceLayout>
</template>
