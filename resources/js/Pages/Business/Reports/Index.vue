<template>
    <BusinessLayout title="Hisobotlar">
        <div class="mb-8">
            <!-- Page Header -->
            <div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Hisobotlar</h2>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Biznesingiz bo'yicha to'liq tahlil va statistika</p>
            </div>

            <!-- Date Range Filter -->
            <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Boshlanish sanasi</label>
                        <input
                            type="date"
                            v-model="filters.start_date"
                            @change="applyFilters"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-opacity-50 transition-colors"
                        >
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tugash sanasi</label>
                        <input
                            type="date"
                            v-model="filters.end_date"
                            @change="applyFilters"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-opacity-50 transition-colors"
                        >
                    </div>
                    <div>
                        <button
                            @click="resetFilters"
                            class="inline-flex items-center px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200 shadow-sm border border-gray-200 dark:border-gray-600"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Tozalash
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overview Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            <!-- Total Sales -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-blue-100 text-sm font-medium mb-1">Jami sotuvlar</p>
                <p class="text-3xl font-bold">{{ stats.total_sales }}</p>
            </div>

            <!-- Total Revenue -->
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8h6m-5 0a3 3 0 110 6H9l3 3m-3-6h6m6 1a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-green-100 text-sm font-medium mb-1">Jami daromad</p>
                <p class="text-3xl font-bold">{{ formatCurrency(stats.total_revenue) }}</p>
            </div>

            <!-- Dream Buyers -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-purple-100 text-sm font-medium mb-1">Orzuidagi xaridorlar</p>
                <p class="text-3xl font-bold">{{ stats.dream_buyers }}</p>
            </div>

            <!-- Active Offers -->
            <div class="bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                </div>
                <p class="text-yellow-100 text-sm font-medium mb-1">Faol takliflar</p>
                <p class="text-3xl font-bold">{{ stats.active_offers }}</p>
            </div>

            <!-- Marketing Channels -->
            <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                    </div>
                </div>
                <p class="text-indigo-100 text-sm font-medium mb-1">Marketing kanallari</p>
                <p class="text-3xl font-bold">{{ stats.marketing_channels }}</p>
            </div>

            <!-- Competitors -->
            <div class="bg-gradient-to-br from-red-500 to-pink-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
                <p class="text-red-100 text-sm font-medium mb-1">Kuzatilayotgan raqiblar</p>
                <p class="text-3xl font-bold">{{ stats.competitors_tracked }}</p>
            </div>
        </div>

        <!-- Sales Trend Chart -->
        <Card title="Sotuvlar tendensiyasi" class="mb-6">
            <div v-if="salesTrend.length > 0" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sana</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sotuvlar soni</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Daromad</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="item in salesTrend" :key="item.date" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ formatDate(item.date) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-semibold">{{ item.count }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-semibold">{{ formatCurrency(item.revenue) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="text-center py-12">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <p class="text-gray-500 dark:text-gray-400">Ma'lumotlar topilmadi</p>
            </div>
        </Card>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Sales by Status -->
            <Card title="Sotuvlar holati bo'yicha">
                <div v-if="salesByStatus.length > 0" class="space-y-3">
                    <div v-for="item in salesByStatus" :key="item.status" class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <div class="flex items-center">
                            <span
                                class="w-3 h-3 rounded-full mr-3"
                                :class="{
                                    'bg-green-500': item.status === 'completed',
                                    'bg-yellow-500': item.status === 'pending',
                                    'bg-red-500': item.status === 'cancelled',
                                    'bg-blue-500': item.status === 'processing'
                                }"
                            ></span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100 capitalize">{{ item.status }}</span>
                        </div>
                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ item.count }}</span>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                    Ma'lumotlar topilmadi
                </div>
            </Card>

            <!-- Marketing Content Stats -->
            <Card title="Marketing kontent statistikasi">
                <div v-if="contentStats.length > 0" class="space-y-3">
                    <div v-for="item in contentStats" :key="item.type" class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100 capitalize">{{ item.type }}</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ item.count }}</span>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                    Ma'lumotlar topilmadi
                </div>
            </Card>
        </div>

        <!-- Top Marketing Channels -->
        <Card title="Eng yaxshi marketing kanallari" class="mb-6">
            <div v-if="topChannels.length > 0" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nomi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Platforma</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Oylik byudjet</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="channel in topChannels" :key="channel.name" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ channel.name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 capitalize">
                                <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-md text-xs font-medium">
                                    {{ channel.platform }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-semibold">{{ formatCurrency(channel.monthly_budget) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="text-center py-12">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
                <p class="text-gray-500 dark:text-gray-400">Ma'lumotlar topilmadi</p>
            </div>
        </Card>

        <!-- Offers Performance -->
        <Card title="Takliflar ko'rsatkichlari" class="mb-6">
            <div v-if="offersPerformance.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div v-for="item in offersPerformance" :key="item.status" class="p-5 bg-gray-50 dark:bg-gray-700 rounded-lg hover:shadow-md transition-all">
                    <div class="text-sm text-gray-500 dark:text-gray-400 capitalize mb-2">{{ item.status }}</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">{{ item.count }}</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">O'rtacha konversiya: {{ item.avg_conversion }}%</div>
                </div>
            </div>
            <div v-else class="text-center py-12">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                <p class="text-gray-500 dark:text-gray-400">Ma'lumotlar topilmadi</p>
            </div>
        </Card>

        <!-- Competitor Analysis -->
        <Card title="Raqiblar tahlili">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 bg-gray-50 dark:bg-gray-700 rounded-lg hover:shadow-md transition-all">
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Jami raqiblar</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ competitorStats.total }}</div>
                </div>
            </div>
        </Card>
    </BusinessLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import Card from '@/Components/Card.vue';

const props = defineProps({
    stats: Object,
    salesTrend: Array,
    salesByStatus: Array,
    topChannels: Array,
    contentStats: Array,
    offersPerformance: Array,
    competitorStats: Object,
    dateRange: Object,
});

const filters = reactive({
    start_date: props.dateRange.start,
    end_date: props.dateRange.end,
});

const applyFilters = () => {
    router.get(route('business.reports.index'), filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    const today = new Date();
    const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));

    filters.start_date = thirtyDaysAgo.toISOString().split('T')[0];
    filters.end_date = today.toISOString().split('T')[0];

    applyFilters();
};

const formatCurrency = (value) => {
    if (!value || isNaN(value)) return '0 so\'m';
    return new Intl.NumberFormat('uz-UZ', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value) + ' so\'m';
};

const formatNumber = (value) => {
    if (!value || isNaN(value)) return '0';
    return new Intl.NumberFormat('uz-UZ').format(value);
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('uz-UZ', { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>
