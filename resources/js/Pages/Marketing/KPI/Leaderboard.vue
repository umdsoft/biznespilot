<template>
  <MarketingLayout title="Marketing Leaderboard">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
            Marketing Leaderboard
          </h2>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Kanallar va kampaniyalar samaradorlik reytingi
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

    <!-- Records -->
    <div v-if="hasRecords" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
      <div v-if="records.best_cpl"
           class="bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-blue-100 text-sm font-medium">Eng yaxshi CPL</p>
            <p class="text-2xl font-bold mt-1">{{ records.best_cpl.value }}</p>
            <p class="text-blue-100 text-xs mt-1">{{ records.best_cpl.holder_name }}</p>
            <p class="text-blue-200 text-xs">{{ records.best_cpl.date }}</p>
          </div>
          <div class="text-4xl">💰</div>
        </div>
      </div>

      <div v-if="records.best_roas"
           class="bg-gradient-to-br from-green-400 to-green-600 rounded-xl p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-green-100 text-sm font-medium">Eng yuqori ROAS</p>
            <p class="text-2xl font-bold mt-1">{{ records.best_roas.value }}</p>
            <p class="text-green-100 text-xs mt-1">{{ records.best_roas.holder_name }}</p>
            <p class="text-green-200 text-xs">{{ records.best_roas.date }}</p>
          </div>
          <div class="text-4xl">📈</div>
        </div>
      </div>

      <div v-if="records.most_leads"
           class="bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-purple-100 text-sm font-medium">Eng ko'p lid</p>
            <p class="text-2xl font-bold mt-1">{{ records.most_leads.value }}</p>
            <p class="text-purple-100 text-xs mt-1">{{ records.most_leads.holder_name }}</p>
            <p class="text-purple-200 text-xs">{{ records.most_leads.date }}</p>
          </div>
          <div class="text-4xl">👥</div>
        </div>
      </div>
    </div>

    <!-- Sort Buttons -->
    <div class="flex gap-2 mb-6 flex-wrap">
      <span class="text-sm text-gray-500 dark:text-gray-400 py-2">Tartiblash:</span>
      <button
        v-for="sort in sortOptions"
        :key="sort.key"
        @click="handleSortChange(sort.key)"
        :class="[
          'px-3 py-1.5 rounded-lg text-sm font-medium transition-all',
          sortBy === sort.key
            ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400'
            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
        ]"
      >
        {{ sort.label }}
      </button>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- Channel Leaderboard -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            Kanallar Reytingi
          </h3>
        </div>

        <!-- Top 3 Podium -->
        <div v-if="channelLeaderboard.length >= 3" class="p-6 bg-gradient-to-b from-gray-50 to-white dark:from-gray-800 dark:to-gray-900">
          <div class="flex items-end justify-center gap-3">
            <!-- 2nd Place -->
            <div class="text-center flex-1">
              <div class="w-14 h-14 mx-auto mb-2 rounded-lg flex items-center justify-center text-white font-bold shadow-lg"
                   :class="getChannelBg(channelLeaderboard[1]?.type)">
                {{ getChannelIcon(channelLeaderboard[1]?.type) }}
              </div>
              <div class="text-2xl mb-1">🥈</div>
              <p class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ channelLeaderboard[1]?.name }}</p>
              <p class="text-lg font-bold text-gray-600 dark:text-gray-300">{{ getSortValue(channelLeaderboard[1]) }}</p>
              <div class="h-16 w-full mx-auto bg-gradient-to-t from-gray-300 to-gray-200 dark:from-gray-600 dark:to-gray-500 rounded-t-lg mt-3 flex items-center justify-center">
                <span class="text-2xl font-bold text-white/80">2</span>
              </div>
            </div>

            <!-- 1st Place -->
            <div class="text-center flex-1">
              <div class="w-16 h-16 mx-auto mb-2 rounded-lg flex items-center justify-center text-white font-bold shadow-lg ring-4 ring-yellow-300"
                   :class="getChannelBg(channelLeaderboard[0]?.type)">
                {{ getChannelIcon(channelLeaderboard[0]?.type) }}
              </div>
              <div class="text-3xl mb-1">🥇</div>
              <p class="font-bold text-gray-900 dark:text-white text-sm truncate">{{ channelLeaderboard[0]?.name }}</p>
              <p class="text-xl font-bold text-yellow-600">{{ getSortValue(channelLeaderboard[0]) }}</p>
              <div class="h-24 w-full mx-auto bg-gradient-to-t from-yellow-500 to-yellow-400 rounded-t-lg mt-3 flex items-center justify-center">
                <span class="text-3xl font-bold text-white/80">1</span>
              </div>
            </div>

            <!-- 3rd Place -->
            <div class="text-center flex-1">
              <div class="w-14 h-14 mx-auto mb-2 rounded-lg flex items-center justify-center text-white font-bold shadow-lg"
                   :class="getChannelBg(channelLeaderboard[2]?.type)">
                {{ getChannelIcon(channelLeaderboard[2]?.type) }}
              </div>
              <div class="text-2xl mb-1">🥉</div>
              <p class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ channelLeaderboard[2]?.name }}</p>
              <p class="text-lg font-bold text-orange-600">{{ getSortValue(channelLeaderboard[2]) }}</p>
              <div class="h-12 w-full mx-auto bg-gradient-to-t from-orange-500 to-orange-400 rounded-t-lg mt-3 flex items-center justify-center">
                <span class="text-2xl font-bold text-white/80">3</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Full List -->
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">#</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kanal</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Lidlar</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">CPL</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ROI</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="channel in channelLeaderboard" :key="channel.id"
                  :class="[
                    'hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors',
                    channel.rank <= 3 ? 'bg-yellow-50/50 dark:bg-yellow-900/10' : ''
                  ]">
                <td class="px-4 py-3 whitespace-nowrap">
                  <span v-if="channel.rank === 1" class="text-xl">🥇</span>
                  <span v-else-if="channel.rank === 2" class="text-xl">🥈</span>
                  <span v-else-if="channel.rank === 3" class="text-xl">🥉</span>
                  <span v-else class="text-gray-500 font-medium ml-1">{{ channel.rank }}</span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold"
                         :class="getChannelBg(channel.type)">
                      {{ getChannelIcon(channel.type) }}
                    </div>
                    <span class="font-medium text-gray-900 dark:text-white text-sm">{{ channel.name }}</span>
                  </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-600 dark:text-gray-300">
                  {{ channel.leads }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-center text-sm">
                  <span :class="channel.cpl > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400'">
                    {{ formatNumber(channel.cpl) }}
                  </span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-center">
                  <span :class="[
                    'text-sm font-medium',
                    channel.roi >= 0 ? 'text-green-600' : 'text-red-600'
                  ]">
                    {{ channel.roi >= 0 ? '+' : '' }}{{ channel.roi.toFixed(0) }}%
                  </span>
                </td>
              </tr>
              <tr v-if="channelLeaderboard.length === 0">
                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                  Kanal ma'lumotlari topilmadi
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Campaign Leaderboard -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
            </svg>
            Kampaniyalar Reytingi
          </h3>
        </div>

        <!-- Top 3 Podium -->
        <div v-if="campaignLeaderboard.length >= 3" class="p-6 bg-gradient-to-b from-gray-50 to-white dark:from-gray-800 dark:to-gray-900">
          <div class="flex items-end justify-center gap-3">
            <!-- 2nd Place -->
            <div class="text-center flex-1">
              <div class="w-14 h-14 mx-auto mb-2 rounded-lg bg-gradient-to-br from-gray-300 to-gray-400 flex items-center justify-center text-white text-lg font-bold shadow-lg">
                2
              </div>
              <div class="text-2xl mb-1">🥈</div>
              <p class="font-medium text-gray-900 dark:text-white text-xs truncate px-1">{{ campaignLeaderboard[1]?.name }}</p>
              <p class="text-lg font-bold text-gray-600 dark:text-gray-300">{{ getSortValue(campaignLeaderboard[1]) }}</p>
              <div class="h-16 w-full mx-auto bg-gradient-to-t from-gray-300 to-gray-200 dark:from-gray-600 dark:to-gray-500 rounded-t-lg mt-3"></div>
            </div>

            <!-- 1st Place -->
            <div class="text-center flex-1">
              <div class="w-16 h-16 mx-auto mb-2 rounded-lg bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center text-white text-xl font-bold shadow-lg ring-4 ring-yellow-300">
                1
              </div>
              <div class="text-3xl mb-1">🥇</div>
              <p class="font-bold text-gray-900 dark:text-white text-xs truncate px-1">{{ campaignLeaderboard[0]?.name }}</p>
              <p class="text-xl font-bold text-yellow-600">{{ getSortValue(campaignLeaderboard[0]) }}</p>
              <div class="h-24 w-full mx-auto bg-gradient-to-t from-yellow-500 to-yellow-400 rounded-t-lg mt-3"></div>
            </div>

            <!-- 3rd Place -->
            <div class="text-center flex-1">
              <div class="w-14 h-14 mx-auto mb-2 rounded-lg bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-lg font-bold shadow-lg">
                3
              </div>
              <div class="text-2xl mb-1">🥉</div>
              <p class="font-medium text-gray-900 dark:text-white text-xs truncate px-1">{{ campaignLeaderboard[2]?.name }}</p>
              <p class="text-lg font-bold text-orange-600">{{ getSortValue(campaignLeaderboard[2]) }}</p>
              <div class="h-12 w-full mx-auto bg-gradient-to-t from-orange-500 to-orange-400 rounded-t-lg mt-3"></div>
            </div>
          </div>
        </div>

        <!-- Full List -->
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">#</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kampaniya</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Lidlar</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ROAS</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ROI</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="campaign in campaignLeaderboard" :key="campaign.id"
                  :class="[
                    'hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors',
                    campaign.rank <= 3 ? 'bg-yellow-50/50 dark:bg-yellow-900/10' : ''
                  ]">
                <td class="px-4 py-3 whitespace-nowrap">
                  <span v-if="campaign.rank === 1" class="text-xl">🥇</span>
                  <span v-else-if="campaign.rank === 2" class="text-xl">🥈</span>
                  <span v-else-if="campaign.rank === 3" class="text-xl">🥉</span>
                  <span v-else class="text-gray-500 font-medium ml-1">{{ campaign.rank }}</span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <p class="font-medium text-gray-900 dark:text-white text-sm truncate max-w-[150px]">
                      {{ campaign.name }}
                    </p>
                    <span :class="[
                      'px-1.5 py-0.5 rounded text-xs',
                      campaign.status === 'active'
                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                        : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                    ]">
                      {{ campaign.status === 'active' ? 'Faol' : 'Tug.' }}
                    </span>
                  </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-600 dark:text-gray-300">
                  {{ campaign.leads }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-center text-sm">
                  <span :class="campaign.roas >= 1 ? 'text-green-600' : 'text-orange-600'">
                    {{ campaign.roas.toFixed(2) }}x
                  </span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-center">
                  <span :class="[
                    'text-sm font-medium',
                    campaign.roi >= 0 ? 'text-green-600' : 'text-red-600'
                  ]">
                    {{ campaign.roi >= 0 ? '+' : '' }}{{ campaign.roi.toFixed(0) }}%
                  </span>
                </td>
              </tr>
              <tr v-if="campaignLeaderboard.length === 0">
                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                  Kampaniya ma'lumotlari topilmadi
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Monthly History -->
    <div v-if="monthlyHistory.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
          <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          Oylik Tarix
        </h3>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Oy</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Lidlar</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Won</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sarflangan</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Daromad</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">CPL</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ROAS</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ROI</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="month in monthlyHistory" :key="month.month"
                class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                {{ month.month }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center text-gray-600 dark:text-gray-300">
                {{ month.leads }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center text-green-600">
                {{ month.won }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-gray-600 dark:text-gray-300">
                {{ formatCurrency(month.spend) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-green-600">
                {{ formatCurrency(month.revenue) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center text-blue-600">
                {{ formatNumber(month.cpl) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span :class="month.roas >= 1 ? 'text-green-600' : 'text-orange-600'">
                  {{ month.roas.toFixed(2) }}x
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span :class="month.roi >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ month.roi >= 0 ? '+' : '' }}{{ month.roi.toFixed(0) }}%
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </MarketingLayout>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import MarketingLayout from '@/layouts/MarketingLayout.vue';

const props = defineProps({
  channelLeaderboard: Array,
  campaignLeaderboard: Array,
  records: Object,
  monthlyHistory: Array,
  periodType: String,
  sortBy: String,
  panelType: String,
});

const periods = [
  { key: 'daily', label: 'Kunlik' },
  { key: 'weekly', label: 'Haftalik' },
  { key: 'monthly', label: 'Oylik' },
];

const sortOptions = [
  { key: 'roas', label: 'ROAS' },
  { key: 'roi', label: 'ROI' },
  { key: 'leads', label: 'Lidlar' },
  { key: 'cpl', label: 'CPL (past)' },
  { key: 'efficiency_score', label: 'Samaradorlik' },
];

const hasRecords = computed(() => {
  return props.records && (
    props.records.best_cpl ||
    props.records.best_roas ||
    props.records.most_leads
  );
});

const handlePeriodChange = (period) => {
  router.get('/marketing/kpi/leaderboard', {
    period,
    sort: props.sortBy
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const handleSortChange = (sort) => {
  router.get('/marketing/kpi/leaderboard', {
    period: props.periodType,
    sort
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const getSortValue = (item) => {
  if (!item) return '-';
  const sortBy = props.sortBy || 'roas';

  if (sortBy === 'roas') return item.roas?.toFixed(2) + 'x';
  if (sortBy === 'roi') return (item.roi >= 0 ? '+' : '') + item.roi?.toFixed(0) + '%';
  if (sortBy === 'leads') return item.leads;
  if (sortBy === 'cpl') return formatNumber(item.cpl);
  if (sortBy === 'efficiency_score') return item.efficiency_score;

  return item[sortBy] || '-';
};

const formatNumber = (num) => {
  if (!num) return '0';
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
  if (num >= 1000) return (num / 1000).toFixed(0) + 'K';
  return Math.round(num).toString();
};

const formatCurrency = (value) => {
  if (!value) return "0";
  if (value >= 1000000) {
    return (value / 1000000).toFixed(1) + " mln";
  }
  if (value >= 1000) {
    return (value / 1000).toFixed(0) + "K";
  }
  return new Intl.NumberFormat('uz-UZ').format(value);
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
