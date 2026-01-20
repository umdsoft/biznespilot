<template>
  <div class="space-y-6">
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Jami Lidlar -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('kpi.total_leads') }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ formatNumber(dashboard.totals?.leads || 0) }}</p>
            <div class="flex items-center mt-2">
              <span
                class="text-sm font-medium"
                :class="dashboard.trends?.leads?.direction === 'up' ? 'text-green-600' : 'text-red-600'"
              >
                {{ dashboard.trends?.leads?.direction === 'up' ? '+' : '' }}{{ dashboard.trends?.leads?.percent || 0 }}%
              </span>
              <span class="text-sm text-gray-500 dark:text-gray-400 ml-1">{{ t('kpi.vs_last_week') }}</span>
            </div>
          </div>
          <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
        </div>
        <!-- Lead breakdown mini chart -->
        <div class="mt-4 flex gap-1">
          <div
            v-if="dashboard.totals?.leads_digital > 0"
            class="h-2 rounded bg-blue-500"
            :style="{width: getLeadPercent('digital') + '%'}"
            :title="'Digital: ' + dashboard.totals?.leads_digital"
          ></div>
          <div
            v-if="dashboard.totals?.leads_offline > 0"
            class="h-2 rounded bg-orange-500"
            :style="{width: getLeadPercent('offline') + '%'}"
            :title="'Offline: ' + dashboard.totals?.leads_offline"
          ></div>
          <div
            v-if="dashboard.totals?.leads_referral > 0"
            class="h-2 rounded bg-green-500"
            :style="{width: getLeadPercent('referral') + '%'}"
            :title="'Referral: ' + dashboard.totals?.leads_referral"
          ></div>
          <div
            v-if="dashboard.totals?.leads_organic > 0"
            class="h-2 rounded bg-purple-500"
            :style="{width: getLeadPercent('organic') + '%'}"
            :title="'Organic: ' + dashboard.totals?.leads_organic"
          ></div>
        </div>
      </div>

      <!-- Jami Xarajat -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('kpi.total_expense') }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ formatMoney(dashboard.totals?.spend || 0) }}</p>
            <div class="flex items-center mt-2">
              <span class="text-sm text-gray-500 dark:text-gray-400">CPL: {{ formatMoney(dashboard.metrics?.cpl || 0) }}</span>
            </div>
          </div>
          <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Jami Sotuvlar -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('kpi.total_sales') }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ formatNumber(dashboard.totals?.sales || 0) }}</p>
            <div class="flex items-center mt-2">
              <span
                class="text-sm font-medium"
                :class="(dashboard.metrics?.conversion_rate || 0) >= 10 ? 'text-green-600' : 'text-yellow-600'"
              >
                {{ t('kpi.conversion') }}: {{ parseFloat(dashboard.metrics?.conversion_rate || 0).toFixed(1) }}%
              </span>
            </div>
          </div>
          <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
          </div>
        </div>
        <!-- Sales breakdown -->
        <div class="mt-4 flex items-center justify-between text-sm">
          <span class="text-gray-500 dark:text-gray-400">{{ t('kpi.new') }}: {{ dashboard.totals?.sales_new || 0 }}</span>
          <span class="text-gray-500 dark:text-gray-400">{{ t('kpi.repeat') }}: {{ dashboard.totals?.sales_repeat || 0 }}</span>
        </div>
      </div>

      <!-- Jami Daromad -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('kpi.total_revenue') }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ formatMoney(dashboard.totals?.revenue || 0) }}</p>
            <div class="flex items-center mt-2">
              <span class="text-sm text-gray-500 dark:text-gray-400">{{ t('kpi.avg_check') }}: {{ formatMoney(dashboard.metrics?.avg_check || 0) }}</span>
            </div>
          </div>
          <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Source Analysis -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Lead Sources by Category -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ t('kpi.lead_sources') }}</h3>
        <div class="space-y-4">
          <div v-for="(category, key) in categoryData" :key="key" class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div
                class="w-10 h-10 rounded-lg flex items-center justify-center"
                :class="getCategoryBgClass(key)"
              >
                <!-- Digital Icon -->
                <svg v-if="key === 'digital'" class="w-5 h-5" :class="getCategoryTextClass(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <!-- Offline Icon -->
                <svg v-else-if="key === 'offline'" class="w-5 h-5" :class="getCategoryTextClass(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <!-- Referral Icon -->
                <svg v-else-if="key === 'referral'" class="w-5 h-5" :class="getCategoryTextClass(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <!-- Organic Icon -->
                <svg v-else class="w-5 h-5" :class="getCategoryTextClass(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              <div>
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ getCategoryName(key) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ category.leads }} {{ t('kpi.lead') }}</p>
              </div>
            </div>
            <div class="text-right">
              <p class="font-semibold text-gray-900 dark:text-gray-100">{{ category.percent }}%</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatMoney(category.spend) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ROI & Performance -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ t('kpi.efficiency') }}</h3>
        <div class="space-y-4">
          <!-- ROI -->
          <div>
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ROI</span>
              <span
                class="font-bold"
                :class="(dashboard.metrics?.roi || 0) >= 100 ? 'text-green-600' : (dashboard.metrics?.roi || 0) >= 0 ? 'text-yellow-600' : 'text-red-600'"
              >
                {{ parseFloat(dashboard.metrics?.roi || 0).toFixed(0) }}%
              </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div
                class="h-2 rounded-full transition-all"
                :class="(dashboard.metrics?.roi || 0) >= 100 ? 'bg-green-500' : (dashboard.metrics?.roi || 0) >= 0 ? 'bg-yellow-500' : 'bg-red-500'"
                :style="{width: Math.min(Math.max((dashboard.metrics?.roi || 0), 0), 200) / 2 + '%'}"
              ></div>
            </div>
          </div>

          <!-- CAC -->
          <div>
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('kpi.cac') }}</span>
              <span class="font-bold text-gray-900 dark:text-gray-100">{{ formatMoney(dashboard.metrics?.cac || 0) }}</span>
            </div>
          </div>

          <!-- LTV/CAC Ratio -->
          <div v-if="dashboard.metrics?.ltv_cac_ratio">
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('kpi.ltv_cac_ratio') }}</span>
              <span
                class="font-bold"
                :class="(dashboard.metrics?.ltv_cac_ratio || 0) >= 3 ? 'text-green-600' : 'text-yellow-600'"
              >
                {{ parseFloat(dashboard.metrics?.ltv_cac_ratio || 0).toFixed(1) }}x
              </span>
            </div>
          </div>

          <!-- Gross Margin -->
          <div v-if="dashboard.metrics?.gross_margin_percent">
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Gross Margin</span>
              <span class="font-bold text-gray-900 dark:text-gray-100">{{ parseFloat(dashboard.metrics?.gross_margin_percent || 0).toFixed(1) }}%</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Entries Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ t('kpi.recent_entries') }}</h3>
        <button
          @click="$emit('viewAll')"
          class="text-sm text-blue-600 dark:text-blue-400 hover:underline"
        >
          {{ t('kpi.view_all') }}
        </button>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.date') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.leads') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.expense') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.sales') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.revenue') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.conversion') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="entry in recentEntries" :key="entry.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                {{ formatDate(entry.date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-100">
                {{ entry.leads_total }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-100">
                {{ formatMoney(entry.spend_total) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-100">
                {{ entry.sales_total }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-100">
                {{ formatMoney(entry.revenue_total) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                <span
                  class="px-2 py-1 rounded-full text-xs font-medium"
                  :class="getConversionClass(entry.conversion_rate)"
                >
                  {{ parseFloat(entry.conversion_rate || 0).toFixed(1) }}%
                </span>
              </td>
            </tr>
            <tr v-if="recentEntries.length === 0">
              <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                {{ t('kpi.no_data_entered') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recommendations -->
    <div v-if="recommendations.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ t('kpi.recommendations') }}</h3>
      <div class="space-y-3">
        <div
          v-for="(rec, index) in recommendations"
          :key="index"
          class="flex items-start gap-3 p-4 rounded-lg"
          :class="getRecommendationClass(rec.type)"
        >
          <div class="flex-shrink-0">
            <svg v-if="rec.type === 'positive'" class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            <svg v-else-if="rec.type === 'warning'" class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <svg v-else class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ rec.title }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ rec.message }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
  businessId: {
    type: [String, Number],
    required: true
  }
});

const emit = defineEmits(['viewAll']);

// State
const isLoading = ref(true);
const dashboard = ref({
  totals: {},
  metrics: {},
  trends: {}
});
const recentEntries = ref([]);
const recommendations = ref([]);

// Computed
const categoryData = computed(() => {
  const totals = dashboard.value.totals || {};
  const totalLeads = totals.leads || 0;

  return {
    digital: {
      leads: totals.leads_digital || 0,
      spend: totals.spend_digital || 0,
      percent: totalLeads > 0 ? Math.round((totals.leads_digital || 0) / totalLeads * 100) : 0
    },
    offline: {
      leads: totals.leads_offline || 0,
      spend: totals.spend_offline || 0,
      percent: totalLeads > 0 ? Math.round((totals.leads_offline || 0) / totalLeads * 100) : 0
    },
    referral: {
      leads: totals.leads_referral || 0,
      spend: 0,
      percent: totalLeads > 0 ? Math.round((totals.leads_referral || 0) / totalLeads * 100) : 0
    },
    organic: {
      leads: totals.leads_organic || 0,
      spend: 0,
      percent: totalLeads > 0 ? Math.round((totals.leads_organic || 0) / totalLeads * 100) : 0
    }
  };
});

// Methods
const formatNumber = (value) => {
  return new Intl.NumberFormat('uz-UZ').format(value || 0);
};

const formatMoney = (value) => {
  if (!value) return "0 so'm";
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

const formatDate = (dateStr) => {
  const date = new Date(dateStr);
  return date.toLocaleDateString('uz-UZ', { day: '2-digit', month: 'short' });
};

const getLeadPercent = (type) => {
  const total = dashboard.value.totals?.leads || 0;
  if (total === 0) return 0;
  const value = dashboard.value.totals?.[`leads_${type}`] || 0;
  return (value / total) * 100;
};

const getCategoryName = (key) => {
  const names = {
    digital: t('kpi.category_digital'),
    offline: t('kpi.category_offline'),
    referral: t('kpi.category_referral'),
    organic: t('kpi.category_organic')
  };
  return names[key] || key;
};

const getCategoryBgClass = (key) => {
  const classes = {
    digital: 'bg-blue-100 dark:bg-blue-900',
    offline: 'bg-orange-100 dark:bg-orange-900',
    referral: 'bg-green-100 dark:bg-green-900',
    organic: 'bg-purple-100 dark:bg-purple-900'
  };
  return classes[key] || 'bg-gray-100';
};

const getCategoryTextClass = (key) => {
  const classes = {
    digital: 'text-blue-600 dark:text-blue-400',
    offline: 'text-orange-600 dark:text-orange-400',
    referral: 'text-green-600 dark:text-green-400',
    organic: 'text-purple-600 dark:text-purple-400'
  };
  return classes[key] || 'text-gray-600';
};


const getConversionClass = (rate) => {
  const r = parseFloat(rate) || 0;
  if (r >= 15) return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
  if (r >= 10) return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
  if (r >= 5) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
  return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
};

const getRecommendationClass = (type) => {
  const classes = {
    positive: 'bg-green-50 dark:bg-green-900/30',
    warning: 'bg-yellow-50 dark:bg-yellow-900/30',
    info: 'bg-blue-50 dark:bg-blue-900/30'
  };
  return classes[type] || 'bg-gray-50';
};

const loadDashboard = async () => {
  if (!props.businessId) {
    isLoading.value = false;
    return;
  }
  isLoading.value = true;
  try {
    const response = await axios.get(`/api/v1/businesses/${props.businessId}/kpi-entry/dashboard`);
    if (response.data.success && response.data.data) {
      const data = response.data.data;
      dashboard.value = {
        totals: data.totals || {},
        metrics: data.metrics || {},
        trends: data.trends || {},
        today: data.today || ''
      };
      recentEntries.value = data.recent_entries || [];
    }
  } catch (error) {
    console.error('Dashboard load error:', error);
  } finally {
    isLoading.value = false;
  }
};

const loadRecommendations = async () => {
  if (!props.businessId) return;
  try {
    const response = await axios.get(`/api/v1/businesses/${props.businessId}/kpi-entry/source-analysis`);
    if (response.data.success) {
      recommendations.value = response.data.data.recommendations || [];
    }
  } catch (error) {
    console.error('Error loading recommendations:', error);
  }
};

// Lifecycle
onMounted(() => {
  loadDashboard();
  loadRecommendations();
});
</script>
