<template>
  <MarketingLayout :title="t('nav.marketing')">
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
