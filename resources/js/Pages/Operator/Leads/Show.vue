<script setup>
import OperatorLayout from '@/layouts/OperatorLayout.vue';
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
    <OperatorLayout :title="lead.name">
        <LeadShow
            :lead="lead"
            :operators="operators"
            :sources="sources"
            :regions="regions"
            :districts="districts"
            :can-assign-leads="false"
            panel-type="operator"
        />
    </OperatorLayout>
</template>
