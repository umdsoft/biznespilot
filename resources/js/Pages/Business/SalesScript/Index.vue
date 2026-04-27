<script setup>
import { computed } from 'vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import HRLayout from '@/layouts/HRLayout.vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import SalesScriptArsenal from '@/components/Sales/SalesScriptArsenal.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    currentBusiness: Object,
    panelType: { type: String, default: 'business' },
});

// Rolga qarab tegishli layout — har xodim profilidan kirsa o'z paneli ko'rinadi.
// Avval Operator/SalesScript va SalesHead/SalesScript alohida sahifalar edi —
// hozir bitta sahifa dynamic layoutComponent bilan barcha rollarga xizmat qiladi.
const layoutComponent = computed(() => {
    const map = {
        operator: OperatorLayout,
        saleshead: SalesHeadLayout,
        marketing: MarketingLayout,
        hr: HRLayout,
        finance: FinanceLayout,
        business: BusinessLayout,
    };
    return map[props.panelType] || BusinessLayout;
});
</script>

<template>
    <component :is="layoutComponent" :title="t('nav.sales_scripts')">
        <Head :title="t('nav.sales_scripts')" />
        <SalesScriptArsenal />
    </component>
</template>
