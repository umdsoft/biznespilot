<template>
    <BusinessLayout title="Yangi Kampaniya">
        <MarketingCampaignCreate
            panel-type="business"
            :current-business="currentBusiness"
            :errors="errors"
        />
    </BusinessLayout>
</template>

<script setup>
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingCampaignCreate from '@/components/marketing/MarketingCampaignCreate.vue';

defineProps({
    currentBusiness: Object,
    errors: Object
});
</script>
