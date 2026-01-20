<script setup>
import { Head } from '@inertiajs/vue3';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import CustdevIndex from '@/components/custdev/CustdevIndex.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    surveys: Array,
});
</script>

<template>
    <MarketingLayout :title="t('nav.custdev')">
        <Head :title="t('nav.custdev')" />
        <CustdevIndex :surveys="surveys" panel-type="marketing" />
    </MarketingLayout>
</template>
