<template>
  <MarketingLayout title="Marketing Markazi">
    <MarketingHubIndex
      panel-type="marketing"
      :channels="channels"
      :recent-posts="recentPosts"
      :stats="stats"
      :current-business="currentBusiness"
    />
  </MarketingLayout>
</template>

<script setup>
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import MarketingHubIndex from '@/components/marketing/MarketingHubIndex.vue';

defineProps({
  channels: {
    type: Array,
    default: () => [],
  },
  recentPosts: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({}),
  },
  currentBusiness: {
    type: Object,
    required: true,
  },
});
</script>
