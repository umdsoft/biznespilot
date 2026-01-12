<script setup>
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import ContentShowPage from '@/components/content/ContentShowPage.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    post: { type: Object, required: true }
});
</script>

<template>
    <BusinessLayout title="Kontent Tafsilotlari">
        <Head title="Kontent Tafsilotlari" />
        <ContentShowPage :post="post" panel-type="business" />
    </BusinessLayout>
</template>
