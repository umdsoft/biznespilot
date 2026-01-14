<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import axios from 'axios';

const props = defineProps({
    campaign: Object,
    currentBusiness: Object,
});

// State
const loading = ref(false);
const adGroups = ref([]);
const keywords = ref({});
const activeTab = ref('adgroups');
const selectedAdGroup = ref(null);
const showKeywordModal = ref(false);
const newKeywords = ref('');
const addingKeywords = ref(false);

// Formatting helpers
const formatNumber = (num) => {
    if (!num) return '0';
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toLocaleString();
};

const formatCurrency = (num) => {
    if (!num) return "0 so'm";
    return new Intl.NumberFormat('uz-UZ').format(num) + " so'm";
};

const formatPercent = (num) => {
    if (!num) return '0%';
    return Number(num).toFixed(2) + '%';
};

const getStatusClass = (status) => {
    switch (status) {
        case 'ENABLED':
            return 'bg-green-500/20 text-green-500';
        case 'PAUSED':
            return 'bg-yellow-500/20 text-yellow-600';
        case 'REMOVED':
            return 'bg-red-500/20 text-red-500';
        default:
            return 'bg-gray-500/20 text-gray-500';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'ENABLED': return 'Faol';
        case 'PAUSED': return 'Pauza';
        case 'REMOVED': return "O'chirilgan";
        default: return status;
    }
};

const getMatchTypeLabel = (type) => {
    switch (type) {
        case 'EXACT': return 'Aniq';
        case 'PHRASE': return 'Ibora';
        case 'BROAD': return 'Keng';
        default: return type;
    }
};

const getMatchTypeClass = (type) => {
    switch (type) {
        case 'EXACT': return 'bg-blue-500/20 text-blue-500';
        case 'PHRASE': return 'bg-purple-500/20 text-purple-500';
        case 'BROAD': return 'bg-orange-500/20 text-orange-600';
        default: return 'bg-gray-500/20 text-gray-500';
    }
};

// Load ad groups
const loadAdGroups = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/business/api/google-ads-campaigns/${props.campaign.id}/ad-groups`);
        if (response.data.success) {
            adGroups.value = response.data.data;
        }
    } catch (error) {
        console.error('Error loading ad groups:', error);
    } finally {
        loading.value = false;
    }
};

// Load keywords for ad group
const loadKeywords = async (adGroupId) => {
    try {
        const response = await axios.get(`/business/api/google-ads-campaigns/${props.campaign.id}/ad-groups/${adGroupId}/keywords`);
        if (response.data.success) {
            keywords.value[adGroupId] = response.data.data;
        }
    } catch (error) {
        console.error('Error loading keywords:', error);
    }
};

// Toggle ad group to show keywords
const toggleAdGroup = async (adGroup) => {
    if (selectedAdGroup.value?.id === adGroup.id) {
        selectedAdGroup.value = null;
    } else {
        selectedAdGroup.value = adGroup;
        if (!keywords.value[adGroup.id]) {
            await loadKeywords(adGroup.id);
        }
    }
};

// Add keywords
const addKeywords = async () => {
    if (!newKeywords.value.trim() || !selectedAdGroup.value) return;

    addingKeywords.value = true;
    try {
        const keywordsList = newKeywords.value.split('\n')
            .map(k => k.trim())
            .filter(k => k.length > 0)
            .map(text => ({ text, match_type: 'BROAD' }));

        const response = await axios.post(`/business/api/google-ads-campaigns/${props.campaign.id}/ad-groups/${selectedAdGroup.value.id}/keywords`, {
            keywords: keywordsList,
        });

        if (response.data.success) {
            await loadKeywords(selectedAdGroup.value.id);
            newKeywords.value = '';
            showKeywordModal.value = false;
        }
    } catch (error) {
        console.error('Error adding keywords:', error);
    } finally {
        addingKeywords.value = false;
    }
};

// Remove keyword
const removeKeyword = async (keywordId) => {
    if (!confirm("Bu kalit so'zni o'chirmoqchimisiz?")) return;

    try {
        const response = await axios.delete(`/business/api/google-ads-campaigns/keywords/${keywordId}`);
        if (response.data.success && selectedAdGroup.value) {
            await loadKeywords(selectedAdGroup.value.id);
        }
    } catch (error) {
        console.error('Error removing keyword:', error);
    }
};

// Toggle keyword status
const toggleKeywordStatus = async (keyword) => {
    const newStatus = keyword.status === 'ENABLED' ? 'PAUSED' : 'ENABLED';
    try {
        await axios.patch(`/business/api/google-ads-campaigns/keywords/${keyword.id}/status`, {
            status: newStatus,
        });
        keyword.status = newStatus;
    } catch (error) {
        console.error('Error updating keyword status:', error);
    }
};

// Toggle campaign status
const toggleCampaignStatus = async () => {
    const newStatus = props.campaign.status === 'ENABLED' ? 'PAUSED' : 'ENABLED';
    try {
        await axios.patch(`/business/api/google-ads-campaigns/${props.campaign.id}/status`, {
            status: newStatus,
        });
        props.campaign.status = newStatus;
    } catch (error) {
        console.error('Error updating campaign status:', error);
    }
};

// Initialize
onMounted(() => {
    loadAdGroups();
});
</script>

<template>
    <Head :title="`${campaign.name} - Google Ads`" />

    <BusinessLayout :title="campaign.name">
        <div class="space-y-6">
            <!-- Back Button -->
            <Link
                :href="route('business.google-ads-analytics')"
                class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
            >
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Google Ads Analitika
            </Link>

            <!-- Campaign Header -->
            <div class="bg-gradient-to-r from-blue-600 via-cyan-600 to-teal-600 rounded-2xl p-6 text-white shadow-xl">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    <!-- Campaign Info -->
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-3">
                            <span :class="[getStatusClass(campaign.status), 'px-3 py-1 text-sm font-semibold rounded-full']">
                                {{ getStatusLabel(campaign.status) }}
                            </span>
                            <span class="px-3 py-1 bg-white/20 text-white text-sm font-medium rounded-full">
                                {{ campaign.advertising_channel_type }}
                            </span>
                        </div>
                        <h1 class="text-2xl lg:text-3xl font-bold mb-2">{{ campaign.name }}</h1>
                        <p v-if="campaign.google_campaign_id" class="text-white/70 font-mono text-sm">
                            ID: {{ campaign.google_campaign_id }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3">
                        <button
                            @click="toggleCampaignStatus"
                            class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-xl text-white font-medium transition-colors"
                        >
                            {{ campaign.status === 'ENABLED' ? "To'xtatish" : 'Faollashtirish' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Byudjet</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(campaign.daily_budget) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">kunlik</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Xarajat</p>
                    <p class="text-xl font-bold text-orange-500">{{ formatCurrency(campaign.total_cost) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Ko'rishlar</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatNumber(campaign.total_impressions) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Kliklar</p>
                    <p class="text-xl font-bold text-blue-500">{{ formatNumber(campaign.total_clicks) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">CTR</p>
                    <p class="text-xl font-bold text-purple-500">{{ formatPercent(campaign.avg_ctr) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">CPC</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(campaign.avg_cpc) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Konversiya</p>
                    <p class="text-xl font-bold text-green-500">{{ formatNumber(campaign.total_conversions) }}</p>
                </div>
            </div>

            <!-- Ad Groups & Keywords -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Reklama guruhlari</h2>
                    <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-sm font-medium rounded-full">
                        {{ adGroups.length }} ta
                    </span>
                </div>

                <div class="p-6">
                    <!-- Loading -->
                    <div v-if="loading" class="text-center py-12">
                        <svg class="w-8 h-8 text-blue-500 animate-spin mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">Yuklanmoqda...</p>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="adGroups.length === 0" class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400 mb-4">Reklama guruhlari topilmadi</p>
                    </div>

                    <!-- Ad Groups List -->
                    <div v-else class="space-y-4">
                        <div
                            v-for="adGroup in adGroups"
                            :key="adGroup.id"
                            class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden"
                        >
                            <!-- Ad Group Header -->
                            <div
                                @click="toggleAdGroup(adGroup)"
                                class="p-4 bg-gray-50 dark:bg-gray-700/50 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <svg
                                            class="w-5 h-5 text-gray-400 transition-transform"
                                            :class="{ 'rotate-90': selectedAdGroup?.id === adGroup.id }"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ adGroup.name }}</h3>
                                        <span :class="[getStatusClass(adGroup.status), 'px-2 py-0.5 text-xs font-medium rounded-full']">
                                            {{ getStatusLabel(adGroup.status) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-4 text-sm">
                                        <div class="text-right">
                                            <p class="text-gray-500 dark:text-gray-400">CPC Bid</p>
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(adGroup.cpc_bid) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Keywords Section -->
                            <div v-if="selectedAdGroup?.id === adGroup.id" class="p-4 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-medium text-gray-900 dark:text-white">Kalit so'zlar</h4>
                                    <button
                                        @click="showKeywordModal = true"
                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 transition-colors"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Qo'shish
                                    </button>
                                </div>

                                <!-- Keywords List -->
                                <div v-if="keywords[adGroup.id] && keywords[adGroup.id].length > 0" class="space-y-2">
                                    <div
                                        v-for="keyword in keywords[adGroup.id]"
                                        :key="keyword.id"
                                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                                    >
                                        <div class="flex items-center gap-3">
                                            <span :class="[getMatchTypeClass(keyword.match_type), 'px-2 py-0.5 text-xs font-medium rounded']">
                                                {{ getMatchTypeLabel(keyword.match_type) }}
                                            </span>
                                            <span class="text-gray-900 dark:text-white">{{ keyword.keyword_text }}</span>
                                            <span :class="[getStatusClass(keyword.status), 'px-2 py-0.5 text-xs font-medium rounded-full']">
                                                {{ getStatusLabel(keyword.status) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span v-if="keyword.quality_score" class="text-sm text-gray-500 dark:text-gray-400">
                                                QS: {{ keyword.quality_score }}/10
                                            </span>
                                            <button
                                                @click="toggleKeywordStatus(keyword)"
                                                class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                                                :title="keyword.status === 'ENABLED' ? 'To\'xtatish' : 'Faollashtirish'"
                                            >
                                                <svg v-if="keyword.status === 'ENABLED'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="removeKeyword(keyword.id)"
                                                class="p-1.5 text-red-400 hover:text-red-600 transition-colors"
                                                title="O'chirish"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty Keywords -->
                                <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                                    <p>Kalit so'zlar topilmadi</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Keywords Modal -->
        <div v-if="showKeywordModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showKeywordModal = false"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-2xl">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Kalit so'z qo'shish</h2>
                        <button
                            @click="showKeywordModal = false"
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="p-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Kalit so'zlar (har bir qatorda bitta)
                        </label>
                        <textarea
                            v-model="newKeywords"
                            rows="6"
                            placeholder="marketing xizmatlari&#10;reklama agentligi&#10;SMM xizmatlari"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white resize-none"
                        ></textarea>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Har bir kalit so'zni yangi qatordan yozing
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 rounded-b-2xl">
                        <button
                            @click="showKeywordModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            Bekor qilish
                        </button>
                        <button
                            @click="addKeywords"
                            :disabled="!newKeywords.trim() || addingKeywords"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50"
                        >
                            <svg v-if="addingKeywords" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ addingKeywords ? 'Qo\'shilmoqda...' : 'Qo\'shish' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
