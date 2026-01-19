<template>
    <div class="space-y-3">
        <div v-if="displayLeads.length === 0" class="text-center py-8">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <p class="text-gray-500 dark:text-gray-400">Leadlar yo'q</p>
        </div>
        <div
            v-for="(lead, index) in displayLeads"
            :key="lead.id || index"
            :class="[
                'flex items-center gap-3 p-3 rounded-xl transition-all duration-200 cursor-pointer',
                'bg-gray-50 dark:bg-gray-700/50',
                'hover:bg-gray-100 dark:hover:bg-gray-700',
                'border border-transparent hover:border-gray-200 dark:hover:border-gray-600'
            ]"
            @click="$emit('click', lead)"
        >
            <!-- Avatar -->
            <div :class="[
                'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0',
                avatarColors[index % avatarColors.length]
            ]">
                <span class="font-semibold text-sm">
                    {{ getInitials(lead.name) }}
                </span>
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-900 dark:text-white truncate">{{ lead.name }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ lead.phone || lead.email }}</p>
            </div>

            <!-- Status & Value -->
            <div class="flex flex-col items-end gap-1.5">
                <span :class="statusColor(lead.status)" class="px-2.5 py-1 rounded-full text-xs font-medium">
                    {{ statusLabel(lead.status) }}
                </span>
                <span v-if="lead.estimated_value" class="text-sm font-bold text-gray-900 dark:text-white">
                    {{ formatCurrency(lead.estimated_value) }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    leads: {
        type: Array,
        default: () => [],
    },
    limit: {
        type: Number,
        default: 5,
    },
});

defineEmits(['click']);

const displayLeads = computed(() => {
    const safeLeads = Array.isArray(props.leads) ? props.leads : [];
    return props.limit > 0 ? safeLeads.slice(0, props.limit) : safeLeads;
});

const avatarColors = [
    'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400',
    'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
    'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
    'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
    'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400',
];

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
        new: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
        contacted: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
        qualified: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
        proposal: 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400',
        negotiation: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
        won: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
        lost: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
    };
    return colors[status] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400';
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
    if (value >= 1000000) {
        return (value / 1000000).toFixed(1) + 'M';
    }
    if (value >= 1000) {
        return (value / 1000).toFixed(0) + 'K';
    }
    return new Intl.NumberFormat('uz-UZ').format(value || 0);
};
</script>
