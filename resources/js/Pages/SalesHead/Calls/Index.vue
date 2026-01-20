<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { useI18n } from '@/i18n';
import {
    PhoneIcon,
    PhoneArrowUpRightIcon,
    PhoneArrowDownLeftIcon,
    PhoneXMarkIcon,
    MagnifyingGlassIcon,
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
});

const periods = [
    { key: 'daily', label: t('common.daily') },
    { key: 'weekly', label: t('common.weekly') },
    { key: 'monthly', label: t('common.monthly') },
];

const searchQuery = ref('');
const typeFilter = ref('');
const selectedDay = ref(props.dateInfo?.selected_day || props.dateInfo?.current_day || 1);

const filteredCalls = computed(() => {
    let result = props.calls;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(c => c.lead?.name?.toLowerCase().includes(q) || c.phone?.includes(q));
    }
    if (typeFilter.value) {
        result = result.filter(c => c.type === typeFilter.value);
    }
    return result;
});

const handlePeriodChange = (periodKey) => {
    router.get('/sales-head/calls', { period: periodKey }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleDayChange = (day) => {
    selectedDay.value = day;
    router.get('/sales-head/calls', { period: 'daily', day: day }, {
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

const formatDate = (date) => {
    if (!date) return '-';
    const d = new Date(date);
    return d.toLocaleDateString('uz-UZ') + ' ' + d.toLocaleTimeString('uz-UZ', { hour: '2-digit', minute: '2-digit' });
};

const getCallIcon = (type) => {
    const icons = {
        incoming: PhoneArrowDownLeftIcon,
        outgoing: PhoneArrowUpRightIcon,
        missed: PhoneXMarkIcon,
    };
    return icons[type] || PhoneIcon;
};

const getCallColor = (type) => {
    const colors = {
        incoming: 'text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30',
        outgoing: 'text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30',
        missed: 'text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30',
    };
    return colors[type] || 'text-gray-600 bg-gray-100';
};

const getCallLabel = (type) => {
    const labels = { incoming: t('calls.incoming'), outgoing: t('calls.outgoing'), missed: t('calls.missed') };
    return labels[type] || type;
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
        return 'bg-blue-600 text-white border-blue-600';
    }
    if (hasCalls) {
        return 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-300 dark:border-blue-700 hover:bg-blue-200 dark:hover:bg-blue-900/50';
    }
    if (isToday) {
        return 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700';
    }
    return 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700';
};
</script>

<template>
    <SalesHeadLayout :title="t('nav.calls')">
        <Head :title="t('nav.calls')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('calls.analytics') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('calls.analytics_subtitle') }}</p>
                </div>
                <div class="flex gap-2">
                    <button
                        v-for="p in periods"
                        :key="p.key"
                        @click="handlePeriodChange(p.key)"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition-all',
                            period === p.key
                                ? 'bg-blue-600 text-white shadow-md'
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
                        ]"
                    >
                        {{ p.label }}
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <PhoneIcon class="w-5 h-5 text-gray-500" />
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('common.total') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <PhoneArrowDownLeftIcon class="w-5 h-5 text-green-500" />
                    </div>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.incoming }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('calls.incoming') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <PhoneArrowUpRightIcon class="w-5 h-5 text-blue-500" />
                    </div>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.outgoing }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('calls.outgoing') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <CheckCircleIcon class="w-5 h-5 text-emerald-500" />
                    </div>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.answered }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('calls.answered') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <XCircleIcon class="w-5 h-5 text-red-500" />
                    </div>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.missed }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('calls.missed') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <ChartBarIcon class="w-5 h-5 text-purple-500" />
                    </div>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ stats.answer_rate }}%</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('calls.answer_rate') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <ClockIcon class="w-5 h-5 text-orange-500" />
                    </div>
                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ formatDuration(stats.avg_duration) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('common.average') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <ClockIcon class="w-5 h-5 text-teal-500" />
                    </div>
                    <p class="text-2xl font-bold text-teal-600 dark:text-teal-400">{{ formatTotalDuration(stats.total_duration) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('calls.total_time') }}</p>
                </div>
            </div>

            <!-- Daily View with Day Selector -->
            <div v-if="period === 'daily'" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ t('common.daily') }} {{ t('saleshead.monitoring') }}</h3>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ dateInfo.current_month }}</span>
                    </div>
                </div>

                <!-- Day buttons -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="day in dateInfo.days_in_month"
                            :key="day"
                            @click="handleDayChange(day)"
                            class="relative px-3 py-2 rounded-lg text-sm font-medium transition-all border"
                            :class="getDayButtonClass(day)"
                            :disabled="day > dateInfo.current_day"
                        >
                            {{ day }}
                            <span
                                v-if="dailyBreakdown[day]?.total > 0 && day !== selectedDay"
                                class="absolute -top-1 -right-1 w-4 h-4 bg-blue-500 text-white text-xs rounded-full flex items-center justify-center"
                            >
                                {{ dailyBreakdown[day].total > 9 ? '9+' : dailyBreakdown[day].total }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Selected day stats -->
                <div v-if="dailyBreakdown[selectedDay]" class="px-6 py-4 bg-blue-50 dark:bg-blue-900/20 border-b border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-3 md:grid-cols-6 gap-4 text-center">
                        <div>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ dailyBreakdown[selectedDay].total }}</p>
                            <p class="text-xs text-gray-500">{{ t('common.total') }}</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-green-600">{{ dailyBreakdown[selectedDay].incoming }}</p>
                            <p class="text-xs text-gray-500">{{ t('calls.incoming') }}</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-blue-600">{{ dailyBreakdown[selectedDay].outgoing }}</p>
                            <p class="text-xs text-gray-500">{{ t('calls.outgoing') }}</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-emerald-600">{{ dailyBreakdown[selectedDay].answered }}</p>
                            <p class="text-xs text-gray-500">{{ t('calls.answered') }}</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-red-600">{{ dailyBreakdown[selectedDay].missed }}</p>
                            <p class="text-xs text-gray-500">{{ t('calls.missed') }}</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-orange-600">{{ formatDuration(dailyBreakdown[selectedDay].avg_duration) }}</p>
                            <p class="text-xs text-gray-500">{{ t('common.average') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operator Performance -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <UserGroupIcon class="w-5 h-5 text-blue-600" />
                        {{ t('saleshead.operator_efficiency') }}
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('saleshead.operator') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('common.total') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('calls.incoming') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('calls.outgoing') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('calls.answered') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('calls.missed') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('calls.answer_rate') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('common.average') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('calls.total_time') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="op in operatorStats" :key="op.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-700 dark:text-blue-400 font-medium text-sm">
                                            {{ op.avatar }}
                                        </div>
                                        <span class="ml-3 font-medium text-gray-900 dark:text-white">{{ op.name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center font-bold text-gray-900 dark:text-white">{{ op.total_calls }}</td>
                                <td class="px-4 py-4 text-center text-green-600 dark:text-green-400">{{ op.incoming }}</td>
                                <td class="px-4 py-4 text-center text-blue-600 dark:text-blue-400">{{ op.outgoing }}</td>
                                <td class="px-4 py-4 text-center text-emerald-600 dark:text-emerald-400">{{ op.answered }}</td>
                                <td class="px-4 py-4 text-center text-red-600 dark:text-red-400">{{ op.missed }}</td>
                                <td class="px-4 py-4 text-center">
                                    <span :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        op.answer_rate >= 80 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' :
                                        op.answer_rate >= 60 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' :
                                        'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                    ]">
                                        {{ op.answer_rate }}%
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center text-orange-600 dark:text-orange-400">{{ formatDuration(op.avg_duration) }}</td>
                                <td class="px-4 py-4 text-right text-gray-700 dark:text-gray-300">{{ formatTotalDuration(op.total_duration) }}</td>
                            </tr>
                            <tr v-if="operatorStats.length === 0">
                                <td colspan="9" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    {{ t('saleshead.no_operator_data') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="relative flex-1">
                    <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        :placeholder="t('common.search')"
                        class="w-full pl-10 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white"
                    />
                </div>
                <select
                    v-model="typeFilter"
                    class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white"
                >
                    <option value="">{{ t('calls.all_types') }}</option>
                    <option value="incoming">{{ t('calls.incoming') }}</option>
                    <option value="outgoing">{{ t('calls.outgoing') }}</option>
                    <option value="missed">{{ t('calls.missed') }}</option>
                </select>
            </div>

            <!-- Calls List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ t('calls.history') }}</h3>
                </div>

                <div v-if="filteredCalls.length === 0" class="p-12 text-center">
                    <PhoneIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ t('calls.no_calls_found') }}</h3>
                    <p class="text-gray-500 dark:text-gray-400">{{ t('calls.no_calls_for_period') }}</p>
                </div>

                <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="call in filteredCalls" :key="call.id" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <div class="flex items-center gap-4">
                            <div :class="[getCallColor(call.type), 'w-10 h-10 rounded-full flex items-center justify-center']">
                                <component :is="getCallIcon(call.type)" class="w-5 h-5" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ call.lead?.name || call.phone }}</p>
                                    <span :class="[getCallColor(call.type), 'px-2 py-0.5 rounded-full text-xs font-medium']">
                                        {{ getCallLabel(call.type) }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ call.phone }}</p>
                            </div>
                            <div class="text-right">
                                <p class="flex items-center gap-1 text-sm text-gray-900 dark:text-white">
                                    <ClockIcon class="w-4 h-4" />
                                    {{ formatDuration(call.duration) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(call.created_at) }}</p>
                            </div>
                            <div v-if="call.operator" class="text-sm text-gray-600 dark:text-gray-400 min-w-[100px] text-right">
                                {{ call.operator.name }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SalesHeadLayout>
</template>
