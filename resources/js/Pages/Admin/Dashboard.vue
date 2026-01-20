<template>
    <AdminLayout :title="t('admin.dashboard.title')">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900">{{ t('admin.dashboard.panel') }}</h2>
                        <p class="mt-2 text-sm text-gray-600">
                            {{ t('admin.dashboard.subtitle') }}
                        </p>
                    </div>
                    <button
                        @click="checkSystemHealth"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ t('admin.dashboard.system_health') }}
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-90">{{ t('admin.dashboard.total_users') }}</p>
                                <p class="text-3xl font-bold mt-2">{{ stats.total_users }}</p>
                            </div>
                            <div class="w-14 h-14 bg-white/30 backdrop-blur-sm rounded-lg flex items-center justify-center">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-90">{{ t('admin.dashboard.total_businesses') }}</p>
                                <p class="text-3xl font-bold mt-2">{{ stats.total_businesses }}</p>
                                <p class="text-xs mt-1 opacity-80">{{ stats.active_businesses }} {{ t('admin.common.active') }}</p>
                            </div>
                            <div class="w-14 h-14 bg-white/30 backdrop-blur-sm rounded-lg flex items-center justify-center">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-90">{{ t('admin.dashboard.total_customers') }}</p>
                                <p class="text-3xl font-bold mt-2">{{ stats.total_customers }}</p>
                            </div>
                            <div class="w-14 h-14 bg-white/30 backdrop-blur-sm rounded-lg flex items-center justify-center">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-90">{{ t('admin.dashboard.active_campaigns') }}</p>
                                <p class="text-3xl font-bold mt-2">{{ stats.active_campaigns }}</p>
                                <p class="text-xs mt-1 opacity-80">/ {{ stats.total_campaigns }} {{ t('admin.common.total') }}</p>
                            </div>
                            <div class="w-14 h-14 bg-white/30 backdrop-blur-sm rounded-lg flex items-center justify-center">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Recent Businesses -->
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">{{ t('admin.dashboard.recent_businesses') }}</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div
                                    v-for="business in recentBusinesses"
                                    :key="business.id"
                                    class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                                >
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900">{{ business.name }}</h4>
                                        <p class="text-sm text-gray-600">{{ t('admin.dashboard.owner') }}: {{ business.owner }}</p>
                                        <p class="text-xs text-gray-500 mt-1">{{ business.created_at }}</p>
                                    </div>
                                    <span
                                        class="px-3 py-1 text-xs font-semibold rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-800': business.status === 'active',
                                            'bg-gray-100 text-gray-800': business.status === 'inactive'
                                        }"
                                    >
                                        {{ business.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Users -->
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">{{ t('admin.dashboard.recent_users') }}</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div
                                    v-for="user in recentUsers"
                                    :key="user.id"
                                    class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                                >
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                                        <span class="text-sm font-bold text-blue-600">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900">{{ user.name }}</h4>
                                        <p class="text-sm text-gray-600">{{ user.email }}</p>
                                        <p class="text-xs text-gray-500 mt-1">{{ user.created_at }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Performing Businesses -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">{{ t('admin.dashboard.top_businesses') }}</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('admin.dashboard.business_name') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ t('admin.dashboard.conversations') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(business, index) in topBusinesses" :key="business.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ index + 1 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ business.name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-3 py-1 bg-purple-100 text-purple-800 text-sm font-semibold rounded-full">
                                            {{ business.conversations_count }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Monthly Growth Charts (Placeholder) -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">{{ t('admin.dashboard.monthly_growth') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div v-for="(data, key) in monthlyGrowth" :key="key" class="border border-gray-200 rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-4 capitalize">{{ key }}</h4>
                            <div class="space-y-2">
                                <div v-for="month in data" :key="month.month" class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">{{ month.month }}</span>
                                    <span class="font-semibold text-gray-900">{{ month.count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Health Modal -->
                <div v-if="showHealthModal" @click="showHealthModal = false" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div @click.stop class="bg-white rounded-xl shadow-xl max-w-2xl w-full p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900">{{ t('admin.dashboard.system_health') }}</h3>
                            <button @click="showHealthModal = false" class="text-gray-500 hover:text-gray-700">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div v-if="systemHealth" class="space-y-4">
                            <!-- Overall Health -->
                            <div class="p-4 rounded-lg" :class="{
                                'bg-green-100': systemHealth.overall.status === 'healthy',
                                'bg-yellow-100': systemHealth.overall.status === 'warning',
                                'bg-red-100': systemHealth.overall.status === 'unhealthy'
                            }">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold">{{ t('admin.dashboard.overall_status') }}</span>
                                    <span class="text-lg font-bold">{{ systemHealth.overall.percentage }}%</span>
                                </div>
                            </div>

                            <!-- Individual Health Checks -->
                            <div v-for="(check, key) in systemHealth.health" :key="key" class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                <div>
                                    <h4 class="font-semibold text-gray-900 capitalize">{{ key }}</h4>
                                    <p class="text-sm text-gray-600">{{ check.message }}</p>
                                </div>
                                <span
                                    class="px-3 py-1 text-xs font-semibold rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-800': check.status === 'healthy',
                                        'bg-yellow-100 text-yellow-800': check.status === 'warning',
                                        'bg-red-100 text-red-800': check.status === 'unhealthy'
                                    }"
                                >
                                    {{ check.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import axios from 'axios'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
    stats: Object,
    recentBusinesses: Array,
    recentUsers: Array,
    monthlyGrowth: Object,
    topBusinesses: Array
})

const showHealthModal = ref(false)
const systemHealth = ref(null)

const checkSystemHealth = async () => {
    try {
        const response = await axios.get('/admin/system-health')
        systemHealth.value = response.data
        showHealthModal.value = true
    } catch (error) {
        alert(t('admin.dashboard.system_health_error'))
    }
}
</script>
