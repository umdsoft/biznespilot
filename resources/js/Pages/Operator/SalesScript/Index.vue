<script setup>
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesScriptArsenal from '@/components/Sales/SalesScriptArsenal.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    currentBusiness: Object,
});
</script>

<template>
    <OperatorLayout title="Sotuv Skriptlari">
        <Head title="Sotuv Skriptlari - Operator" />
        <SalesScriptArsenal />
    </OperatorLayout>
</template>
