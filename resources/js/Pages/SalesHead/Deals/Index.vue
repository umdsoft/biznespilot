<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import {
    CurrencyDollarIcon,
    CheckCircleIcon,
    ClockIcon,
    EyeIcon,
    MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    deals: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            won: 0,
            lost: 0,
            total_value: 0,
        }),
    },
});

const searchQuery = ref('');
const statusFilter = ref('');

const filteredDeals = computed(() => {
    let result = props.deals;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(d => d.name?.toLowerCase().includes(q) || d.lead?.name?.toLowerCase().includes(q));
    }
    if (statusFilter.value) {
        result = result.filter(d => d.status === statusFilter.value);
    }
    return result;
});

const formatCurrency = (amount) => {
    if (!amount) return '0 so\'m';
    return new Intl.NumberFormat('uz-UZ').format(amount) + ' so\'m';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('uz-UZ');
};

const getStatusColor = (status) => {
    const colors = {
        won: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        lost: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    };
    return colors[status] || 'bg-gray-100 text-gray-700';
};

const getStatusLabel = (status) => {
    const labels = { won: 'Yutildi', lost: "Yo'qotildi", pending: 'Kutilmoqda' };
    return labels[status] || status;
};
</script>

<template>
    <SalesHeadLayout title="Bitimlar">
        <Head title="Bitimlar" />

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Bitimlar</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Yopilgan va faol bitimlar ro'yxati</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <CurrencyDollarIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jami bitimlar</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <CheckCircleIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Yutilgan</p>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ stats.won }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                            <ClockIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Yo'qotilgan</p>
                            <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ stats.lost }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                            <CurrencyDollarIcon class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jami summa</p>
                            <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(stats.total_value) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="relative flex-1">
                    <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Bitim qidirish..."
                        class="w-full pl-10 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white"
                    />
                </div>
                <select
                    v-model="statusFilter"
                    class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white"
                >
                    <option value="">Barcha statuslar</option>
                    <option value="won">Yutilgan</option>
                    <option value="lost">Yo'qotilgan</option>
                    <option value="pending">Kutilmoqda</option>
                </select>
            </div>

            <!-- Deals Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div v-if="filteredDeals.length === 0" class="p-12 text-center">
                    <CurrencyDollarIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Bitim topilmadi</h3>
                    <p class="text-gray-500 dark:text-gray-400">Hali bitimlar yo'q</p>
                </div>

                <table v-else class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Bitim</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Lead</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Summa</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Sana</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Amallar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="deal in filteredDeals" :key="deal.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ deal.name }}</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ deal.lead?.name || '-' }}</td>
                            <td class="px-6 py-4 font-semibold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(deal.value) }}</td>
                            <td class="px-6 py-4">
                                <span :class="[getStatusColor(deal.status), 'px-2.5 py-1 rounded-full text-xs font-medium']">
                                    {{ getStatusLabel(deal.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ formatDate(deal.closed_at) }}</td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="`/sales-head/deals/${deal.id}`" class="p-2 text-gray-400 hover:text-emerald-600 inline-block">
                                    <EyeIcon class="w-5 h-5" />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </SalesHeadLayout>
</template>
