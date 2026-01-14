<template>
    <div class="space-y-3">
        <div v-if="leads.length === 0" class="text-center py-8 text-gray-500">
            Leadlar yo'q
        </div>
        <div
            v-for="lead in leads"
            :key="lead.id"
            class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
            @click="$emit('click', lead)"
        >
            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-indigo-600 font-semibold text-sm">
                    {{ getInitials(lead.name) }}
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-900 truncate">{{ lead.name }}</p>
                <p class="text-sm text-gray-500 truncate">{{ lead.phone || lead.email }}</p>
            </div>
            <div class="flex flex-col items-end gap-1">
                <span
                    :class="statusColor(lead.status)"
                    class="px-2 py-0.5 rounded-full text-xs font-medium"
                >
                    {{ statusLabel(lead.status) }}
                </span>
                <span v-if="lead.estimated_value" class="text-sm font-medium text-gray-900">
                    {{ formatCurrency(lead.estimated_value) }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    leads: {
        type: Array,
        default: () => [],
    },
});

defineEmits(['click']);

const getInitials = (name) => {
    if (!name) return 'L';
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};

const statusColor = (status) => {
    const colors = {
        new: 'bg-blue-100 text-blue-700',
        contacted: 'bg-yellow-100 text-yellow-700',
        qualified: 'bg-purple-100 text-purple-700',
        proposal: 'bg-indigo-100 text-indigo-700',
        negotiation: 'bg-orange-100 text-orange-700',
        won: 'bg-green-100 text-green-700',
        lost: 'bg-red-100 text-red-700',
    };
    return colors[status] || 'bg-gray-100 text-gray-700';
};

const statusLabel = (status) => {
    const labels = {
        new: 'Yangi',
        contacted: 'Bog\'lanildi',
        qualified: 'Malakali',
        proposal: 'Taklif',
        negotiation: 'Muzokara',
        won: 'Yutildi',
        lost: 'Yo\'qotildi',
    };
    return labels[status] || status;
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('uz-UZ').format(value || 0) + ' so\'m';
};
</script>
