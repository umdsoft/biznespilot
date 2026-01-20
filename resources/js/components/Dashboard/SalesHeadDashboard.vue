<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import StatCard from './StatCard.vue';
import DashboardCard from './DashboardCard.vue';
import LeadsList from './LeadsList.vue';
import TasksList from './TasksList.vue';
import PipelineChart from './PipelineChart.vue';
import TeamPerformance from './TeamPerformance.vue';
import {
    UserPlusIcon,
    CheckCircleIcon,
    CurrencyDollarIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { formatFullCurrency } from '@/utils/formatting';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    teamMembers: { type: Array, default: () => [] },
    leadStats: { type: Object, default: () => ({}) },
    revenueStats: { type: Object, default: () => ({}) },
    pipeline: { type: Object, default: () => ({}) },
    teamPerformance: { type: Array, default: () => [] },
    recentLeads: { type: Array, default: () => [] },
    overdueTasks: { type: Array, default: () => [] },
    panelType: {
        type: String,
        default: 'saleshead',
        validator: (v) => ['saleshead', 'business'].includes(v),
    },
});

const isRefreshing = ref(false);

const pipelineStages = computed(() => ({
    new: { label: t('dashboard.saleshead.stage_new'), color: 'bg-blue-500' },
    contacted: { label: t('dashboard.saleshead.stage_contacted'), color: 'bg-yellow-500' },
    qualified: { label: t('dashboard.saleshead.stage_qualified'), color: 'bg-purple-500' },
    proposal: { label: t('dashboard.saleshead.stage_proposal'), color: 'bg-orange-500' },
    negotiation: { label: t('dashboard.saleshead.stage_negotiation'), color: 'bg-pink-500' },
}));

const currentDate = computed(() => {
    return new Date().toLocaleDateString('uz-UZ', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

const formatCurrency = (value) => formatFullCurrency(value);

const getBasePath = () => {
    return props.panelType === 'saleshead' ? '/sales-head' : '/business';
};

const refreshData = () => {
    isRefreshing.value = true;
    router.reload({
        only: ['teamMembers', 'leadStats', 'revenueStats', 'pipeline', 'teamPerformance', 'recentLeads', 'overdueTasks'],
        onFinish: () => {
            isRefreshing.value = false;
        }
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('dashboard.saleshead.title') }}</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">{{ t('dashboard.saleshead.subtitle') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ currentDate }}</span>
                <button
                    @click="refreshData"
                    :disabled="isRefreshing"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors flex items-center gap-2 disabled:opacity-50"
                >
                    <svg :class="{ 'animate-spin': isRefreshing }" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ t('dashboard.saleshead.refresh') }}
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <StatCard
                :title="t('dashboard.saleshead.today_new_leads')"
                :value="leadStats.new_today || 0"
                :subtitle="`${t('dashboard.saleshead.active_leads')}: ${leadStats.total_active || 0}`"
                :icon="UserPlusIcon"
                icon-bg-color="emerald"
            />
            <StatCard
                :title="t('dashboard.saleshead.monthly_won')"
                :value="leadStats.won_this_month || 0"
                :subtitle="`${t('dashboard.saleshead.conversion')}: ${leadStats.conversion_rate || 0}%`"
                :icon="CheckCircleIcon"
                icon-bg-color="green"
                value-color="emerald"
            />
            <StatCard
                :title="t('dashboard.saleshead.today_revenue')"
                :value="formatCurrency(revenueStats.today)"
                :subtitle="`${t('dashboard.saleshead.monthly')}: ${formatCurrency(revenueStats.this_month)}`"
                :icon="CurrencyDollarIcon"
                icon-bg-color="blue"
                value-color="blue"
            />
            <StatCard
                :title="t('dashboard.saleshead.operators_count')"
                :value="teamMembers.length"
                :icon="UsersIcon"
                icon-bg-color="purple"
                value-color="purple"
                :href="`${getBasePath()}/team`"
                :link-text="t('dashboard.view_details')"
            />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Pipeline Summary -->
            <div class="lg:col-span-2">
                <DashboardCard
                    :title="t('dashboard.saleshead.sales_funnel')"
                    :link-href="`${getBasePath()}/pipeline`"
                    :link-text="t('dashboard.view_details')"
                >
                    <PipelineChart :pipeline="pipeline" :stages="pipelineStages" />
                </DashboardCard>
            </div>

            <!-- Team Performance -->
            <DashboardCard
                :title="t('dashboard.saleshead.top_operators')"
                :link-href="`${getBasePath()}/performance`"
                :link-text="t('dashboard.saleshead.view_all')"
            >
                <TeamPerformance :members="teamPerformance" :limit="5" />
            </DashboardCard>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Leads -->
            <DashboardCard
                :title="t('dashboard.saleshead.recent_leads')"
                :link-href="`${getBasePath()}/leads`"
                :link-text="t('dashboard.saleshead.view_all')"
            >
                <LeadsList
                    :leads="recentLeads"
                    :base-path="`${getBasePath()}/leads`"
                    show-avatar
                    show-date
                />
            </DashboardCard>

            <!-- Overdue Tasks -->
            <DashboardCard
                :title="t('dashboard.saleshead.overdue_tasks')"
                :badge="overdueTasks.length > 0 ? overdueTasks.length : null"
                badge-color="red"
                :link-href="`${getBasePath()}/tasks`"
                :link-text="t('dashboard.saleshead.view_all')"
            >
                <TasksList
                    :tasks="overdueTasks"
                    is-overdue
                    show-assignee
                    :empty-text="t('dashboard.saleshead.no_overdue_tasks')"
                    empty-icon="success"
                />
            </DashboardCard>
        </div>
    </div>
</template>
