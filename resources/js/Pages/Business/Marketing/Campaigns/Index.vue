<template>
    <BusinessLayout title="Marketing Kampaniyalari">
        <div class="py-12">
            <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900">Marketing Kampaniyalari</h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Barcha kanallar bo'ylab marketing kampaniyalarini boshqaring
                        </p>
                    </div>
                    <Link
                        :href="route('business.marketing.campaigns.create')"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Yangi Kampaniya
                    </Link>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Jami Kampaniyalar</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1">{{ campaigns.length }}</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Faol Kampaniyalar</p>
                                <p class="text-2xl font-bold text-green-600 mt-1">
                                    {{ campaigns.filter(c => c.status === 'running').length }}
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Yuborilgan Xabarlar</p>
                                <p class="text-2xl font-bold text-blue-600 mt-1">
                                    {{ campaigns.reduce((sum, c) => sum + (c.sent_count || 0), 0) }}
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Rejalashtirilgan</p>
                                <p class="text-2xl font-bold text-orange-600 mt-1">
                                    {{ campaigns.filter(c => c.status === 'scheduled').length }}
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Campaigns Table -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Barcha Kampaniyalar</h3>
                    </div>

                    <div v-if="campaigns.length === 0" class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Kampaniya yo'q</h3>
                        <p class="mt-1 text-sm text-gray-500">Birinchi marketing kampaniyangizni yarating.</p>
                        <div class="mt-6">
                            <Link
                                :href="route('business.marketing.campaigns.create')"
                                class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
                            >
                                Kampaniya Yaratish
                            </Link>
                        </div>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nomi
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Turi
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Kanal
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Yuborildi
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Yaratilgan
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Harakatlar
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="campaign in campaigns" :key="campaign.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ campaign.name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                              :class="{
                                                  'bg-blue-100 text-blue-800': campaign.type === 'broadcast',
                                                  'bg-purple-100 text-purple-800': campaign.type === 'drip',
                                                  'bg-green-100 text-green-800': campaign.type === 'trigger'
                                              }">
                                            {{ campaign.type === 'broadcast' ? 'Ommaviy' : campaign.type === 'drip' ? 'Drip' : 'Trigger' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ channelName(campaign.channel) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                              :class="{
                                                  'bg-gray-100 text-gray-800': campaign.status === 'draft',
                                                  'bg-orange-100 text-orange-800': campaign.status === 'scheduled',
                                                  'bg-green-100 text-green-800': campaign.status === 'running',
                                                  'bg-blue-100 text-blue-800': campaign.status === 'completed',
                                                  'bg-yellow-100 text-yellow-800': campaign.status === 'paused',
                                                  'bg-red-100 text-red-800': campaign.status === 'failed'
                                              }">
                                            {{ statusName(campaign.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ campaign.sent_count || 0 }} / {{ (campaign.sent_count || 0) + (campaign.failed_count || 0) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ campaign.created_at }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            v-if="campaign.status === 'draft' || campaign.status === 'scheduled'"
                                            @click="launchCampaign(campaign.id)"
                                            class="text-purple-600 hover:text-purple-900 mr-3"
                                        >
                                            Ishga tushirish
                                        </button>
                                        <Link
                                            :href="route('business.marketing.campaigns.show', campaign.id)"
                                            class="text-blue-600 hover:text-blue-900"
                                        >
                                            Ko'rish
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'

const props = defineProps({
    campaigns: Array,
    currentBusiness: Object
})

const channelName = (channel) => {
    const names = {
        whatsapp: 'WhatsApp',
        instagram: 'Instagram',
        telegram: 'Telegram',
        facebook: 'Facebook',
        all: 'Barcha kanallar'
    }
    return names[channel] || channel
}

const statusName = (status) => {
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

const launchCampaign = (campaignId) => {
    if (confirm('Kampaniyani ishga tushirishni xohlaysizmi?')) {
        router.post(route('business.marketing.campaigns.launch', campaignId), {}, {
            onSuccess: () => {
                alert('Kampaniya muvaffaqiyatli ishga tushirildi!')
            }
        })
    }
}
</script>
