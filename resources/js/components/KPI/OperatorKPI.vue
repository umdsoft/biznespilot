<script setup>
import {
    PhoneIcon,
    UserGroupIcon,
    ArrowTrendingUpIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';
import KPICard from './KPICard.vue';
import KPIStatsList from './KPIStatsList.vue';
import KPIWeeklyTable from './KPIWeeklyTable.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    dailyStats: { type: Array, default: () => [] },
    weeklyStats: { type: Array, default: () => [] },
    targets: { type: Object, default: () => ({}) },
    panelType: {
        type: String,
        default: 'operator',
        validator: (v) => ['operator', 'business'].includes(v),
    },
});

const formatTime = (minutes) => {
    if (!minutes) return `0 ${t('kpi.minutes')}`;
    if (minutes < 60) return `${minutes} ${t('kpi.minutes')}`;
    return `${Math.floor(minutes / 60)} ${t('kpi.hours')} ${minutes % 60} ${t('kpi.minutes')}`;
};

const getProgressPercentage = (current, target) => {
    if (!target) return 0;
    return Math.min(100, Math.round((current / target) * 100));
};

const getCallStatsItems = () => [
    { icon: CheckCircleIcon, iconColor: 'text-green-600', label: t('kpi.answered'), key: 'answered_calls' },
    { icon: XCircleIcon, iconColor: 'text-red-600', label: t('kpi.not_answered'), key: 'missed_calls' },
    { icon: ClockIcon, iconColor: 'text-yellow-600', label: t('kpi.callback'), key: 'callback_scheduled' },
    { icon: PhoneIcon, iconColor: 'text-blue-600', label: t('kpi.avg_duration'), key: 'avg_call_duration', format: 'time', highlight: true },
];

const getLeadStatsItems = () => [
    { dotColor: 'bg-blue-500', label: t('kpi.new'), key: 'leads_new' },
    { dotColor: 'bg-yellow-500', label: t('kpi.contacted'), key: 'leads_contacted' },
    { dotColor: 'bg-green-500', label: t('kpi.qualified'), key: 'leads_qualified' },
    { dotColor: 'bg-red-500', label: t('kpi.lost'), key: 'leads_lost' },
];

const getCallStats = () => {
    return getCallStatsItems().map(item => ({
        ...item,
        value: item.format === 'time' ? formatTime(props.stats?.[item.key]) : (props.stats?.[item.key] || 0),
    }));
};

const getLeadStats = () => {
    return getLeadStatsItems().map(item => ({
        ...item,
        value: props.stats?.[item.key] || 0,
    }));
};

const getConversionBadge = () => {
    const current = props.stats?.conversion_rate || 0;
    const target = props.targets?.conversion_rate || 0;
    const diff = (current - target).toFixed(1);
    return current >= target ? `+${diff}%` : `${diff}%`;
};

const getConversionBadgeType = () => {
    return (props.stats?.conversion_rate || 0) >= (props.targets?.conversion_rate || 0) ? 'success' : 'danger';
};

const getResponseBadge = () => {
    const current = props.stats?.avg_response_time || 0;
    const target = props.targets?.max_response_time || 60;
    return current <= target ? t('kpi.good') : t('kpi.slow');
};

const getResponseBadgeType = () => {
    return (props.stats?.avg_response_time || 0) <= (props.targets?.max_response_time || 60) ? 'success' : 'danger';
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('kpi.my_kpi_indicators') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ t('kpi.your_performance') }}</p>
        </div>

        <!-- Main KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <KPICard
                :title="t('kpi.todays_calls')"
                :value="stats?.calls_today || 0"
                :target="targets?.daily_calls"
                :badge="`${getProgressPercentage(stats?.calls_today, targets?.daily_calls)}%`"
                badge-type="neutral"
                :icon="PhoneIcon"
                icon-bg-color="green"
            />
            <KPICard
                :title="t('kpi.contacted_leads')"
                :value="stats?.leads_contacted || 0"
                :target="targets?.daily_leads"
                :badge="`${getProgressPercentage(stats?.leads_contacted, targets?.daily_leads)}%`"
                badge-type="neutral"
                :icon="UserGroupIcon"
                icon-bg-color="blue"
            />
            <KPICard
                :title="t('kpi.conversion_rate')"
                :value="stats?.conversion_rate || 0"
                suffix="%"
                :target="targets?.conversion_rate"
                :badge="getConversionBadge()"
                :badge-type="getConversionBadgeType()"
                :icon="ArrowTrendingUpIcon"
                icon-bg-color="purple"
            />
            <KPICard
                :title="t('kpi.avg_response_time')"
                :value="formatTime(stats?.avg_response_time)"
                :badge="getResponseBadge()"
                :badge-type="getResponseBadgeType()"
                :icon="ClockIcon"
                icon-bg-color="orange"
                :show-progress="false"
                :subtitle="`${t('kpi.target')}: ${formatTime(targets?.max_response_time)} ${t('kpi.within')}`"
            />
        </div>

        <!-- Detailed Stats -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <KPIStatsList :title="t('kpi.call_statistics')" :items="getCallStats()" />
            <KPIStatsList :title="t('kpi.lead_statistics')" :items="getLeadStats()" />
        </div>

        <!-- Weekly Performance -->
        <KPIWeeklyTable
            :data="weeklyStats"
            :conversion-target="targets?.conversion_rate || 0"
        />
    </div>
</template>
