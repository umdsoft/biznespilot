<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import axios from 'axios'

const props = defineProps({
    weeks: {
        type: Array,
        default: () => [],
    },
    currentWeekStart: {
        type: String,
        default: () => new Date().toISOString().split('T')[0],
    },
    error: {
        type: String,
        default: null,
    },
})

const loading = ref(true)
const aiLoading = ref(false)
const data = ref(null)
const selectedWeek = ref(props.currentWeekStart)
const activeTab = ref('summary')

const tabs = [
    { key: 'summary', label: 'Umumiy', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
    { key: 'channels', label: 'Kanallar', icon: 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z' },
    { key: 'operators', label: 'Operatorlar', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
    { key: 'lost', label: 'Yo\'qotilgan', icon: 'M6 18L18 6M6 6l12 12' },
    { key: 'time', label: 'Vaqt', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    { key: 'ai', label: 'AI Tahlil', icon: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z' },
]

const fetchData = async () => {
    loading.value = true
    try {
        const url = selectedWeek.value
            ? route('business.analytics.api.weekly-report', { weekStart: selectedWeek.value })
            : route('business.analytics.api.weekly-report')
        const response = await axios.get(url)
        data.value = response.data
    } catch (error) {
        console.error('Failed to fetch weekly report:', error)
        data.value = null
    } finally {
        loading.value = false
    }
}

const generateAi = async () => {
    if (!data.value?.id || aiLoading.value) return

    aiLoading.value = true
    try {
        const response = await axios.post(route('business.analytics.api.weekly-report.ai', { id: data.value.id }))
        if (response.data.success) {
            data.value.ai = response.data.ai
        }
    } catch (error) {
        console.error('Failed to generate AI analysis:', error)
    } finally {
        aiLoading.value = false
    }
}

onMounted(() => {
    fetchData()
})

watch(selectedWeek, () => {
    fetchData()
})

const formatNumber = (num) => {
    if (!num) return '0'
    return new Intl.NumberFormat('uz-UZ').format(num)
}

const formatResponseTime = (minutes) => {
    if (!minutes) return '-'
    if (minutes < 60) return `${minutes} daqiqa`
    const hours = Math.floor(minutes / 60)
    const mins = minutes % 60
    return `${hours}s ${mins}d`
}

const getChangeColor = (val) => {
    if (!val) return 'text-gray-500'
    if (typeof val === 'string') {
        return val.startsWith('+') ? 'text-green-600' : val.startsWith('-') ? 'text-red-600' : 'text-gray-500'
    }
    return val > 0 ? 'text-green-600' : val < 0 ? 'text-red-600' : 'text-gray-500'
}

const getConversionColor = (rate, avg) => {
    if (!avg) return 'text-gray-600 bg-gray-50'
    if (rate >= avg * 1.2) return 'text-green-600 bg-green-50'
    if (rate >= avg) return 'text-blue-600 bg-blue-50'
    if (rate >= avg * 0.8) return 'text-yellow-600 bg-yellow-50'
    return 'text-red-600 bg-red-50'
}

const avgConversion = computed(() => {
    return data.value?.summary?.conversion_rate || 0
})

const topPerformers = computed(() => {
    if (!data.value?.operators?.length) return []
    return data.value.operators.slice(0, 3)
})

const bottomPerformers = computed(() => {
    if (!data.value?.operators?.length) return []
    // Only show bottom performers if we have more than 3 operators
    // to avoid showing same operators in both lists
    if (data.value.operators.length <= 3) return []
    return [...data.value.operators].reverse().slice(0, 3)
})
</script>

<template>
    <BusinessLayout title="Haftalik Hisobot">
        <Head title="Haftalik Hisobot" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Error State -->
            <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 text-red-800">
                {{ error }}
            </div>

            <template v-else>
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Haftalik Hisobot</h1>
                        <p class="mt-1 text-sm text-gray-500">Biznesingizning haftalik natijalarini ko'ring</p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <select
                            v-model="selectedWeek"
                            class="block w-full md:w-auto rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option v-for="week in weeks" :key="week.week_start" :value="week.week_start">
                                {{ week.week_label }} {{ week.has_ai ? '(AI)' : '' }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="flex justify-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                </div>

                <template v-else-if="data">
                    <!-- Week Header Card -->
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg shadow-lg p-6 mb-8 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-indigo-200 text-sm">Hafta</p>
                                <h2 class="text-2xl font-bold">{{ data.week_label }}</h2>
                                <p class="text-indigo-200 text-sm mt-1">{{ data.week_start }} - {{ data.week_end }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-indigo-200 text-sm">Umumiy daromad</p>
                                <p class="text-3xl font-bold">{{ formatNumber(data.summary?.total_revenue) }}</p>
                                <p class="text-sm" :class="getChangeColor(data.summary?.vs_last_week?.revenue)">
                                    {{ data.summary?.vs_last_week?.revenue }} o'tgan haftaga nisbatan
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs Navigation -->
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="flex -mb-px overflow-x-auto">
                            <button
                                v-for="tab in tabs"
                                :key="tab.key"
                                @click="activeTab = tab.key"
                                class="flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors"
                                :class="activeTab === tab.key
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
                                </svg>
                                {{ tab.label }}
                                <span v-if="tab.key === 'ai' && data.ai?.has_analysis" class="ml-1 flex h-2 w-2">
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                </span>
                            </button>
                        </nav>
                    </div>

                    <!-- Summary Tab -->
                    <div v-show="activeTab === 'summary'" class="space-y-6">
                        <!-- Key Metrics -->
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            <div class="bg-white rounded-lg shadow p-4">
                                <p class="text-sm text-gray-500">Jami lidlar</p>
                                <p class="text-2xl font-bold text-gray-900">{{ data.summary?.total_leads }}</p>
                                <p class="text-xs" :class="getChangeColor(data.summary?.vs_last_week?.leads)">
                                    {{ data.summary?.vs_last_week?.leads }}
                                </p>
                            </div>
                            <div class="bg-white rounded-lg shadow p-4">
                                <p class="text-sm text-gray-500">Yutilgan</p>
                                <p class="text-2xl font-bold text-green-600">{{ data.summary?.won }}</p>
                            </div>
                            <div class="bg-white rounded-lg shadow p-4">
                                <p class="text-sm text-gray-500">Yo'qotilgan</p>
                                <p class="text-2xl font-bold text-red-600">{{ data.summary?.lost }}</p>
                            </div>
                            <div class="bg-white rounded-lg shadow p-4">
                                <p class="text-sm text-gray-500">Konversiya</p>
                                <p class="text-2xl font-bold text-indigo-600">{{ data.summary?.conversion_rate }}%</p>
                                <p class="text-xs" :class="getChangeColor(data.summary?.vs_last_week?.conversion)">
                                    {{ data.summary?.vs_last_week?.conversion > 0 ? '+' : '' }}{{ data.summary?.vs_last_week?.conversion }}%
                                </p>
                            </div>
                            <div class="bg-white rounded-lg shadow p-4">
                                <p class="text-sm text-gray-500">O'rtacha chek</p>
                                <p class="text-2xl font-bold text-gray-900">{{ formatNumber(data.summary?.avg_deal_value) }}</p>
                            </div>
                        </div>

                        <!-- 4-Week Trend Chart -->
                        <div class="bg-white rounded-lg shadow p-6" v-if="data.trends?.weeks?.length">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">4 haftalik trend</h3>
                            <div class="grid grid-cols-4 gap-4">
                                <div v-for="(week, i) in data.trends.weeks" :key="week" class="text-center">
                                    <p class="text-sm text-gray-500 mb-2">{{ week }}</p>
                                    <div class="space-y-2">
                                        <div>
                                            <p class="text-lg font-bold text-gray-900">{{ data.trends.leads[i] }}</p>
                                            <p class="text-xs text-gray-500">lid</p>
                                        </div>
                                        <div>
                                            <p class="text-lg font-bold text-indigo-600">{{ data.trends.conversion[i] }}%</p>
                                            <p class="text-xs text-gray-500">konversiya</p>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-green-600">{{ formatNumber(data.trends.revenue[i]) }}</p>
                                            <p class="text-xs text-gray-500">so'm</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Channels Tab -->
                    <div v-show="activeTab === 'channels'" class="space-y-6">
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-medium text-gray-900">Marketing kanallar</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kanal</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Lidlar</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Yutilgan</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Yo'qotilgan</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Konversiya</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Daromad</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">vs o'tgan hafta</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="ch in data.channels" :key="ch.id" class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="font-medium text-gray-900">{{ ch.name }}</div>
                                                <div class="text-xs text-gray-500">{{ ch.type }}</div>
                                            </td>
                                            <td class="px-6 py-4 text-center text-sm text-gray-900">{{ ch.leads }}</td>
                                            <td class="px-6 py-4 text-center text-sm text-green-600 font-medium">{{ ch.won }}</td>
                                            <td class="px-6 py-4 text-center text-sm text-red-600 font-medium">{{ ch.lost }}</td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="px-2 py-1 text-sm font-bold rounded" :class="getConversionColor(ch.conversion, avgConversion)">
                                                    {{ ch.conversion }}%
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">{{ formatNumber(ch.revenue) }}</td>
                                            <td class="px-6 py-4 text-center text-sm" :class="getChangeColor(ch.vs_last_week)">
                                                {{ ch.vs_last_week > 0 ? '+' : '' }}{{ ch.vs_last_week }}%
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Channel Lost Reasons -->
                        <div class="bg-white rounded-lg shadow p-6" v-if="data.channels?.length">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Kanal bo'yicha yo'qotish sabablari</h3>
                            <div class="space-y-4">
                                <div v-for="ch in data.channels?.filter(c => Object.keys(c.lost_reasons || {}).length)" :key="ch.id">
                                    <p class="font-medium text-gray-700 mb-2">{{ ch.name }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="(count, reason) in ch.lost_reasons" :key="reason" class="px-2 py-1 text-xs bg-red-50 text-red-700 rounded">
                                            {{ reason }}: {{ count }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Operators Tab -->
                    <div v-show="activeTab === 'operators'" class="space-y-6">
                        <!-- Top & Bottom Performers -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6" v-if="data.operators?.length > 0">
                            <div class="bg-green-50 rounded-lg p-6 border border-green-200">
                                <h3 class="text-lg font-semibold text-green-800 mb-4 flex items-center gap-2">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    Eng yaxshilar
                                </h3>
                                <div class="space-y-3">
                                    <div v-for="(op, i) in topPerformers" :key="op.id" class="flex items-center justify-between bg-white rounded-lg p-3">
                                        <div class="flex items-center gap-3">
                                            <span class="w-6 h-6 flex items-center justify-center rounded-full bg-green-200 text-green-800 text-sm font-bold">{{ i + 1 }}</span>
                                            <div>
                                                <span class="font-medium">{{ op.name }}</span>
                                                <p class="text-xs text-gray-500">Eng yaxshi kanal: {{ op.best_channel || '-' }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-green-600 font-bold">{{ op.conversion }}%</span>
                                            <p class="text-xs text-gray-500">{{ formatNumber(op.revenue) }} so'm</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-red-50 rounded-lg p-6 border border-red-200" v-if="bottomPerformers.length > 0">
                                <h3 class="text-lg font-semibold text-red-800 mb-4 flex items-center gap-2">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    Yordam kerak
                                </h3>
                                <div class="space-y-3">
                                    <div v-for="(op, i) in bottomPerformers" :key="op.id" class="flex items-center justify-between bg-white rounded-lg p-3">
                                        <div class="flex items-center gap-3">
                                            <span class="w-6 h-6 flex items-center justify-center rounded-full bg-red-200 text-red-800 text-sm font-bold">{{ i + 1 }}</span>
                                            <span class="font-medium">{{ op.name }}</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-red-600 font-bold">{{ op.conversion }}%</span>
                                            <p class="text-xs text-gray-500">{{ op.lost }} yo'qotilgan</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- No operators message -->
                        <div v-else class="bg-gray-50 rounded-lg p-6 text-center text-gray-500">
                            Bu hafta uchun operator ma'lumotlari mavjud emas
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
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Lidlar</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Yutilgan</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Konversiya</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Daromad</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Javob vaqti</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Eng yaxshi kanal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="op in data.operators" :key="op.id" class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="h-10 w-10 flex-shrink-0 rounded-full bg-indigo-100 flex items-center justify-center">
                                                        <span class="text-indigo-600 font-medium">{{ op.name?.charAt(0) }}</span>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">{{ op.name }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-center text-sm text-gray-900">{{ op.leads }}</td>
                                            <td class="px-6 py-4 text-center text-sm text-green-600 font-medium">{{ op.won }}</td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="px-2 py-1 text-sm font-bold rounded" :class="getConversionColor(op.conversion, avgConversion)">
                                                    {{ op.conversion }}%
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">{{ formatNumber(op.revenue) }}</td>
                                            <td class="px-6 py-4 text-center text-sm text-gray-500">{{ formatResponseTime(op.avg_response_minutes) }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-900">{{ op.best_channel || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Lost Deals Tab -->
                    <div v-show="activeTab === 'lost'" class="space-y-6">
                        <!-- Summary Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="bg-red-50 rounded-lg p-6 border border-red-200">
                                <p class="text-sm font-medium text-red-600">Jami yo'qotilgan</p>
                                <p class="text-3xl font-bold text-red-700">{{ data.lost_reasons?.total_lost }}</p>
                            </div>
                            <div class="bg-orange-50 rounded-lg p-6 border border-orange-200">
                                <p class="text-sm font-medium text-orange-600">Yo'qotilgan qiymat</p>
                                <p class="text-3xl font-bold text-orange-700">{{ formatNumber(data.lost_reasons?.total_value_lost) }}</p>
                                <p class="text-xs text-orange-500">so'm</p>
                            </div>
                            <div class="bg-yellow-50 rounded-lg p-6 border border-yellow-200">
                                <p class="text-sm font-medium text-yellow-600">Asosiy sabab</p>
                                <p class="text-xl font-bold text-yellow-700">{{ data.lost_reasons?.reasons?.[0]?.label || '-' }}</p>
                                <p class="text-xs text-yellow-500">{{ data.lost_reasons?.reasons?.[0]?.percentage }}%</p>
                            </div>
                        </div>

                        <!-- Reasons Breakdown -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Yo'qotish sabablari</h3>
                            <div class="space-y-4">
                                <div v-for="reason in data.lost_reasons?.reasons" :key="reason.reason" class="p-4 bg-gray-50 rounded-lg">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-medium text-gray-900">{{ reason.label }}</h4>
                                        <div class="text-right">
                                            <span class="text-sm text-gray-500">{{ reason.count }} lid ({{ reason.percentage }}%)</span>
                                            <span class="ml-2 text-xs" :class="getChangeColor(reason.vs_last_week)">
                                                {{ reason.vs_last_week > 0 ? '+' : '' }}{{ reason.vs_last_week }}%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div class="bg-red-500 h-3 rounded-full transition-all" :style="{ width: reason.percentage + '%' }"></div>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-600">Yo'qotilgan qiymat: {{ formatNumber(reason.value_lost) }} so'm</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Time Analysis Tab -->
                    <div v-show="activeTab === 'time'" class="space-y-6">
                        <!-- Best/Worst Summary -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                                <p class="text-xs text-green-600 uppercase">Eng yaxshi kun</p>
                                <p class="text-lg font-bold text-green-800">{{ data.time_stats?.by_day?.[data.time_stats?.best_day]?.label }}</p>
                            </div>
                            <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                                <p class="text-xs text-red-600 uppercase">Eng yomon kun</p>
                                <p class="text-lg font-bold text-red-800">{{ data.time_stats?.by_day?.[data.time_stats?.worst_day]?.label }}</p>
                            </div>
                            <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                                <p class="text-xs text-green-600 uppercase">Eng yaxshi vaqt</p>
                                <p class="text-lg font-bold text-green-800">{{ data.time_stats?.by_hour?.[data.time_stats?.best_hour]?.label }}</p>
                            </div>
                            <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                                <p class="text-xs text-red-600 uppercase">Eng yomon vaqt</p>
                                <p class="text-lg font-bold text-red-800">{{ data.time_stats?.by_hour?.[data.time_stats?.worst_hour]?.label }}</p>
                            </div>
                        </div>

                        <!-- By Day of Week -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Hafta kunlari bo'yicha</h3>
                            <div class="grid grid-cols-7 gap-2">
                                <div v-for="(dayData, day) in data.time_stats?.by_day" :key="day" class="text-center p-3 rounded-lg" :class="day === data.time_stats?.best_day ? 'bg-green-50 border-2 border-green-300' : 'bg-gray-50'">
                                    <p class="text-sm font-medium text-gray-700">{{ dayData.label }}</p>
                                    <p class="text-lg font-bold text-gray-900">{{ dayData.leads }}</p>
                                    <p class="text-xs text-gray-500">lid</p>
                                    <p class="text-sm font-bold" :class="dayData.conversion >= avgConversion ? 'text-green-600' : 'text-red-600'">{{ dayData.conversion }}%</p>
                                </div>
                            </div>
                        </div>

                        <!-- By Hour Ranges -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Soatlar bo'yicha</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div v-for="(hourData, hour) in data.time_stats?.by_hour" :key="hour" class="text-center p-4 rounded-lg" :class="hour === data.time_stats?.best_hour ? 'bg-green-50 border-2 border-green-300' : 'bg-gray-50'">
                                    <p class="text-sm font-medium text-gray-700">{{ hourData.label }}</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ hourData.leads }}</p>
                                    <p class="text-xs text-gray-500">lid</p>
                                    <p class="text-lg font-bold" :class="hourData.conversion >= avgConversion ? 'text-green-600' : 'text-red-600'">{{ hourData.conversion }}%</p>
                                    <p class="text-xs text-gray-500">{{ hourData.won }} yutilgan</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Analysis Tab -->
                    <div v-show="activeTab === 'ai'" class="space-y-6">
                        <!-- Generate AI Button -->
                        <div v-if="!data.ai?.has_analysis" class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg p-8 text-center border border-purple-200">
                            <svg class="mx-auto h-16 w-16 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            <h3 class="mt-4 text-xl font-semibold text-gray-900">AI Tahlil</h3>
                            <p class="mt-2 text-gray-600">Sun'iy intellekt yordamida haftalik natijalarni tahlil qiling</p>
                            <button
                                @click="generateAi"
                                :disabled="aiLoading"
                                class="mt-6 px-6 py-3 bg-purple-600 text-white font-medium rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <span v-if="aiLoading" class="flex items-center gap-2">
                                    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                    Tahlil qilinmoqda...
                                </span>
                                <span v-else>AI Tahlil yaratish</span>
                            </button>
                        </div>

                        <!-- AI Results -->
                        <template v-else>
                            <div class="text-sm text-gray-500 mb-4">
                                Yaratilgan: {{ data.ai.generated_at }} | Tokenlar: {{ data.ai.tokens_used }}
                            </div>

                            <!-- Good Results -->
                            <div class="bg-green-50 rounded-lg p-6 border border-green-200">
                                <h3 class="text-lg font-semibold text-green-800 mb-4 flex items-center gap-2">
                                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Yaxshi natijalar
                                </h3>
                                <ul class="space-y-2">
                                    <li v-for="(item, i) in data.ai.good_results" :key="i" class="text-green-800">
                                        {{ item }}
                                    </li>
                                </ul>
                            </div>

                            <!-- Problems -->
                            <div class="bg-red-50 rounded-lg p-6 border border-red-200">
                                <h3 class="text-lg font-semibold text-red-800 mb-4 flex items-center gap-2">
                                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    Muammolar
                                </h3>
                                <ul class="space-y-2">
                                    <li v-for="(item, i) in data.ai.problems" :key="i" class="text-red-800">
                                        {{ item }}
                                    </li>
                                </ul>
                            </div>

                            <!-- Recommendations -->
                            <div class="bg-blue-50 rounded-lg p-6 border border-blue-200">
                                <h3 class="text-lg font-semibold text-blue-800 mb-4 flex items-center gap-2">
                                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.476.859h4.002z" />
                                    </svg>
                                    Tavsiyalar
                                </h3>
                                <ol class="space-y-2 list-decimal list-inside">
                                    <li v-for="(item, i) in data.ai.recommendations" :key="i" class="text-blue-800">
                                        {{ item }}
                                    </li>
                                </ol>
                            </div>

                            <!-- Next Week Goal -->
                            <div class="bg-purple-50 rounded-lg p-6 border border-purple-200">
                                <h3 class="text-lg font-semibold text-purple-800 mb-4 flex items-center gap-2">
                                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Keyingi hafta maqsadi
                                </h3>
                                <p class="text-purple-800 text-lg">{{ data.ai.next_week_goal }}</p>
                            </div>
                        </template>
                    </div>
                </template>
            </template>
        </div>
    </BusinessLayout>
</template>
