<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import CompetitorsIndex from '@/components/Competitors/CompetitorsIndex.vue';
import CompetitorModal from '@/components/Competitors/CompetitorModal.vue';

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

const showModal = ref(false);
const editingCompetitor = ref(null);

// Route helper based on panel type
const getRoutePrefix = () => {
    return '/' + props.panelType;
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
    if (confirm(`${competitor.name} raqobatchini o'chirishni tasdiqlaysizmi?`)) {
        const prefix = getRoutePrefix();
        router.delete(`${prefix}/competitors/${competitor.id}`);
    }
};
</script>

<template>
    <component :is="layoutComponent" title="Raqobatchilar">
        <CompetitorsIndex
            :competitors="competitors"
            :stats="stats"
            :current-business="currentBusiness"
            :panel-type="panelType"
            @add="openAddModal"
            @edit="editCompetitor"
            @delete="deleteCompetitor"
        />

        <!-- Add/Edit Competitor Modal -->
        <CompetitorModal
            v-if="showModal"
            :competitor="editingCompetitor"
            :current-business="currentBusiness"
            @close="closeModal"
            @submit="submitForm"
        />
    </component>
</template>
