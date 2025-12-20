<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({
    business: Object,
    analysis: Object,
    lastUpdated: String,
    error: String,
});

const page = usePage();
const loading = ref(false);
const refreshing = ref(false);
const activeTab = ref('overview');
const showAIInsights = ref(false);
const generatingInsights = ref(false);
const aiInsights = ref(null);

// Computed properties for formatted data
const formattedRevenue = computed(() => {
    return new Intl.NumberFormat('uz-UZ').format(props.analysis?.overview?.total_revenue || 0);
});

const formattedAvgLTV = computed(() => {
    return new Intl.NumberFormat('uz-UZ').format(props.analysis?.overview?.avg_ltv || 0);
});

const formattedAvgOrderValue = computed(() => {
    return new Intl.NumberFormat('uz-UZ').format(props.analysis?.overview?.avg_order_value || 0);
});

// Methods
const refreshAnalysis = async () => {
    refreshing.value = true;
    try {
        const response = await axios.get('/api/target-analysis', {
            params: { business_id: props.business.id }
        });

        if (response.data.success) {
            // Reload page to update data
            window.location.reload();
        }
    } catch (error) {
        console.error('Error refreshing analysis:', error);
    } finally {
        refreshing.value = false;
    }
};

const generateAIInsights = async () => {
    generatingInsights.value = true;
    showAIInsights.value = true;

    try {
        const response = await axios.post('/api/target-analysis/insights/regenerate', {
            business_id: props.business.id
        });

        if (response.data.success) {
            aiInsights.value = response.data.insights;
        }
    } catch (error) {
        console.error('Error generating AI insights:', error);
        aiInsights.value = {
            success: false,
            insights: 'AI tahlil yaratishda xatolik yuz berdi'
        };
    } finally {
        generatingInsights.value = false;
    }
};

const exportAnalysis = async () => {
    try {
        const response = await axios.get('/api/target-analysis/export', {
            params: { business_id: props.business.id },
            responseType: 'blob'
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `target-analysis-${Date.now()}.json`);
        document.body.appendChild(link);
        link.click();
        link.remove();
    } catch (error) {
        console.error('Error exporting analysis:', error);
    }
};

onMounted(() => {
    if (props.analysis?.ai_insights) {
        aiInsights.value = props.analysis.ai_insights;
    }
});
</script>

<template>
    <Head title="Target Tahlili" />

    <BusinessLayout>
        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Target Tahlili</h1>
                            <p class="mt-2 text-gray-600">
                                <span v-if="business">{{ business.name }}</span>
                                <span v-if="lastUpdated" class="ml-2 text-sm">• Oxirgi yangilanish: {{ lastUpdated }}</span>
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <button
                                @click="generateAIInsights"
                                :disabled="generatingInsights"
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 flex items-center gap-2"
                            >
                                <svg v-if="!generatingInsights" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                                <svg v-else class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ generatingInsights ? 'Tahlil qilinmoqda...' : 'AI Tahlil' }}
                            </button>
                            <button
                                @click="exportAnalysis"
                                class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 flex items-center gap-2"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Export
                            </button>
                            <button
                                @click="refreshAnalysis"
                                :disabled="refreshing"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2"
                            >
                                <svg :class="{ 'animate-spin': refreshing }" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Yangilash
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Error State -->
                <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                    <p class="text-red-800">{{ error }}</p>
                </div>

                <!-- Main Content -->
                <div v-else-if="analysis">
                    <!-- AI Insights Modal -->
                    <div v-if="showAIInsights" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                        <div class="bg-white rounded-xl max-w-4xl w-full max-h-[80vh] overflow-y-auto">
                            <div class="p-6 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white">
                                <h3 class="text-xl font-bold text-gray-900">AI Tahlil Natijalari</h3>
                                <button @click="showAIInsights = false" class="text-gray-500 hover:text-gray-700">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div class="p-6">
                                <div v-if="generatingInsights" class="text-center py-12">
                                    <svg class="animate-spin h-12 w-12 mx-auto mb-4 text-purple-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <p class="text-gray-600">AI tahlil yaratilmoqda...</p>
                                </div>
                                <div v-else-if="aiInsights" class="prose max-w-none">
                                    <div class="whitespace-pre-wrap text-gray-800">{{ aiInsights.insights }}</div>
                                    <p class="text-sm text-gray-500 mt-4" v-if="aiInsights.generated_at">
                                        Yaratilgan: {{ aiInsights.generated_at }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="-mb-px flex space-x-8">
                            <button
                                @click="activeTab = 'overview'"
                                :class="[
                                    activeTab === 'overview'
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                Umumiy Ko'rinish
                            </button>
                            <button
                                @click="activeTab = 'dreambuyer'"
                                :class="[
                                    activeTab === 'dreambuyer'
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                Dream Buyer Moslik
                            </button>
                            <button
                                @click="activeTab = 'segments'"
                                :class="[
                                    activeTab === 'segments'
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                Segmentatsiya
                            </button>
                            <button
                                @click="activeTab = 'funnel'"
                                :class="[
                                    activeTab === 'funnel'
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                Konversiya Voronkasi
                            </button>
                            <button
                                @click="activeTab = 'churn'"
                                :class="[
                                    activeTab === 'churn'
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                Churn Tahlili
                            </button>
                        </nav>
                    </div>

                    <!-- Overview Tab -->
                    <div v-show="activeTab === 'overview'" class="space-y-6">
                        <!-- Stats Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Jami Mijozlar</p>
                                        <p class="text-3xl font-bold text-gray-900 mt-2">
                                            {{ analysis.overview.total_customers }}
                                        </p>
                                        <p class="text-sm text-green-600 mt-1">
                                            {{ analysis.overview.active_customers }} faol
                                        </p>
                                    </div>
                                    <div class="bg-blue-100 p-3 rounded-lg">
                                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Umumiy Daromad</p>
                                        <p class="text-2xl font-bold text-gray-900 mt-2">
                                            {{ formattedRevenue }} so'm
                                        </p>
                                    </div>
                                    <div class="bg-green-100 p-3 rounded-lg">
                                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">O'rtacha LTV</p>
                                        <p class="text-2xl font-bold text-gray-900 mt-2">
                                            {{ formattedAvgLTV }} so'm
                                        </p>
                                    </div>
                                    <div class="bg-purple-100 p-3 rounded-lg">
                                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Konversiya</p>
                                        <p class="text-3xl font-bold text-gray-900 mt-2">
                                            {{ analysis.overview.conversion_rate }}%
                                        </p>
                                    </div>
                                    <div class="bg-orange-100 p-3 rounded-lg">
                                        <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top Performers -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                            <div class="p-6 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Top Mijozlar</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mijoz</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LTV</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jami Xarajat</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Buyurtmalar</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kanal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="customer in analysis.top_performers" :key="customer.id">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ customer.name }}</div>
                                                <div class="text-sm text-gray-500">{{ customer.phone }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ new Intl.NumberFormat('uz-UZ').format(customer.ltv) }} so'm
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ new Intl.NumberFormat('uz-UZ').format(customer.total_spent) }} so'm
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ customer.total_orders }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    {{ customer.acquisition_source }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Demographic Insights -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- By City -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Shahar Bo'yicha</h3>
                                <div class="space-y-3">
                                    <div v-for="(data, city) in analysis.demographic_insights.city_distribution" :key="city" class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex justify-between mb-1">
                                                <span class="text-sm font-medium text-gray-700">{{ city }}</span>
                                                <span class="text-sm text-gray-600">{{ data.count }} ({{ data.percentage }}%)</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="bg-blue-600 h-2 rounded-full" :style="{ width: data.percentage + '%' }"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- By Source -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Kanal Bo'yicha</h3>
                                <div class="space-y-3">
                                    <div v-for="(data, source) in analysis.demographic_insights.source_performance" :key="source">
                                        <div class="flex justify-between mb-1">
                                            <span class="text-sm font-medium text-gray-700">{{ source }}</span>
                                            <span class="text-sm text-gray-600">{{ data.count }} mijoz</span>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            O'rtacha LTV: {{ new Intl.NumberFormat('uz-UZ').format(data.avg_ltv) }} so'm
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dream Buyer Match Tab -->
                    <div v-show="activeTab === 'dreambuyer'" class="space-y-6">
                        <div v-if="!analysis.dream_buyer_match.has_dream_buyers" class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                            <p class="text-yellow-800">{{ analysis.dream_buyer_match.message }}</p>
                            <a href="/dream-buyers" class="mt-4 inline-block px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">
                                Dream Buyer Yaratish
                            </a>
                        </div>

                        <div v-else class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div v-for="match in analysis.dream_buyer_match.matches" :key="match.dream_buyer_id" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ match.dream_buyer_name }}</h3>
                                    <div class="space-y-3">
                                        <div>
                                            <p class="text-sm text-gray-600">Mos Kelgan Mijozlar</p>
                                            <p class="text-2xl font-bold text-gray-900">{{ match.total_matches }}</p>
                                            <p class="text-sm text-gray-500">{{ match.match_percentage }}% moslik</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600">O'rtacha LTV</p>
                                            <p class="text-lg font-semibold text-gray-900">
                                                {{ new Intl.NumberFormat('uz-UZ').format(match.avg_ltv_of_matches) }} so'm
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600">Umumiy Daromad</p>
                                            <p class="text-lg font-semibold text-green-600">
                                                {{ new Intl.NumberFormat('uz-UZ').format(match.total_revenue_from_matches) }} so'm
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Top Matching Customers -->
                                    <div v-if="match.top_matching_customers.length > 0" class="mt-4 pt-4 border-t border-gray-200">
                                        <p class="text-sm font-medium text-gray-700 mb-2">Top Mijozlar:</p>
                                        <div class="space-y-2">
                                            <div v-for="customer in match.top_matching_customers" :key="customer.id" class="text-sm text-gray-600">
                                                {{ customer.name }} - {{ new Intl.NumberFormat('uz-UZ').format(customer.ltv) }} so'm
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Segments Tab -->
                    <div v-show="activeTab === 'segments'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- LTV Segments -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">LTV Bo'yicha</h3>
                                <div class="space-y-4">
                                    <div>
                                        <div class="flex justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-700">Yuqori Qiymatli</span>
                                            <span class="text-sm font-semibold text-gray-900">{{ analysis.customer_segments.by_ltv.high_value }}</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-green-600 h-2 rounded-full" :style="{ width: (analysis.customer_segments.by_ltv.high_value / analysis.overview.total_customers * 100) + '%' }"></div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-700">O'rta Qiymatli</span>
                                            <span class="text-sm font-semibold text-gray-900">{{ analysis.customer_segments.by_ltv.medium_value }}</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-yellow-600 h-2 rounded-full" :style="{ width: (analysis.customer_segments.by_ltv.medium_value / analysis.overview.total_customers * 100) + '%' }"></div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-700">Past Qiymatli</span>
                                            <span class="text-sm font-semibold text-gray-900">{{ analysis.customer_segments.by_ltv.low_value }}</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-red-600 h-2 rounded-full" :style="{ width: (analysis.customer_segments.by_ltv.low_value / analysis.overview.total_customers * 100) + '%' }"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- RFM Segments -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">RFM Segmentatsiyasi</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                                        <span class="text-sm font-medium text-gray-700">Champions</span>
                                        <span class="text-lg font-bold text-green-600">{{ analysis.customer_segments.rfm_segments.champions }}</span>
                                    </div>
                                    <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                                        <span class="text-sm font-medium text-gray-700">Loyal</span>
                                        <span class="text-lg font-bold text-blue-600">{{ analysis.customer_segments.rfm_segments.loyal }}</span>
                                    </div>
                                    <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                                        <span class="text-sm font-medium text-gray-700">Potential</span>
                                        <span class="text-lg font-bold text-purple-600">{{ analysis.customer_segments.rfm_segments.potential }}</span>
                                    </div>
                                    <div class="flex justify-between items-center p-3 bg-orange-50 rounded-lg">
                                        <span class="text-sm font-medium text-gray-700">At Risk</span>
                                        <span class="text-lg font-bold text-orange-600">{{ analysis.customer_segments.rfm_segments.at_risk }}</span>
                                    </div>
                                    <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                                        <span class="text-sm font-medium text-gray-700">Need Attention</span>
                                        <span class="text-lg font-bold text-red-600">{{ analysis.customer_segments.rfm_segments.need_attention }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Funnel Tab -->
                    <div v-show="activeTab === 'funnel'" class="space-y-6">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6">Konversiya Voronkasi</h3>
                            <div class="space-y-4">
                                <div v-for="stage in analysis.conversion_funnel.stages" :key="stage.name" class="relative">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-700">{{ stage.name }}</span>
                                        <div class="text-right">
                                            <span class="text-lg font-bold text-gray-900">{{ stage.count }}</span>
                                            <span class="text-sm text-gray-500 ml-2">({{ stage.percentage }}%)</span>
                                        </div>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-8">
                                        <div
                                            class="bg-gradient-to-r from-blue-500 to-blue-600 h-8 rounded-full flex items-center justify-end px-3 transition-all duration-500"
                                            :style="{ width: stage.percentage + '%' }"
                                        >
                                            <span class="text-white text-xs font-semibold" v-if="stage.percentage > 10">{{ stage.percentage }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="text-center p-4 bg-blue-50 rounded-lg">
                                    <p class="text-sm text-gray-600">Lead → Qualified</p>
                                    <p class="text-2xl font-bold text-blue-600">{{ analysis.conversion_funnel.conversion_rates.lead_to_qualified }}%</p>
                                </div>
                                <div class="text-center p-4 bg-green-50 rounded-lg">
                                    <p class="text-sm text-gray-600">Qualified → Converted</p>
                                    <p class="text-2xl font-bold text-green-600">{{ analysis.conversion_funnel.conversion_rates.qualified_to_converted }}%</p>
                                </div>
                                <div class="text-center p-4 bg-purple-50 rounded-lg">
                                    <p class="text-sm text-gray-600">Lead → Customer</p>
                                    <p class="text-2xl font-bold text-purple-600">{{ analysis.conversion_funnel.conversion_rates.lead_to_customer }}%</p>
                                </div>
                                <div class="text-center p-4 bg-orange-50 rounded-lg">
                                    <p class="text-sm text-gray-600">Customer Retention</p>
                                    <p class="text-2xl font-bold text-orange-600">{{ analysis.conversion_funnel.conversion_rates.customer_retention }}%</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Churn Risk Tab -->
                    <div v-show="activeTab === 'churn'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                                <h4 class="text-sm font-medium text-red-800 mb-2">Yuqori Xavf</h4>
                                <p class="text-3xl font-bold text-red-600">{{ analysis.churn_risk.high_risk_count }}</p>
                                <p class="text-sm text-red-700 mt-2">90+ kun faolsiz</p>
                            </div>
                            <div class="bg-orange-50 border border-orange-200 rounded-xl p-6">
                                <h4 class="text-sm font-medium text-orange-800 mb-2">O'rta Xavf</h4>
                                <p class="text-3xl font-bold text-orange-600">{{ analysis.churn_risk.medium_risk_count }}</p>
                                <p class="text-sm text-orange-700 mt-2">60-90 kun faolsiz</p>
                            </div>
                            <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                                <h4 class="text-sm font-medium text-green-800 mb-2">Past Xavf</h4>
                                <p class="text-3xl font-bold text-green-600">{{ analysis.churn_risk.low_risk_count }}</p>
                                <p class="text-sm text-green-700 mt-2">So'nggi 60 kun ichida faol</p>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                            <div class="p-6 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Yuqori Xavfdagi Mijozlar</h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    Potentsial yo'qotilgan daromad: <span class="font-semibold text-red-600">{{ new Intl.NumberFormat('uz-UZ').format(analysis.churn_risk.potential_lost_revenue) }} so'm</span>
                                </p>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mijoz</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Faolsiz Kunlar</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LTV</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harakat</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="customer in analysis.churn_risk.high_risk_customers" :key="customer.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ customer.name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                    {{ customer.days_since_purchase }} kun
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ new Intl.NumberFormat('uz-UZ').format(customer.ltv) }} so'm
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <a href="/marketing/campaigns/create" class="text-blue-600 hover:text-blue-900">
                                                    Kampaniya Boshlash
                                                </a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
