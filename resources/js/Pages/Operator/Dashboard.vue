<script setup>
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import OperatorDashboard from '@/components/dashboard/OperatorDashboard.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    stats: Object,
    recentLeads: Array,
    todayTasks: Array,
    currentBusiness: Object,
});
</script>

<template>
    <OperatorLayout title="Dashboard">
        <Head title="Operator Dashboard" />
        <OperatorDashboard
            :stats="stats"
            :recent-leads="recentLeads"
            :today-tasks="todayTasks"
            :current-business="currentBusiness"
            panel-type="operator"
        />
    </OperatorLayout>
</template>
