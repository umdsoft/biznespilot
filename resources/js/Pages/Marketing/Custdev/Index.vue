<script setup>
import { Head } from '@inertiajs/vue3';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import CustdevIndex from '@/components/custdev/CustdevIndex.vue';

defineProps({
    surveys: Array,
});
</script>

<template>
    <MarketingLayout title="CustDev So'rovnomalar">
        <Head title="CustDev So'rovnomalar" />
        <CustdevIndex :surveys="surveys" panel-type="marketing" />
    </MarketingLayout>
</template>
