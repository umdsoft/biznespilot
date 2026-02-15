<script setup>
import { ref, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import axios from 'axios'

const props = defineProps({
    lazyLoad: Boolean,
})

const loading = ref(true)
const error = ref(null)
const data = ref(null)
const dateFrom = ref('')
const dateTo = ref('')

const fetchData = async () => {
    loading.value = true
    error.value = null
    try {
        const params = {}
        if (dateFrom.value) params.date_from = dateFrom.value
        if (dateTo.value) params.date_to = dateTo.value

        const response = await axios.get(route('business.analytics.api.channel-roi'), { params })
        data.value = response.data
    } catch (e) {
        console.error('Failed to fetch channel ROI:', e)
        error.value = 'Ma\'lumotlarni yuklashda xatolik yuz berdi. Qayta urinib ko\'ring.'
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

const getROIColor = (roi) => {
    if (roi > 200) return 'text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/50'
    if (roi > 100) return 'text-green-500 dark:text-green-400 bg-green-50 dark:bg-green-900/30'
    if (roi > 0) return 'text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/30'
    return 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30'
}

const getRecommendation = (rec) => {
    const recommendations = {
        'scale_up': { text: 'Byudjetni oshiring', color: 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200', icon: 'M5 10l7-7m0 0l7 7m-7-7v18' },
        'maintain': { text: 'Davom eting', color: 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
        'optimize': { text: 'Optimizatsiya qiling', color: 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200', icon: 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4' },
        'test_more': { text: 'Ko\'proq test qiling', color: 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' },
        'reduce': { text: 'Kamaytiring', color: 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-200', icon: 'M19 14l-7 7m0 0l-7-7m7 7V3' },
    }
    return recommendations[rec] || recommendations['maintain']
}
</script>

<template>
    <BusinessLayout title="Marketing Kanallar ROI">
        <Head title="Marketing Kanallar ROI" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Marketing Kanallar ROI</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Har bir marketing kanalingizning qaytimi va samaradorligini kuzating</p>
            </div>

            <!-- Date Filter -->
            <div class="mb-6 flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Boshlanish</label>
                    <input type="date" v-model="dateFrom" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tugash</label>
                    <input type="date" v-model="dateTo" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
                <button @click="fetchData" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Yangilash
                </button>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex justify-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
            </div>

            <!-- Error -->
            <div v-else-if="error" class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm text-red-800 dark:text-red-200">{{ error }}</p>
                    <button @click="fetchData" class="ml-auto text-sm text-red-600 dark:text-red-400 hover:underline">Qayta urinish</button>
                </div>
            </div>

            <template v-else-if="data">
                <!-- Summary -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Jami xarajat</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(data.summary?.total_spend) }} so'm</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Jami daromad</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatNumber(data.summary?.total_revenue) }} so'm</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Umumiy ROI</p>
                        <p class="text-2xl font-bold" :class="data.summary?.overall_roi > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                            {{ data.summary?.overall_roi }}%
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6" v-if="data.summary?.best_channel">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Eng yaxshi kanal</p>
                        <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ data.summary?.best_channel?.channel_name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ROI: {{ data.summary?.best_channel?.roi }}%</p>
                    </div>
                </div>

                <!-- Channels Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Kanallar bo'yicha tahlil</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kanal</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Lidlar</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Yutilgan</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Konversiya</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Xarajat</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Daromad</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ROI</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ROAS</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Lid narxi</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tavsiya</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="channel in data.channels" :key="channel.channel_id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ channel.channel_name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ channel.channel_type }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900 dark:text-gray-100">{{ channel.total_leads }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-green-600 dark:text-green-400 font-medium">{{ channel.won_leads }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900 dark:text-gray-100">{{ channel.conversion_rate }}%</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-gray-100">{{ formatNumber(channel.spend) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-green-600 dark:text-green-400 font-medium">{{ formatNumber(channel.revenue) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-2 py-1 text-sm font-bold rounded" :class="getROIColor(channel.roi)">
                                            {{ channel.roi }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ channel.roas }}x</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-gray-100">{{ formatNumber(channel.cost_per_lead) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full" :class="getRecommendation(channel.recommendation).color">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getRecommendation(channel.recommendation).icon" />
                                            </svg>
                                            {{ getRecommendation(channel.recommendation).text }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- No spend warning -->
                <div v-if="data.summary?.total_spend === 0" class="mt-6 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <h4 class="font-medium text-yellow-800 dark:text-yellow-200">Xarajat ma'lumotlari yo'q</h4>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                ROI ni to'g'ri hisoblash uchun Marketing Kanallar sozlamalarida oylik byudjetni kiriting.
                                Sozlamalar > Marketing Kanallar bo'limiga o'ting.
                            </p>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </BusinessLayout>
</template>
