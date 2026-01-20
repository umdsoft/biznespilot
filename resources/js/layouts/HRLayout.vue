<script setup>
import BaseLayout from './BaseLayout.vue';
import { hrLayoutConfig } from '@/composables/useLayoutConfig';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    title: String,
});
</script>

<template>
    <BaseLayout :config="hrLayoutConfig" :title="title || t('layout.home')" panel-type="hr">
        <slot />
    </BaseLayout>
</template>
