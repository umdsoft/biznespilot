<template>
    <BusinessLayout :title="competitor.name">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <Link
                        :href="route('business.competitors.index')"
                        class="text-sm text-gray-500 hover:text-gray-700 mb-2 inline-flex items-center"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Orqaga
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ competitor.name }}
                    </h2>
                </div>
                <div class="flex gap-3">
                    <button
                        @click="generateSwot"
                        :disabled="generating_swot"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ generating_swot ? 'Tayyorlanmoqda...' : 'SWOT Tahlil' }}
                    </button>
                    <button
                        @click="monitorNow"
                        :disabled="monitoring"
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ monitoring ? 'Tekshirilmoqda...' : 'Hozir Tekshirish' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Competitor Info Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Asosiy Ma'lumotlar</h3>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Tavsif</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ competitor.description || 'Ma\'lumot yo\'q' }}</dd>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Soha</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ competitor.industry || '-' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Joylashuv</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ competitor.location || '-' }}</dd>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Tahdid Darajasi</dt>
                                            <dd class="mt-1">
                                                <span
                                                    :class="{
                                                        'bg-green-100 text-green-800': competitor.threat_level === 'low',
                                                        'bg-yellow-100 text-yellow-800': competitor.threat_level === 'medium',
                                                        'bg-orange-100 text-orange-800': competitor.threat_level === 'high',
                                                        'bg-red-100 text-red-800': competitor.threat_level === 'critical'
                                                    }"
                                                    class="px-2 py-1 text-xs font-semibold rounded"
                                                >
                                                    {{ getThreatLevelText(competitor.threat_level) }}
                                                </span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                                            <dd class="mt-1">
                                                <span
                                                    :class="{
                                                        'bg-green-100 text-green-800': competitor.status === 'active',
                                                        'bg-gray-100 text-gray-800': competitor.status === 'inactive',
                                                        'bg-blue-100 text-blue-800': competitor.status === 'archived'
                                                    }"
                                                    class="px-2 py-1 text-xs font-semibold rounded"
                                                >
                                                    {{ getStatusText(competitor.status) }}
                                                </span>
                                            </dd>
                                        </div>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 mb-2">Avtomatik Kuzatuv</dt>
                                        <dd class="text-sm text-gray-900">
                                            {{ competitor.auto_monitor ? `Yoqilgan (har ${competitor.check_frequency_hours} soatda)` : 'O\'chirilgan' }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Ijtimoiy Tarmoqlar</h3>
                                <dl class="space-y-3">
                                    <div v-if="competitor.instagram_handle">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-pink-600" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                            </svg>
                                            Instagram
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ competitor.instagram_handle }}</dd>
                                    </div>
                                    <div v-if="competitor.telegram_handle">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                                            </svg>
                                            Telegram
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ competitor.telegram_handle }}</dd>
                                    </div>
                                    <div v-if="competitor.facebook_page">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                            </svg>
                                            Facebook
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ competitor.facebook_page }}</dd>
                                    </div>
                                    <div v-if="competitor.tiktok_handle">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-gray-900" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
                                            </svg>
                                            TikTok
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ competitor.tiktok_handle }}</dd>
                                    </div>
                                    <div v-if="!competitor.instagram_handle && !competitor.telegram_handle && !competitor.facebook_page && !competitor.tiktok_handle">
                                        <p class="text-sm text-gray-500">Ijtimoiy tarmoqlar qo'shilmagan</p>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Latest Metrics -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" v-if="latest_metric">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">So'nggi Ko'rsatkichlar</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div v-if="latest_metric.instagram_followers">
                                <dt class="text-sm font-medium text-gray-500">Instagram Followers</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                    {{ latest_metric.instagram_followers.toLocaleString() }}
                                </dd>
                                <dd v-if="latest_metric.follower_growth_rate" class="text-sm" :class="latest_metric.follower_growth_rate > 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ latest_metric.follower_growth_rate > 0 ? '+' : '' }}{{ latest_metric.follower_growth_rate.toFixed(1) }}%
                                </dd>
                            </div>
                            <div v-if="latest_metric.instagram_engagement_rate">
                                <dt class="text-sm font-medium text-gray-500">Engagement Rate</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                    {{ latest_metric.instagram_engagement_rate.toFixed(1) }}%
                                </dd>
                                <dd v-if="latest_metric.engagement_growth_rate" class="text-sm" :class="latest_metric.engagement_growth_rate > 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ latest_metric.engagement_growth_rate > 0 ? '+' : '' }}{{ latest_metric.engagement_growth_rate.toFixed(1) }}%
                                </dd>
                            </div>
                            <div v-if="latest_metric.telegram_members">
                                <dt class="text-sm font-medium text-gray-500">Telegram Members</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                    {{ latest_metric.telegram_members.toLocaleString() }}
                                </dd>
                            </div>
                            <div v-if="latest_metric.facebook_followers">
                                <dt class="text-sm font-medium text-gray-500">Facebook Followers</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                    {{ latest_metric.facebook_followers.toLocaleString() }}
                                </dd>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-4">
                            So'nggi yangilanish: {{ formatDate(latest_metric.date) }}
                        </p>
                    </div>
                </div>

                <!-- SWOT Analysis -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" v-if="swot_analysis">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">SWOT Tahlil</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Strengths -->
                            <div class="border border-green-200 rounded-lg p-4 bg-green-50">
                                <h4 class="font-semibold text-green-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Kuchli Tomonlar (Strengths)
                                </h4>
                                <ul class="space-y-2">
                                    <li v-for="(strength, index) in swot_analysis.strengths" :key="index" class="text-sm text-gray-700 flex items-start">
                                        <span class="text-green-600 mr-2">•</span>
                                        <span>{{ strength }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Weaknesses -->
                            <div class="border border-red-200 rounded-lg p-4 bg-red-50">
                                <h4 class="font-semibold text-red-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Zaif Tomonlar (Weaknesses)
                                </h4>
                                <ul class="space-y-2">
                                    <li v-for="(weakness, index) in swot_analysis.weaknesses" :key="index" class="text-sm text-gray-700 flex items-start">
                                        <span class="text-red-600 mr-2">•</span>
                                        <span>{{ weakness }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Opportunities -->
                            <div class="border border-blue-200 rounded-lg p-4 bg-blue-50">
                                <h4 class="font-semibold text-blue-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                    Imkoniyatlar (Opportunities)
                                </h4>
                                <ul class="space-y-2">
                                    <li v-for="(opportunity, index) in swot_analysis.opportunities" :key="index" class="text-sm text-gray-700 flex items-start">
                                        <span class="text-blue-600 mr-2">•</span>
                                        <span>{{ opportunity }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Threats -->
                            <div class="border border-orange-200 rounded-lg p-4 bg-orange-50">
                                <h4 class="font-semibold text-orange-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    Tahdidlar (Threats)
                                </h4>
                                <ul class="space-y-2">
                                    <li v-for="(threat, index) in swot_analysis.threats" :key="index" class="text-sm text-gray-700 flex items-start">
                                        <span class="text-orange-600 mr-2">•</span>
                                        <span>{{ threat }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Overall Assessment -->
                        <div class="border-t pt-4">
                            <h4 class="font-semibold text-gray-900 mb-2">Umumiy Baholash</h4>
                            <p class="text-sm text-gray-700">{{ swot_analysis.overall_assessment }}</p>
                        </div>

                        <!-- Recommendations -->
                        <div class="border-t pt-4 mt-4" v-if="swot_analysis.recommendations && swot_analysis.recommendations.length > 0">
                            <h4 class="font-semibold text-gray-900 mb-3">Tavsiyalar</h4>
                            <ul class="space-y-2">
                                <li v-for="(recommendation, index) in swot_analysis.recommendations" :key="index" class="text-sm text-gray-700 flex items-start">
                                    <svg class="w-4 h-4 mr-2 mt-0.5 text-indigo-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                    <span>{{ recommendation }}</span>
                                </li>
                            </ul>
                        </div>

                        <p class="text-xs text-gray-500 mt-4">
                            Yaratildi: {{ formatDate(swot_analysis.generated_at) }}
                        </p>
                    </div>
                </div>

                <!-- Metrics History -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" v-if="metrics.length > 0">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Ko'rsatkichlar Tarixi (90 kun)</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sana</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Instagram</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Engagement</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telegram</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">O'sish</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="metric in metrics.slice(0, 10)" :key="metric.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ formatDate(metric.date) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ metric.instagram_followers ? metric.instagram_followers.toLocaleString() : '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ metric.instagram_engagement_rate ? metric.instagram_engagement_rate.toFixed(1) + '%' : '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ metric.telegram_members ? metric.telegram_members.toLocaleString() : '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span v-if="metric.follower_growth_rate" :class="metric.follower_growth_rate > 0 ? 'text-green-600' : 'text-red-600'">
                                                {{ metric.follower_growth_rate > 0 ? '+' : '' }}{{ metric.follower_growth_rate.toFixed(1) }}%
                                            </span>
                                            <span v-else class="text-gray-500">-</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';

const props = defineProps({
    competitor: Object,
    metrics: Array,
    latest_metric: Object,
    swot_analysis: Object,
});

const generating_swot = ref(false);
const monitoring = ref(false);

function getThreatLevelText(level) {
    const levels = {
        low: 'Past',
        medium: 'O\'rta',
        high: 'Yuqori',
        critical: 'Kritik'
    };
    return levels[level] || level;
}

function getStatusText(status) {
    const statuses = {
        active: 'Faol',
        inactive: 'Nofaol',
        archived: 'Arxivlangan'
    };
    return statuses[status] || status;
}

function formatDate(dateString) {
    if (!dateString) return '-';

    const date = new Date(dateString);
    const now = new Date();
    const diffInHours = Math.floor((now - date) / (1000 * 60 * 60));

    if (diffInHours < 1) return 'Hozirgina';
    if (diffInHours < 24) return `${diffInHours} soat oldin`;
    if (diffInHours < 48) return 'Kecha';

    return date.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short', year: 'numeric' });
}

function generateSwot() {
    if (confirm('SWOT tahlilni yaratish uchun AI ishlatiladi. Davom ettirilsinmi?')) {
        generating_swot.value = true;
        router.post(route('business.competitors.swot.generate', props.competitor.id), {}, {
            onFinish: () => {
                generating_swot.value = false;
            },
        });
    }
}

function monitorNow() {
    if (confirm('Bu raqobatchini hoziroq tekshirishni xohlaysizmi?')) {
        monitoring.value = true;
        router.post(route('business.competitors.monitor', props.competitor.id), {}, {
            onSuccess: () => {
                alert('Tekshiruv boshlandi. Natijalar biroz vaqt ichida tayyorlanadi.');
            },
            onFinish: () => {
                monitoring.value = false;
            },
        });
    }
}
</script>
