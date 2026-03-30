<template>
  <BusinessLayout :title="product.name">
    <ProductAnalysisShow panel-type="business" v-bind="$props" />
  </BusinessLayout>
</template>

<script setup>
import BusinessLayout from '@/layouts/BusinessLayout.vue';
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
