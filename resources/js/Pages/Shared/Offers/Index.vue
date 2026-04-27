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
import HRLayout from '@/layouts/HRLayout.vue';
import OffersIndex from '@/components/offers/OffersIndex.vue';

const { t } = useI18n();

const props = defineProps({
    offers: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    panelType: {
        type: String,
        required: true,
        // 6 rolni qo'llab-quvvatlash (HR ham qo'shildi)
        validator: (v) => ['business', 'marketing', 'finance', 'operator', 'saleshead', 'hr'].includes(v),
    },
});

const layoutComponent = computed(() => {
    const layouts = {
        business: BusinessLayout,
        marketing: MarketingLayout,
        finance: FinanceLayout,
        operator: OperatorLayout,
        saleshead: SalesHeadLayout,
        hr: HRLayout,
    };
    return layouts[props.panelType] || BaseLayout;
});
</script>

<template>
    <component :is="layoutComponent" :title="t('offers.title')">
        <Head :title="t('offers.title')" />
        <OffersIndex
            :offers="offers"
            :stats="stats"
            :panel-type="panelType"
        />
    </component>
</template>
