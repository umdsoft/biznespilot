<template>
    <component :is="layoutComponent" title="SWOT Tahlil">
        <SwotIndex
            :current-business="currentBusiness"
            :swot="swot"
            :competitor-count="competitorCount"
            :last-updated="lastUpdated"
            :competitors="competitors"
            :panel-type="panelType"
        />
    </component>
</template>

<script setup>
import { computed } from 'vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import SwotIndex from '@/components/swot/SwotIndex.vue';

const props = defineProps({
    currentBusiness: { type: Object, required: true },
    swot: { type: Object, default: () => ({}) },
    competitorCount: { type: Number, default: 0 },
    lastUpdated: { type: String, default: null },
    competitors: { type: Array, default: () => [] },
    panelType: {
        type: String,
        required: true,
        validator: (v) => ['business', 'marketing'].includes(v),
    },
});

const layoutComponent = computed(() => {
    return props.panelType === 'business' ? BusinessLayout : MarketingLayout;
});
</script>
