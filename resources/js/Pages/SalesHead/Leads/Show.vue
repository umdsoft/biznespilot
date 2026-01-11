<script setup>
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import LeadShow from '@/components/leads/LeadShow.vue';

defineProps({
    lead: {
        type: Object,
        required: true,
    },
    operators: {
        type: Array,
        default: () => [],
    },
    sources: {
        type: Array,
        default: () => [],
    },
    regions: {
        type: Object,
        default: () => ({}),
    },
    districts: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <SalesHeadLayout :title="lead.name">
        <LeadShow
            :lead="lead"
            :operators="operators"
            :sources="sources"
            :regions="regions"
            :districts="districts"
            :can-assign-leads="true"
            panel-type="saleshead"
        />
    </SalesHeadLayout>
</template>
