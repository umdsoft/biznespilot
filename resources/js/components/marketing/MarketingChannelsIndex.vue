<template>
  <div class="max-w-8xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between mb-6">
        <div>
          <Link :href="getHref('')" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-2 transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            {{ t('nav.marketing') }}
          </Link>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Marketing Kanallari</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Ulangan kanallar analitikasi va samaradorlik tahlili
          </p>
        </div>
        <Link
          :href="'/integrations'"
          class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium rounded-xl shadow-lg shadow-primary-500/25 transition-all duration-200 hover:shadow-xl hover:shadow-primary-500/30 hover:-translate-y-0.5"
        >
          <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
          </svg>
          Kanal Ulash
        </Link>
      </div>

      <!-- Stats Overview -->
      <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Ulangan kanallar</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.total_channels }}</p>
            </div>
            <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Jami obunachilar</p>
              <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ formatNumber(stats.total_followers) }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Leadlar (30 kun)</p>
              <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ stats.total_leads_30d }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">O'rtacha engagement</p>
              <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ stats.avg_engagement }}%</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">O'rtacha samaradorlik</p>
              <p class="text-2xl font-bold mt-1" :class="getEffectivenessTextColor(stats.avg_effectiveness)">{{ stats.avg_effectiveness }}%</p>
            </div>
            <div class="w-12 h-12 rounded-xl flex items-center justify-center" :class="getEffectivenessBgColor(stats.avg_effectiveness)">
              <svg class="w-6 h-6" :class="getEffectivenessIconColor(stats.avg_effectiveness)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Content - Channels -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Connected Channels -->
        <div v-if="connectedChannels.length > 0">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
            Ulangan Kanallar
          </h2>
          <div class="space-y-4">
            <div
              v-for="channel in connectedChannels"
              :key="channel.id"
              class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden"
            >
              <!-- Platform Header -->
              <div :class="getPlatformHeaderClass(channel.platform)" class="h-1.5"></div>

              <div class="p-5">
                <div class="flex items-start justify-between mb-4">
                  <div class="flex items-center gap-4">
                    <!-- Platform Icon -->
                    <div :class="getPlatformIconClass(channel.platform)" class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-lg">
                      <component :is="getPlatformIcon(channel.platform)" class="w-7 h-7" />
                    </div>
                    <div>
                      <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ channel.name }}</h3>
                      <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ channel.platform }}</span>
                        <span v-if="channel.last_synced_at" class="text-xs text-gray-400">
                          <svg class="w-3 h-3 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                          </svg>
                          {{ channel.last_synced_at }}
                        </span>
                      </div>
                    </div>
                  </div>
                  <!-- Effectiveness Badge -->
                  <div class="flex flex-col items-end">
                    <div
                      class="px-3 py-1.5 rounded-xl text-sm font-bold"
                      :class="getEffectivenessBadgeClass(channel.effectiveness_color)"
                    >
                      {{ channel.effectiveness_score }}%
                    </div>
                    <span class="text-xs text-gray-500 mt-1">{{ channel.effectiveness_label }}</span>
                  </div>
                </div>

                <!-- Metrics Grid -->
                <div class="grid grid-cols-4 gap-3 mb-4">
                  <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Obunachilar</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatCompact(channel.metrics.followers) }}</p>
                  </div>
                  <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Engagement</p>
                    <p class="text-lg font-bold" :class="getEngagementColor(channel.metrics.engagement_rate)">
                      {{ channel.metrics.engagement_rate }}%
                    </p>
                  </div>
                  <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Leadlar</p>
                    <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ channel.metrics.leads_30d }}</p>
                  </div>
                  <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">O'sish</p>
                    <p class="text-lg font-bold" :class="getGrowthColor(channel.growth)">
                      <span v-if="channel.growth">
                        {{ channel.growth.change >= 0 ? '+' : '' }}{{ channel.growth.change_percent }}%
                      </span>
                      <span v-else class="text-gray-400">-</span>
                    </p>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
                  <a
                    v-if="channel.profile_url"
                    :href="channel.profile_url"
                    target="_blank"
                    class="inline-flex items-center text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700 font-medium"
                  >
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    Profilga o'tish
                  </a>
                  <div class="flex items-center gap-2">
                    <Link
                      :href="getChannelDetailUrl(channel)"
                      class="px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                    >
                      Batafsil
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State for Connected Channels -->
        <div v-else class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-8 text-center">
          <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-primary-100 to-purple-100 dark:from-primary-900/30 dark:to-purple-900/30 rounded-3xl mb-6">
            <svg class="w-10 h-10 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Kanallar ulanmagan</h3>
          <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
            Instagram, Telegram yoki boshqa kanallarni ulang va avtomatik analitika oling
          </p>

          <!-- Platform Connect Buttons -->
          <div class="flex flex-wrap justify-center gap-3">
            <Link
              v-for="platform in availablePlatforms"
              :key="platform.id"
              :href="platform.connect_url"
              class="inline-flex items-center px-4 py-2.5 rounded-xl font-medium transition-all duration-200 hover:-translate-y-0.5"
              :class="platform.is_connected ? 'bg-gray-100 dark:bg-gray-700 text-gray-500 cursor-default' : getPlatformButtonClass(platform.id)"
            >
              <component :is="getPlatformIcon(platform.name)" class="w-5 h-5 mr-2" />
              {{ platform.is_connected ? 'Ulangan' : platform.name + ' ulash' }}
            </Link>
          </div>
        </div>

        <!-- Manual Channels (Legacy) -->
        <div v-if="manualChannels.length > 0" class="mt-8">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            Qo'lda qo'shilgan kanallar
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="channel in manualChannels"
              :key="channel.id"
              class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-4"
            >
              <div class="flex items-center gap-3">
                <div :class="getPlatformIconClass(channel.platform)" class="w-10 h-10 rounded-xl flex items-center justify-center">
                  <component :is="getPlatformIcon(channel.platform)" class="w-5 h-5" />
                </div>
                <div class="flex-1 min-w-0">
                  <h4 class="font-medium text-gray-900 dark:text-white truncate">{{ channel.name }}</h4>
                  <p class="text-sm text-gray-500">{{ formatCompact(channel.metrics.followers) }} obunachilar</p>
                </div>
                <span
                  class="px-2 py-1 text-xs rounded-lg"
                  :class="channel.status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'"
                >
                  {{ channel.status === 'active' ? 'Faol' : 'Nofaol' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar - AI Recommendations & Platforms -->
      <div class="space-y-6">
        <!-- AI Recommendations -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20">
            <h3 class="font-semibold text-gray-900 dark:text-white flex items-center">
              <svg class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
              </svg>
              AI Tavsiyalari
            </h3>
          </div>
          <div class="p-4 space-y-3">
            <div
              v-for="(rec, index) in recommendations"
              :key="index"
              class="p-3 rounded-xl border transition-colors"
              :class="getRecommendationClass(rec.type)"
            >
              <div class="flex items-start gap-3">
                <div class="mt-0.5">
                  <component :is="getRecommendationIcon(rec.type)" class="w-5 h-5" :class="getRecommendationIconColor(rec.type)" />
                </div>
                <div>
                  <h4 class="font-medium text-gray-900 dark:text-white text-sm">{{ rec.title }}</h4>
                  <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ rec.description }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Available Platforms -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white flex items-center">
              <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Mavjud Platformalar
            </h3>
          </div>
          <div class="p-4 space-y-2">
            <Link
              v-for="platform in availablePlatforms"
              :key="platform.id"
              :href="platform.connect_url"
              class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
            >
              <div :class="getPlatformSmallIconClass(platform.id)" class="w-10 h-10 rounded-xl flex items-center justify-center">
                <component :is="getPlatformIcon(platform.name)" class="w-5 h-5 text-white" />
              </div>
              <div class="flex-1">
                <h4 class="font-medium text-gray-900 dark:text-white text-sm">{{ platform.name }}</h4>
                <p class="text-xs text-gray-500">{{ platform.description }}</p>
              </div>
              <div v-if="platform.is_connected" class="flex items-center text-green-600 dark:text-green-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <div v-else class="text-gray-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </Link>
          </div>
        </div>

        <!-- Best Channel Highlight -->
        <div v-if="stats.best_channel" class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl border border-green-200 dark:border-green-800 p-5">
          <div class="flex items-center gap-2 mb-3">
            <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
            </svg>
            <span class="font-semibold text-green-800 dark:text-green-300">Eng samarali kanal</span>
          </div>
          <div class="flex items-center gap-3">
            <div :class="getPlatformIconClass(stats.best_channel.platform)" class="w-12 h-12 rounded-xl flex items-center justify-center">
              <component :is="getPlatformIcon(stats.best_channel.platform)" class="w-6 h-6" />
            </div>
            <div>
              <h4 class="font-bold text-gray-900 dark:text-white">{{ stats.best_channel.name }}</h4>
              <p class="text-sm text-green-700 dark:text-green-400">{{ stats.best_channel.effectiveness_score }}% samaradorlik</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, h } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
  panelType: {
    type: String,
    default: 'business',
  },
  connectedChannels: {
    type: Array,
    default: () => [],
  },
  manualChannels: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({
      total_channels: 0,
      total_followers: 0,
      total_leads_30d: 0,
      avg_engagement: 0,
      avg_effectiveness: 0,
      best_channel: null,
    }),
  },
  recommendations: {
    type: Array,
    default: () => [],
  },
  availablePlatforms: {
    type: Array,
    default: () => [],
  },
});

const getHref = (path) => {
  const prefix = props.panelType === 'business' ? '/business/marketing' : '/marketing';
  return prefix + path;
};

const getChannelDetailUrl = (channel) => {
  const prefix = props.panelType === 'business' ? '/business' : '';
  const platformUrls = {
    'Instagram': '/integrations/instagram',
    'Facebook': prefix + '/facebook-analysis',
    'Telegram': prefix + '/telegram-funnels',
  };
  return platformUrls[channel.platform] || prefix + '/marketing/channels';
};

// Format helpers
const formatNumber = (num) => new Intl.NumberFormat('uz-UZ').format(num || 0);
const formatCompact = (num) => {
  if (!num) return '0';
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
  return num.toString();
};

// Platform styling
const getPlatformHeaderClass = (platform) => {
  const classes = {
    'Instagram': 'bg-gradient-to-r from-purple-600 via-pink-500 to-orange-400',
    'Facebook': 'bg-blue-600',
    'Telegram': 'bg-sky-500',
    'WhatsApp': 'bg-green-500',
    'YouTube': 'bg-red-600',
    'TikTok': 'bg-black',
  };
  return classes[platform] || 'bg-gray-400';
};

const getPlatformIconClass = (platform) => {
  const classes = {
    'Instagram': 'bg-gradient-to-br from-purple-600 via-pink-500 to-orange-400 text-white',
    'Facebook': 'bg-blue-600 text-white',
    'Telegram': 'bg-sky-500 text-white',
    'WhatsApp': 'bg-green-500 text-white',
    'YouTube': 'bg-red-600 text-white',
    'TikTok': 'bg-black text-white',
  };
  return classes[platform] || 'bg-gray-500 text-white';
};

const getPlatformSmallIconClass = (id) => {
  const classes = {
    'instagram': 'bg-gradient-to-br from-purple-600 via-pink-500 to-orange-400',
    'facebook': 'bg-blue-600',
    'telegram': 'bg-sky-500',
    'whatsapp': 'bg-green-500',
  };
  return classes[id] || 'bg-gray-500';
};

const getPlatformButtonClass = (id) => {
  const classes = {
    'instagram': 'bg-gradient-to-r from-purple-600 via-pink-500 to-orange-400 text-white hover:shadow-lg hover:shadow-pink-500/25',
    'facebook': 'bg-blue-600 text-white hover:bg-blue-700',
    'telegram': 'bg-sky-500 text-white hover:bg-sky-600',
    'whatsapp': 'bg-green-500 text-white hover:bg-green-600',
  };
  return classes[id] || 'bg-gray-500 text-white';
};

// Platform icons as render functions
const InstagramIcon = {
  render: () => h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
    h('path', { d: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z' })
  ])
};

const FacebookIcon = {
  render: () => h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
    h('path', { d: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z' })
  ])
};

const TelegramIcon = {
  render: () => h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
    h('path', { d: 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z' })
  ])
};

const WhatsAppIcon = {
  render: () => h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
    h('path', { d: 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z' })
  ])
};

const YouTubeIcon = {
  render: () => h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
    h('path', { d: 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z' })
  ])
};

const DefaultIcon = {
  render: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z' })
  ])
};

const getPlatformIcon = (platform) => {
  const icons = {
    'Instagram': InstagramIcon,
    'Facebook': FacebookIcon,
    'Telegram': TelegramIcon,
    'WhatsApp': WhatsAppIcon,
    'YouTube': YouTubeIcon,
  };
  return icons[platform] || DefaultIcon;
};

// Effectiveness styling
const getEffectivenessTextColor = (score) => {
  if (score >= 80) return 'text-green-600 dark:text-green-400';
  if (score >= 60) return 'text-blue-600 dark:text-blue-400';
  if (score >= 40) return 'text-yellow-600 dark:text-yellow-400';
  return 'text-red-600 dark:text-red-400';
};

const getEffectivenessBgColor = (score) => {
  if (score >= 80) return 'bg-green-100 dark:bg-green-900/30';
  if (score >= 60) return 'bg-blue-100 dark:bg-blue-900/30';
  if (score >= 40) return 'bg-yellow-100 dark:bg-yellow-900/30';
  return 'bg-red-100 dark:bg-red-900/30';
};

const getEffectivenessIconColor = (score) => {
  if (score >= 80) return 'text-green-600 dark:text-green-400';
  if (score >= 60) return 'text-blue-600 dark:text-blue-400';
  if (score >= 40) return 'text-yellow-600 dark:text-yellow-400';
  return 'text-red-600 dark:text-red-400';
};

const getEffectivenessBadgeClass = (color) => {
  const classes = {
    'green': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    'blue': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    'yellow': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    'orange': 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    'red': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
  };
  return classes[color] || classes.gray;
};

// Engagement color
const getEngagementColor = (rate) => {
  if (!rate) return 'text-gray-900 dark:text-white';
  if (rate >= 5) return 'text-green-600 dark:text-green-400';
  if (rate >= 2) return 'text-blue-600 dark:text-blue-400';
  return 'text-yellow-600 dark:text-yellow-400';
};

// Growth color
const getGrowthColor = (growth) => {
  if (!growth) return 'text-gray-400';
  if (growth.change_percent > 0) return 'text-green-600 dark:text-green-400';
  if (growth.change_percent < 0) return 'text-red-600 dark:text-red-400';
  return 'text-gray-600 dark:text-gray-400';
};

// Recommendation styling
const getRecommendationClass = (type) => {
  const classes = {
    'success': 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
    'warning': 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800',
    'action': 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
    'info': 'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600',
    'tip': 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800',
  };
  return classes[type] || classes.info;
};

const getRecommendationIconColor = (type) => {
  const colors = {
    'success': 'text-green-600 dark:text-green-400',
    'warning': 'text-yellow-600 dark:text-yellow-400',
    'action': 'text-blue-600 dark:text-blue-400',
    'info': 'text-gray-600 dark:text-gray-400',
    'tip': 'text-purple-600 dark:text-purple-400',
  };
  return colors[type] || colors.info;
};

const TrophyIcon = {
  render: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z' })
  ])
};

const WarningIcon = {
  render: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' })
  ])
};

const ActionIcon = {
  render: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M13 10V3L4 14h7v7l9-11h-7z' })
  ])
};

const InfoIcon = {
  render: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' })
  ])
};

const LightbulbIcon = {
  render: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z' })
  ])
};

const getRecommendationIcon = (type) => {
  const icons = {
    'success': TrophyIcon,
    'warning': WarningIcon,
    'action': ActionIcon,
    'info': InfoIcon,
    'tip': LightbulbIcon,
  };
  return icons[type] || InfoIcon;
};
</script>
