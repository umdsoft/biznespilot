<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import ContentEditPage from '@/components/content/ContentEditPage.vue';

const props = defineProps({
    post: { type: Object, required: true },
    panelType: {
        type: String,
        required: true,
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
    <component :is="layoutComponent" title="Kontentni Tahrirlash">
        <Head title="Kontentni Tahrirlash" />
        <ContentEditPage :post="post" :panel-type="panelType" />
    </component>
</template>
