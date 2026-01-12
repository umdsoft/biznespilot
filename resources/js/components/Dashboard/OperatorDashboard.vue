<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
    PhoneIcon,
    UserGroupIcon,
    CheckCircleIcon,
    ClockIcon,
    ArrowTrendingUpIcon,
    ChatBubbleLeftRightIcon,
} from '@heroicons/vue/24/outline';
import StatCard from './StatCard.vue';
import DashboardCard from './DashboardCard.vue';
import LeadsList from './LeadsList.vue';
import TasksList from './TasksList.vue';
import QuickActions from './QuickActions.vue';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    recentLeads: { type: Array, default: () => [] },
    todayTasks: { type: Array, default: () => [] },
    currentBusiness: { type: Object, default: null },
    panelType: {
        type: String,
        default: 'operator',
        validator: (v) => ['operator', 'business'].includes(v),
    },
});

const formatTime = (minutes) => {
    if (!minutes) return '0 daqiqa';
    if (minutes < 60) return `${minutes} daqiqa`;
    return `${Math.floor(minutes / 60)} soat ${minutes % 60} daqiqa`;
};

const getBasePath = () => {
    return props.panelType === 'operator' ? '/operator' : '/business';
};

const quickActions = [
    { href: `${getBasePath()}/leads`, icon: UserGroupIcon, label: 'Leadlar', color: 'blue' },
    { href: `${getBasePath()}/inbox`, icon: ChatBubbleLeftRightIcon, label: 'Inbox', color: 'green' },
    { href: `${getBasePath()}/kpi`, icon: ArrowTrendingUpIcon, label: 'KPI', color: 'purple' },
    { href: `${getBasePath()}/tasks`, icon: CheckCircleIcon, label: 'Vazifalar', color: 'orange' },
];
</script>

<template>
    <div class="space-y-6">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <StatCard
                title="Tayinlangan leadlar"
                :value="stats?.total_leads || 0"
                :badge="`${stats?.new_leads || 0} yangi`"
                badge-color="blue"
                :icon="UserGroupIcon"
                icon-bg-color="blue"
            />
            <StatCard
                title="Bugungi qo'ng'iroqlar"
                :value="stats?.calls_today || 0"
                :badge="`${stats?.successful_calls || 0} muvaffaqiyatli`"
                badge-color="green"
                :icon="PhoneIcon"
                icon-bg-color="green"
            />
            <StatCard
                title="Konversiya darajasi"
                :value="`${stats?.conversion_rate || 0}%`"
                :badge="`${stats?.qualified_leads || 0} kvalifikatsiya`"
                badge-color="purple"
                :icon="ArrowTrendingUpIcon"
                icon-bg-color="purple"
            />
            <StatCard
                title="O'rtacha javob vaqti"
                :value="formatTime(stats?.avg_response_time || 0)"
                :icon="ClockIcon"
                icon-bg-color="orange"
            />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Leads -->
            <DashboardCard
                title="So'nggi Leadlar"
                :link-href="`${getBasePath()}/leads`"
                divided
                no-padding
            >
                <LeadsList
                    :leads="recentLeads"
                    :base-path="`${getBasePath()}/leads`"
                />
            </DashboardCard>

            <!-- Today's Tasks -->
            <DashboardCard
                title="Bugungi Vazifalar"
                :link-href="`${getBasePath()}/tasks`"
                divided
                no-padding
            >
                <TasksList :tasks="todayTasks" />
            </DashboardCard>
        </div>

        <!-- Quick Actions -->
        <QuickActions :actions="quickActions" />
    </div>
</template>
