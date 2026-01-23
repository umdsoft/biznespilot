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

        const response = await axios.get(route('business.analytics.api.operator-performance'), { params })
        data.value = response.data
    } catch (error) {
        console.error('Failed to fetch operator performance:', error)
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

const getConversionColor = (rate, avg) => {
    if (rate >= avg * 1.2) return 'text-green-600 bg-green-50'
    if (rate >= avg) return 'text-blue-600 bg-blue-50'
    if (rate >= avg * 0.8) return 'text-yellow-600 bg-yellow-50'
    return 'text-red-600 bg-red-50'
}

const formatResponseTime = (minutes) => {
    if (!minutes) return '-'
    if (minutes < 60) return `${minutes} daqiqa`
    const hours = Math.floor(minutes / 60)
    const mins = minutes % 60
    return `${hours}s ${mins}d`
}
</script>

<template>
    <BusinessLayout title="Operator Samaradorligi">
        <Head title="Operator Samaradorligi" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Operator Samaradorligi</h1>
                <p class="mt-1 text-sm text-gray-500">Har bir operatorning KPI va natijalarini kuzating</p>
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
                <!-- Team Stats -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                    <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-500">Operatorlar</p>
                        <p class="text-2xl font-bold text-gray-900">{{ data.team_stats?.total_operators }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-500">Jami lidlar</p>
                        <p class="text-2xl font-bold text-gray-900">{{ data.team_stats?.total_leads }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-500">Jami daromad</p>
                        <p class="text-2xl font-bold text-green-600">{{ formatNumber(data.team_stats?.total_revenue) }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-500">O'rtacha konversiya</p>
                        <p class="text-2xl font-bold text-indigo-600">{{ data.team_stats?.avg_conversion_rate }}%</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-500">O'rtacha chek</p>
                        <p class="text-2xl font-bold text-gray-900">{{ formatNumber(data.team_stats?.avg_deal_size) }}</p>
                    </div>
                </div>

                <!-- Top & Bottom Performers -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Top Performers -->
                    <div class="bg-green-50 rounded-lg p-6 border border-green-200">
                        <h3 class="text-lg font-semibold text-green-800 mb-4 flex items-center gap-2">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            Eng yaxshilar
                        </h3>
                        <div class="space-y-3">
                            <div v-for="(op, i) in data.top_performers" :key="op.user_id" class="flex items-center justify-between bg-white rounded-lg p-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-6 h-6 flex items-center justify-center rounded-full bg-green-200 text-green-800 text-sm font-bold">{{ i + 1 }}</span>
                                    <span class="font-medium">{{ op.user_name }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-green-600 font-bold">{{ op.conversion_rate }}%</span>
                                    <span class="text-gray-500 text-sm ml-2">{{ formatNumber(op.total_revenue) }} so'm</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Performers -->
                    <div class="bg-red-50 rounded-lg p-6 border border-red-200">
                        <h3 class="text-lg font-semibold text-red-800 mb-4 flex items-center gap-2">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Yordam kerak
                        </h3>
                        <div class="space-y-3">
                            <div v-for="(op, i) in data.bottom_performers" :key="op.user_id" class="flex items-center justify-between bg-white rounded-lg p-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-6 h-6 flex items-center justify-center rounded-full bg-red-200 text-red-800 text-sm font-bold">{{ i + 1 }}</span>
                                    <span class="font-medium">{{ op.user_name }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-red-600 font-bold">{{ op.conversion_rate }}%</span>
                                    <span class="text-gray-500 text-sm ml-2">{{ op.lost_leads }} yo'qotilgan</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- All Operators Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Barcha operatorlar</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operator</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Lidlar</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Yutilgan</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Yo'qotilgan</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Konversiya</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Daromad</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Javob vaqti</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Pipeline</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="op in data.operators" :key="op.user_id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0 rounded-full bg-indigo-100 flex items-center justify-center">
                                                <span class="text-indigo-600 font-medium">{{ op.user_name?.charAt(0) }}</span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ op.user_name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">{{ op.total_leads }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-green-600 font-medium">{{ op.won_leads }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-red-600 font-medium">{{ op.lost_leads }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-2 py-1 text-sm font-bold rounded" :class="getConversionColor(op.conversion_rate, data.team_stats?.avg_conversion_rate)">
                                            {{ op.conversion_rate }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">{{ formatNumber(op.total_revenue) }} so'm</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">{{ formatResponseTime(op.avg_response_time_minutes) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-indigo-600">{{ formatNumber(op.pipeline_value) }} so'm</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>
    </BusinessLayout>
</template>
