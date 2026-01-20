<template>
  <MarketingLayout title="Marketing KPI">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
            Marketing KPI Dashboard
          </h2>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Marketing samaradorligi va ROI ko'rsatkichlari
          </p>
        </div>
        <div class="flex gap-3">
          <button
            v-for="p in periods"
            :key="p.key"
            @click="handlePeriodChange(p.key)"
            :class="[
              'px-4 py-2 rounded-lg font-medium transition-all',
              periodType === p.key
                ? 'bg-indigo-600 text-white shadow-md'
                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
            ]"
          >
            {{ p.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div v-for="kpi in kpiCards" :key="kpi.title"
           :class="[
             'rounded-xl shadow-lg p-6 text-white',
             `bg-gradient-to-br from-${kpi.color}-500 to-${kpi.color}-600`
           ]">
        <div class="flex items-center justify-between mb-4">
          <div :class="['w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center']">
            <component :is="getKpiIcon(kpi.title)" class="h-6 w-6" />
          </div>
          <div v-if="kpi.trend" class="flex items-center gap-1 text-sm"
               :class="kpi.trend.positive ? 'text-green-200' : 'text-red-200'">
            <span>{{ kpi.trend.direction === 'up' ? '↑' : '↓' }}</span>
            <span>{{ kpi.trend.value }}%</span>
          </div>
        </div>
        <p :class="[`text-${kpi.color}-100`, 'text-sm font-medium mb-1']">{{ kpi.title }}</p>
        <div class="flex items-baseline gap-1">
          <span class="text-3xl font-bold">{{ kpi.value }}</span>
          <span class="text-lg opacity-75">{{ kpi.suffix }}</span>
        </div>
        <p :class="[`text-${kpi.color}-200`, 'text-xs mt-1']">{{ kpi.description }}</p>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
      <!-- Funnel va Overview -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Lead Funnel -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            Lead Funnel
          </h3>
          <div class="flex items-end justify-between gap-4">
            <div v-for="(stage, index) in funnel" :key="stage.stage"
                 class="flex-1 text-center">
              <div class="relative">
                <div class="mx-auto rounded-lg transition-all"
                     :class="[`bg-${stage.color}-100 dark:bg-${stage.color}-900/30`]"
                     :style="{
                       height: getBarHeight(stage.count) + 'px',
                       minHeight: '60px'
                     }">
                  <div class="absolute inset-0 flex items-center justify-center">
                    <span :class="[`text-${stage.color}-700 dark:text-${stage.color}-400`, 'text-2xl font-bold']">
                      {{ formatNumber(stage.count) }}
                    </span>
                  </div>
                </div>
                <div v-if="index < funnel.length - 1"
                     class="absolute -right-2 top-1/2 transform -translate-y-1/2 text-gray-400">
                  →
                </div>
              </div>
              <p class="mt-3 text-sm font-medium text-gray-600 dark:text-gray-400">{{ stage.stage }}</p>
              <p v-if="index > 0" class="text-xs text-gray-500">
                {{ getConversionRate(index) }}% konversiya
              </p>
            </div>
          </div>
        </div>

        <!-- Budget Overview -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Moliyaviy Ko'rsatkichlar
          </h3>
          <div class="grid grid-cols-2 gap-6">
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Jami sarflangan</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ formatCurrency(overview.total_spend) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Jami daromad</p>
              <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                {{ formatCurrency(overview.total_revenue) }}
              </p>
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-500">Foyda</span>
              <span :class="[
                'text-lg font-bold',
                overview.total_revenue - overview.total_spend >= 0
                  ? 'text-green-600'
                  : 'text-red-600'
              ]">
                {{ formatCurrency(overview.total_revenue - overview.total_spend) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Sidebar -->
      <div class="space-y-6">
        <!-- Top Campaigns -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
              </svg>
              Top Kampaniyalar
            </h3>
            <a href="/marketing/campaigns" class="text-sm text-indigo-600 hover:text-indigo-700">
              Barchasi →
            </a>
          </div>
          <div class="divide-y divide-gray-100 dark:divide-gray-700">
            <div v-for="campaign in topCampaigns" :key="campaign.campaign_id"
                 class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <div class="flex items-center justify-between mb-2">
                <p class="font-medium text-gray-900 dark:text-white text-sm truncate max-w-[180px]">
                  {{ campaign.campaign_name }}
                </p>
                <span :class="[
                  'px-2 py-0.5 rounded text-xs font-medium',
                  campaign.campaign_status === 'active'
                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                    : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                ]">
                  {{ campaign.campaign_status === 'active' ? 'Faol' : 'Tugallangan' }}
                </span>
              </div>
              <div class="flex items-center justify-between text-xs text-gray-500">
                <span>{{ campaign.leads }} lid</span>
                <span>ROAS: {{ campaign.roas.toFixed(2) }}x</span>
              </div>
            </div>
            <div v-if="topCampaigns.length === 0" class="px-6 py-8 text-center text-gray-500">
              Kampaniyalar topilmadi
            </div>
          </div>
        </div>

        <!-- Channel Performance -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
              Kanal Samaradorligi
            </h3>
            <a href="/marketing/kpi/leaderboard" class="text-sm text-indigo-600 hover:text-indigo-700">
              Leaderboard →
            </a>
          </div>
          <div class="p-4 space-y-3">
            <div v-for="(channel, index) in channelPerformance.slice(0, 5)" :key="channel.channel_id"
                 class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm font-bold"
                   :class="getChannelBg(channel.channel_type)">
                {{ getChannelIcon(channel.channel_type) }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                  {{ channel.channel_name }}
                </p>
                <div class="flex items-center gap-2 text-xs text-gray-500">
                  <span>{{ channel.leads }} lid</span>
                  <span>•</span>
                  <span>CPL: {{ formatNumber(channel.cpl) }}</span>
                </div>
              </div>
              <div class="text-right">
                <p class="text-sm font-bold" :class="channel.roi >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ channel.roi >= 0 ? '+' : '' }}{{ channel.roi.toFixed(0) }}%
                </p>
                <p class="text-xs text-gray-500">ROI</p>
              </div>
            </div>
            <div v-if="channelPerformance.length === 0" class="py-4 text-center text-gray-500 text-sm">
              Ma'lumot yo'q
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <a href="/marketing/kpi/leaderboard" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow group">
        <div class="w-12 h-12 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
          <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
          </svg>
        </div>
        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Leaderboard</h4>
        <p class="text-sm text-gray-500">Kanal va kampaniya reytingi</p>
      </a>

      <a href="/marketing/campaigns" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow group">
        <div class="w-12 h-12 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
          <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
          </svg>
        </div>
        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Kampaniyalar</h4>
        <p class="text-sm text-gray-500">Barcha kampaniyalar</p>
      </a>

      <a href="/marketing/channels" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow group">
        <div class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
          <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
          </svg>
        </div>
        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Kanallar</h4>
        <p class="text-sm text-gray-500">Kanal statistikasi</p>
      </a>

      <a href="/marketing/analytics" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow group">
        <div class="w-12 h-12 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
          <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
        </div>
        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Analitika</h4>
        <p class="text-sm text-gray-500">Batafsil hisobot</p>
      </a>
    </div>
  </MarketingLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import { CurrencyDollarIcon, ChartBarIcon, ArrowTrendingUpIcon, UserGroupIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  kpiCards: Array,
  overview: Object,
  funnel: Array,
  topCampaigns: Array,
  channelPerformance: Array,
  snapshots: Array,
  periodType: String,
  panelType: String,
});

const periods = [
  { key: 'daily', label: 'Kunlik' },
  { key: 'weekly', label: 'Haftalik' },
  { key: 'monthly', label: 'Oylik' },
];

const handlePeriodChange = (period) => {
  router.get('/marketing/kpi', { period }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const getKpiIcon = (title) => {
  const icons = {
    'CPL': CurrencyDollarIcon,
    'ROAS': ArrowTrendingUpIcon,
    'ROI': ChartBarIcon,
    'CAC': UserGroupIcon,
  };
  return icons[title] || ChartBarIcon;
};

const formatNumber = (num) => {
  if (!num) return '0';
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
  return num.toString();
};

const formatCurrency = (value) => {
  if (!value) return "0 so'm";
  if (value >= 1000000) {
    return (value / 1000000).toFixed(1) + " mln";
  }
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

const getBarHeight = (count) => {
  const maxCount = Math.max(...props.funnel.map(f => f.count), 1);
  const minHeight = 60;
  const maxHeight = 180;
  return Math.max(minHeight, (count / maxCount) * maxHeight);
};

const getConversionRate = (index) => {
  if (index === 0 || !props.funnel[index - 1]?.count) return 0;
  return ((props.funnel[index].count / props.funnel[index - 1].count) * 100).toFixed(1);
};

const getChannelBg = (type) => {
  const colors = {
    instagram: 'bg-gradient-to-br from-pink-500 to-purple-600',
    telegram: 'bg-gradient-to-br from-blue-400 to-blue-600',
    facebook: 'bg-gradient-to-br from-blue-600 to-blue-800',
    google: 'bg-gradient-to-br from-red-500 to-yellow-500',
    youtube: 'bg-gradient-to-br from-red-600 to-red-700',
    tiktok: 'bg-gradient-to-br from-gray-900 to-gray-700',
    other: 'bg-gradient-to-br from-gray-500 to-gray-600',
  };
  return colors[type] || colors.other;
};

const getChannelIcon = (type) => {
  const icons = {
    instagram: 'IG',
    telegram: 'TG',
    facebook: 'FB',
    google: 'G',
    youtube: 'YT',
    tiktok: 'TT',
  };
  return icons[type] || type?.charAt(0)?.toUpperCase() || '?';
};
</script>
