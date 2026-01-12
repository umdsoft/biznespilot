<script setup>
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import ContentShowPage from '@/components/content/ContentShowPage.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    post: { type: Object, required: true }
});
</script>

<template>
    <MarketingLayout title="Kontent Tafsilotlari">
        <Head title="Kontent Tafsilotlari" />
        <ContentShowPage :post="post" panel-type="marketing" />
    </MarketingLayout>
</template>
