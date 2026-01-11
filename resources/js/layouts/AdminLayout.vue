<template>
  <BaseLayout :title="title" :config="layoutConfig">
    <slot />
  </BaseLayout>
</template>

<script setup>
import BaseLayout from './BaseLayout.vue';
import { adminLayoutConfig } from '@/composables/useLayoutConfig';

defineProps({
  title: {
    type: String,
    default: 'Admin Dashboard',
  },
});

const layoutConfig = adminLayoutConfig;
</script>
