<template>
    <div class="space-y-3">
        <div v-if="members.length === 0" class="text-center py-8 text-gray-500">
            Jamoa a'zolari yo'q
        </div>
        <div
            v-for="member in members"
            :key="member.id"
            class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg"
        >
            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-indigo-600 font-semibold text-sm">
                    {{ getInitials(member.name) }}
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-900 truncate">{{ member.name }}</p>
                <div class="flex items-center gap-4 text-sm text-gray-500">
                    <span>{{ member.leads_count || 0 }} lead</span>
                    <span>{{ member.won_count || 0 }} yutildi</span>
                    <span class="text-green-600 font-medium">{{ member.conversion_rate || 0 }}% CR</span>
                </div>
            </div>
            <div class="text-right">
                <p class="font-semibold text-gray-900">{{ formatCurrency(member.revenue || 0) }}</p>
                <p class="text-xs text-gray-500">daromad</p>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    members: {
        type: Array,
        default: () => [],
    },
});

const getInitials = (name) => {
    if (!name) return 'U';
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};

const formatCurrency = (value) => {
    if (value >= 1000000) {
        return (value / 1000000).toFixed(1) + 'M';
    }
    if (value >= 1000) {
        return (value / 1000).toFixed(0) + 'K';
    }
    return new Intl.NumberFormat('uz-UZ').format(value || 0) + ' so\'m';
};
</script>
