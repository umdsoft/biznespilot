<script setup>
defineProps({
    title: { type: String, default: "Haftalik Ko'rsatkichlar" },
    data: { type: Array, default: () => [] },
    columns: {
        type: Array,
        default: () => [
            { key: 'day', label: 'Kun', align: 'left' },
            { key: 'calls', label: "Qo'ng'iroqlar", align: 'center' },
            { key: 'leads', label: 'Leadlar', align: 'center' },
            { key: 'conversion', label: 'Konversiya', align: 'center', badge: true },
            { key: 'response_time', label: 'Javob vaqti', align: 'center', format: 'time' },
        ],
    },
    conversionTarget: { type: Number, default: 0 },
});

const formatTime = (minutes) => {
    if (!minutes) return '0 daqiqa';
    if (minutes < 60) return `${minutes} daqiqa`;
    return `${Math.floor(minutes / 60)} soat ${minutes % 60} daqiqa`;
};

const formatValue = (value, format) => {
    if (format === 'time') return formatTime(value);
    return value;
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ title }}</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-sm text-gray-500 dark:text-gray-400">
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            :class="['pb-3 font-medium', col.align === 'center' ? 'text-center' : '']"
                        >
                            {{ col.label }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <tr v-for="(row, index) in data" :key="index" class="text-sm">
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            :class="[
                                'py-3',
                                col.align === 'center' ? 'text-center' : '',
                                col.key === columns[0].key ? 'font-medium text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-300'
                            ]"
                        >
                            <span
                                v-if="col.badge && col.key === 'conversion'"
                                :class="[
                                    'px-2 py-1 rounded-full text-xs font-medium',
                                    row[col.key] >= conversionTarget ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                ]"
                            >
                                {{ row[col.key] }}%
                            </span>
                            <template v-else>
                                {{ formatValue(row[col.key], col.format) }}
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-if="!data?.length" class="py-8 text-center text-gray-500 dark:text-gray-400">
            Ma'lumot mavjud emas
        </div>
    </div>
</template>
