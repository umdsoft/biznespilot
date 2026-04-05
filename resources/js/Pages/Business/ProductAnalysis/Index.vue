<template>
  <BusinessLayout title="Mahsulot Analizi">
    <ProductAnalysisIndex panel-type="business" :products="products" :stats="stats" :insights="insights" :dream-buyers="dreamBuyers" />
  </BusinessLayout>
</template>

<script setup>
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import ProductAnalysisIndex from '@/components/marketing/ProductAnalysisIndex.vue';

defineProps({
  products: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
  insights: { type: Array, default: () => [] },
  dreamBuyers: { type: Array, default: () => [] },
});
</script>
