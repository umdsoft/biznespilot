<template>
    <BusinessLayout title="Marketing Kampaniyalari">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Marketing Kampaniyalari</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Barcha kanallar bo'ylab marketing kampaniyalarini boshqaring
                    </p>
                </div>
                <Link
                    :href="route('business.marketing.campaigns.create')"
                    class="inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-sm font-medium rounded-xl shadow-lg shadow-purple-500/25 hover:shadow-purple-500/40 transition-all duration-200"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Yangi Kampaniya
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Jami Kampaniyalar -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-shadow duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Jami Kampaniyalar</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ campaigns.length }}</p>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-100 to-indigo-100 dark:from-purple-900/40 dark:to-indigo-900/40 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center text-xs text-gray-500 dark:text-gray-400">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Barcha turlar
                    </div>
                </div>

                <!-- Faol Kampaniyalar -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-shadow duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Faol Kampaniyalar</p>
                            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                                {{ activeCampaigns }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-emerald-100 to-green-100 dark:from-emerald-900/40 dark:to-green-900/40 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center text-xs text-emerald-600 dark:text-emerald-400">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full mr-2 animate-pulse"></span>
                        Hozir ishlayapti
                    </div>
                </div>

                <!-- Yuborilgan Xabarlar -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-shadow duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Yuborilgan Xabarlar</p>
                            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1">
                                {{ totalSentMessages }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-cyan-100 dark:from-blue-900/40 dark:to-cyan-900/40 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center text-xs text-gray-500 dark:text-gray-400">
                        <svg class="w-4 h-4 mr-1 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Muvaffaqiyatli yetkazildi
                    </div>
                </div>

                <!-- Rejalashtirilgan -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-shadow duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Rejalashtirilgan</p>
                            <p class="text-3xl font-bold text-amber-600 dark:text-amber-400 mt-1">
                                {{ scheduledCampaigns }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/40 dark:to-orange-900/40 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center text-xs text-amber-600 dark:text-amber-400">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Navbatda kutmoqda
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="filter in filterOptions"
                    :key="filter.value"
                    @click="activeFilter = filter.value"
                    class="px-4 py-2 text-sm font-medium rounded-xl transition-all duration-200"
                    :class="activeFilter === filter.value
                        ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/25'
                        : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-600'"
                >
                    {{ filter.label }}
                    <span
                        v-if="filter.count > 0"
                        class="ml-1.5 px-1.5 py-0.5 text-xs rounded-full"
                        :class="activeFilter === filter.value
                            ? 'bg-white/20'
                            : 'bg-gray-100 dark:bg-gray-700'"
                    >
                        {{ filter.count }}
                    </span>
                </button>
            </div>

            <!-- Campaigns List -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <!-- Empty State -->
                <div v-if="filteredCampaigns.length === 0" class="p-12 text-center">
                    <div class="w-20 h-20 mx-auto bg-gradient-to-br from-purple-100 to-indigo-100 dark:from-purple-900/40 dark:to-indigo-900/40 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ activeFilter === 'all' ? 'Kampaniya yo\'q' : 'Bu turkumda kampaniya yo\'q' }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                        Marketing kampaniyalari orqali mijozlaringizga xabarlar yuboring va ularni jalb qiling.
                    </p>
                    <Link
                        :href="route('business.marketing.campaigns.create')"
                        class="mt-6 inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-sm font-medium rounded-xl shadow-lg shadow-purple-500/25 hover:shadow-purple-500/40 transition-all duration-200"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Birinchi Kampaniyani Yarating
                    </Link>
                </div>

                <!-- Campaigns Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Kampaniya
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Turi
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Kanal
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Natija
                                </th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Harakatlar
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr
                                v-for="campaign in filteredCampaigns"
                                :key="campaign.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150"
                            >
                                <!-- Kampaniya nomi -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center mr-3" :class="getTypeIconBg(campaign.type)">
                                            <component :is="getTypeIcon(campaign.type)" class="w-5 h-5" :class="getTypeIconClass(campaign.type)" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ campaign.name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(campaign.created_at) }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Turi -->
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg"
                                        :class="getTypeBadgeClass(campaign.type)"
                                    >
                                        {{ getTypeName(campaign.type) }}
                                    </span>
                                </td>

                                <!-- Kanal -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <component :is="getChannelIcon(campaign.channel)" class="w-5 h-5 mr-2" :class="getChannelIconClass(campaign.channel)" />
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ getChannelName(campaign.channel) }}</span>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg"
                                        :class="getStatusBadgeClass(campaign.status)"
                                    >
                                        <span
                                            v-if="campaign.status === 'running'"
                                            class="w-1.5 h-1.5 bg-current rounded-full mr-1.5 animate-pulse"
                                        ></span>
                                        {{ getStatusName(campaign.status) }}
                                    </span>
                                </td>

                                <!-- Natija -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-1 max-w-[120px]">
                                            <div class="flex justify-between text-xs mb-1">
                                                <span class="text-gray-500 dark:text-gray-400">Yuborildi</span>
                                                <span class="font-medium text-gray-900 dark:text-white">{{ campaign.sent_count || 0 }}</span>
                                            </div>
                                            <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                                <div
                                                    class="h-full bg-gradient-to-r from-emerald-500 to-green-500 rounded-full transition-all duration-500"
                                                    :style="{ width: getSuccessRate(campaign) + '%' }"
                                                ></div>
                                            </div>
                                        </div>
                                        <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                            {{ getSuccessRate(campaign) }}%
                                        </span>
                                    </div>
                                </td>

                                <!-- Harakatlar -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end space-x-2">
                                        <button
                                            v-if="campaign.status === 'draft' || campaign.status === 'scheduled'"
                                            @click="launchCampaign(campaign.id)"
                                            class="p-2 text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/30 rounded-lg transition-colors"
                                            title="Ishga tushirish"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                        <button
                                            v-if="campaign.status === 'running'"
                                            @click="pauseCampaign(campaign.id)"
                                            class="p-2 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition-colors"
                                            title="To'xtatish"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                        <Link
                                            :href="route('business.marketing.campaigns.show', campaign.id)"
                                            class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                                            title="Ko'rish"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </Link>
                                        <button
                                            @click="deleteCampaign(campaign.id)"
                                            class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                            title="O'chirish"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Tips -->
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-2xl p-6 border border-purple-100 dark:border-purple-800/30">
                <div class="flex items-start">
                    <div class="w-10 h-10 bg-white dark:bg-gray-800 rounded-xl flex items-center justify-center shadow-sm mr-4 flex-shrink-0">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Kampaniya turlari haqida</h4>
                        <ul class="mt-2 space-y-1.5 text-sm text-gray-600 dark:text-gray-400">
                            <li class="flex items-center">
                                <span class="w-1.5 h-1.5 bg-blue-500 rounded-full mr-2"></span>
                                <strong class="text-gray-700 dark:text-gray-300">Ommaviy:</strong>&nbsp;Bir vaqtda ko'p mijozlarga xabar yuborish
                            </li>
                            <li class="flex items-center">
                                <span class="w-1.5 h-1.5 bg-purple-500 rounded-full mr-2"></span>
                                <strong class="text-gray-700 dark:text-gray-300">Drip:</strong>&nbsp;Ketma-ket avtomatik xabarlar zanjiri
                            </li>
                            <li class="flex items-center">
                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-2"></span>
                                <strong class="text-gray-700 dark:text-gray-300">Trigger:</strong>&nbsp;Ma'lum hodisalarga javob sifatida yuboriladi
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>

<script setup>
import { ref, computed, h } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'

const props = defineProps({
    campaigns: {
        type: Array,
        default: () => []
    },
    currentBusiness: Object
})

const activeFilter = ref('all')

// Computed
const activeCampaigns = computed(() => props.campaigns.filter(c => c.status === 'running').length)
const scheduledCampaigns = computed(() => props.campaigns.filter(c => c.status === 'scheduled').length)
const totalSentMessages = computed(() => props.campaigns.reduce((sum, c) => sum + (c.sent_count || 0), 0))

const filterOptions = computed(() => [
    { value: 'all', label: 'Barchasi', count: props.campaigns.length },
    { value: 'running', label: 'Faol', count: activeCampaigns.value },
    { value: 'scheduled', label: 'Rejalashtirilgan', count: scheduledCampaigns.value },
    { value: 'draft', label: 'Qoralama', count: props.campaigns.filter(c => c.status === 'draft').length },
    { value: 'completed', label: 'Yakunlangan', count: props.campaigns.filter(c => c.status === 'completed').length }
])

const filteredCampaigns = computed(() => {
    if (activeFilter.value === 'all') return props.campaigns
    return props.campaigns.filter(c => c.status === activeFilter.value)
})

// Channel Icons (without WhatsApp)
const InstagramIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z' })
        ])
    }
}

const TelegramIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z' })
        ])
    }
}

const FacebookIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z' })
        ])
    }
}

const AllChannelsIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z' })
        ])
    }
}

// Type Icons
const BroadcastIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z' })
        ])
    }
}

const DripIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z' })
        ])
    }
}

const TriggerIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M13 10V3L4 14h7v7l9-11h-7z' })
        ])
    }
}

// Helper functions
const getChannelName = (channel) => {
    const names = {
        instagram: 'Instagram',
        telegram: 'Telegram',
        facebook: 'Facebook',
        all: 'Barcha kanallar'
    }
    return names[channel] || channel
}

const getChannelIcon = (channel) => {
    const icons = {
        instagram: InstagramIcon,
        telegram: TelegramIcon,
        facebook: FacebookIcon,
        all: AllChannelsIcon
    }
    return icons[channel] || AllChannelsIcon
}

const getChannelIconClass = (channel) => {
    const classes = {
        instagram: 'text-pink-600 dark:text-pink-400',
        telegram: 'text-sky-600 dark:text-sky-400',
        facebook: 'text-blue-600 dark:text-blue-400',
        all: 'text-purple-600 dark:text-purple-400'
    }
    return classes[channel] || 'text-gray-600 dark:text-gray-400'
}

const getTypeName = (type) => {
    const names = {
        broadcast: 'Ommaviy',
        drip: 'Drip',
        trigger: 'Trigger'
    }
    return names[type] || type
}

const getTypeIcon = (type) => {
    const icons = {
        broadcast: BroadcastIcon,
        drip: DripIcon,
        trigger: TriggerIcon
    }
    return icons[type] || BroadcastIcon
}

const getTypeIconBg = (type) => {
    const classes = {
        broadcast: 'bg-blue-100 dark:bg-blue-900/40',
        drip: 'bg-purple-100 dark:bg-purple-900/40',
        trigger: 'bg-emerald-100 dark:bg-emerald-900/40'
    }
    return classes[type] || 'bg-gray-100 dark:bg-gray-800'
}

const getTypeIconClass = (type) => {
    const classes = {
        broadcast: 'text-blue-600 dark:text-blue-400',
        drip: 'text-purple-600 dark:text-purple-400',
        trigger: 'text-emerald-600 dark:text-emerald-400'
    }
    return classes[type] || 'text-gray-600 dark:text-gray-400'
}

const getTypeBadgeClass = (type) => {
    const classes = {
        broadcast: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        drip: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
        trigger: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
    }
    return classes[type] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'
}

const getStatusName = (status) => {
    const names = {
        draft: 'Qoralama',
        scheduled: 'Rejalashtirilgan',
        running: 'Faol',
        completed: 'Yakunlangan',
        paused: 'To\'xtatilgan',
        failed: 'Xatolik'
    }
    return names[status] || status
}

const getStatusBadgeClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        scheduled: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        running: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        completed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        paused: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
        failed: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
    }
    return classes[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'
}

const getSuccessRate = (campaign) => {
    const total = (campaign.sent_count || 0) + (campaign.failed_count || 0)
    if (total === 0) return 0
    return Math.round((campaign.sent_count || 0) / total * 100)
}

const formatDate = (dateString) => {
    if (!dateString) return '-'
    const date = new Date(dateString)
    return date.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short', year: 'numeric' })
}

// Actions
const launchCampaign = (campaignId) => {
    if (confirm('Kampaniyani ishga tushirishni xohlaysizmi?')) {
        router.post(route('business.marketing.campaigns.launch', campaignId), {}, {
            preserveScroll: true
        })
    }
}

const pauseCampaign = (campaignId) => {
    if (confirm('Kampaniyani to\'xtatishni xohlaysizmi?')) {
        router.post(route('business.marketing.campaigns.pause', campaignId), {}, {
            preserveScroll: true
        })
    }
}

const deleteCampaign = (campaignId) => {
    if (confirm('Kampaniyani o\'chirishni xohlaysizmi? Bu amalni ortga qaytarib bo\'lmaydi.')) {
        router.delete(route('business.marketing.campaigns.destroy', campaignId), {
            preserveScroll: true
        })
    }
}
</script>
