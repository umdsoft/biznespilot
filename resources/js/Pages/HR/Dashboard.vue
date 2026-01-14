<script setup>
import HRLayout from '@/layouts/HRLayout.vue';
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
    <HRLayout title="Bosh sahifa">
        <Head title="Kadrlar bo'limi - Bosh sahifa" />
        <HRDashboard
            :stats="stats"
            :recent-activities="recentActivities"
            :pending-tasks="pendingTasks"
            :today-attendance="todayAttendance"
            :current-business="currentBusiness"
        />
    </HRLayout>
</template>
