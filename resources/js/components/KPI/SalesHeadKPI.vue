<script setup>
import {
    ChartBarIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    UserGroupIcon,
    CurrencyDollarIcon,
    PhoneIcon,
    ClockIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline';
import TeamPerformanceTable from './TeamPerformanceTable.vue';

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

const kpiMetrics = [
    {
        label: 'Sotuvlar soni',
        value: 156,
        target: 200,
        icon: CurrencyDollarIcon,
        change: 12,
        color: 'emerald',
    },
    {
        label: 'Konversiya',
        value: 24,
        target: 30,
        icon: ArrowTrendingUpIcon,
        change: 5,
        color: 'blue',
        suffix: '%',
    },
    {
        label: "Qo'ng'iroqlar",
        value: 1250,
        target: 1500,
        icon: PhoneIcon,
        change: -3,
        color: 'purple',
    },
    {
        label: "O'rtacha javob vaqti",
        value: 45,
        target: 30,
        icon: ClockIcon,
        change: -8,
        color: 'orange',
        suffix: ' sek',
    },
    {
        label: 'Yopilgan leadlar',
        value: 89,
        target: 100,
        icon: CheckCircleIcon,
        change: 15,
        color: 'green',
    },
    {
        label: 'Faol mijozlar',
        value: 342,
        target: 400,
        icon: UserGroupIcon,
        change: 8,
        color: 'indigo',
    },
];

const teamPerformance = [
    { name: 'Abdulloh', sales: 45, calls: 320, conversion: 28, target: 50, avatar: 'A' },
    { name: 'Bekzod', sales: 38, calls: 280, conversion: 24, target: 50, avatar: 'B' },
    { name: 'Sardor', sales: 42, calls: 350, conversion: 26, target: 50, avatar: 'S' },
    { name: 'Dilshod', sales: 31, calls: 220, conversion: 22, target: 50, avatar: 'D' },
];

const getProgressColor = (value, target) => {
    const percentage = (value / target) * 100;
    if (percentage >= 100) return 'bg-green-500';
    if (percentage >= 75) return 'bg-emerald-500';
    if (percentage >= 50) return 'bg-yellow-500';
    return 'bg-red-500';
};

const getProgressPercentage = (value, target) => {
    return Math.min((value / target) * 100, 100);
};

const periods = [
    { key: 'daily', label: 'Kunlik' },
    { key: 'weekly', label: 'Haftalik' },
    { key: 'monthly', label: 'Oylik' },
];
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
                    @click="emit('period-change', p.key)"
                >
                    {{ p.label }}
                </button>
            </div>
        </div>

        <!-- KPI Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="(metric, index) in kpiMetrics"
                :key="index"
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-lg transition-all"
            >
                <div class="flex items-start justify-between">
                    <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                        <component :is="metric.icon" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <span
                        :class="[
                            'inline-flex items-center text-xs font-semibold px-2 py-1 rounded-full',
                            metric.change >= 0
                                ? 'text-green-700 bg-green-100 dark:text-green-400 dark:bg-green-900/30'
                                : 'text-red-700 bg-red-100 dark:text-red-400 dark:bg-red-900/30'
                        ]"
                    >
                        <component
                            :is="metric.change >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon"
                            class="w-3 h-3 mr-1"
                        />
                        {{ Math.abs(metric.change) }}%
                    </span>
                </div>
                <div class="mt-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ metric.label }}</p>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ metric.value }}{{ metric.suffix || '' }}
                        </span>
                        <span class="text-sm text-gray-400">/ {{ metric.target }}{{ metric.suffix || '' }}</span>
                    </div>
                </div>
                <!-- Progress bar -->
                <div class="mt-4">
                    <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div
                            :class="getProgressColor(metric.value, metric.target)"
                            class="h-full rounded-full transition-all duration-500"
                            :style="{ width: getProgressPercentage(metric.value, metric.target) + '%' }"
                        ></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-right">
                        {{ Math.round(getProgressPercentage(metric.value, metric.target)) }}% bajarildi
                    </p>
                </div>
            </div>
        </div>

        <!-- Team Performance -->
        <TeamPerformanceTable :members="teamPerformance" />

        <!-- Coming Soon Notice -->
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
