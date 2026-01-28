<template>
    <AdminLayout :title="t('admin.analytics.title')">
        <div class="py-6">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                            {{ t('admin.analytics.title') }}
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ t('admin.analytics.subtitle') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <select
                            v-model="period"
                            class="px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                        >
                            <option value="7">{{ t('admin.analytics.last_7_days') }}</option>
                            <option value="30">{{ t('admin.analytics.last_30_days') }}</option>
                            <option value="90">{{ t('admin.analytics.last_90_days') }}</option>
                            <option value="365">{{ t('admin.analytics.last_year') }}</option>
                        </select>
                        <button
                            @click="refreshAnalytics"
                            :disabled="loading"
                            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors disabled:opacity-50"
                        >
                            <svg :class="['w-4 h-4', loading ? 'animate-spin' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ t('admin.analytics.refresh') }}
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <!-- Active Subscriptions -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-50 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ analytics.active_subscriptions }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.analytics.active_subscriptions') }}</p>
                                <p class="text-xs text-green-600 dark:text-green-400 flex items-center mt-0.5">
                                    <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    +{{ analytics.subscription_growth }}%
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Avg Campaigns -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-50 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ analytics.avg_campaigns }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.analytics.avg_campaigns') }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ t('admin.analytics.per_business') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Avg Conversations -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-50 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ analytics.avg_conversations }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.analytics.avg_conversations') }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ t('admin.analytics.per_business') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Churn Rate -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-orange-50 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold" :class="analytics.churn_rate > 5 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
                                    {{ analytics.churn_rate }}%
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.analytics.churn_rate') }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ t('admin.analytics.this_month') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
                    <!-- Users Growth Chart -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ t('admin.dashboard.users_growth') }}
                            </h3>
                        </div>
                        <div class="h-48">
                            <svg class="w-full h-full" viewBox="0 0 400 150" preserveAspectRatio="none">
                                <!-- Grid lines -->
                                <line v-for="i in 5" :key="i" x1="0" :y1="(i-1) * 37.5" x2="400" :y2="(i-1) * 37.5" stroke="currentColor" class="text-gray-100 dark:text-gray-700" stroke-width="1"/>

                                <!-- Area fill -->
                                <path :d="usersAreaPath" fill="url(#usersGradientAnalytics)" opacity="0.3"/>

                                <!-- Line -->
                                <path :d="usersLinePath" fill="none" stroke="#3B82F6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

                                <!-- Dots -->
                                <circle v-for="(point, i) in usersChartPoints" :key="i" :cx="point.x" :cy="point.y" r="4" fill="#3B82F6"/>

                                <defs>
                                    <linearGradient id="usersGradientAnalytics" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#3B82F6"/>
                                        <stop offset="100%" stop-color="#3B82F6" stop-opacity="0"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                        </div>
                        <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <span v-for="(item, i) in analytics.user_growth" :key="i">{{ item.month }}</span>
                        </div>
                    </div>

                    <!-- Businesses Growth Chart -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ t('admin.dashboard.businesses_growth') }}
                            </h3>
                        </div>
                        <div class="h-48">
                            <svg class="w-full h-full" viewBox="0 0 400 150" preserveAspectRatio="none">
                                <!-- Grid lines -->
                                <line v-for="i in 5" :key="i" x1="0" :y1="(i-1) * 37.5" x2="400" :y2="(i-1) * 37.5" stroke="currentColor" class="text-gray-100 dark:text-gray-700" stroke-width="1"/>

                                <!-- Area fill -->
                                <path :d="businessesAreaPath" fill="url(#businessesGradientAnalytics)" opacity="0.3"/>

                                <!-- Line -->
                                <path :d="businessesLinePath" fill="none" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

                                <!-- Dots -->
                                <circle v-for="(point, i) in businessesChartPoints" :key="i" :cx="point.x" :cy="point.y" r="4" fill="#10B981"/>

                                <defs>
                                    <linearGradient id="businessesGradientAnalytics" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#10B981"/>
                                        <stop offset="100%" stop-color="#10B981" stop-opacity="0"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                        </div>
                        <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <span v-for="(item, i) in analytics.business_growth" :key="i">{{ item.month }}</span>
                        </div>
                    </div>
                </div>

                <!-- Top Industries -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ t('admin.analytics.top_industries') }}
                        </h3>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <div
                                v-for="(industry, index) in analytics.top_industries"
                                :key="industry.name"
                                class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ industry.name }}</span>
                                    <span
                                        class="px-2 py-0.5 text-xs font-medium rounded"
                                        :class="{
                                            'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': index === 0,
                                            'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300': index === 1,
                                            'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': index === 2,
                                            'bg-gray-50 text-gray-500 dark:bg-gray-700 dark:text-gray-400': index > 2
                                        }"
                                    >
                                        #{{ index + 1 }}
                                    </span>
                                </div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ industry.count }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.analytics.businesses') }}</p>
                            </div>
                            <div v-if="!analytics.top_industries || analytics.top_industries.length === 0" class="col-span-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ t('admin.common.no_data') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import axios from 'axios'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
    initialAnalytics: {
        type: Object,
        default: () => ({})
    }
})

const loading = ref(false)
const period = ref('30')

const analytics = ref({
    active_subscriptions: props.initialAnalytics.active_subscriptions || 0,
    subscription_growth: props.initialAnalytics.subscription_growth || 0,
    avg_campaigns: props.initialAnalytics.average_campaigns_per_business?.toFixed(1) || '0.0',
    avg_conversations: props.initialAnalytics.average_conversations_per_business?.toFixed(1) || '0.0',
    churn_rate: props.initialAnalytics.churn_rate || 0,
    user_growth: props.initialAnalytics.user_growth || [],
    business_growth: props.initialAnalytics.business_growth || [],
    top_industries: props.initialAnalytics.top_industries || []
})

// Chart computed properties
const usersChartData = computed(() => {
    if (!analytics.value.user_growth) return []
    return analytics.value.user_growth.map(item => item.count)
})

const businessesChartData = computed(() => {
    if (!analytics.value.business_growth) return []
    return analytics.value.business_growth.map(item => item.count)
})

// Calculate chart points and paths
const calculateChartPoints = (data, width = 400, height = 150, padding = 10) => {
    if (!data || data.length === 0) return []
    const max = Math.max(...data, 1)
    const step = (width - padding * 2) / (data.length - 1 || 1)
    return data.map((value, i) => ({
        x: padding + i * step,
        y: height - padding - (value / max) * (height - padding * 2)
    }))
}

const usersChartPoints = computed(() => calculateChartPoints(usersChartData.value))
const businessesChartPoints = computed(() => calculateChartPoints(businessesChartData.value))

const createLinePath = (points) => {
    if (!points || points.length === 0) return ''
    return points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ')
}

const createAreaPath = (points, height = 150) => {
    if (!points || points.length === 0) return ''
    const linePath = createLinePath(points)
    return `${linePath} L ${points[points.length - 1].x} ${height} L ${points[0].x} ${height} Z`
}

const usersLinePath = computed(() => createLinePath(usersChartPoints.value))
const usersAreaPath = computed(() => createAreaPath(usersChartPoints.value))
const businessesLinePath = computed(() => createLinePath(businessesChartPoints.value))
const businessesAreaPath = computed(() => createAreaPath(businessesChartPoints.value))

const refreshAnalytics = async () => {
    loading.value = true
    try {
        const response = await axios.get('/admin/analytics', {
            params: { period: period.value }
        })
        analytics.value = {
            active_subscriptions: response.data.analytics.active_subscriptions || 0,
            subscription_growth: response.data.analytics.subscription_growth || 0,
            avg_campaigns: response.data.analytics.average_campaigns_per_business?.toFixed(1) || '0.0',
            avg_conversations: response.data.analytics.average_conversations_per_business?.toFixed(1) || '0.0',
            churn_rate: response.data.analytics.churn_rate || 0,
            user_growth: response.data.analytics.user_growth || [],
            business_growth: response.data.analytics.business_growth || [],
            top_industries: response.data.analytics.top_industries || []
        }
    } catch (error) {
        console.error('Analytics refresh failed:', error)
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    if (!props.initialAnalytics.active_subscriptions) {
        refreshAnalytics()
    }
})
</script>
