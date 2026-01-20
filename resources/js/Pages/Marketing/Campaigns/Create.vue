<template>
    <MarketingLayout :title="t('marketing.new_campaign')">
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
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    currentBusiness: Object,
    errors: Object
});
</script>
