<template>
  <MarketingLayout title="Marketing Kanallari">
    <MarketingChannelsIndex
      panel-type="marketing"
      :channels="channels"
    />
  </MarketingLayout>
</template>

<script setup>
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import MarketingChannelsIndex from '@/components/marketing/MarketingChannelsIndex.vue';

defineProps({
  channels: {
    type: Array,
    default: () => [],
  },
});
</script>
