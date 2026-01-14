<script setup>
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import OperatorDashboard from '@/components/Dashboard/OperatorDashboard.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    stats: Object,
    recentLeads: Array,
    todayTasks: Array,
    currentBusiness: Object,
});
</script>

<template>
    <OperatorLayout title="Bosh sahifa">
        <Head title="Operator - Bosh sahifa" />
        <OperatorDashboard
            :stats="stats"
            :recent-leads="recentLeads"
            :today-tasks="todayTasks"
            :current-business="currentBusiness"
            panel-type="operator"
        />
    </OperatorLayout>
</template>
