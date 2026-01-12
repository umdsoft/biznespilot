<script setup>
import BaseLayout from './BaseLayout.vue';
import { operatorLayoutConfig } from '@/composables/useLayoutConfig';

defineProps({
    title: String,
});
</script>

<template>
    <BaseLayout :config="operatorLayoutConfig" :title="title" panel-type="operator">
        <slot />
    </BaseLayout>
</template>
