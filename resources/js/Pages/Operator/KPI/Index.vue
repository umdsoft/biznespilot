<script setup>
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import OperatorKPI from '@/components/KPI/OperatorKPI.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    stats: Object,
    dailyStats: Array,
    weeklyStats: Array,
    targets: Object,
});
</script>

<template>
    <OperatorLayout :title="t('nav.kpi')">
        <Head :title="t('nav.kpi')" />
        <OperatorKPI
            :stats="stats"
            :daily-stats="dailyStats"
            :weekly-stats="weeklyStats"
            :targets="targets"
            panel-type="operator"
        />
    </OperatorLayout>
</template>
