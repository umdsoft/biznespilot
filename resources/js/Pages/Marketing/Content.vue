<script setup>
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import ContentIndex from '@/components/content/ContentIndex.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    posts: { type: Array, default: () => [] }
});
</script>

<template>
    <MarketingLayout title="Kontent Reja">
        <Head title="Kontent Reja" />
        <ContentIndex :posts="posts" panel-type="marketing" />
    </MarketingLayout>
</template>
