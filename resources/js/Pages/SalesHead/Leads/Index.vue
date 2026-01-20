<script setup>
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import LeadIndex from '@/components/leads/LeadIndex.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    leads: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_leads: 0,
            new_leads: 0,
            qualified_leads: 0,
            pipeline_value: 0,
            won_deals: 0,
            total_value: 0
        }),
    },
    channels: {
        type: Array,
        default: () => [],
    },
    operators: {
        type: Array,
        default: () => [],
    },
    pipelineStages: {
        type: Array,
        default: () => [],
    },
    lazyLoad: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <SalesHeadLayout :title="t('sales.leads')">
        <LeadIndex
            :leads="leads"
            :stats="stats"
            :channels="channels"
            :operators="operators"
            :pipeline-stages="pipelineStages"
            :lazy-load="lazyLoad"
            :can-assign-leads="true"
            panel-type="saleshead"
        />
    </SalesHeadLayout>
</template>
