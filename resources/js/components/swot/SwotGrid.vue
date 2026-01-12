<template>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Strengths -->
        <SwotCard
            type="strengths"
            title="Kuchli Tomonlar"
            :subtitle="isBusiness ? 'Sizning afzalliklaringiz' : 'Raqobatchining kuchli tomonlari'"
            :items="localSwot.strengths"
            color="green"
            :placeholder="isBusiness ? 'Masalan: Sifatli mahsulot, Tajribali jamoa...' : 'Masalan: Kuchli brend, Keng mijozlar bazasi...'"
            :current-business-id="currentBusinessId"
            @add="addItem('strengths', $event)"
            @remove="removeItem('strengths', $event)"
            @edit="editItem('strengths', $event.index, $event.value)"
        />

        <!-- Weaknesses -->
        <SwotCard
            type="weaknesses"
            title="Zaif Tomonlar"
            :subtitle="isBusiness ? 'Yaxshilash kerak bo\'lgan joylar' : 'Raqobatchining zaif tomonlari'"
            :items="localSwot.weaknesses"
            color="red"
            :placeholder="isBusiness ? 'Masalan: Marketing byudjeti kam, Onlayn mavjudlik past...' : 'Masalan: Narxlari yuqori, Xizmat sifati past...'"
            :current-business-id="currentBusinessId"
            @add="addItem('weaknesses', $event)"
            @remove="removeItem('weaknesses', $event)"
            @edit="editItem('weaknesses', $event.index, $event.value)"
        />

        <!-- Opportunities -->
        <SwotCard
            type="opportunities"
            title="Imkoniyatlar"
            :subtitle="isBusiness ? 'O\'sish imkoniyatlari' : 'Raqobatchidan o\'rganish'"
            :items="localSwot.opportunities"
            color="blue"
            :placeholder="isBusiness ? 'Masalan: Yangi bozorlar, Onlayn savdo...' : 'Masalan: Ularning marketing usullari, Mahsulot liniyasi...'"
            :current-business-id="currentBusinessId"
            @add="addItem('opportunities', $event)"
            @remove="removeItem('opportunities', $event)"
            @edit="editItem('opportunities', $event.index, $event.value)"
        />

        <!-- Threats -->
        <SwotCard
            type="threats"
            title="Tahdidlar"
            :subtitle="isBusiness ? 'Ehtiyot bo\'lish kerak' : 'Raqobatchidan keladigan xavf'"
            :items="localSwot.threats"
            color="orange"
            :placeholder="isBusiness ? 'Masalan: Yangi raqobatchilar, Narx urushi...' : 'Masalan: Kuchli reklama, Tajribali jamoa...'"
            :current-business-id="currentBusinessId"
            @add="addItem('threats', $event)"
            @remove="removeItem('threats', $event)"
            @edit="editItem('threats', $event.index, $event.value)"
        />
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import SwotCard from './SwotCard.vue';

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
