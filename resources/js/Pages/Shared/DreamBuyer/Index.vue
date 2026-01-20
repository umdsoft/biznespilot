<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import DreamBuyerIndex from '@/components/dream-buyer/DreamBuyerIndex.vue';

const { t } = useI18n();

const props = defineProps({
    dreamBuyers: Array,
    panelType: {
        type: String,
        default: 'business',
        validator: (v) => ['business', 'marketing', 'finance', 'operator', 'saleshead'].includes(v),
    },
});

const layoutComponent = computed(() => {
    const layouts = {
        business: BusinessLayout,
        marketing: MarketingLayout,
        finance: FinanceLayout,
        operator: OperatorLayout,
        saleshead: SalesHeadLayout,
    };
    return layouts[props.panelType] || BaseLayout;
});
</script>

<template>
    <component :is="layoutComponent" :title="t('dream_buyer.title')">
        <Head :title="t('dream_buyer.title')" />
        <DreamBuyerIndex :dream-buyers="dreamBuyers" :panel-type="panelType" />
    </component>
</template>
