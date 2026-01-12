<template>
    <BusinessLayout title="Marketing Kampaniyalari">
        <MarketingCampaignsIndex
            panel-type="business"
            :campaigns="campaigns"
            :stats="stats"
            :filters="filters"
            :current-business="currentBusiness"
        />
    </BusinessLayout>
</template>

<script setup>
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingCampaignsIndex from '@/components/marketing/MarketingCampaignsIndex.vue';

defineProps({
    campaigns: {
        type: Array,
        default: () => []
    },
    stats: Object,
    filters: Object,
    currentBusiness: Object
});
</script>
