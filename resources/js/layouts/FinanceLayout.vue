<script setup>
import BaseLayout from './BaseLayout.vue';
import { financeLayoutConfig } from '@/composables/useLayoutConfig';

defineProps({
    title: String,
});
</script>

<template>
    <BaseLayout :config="financeLayoutConfig" :title="title" panel-type="finance">
        <slot />
    </BaseLayout>
</template>
