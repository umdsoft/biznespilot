<template>
    <MarketingLayout title="Marketing Kampaniyalari">
        <MarketingCampaignsIndex
            panel-type="marketing"
            :campaigns="campaigns"
            :stats="stats"
            :filters="filters"
            :current-business="currentBusiness"
        />
    </MarketingLayout>
</template>

<script setup>
import MarketingLayout from '@/layouts/MarketingLayout.vue';
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
