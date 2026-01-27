<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import CallList from '@/components/CallCenter/CallList.vue';
import { useI18n } from '@/i18n';
import {
    PhoneIcon,
    PhoneArrowUpRightIcon,
    PhoneArrowDownLeftIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    UserGroupIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    calls: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            incoming: 0,
            outgoing: 0,
            answered: 0,
            missed: 0,
            avg_duration: 0,
            total_duration: 0,
            answer_rate: 0,
        }),
    },
    dailyBreakdown: {
        type: Object,
        default: () => ({}),
    },
    operatorStats: {
        type: Array,
        default: () => [],
    },
    period: {
        type: String,
        default: 'daily',
    },
    dateInfo: {
        type: Object,
        default: () => ({
            current_month: '',
            current_day: 1,
            days_in_month: 31,
            selected_day: 1,
        }),
    },
    tab: {
        type: String,
        default: 'all',
    },
    auditCount: {
        type: Number,
        default: 0,
    },
    canViewAudit: {
        type: Boolean,
        default: false,
    },
});

const periods = [
    { key: 'daily', label: t('common.daily') },
    { key: 'weekly', label: t('common.weekly') },
    { key: 'monthly', label: t('common.monthly') },
];

const selectedDay = ref(props.dateInfo?.selected_day || props.dateInfo?.current_day || 1);

const handlePeriodChange = (periodKey) => {
    router.get('/sales-head/calls', { period: periodKey, tab: props.tab }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleDayChange = (day) => {
    selectedDay.value = day;
    router.get('/sales-head/calls', { period: 'daily', day: day, tab: props.tab }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatDuration = (seconds) => {
    if (!seconds) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const formatTotalDuration = (seconds) => {
    if (!seconds) return '0:00:00';
    const hours = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    return `${hours}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

const getDayButtonClass = (day) => {
    const isToday = day === props.dateInfo?.current_day;
    const isSelected = day === selectedDay.value;
    const isFuture = day > props.dateInfo?.current_day;
    const hasCalls = props.dailyBreakdown[day]?.total > 0;

    if (isFuture) {
        return 'bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600 border-gray-200 dark:border-gray-700 cursor-not-allowed';
    }
    if (isSelected) {
        return 'bg-indigo-600 text-white border-indigo-600 shadow-lg ring-2 ring-indigo-200';
    }
    if (hasCalls) {
        return 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-700 hover:bg-indigo-100 dark:hover:bg-indigo-900/50';
    }
    if (isToday) {
        return 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-700 ring-2 ring-emerald-100';
    }
    return 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700';
};
</script>

<template>
    <SalesHeadLayout :title="t('nav.calls')">
        <Head :title="t('nav.calls')" />

        <div class="space-y-8">
            <!-- Modern Header with Gradient -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 p-8 shadow-xl">
                <div class="absolute inset-0 bg-grid-white/10 [mask-image:radial-gradient(white,transparent_70%)]"></div>
                <div class="relative">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-white mb-2">
                                {{ t('calls.analytics') }}
                            </h1>
                            <p class="text-indigo-100">
                                {{ t('calls.analytics_subtitle') }}
                            </p>
                        </div>
                        <div v-if="tab === 'all'" class="flex gap-2">
                            <button
                                v-for="p in periods"
                                :key="p.key"
                                @click="handlePeriodChange(p.key)"
                                :class="[
                                    'px-5 py-2.5 rounded-xl font-semibold transition-all',
                                    period === p.key
                                        ? 'bg-white text-indigo-600 shadow-lg'
                                        : 'bg-white/10 text-white hover:bg-white/20 backdrop-blur-sm'
                                ]"
                            >
                                {{ p.label }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards with Modern Design (Overview Tab Only) -->
            <div v-if="tab === 'all'" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-6">
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 p-6 shadow-lg hover:shadow-xl transition-shadow">
                    <div class="relative z-10">
                        <PhoneIcon class="w-8 h-8 text-white/80 mb-3" />
                        <p class="text-4xl font-bold text-white mb-1">{{ stats.total }}</p>
                        <p class="text-sm text-blue-100">{{ t('common.total') }}</p>
                    </div>
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16"></div>
                </div>

                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 p-6 shadow-lg hover:shadow-xl transition-shadow">
                    <div class="relative z-10">
                        <CheckCircleIcon class="w-8 h-8 text-white/80 mb-3" />
                        <p class="text-4xl font-bold text-white mb-1">{{ stats.answered }}</p>
                        <p class="text-sm text-emerald-100">{{ t('calls.answered') }}</p>
                    </div>
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16"></div>
                </div>

                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-rose-500 to-rose-600 p-6 shadow-lg hover:shadow-xl transition-shadow">
                    <div class="relative z-10">
                        <XCircleIcon class="w-8 h-8 text-white/80 mb-3" />
                        <p class="text-4xl font-bold text-white mb-1">{{ stats.missed }}</p>
                        <p class="text-sm text-rose-100">{{ t('calls.missed') }}</p>
                    </div>
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16"></div>
                </div>

                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 p-6 shadow-lg hover:shadow-xl transition-shadow">
                    <div class="relative z-10">
                        <ChartBarIcon class="w-8 h-8 text-white/80 mb-3" />
                        <p class="text-4xl font-bold text-white mb-1">{{ stats.answer_rate }}%</p>
                        <p class="text-sm text-purple-100">{{ t('calls.answer_rate') }}</p>
                    </div>
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16"></div>
                </div>
            </div>

            <!-- Daily Calendar (Overview + Daily Period) -->
            <div v-if="tab === 'all' && period === 'daily'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ t('common.daily') }} {{ t('saleshead.monitoring') }}
                        </h3>
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            {{ dateInfo.current_month }}
                        </span>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-7 sm:grid-cols-10 md:grid-cols-15 gap-2">
                        <button
                            v-for="day in dateInfo.days_in_month"
                            :key="day"
                            @click="handleDayChange(day)"
                            :class="[
                                'relative p-3 rounded-xl text-sm font-semibold transition-all border-2',
                                getDayButtonClass(day)
                            ]"
                            :disabled="day > dateInfo.current_day"
                        >
                            {{ day }}
                            <span
                                v-if="dailyBreakdown[day]?.total > 0 && day !== selectedDay"
                                class="absolute -top-1 -right-1 w-5 h-5 bg-indigo-500 text-white text-xs rounded-full flex items-center justify-center font-bold shadow-lg"
                            >
                                {{ dailyBreakdown[day].total > 9 ? '9+' : dailyBreakdown[day].total }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Operator Performance (Overview Tab Only) -->
            <div v-if="tab === 'all' && operatorStats.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <UserGroupIcon class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                        {{ t('saleshead.operator_efficiency') }}
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ t('saleshead.operator') }}</th>
                                <th class="px-4 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ t('common.total') }}</th>
                                <th class="px-4 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ t('calls.answered') }}</th>
                                <th class="px-4 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ t('calls.answer_rate') }}</th>
                                <th class="px-4 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ t('common.average') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="op in operatorStats" :key="op.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold shadow-md">
                                            {{ op.avatar }}
                                        </div>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ op.name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center font-bold text-gray-900 dark:text-white">{{ op.total_calls }}</td>
                                <td class="px-4 py-4 text-center text-emerald-600 dark:text-emerald-400 font-semibold">{{ op.answered }}</td>
                                <td class="px-4 py-4 text-center">
                                    <span :class="[
                                        'px-3 py-1 rounded-full text-sm font-bold',
                                        op.answer_rate >= 80 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' :
                                        op.answer_rate >= 60 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' :
                                        'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                                    ]">
                                        {{ op.answer_rate }}%
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center text-gray-700 dark:text-gray-300 font-mono">{{ formatDuration(op.avg_duration) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modern Call List Component -->
            <CallList
                :calls="calls"
                :active-tab="tab"
                :audit-count="auditCount"
                :can-view-audit="canViewAudit"
                route-prefix="/sales-head/calls"
            />
        </div>
    </SalesHeadLayout>
</template>

<style scoped>
.bg-grid-white\/10 {
    background-image: linear-gradient(to right, rgba(255, 255, 255, 0.1) 1px, transparent 1px),
                      linear-gradient(to bottom, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
    background-size: 20px 20px;
}
</style>
