<template>
    <BusinessLayout :title="t('marketing.dashboard')">
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
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingDashboard from '@/components/marketing/MarketingDashboard.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    stats: Object,
    recentCampaigns: Array,
    upcomingContent: Array,
    currentBusiness: Object,
});
</script>
