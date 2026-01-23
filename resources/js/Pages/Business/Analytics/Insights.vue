<script setup>
import { ref, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import axios from 'axios'

const props = defineProps({
    lazyLoad: Boolean,
})

const loading = ref(true)
const data = ref(null)
const dateFrom = ref('')
const dateTo = ref('')

const fetchData = async () => {
    loading.value = true
    try {
        const params = {}
        if (dateFrom.value) params.date_from = dateFrom.value
        if (dateTo.value) params.date_to = dateTo.value

        const response = await axios.get(route('business.analytics.api.insights'), { params })
        data.value = response.data
    } catch (error) {
        console.error('Failed to fetch insights:', error)
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    fetchData()
})

const formatNumber = (num) => {
    if (!num) return '0'
    return new Intl.NumberFormat('uz-UZ').format(num)
}

const getInsightIcon = (icon) => {
    const icons = {
        'alert': 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        'star': 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
        'user': 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'trending-up': 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
        'trending-down': 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6',
        'dollar': 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    }
    return icons[icon] || icons['alert']
}

const getInsightColor = (type) => {
    const colors = {
        'success': 'bg-green-50 border-green-200 text-green-800',
        'warning': 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'danger': 'bg-red-50 border-red-200 text-red-800',
        'info': 'bg-blue-50 border-blue-200 text-blue-800',
    }
    return colors[type] || colors['info']
}

const getRecommendationLabel = (rec) => {
    const labels = {
        'scale_up': { text: 'Byudjetni oshiring', color: 'bg-green-100 text-green-800' },
        'maintain': { text: 'Davom eting', color: 'bg-blue-100 text-blue-800' },
        'optimize': { text: 'Optimizatsiya qiling', color: 'bg-yellow-100 text-yellow-800' },
        'test_more': { text: 'Ko\'proq test qiling', color: 'bg-gray-100 text-gray-800' },
        'reduce': { text: 'Kamaytiring', color: 'bg-red-100 text-red-800' },
    }
    return labels[rec] || labels['maintain']
}
</script>

<template>
    <BusinessLayout title="Biznes Insaytlar">
        <Head title="Biznes Insaytlar" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Biznes Insaytlar</h1>
                <p class="mt-1 text-sm text-gray-500">Biznesingiz holati haqida muhim ma'lumotlar</p>
            </div>

            <!-- Date Filter -->
            <div class="mb-6 flex gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Boshlanish</label>
                    <input type="date" v-model="dateFrom" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tugash</label>
                    <input type="date" v-model="dateTo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
                <button @click="fetchData" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Yangilash
                </button>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex justify-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
            </div>

            <template v-else-if="data">
                <!-- Key Insights -->
                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Asosiy Topilmalar</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="insight in data.insights"
                            :key="insight.title"
                            class="p-4 rounded-lg border"
                            :class="getInsightColor(insight.type)"
                        >
                            <div class="flex items-start gap-3">
                                <svg class="h-6 w-6 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getInsightIcon(insight.icon)" />
                                </svg>
                                <div>
                                    <h3 class="font-medium">{{ insight.title }}</h3>
                                    <p class="text-sm mt-1 opacity-80">{{ insight.description }}</p>
                                    <p class="text-sm mt-2 font-medium">{{ insight.action }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-500">Jami daromad</p>
                        <p class="text-2xl font-bold text-gray-900">{{ formatNumber(data.metrics?.total_revenue) }} so'm</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-500">Konversiya</p>
                        <p class="text-2xl font-bold text-gray-900">{{ data.metrics?.conversion_rate }}%</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-500">Yo'qotilgan qiymat</p>
                        <p class="text-2xl font-bold text-red-600">{{ formatNumber(data.lost_deals?.summary?.total_value_lost) }} so'm</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-500">Operatorlar</p>
                        <p class="text-2xl font-bold text-gray-900">{{ data.operators?.team_stats?.total_operators }}</p>
                    </div>
                </div>

                <!-- Top Operators -->
                <div class="bg-white rounded-lg shadow mb-8">
                    <div class="px-4 py-5 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Eng yaxshi operatorlar</h3>
                    </div>
                    <div class="p-4">
                        <div class="space-y-4">
                            <div v-for="(op, index) in data.operators?.top_performers" :key="op.user_id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full" :class="index === 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-200 text-gray-600'">
                                        {{ index + 1 }}
                                    </span>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ op.user_name }}</p>
                                        <p class="text-sm text-gray-500">{{ op.total_leads }} lid, {{ op.won_leads }} yutilgan</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-green-600">{{ op.conversion_rate }}%</p>
                                    <p class="text-sm text-gray-500">{{ formatNumber(op.total_revenue) }} so'm</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Channel ROI -->
                <div class="bg-white rounded-lg shadow mb-8">
                    <div class="px-4 py-5 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Marketing kanallar ROI</h3>
                    </div>
                    <div class="p-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Kanal</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Lidlar</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Daromad</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Xarajat</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">ROI</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Tavsiya</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="channel in data.channels?.channels" :key="channel.channel_id">
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ channel.channel_name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 text-right">{{ channel.total_leads }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 text-right">{{ formatNumber(channel.revenue) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 text-right">{{ formatNumber(channel.spend) }}</td>
                                    <td class="px-4 py-3 text-sm text-right" :class="channel.roi > 0 ? 'text-green-600 font-bold' : 'text-red-600 font-bold'">
                                        {{ channel.roi }}%
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 text-xs rounded-full" :class="getRecommendationLabel(channel.recommendation).color">
                                            {{ getRecommendationLabel(channel.recommendation).text }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Lost Deals by Reason -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-4 py-5 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Yo'qotish sabablari</h3>
                    </div>
                    <div class="p-4">
                        <div class="space-y-3">
                            <div v-for="reason in data.lost_deals?.by_reason" :key="reason.reason" class="flex items-center">
                                <div class="flex-1">
                                    <div class="flex justify-between mb-1">
                                        <span class="text-sm text-gray-700">{{ reason.reason_label }}</span>
                                        <span class="text-sm text-gray-500">{{ reason.count }} ({{ reason.percentage }}%)</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-red-500 h-2 rounded-full" :style="{ width: reason.percentage + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </BusinessLayout>
</template>
