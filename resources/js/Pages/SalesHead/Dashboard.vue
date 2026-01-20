<script setup>
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import SalesHeadDashboard from '@/components/Dashboard/SalesHeadDashboard.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    teamMembers: { type: Array, default: () => [] },
    leadStats: { type: Object, default: () => ({}) },
    revenueStats: { type: Object, default: () => ({}) },
    pipeline: { type: Object, default: () => ({}) },
    teamPerformance: { type: Array, default: () => [] },
    recentLeads: { type: Array, default: () => [] },
    overdueTasks: { type: Array, default: () => [] },
});
</script>

<template>
    <SalesHeadLayout :title="t('nav.dashboard')">
        <Head :title="t('saleshead.dashboard_title')" />
        <SalesHeadDashboard
            :team-members="teamMembers"
            :lead-stats="leadStats"
            :revenue-stats="revenueStats"
            :pipeline="pipeline"
            :team-performance="teamPerformance"
            :recent-leads="recentLeads"
            :overdue-tasks="overdueTasks"
            panel-type="saleshead"
        />
    </SalesHeadLayout>
</template>
