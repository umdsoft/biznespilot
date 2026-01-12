<script setup>
import BaseLayout from './BaseLayout.vue';
import { marketingLayoutConfig } from '@/composables/useLayoutConfig';

defineProps({
    title: String,
});
</script>

<template>
    <BaseLayout :config="marketingLayoutConfig" :title="title" panel-type="marketing">
        <slot />
    </BaseLayout>
</template>
