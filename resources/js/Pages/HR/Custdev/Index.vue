<script setup>
import { Head } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import CustdevIndex from '@/components/custdev/CustdevIndex.vue';

defineProps({
    surveys: Array,
});
</script>

<template>
    <HRLayout title="HR So'rovnomalar">
        <Head title="HR So'rovnomalar" />
        <CustdevIndex :surveys="surveys" panel-type="hr" />
    </HRLayout>
</template>
