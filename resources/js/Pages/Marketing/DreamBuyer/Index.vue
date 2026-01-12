<script setup>
import { Head } from '@inertiajs/vue3';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import DreamBuyerIndex from '@/components/dream-buyer/DreamBuyerIndex.vue';

defineProps({
    dreamBuyers: Array,
});
</script>

<template>
    <MarketingLayout title="Ideal Mijoz">
        <Head title="Ideal Mijoz" />
        <DreamBuyerIndex :dream-buyers="dreamBuyers" panel-type="marketing" />
    </MarketingLayout>
</template>
