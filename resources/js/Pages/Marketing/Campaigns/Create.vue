<template>
    <MarketingLayout title="Yangi Kampaniya">
        <MarketingCampaignCreate
            panel-type="marketing"
            :current-business="currentBusiness"
            :errors="errors"
        />
    </MarketingLayout>
</template>

<script setup>
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import MarketingCampaignCreate from '@/components/marketing/MarketingCampaignCreate.vue';

defineProps({
    currentBusiness: Object,
    errors: Object
});
</script>
