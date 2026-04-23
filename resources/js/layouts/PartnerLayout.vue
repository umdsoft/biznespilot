<script setup>
import BaseLayout from './BaseLayout.vue';
import { partnerLayoutConfig } from '@/composables/useLayoutConfig';

defineProps({
    title: {
        type: String,
        default: 'Partner Panel',
    },
});
</script>

<template>
    <BaseLayout :config="partnerLayoutConfig" :title="title" panel-type="partner">
        <slot />
    </BaseLayout>
</template>
