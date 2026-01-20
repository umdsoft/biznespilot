<script setup>
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import SalesScriptArsenal from '@/components/Sales/SalesScriptArsenal.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    currentBusiness: Object,
});
</script>

<template>
    <SalesHeadLayout :title="t('nav.sales_scripts')">
        <Head :title="t('nav.sales_scripts')" />
        <SalesScriptArsenal />
    </SalesHeadLayout>
</template>
