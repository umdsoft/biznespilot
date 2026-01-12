<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import DreamBuyerIndex from '@/components/dream-buyer/DreamBuyerIndex.vue';

defineProps({
    dreamBuyers: Array,
});
</script>

<template>
    <BusinessLayout title="Ideal Mijoz">
        <Head title="Ideal Mijoz" />
        <DreamBuyerIndex :dream-buyers="dreamBuyers" panel-type="business" />
    </BusinessLayout>
</template>
