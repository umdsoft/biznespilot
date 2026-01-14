<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Ko'rsatkich
                    </th>
                    <th
                        v-for="day in weekDays"
                        :key="day"
                        class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                    >
                        {{ day }}
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Jami
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="(row, index) in data" :key="row.id || index" class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="font-medium text-gray-900">{{ row.label }}</span>
                    </td>
                    <td
                        v-for="(value, dayIndex) in row.values"
                        :key="dayIndex"
                        class="px-3 py-3 text-center whitespace-nowrap"
                    >
                        <span :class="getCellColor(value, row.target)">
                            {{ formatValue(value, row.format) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <span class="font-semibold text-gray-900">
                            {{ formatValue(getTotal(row.values), row.format) }}
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
        <div v-if="data.length === 0" class="text-center py-8 text-gray-500">
            Ma'lumot yo'q
        </div>
    </div>
</template>

<script setup>
defineProps({
    data: {
        type: Array,
        default: () => [],
    },
    weekDays: {
        type: Array,
        default: () => ['Dush', 'Sesh', 'Chor', 'Pay', 'Jum', 'Shan', 'Yak'],
    },
});

const formatValue = (value, format) => {
    if (format === 'currency') {
        if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
        if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
        return new Intl.NumberFormat('uz-UZ').format(value || 0);
    }
    if (format === 'percent') {
        return (value || 0) + '%';
    }
    return value || 0;
};

const getTotal = (values) => {
    if (!values || !Array.isArray(values)) return 0;
    return values.reduce((sum, val) => sum + (val || 0), 0);
};

const getCellColor = (value, target) => {
    if (!target) return 'text-gray-900';
    const percentage = (value / target) * 100;
    if (percentage >= 100) return 'text-green-600 font-medium';
    if (percentage >= 70) return 'text-yellow-600';
    return 'text-red-600';
};
</script>
