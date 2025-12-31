<script setup>
import { computed } from 'vue';

const props = defineProps({
    overview: Object,
    demographics: Object,
    placements: Object,
    campaignCount: {
        type: Number,
        default: 0
    },
    activeCampaigns: {
        type: Number,
        default: 0
    },
    currency: {
        type: String,
        default: 'USD'
    },
    loading: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['generate-ai-insights']);

// Format helpers
const formatNumber = (num) => new Intl.NumberFormat('uz-UZ').format(num || 0);
const formatCurrency = (amount, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(amount || 0);
};
const formatPercent = (value) => (value || 0).toFixed(2) + '%';

// Computed stats from overview
const currentStats = computed(() => props.overview?.current || {});
const previousStats = computed(() => props.overview?.previous || {});

// Calculate change percentage
const getChange = (current, previous) => {
    if (!previous || previous === 0) return null;
    return ((current - previous) / previous * 100).toFixed(1);
};
</script>

<template>
    <div class="space-y-6">
        <!-- Loading State -->
        <div v-if="loading" class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 flex items-center justify-center">
            <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>

        <template v-else>
            <!-- Key Metrics Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <!-- Total Spend -->
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Sarflangan</p>
                            <p class="text-xl font-bold text-gray-900">{{ formatCurrency(currentStats.spend, currency) }}</p>
                            <p v-if="getChange(currentStats.spend, previousStats.spend)"
                               :class="getChange(currentStats.spend, previousStats.spend) > 0 ? 'text-red-500' : 'text-green-500'"
                               class="text-xs mt-0.5">
                                {{ getChange(currentStats.spend, previousStats.spend) > 0 ? '+' : '' }}{{ getChange(currentStats.spend, previousStats.spend) }}%
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Impressions -->
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Ko'rishlar</p>
                            <p class="text-xl font-bold text-gray-900">{{ formatNumber(currentStats.impressions) }}</p>
                            <p v-if="getChange(currentStats.impressions, previousStats.impressions)"
                               :class="getChange(currentStats.impressions, previousStats.impressions) > 0 ? 'text-green-500' : 'text-red-500'"
                               class="text-xs mt-0.5">
                                {{ getChange(currentStats.impressions, previousStats.impressions) > 0 ? '+' : '' }}{{ getChange(currentStats.impressions, previousStats.impressions) }}%
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Clicks -->
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Kliklar</p>
                            <p class="text-xl font-bold text-gray-900">{{ formatNumber(currentStats.clicks) }}</p>
                            <p v-if="getChange(currentStats.clicks, previousStats.clicks)"
                               :class="getChange(currentStats.clicks, previousStats.clicks) > 0 ? 'text-green-500' : 'text-red-500'"
                               class="text-xs mt-0.5">
                                {{ getChange(currentStats.clicks, previousStats.clicks) > 0 ? '+' : '' }}{{ getChange(currentStats.clicks, previousStats.clicks) }}%
                            </p>
                        </div>
                    </div>
                </div>

                <!-- CTR -->
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">CTR</p>
                            <p class="text-xl font-bold text-gray-900">{{ formatPercent(currentStats.ctr) }}</p>
                            <p v-if="getChange(currentStats.ctr, previousStats.ctr)"
                               :class="getChange(currentStats.ctr, previousStats.ctr) > 0 ? 'text-green-500' : 'text-red-500'"
                               class="text-xs mt-0.5">
                                {{ getChange(currentStats.ctr, previousStats.ctr) > 0 ? '+' : '' }}{{ getChange(currentStats.ctr, previousStats.ctr) }}%
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Stats Row -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <!-- Reach -->
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Qamrov</p>
                            <p class="text-xl font-bold text-gray-900">{{ formatNumber(currentStats.reach) }}</p>
                        </div>
                    </div>
                </div>

                <!-- CPC -->
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">CPC</p>
                            <p class="text-xl font-bold text-gray-900">{{ formatCurrency(currentStats.cpc, currency) }}</p>
                        </div>
                    </div>
                </div>

                <!-- CPM -->
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">CPM</p>
                            <p class="text-xl font-bold text-gray-900">{{ formatCurrency(currentStats.cpm, currency) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Campaigns Count -->
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Kampaniyalar</p>
                            <p class="text-xl font-bold text-gray-900">{{ campaignCount }}</p>
                            <p class="text-xs text-green-600 mt-0.5">{{ activeCampaigns }} faol</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Demographics & Placements Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Age Demographics -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Yosh Bo'yicha Auditoriya</h3>
                    <div v-if="demographics?.age?.length" class="space-y-4">
                        <div v-for="item in demographics.age" :key="item.label">
                            <div class="flex justify-between items-center mb-1.5">
                                <span class="text-sm font-medium text-gray-700">{{ item.label }}</span>
                                <div class="text-right">
                                    <span class="text-sm font-semibold text-gray-900">{{ item.percentage }}%</span>
                                    <span class="text-xs text-gray-500 ml-2">{{ formatCurrency(item.spend, currency) }}</span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2.5">
                                <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2.5 rounded-full transition-all" :style="{ width: item.percentage + '%' }"></div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-8 text-gray-400">
                        <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <p class="text-sm">Demografik ma'lumot mavjud emas</p>
                    </div>
                </div>

                <!-- Gender Demographics -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Jins Bo'yicha Auditoriya</h3>
                    <div v-if="demographics?.gender?.length" class="space-y-4">
                        <div v-for="item in demographics.gender" :key="item.label" class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
                            <div :class="item.label === 'male' ? 'bg-blue-100' : 'bg-pink-100'" class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6" :class="item.label === 'male' ? 'text-blue-600' : 'text-pink-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900">{{ item.label === 'male' ? 'Erkaklar' : 'Ayollar' }}</p>
                                <p class="text-sm text-gray-500">{{ formatCurrency(item.spend, currency) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-gray-900">{{ item.percentage }}%</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-8 text-gray-400">
                        <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <p class="text-sm">Jins ma'lumoti mavjud emas</p>
                    </div>
                </div>
            </div>

            <!-- Platform Distribution -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Platforma Bo'yicha</h3>
                <div v-if="placements?.platforms?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div v-for="item in placements.platforms" :key="item.label" class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
                        <div :class="item.label.toLowerCase() === 'facebook' ? 'bg-blue-100' : 'bg-gradient-to-br from-purple-100 to-pink-100'" class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg v-if="item.label.toLowerCase() === 'facebook'" class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            <svg v-else class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ item.label }}</p>
                            <p class="text-sm text-gray-500">{{ formatCurrency(item.spend, currency) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-gray-900">{{ item.percentage }}%</p>
                        </div>
                    </div>
                </div>
                <div v-else class="flex flex-col items-center justify-center py-8 text-gray-400">
                    <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <p class="text-sm">Platforma ma'lumoti mavjud emas</p>
                </div>
            </div>

            <!-- AI Insights Button -->
            <div class="flex justify-center">
                <button @click="$emit('generate-ai-insights')"
                    class="px-6 py-3 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-xl hover:from-purple-700 hover:to-blue-700 font-semibold inline-flex items-center gap-2 shadow-lg transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    AI Tahlil Yaratish
                </button>
            </div>
        </template>
    </div>
</template>
