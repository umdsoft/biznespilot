<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Hodim
                    </th>
                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Leadlar
                    </th>
                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Yutilgan
                    </th>
                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        CR%
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Daromad
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="member in members" :key="member.id" class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-indigo-600 font-semibold text-xs">
                                    {{ getInitials(member.name) }}
                                </span>
                            </div>
                            <span class="font-medium text-gray-900">{{ member.name }}</span>
                        </div>
                    </td>
                    <td class="px-3 py-3 text-center whitespace-nowrap text-gray-900">
                        {{ member.leads_count || 0 }}
                    </td>
                    <td class="px-3 py-3 text-center whitespace-nowrap">
                        <span class="text-green-600 font-medium">{{ member.won_count || 0 }}</span>
                    </td>
                    <td class="px-3 py-3 text-center whitespace-nowrap">
                        <span :class="getCRColor(member.conversion_rate)" class="font-medium">
                            {{ member.conversion_rate || 0 }}%
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap font-semibold text-gray-900">
                        {{ formatCurrency(member.revenue) }}
                    </td>
                </tr>
            </tbody>
        </table>
        <div v-if="members.length === 0" class="text-center py-8 text-gray-500">
            Jamoa a'zolari yo'q
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
    return name.split(' ').map((n) => n[0]).join('').toUpperCase().substring(0, 2);
};

const getCRColor = (cr) => {
    if (cr >= 30) return 'text-green-600';
    if (cr >= 20) return 'text-yellow-600';
    return 'text-red-600';
};

const formatCurrency = (value) => {
    if (!value) return '0 so\'m';
    if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
    if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
    return new Intl.NumberFormat('uz-UZ').format(value) + ' so\'m';
};
</script>
