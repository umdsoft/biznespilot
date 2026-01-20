<script setup>
import BaseLayout from './BaseLayout.vue';
import { marketingLayoutConfig } from '@/composables/useLayoutConfig';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    title: String,
});
</script>

<template>
    <BaseLayout :config="marketingLayoutConfig" :title="title || t('layout.home')" panel-type="marketing">
        <slot />
    </BaseLayout>
</template>
