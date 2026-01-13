<script setup>
import HRLayout from '@/Layouts/HRLayout.vue';
import HRDashboard from '@/components/dashboard/HRDashboard.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    stats: Object,
    recentActivities: Array,
    pendingTasks: Array,
    todayAttendance: Object,
    currentBusiness: Object,
});
</script>

<template>
    <HRLayout title="Dashboard">
        <Head title="HR Dashboard" />
        <HRDashboard
            :stats="stats"
            :recent-activities="recentActivities"
            :pending-tasks="pendingTasks"
            :today-attendance="todayAttendance"
            :current-business="currentBusiness"
        />
    </HRLayout>
</template>
