<script setup>
import { computed } from 'vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import OperatorKPI from '@/components/KPI/OperatorKPI.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    stats: Object,
    dailyStats: Array,
    weeklyStats: Array,
    targets: Object,
    panelType: { type: String, default: 'operator' },
});

// Operator KPI sahifasi — operator/saleshead/owner kira oladi
const layoutComponent = computed(() => {
    const map = {
        operator: OperatorLayout,
        saleshead: SalesHeadLayout,
        business: BusinessLayout,
    };
    return map[props.panelType] || OperatorLayout;
});
</script>

<template>
    <component :is="layoutComponent" :title="t('nav.kpi')">
        <Head :title="t('nav.kpi')" />
        <OperatorKPI
            :stats="stats"
            :daily-stats="dailyStats"
            :weekly-stats="weeklyStats"
            :targets="targets"
            :panel-type="panelType"
        />
    </component>
</template>
