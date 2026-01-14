<template>
  <BusinessLayout title="Marketing Kanallari">
    <MarketingChannelsIndex
      panel-type="business"
      :channels="channels"
    />
  </BusinessLayout>
</template>

<script setup>
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingChannelsIndex from '@/components/marketing/MarketingChannelsIndex.vue';

defineProps({
  channels: {
    type: Array,
    default: () => [],
  },
});
</script>
