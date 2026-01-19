<script setup>
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import SalesScriptArsenal from '@/components/Sales/SalesScriptArsenal.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    currentBusiness: Object,
});
</script>

<template>
    <SalesHeadLayout title="Sotuv Skriptlari">
        <Head title="Sotuv Skriptlari - Sotuv Rahbari" />
        <SalesScriptArsenal />
    </SalesHeadLayout>
</template>
