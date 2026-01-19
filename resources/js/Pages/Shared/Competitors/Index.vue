<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import CompetitorsIndex from '@/components/Competitors/CompetitorsIndex.vue';
import CompetitorModal from '@/components/Competitors/CompetitorModal.vue';

const { t } = useI18n();

const props = defineProps({
    competitors: { type: [Array, Object], default: () => [] },
    stats: { type: Object, default: () => ({}) },
    currentBusiness: { type: Object, default: null },
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

// SalesHead panel faqat ko'rish uchun (read-only)
const isReadOnly = computed(() => props.panelType === 'saleshead');

const showModal = ref(false);
const editingCompetitor = ref(null);

// Route helper based on panel type (saleshead -> sales-head URL conversion)
const getRoutePrefix = () => {
    const prefix = props.panelType === 'saleshead' ? 'sales-head' : props.panelType;
    return '/' + prefix;
};

const openAddModal = () => {
    editingCompetitor.value = null;
    showModal.value = true;
};

const editCompetitor = (competitor) => {
    editingCompetitor.value = competitor;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingCompetitor.value = null;
};

const submitForm = (formData) => {
    const prefix = getRoutePrefix();

    if (editingCompetitor.value) {
        router.put(`${prefix}/competitors/${editingCompetitor.value.id}`, formData, {
            onSuccess: () => closeModal(),
        });
    } else {
        router.post(`${prefix}/competitors`, formData, {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteCompetitor = (competitor) => {
    if (confirm(t('competitors.confirm_delete', { name: competitor.name }))) {
        const prefix = getRoutePrefix();
        router.delete(`${prefix}/competitors/${competitor.id}`);
    }
};
</script>

<template>
    <component :is="layoutComponent" :title="t('competitors.title')">
        <CompetitorsIndex
            :competitors="competitors"
            :stats="stats"
            :current-business="currentBusiness"
            :panel-type="panelType"
            :read-only="isReadOnly"
            @add="openAddModal"
            @edit="editCompetitor"
            @delete="deleteCompetitor"
        />

        <!-- Add/Edit Competitor Modal (faqat read-only bo'lmagan panellarda) -->
        <CompetitorModal
            v-if="showModal && !isReadOnly"
            :competitor="editingCompetitor"
            :current-business="currentBusiness"
            @close="closeModal"
            @submit="submitForm"
        />
    </component>
</template>
