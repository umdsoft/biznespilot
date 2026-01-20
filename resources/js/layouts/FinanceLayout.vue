<script setup>
import BaseLayout from './BaseLayout.vue';
import { financeLayoutConfig } from '@/composables/useLayoutConfig';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    title: String,
});
</script>

<template>
    <BaseLayout :config="financeLayoutConfig" :title="title || t('layout.home')" panel-type="finance">
        <slot />
    </BaseLayout>
</template>
