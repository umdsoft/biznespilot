<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import CustdevIndex from '@/components/custdev/CustdevIndex.vue';

defineProps({
    surveys: Array,
});
</script>

<template>
    <BusinessLayout title="CustDev So'rovnomalar">
        <Head title="CustDev So'rovnomalar" />
        <CustdevIndex :surveys="surveys" panel-type="business" />
    </BusinessLayout>
</template>
