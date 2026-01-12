<script setup>
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import ContentEditPage from '@/components/content/ContentEditPage.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    post: { type: Object, required: true }
});
</script>

<template>
    <MarketingLayout title="Kontentni Tahrirlash">
        <Head title="Kontentni Tahrirlash" />
        <ContentEditPage :post="post" panel-type="marketing" />
    </MarketingLayout>
</template>
