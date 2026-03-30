<template>
  <MarketingLayout :title="product.name">
    <ProductAnalysisShow panel-type="marketing" v-bind="$props" />
  </MarketingLayout>
</template>

<script setup>
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import ProductAnalysisShow from '@/components/marketing/ProductAnalysisShow.vue';

defineProps({
  product: Object,
  sales: Object,
  competitors: Array,
  insights: Array,
  scores: Object,
  ai_analysis: Object,
  ai_stale: Boolean,
});
</script>
