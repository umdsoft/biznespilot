<template>
    <BusinessLayout title="Kanal Tahlili">
        <div class="py-12">
            <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Kanal Tahlili</h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Barcha messaging kanallar bo'yicha batafsil statistika
                    </p>
                </div>

                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Channel Selector -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kanal</label>
                            <select
                                v-model="currentChannel"
                                @change="updateChannel"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-purple-500 focus:border-purple-500"
                            >
                                <option value="whatsapp">WhatsApp</option>
                                <option value="instagram">Instagram</option>
                                <option value="telegram">Telegram</option>
                                <option value="facebook">Facebook</option>
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Boshlanish</label>
                            <input
                                v-model="startDate"
                                @change="updateDateRange"
                                type="date"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-purple-500 focus:border-purple-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tugash</label>
                            <input
                                v-model="endDate"
                                @change="updateDateRange"
                                type="date"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-purple-500 focus:border-purple-500"
                            />
                        </div>
                    </div>

                    <div class="mt-4">
                        <button
                            @click="compareChannels"
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
                        >
                            Kanallarni Taqqoslash
                        </button>
                    </div>
                </div>

                <!-- Overview Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-md p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-90">Jami Suhbatlar</p>
                                <p class="text-3xl font-bold mt-2">{{ analytics.overview?.total_conversations || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z" />
                                    <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-md p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-90">Jami Xabarlar</p>
                                <p class="text-3xl font-bold mt-2">{{ analytics.overview?.total_messages || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-md p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-90">Noyob Mijozlar</p>
                                <p class="text-3xl font-bold mt-2">{{ analytics.overview?.unique_customers || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-md p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-90">O'rtacha Javob Vaqti</p>
                                <p class="text-3xl font-bold mt-2">
                                    {{ analytics.response_metrics?.avg_response_time_minutes?.toFixed(1) || 0 }}<span class="text-lg">min</span>
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 1 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Message Volume -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Xabarlar Hajmi</h3>
                        <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <p class="text-sm">
                                    Kunlik: {{ analytics.message_volume?.daily?.length || 0 }} kun
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    Chart.js integratsiyasi kerak
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Engagement Metrics -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Engagement Metrikalari</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Faol Suhbatlar</span>
                                <span class="text-lg font-bold text-green-600 dark:text-green-400">
                                    {{ analytics.engagement_metrics?.active_conversations || 0 }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Yopilgan Suhbatlar</span>
                                <span class="text-lg font-bold text-gray-600 dark:text-gray-300">
                                    {{ analytics.engagement_metrics?.closed_conversations || 0 }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-sm text-gray-700 dark:text-gray-300">O'rtacha Davomiylik</span>
                                <span class="text-lg font-bold text-purple-600 dark:text-purple-400">
                                    {{ analytics.engagement_metrics?.avg_conversation_duration_minutes?.toFixed(1) || 0 }} min
                                </span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Qaytib Kelganlar</span>
                                <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                    {{ analytics.engagement_metrics?.returning_customers || 0 }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Conversion Funnel -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Konversiya Voronkasi</h3>
                        <div class="space-y-3">
                            <div class="text-center mb-4">
                                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                                    {{ analytics.conversion_metrics?.conversion_rate || 0 }}%
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Konversiya Darajasi</div>
                            </div>

                            <div v-if="analytics.conversion_metrics?.stages_distribution" class="space-y-2">
                                <div
                                    v-for="(count, stage) in analytics.conversion_metrics.stages_distribution"
                                    :key="stage"
                                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                                >
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ stage }}</span>
                                    <span class="text-sm font-bold text-purple-600 dark:text-purple-400">{{ count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hourly Distribution -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Soatlik Taqsimot</h3>
                        <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm">
                                    {{ analytics.hourly_distribution?.length || 0 }} soatlik ma'lumot
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    Chart.js integratsiyasi kerak
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Response Metrics -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Javob Metrikalari</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 rounded-xl">
                            <div class="text-sm text-blue-700 dark:text-blue-300 mb-2">O'rtacha Javob Vaqti</div>
                            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                {{ analytics.response_metrics?.avg_response_time_seconds?.toFixed(1) || 0 }}s
                            </div>
                        </div>
                        <div class="text-center p-6 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 rounded-xl">
                            <div class="text-sm text-green-700 dark:text-green-300 mb-2">Javob Darajasi</div>
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                                {{ analytics.response_metrics?.response_rate?.toFixed(1) || 0 }}%
                            </div>
                        </div>
                        <div class="text-center p-6 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 rounded-xl">
                            <div class="text-sm text-purple-700 dark:text-purple-300 mb-2">O'rtacha Xabarlar</div>
                            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                                {{ analytics.overview?.avg_messages_per_conversation?.toFixed(1) || 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Channel Comparison Modal -->
                <div v-if="showComparison" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Kanallar Taqqoslash</h3>
                            <button @click="showComparison = false" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="p-6">
                            <div v-if="comparisonData" class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="border-b border-gray-200 dark:border-gray-700">
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Kanal</th>
                                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-900 dark:text-gray-100">Suhbatlar</th>
                                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-900 dark:text-gray-100">Xabarlar</th>
                                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-900 dark:text-gray-100">Konversiya %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(data, channel) in comparisonData"
                                            :key="channel"
                                            class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                                        >
                                            <td class="px-4 py-3">
                                                <div class="flex items-center">
                                                    <span class="text-xl mr-2">{{ getChannelIcon(channel) }}</span>
                                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ getChannelName(channel) }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-center text-gray-900 dark:text-gray-100">{{ data.conversations }}</td>
                                            <td class="px-4 py-3 text-center text-gray-900 dark:text-gray-100">{{ data.messages }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300 rounded-full font-semibold">
                                                    {{ data.conversion_rate }}%
                                                </span>
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

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import BusinessLayout from '@/Layouts/BusinessLayout.vue'
import axios from 'axios'

const props = defineProps({
    analytics: Object,
    selectedChannel: String,
    dateRange: Object,
    currentBusiness: Object
})

const currentChannel = ref(props.selectedChannel || 'whatsapp')
const startDate = ref(props.dateRange?.start || '')
const endDate = ref(props.dateRange?.end || '')
const showComparison = ref(false)
const comparisonData = ref(null)

const updateChannel = () => {
    router.get(route('business.analytics.channels.index'), {
        channel: currentChannel.value,
        start_date: startDate.value,
        end_date: endDate.value
    })
}

const updateDateRange = () => {
    router.get(route('business.analytics.channels.index'), {
        channel: currentChannel.value,
        start_date: startDate.value,
        end_date: endDate.value
    })
}

const compareChannels = async () => {
    try {
        const response = await axios.post(route('business.analytics.channels.compare'), {
            start_date: startDate.value,
            end_date: endDate.value
        })

        if (response.data.success) {
            comparisonData.value = response.data.comparison
            showComparison.value = true
        }
    } catch (error) {
        alert('Kanallarni taqqoslashda xatolik yuz berdi')
    }
}

const getChannelIcon = (channel) => {
    const icons = {
        whatsapp: '💬',
        instagram: '📸',
        telegram: '✈️',
        facebook: '👥'
    }
    return icons[channel] || '💬'
}

const getChannelName = (channel) => {
    const names = {
        whatsapp: 'WhatsApp',
        instagram: 'Instagram',
        telegram: 'Telegram',
        facebook: 'Facebook'
    }
    return names[channel] || channel
}
</script>
