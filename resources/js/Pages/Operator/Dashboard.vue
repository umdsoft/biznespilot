<script setup>
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import OperatorDashboard from '@/components/Dashboard/OperatorDashboard.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    stats: Object,
    recentLeads: Array,
    todayTasks: Array,
    currentBusiness: Object,
});
</script>

<template>
    <OperatorLayout :title="t('nav.dashboard')">
        <Head :title="'Operator - ' + t('nav.dashboard')" />
        <OperatorDashboard
            :stats="stats"
            :recent-leads="recentLeads"
            :today-tasks="todayTasks"
            :current-business="currentBusiness"
            panel-type="operator"
        />
    </OperatorLayout>
</template>
