<template>
  <BusinessLayout :title="t('nav.marketing')">
    <MarketingHubIndex
      panel-type="business"
      :channels="channels"
      :recent-posts="recentPosts"
      :stats="stats"
      :current-business="currentBusiness"
    />
  </BusinessLayout>
</template>

<script setup>
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import MarketingHubIndex from '@/components/marketing/MarketingHubIndex.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

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
