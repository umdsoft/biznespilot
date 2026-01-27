<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
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
    FireIcon,
    SparklesIcon,
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
    router.get('/business/calls', { period: periodKey }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleDayChange = (day) => {
    selectedDay.value = day;
    router.get('/business/calls', { period: 'daily', day: day }, {
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

const handleTabChange = (tabKey) => {
    router.get('/business/calls', { tab: tabKey }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const analyzingCalls = ref(new Set());

const handleAnalyzeCall = async (callId) => {
    if (analyzingCalls.value.has(callId)) return;

    analyzingCalls.value.add(callId);
    try {
        // TODO: Implement AI analysis API call
        await new Promise(resolve => setTimeout(resolve, 2000)); // Mock delay
        console.log('Analyzing call:', callId);
        // After successful analysis, refresh the page
        router.reload({ preserveScroll: true });
    } catch (err) {
        console.error('Failed to analyze call:', err);
    } finally {
        analyzingCalls.value.delete(callId);
    }
};
</script>

<template>
    <BusinessLayout :title="t('nav.calls')">
        <Head :title="t('nav.calls')" />

        <div class="space-y-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('calls.analytics') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('calls.analytics_subtitle') }}</p>
                </div>
                <div v-if="tab === 'all'" class="flex gap-2">
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

            <!-- All Tab Content -->
            <div v-if="tab === 'all'" class="space-y-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-5">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <PhoneIcon class="w-5 h-5 text-gray-500" />
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Jami</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <PhoneArrowDownLeftIcon class="w-5 h-5 text-green-500" />
                    </div>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.incoming }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Kiruvchi</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <PhoneArrowUpRightIcon class="w-5 h-5 text-blue-500" />
                    </div>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.outgoing }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Chiquvchi</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <CheckCircleIcon class="w-5 h-5 text-emerald-500" />
                    </div>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.answered }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Javob berildi</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <XCircleIcon class="w-5 h-5 text-red-500" />
                    </div>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.missed }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Javob yo'q</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <ChartBarIcon class="w-5 h-5 text-purple-500" />
                    </div>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ stats.answer_rate }}%</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Javob foizi</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <ClockIcon class="w-5 h-5 text-orange-500" />
                    </div>
                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ formatDuration(stats.avg_duration) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">O'rtacha</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <ClockIcon class="w-5 h-5 text-teal-500" />
                    </div>
                    <p class="text-2xl font-bold text-teal-600 dark:text-teal-400">{{ formatTotalDuration(stats.total_duration) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Umumiy vaqt</p>
                </div>
            </div>

            <!-- Daily View with Day Selector -->
            <div v-if="period === 'daily'" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Kunlik Monitoring</h3>
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
                            <div class="flex flex-col items-center">
                                <span>{{ day }}</span>
                                <span v-if="dailyBreakdown[day]?.total > 0" class="text-xs opacity-75 mt-0.5">
                                    {{ dailyBreakdown[day].total }}
                                </span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Selected day stats -->
                <div v-if="dailyBreakdown[selectedDay]" class="px-6 py-4 bg-blue-50 dark:bg-blue-900/20 border-b border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-3 md:grid-cols-6 gap-4 text-center">
                        <div>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ dailyBreakdown[selectedDay].total }}</p>
                            <p class="text-xs text-gray-500">Jami</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-green-600">{{ dailyBreakdown[selectedDay].incoming }}</p>
                            <p class="text-xs text-gray-500">Kiruvchi</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-blue-600">{{ dailyBreakdown[selectedDay].outgoing }}</p>
                            <p class="text-xs text-gray-500">Chiquvchi</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-emerald-600">{{ dailyBreakdown[selectedDay].answered }}</p>
                            <p class="text-xs text-gray-500">Javob berildi</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-red-600">{{ dailyBreakdown[selectedDay].missed }}</p>
                            <p class="text-xs text-gray-500">Javob yo'q</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-orange-600">{{ formatDuration(dailyBreakdown[selectedDay].avg_duration) }}</p>
                            <p class="text-xs text-gray-500">O'rtacha</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operator Performance & Calls List -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Operator Performance -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <UserGroupIcon class="w-5 h-5 text-blue-600" />
                                Operatorlar samaradorligi
                            </h3>
                            <span v-if="period === 'daily'" class="text-sm text-gray-500 dark:text-gray-400">
                                {{ selectedDay }}-kun
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Operator</th>
                                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Jami</th>
                                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Javob</th>
                                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">%</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="op in operatorStats" :key="op.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-700 dark:text-blue-400 font-medium text-xs">
                                                {{ op.avatar }}
                                            </div>
                                            <span class="font-medium text-gray-900 dark:text-white text-sm">{{ op.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-2 py-3 text-center font-bold text-gray-900 dark:text-white">{{ op.total_calls }}</td>
                                    <td class="px-2 py-3 text-center text-emerald-600 dark:text-emerald-400">{{ op.answered }}</td>
                                    <td class="px-2 py-3 text-center">
                                        <span :class="[
                                            'px-2 py-1 rounded-full text-xs font-medium',
                                            op.answer_rate >= 80 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' :
                                            op.answer_rate >= 60 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' :
                                            'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                        ]">
                                            {{ op.answer_rate }}%
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="operatorStats.length === 0">
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Ma'lumot yo'q
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Calls List -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Qo'ng'iroqlar ro'yxati</h3>
                            <span v-if="period === 'daily'" class="text-sm text-gray-500 dark:text-gray-400">
                                {{ selectedDay }}-kun
                            </span>
                        </div>
                    </div>

                    <div v-if="calls.length === 0" class="p-12 text-center">
                        <PhoneIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Qo'ng'iroqlar yo'q</h3>
                        <p class="text-gray-500 dark:text-gray-400">Bu kun uchun hech qanday qo'ng'iroq topilmadi</p>
                    </div>

                    <div v-else class="divide-y divide-gray-200 dark:divide-gray-700 max-h-[600px] overflow-y-auto">
                        <div v-for="call in calls" :key="call.id" class="p-3 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <div class="flex items-start gap-3">
                                <div :class="[getCallColor(call.type), 'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0']">
                                    <component :is="getCallIcon(call.type)" class="w-4 h-4" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ call.lead?.name || call.phone }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ call.phone }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span :class="[getCallColor(call.type), 'px-2 py-0.5 rounded-full text-xs font-medium']">
                                            {{ getCallLabel(call.type) }}
                                        </span>
                                        <span class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400">
                                            <ClockIcon class="w-3 h-3" />
                                            {{ formatDuration(call.duration) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ formatDate(call.created_at) }}</p>
                                    <p v-if="call.operator" class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">{{ call.operator.name }}</p>

                                    <!-- Audio Player -->
                                    <div v-if="call.recording_url" class="mt-2">
                                        <audio controls class="w-full h-8" style="max-height: 32px;">
                                            <source :src="call.recording_url" type="audio/mpeg">
                                            Brauzer audio formatini qo'llab-quvvatlamaydi.
                                        </audio>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>

            <!-- Audit Tab Content -->
            <div v-if="tab === 'audit' && canViewAudit">
                <div v-if="calls.length === 0" class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl border-2 border-green-200 dark:border-green-800 p-12 text-center">
                    <div class="flex justify-center mb-4">
                        <div class="w-20 h-20 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center">
                            <CheckCircleIcon class="w-12 h-12 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-green-900 dark:text-green-100 mb-2">Ajoyib!</h3>
                    <p class="text-green-700 dark:text-green-300">Bugun shubhali qo'ng'iroqlar yo'q</p>
                </div>

                <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <FireIcon class="w-5 h-5 text-orange-600" />
                            Diqqat talab etadigan qo'ng'iroqlar
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            AI tahlil uchun tavsiya etilgan qo'ng'iroqlar
                        </p>
                    </div>

                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div v-for="call in calls" :key="call.id" class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <div class="flex items-start gap-4">
                                <div :class="[getCallColor(call.type), 'w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0']">
                                    <component :is="getCallIcon(call.type)" class="w-6 h-6" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-2">
                                        <p class="font-semibold text-gray-900 dark:text-white text-lg">{{ call.lead?.name || call.phone }}</p>
                                        <span :class="[getCallColor(call.type), 'px-2 py-0.5 rounded-full text-xs font-medium']">
                                            {{ getCallLabel(call.type) }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ call.phone }}</p>

                                    <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-3 mb-3">
                                        <p class="text-sm font-medium text-orange-900 dark:text-orange-100 mb-1">Sabab:</p>
                                        <p class="text-sm text-orange-800 dark:text-orange-200">{{ call.recommended_reason || 'Tahlil talab qilinadi' }}</p>
                                    </div>

                                    <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                        <div class="flex items-center gap-1">
                                            <ClockIcon class="w-4 h-4" />
                                            {{ call.formatted_duration || formatDuration(call.duration) }}
                                        </div>
                                        <div>{{ formatDate(call.created_at) }}</div>
                                        <div v-if="call.operator">Operator: {{ call.operator.name }}</div>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2 flex-shrink-0">
                                    <button
                                        @click="handleAnalyzeCall(call.id)"
                                        :disabled="analyzingCalls.has(call.id)"
                                        :class="[
                                            'px-4 py-2 rounded-lg font-medium transition-all flex items-center gap-2',
                                            analyzingCalls.has(call.id)
                                                ? 'bg-gray-300 dark:bg-gray-700 text-gray-500 cursor-not-allowed'
                                                : 'bg-gradient-to-r from-purple-600 to-blue-600 text-white hover:from-purple-700 hover:to-blue-700 shadow-md hover:shadow-lg'
                                        ]"
                                    >
                                        <SparklesIcon class="w-5 h-5" />
                                        <span v-if="analyzingCalls.has(call.id)">Tahlil qilinmoqda...</span>
                                        <span v-else>AI Tahlil</span>
                                    </button>
                                    <a
                                        v-if="call.recording_url"
                                        :href="call.recording_url"
                                        target="_blank"
                                        class="px-4 py-2 rounded-lg font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-center"
                                    >
                                        Yozuvni tinglash
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
