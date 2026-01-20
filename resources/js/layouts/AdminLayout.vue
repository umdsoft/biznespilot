<template>
  <BaseLayout :title="title || t('layout.admin_dashboard')" :config="layoutConfig">
    <slot />
  </BaseLayout>
</template>

<script setup>
import BaseLayout from './BaseLayout.vue';
import { adminLayoutConfig } from '@/composables/useLayoutConfig';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
  title: {
    type: String,
    default: '',
  },
});

const layoutConfig = adminLayoutConfig;
</script>
