<template>
    <MarketingLayout :title="t('marketing.campaigns')">
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
import { useI18n } from '@/i18n';

const { t } = useI18n();

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
