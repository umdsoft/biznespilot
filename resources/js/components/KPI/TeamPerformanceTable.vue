<script setup>
import { UserGroupIcon } from '@heroicons/vue/24/outline';

defineProps({
    title: { type: String, default: 'Jamoa samaradorligi' },
    members: { type: Array, default: () => [] },
    columns: {
        type: Array,
        default: () => [
            { key: 'name', label: 'Xodim' },
            { key: 'sales', label: 'Sotuvlar' },
            { key: 'calls', label: "Qo'ng'iroqlar" },
            { key: 'conversion', label: 'Konversiya' },
            { key: 'progress', label: 'Progress' },
        ],
    },
    conversionThreshold: { type: Number, default: 25 },
});

const getProgressColor = (value, target) => {
    const percentage = (value / target) * 100;
    if (percentage >= 100) return 'bg-green-500';
    if (percentage >= 75) return 'bg-emerald-500';
    if (percentage >= 50) return 'bg-yellow-500';
    return 'bg-red-500';
};

const getProgressPercentage = (value, target) => {
    return Math.min((value / target) * 100, 100);
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <UserGroupIcon class="w-5 h-5 text-emerald-600" />
                {{ title }}
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                        >
                            {{ col.label }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr
                        v-for="member in members"
                        :key="member.name"
                        class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                    >
                        <!-- Name with avatar -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white font-semibold">
                                    {{ member.avatar || member.name?.charAt(0) }}
                                </div>
                                <span class="font-medium text-gray-900 dark:text-white">{{ member.name }}</span>
                            </div>
                        </td>
                        <!-- Sales -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-gray-900 dark:text-white font-semibold">{{ member.sales }}</span>
                            <span class="text-gray-400 text-sm"> / {{ member.target }}</span>
                        </td>
                        <!-- Calls -->
                        <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-300">
                            {{ member.calls }}
                        </td>
                        <!-- Conversion -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                :class="[
                                    'px-2 py-1 text-xs font-semibold rounded-full',
                                    member.conversion >= conversionThreshold
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                                ]"
                            >
                                {{ member.conversion }}%
                            </span>
                        </td>
                        <!-- Progress -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-24 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div
                                        :class="getProgressColor(member.sales, member.target)"
                                        class="h-full rounded-full"
                                        :style="{ width: getProgressPercentage(member.sales, member.target) + '%' }"
                                    ></div>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ Math.round(getProgressPercentage(member.sales, member.target)) }}%
                                </span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-if="!members?.length" class="p-8 text-center text-gray-500 dark:text-gray-400">
            Jamoa a'zolari mavjud emas
        </div>
    </div>
</template>
