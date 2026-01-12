<script setup>
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import SalesHeadDashboard from '@/components/dashboard/SalesHeadDashboard.vue';
import { Head } from '@inertiajs/vue3';

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
    <SalesHeadLayout title="Dashboard">
        <Head title="Sotuv Bo'limi Dashboard" />
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
