<template>
    <AdminLayout :title="t('admin.dashboard.title')">
        <div class="py-6">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                            {{ t('admin.dashboard.panel') }}
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ t('admin.dashboard.subtitle') }}
                        </p>
                    </div>
                    <button
                        @click="checkSystemHealth"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ t('admin.dashboard.system_health') }}
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-50 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.total_users }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.dashboard.total_users') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-50 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.total_businesses }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ t('admin.dashboard.total_businesses') }}
                                    <span class="text-green-600 dark:text-green-400">({{ stats.active_businesses }} {{ t('admin.common.active') }})</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-50 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.total_customers }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.dashboard.total_customers') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-orange-50 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.active_campaigns }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ t('admin.dashboard.active_campaigns') }}
                                    <span class="text-gray-400 dark:text-gray-500">/ {{ stats.total_campaigns }}</span>
                                </p>
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
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.dashboard.last_6_months') }}</span>
                        </div>
                        <div class="h-48">
                            <svg class="w-full h-full" viewBox="0 0 400 150" preserveAspectRatio="none">
                                <line v-for="y in [0, 37.5, 75, 112.5, 150]" :key="'ug'+y" x1="0" :y1="y" x2="400" :y2="y" stroke="currentColor" class="text-gray-100 dark:text-gray-700" stroke-width="1"/>
                                <path :d="usersAreaPath" fill="url(#usersGradient)" opacity="0.3"/>
                                <path :d="usersLinePath" fill="none" stroke="#3B82F6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle v-for="(point, i) in usersChartPoints" :key="i" :cx="point.x" :cy="point.y" r="4" fill="#3B82F6"/>
                                <defs>
                                    <linearGradient id="usersGradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#3B82F6"/>
                                        <stop offset="100%" stop-color="#3B82F6" stop-opacity="0"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                        </div>
                        <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <span v-for="(month, i) in usersChartLabels" :key="i">{{ month }}</span>
                        </div>
                    </div>

                    <!-- Businesses Growth Chart -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ t('admin.dashboard.businesses_growth') }}
                            </h3>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.dashboard.last_6_months') }}</span>
                        </div>
                        <div class="h-48">
                            <svg class="w-full h-full" viewBox="0 0 400 150" preserveAspectRatio="none">
                                <line v-for="y in [0, 37.5, 75, 112.5, 150]" :key="'bg'+y" x1="0" :y1="y" x2="400" :y2="y" stroke="currentColor" class="text-gray-100 dark:text-gray-700" stroke-width="1"/>
                                <path :d="businessesAreaPath" fill="url(#businessesGradient)" opacity="0.3"/>
                                <path :d="businessesLinePath" fill="none" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle v-for="(point, i) in businessesChartPoints" :key="i" :cx="point.x" :cy="point.y" r="4" fill="#10B981"/>
                                <defs>
                                    <linearGradient id="businessesGradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#10B981"/>
                                        <stop offset="100%" stop-color="#10B981" stop-opacity="0"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                        </div>
                        <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <span v-for="(month, i) in businessesChartLabels" :key="i">{{ month }}</span>
                        </div>
                    </div>
                </div>

                <!-- Tables Row -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">
                    <!-- Recent Businesses -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ t('admin.dashboard.recent_businesses') }}
                            </h3>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                            <div v-for="business in recentBusinesses.slice(0, 5)" :key="business.id" class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ business.name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ business.owner }}</p>
                                    </div>
                                    <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded" :class="business.status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'">
                                        {{ business.status === 'active' ? t('admin.common.active') : t('admin.common.inactive') }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="!recentBusinesses || recentBusinesses.length === 0" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ t('admin.businesses.not_found') }}
                            </div>
                        </div>
                    </div>

                    <!-- Recent Users -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ t('admin.dashboard.recent_users') }}
                            </h3>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                            <div v-for="user in recentUsers.slice(0, 5)" :key="user.id" class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-medium text-blue-600 dark:text-blue-400">{{ user.name?.charAt(0).toUpperCase() || 'U' }}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ user.name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ user.email }}</p>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!recentUsers || recentUsers.length === 0" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ t('admin.users.not_found') }}
                            </div>
                        </div>
                    </div>

                    <!-- Top Businesses -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ t('admin.dashboard.top_businesses') }}
                            </h3>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                            <div v-for="(business, index) in topBusinesses.slice(0, 5)" :key="business.id" class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium" :class="{
                                        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': index === 0,
                                        'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400': index === 1,
                                        'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': index === 2,
                                        'bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-500': index > 2
                                    }">{{ index + 1 }}</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ business.name }}</p>
                                    </div>
                                    <span class="text-xs font-medium text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30 px-2 py-0.5 rounded">
                                        {{ business.conversations_count }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="!topBusinesses || topBusinesses.length === 0" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ t('admin.businesses.not_found') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Growth Vertical Bar Chart -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ t('admin.dashboard.monthly_growth') }}
                        </h3>
                        <div class="flex items-center gap-4 text-xs">
                            <span class="flex items-center gap-1.5">
                                <span class="w-3 h-3 bg-blue-500 rounded-full"></span>
                                {{ t('admin.dashboard.users') }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="w-3 h-3 bg-emerald-500 rounded-full"></span>
                                {{ t('admin.dashboard.businesses') }}
                            </span>
                        </div>
                    </div>

                    <div v-if="barChartData.length > 0" class="flex items-end gap-1 justify-between h-44 px-2">
                        <div v-for="(item, i) in barChartData" :key="i" class="flex-1 flex flex-col items-center gap-1">
                            <!-- Values -->
                            <div class="flex gap-1 text-[10px] font-bold">
                                <span class="text-blue-600 dark:text-blue-400">{{ item.usersCount }}</span>
                                <span class="text-gray-300 dark:text-gray-600">/</span>
                                <span class="text-emerald-600 dark:text-emerald-400">{{ item.businessesCount }}</span>
                            </div>
                            <!-- Bars -->
                            <div class="flex items-end gap-1 flex-1 w-full justify-center">
                                <div class="w-5 bg-gradient-to-t from-blue-600 to-blue-400 rounded-t-md transition-all duration-700 min-h-[2px]"
                                     :style="{ height: item.usersPercent + '%' }"></div>
                                <div class="w-5 bg-gradient-to-t from-emerald-600 to-emerald-400 rounded-t-md transition-all duration-700 min-h-[2px]"
                                     :style="{ height: item.businessesPercent + '%' }"></div>
                            </div>
                            <!-- Month label -->
                            <span class="text-[10px] font-medium text-gray-500 dark:text-gray-400 mt-1">{{ item.month }}</span>
                        </div>
                    </div>

                    <div v-else class="py-12 text-center text-sm text-gray-400 dark:text-gray-500">
                        Ma'lumot yo'q
                    </div>
                </div>

                <!-- System Health Modal -->
                <Teleport to="body">
                    <Transition enter-active-class="transition-opacity duration-200" leave-active-class="transition-opacity duration-150" enter-from-class="opacity-0" leave-to-class="opacity-0">
                        <div v-if="showHealthModal" @click="showHealthModal = false" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                            <Transition enter-active-class="transition-all duration-200" leave-active-class="transition-all duration-150" enter-from-class="opacity-0 scale-95" leave-to-class="opacity-0 scale-95">
                                <div v-if="showHealthModal" @click.stop class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full border border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ t('admin.dashboard.system_health') }}</h3>
                                        <button @click="showHealthModal = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded transition-colors">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </div>
                                    <div class="p-4">
                                        <div v-if="systemHealth" class="space-y-3">
                                            <div class="p-3 rounded-lg" :class="{
                                                'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800': systemHealth.overall.status === 'healthy',
                                                'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800': systemHealth.overall.status === 'warning',
                                                'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800': systemHealth.overall.status === 'unhealthy'
                                            }">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('admin.dashboard.overall_status') }}</span>
                                                    <span class="text-lg font-semibold" :class="{
                                                        'text-green-600 dark:text-green-400': systemHealth.overall.status === 'healthy',
                                                        'text-yellow-600 dark:text-yellow-400': systemHealth.overall.status === 'warning',
                                                        'text-red-600 dark:text-red-400': systemHealth.overall.status === 'unhealthy'
                                                    }">{{ systemHealth.overall.percentage }}%</span>
                                                </div>
                                            </div>
                                            <div v-for="(check, key) in systemHealth.health" :key="key" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-lg">
                                                <div>
                                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white capitalize">{{ key }}</h4>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ check.message }}</p>
                                                </div>
                                                <span class="px-2 py-0.5 text-xs font-medium rounded" :class="{
                                                    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': check.status === 'healthy',
                                                    'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': check.status === 'warning',
                                                    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': check.status === 'unhealthy'
                                                }">{{ check.status }}</span>
                                            </div>
                                        </div>
                                        <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                                            <svg class="animate-spin h-6 w-6 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                            <p class="mt-2 text-sm">{{ t('admin.common.loading') }}...</p>
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </Transition>
                </Teleport>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import axios from 'axios'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({ total_users: 0, total_businesses: 0, active_businesses: 0, total_customers: 0, active_campaigns: 0, total_campaigns: 0 })
    },
    recentBusinesses: { type: Array, default: () => [] },
    recentUsers: { type: Array, default: () => [] },
    monthlyGrowth: { type: Object, default: () => ({}) },
    topBusinesses: { type: Array, default: () => [] },
})

const showHealthModal = ref(false)
const systemHealth = ref(null)

// Line chart helpers
const usersChartData = computed(() => props.monthlyGrowth?.users?.map(i => i.count) || [])
const usersChartLabels = computed(() => props.monthlyGrowth?.users?.map(i => i.month) || [])
const businessesChartData = computed(() => props.monthlyGrowth?.businesses?.map(i => i.count) || [])
const businessesChartLabels = computed(() => props.monthlyGrowth?.businesses?.map(i => i.month) || [])

const calculateChartPoints = (data, width = 400, height = 150, padding = 10) => {
    if (!data || data.length === 0) return []
    const max = Math.max(...data, 1)
    const step = (width - padding * 2) / (data.length - 1 || 1)
    return data.map((value, i) => ({ x: padding + i * step, y: height - padding - (value / max) * (height - padding * 2) }))
}

const usersChartPoints = computed(() => calculateChartPoints(usersChartData.value))
const businessesChartPoints = computed(() => calculateChartPoints(businessesChartData.value))

const createLinePath = (points) => points?.length ? points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ') : ''
const createAreaPath = (points, height = 150) => {
    if (!points?.length) return ''
    return `${createLinePath(points)} L ${points[points.length - 1].x} ${height} L ${points[0].x} ${height} Z`
}

const usersLinePath = computed(() => createLinePath(usersChartPoints.value))
const usersAreaPath = computed(() => createAreaPath(usersChartPoints.value))
const businessesLinePath = computed(() => createLinePath(businessesChartPoints.value))
const businessesAreaPath = computed(() => createAreaPath(businessesChartPoints.value))

// Vertical bar chart data — deduplicate months, merge users + businesses
const barChartData = computed(() => {
    const users = props.monthlyGrowth?.users || []
    const businesses = props.monthlyGrowth?.businesses || []

    // Merge by month key
    const monthMap = new Map()
    users.forEach(u => {
        if (!monthMap.has(u.month)) monthMap.set(u.month, { month: u.month, usersCount: 0, businessesCount: 0 })
        monthMap.get(u.month).usersCount = u.count
    })
    businesses.forEach(b => {
        if (!monthMap.has(b.month)) monthMap.set(b.month, { month: b.month, usersCount: 0, businessesCount: 0 })
        monthMap.get(b.month).businessesCount = b.count
    })

    const merged = Array.from(monthMap.values()).slice(-6)
    const maxValue = Math.max(...merged.map(m => Math.max(m.usersCount, m.businessesCount)), 1)

    return merged.map(item => ({
        ...item,
        usersPercent: item.usersCount > 0 ? Math.max((item.usersCount / maxValue) * 100, 5) : 2,
        businessesPercent: item.businessesCount > 0 ? Math.max((item.businessesCount / maxValue) * 100, 5) : 2,
    }))
})

const checkSystemHealth = async () => {
    showHealthModal.value = true
    systemHealth.value = null
    try {
        const response = await axios.get('/admin/system-health')
        systemHealth.value = response.data
    } catch (error) {
        console.error('System health check failed:', error)
        alert(t('admin.dashboard.system_health_error'))
        showHealthModal.value = false
    }
}
</script>
