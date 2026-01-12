<script setup>
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import ContentIndex from '@/components/content/ContentIndex.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    posts: { type: Array, default: () => [] }
});
</script>

<template>
    <BusinessLayout title="Kontent Reja">
        <Head title="Kontent Reja" />
        <ContentIndex :posts="posts" panel-type="business" />
    </BusinessLayout>
</template>
