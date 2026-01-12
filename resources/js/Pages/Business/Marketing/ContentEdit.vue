<script setup>
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import ContentEditPage from '@/components/content/ContentEditPage.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    post: { type: Object, required: true }
});
</script>

<template>
    <BusinessLayout title="Kontentni Tahrirlash">
        <Head title="Kontentni Tahrirlash" />
        <ContentEditPage :post="post" panel-type="business" />
    </BusinessLayout>
</template>
