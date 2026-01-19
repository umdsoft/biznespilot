<template>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Strengths -->
        <SwotCard
            type="strengths"
            :title="t('swot.grid.strengths_title')"
            :subtitle="isBusiness ? t('swot.grid.strengths_business') : t('swot.grid.strengths_competitor')"
            :items="localSwot.strengths"
            color="green"
            :placeholder="isBusiness ? t('swot.grid.strengths_placeholder_business') : t('swot.grid.strengths_placeholder_competitor')"
            :current-business-id="currentBusinessId"
            @add="addItem('strengths', $event)"
            @remove="removeItem('strengths', $event)"
            @edit="editItem('strengths', $event.index, $event.value)"
        />

        <!-- Weaknesses -->
        <SwotCard
            type="weaknesses"
            :title="t('swot.grid.weaknesses_title')"
            :subtitle="isBusiness ? t('swot.grid.weaknesses_business') : t('swot.grid.weaknesses_competitor')"
            :items="localSwot.weaknesses"
            color="red"
            :placeholder="isBusiness ? t('swot.grid.weaknesses_placeholder_business') : t('swot.grid.weaknesses_placeholder_competitor')"
            :current-business-id="currentBusinessId"
            @add="addItem('weaknesses', $event)"
            @remove="removeItem('weaknesses', $event)"
            @edit="editItem('weaknesses', $event.index, $event.value)"
        />

        <!-- Opportunities -->
        <SwotCard
            type="opportunities"
            :title="t('swot.grid.opportunities_title')"
            :subtitle="isBusiness ? t('swot.grid.opportunities_business') : t('swot.grid.opportunities_competitor')"
            :items="localSwot.opportunities"
            color="blue"
            :placeholder="isBusiness ? t('swot.grid.opportunities_placeholder_business') : t('swot.grid.opportunities_placeholder_competitor')"
            :current-business-id="currentBusinessId"
            @add="addItem('opportunities', $event)"
            @remove="removeItem('opportunities', $event)"
            @edit="editItem('opportunities', $event.index, $event.value)"
        />

        <!-- Threats -->
        <SwotCard
            type="threats"
            :title="t('swot.grid.threats_title')"
            :subtitle="isBusiness ? t('swot.grid.threats_business') : t('swot.grid.threats_competitor')"
            :items="localSwot.threats"
            color="orange"
            :placeholder="isBusiness ? t('swot.grid.threats_placeholder_business') : t('swot.grid.threats_placeholder_competitor')"
            :current-business-id="currentBusinessId"
            @add="addItem('threats', $event)"
            @remove="removeItem('threats', $event)"
            @edit="editItem('threats', $event.index, $event.value)"
        />
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import SwotCard from './SwotCard.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    swot: {
        type: Object,
        default: () => ({}),
    },
    isBusiness: {
        type: Boolean,
        default: true,
    },
    currentBusinessId: {
        type: [Number, String],
        default: null,
    },
});

const emit = defineEmits(['update']);

// Helper to normalize items (handle both string and object format)
const normalizeItems = (items) => {
    if (!items || !Array.isArray(items)) return [];
    return items.map(item => {
        if (typeof item === 'string') {
            return item;
        }
        return item;
    });
};

// Local copy of swot data
const localSwot = ref({
    strengths: normalizeItems(props.swot?.strengths),
    weaknesses: normalizeItems(props.swot?.weaknesses),
    opportunities: normalizeItems(props.swot?.opportunities),
    threats: normalizeItems(props.swot?.threats),
});

// Watch for external changes
watch(() => props.swot, (newSwot) => {
    localSwot.value = {
        strengths: normalizeItems(newSwot?.strengths),
        weaknesses: normalizeItems(newSwot?.weaknesses),
        opportunities: normalizeItems(newSwot?.opportunities),
        threats: normalizeItems(newSwot?.threats),
    };
}, { deep: true });

const addItem = (type, value) => {
    if (value.trim()) {
        // Add item with business_id for contributor tracking
        const newItem = {
            text: value.trim(),
            business_id: props.currentBusinessId,
        };
        localSwot.value[type].push(newItem);
        emitUpdate();
    }
};

const removeItem = (type, index) => {
    localSwot.value[type].splice(index, 1);
    emitUpdate();
};

const editItem = (type, index, value) => {
    if (value.trim()) {
        const currentItem = localSwot.value[type][index];
        // Preserve business_id if it exists, otherwise use current business
        const businessId = (typeof currentItem === 'object' && currentItem.business_id)
            ? currentItem.business_id
            : props.currentBusinessId;

        localSwot.value[type][index] = {
            text: value.trim(),
            business_id: businessId,
        };
        emitUpdate();
    }
};

const emitUpdate = () => {
    emit('update', { ...localSwot.value });
};
</script>
