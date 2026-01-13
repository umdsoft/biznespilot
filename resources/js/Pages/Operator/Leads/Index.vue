<script setup>
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import LeadIndex from '@/components/leads/LeadIndex.vue';

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
    lazyLoad: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <OperatorLayout title="Leadlar">
        <LeadIndex
            :leads="leads"
            :stats="stats"
            :channels="channels"
            :operators="operators"
            :lazy-load="lazyLoad"
            :can-assign-leads="false"
            panel-type="operator"
        />
    </OperatorLayout>
</template>
