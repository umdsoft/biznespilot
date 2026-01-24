<template>
  <BusinessLayout title="Marketing Kanallari">
    <MarketingChannelsIndex
      panel-type="business"
      :connected-channels="connectedChannels"
      :manual-channels="manualChannels"
      :stats="stats"
      :recommendations="recommendations"
      :available-platforms="availablePlatforms"
    />
  </BusinessLayout>
</template>

<script setup>
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingChannelsIndex from '@/components/marketing/MarketingChannelsIndex.vue';

defineProps({
  connectedChannels: {
    type: Array,
    default: () => [],
  },
  manualChannels: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({
      total_channels: 0,
      total_followers: 0,
      total_leads_30d: 0,
      avg_engagement: 0,
      avg_effectiveness: 0,
      best_channel: null,
    }),
  },
  recommendations: {
    type: Array,
    default: () => [],
  },
  availablePlatforms: {
    type: Array,
    default: () => [],
  },
  currentBusiness: {
    type: Object,
    default: null,
  },
});
</script>
