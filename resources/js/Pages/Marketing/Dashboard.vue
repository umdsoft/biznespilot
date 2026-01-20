<template>
    <MarketingLayout :title="t('nav.dashboard')">
        <MarketingDashboard
            panel-type="marketing"
            :stats="stats"
            :recent-campaigns="recentCampaigns"
            :upcoming-content="upcomingContent"
            :current-business="currentBusiness"
        />
    </MarketingLayout>
</template>

<script setup>
import MarketingLayout from '@/layouts/MarketingLayout.vue';
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
