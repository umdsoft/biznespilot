<script setup>
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
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
    currentBusiness: {
        type: Object,
        default: null,
    },
    lazyLoad: {
        type: Boolean,
        default: false,
    },
    canAssignLeads: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <BusinessLayout title="Sotuv Pipeline">
        <LeadIndex
            :leads="leads"
            :stats="stats"
            :channels="channels"
            :lazy-load="lazyLoad"
            :can-assign-leads="canAssignLeads"
            panel-type="business"
        />
    </BusinessLayout>
</template>
