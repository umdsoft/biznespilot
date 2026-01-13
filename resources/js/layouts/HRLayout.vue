<script setup>
import BaseLayout from './BaseLayout.vue';
import { hrLayoutConfig } from '@/composables/useLayoutConfig';

defineProps({
    title: String,
});
</script>

<template>
    <BaseLayout :config="hrLayoutConfig" :title="title" panel-type="hr">
        <slot />
    </BaseLayout>
</template>
