<template>
  <div class="space-y-6">
    <!-- Period Selector -->
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ t('kpi.source_analysis') }}</h3>
      <div class="flex items-center gap-2">
        <select
          v-model="selectedPeriod"
          class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
        >
          <option value="week">{{ t('kpi.last_week') }}</option>
          <option value="month">{{ t('kpi.current_month') }}</option>
          <option value="quarter">{{ t('kpi.current_quarter') }}</option>
        </select>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div
        v-for="(category, key) in categoryAnalysis"
        :key="key"
        class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-5"
      >
        <div class="flex items-center gap-3 mb-3">
          <div
            class="w-10 h-10 rounded-lg flex items-center justify-center"
            :class="getCategoryBgClass(key)"
          >
            <span :class="getCategoryTextClass(key)" class="text-lg font-bold">
              {{ getCategoryIcon(key) }}
            </span>
          </div>
          <span class="font-medium text-gray-700 dark:text-gray-300">{{ getCategoryName(key) }}</span>
        </div>
        <div class="space-y-2">
          <div class="flex justify-between">
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ t('kpi.leads') }}</span>
            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ category.leads }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ t('kpi.expense') }}</span>
            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ formatMoney(category.spend) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ t('kpi.conversion') }}</span>
            <span
              class="font-semibold"
              :class="category.conversion_rate >= 10 ? 'text-green-600' : 'text-yellow-600'"
            >
              {{ category.conversion_rate }}%
            </span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-500 dark:text-gray-400">CPL</span>
            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ formatMoney(category.cpl) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-500 dark:text-gray-400">ROI</span>
            <span
              class="font-semibold"
              :class="category.roi >= 100 ? 'text-green-600' : category.roi >= 0 ? 'text-yellow-600' : 'text-red-600'"
            >
              {{ category.roi }}%
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Detailed Sources Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ t('kpi.detailed_source_analysis') }}</h4>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.source') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.category') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.leads') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.share') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.expense') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.conversion') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">CPL</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ROI</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="source in sourceAnalysis.sources"
              :key="source.source_id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <span class="text-lg">{{ source.icon || '📊' }}</span>
                  <span class="font-medium text-gray-900 dark:text-gray-100">{{ source.source_name }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="px-2 py-1 rounded-full text-xs font-medium"
                  :class="getCategoryBadgeClass(source.category)"
                >
                  {{ getCategoryName(source.category) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-gray-900 dark:text-gray-100">
                {{ source.leads }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right">
                <div class="flex items-center justify-end gap-2">
                  <div class="w-16 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                    <div
                      class="h-2 rounded-full bg-blue-500"
                      :style="{width: source.leads_percent + '%'}"
                    ></div>
                  </div>
                  <span class="text-sm text-gray-600 dark:text-gray-400">{{ source.leads_percent }}%</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-gray-900 dark:text-gray-100">
                {{ formatMoney(source.spend) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right">
                <span
                  class="font-medium"
                  :class="source.conversion_rate >= 15 ? 'text-green-600' : source.conversion_rate >= 10 ? 'text-blue-600' : 'text-yellow-600'"
                >
                  {{ source.conversion_rate }}%
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-gray-900 dark:text-gray-100">
                {{ formatMoney(source.cpl) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right">
                <span
                  class="font-medium"
                  :class="source.roi >= 100 ? 'text-green-600' : source.roi >= 0 ? 'text-yellow-600' : 'text-red-600'"
                >
                  {{ source.roi }}%
                </span>
              </td>
            </tr>
            <tr v-if="!sourceAnalysis.sources?.length">
              <td colspan="8" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                {{ t('kpi.no_source_data') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Insights -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Best Conversion -->
      <div v-if="sourceAnalysis.insights?.best_conversion" class="bg-green-50 dark:bg-green-900/30 rounded-xl p-5">
        <div class="flex items-center gap-2 mb-2">
          <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span class="font-semibold text-green-800 dark:text-green-300">{{ t('kpi.best_conversion') }}</span>
        </div>
        <p class="text-lg font-bold text-green-900 dark:text-green-200">
          {{ sourceAnalysis.insights.best_conversion.source_name }}
        </p>
        <p class="text-sm text-green-700 dark:text-green-400">
          {{ sourceAnalysis.insights.best_conversion.conversion_rate }}% {{ t('kpi.conversion') }}
        </p>
      </div>

      <!-- Best ROI -->
      <div v-if="sourceAnalysis.insights?.best_roi" class="bg-blue-50 dark:bg-blue-900/30 rounded-xl p-5">
        <div class="flex items-center gap-2 mb-2">
          <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
          </svg>
          <span class="font-semibold text-blue-800 dark:text-blue-300">{{ t('kpi.best_roi') }}</span>
        </div>
        <p class="text-lg font-bold text-blue-900 dark:text-blue-200">
          {{ sourceAnalysis.insights.best_roi.source_name }}
        </p>
        <p class="text-sm text-blue-700 dark:text-blue-400">
          {{ sourceAnalysis.insights.best_roi.roi }}% ROI
        </p>
      </div>

      <!-- Lowest CPL -->
      <div v-if="sourceAnalysis.insights?.lowest_cpl" class="bg-purple-50 dark:bg-purple-900/30 rounded-xl p-5">
        <div class="flex items-center gap-2 mb-2">
          <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span class="font-semibold text-purple-800 dark:text-purple-300">{{ t('kpi.lowest_cpl') }}</span>
        </div>
        <p class="text-lg font-bold text-purple-900 dark:text-purple-200">
          {{ sourceAnalysis.insights.lowest_cpl.source_name }}
        </p>
        <p class="text-sm text-purple-700 dark:text-purple-400">
          {{ formatMoney(sourceAnalysis.insights.lowest_cpl.cpl) }} / {{ t('kpi.lead') }}
        </p>
      </div>
    </div>

    <!-- Recommendations -->
    <div v-if="recommendations.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
      <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ t('kpi.recommendations') }}</h4>
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
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
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
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
  businessId: {
    type: [String, Number],
    required: true
  }
});

// State
const isLoading = ref(true);
const selectedPeriod = ref('month');
const sourceAnalysis = ref({
  sources: [],
  totals: {},
  insights: {}
});
const categoryAnalysis = ref({
  digital: { leads: 0, spend: 0, conversion_rate: 0, cpl: 0, roi: 0 },
  offline: { leads: 0, spend: 0, conversion_rate: 0, cpl: 0, roi: 0 },
  referral: { leads: 0, spend: 0, conversion_rate: 0, cpl: 0, roi: 0 },
  organic: { leads: 0, spend: 0, conversion_rate: 0, cpl: 0, roi: 0 }
});
const recommendations = ref([]);

// Methods
const formatMoney = (value) => {
  if (!value) return "0 so'm";
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
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

const getCategoryIcon = (key) => {
  const icons = {
    digital: '📱',
    offline: '📋',
    referral: '👥',
    organic: '🌱'
  };
  return icons[key] || '📊';
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

const getCategoryBadgeClass = (key) => {
  const classes = {
    digital: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    offline: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
    referral: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    organic: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300'
  };
  return classes[key] || 'bg-gray-100 text-gray-800';
};

const getRecommendationClass = (type) => {
  const classes = {
    positive: 'bg-green-50 dark:bg-green-900/30',
    warning: 'bg-yellow-50 dark:bg-yellow-900/30',
    info: 'bg-blue-50 dark:bg-blue-900/30'
  };
  return classes[type] || 'bg-gray-50';
};

const getDateRange = () => {
  const now = new Date();
  let startDate, endDate;

  switch (selectedPeriod.value) {
    case 'week':
      startDate = new Date(now);
      startDate.setDate(now.getDate() - 7);
      endDate = now;
      break;
    case 'quarter':
      startDate = new Date(now.getFullYear(), Math.floor(now.getMonth() / 3) * 3, 1);
      endDate = now;
      break;
    case 'month':
    default:
      startDate = new Date(now.getFullYear(), now.getMonth(), 1);
      endDate = now;
  }

  return {
    start_date: startDate.toISOString().split('T')[0],
    end_date: endDate.toISOString().split('T')[0]
  };
};

const loadData = async () => {
  if (!props.businessId) {
    console.error('businessId is undefined');
    isLoading.value = false;
    return;
  }
  isLoading.value = true;
  const dateRange = getDateRange();

  try {
    // Load source analysis
    const analysisResponse = await axios.get(`/api/v1/businesses/${props.businessId}/kpi-entry/source-analysis`, {
      params: dateRange
    });

    if (analysisResponse.data.success) {
      sourceAnalysis.value = analysisResponse.data.data.analysis || { sources: [], insights: {} };
      recommendations.value = analysisResponse.data.data.recommendations || [];
    }

    // Load category analysis
    const categoryResponse = await axios.get(`/api/v1/businesses/${props.businessId}/kpi-entry/category-analysis`, {
      params: dateRange
    });

    if (categoryResponse.data.success) {
      categoryAnalysis.value = categoryResponse.data.data;
    }
  } catch (error) {
    console.error('Error loading analysis:', error);
  } finally {
    isLoading.value = false;
  }
};

// Watchers
watch(selectedPeriod, () => {
  loadData();
});

// Lifecycle
onMounted(() => {
  loadData();
});
</script>
