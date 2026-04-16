<template>
  <BusinessLayout title="Dashboard">

    <!-- Telegram System Bot Connect Banner -->
    <TelegramConnectBanner />

    <!-- 1. Welcome + Health Score -->
    <div class="mb-6 flex items-center justify-between flex-wrap gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
          Assalomu alaykum, {{ $page.props.auth?.user?.name }}!
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
          <BuildingOfficeIcon class="w-4 h-4" />
          <span class="font-medium text-gray-700 dark:text-gray-300">{{ currentBusiness?.name }}</span>
          <span class="text-gray-300 dark:text-gray-600">|</span>
          <span>{{ todayDate }}</span>
        </p>
      </div>

      <!-- Health Score -->
      <div v-if="!isLoading && healthScore > 0" class="flex items-center gap-3">
        <div class="relative w-14 h-14">
          <svg class="w-14 h-14 -rotate-90" viewBox="0 0 56 56">
            <circle cx="28" cy="28" r="24" fill="none" stroke-width="4"
                    class="stroke-gray-200 dark:stroke-gray-700" />
            <circle cx="28" cy="28" r="24" fill="none" stroke-width="4"
                    :stroke="healthScoreColor"
                    stroke-linecap="round"
                    :stroke-dasharray="`${healthScore * 1.508} 150.8`" />
          </svg>
          <span class="absolute inset-0 flex items-center justify-center text-sm font-bold"
                :class="healthScoreTextColor">
            {{ healthScore }}
          </span>
        </div>
        <div>
          <p class="text-xs text-gray-500 dark:text-gray-400">Biznes sog'ligi</p>
          <p class="text-sm font-semibold" :class="healthScoreTextColor">{{ healthScoreLabel }}</p>
        </div>
      </div>
    </div>

    <!-- 2. KPI Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <!-- Bugungi daromad -->
      <StatCard
        title="Bugungi daromad"
        :value="isLoading ? '---' : formatMoney(stats.today_revenue)"
        :icon="BanknotesIcon"
        icon-bg-color="green"
        :change="revenueChange"
        change-label="kechagiga nisbatan"
      />
      <!-- Oylik daromad -->
      <StatCard
        title="Oylik daromad"
        :value="isLoading ? '---' : formatMoney(stats.monthly_revenue)"
        :icon="CurrencyDollarIcon"
        icon-bg-color="blue"
        :change="monthlyRevenueChange"
        change-label="oldingi oyga nisbatan"
      />
      <!-- Buyurtmalar -->
      <StatCard
        title="Buyurtmalar (30 kun)"
        :value="isLoading ? '---' : formatNumber(stats.monthly_orders)"
        :icon="ShoppingBagIcon"
        icon-bg-color="purple"
        :change="ordersChange"
        change-label="oldingi oyga nisbatan"
      />
      <!-- Mijozlar -->
      <StatCard
        title="Jami mijozlar"
        :value="isLoading ? '---' : formatNumber(stats.total_customers)"
        :icon="UsersIcon"
        icon-bg-color="orange"
        :subtitle="stats.new_customers_today > 0 ? `+${stats.new_customers_today} bugun` : ''"
      />
    </div>

    <!-- 3. Revenue Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 mb-6 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Daromad dinamikasi</h3>
        <span class="text-xs text-gray-400 dark:text-gray-500">Oxirgi 14 kun</span>
      </div>
      <div class="p-4">
        <div v-if="isLoading" class="h-56 flex items-center justify-center">
          <div class="animate-pulse text-gray-400 dark:text-gray-500 text-sm">Yuklanmoqda...</div>
        </div>
        <div v-else-if="hasRevenueData" class="h-56">
          <apexchart
            type="area"
            height="220"
            :options="chartOptions"
            :series="chartSeries"
          />
        </div>
        <div v-else class="h-56 flex flex-col items-center justify-center">
          <ChartBarIcon class="w-10 h-10 text-gray-300 dark:text-gray-600 mb-2" />
          <p class="text-sm text-gray-400 dark:text-gray-500">Hali ma'lumot yo'q</p>
        </div>
      </div>
    </div>

    <!-- 4. Action Items + Quick Links -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <!-- Kutilayotgan ishlar -->
      <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Kutilayotgan ishlar</h3>
          </div>
          <div class="p-5 space-y-3">
            <!-- Pending Orders -->
            <Link v-if="pendingActions.pending_orders > 0"
                  href="/business/store/orders"
                  class="flex items-center justify-between p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors group">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center">
                  <ClockIcon class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ pendingActions.pending_orders }} ta buyurtma kutilmoqda</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Tasdiqlash kerak</p>
                </div>
              </div>
              <ChevronRightIcon class="w-5 h-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" />
            </Link>

            <!-- Unanswered Leads -->
            <Link v-if="pendingActions.unanswered_leads > 0"
                  href="/business/sales"
                  class="flex items-center justify-between p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors group">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                  <UsersIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ pendingActions.unanswered_leads }} ta yangi lid</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Javob berilmagan</p>
                </div>
              </div>
              <ChevronRightIcon class="w-5 h-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" />
            </Link>

            <!-- Overdue Tasks -->
            <Link v-if="pendingActions.today_tasks > 0"
                  href="/business/tasks"
                  class="flex items-center justify-between p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors group">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-red-100 dark:bg-red-900/40 flex items-center justify-center">
                  <ExclamationCircleIcon class="w-5 h-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ pendingActions.today_tasks }} ta vazifa</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Bajarish kerak / muddati o'tgan</p>
                </div>
              </div>
              <ChevronRightIcon class="w-5 h-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" />
            </Link>

            <!-- Hech narsa kutilmayotgan bo'lsa -->
            <div v-if="!pendingActions.pending_orders && !pendingActions.unanswered_leads && !pendingActions.today_tasks"
                 class="text-center py-6">
              <CheckCircleIcon class="w-10 h-10 mx-auto mb-2 text-green-400" />
              <p class="text-sm text-gray-500 dark:text-gray-400">Barcha ishlar bajarilgan!</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="lg:col-span-1 space-y-3">
        <Link v-for="action in quickLinks" :key="action.href" :href="action.href"
              class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 hover:shadow-md transition-all group">
          <div :class="action.bgClass" class="w-10 h-10 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
            <component :is="action.icon" :class="action.iconClass" class="w-5 h-5" />
          </div>
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ action.label }}</span>
        </Link>
      </div>
    </div>

    <!-- 5. Tavfsiyalar -->
    <div v-if="recommendations.length > 0" class="mb-6">
      <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3">Tavfsiyalar</h3>
      <div class="space-y-2">
        <component
          :is="rec.action_url ? Link : 'div'"
          v-for="(rec, idx) in recommendations"
          :key="idx"
          :href="rec.action_url || undefined"
          :class="[
            'flex items-center gap-3 p-3 rounded-lg border transition-colors',
            rec.type === 'warning' ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' :
            rec.type === 'success' ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' :
            'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
            rec.action_url ? 'hover:shadow-sm cursor-pointer' : '',
          ]"
        >
          <div :class="[
            'w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0',
            rec.type === 'warning' ? 'bg-red-100 dark:bg-red-900/40' :
            rec.type === 'success' ? 'bg-green-100 dark:bg-green-900/40' :
            'bg-blue-100 dark:bg-blue-900/40',
          ]">
            <component :is="getRecIcon(rec.icon)" :class="[
              'w-4 h-4',
              rec.type === 'warning' ? 'text-red-600 dark:text-red-400' :
              rec.type === 'success' ? 'text-green-600 dark:text-green-400' :
              'text-blue-600 dark:text-blue-400',
            ]" />
          </div>
          <p class="text-sm text-gray-800 dark:text-gray-200 flex-1">{{ rec.message }}</p>
          <span v-if="rec.action_text" :class="[
            'text-xs font-medium whitespace-nowrap',
            rec.type === 'warning' ? 'text-red-600 dark:text-red-400' :
            rec.type === 'success' ? 'text-green-600 dark:text-green-400' :
            'text-blue-600 dark:text-blue-400',
          ]">{{ rec.action_text }} &rarr;</span>
        </component>
      </div>
    </div>

    <!-- 6. Oxirgi Buyurtmalar + Faoliyat -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <!-- Oxirgi buyurtmalar -->
      <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Oxirgi buyurtmalar</h3>
            <Link href="/business/store/orders" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
              Barchasi &rarr;
            </Link>
          </div>
          <div v-if="recentOrders.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
              <thead>
                <tr class="bg-gray-50 dark:bg-gray-700/50">
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">#</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Mijoz</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Summa</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Vaqt</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr v-for="order in recentOrders" :key="order.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ order.order_number }}</td>
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ order.customer_name }}</td>
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency(order.total) }}</td>
                  <td class="px-4 py-3">
                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(order.status)]">
                      {{ order.status_label }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ order.created_at }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="text-center py-10">
            <ShoppingBagIcon class="w-10 h-10 mx-auto mb-2 text-gray-300 dark:text-gray-600" />
            <p class="text-sm text-gray-400 dark:text-gray-500">Hali buyurtmalar yo'q</p>
          </div>
        </div>
      </div>

      <!-- So'nggi faoliyat -->
      <div class="lg:col-span-1">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">So'nggi faoliyat</h3>
          </div>
          <div class="p-4">
            <div v-if="recentActivities.length > 0" class="space-y-3">
              <div v-for="activity in recentActivities" :key="activity.id" class="flex items-start gap-3">
                <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0"
                     :class="getActivityColor(activity.type)">
                  <component :is="getActivityIcon(activity.type)" class="w-3.5 h-3.5" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm text-gray-900 dark:text-gray-100 leading-tight">{{ activity.description }}</p>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ activity.created_at }}</p>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-6">
              <ClockIcon class="w-8 h-8 mx-auto mb-2 text-gray-300 dark:text-gray-600" />
              <p class="text-xs text-gray-400 dark:text-gray-500">Faoliyat yo'q</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 7. Subscription Widget -->
    <SubscriptionWidget v-if="!isTrial" :subscription-status="subscriptionStatus" />

  </BusinessLayout>
</template>

<script setup>
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import SubscriptionWidget from '@/components/Dashboard/SubscriptionWidget.vue';
import TelegramConnectBanner from '@/components/Dashboard/TelegramConnectBanner.vue';
import StatCard from '@/components/Dashboard/StatCard.vue';
import { Link } from '@inertiajs/vue3';
import { ref, onMounted, computed, defineAsyncComponent } from 'vue';
import axios from 'axios';
import { formatNumber, formatFullCurrency } from '@/utils/formatting';
import {
  UsersIcon,
  BanknotesIcon,
  CurrencyDollarIcon,
  ChartBarIcon,
  ShoppingBagIcon,
  ClockIcon,
  BuildingOfficeIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  MegaphoneIcon,
  CalendarIcon,
  CogIcon,
  ChevronRightIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  UserPlusIcon,
  ArrowPathIcon,
  BoltIcon,
  DocumentPlusIcon,
  TagIcon,
} from '@heroicons/vue/24/outline';

// ApexCharts — lazy load to avoid SSR issues
const apexchart = defineAsyncComponent(() => import('vue3-apexcharts').then(m => m.default || m));

const props = defineProps({
  dashboardData: Object,
  recentActivities: { type: Array, default: () => [] },
  activeAlertsCount: { type: Number, default: 0 },
  subscriptionStatus: { type: Object, default: null },
  currentBusiness: Object,
  lazyLoad: { type: Boolean, default: false },
});

// State
const isLoading = ref(false);
const loadedData = ref(null);

// Computed data
const data = computed(() => loadedData.value || props.dashboardData || {});
const stats = computed(() => data.value?.stats || {});
const healthScore = computed(() => data.value?.health_score || 0);
const revenueChart = computed(() => data.value?.revenue_chart || []);
const pendingActions = computed(() => data.value?.pending_actions || {});
const recommendations = computed(() => data.value?.recommendations || []);
const recentOrders = computed(() => data.value?.recent_orders || []);
const recentActivities = computed(() => props.recentActivities || []);
const subscriptionStatus = computed(() => props.subscriptionStatus);
const isTrial = computed(() => props.subscriptionStatus?.is_trial === true);

// Today date
const todayDate = computed(() => {
  const now = new Date();
  return now.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'long', year: 'numeric' });
});

// Change calculations
const revenueChange = computed(() => {
  const t = stats.value.today_revenue || 0;
  const y = stats.value.yesterday_revenue || 0;
  if (!y) return undefined;
  return Math.round(((t - y) / y) * 100);
});

const monthlyRevenueChange = computed(() => {
  const c = stats.value.monthly_revenue || 0;
  const p = stats.value.prev_month_revenue || 0;
  if (!p) return undefined;
  return Math.round(((c - p) / p) * 100);
});

const ordersChange = computed(() => {
  const c = stats.value.monthly_orders || 0;
  const p = stats.value.prev_month_orders || 0;
  if (!p) return undefined;
  return Math.round(((c - p) / p) * 100);
});

// Health score display
const healthScoreColor = computed(() => {
  const s = healthScore.value;
  if (s >= 70) return '#10b981';
  if (s >= 40) return '#f59e0b';
  return '#ef4444';
});

const healthScoreTextColor = computed(() => {
  const s = healthScore.value;
  if (s >= 70) return 'text-green-600 dark:text-green-400';
  if (s >= 40) return 'text-amber-600 dark:text-amber-400';
  return 'text-red-600 dark:text-red-400';
});

const healthScoreLabel = computed(() => {
  const s = healthScore.value;
  if (s >= 70) return 'Yaxshi';
  if (s >= 40) return "O'rtacha";
  return 'Past';
});

// Revenue chart
const hasRevenueData = computed(() => revenueChart.value.some(d => d.revenue > 0 || d.orders > 0));

const chartSeries = computed(() => [{
  name: 'Daromad',
  data: revenueChart.value.map(d => d.revenue),
}]);

const chartOptions = computed(() => ({
  chart: {
    type: 'area',
    toolbar: { show: false },
    sparkline: { enabled: false },
    fontFamily: 'Inter, system-ui, sans-serif',
    background: 'transparent',
  },
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2 },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.4,
      opacityTo: 0.05,
      stops: [0, 100],
    },
  },
  colors: ['#3b82f6'],
  xaxis: {
    categories: revenueChart.value.map(d => {
      const date = new Date(d.date);
      return `${date.getDate()}/${date.getMonth() + 1}`;
    }),
    labels: {
      style: { colors: '#9ca3af', fontSize: '11px' },
    },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      style: { colors: '#9ca3af', fontSize: '11px' },
      formatter: (val) => {
        if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
        if (val >= 1000) return (val / 1000).toFixed(0) + 'K';
        return val;
      },
    },
  },
  grid: {
    borderColor: '#e5e7eb',
    strokeDashArray: 4,
    xaxis: { lines: { show: false } },
  },
  tooltip: {
    theme: 'dark',
    y: {
      formatter: (val) => new Intl.NumberFormat('uz-UZ').format(val) + " so'm",
    },
  },
}));

// Quick links
const quickLinks = [
  {
    href: '/business/store/orders',
    label: "Do'kon buyurtmalari",
    icon: ShoppingBagIcon,
    bgClass: 'bg-purple-100 dark:bg-purple-900/30',
    iconClass: 'text-purple-600 dark:text-purple-400',
  },
  {
    href: '/business/sales',
    label: 'Lidlar va sotuvlar',
    icon: UsersIcon,
    bgClass: 'bg-blue-100 dark:bg-blue-900/30',
    iconClass: 'text-blue-600 dark:text-blue-400',
  },
  {
    href: '/business/marketing/content-ai',
    label: 'AI Kontent',
    icon: BoltIcon,
    bgClass: 'bg-green-100 dark:bg-green-900/30',
    iconClass: 'text-green-600 dark:text-green-400',
  },
  {
    href: '/business/settings',
    label: 'Sozlamalar',
    icon: CogIcon,
    bgClass: 'bg-gray-100 dark:bg-gray-700',
    iconClass: 'text-gray-600 dark:text-gray-400',
  },
];

// Order status colors
const getStatusColor = (status) => {
  const map = {
    pending: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
    confirmed: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    processing: 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400',
    shipped: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
    delivered: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
    cancelled: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
    refunded: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400',
  };
  return map[status] || map.pending;
};

// Activity helpers
const getActivityIcon = (type) => {
  const map = {
    lead_created: DocumentPlusIcon,
    lead_updated: UsersIcon,
    lead_won: CheckCircleIcon,
    lead_lost: ExclamationCircleIcon,
    offer_created: TagIcon,
    task_completed: CheckCircleIcon,
    store_order: BanknotesIcon,
  };
  return map[type] || BoltIcon;
};

const getActivityColor = (type) => {
  const map = {
    lead_created: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
    lead_updated: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
    lead_won: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
    lead_lost: 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
    offer_created: 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400',
    task_completed: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
    store_order: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
  };
  return map[type] || 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400';
};

// Recommendation icons
const getRecIcon = (icon) => {
  const map = {
    clock: ClockIcon,
    users: UsersIcon,
    megaphone: MegaphoneIcon,
    'trending-up': ArrowTrendingUpIcon,
    'trending-down': ArrowTrendingDownIcon,
    calendar: CalendarIcon,
    'user-plus': UserPlusIcon,
    exclamation: ExclamationCircleIcon,
    refresh: ArrowPathIcon,
  };
  return map[icon] || BoltIcon;
};

// Format helpers — Dashboard uchun to'liq raqam (vergul bilan)
const formatMoney = (value) => {
  if (!value && value !== 0) return "0 so'm";
  return formatFullCurrency(value, "so'm");
};

const formatCurrency = (value) => {
  if (!value && value !== 0) return "0 so'm";
  return formatFullCurrency(value, "so'm");
};

// Fetch data
const fetchDashboardData = async () => {
  if (!props.lazyLoad) return;
  isLoading.value = true;
  try {
    const response = await axios.get('/business/api/dashboard/initial');
    if (response.data) {
      loadedData.value = response.data;
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
</script>
