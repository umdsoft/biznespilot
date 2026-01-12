<script setup>
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import OperatorKPI from '@/components/kpi/OperatorKPI.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    stats: Object,
    dailyStats: Array,
    weeklyStats: Array,
    targets: Object,
});
</script>

<template>
    <OperatorLayout title="KPI">
        <Head title="Mening KPI" />
        <OperatorKPI
            :stats="stats"
            :daily-stats="dailyStats"
            :weekly-stats="weeklyStats"
            :targets="targets"
            panel-type="operator"
        />
    </OperatorLayout>
</template>
