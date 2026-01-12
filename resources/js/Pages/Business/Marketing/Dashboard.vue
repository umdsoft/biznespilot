<template>
    <BusinessLayout title="Marketing Dashboard">
        <MarketingDashboard
            panel-type="business"
            :stats="stats"
            :recent-campaigns="recentCampaigns"
            :upcoming-content="upcomingContent"
            :current-business="currentBusiness"
        />
    </BusinessLayout>
</template>

<script setup>
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import MarketingDashboard from '@/components/marketing/MarketingDashboard.vue';

defineProps({
    stats: Object,
    recentCampaigns: Array,
    upcomingContent: Array,
    currentBusiness: Object,
});
</script>
