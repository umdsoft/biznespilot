<script setup>
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
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
    canAssignLeads: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <BusinessLayout :title="lead.name">
        <LeadShow
            :lead="lead"
            :operators="operators"
            :sources="sources"
            :regions="regions"
            :districts="districts"
            :can-assign-leads="canAssignLeads"
            panel-type="business"
        />
    </BusinessLayout>
</template>
