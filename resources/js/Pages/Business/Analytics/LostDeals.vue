<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import axios from 'axios'

const props = defineProps({
    lostReasons: Object,
    lazyLoad: Boolean,
})

const loading = ref(true)
const data = ref(null)
const dateFrom = ref('')
const dateTo = ref('')
const activeTab = ref('reason')

const fetchData = async () => {
    loading.value = true
    try {
        const params = {}
        if (dateFrom.value) params.date_from = dateFrom.value
        if (dateTo.value) params.date_to = dateTo.value

        const response = await axios.get(route('business.analytics.api.lost-deals'), { params })
        data.value = response.data
    } catch (error) {
        console.error('Failed to fetch lost deals:', error)
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

const tabs = [
    { key: 'reason', label: 'Sabab bo\'yicha' },
    { key: 'stage', label: 'Bosqich bo\'yicha' },
    { key: 'operator', label: 'Operator bo\'yicha' },
    { key: 'source', label: 'Manba bo\'yicha' },
]
</script>

<template>
    <BusinessLayout title="Yo'qotilgan Sotuvlar Tahlili">
        <Head title="Yo'qotilgan Sotuvlar" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Yo'qotilgan Sotuvlar Tahlili</h1>
                <p class="mt-1 text-sm text-gray-500">Nega sotuvlar yo'qolgani va qanday yo'qotishlarni oldini olish mumkinligi haqida</p>
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
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-red-50 rounded-lg p-6 border border-red-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-red-600">Jami yo'qotilgan</p>
                                <p class="text-3xl font-bold text-red-700">{{ data.summary?.total_lost }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-orange-50 rounded-lg p-6 border border-orange-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-orange-600">Yo'qotilgan qiymat</p>
                                <p class="text-3xl font-bold text-orange-700">{{ formatNumber(data.summary?.total_value_lost) }}</p>
                                <p class="text-xs text-orange-500">so'm</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-orange-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 rounded-lg p-6 border border-yellow-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-yellow-600">O'rtacha qiymat</p>
                                <p class="text-3xl font-bold text-yellow-700">{{ formatNumber(data.summary?.avg_value_lost) }}</p>
                                <p class="text-xs text-yellow-500">so'm / lid</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="bg-white rounded-lg shadow">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px">
                            <button
                                v-for="tab in tabs"
                                :key="tab.key"
                                @click="activeTab = tab.key"
                                class="px-6 py-3 text-sm font-medium border-b-2 transition-colors"
                                :class="activeTab === tab.key
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            >
                                {{ tab.label }}
                            </button>
                        </nav>
                    </div>

                    <div class="p-6">
                        <!-- By Reason -->
                        <div v-if="activeTab === 'reason'" class="space-y-4">
                            <div v-for="reason in data.by_reason" :key="reason.reason" class="flex items-center p-4 bg-gray-50 rounded-lg">
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-medium text-gray-900">{{ reason.reason_label }}</h4>
                                        <span class="text-sm text-gray-500">{{ reason.count }} lid ({{ reason.percentage }}%)</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div class="bg-red-500 h-3 rounded-full transition-all" :style="{ width: reason.percentage + '%' }"></div>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-600">Yo'qotilgan qiymat: {{ formatNumber(reason.total_value) }} so'm</p>
                                </div>
                            </div>
                        </div>

                        <!-- By Stage -->
                        <div v-if="activeTab === 'stage'" class="space-y-4">
                            <div v-for="stage in data.by_stage" :key="stage.stage_id" class="flex items-center p-4 bg-gray-50 rounded-lg">
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-medium text-gray-900">{{ stage.stage_name }}</h4>
                                        <span class="text-sm text-gray-500">{{ stage.count }} lid ({{ stage.percentage }}%)</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div class="bg-orange-500 h-3 rounded-full transition-all" :style="{ width: stage.percentage + '%' }"></div>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-600">Yo'qotilgan qiymat: {{ formatNumber(stage.total_value) }} so'm</p>
                                </div>
                            </div>
                        </div>

                        <!-- By Operator -->
                        <div v-if="activeTab === 'operator'" class="space-y-4">
                            <div v-for="op in data.by_operator" :key="op.user_id" class="flex items-center p-4 bg-gray-50 rounded-lg">
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-medium text-gray-900">{{ op.user_name }}</h4>
                                        <span class="text-sm text-gray-500">{{ op.count }} lid ({{ op.percentage }}%)</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div class="bg-yellow-500 h-3 rounded-full transition-all" :style="{ width: op.percentage + '%' }"></div>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-600">Yo'qotilgan qiymat: {{ formatNumber(op.total_value) }} so'm</p>
                                </div>
                            </div>
                        </div>

                        <!-- By Source -->
                        <div v-if="activeTab === 'source'" class="space-y-4">
                            <div v-for="src in data.by_source" :key="src.channel_id" class="flex items-center p-4 bg-gray-50 rounded-lg">
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-medium text-gray-900">{{ src.channel_name }}</h4>
                                        <span class="text-sm text-gray-500">{{ src.count }} lid ({{ src.percentage }}%)</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div class="bg-purple-500 h-3 rounded-full transition-all" :style="{ width: src.percentage + '%' }"></div>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-600">Yo'qotilgan qiymat: {{ formatNumber(src.total_value) }} so'm</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Trend -->
                <div class="mt-8 bg-white rounded-lg shadow p-6" v-if="data.monthly_trend?.length">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Oylik trend</h3>
                    <div class="space-y-2">
                        <div v-for="month in data.monthly_trend" :key="month.month" class="flex items-center justify-between p-3 bg-gray-50 rounded">
                            <span class="font-medium">{{ month.month }}</span>
                            <div class="text-right">
                                <span class="text-red-600 font-bold">{{ month.count }} lid</span>
                                <span class="text-gray-500 ml-2">{{ formatNumber(month.total_value) }} so'm</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </BusinessLayout>
</template>
