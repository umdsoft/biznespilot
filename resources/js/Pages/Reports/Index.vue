<template>
    <BusinessLayout title="Hisobotlar">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Page Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">Hisobotlar</h2>
                                <p class="mt-1 text-sm text-gray-600">Biznesingiz bo'yicha to'liq tahlil va statistika</p>
                            </div>
                        </div>
                    </div>

                    <!-- Date Range Filter -->
                    <div class="p-6 bg-gray-50">
                        <div class="flex items-center gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Boshlanish sanasi</label>
                                <input
                                    type="date"
                                    v-model="filters.start_date"
                                    @change="applyFilters"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tugash sanasi</label>
                                <input
                                    type="date"
                                    v-model="filters.end_date"
                                    @change="applyFilters"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                            </div>
                            <div class="mt-6">
                                <button
                                    @click="resetFilters"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition"
                                >
                                    Tozalash
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overview Stats -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Jami sotuvlar</dt>
                                        <dd class="text-2xl font-semibold text-gray-900">{{ stats.total_sales }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8h6m-5 0a3 3 0 110 6H9l3 3m-3-6h6m6 1a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Jami daromad</dt>
                                        <dd class="text-2xl font-semibold text-gray-900">{{ formatCurrency(stats.total_revenue) }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-purple-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Orzuidagi xaridorlar</dt>
                                        <dd class="text-2xl font-semibold text-gray-900">{{ stats.dream_buyers }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Faol takliflar</dt>
                                        <dd class="text-2xl font-semibold text-gray-900">{{ stats.active_offers }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-indigo-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Marketing kanallari</dt>
                                        <dd class="text-2xl font-semibold text-gray-900">{{ stats.marketing_channels }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-red-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Kuzatilayotgan raqiblar</dt>
                                        <dd class="text-2xl font-semibold text-gray-900">{{ stats.competitors_tracked }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Trend Chart -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Sotuvlar tendensiyasi</h3>
                        <div v-if="salesTrend.length > 0" class="space-y-4">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sana</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sotuvlar soni</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Daromad</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="item in salesTrend" :key="item.date">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(item.date) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.count }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatCurrency(item.revenue) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500">
                            Ma'lumotlar topilmadi
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Sales by Status -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Sotuvlar holati bo'yicha</h3>
                            <div v-if="salesByStatus.length > 0" class="space-y-3">
                                <div v-for="item in salesByStatus" :key="item.status" class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
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
                                        <span class="text-sm font-medium text-gray-900 capitalize">{{ item.status }}</span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-700">{{ item.count }}</span>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500">
                                Ma'lumotlar topilmadi
                            </div>
                        </div>
                    </div>

                    <!-- Marketing Content Stats -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Marketing kontent statistikasi</h3>
                            <div v-if="contentStats.length > 0" class="space-y-3">
                                <div v-for="item in contentStats" :key="item.type" class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                                    <span class="text-sm font-medium text-gray-900 capitalize">{{ item.type }}</span>
                                    <span class="text-sm font-semibold text-gray-700">{{ item.count }}</span>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500">
                                Ma'lumotlar topilmadi
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Marketing Channels -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Eng yaxshi marketing kanallari</h3>
                        <div v-if="topChannels.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nomi</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Platforma</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Oylik qamrov</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Engagement (%)</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="channel in topChannels" :key="channel.name">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ channel.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 capitalize">{{ channel.platform }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatNumber(channel.monthly_reach) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ channel.engagement_rate }}%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500">
                            Ma'lumotlar topilmadi
                        </div>
                    </div>
                </div>

                <!-- Offers Performance -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Takliflar ko'rsatkichlari</h3>
                        <div v-if="offersPerformance.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div v-for="item in offersPerformance" :key="item.status" class="p-4 bg-gray-50 rounded-lg">
                                <div class="text-sm text-gray-500 capitalize mb-1">{{ item.status }}</div>
                                <div class="text-2xl font-bold text-gray-900 mb-1">{{ item.count }}</div>
                                <div class="text-xs text-gray-600">O'rtacha konversiya: {{ item.avg_conversion }}%</div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500">
                            Ma'lumotlar topilmadi
                        </div>
                    </div>
                </div>

                <!-- Competitor Threat Analysis -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Raqiblar tahlili</h3>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <div class="text-sm text-gray-500 mb-1">Jami raqiblar</div>
                                <div class="text-2xl font-bold text-gray-900">{{ competitorStats.total }}</div>
                            </div>
                            <div class="p-4 bg-red-50 rounded-lg">
                                <div class="text-sm text-red-600 mb-1">Yuqori xavf</div>
                                <div class="text-2xl font-bold text-red-700">{{ competitorStats.high_threat }}</div>
                            </div>
                            <div class="p-4 bg-yellow-50 rounded-lg">
                                <div class="text-sm text-yellow-600 mb-1">O'rta xavf</div>
                                <div class="text-2xl font-bold text-yellow-700">{{ competitorStats.medium_threat }}</div>
                            </div>
                            <div class="p-4 bg-green-50 rounded-lg">
                                <div class="text-sm text-green-600 mb-1">Past xavf</div>
                                <div class="text-2xl font-bold text-green-700">{{ competitorStats.low_threat }}</div>
                            </div>
                        </div>
                        <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                            <div class="text-sm text-blue-600">O'rtacha xavf darajasi</div>
                            <div class="text-2xl font-bold text-blue-700">{{ competitorStats.avg_threat }} / 10</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';

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
    if (!value) return '0 so\'m';
    return new Intl.NumberFormat('uz-UZ').format(value) + ' so\'m';
};

const formatNumber = (value) => {
    if (!value) return '0';
    return new Intl.NumberFormat('uz-UZ').format(value);
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('uz-UZ', { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>
