<script setup>
import HRLayout from '@/layouts/HRLayout.vue';
import HRDashboard from '@/components/Dashboard/HRDashboard.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    stats: Object,
    recentActivities: Array,
    pendingTasks: Array,
    todayAttendance: Object,
    currentBusiness: Object,
});
</script>

<template>
    <HRLayout :title="t('hr.dashboard')">
        <Head :title="t('hr.dashboard')" />
        <HRDashboard
            :stats="stats"
            :recent-activities="recentActivities"
            :pending-tasks="pendingTasks"
            :today-attendance="todayAttendance"
            :current-business="currentBusiness"
        />
    </HRLayout>
</template>
