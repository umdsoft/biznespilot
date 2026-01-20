<script setup>
import BaseLayout from './BaseLayout.vue';
import AlertDropdown from '@/components/Sales/AlertDropdown.vue';
import { operatorLayoutConfig } from '@/composables/useLayoutConfig';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    title: String,
});
</script>

<template>
    <BaseLayout :config="operatorLayoutConfig" :title="title || t('layout.home')" panel-type="operator">
        <!-- Alert Dropdown in Header -->
        <template #notifications>
            <AlertDropdown />
        </template>

        <slot />
    </BaseLayout>
</template>
