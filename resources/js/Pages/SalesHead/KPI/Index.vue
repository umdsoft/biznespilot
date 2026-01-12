<script setup>
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import SalesHeadKPI from '@/components/kpi/SalesHeadKPI.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    kpiData: {
        type: Object,
        default: () => ({}),
    },
    teamKpi: {
        type: Array,
        default: () => [],
    },
    period: {
        type: String,
        default: 'monthly',
    },
});

const currentPeriod = ref(props.period);

const handlePeriodChange = (newPeriod) => {
    currentPeriod.value = newPeriod;
    // You can add Inertia router call here if needed
};
</script>

<template>
    <SalesHeadLayout title="KPI">
        <Head title="KPI Ko'rsatkichlari" />
        <SalesHeadKPI
            :kpi-data="kpiData"
            :team-kpi="teamKpi"
            :period="currentPeriod"
            panel-type="saleshead"
            @period-change="handlePeriodChange"
        />
    </SalesHeadLayout>
</template>
