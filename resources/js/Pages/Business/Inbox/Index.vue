<script setup>
import { computed } from 'vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import HRLayout from '@/layouts/HRLayout.vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import InboxIndex from '@/components/inbox/InboxIndex.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    conversations: Array,
    stats: Object,
    filters: Object,
    currentBusiness: Object,
    // Foydalanuvchi roli asosida — backend HasCurrentBusiness::detectPanelType()
    panelType: { type: String, default: 'business' },
});

// Rolga qarab tegishli layout. Har bir xodim profilidan kirsa o'z paneli ko'rinadi.
const layoutComponent = computed(() => {
    const map = {
        marketing: MarketingLayout,
        operator: OperatorLayout,
        saleshead: SalesHeadLayout,
        hr: HRLayout,
        finance: FinanceLayout,
        business: BusinessLayout,
    };
    return map[props.panelType] || BusinessLayout;
});
</script>

<template>
    <component :is="layoutComponent" :title="t('nav.inbox')">
        <Head :title="t('nav.inbox')" />
        <InboxIndex
            :conversations="conversations"
            :stats="stats"
            :filters="filters"
            :current-business="currentBusiness"
            :panel-type="panelType"
        />
    </component>
</template>
