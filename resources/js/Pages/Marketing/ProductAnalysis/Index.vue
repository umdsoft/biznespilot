<template>
  <MarketingLayout title="Mahsulot Analizi">
    <ProductAnalysisIndex panel-type="marketing" :products="products" :stats="stats" :insights="insights" :dream-buyers="dreamBuyers" />
  </MarketingLayout>
</template>

<script setup>
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import ProductAnalysisIndex from '@/components/marketing/ProductAnalysisIndex.vue';

defineProps({
  products: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
  insights: { type: Array, default: () => [] },
  dreamBuyers: { type: Array, default: () => [] },
});
</script>
