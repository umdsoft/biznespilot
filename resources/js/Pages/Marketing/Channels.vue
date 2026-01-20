<template>
  <MarketingLayout :title="t('marketing.channels')">
    <MarketingChannelsIndex
      panel-type="marketing"
      :channels="channels"
    />
  </MarketingLayout>
</template>

<script setup>
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import MarketingChannelsIndex from '@/components/marketing/MarketingChannelsIndex.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
  channels: {
    type: Array,
    default: () => [],
  },
});
</script>
