<script setup>
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesScriptArsenal from '@/components/Sales/SalesScriptArsenal.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    currentBusiness: Object,
});
</script>

<template>
    <OperatorLayout :title="t('nav.sales_scripts')">
        <Head :title="t('nav.sales_scripts') + ' - Operator'" />
        <SalesScriptArsenal />
    </OperatorLayout>
</template>
