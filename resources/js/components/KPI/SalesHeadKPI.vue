<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    ChartBarIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    UserGroupIcon,
    CurrencyDollarIcon,
    ClipboardDocumentCheckIcon,
    ExclamationTriangleIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    kpiData: { type: Object, default: () => ({}) },
    teamKpi: { type: Array, default: () => [] },
    period: { type: String, default: 'monthly' },
    panelType: {
        type: String,
        default: 'saleshead',
        validator: (v) => ['saleshead', 'business'].includes(v),
    },
});

const emit = defineEmits(['period-change']);

// Format currency
const formatCurrency = (value) => {
    if (!value) return '0 so\'m';
    if (value >= 1000000000) {
        return (value / 1000000000).toFixed(1) + ' mlrd so\'m';
    }
    if (value >= 1000000) {
        return (value / 1000000).toFixed(1) + ' mln so\'m';
    }
    return new Intl.NumberFormat('uz-UZ').format(value) + ' so\'m';
};

// Build KPI metrics from real data
const kpiMetrics = computed(() => {
    const data = props.kpiData;
    return [
        {
            key: 'sales',
            label: 'Yopilgan sotuvlar',
            value: data.sales?.value ?? 0,
            target: data.sales?.target ?? 50,
            icon: CurrencyDollarIcon,
            change: data.sales?.change ?? 0,
            color: 'emerald',
        },
        {
            key: 'conversion',
            label: 'Konversiya',
            value: data.conversion?.value ?? 0,
            target: data.conversion?.target ?? 30,
            icon: ArrowTrendingUpIcon,
            change: data.conversion?.change ?? 0,
            color: 'blue',
            suffix: '%',
        },
        {
            key: 'revenue',
            label: 'Daromad',
            value: data.revenue?.value ?? 0,
            target: data.revenue?.target ?? 10000000,
            icon: CurrencyDollarIcon,
            change: data.revenue?.change ?? 0,
            color: 'purple',
            isCurrency: true,
        },
        {
            key: 'active_leads',
            label: 'Faol lidlar',
            value: data.active_leads?.value ?? 0,
            target: data.active_leads?.target ?? 100,
            icon: UsersIcon,
            change: data.active_leads?.change ?? 0,
            color: 'indigo',
        },
        {
            key: 'completed_tasks',
            label: 'Bajarilgan vazifalar',
            value: data.completed_tasks?.value ?? 0,
            target: data.completed_tasks?.target ?? 50,
            icon: ClipboardDocumentCheckIcon,
            change: data.completed_tasks?.change ?? 0,
            color: 'green',
        },
        {
            key: 'overdue_tasks',
            label: 'Kechikkan vazifalar',
            value: data.overdue_tasks?.value ?? 0,
            target: data.overdue_tasks?.target ?? 0,
            icon: ExclamationTriangleIcon,
            change: data.overdue_tasks?.change ?? 0,
            color: 'red',
            isNegative: true, // Lower is better
        },
    ];
});

// Team performance from real data
const teamPerformance = computed(() => {
    return props.teamKpi.map(member => ({
        name: member.name,
        avatar: member.avatar,
        leads: member.leads,
        won: member.won,
        conversion: member.conversion,
        revenue: member.revenue,
    }));
});

const getProgressColor = (value, target, isNegative = false) => {
    if (isNegative) {
        // For negative metrics (like overdue tasks), less is better
        if (value === 0) return 'bg-green-500';
        if (value <= target + 5) return 'bg-yellow-500';
        return 'bg-red-500';
    }
    const percentage = (value / target) * 100;
    if (percentage >= 100) return 'bg-green-500';
    if (percentage >= 75) return 'bg-emerald-500';
    if (percentage >= 50) return 'bg-yellow-500';
    return 'bg-red-500';
};

const getProgressPercentage = (value, target, isNegative = false) => {
    if (isNegative) {
        // For negative metrics, show inverse progress
        if (target === 0) return value === 0 ? 100 : Math.min((value / 10) * 100, 100);
        return Math.max(0, 100 - (value / target) * 100);
    }
    return Math.min((value / target) * 100, 100);
};

const formatValue = (metric) => {
    if (metric.isCurrency) {
        return formatCurrency(metric.value);
    }
    return metric.value + (metric.suffix || '');
};

const formatTarget = (metric) => {
    if (metric.isCurrency) {
        return formatCurrency(metric.target);
    }
    return metric.target + (metric.suffix || '');
};

const periods = [
    { key: 'daily', label: 'Kunlik' },
    { key: 'weekly', label: 'Haftalik' },
    { key: 'monthly', label: 'Oylik' },
];

const handlePeriodChange = (periodKey) => {
    router.get('/sales-head/kpi', { period: periodKey }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <ChartBarIcon class="w-7 h-7 text-emerald-600" />
                    KPI Ko'rsatkichlari
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Sotuv bo'limi samaradorlik ko'rsatkichlari
                </p>
            </div>
            <div class="flex gap-2">
                <button
                    v-for="p in periods"
                    :key="p.key"
                    :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                        period === p.key
                            ? 'bg-emerald-600 text-white shadow-md'
                            : 'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                    ]"
                    @click="handlePeriodChange(p.key)"
                >
                    {{ p.label }}
                </button>
            </div>
        </div>

        <!-- KPI Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="(metric, index) in kpiMetrics"
                :key="metric.key"
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-lg transition-all"
            >
                <div class="flex items-start justify-between">
                    <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                        <component :is="metric.icon" :class="[
                            'w-6 h-6',
                            metric.isNegative && metric.value > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'
                        ]" />
                    </div>
                    <span
                        v-if="metric.change !== 0"
                        :class="[
                            'inline-flex items-center text-xs font-semibold px-2 py-1 rounded-full',
                            (metric.isNegative ? metric.change < 0 : metric.change >= 0)
                                ? 'text-green-700 bg-green-100 dark:text-green-400 dark:bg-green-900/30'
                                : 'text-red-700 bg-red-100 dark:text-red-400 dark:bg-red-900/30'
                        ]"
                    >
                        <component
                            :is="(metric.isNegative ? metric.change < 0 : metric.change >= 0) ? ArrowTrendingUpIcon : ArrowTrendingDownIcon"
                            class="w-3 h-3 mr-1"
                        />
                        {{ Math.abs(metric.change) }}%
                    </span>
                </div>
                <div class="mt-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ metric.label }}</p>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ formatValue(metric) }}
                        </span>
                        <span v-if="!metric.isNegative" class="text-sm text-gray-400">/ {{ formatTarget(metric) }}</span>
                    </div>
                </div>
                <!-- Progress bar -->
                <div class="mt-4">
                    <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div
                            :class="getProgressColor(metric.value, metric.target, metric.isNegative)"
                            class="h-full rounded-full transition-all duration-500"
                            :style="{ width: getProgressPercentage(metric.value, metric.target, metric.isNegative) + '%' }"
                        ></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-right">
                        {{ Math.round(getProgressPercentage(metric.value, metric.target, metric.isNegative)) }}% bajarildi
                    </p>
                </div>
            </div>
        </div>

        <!-- Team Performance -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <UserGroupIcon class="w-5 h-5 text-emerald-600" />
                    Jamoa samaradorligi
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hodim</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Leadlar</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Yutilgan</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">CR%</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Daromad</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="member in teamPerformance" :key="member.name" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-700 dark:text-emerald-400 font-medium text-sm">
                                        {{ member.avatar }}
                                    </div>
                                    <span class="ml-3 font-medium text-gray-900 dark:text-white">{{ member.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-gray-700 dark:text-gray-300">{{ member.leads }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span :class="member.won > 0 ? 'text-emerald-600 dark:text-emerald-400 font-medium' : 'text-gray-500'">{{ member.won }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span :class="[
                                    'font-medium',
                                    member.conversion >= 30 ? 'text-green-600 dark:text-green-400' :
                                    member.conversion >= 15 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'
                                ]">{{ member.conversion }}%</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-gray-900 dark:text-white font-medium">
                                {{ formatCurrency(member.revenue) }}
                            </td>
                        </tr>
                        <tr v-if="teamPerformance.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                Jamoa a'zolari topilmadi
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Info Notice -->
        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800 p-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center flex-shrink-0">
                    <ChartBarIcon class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <h3 class="font-semibold text-emerald-900 dark:text-emerald-100">
                        KPI Tizimi Rivojlanmoqda
                    </h3>
                    <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-300">
                        Tez orada real vaqt rejimida KPI ko'rsatkichlarini kuzatish, maqsadlarni belgilash va
                        jamoa samaradorligini tahlil qilish imkoniyatlari qo'shiladi.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
