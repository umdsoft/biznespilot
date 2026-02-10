<template>
  <BusinessLayout :title="t('nav.dashboard')">

    <!-- Welcome Section -->
    <div class="mb-8">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            {{ t('dashboard.welcome') }}, {{ $page.props.auth?.user?.name }}!
          </h2>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
            <BuildingOfficeIcon class="w-4 h-4" />
            <span class="font-medium text-gray-700 dark:text-gray-300">{{ currentBusiness?.name }}</span>
            <span class="text-gray-300 dark:text-gray-600">|</span>
            <span>{{ todayDate }}</span>
          </p>
        </div>
      </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <!-- Leads -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
            <UsersIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
          </div>
          <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('dashboard.total_leads') }}</span>
        </div>
        <p v-if="isLoading" class="text-2xl font-bold text-gray-900 dark:text-gray-100 animate-pulse">---</p>
        <p v-else class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(stats.total_leads) }}</p>
      </div>

      <!-- Customers -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
            <UserIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
          </div>
          <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('dashboard.customers') }}</span>
        </div>
        <p v-if="isLoading" class="text-2xl font-bold text-gray-900 dark:text-gray-100 animate-pulse">---</p>
        <p v-else class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(stats.total_customers) }}</p>
      </div>

      <!-- Revenue -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
            <BanknotesIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
          </div>
          <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('dashboard.revenue_30d') }}</span>
        </div>
        <p v-if="isLoading" class="text-2xl font-bold text-gray-900 dark:text-gray-100 animate-pulse">---</p>
        <p v-else class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(stats.total_revenue) }}</p>
      </div>

      <!-- Conversion -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
            <ChartBarIcon class="w-5 h-5 text-orange-600 dark:text-orange-400" />
          </div>
          <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('dashboard.conversion') }}</span>
        </div>
        <p v-if="isLoading" class="text-2xl font-bold text-gray-900 dark:text-gray-100 animate-pulse">---</p>
        <p v-else class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.conversion_rate }}%</p>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="mb-8">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ t('dashboard.quick_actions') }}</h3>
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <Link
          v-for="action in quickActions"
          :key="action.href"
          :href="action.href"
          class="flex flex-col items-center gap-2 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 hover:shadow-md transition-all text-center group"
        >
          <div :class="action.bgClass" class="w-11 h-11 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
            <component :is="action.icon" :class="action.iconClass" class="w-5 h-5" />
          </div>
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ action.label }}</span>
        </Link>
      </div>
    </div>

    <!-- Two Column Layout: Activity + Business Status -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

      <!-- Left: Recent Activity -->
      <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ t('dashboard.recent_activity') }}</h3>
          </div>
          <div class="p-5">
            <div v-if="recentActivities.length > 0" class="space-y-4">
              <div
                v-for="activity in recentActivities"
                :key="activity.id"
                class="flex items-start gap-3"
              >
                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                     :class="getActivityColor(activity.type)">
                  <component :is="getActivityIcon(activity.type)" class="w-4 h-4" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm text-gray-900 dark:text-gray-100">{{ activity.description }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ activity.user_name }} &middot; {{ activity.created_at }}
                  </p>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8">
              <ClockIcon class="w-10 h-10 mx-auto mb-2 text-gray-300 dark:text-gray-600" />
              <p class="text-sm text-gray-400 dark:text-gray-500">{{ t('dashboard.no_activity') }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Business Status -->
      <div class="lg:col-span-1 space-y-6">
        <!-- Module Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ t('dashboard.business_status') }}</h3>
          </div>
          <div class="p-5 space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <UserGroupIcon class="w-4 h-4 text-purple-500" />
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ t('dashboard.ideal_customers') }}</span>
              </div>
              <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ moduleStats.dream_buyers }}</span>
            </div>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <MegaphoneIcon class="w-4 h-4 text-blue-500" />
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ t('dashboard.marketing_channels') }}</span>
              </div>
              <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ moduleStats.marketing_channels }}</span>
            </div>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <TagIcon class="w-4 h-4 text-green-500" />
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ t('dashboard.active_offers') }}</span>
              </div>
              <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ moduleStats.active_offers }}</span>
            </div>
          </div>
        </div>

        <!-- Subscription Widget (hidden during trial — TrialBanner handles it) -->
        <SubscriptionWidget v-if="!isTrial" :subscription-status="subscriptionStatus" />
      </div>
    </div>

    <!-- Sales Trend -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ t('dashboard.sales_trend') }}</h3>
      </div>
      <div v-if="salesTrend?.length > 0" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
          <thead>
            <tr class="bg-gray-50 dark:bg-gray-700/50">
              <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('dashboard.date') }}</th>
              <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('dashboard.sales') }}</th>
              <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('dashboard.revenue') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="item in salesTrend" :key="item.date" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
              <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ formatDate(item.date) }}</td>
              <td class="px-5 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ item.count }}</td>
              <td class="px-5 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency(item.revenue) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="text-center py-10">
        <ChartBarIcon class="w-10 h-10 mx-auto mb-2 text-gray-300 dark:text-gray-600" />
        <p class="text-sm text-gray-400 dark:text-gray-500">{{ t('dashboard.no_sales') }}</p>
      </div>
    </div>

  </BusinessLayout>
</template>

<script setup>
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import SubscriptionWidget from '@/components/Dashboard/SubscriptionWidget.vue';
import { Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useI18n } from '@/i18n';
import { formatNumber, formatFullCurrency, formatDateFull } from '@/utils/formatting';
import {
  UsersIcon,
  UserIcon,
  UserGroupIcon,
  BanknotesIcon,
  ChartBarIcon,
  MegaphoneIcon,
  CalendarIcon,
  PresentationChartLineIcon,
  CogIcon,
  TagIcon,
  ClockIcon,
  BuildingOfficeIcon,
  DocumentPlusIcon,
  BoltIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
  stats: Object,
  kpis: Object,
  roasBenchmark: Object,
  ltvCacBenchmark: Object,
  salesTrend: Array,
  moduleStats: Object,
  currentBusiness: Object,
  lazyLoad: { type: Boolean, default: false },
  revenueForecast: Array,
  aiInsights: Array,
  recentActivities: Array,
  activeAlertsCount: { type: Number, default: 0 },
  subscriptionStatus: { type: Object, default: null },
});

// Reactive state for lazy-loaded data
const isLoading = ref(false);
const loadedData = ref({
  stats: null,
  salesTrend: null,
});

// Default values
const defaultModuleStats = {
  dream_buyers: 0,
  marketing_channels: 0,
  active_offers: 0,
};

// Computed properties
const isTrial = computed(() => props.subscriptionStatus?.is_trial === true);

const stats = computed(() => loadedData.value.stats || props.stats || { total_leads: 0, total_customers: 0, total_revenue: 0, conversion_rate: 0 });
const salesTrend = computed(() => loadedData.value.salesTrend || props.salesTrend || []);
const moduleStats = computed(() => props.moduleStats || defaultModuleStats);
const recentActivities = computed(() => props.recentActivities || []);
const subscriptionStatus = computed(() => props.subscriptionStatus);

// Today's date formatted
const todayDate = computed(() => {
  const now = new Date();
  return now.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'long', year: 'numeric' });
});

// Quick actions
const quickActions = computed(() => [
  {
    href: '/business/sales',
    label: t('dashboard.go_to_leads'),
    icon: PresentationChartLineIcon,
    bgClass: 'bg-purple-100 dark:bg-purple-900/30',
    iconClass: 'text-purple-600 dark:text-purple-400',
  },
  {
    href: '/business/marketing',
    label: t('dashboard.go_to_marketing'),
    icon: MegaphoneIcon,
    bgClass: 'bg-blue-100 dark:bg-blue-900/30',
    iconClass: 'text-blue-600 dark:text-blue-400',
  },
  {
    href: '/business/marketing/content',
    label: t('dashboard.go_to_content'),
    icon: CalendarIcon,
    bgClass: 'bg-green-100 dark:bg-green-900/30',
    iconClass: 'text-green-600 dark:text-green-400',
  },
  {
    href: '/business/analytics',
    label: t('dashboard.go_to_analytics'),
    icon: ChartBarIcon,
    bgClass: 'bg-orange-100 dark:bg-orange-900/30',
    iconClass: 'text-orange-600 dark:text-orange-400',
  },
  {
    href: '/business/kpi',
    label: t('dashboard.go_to_kpi'),
    icon: BoltIcon,
    bgClass: 'bg-red-100 dark:bg-red-900/30',
    iconClass: 'text-red-600 dark:text-red-400',
  },
  {
    href: '/business/settings',
    label: t('dashboard.go_to_settings'),
    icon: CogIcon,
    bgClass: 'bg-gray-100 dark:bg-gray-700',
    iconClass: 'text-gray-600 dark:text-gray-400',
  },
]);

// Activity type icon mapping
const getActivityIcon = (type) => {
  const map = {
    lead_created: DocumentPlusIcon,
    lead_updated: UsersIcon,
    lead_won: CheckCircleIcon,
    lead_lost: ExclamationCircleIcon,
    offer_created: TagIcon,
    task_completed: CheckCircleIcon,
  };
  return map[type] || BoltIcon;
};

// Activity type color mapping
const getActivityColor = (type) => {
  const map = {
    lead_created: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
    lead_updated: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
    lead_won: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
    lead_lost: 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
    offer_created: 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400',
    task_completed: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
  };
  return map[type] || 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400';
};

// Fetch data lazily after component mounts
const fetchDashboardData = async () => {
  if (!props.lazyLoad) return;

  isLoading.value = true;
  try {
    const response = await axios.get('/business/api/dashboard/initial');
    if (response.data) {
      loadedData.value = {
        stats: response.data.stats,
        salesTrend: response.data.salesTrend,
      };
    }
  } catch (error) {
    console.error('Dashboard data loading error:', error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  if (props.lazyLoad) {
    fetchDashboardData();
  }
});

// formatCurrency
const formatCurrency = (value) => {
  if (!value) return '0 ' + t('common.currency');
  return formatFullCurrency(value, t('common.currency'));
};

// formatDate
const formatDate = (dateString) => {
  return formatDateFull(dateString);
};
</script>
