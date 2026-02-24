<template>
  <Head title="Do'kon - Boshqaruv paneli" />
  <component :is="layoutComponent" title="Do'kon">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Do'kon boshqaruvi</h1>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Statistika va buyurtmalar</p>
        </div>
        <div class="flex items-center gap-3">
          <Link
            :href="storeRoute('orders.index')"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-medium rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
          >
            <ClipboardDocumentListIcon class="w-4 h-4" />
            Buyurtmalar
          </Link>
          <Link
            v-if="isBusinessPanel"
            :href="storeRoute('products.create')"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors"
          >
            <PlusIcon class="w-4 h-4" />
            Yangi mahsulot
          </Link>
        </div>
      </div>

      <!-- Period Tabs + Stats -->
      <div>
        <!-- Period Selector -->
        <div class="flex items-center gap-1 mb-4 bg-slate-100 dark:bg-slate-800 rounded-lg p-1 w-fit">
          <button
            v-for="tab in periodTabs"
            :key="tab.key"
            @click="activePeriod = tab.key"
            class="px-4 py-1.5 text-sm font-medium rounded-md transition-all"
            :class="activePeriod === tab.key
              ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm'
              : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
          >
            {{ tab.label }}
          </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                <BanknotesIcon class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
              </div>
              <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Daromad</span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatPrice(currentStats.revenue || 0) }}</p>
            <p v-if="currentStats.revenue_change" class="text-xs mt-1.5 flex items-center gap-1"
              :class="currentStats.revenue_change >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'"
            >
              <ArrowTrendingUpIcon v-if="currentStats.revenue_change >= 0" class="w-3.5 h-3.5" />
              <ArrowTrendingDownIcon v-else class="w-3.5 h-3.5" />
              {{ Math.abs(currentStats.revenue_change) }}% {{ periodCompareLabel }}
            </p>
          </div>

          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                <ShoppingCartIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
              </div>
              <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Buyurtmalar</span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ currentStats.orders_count || 0 }}</p>
            <p v-if="currentStats.orders_change" class="text-xs mt-1.5 flex items-center gap-1"
              :class="currentStats.orders_change >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'"
            >
              <ArrowTrendingUpIcon v-if="currentStats.orders_change >= 0" class="w-3.5 h-3.5" />
              <ArrowTrendingDownIcon v-else class="w-3.5 h-3.5" />
              {{ Math.abs(currentStats.orders_change) }}% {{ periodCompareLabel }}
            </p>
          </div>

          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 bg-violet-100 dark:bg-violet-900/30 rounded-lg flex items-center justify-center">
                <ReceiptPercentIcon class="w-5 h-5 text-violet-600 dark:text-violet-400" />
              </div>
              <span class="text-sm font-medium text-slate-500 dark:text-slate-400">O'rtacha check</span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatPrice(currentStats.avg_order_value || 0) }}</p>
          </div>

          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                <UserPlusIcon class="w-5 h-5 text-amber-600 dark:text-amber-400" />
              </div>
              <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Yangi mijozlar</span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ currentStats.new_customers || 0 }}</p>
          </div>
        </div>
      </div>

      <!-- Quick Navigation -->
      <div class="grid grid-cols-3 sm:grid-cols-6 gap-3">
        <Link
          v-for="nav in quickLinks"
          :key="nav.suffix"
          :href="storeRoute(nav.suffix)"
          class="group bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 sm:p-4 hover:border-emerald-300 dark:hover:border-emerald-700 transition-colors text-center"
        >
          <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center mx-auto mb-2" :class="nav.bgColor">
            <component :is="nav.icon" class="w-5 h-5" :class="nav.iconColor" />
          </div>
          <p class="text-xs sm:text-sm font-semibold text-slate-900 dark:text-white">{{ nav.label }}</p>
        </Link>
      </div>

      <!-- Revenue Chart + Status Overview -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Revenue Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <div>
              <h3 class="text-base font-semibold text-slate-900 dark:text-white">Oxirgi 30 kun daromadi</h3>
              <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ formatPrice(totalChartRevenue) }} jami</p>
            </div>
          </div>
          <div class="p-5">
            <div v-if="chartData && chartData.length > 0">
              <!-- SVG Chart -->
              <div class="relative" @mouseleave="hoveredIndex = -1">
                <svg :viewBox="`0 0 ${svgWidth} ${svgHeight + 30}`" class="w-full h-64 sm:h-72" preserveAspectRatio="none">
                  <!-- Grid lines -->
                  <line v-for="(line, i) in gridLines" :key="'grid-'+i"
                    :x1="chartPadding.left" :y1="line.y" :x2="svgWidth - chartPadding.right" :y2="line.y"
                    stroke="currentColor" class="text-slate-100 dark:text-slate-700" stroke-width="1" stroke-dasharray="4,4"
                  />

                  <!-- Y-axis labels -->
                  <text v-for="(line, i) in gridLines" :key="'label-'+i"
                    :x="chartPadding.left - 8" :y="line.y + 4"
                    text-anchor="end" class="fill-slate-400 dark:fill-slate-500" font-size="11"
                  >{{ line.label }}</text>

                  <!-- Area gradient -->
                  <defs>
                    <linearGradient id="areaGradient" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stop-color="rgb(16, 185, 129)" stop-opacity="0.3" />
                      <stop offset="100%" stop-color="rgb(16, 185, 129)" stop-opacity="0.02" />
                    </linearGradient>
                  </defs>

                  <!-- Area fill -->
                  <polygon
                    :points="areaPoints"
                    fill="url(#areaGradient)"
                  />

                  <!-- Line -->
                  <polyline
                    :points="linePoints"
                    fill="none"
                    stroke="rgb(16, 185, 129)"
                    stroke-width="2.5"
                    stroke-linejoin="round"
                    stroke-linecap="round"
                  />

                  <!-- Data points -->
                  <circle v-for="(point, i) in chartPoints" :key="'dot-'+i"
                    :cx="point.x" :cy="point.y"
                    :r="hoveredIndex === i ? 5 : 0"
                    fill="rgb(16, 185, 129)" stroke="white" stroke-width="2"
                    class="transition-all duration-150"
                  />

                  <!-- Hover line -->
                  <line v-if="hoveredIndex >= 0 && chartPoints[hoveredIndex]"
                    :x1="chartPoints[hoveredIndex].x" :y1="chartPadding.top"
                    :x2="chartPoints[hoveredIndex].x" :y2="svgHeight - chartPadding.bottom"
                    stroke="rgb(16, 185, 129)" stroke-width="1" stroke-dasharray="3,3" opacity="0.5"
                  />

                  <!-- Invisible hover zones -->
                  <rect v-for="(point, i) in chartPoints" :key="'zone-'+i"
                    :x="point.x - (chartAreaWidth / chartData.length / 2)"
                    :y="chartPadding.top"
                    :width="chartAreaWidth / chartData.length"
                    :height="svgHeight - chartPadding.top - chartPadding.bottom"
                    fill="transparent"
                    @mouseenter="hoveredIndex = i"
                    class="cursor-pointer"
                  />

                  <!-- X-axis labels -->
                  <text v-for="(label, i) in xAxisLabels" :key="'x-'+i"
                    :x="label.x" :y="svgHeight + 20"
                    text-anchor="middle" class="fill-slate-400 dark:fill-slate-500" font-size="11"
                  >{{ label.text }}</text>
                </svg>

                <!-- Tooltip -->
                <div v-if="hoveredIndex >= 0 && chartData[hoveredIndex]"
                  class="absolute top-2 right-2 bg-slate-900 dark:bg-slate-700 text-white rounded-lg px-3.5 py-2.5 shadow-xl pointer-events-none z-10"
                >
                  <p class="text-xs text-slate-300 mb-1">{{ chartData[hoveredIndex].date }}</p>
                  <p class="text-sm font-bold">{{ formatPrice(chartData[hoveredIndex].revenue) }}</p>
                  <p class="text-xs text-slate-300 mt-0.5">{{ chartData[hoveredIndex].orders_count }} ta buyurtma</p>
                </div>
              </div>
            </div>
            <div v-else class="flex flex-col items-center justify-center py-16 text-slate-400 dark:text-slate-500">
              <ChartBarIcon class="w-12 h-12 mb-3" />
              <p class="text-sm font-medium">Hozircha ma'lumot yo'q</p>
              <p class="text-xs mt-1">Buyurtmalar kelib tushganda grafik paydo bo'ladi</p>
            </div>
          </div>
        </div>

        <!-- Store Health + Top Products -->
        <div class="space-y-6">
          <!-- Store Health -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
              <h3 class="text-base font-semibold text-slate-900 dark:text-white">Do'kon holati</h3>
            </div>
            <div class="p-5 grid grid-cols-2 gap-4">
              <div class="text-center">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ storeHealth?.total_products || 0 }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Mahsulotlar</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ storeHealth?.total_customers || 0 }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Mijozlar</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold" :class="storeHealth?.pending_orders > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-slate-900 dark:text-white'">
                  {{ storeHealth?.pending_orders || 0 }}
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Kutilmoqda</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ storeHealth?.active_products || 0 }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Faol mahsulot</p>
              </div>
            </div>
          </div>

          <!-- Top Products -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
              <h3 class="text-base font-semibold text-slate-900 dark:text-white">Top mahsulotlar</h3>
            </div>
            <div class="p-4">
              <div v-if="topProducts && topProducts.length > 0" class="space-y-3">
                <div
                  v-for="(product, index) in topProducts.slice(0, 5)"
                  :key="product.id"
                  class="flex items-center gap-3"
                >
                  <span class="text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0"
                    :class="index < 3 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400'"
                  >{{ index + 1 }}</span>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ product.name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ product.total_quantity }} ta sotildi</p>
                  </div>
                  <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 whitespace-nowrap">{{ formatCompactPrice(product.total_revenue) }}</span>
                </div>
              </div>
              <div v-else class="text-center py-8 text-slate-400 dark:text-slate-500">
                <CubeIcon class="w-8 h-8 mx-auto mb-2" />
                <p class="text-sm">Hozircha sotuvlar yo'q</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pending Orders + Recent Orders -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Pending Orders (Need Attention) -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <h3 class="text-base font-semibold text-slate-900 dark:text-white">Yangi buyurtmalar</h3>
              <span v-if="pendingOrders.length > 0" class="px-2 py-0.5 text-xs font-bold bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full">
                {{ pendingOrders.length }}
              </span>
            </div>
            <Link
              :href="storeRoute('orders.index', { status: 'pending' })"
              class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 font-medium"
            >
              Barchasi
            </Link>
          </div>

          <div v-if="pendingOrders.length > 0" class="divide-y divide-slate-100 dark:divide-slate-700">
            <div
              v-for="order in pendingOrders.slice(0, 5)"
              :key="order.id"
              class="px-5 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors cursor-pointer"
              @click="router.visit(storeRoute('orders.show', order.id))"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-sm font-bold text-slate-900 dark:text-white">#{{ order.order_number }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="getStatusClass(order.status)">
                      {{ getStatusLabel(order.status) }}
                    </span>
                  </div>
                  <p class="text-sm text-slate-600 dark:text-slate-400">{{ order.customer_name }}</p>
                  <p v-if="order.items_preview?.length" class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                    {{ order.items_preview.map(i => `${i.name} ×${i.quantity}`).join(', ') }}
                    <span v-if="order.items_count > 3">...</span>
                  </p>
                </div>
                <div class="text-right flex-shrink-0">
                  <p class="text-sm font-bold text-slate-900 dark:text-white">{{ formatPrice(order.total) }}</p>
                  <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ timeAgo(order.created_at) }}</p>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-12 text-slate-400 dark:text-slate-500">
            <CheckCircleIcon class="w-10 h-10 mx-auto mb-2 text-emerald-400" />
            <p class="text-sm font-medium">Kutilayotgan buyurtmalar yo'q</p>
            <p class="text-xs mt-1">Barcha buyurtmalar ko'rib chiqilgan</p>
          </div>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Oxirgi buyurtmalar</h3>
            <Link
              :href="storeRoute('orders.index')"
              class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 font-medium"
            >
              Barchasi
            </Link>
          </div>

          <div v-if="recentOrders && recentOrders.length > 0" class="divide-y divide-slate-100 dark:divide-slate-700">
            <div
              v-for="order in recentOrders.slice(0, 5)"
              :key="order.id"
              class="px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors cursor-pointer flex items-center gap-3"
              @click="router.visit(storeRoute('orders.show', order.id))"
            >
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <span class="text-sm font-semibold text-slate-900 dark:text-white">#{{ order.order_number }}</span>
                  <span class="text-xs text-slate-500 dark:text-slate-400">&middot;</span>
                  <span class="text-sm text-slate-600 dark:text-slate-400 truncate">{{ order.customer_name }}</span>
                </div>
                <div class="flex items-center gap-2 mt-1">
                  <span class="text-xs text-slate-400 dark:text-slate-500">{{ order.items_count }} ta mahsulot</span>
                  <span class="text-xs text-slate-400">&middot;</span>
                  <span class="text-xs text-slate-400 dark:text-slate-500">{{ formatDate(order.created_at) }}</span>
                </div>
              </div>
              <div class="text-right flex-shrink-0">
                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ formatPrice(order.total) }}</p>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mt-1" :class="getStatusClass(order.status)">
                  {{ getStatusLabel(order.status) }}
                </span>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-12 text-slate-400 dark:text-slate-500">
            <ShoppingCartIcon class="w-10 h-10 mx-auto mb-2" />
            <p class="text-sm">Hozircha buyurtmalar yo'q</p>
          </div>
        </div>
      </div>
    </div>
  </component>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useStorePanel } from '@/composables/useStorePanel';
import {
  BanknotesIcon,
  ShoppingCartIcon,
  ChartBarIcon,
  CubeIcon,
  PlusIcon,
  FolderIcon,
  TagIcon,
  CogIcon,
  UsersIcon,
  CheckCircleIcon,
  ClipboardDocumentListIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  UserPlusIcon,
  ReceiptPercentIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  store: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
  weekStats: { type: Object, default: () => ({}) },
  monthStats: { type: Object, default: () => ({}) },
  recentOrders: { type: Array, default: () => [] },
  pendingOrders: { type: Array, default: () => [] },
  topProducts: { type: Array, default: () => [] },
  chartData: { type: Array, default: () => [] },
  statusDistribution: { type: Object, default: () => ({}) },
  storeHealth: { type: Object, default: () => ({}) },
  panelType: { type: String, default: 'business' },
});

const { layoutComponent, storeRoute, isBusinessPanel } = useStorePanel(props.panelType);

// Period management
const activePeriod = ref('today');
const periodTabs = [
  { key: 'today', label: 'Bugun' },
  { key: 'week', label: 'Hafta' },
  { key: 'month', label: 'Oy' },
];

const currentStats = computed(() => {
  if (activePeriod.value === 'week') return props.weekStats || {};
  if (activePeriod.value === 'month') return props.monthStats || {};
  return props.stats || {};
});

const periodCompareLabel = computed(() => {
  if (activePeriod.value === 'week') return "o'tgan hafta";
  if (activePeriod.value === 'month') return "o'tgan oy";
  return 'kechagiga';
});

// Quick nav links
const allQuickLinks = [
  { suffix: 'categories.index', label: 'Kategoriyalar', icon: FolderIcon, bgColor: 'bg-amber-100 dark:bg-amber-900/30', iconColor: 'text-amber-600 dark:text-amber-400', businessOnly: true },
  { suffix: 'catalog.index', label: 'Katalog', icon: CubeIcon, bgColor: 'bg-emerald-100 dark:bg-emerald-900/30', iconColor: 'text-emerald-600 dark:text-emerald-400' },
  { suffix: 'orders.index', label: 'Buyurtmalar', icon: ShoppingCartIcon, bgColor: 'bg-blue-100 dark:bg-blue-900/30', iconColor: 'text-blue-600 dark:text-blue-400' },
  { suffix: 'customers.index', label: 'Mijozlar', icon: UsersIcon, bgColor: 'bg-violet-100 dark:bg-violet-900/30', iconColor: 'text-violet-600 dark:text-violet-400' },
  { suffix: 'promo-codes.index', label: 'Promo kodlar', icon: TagIcon, bgColor: 'bg-pink-100 dark:bg-pink-900/30', iconColor: 'text-pink-600 dark:text-pink-400', businessOnly: true },
  { suffix: 'settings', label: 'Sozlamalar', icon: CogIcon, bgColor: 'bg-slate-100 dark:bg-slate-700', iconColor: 'text-slate-600 dark:text-slate-400', businessOnly: true },
];
const quickLinks = computed(() => allQuickLinks.filter(l => !l.businessOnly || isBusinessPanel));

// Chart configuration
const svgWidth = 600;
const svgHeight = 220;
const chartPadding = { top: 10, right: 10, bottom: 10, left: 55 };
const chartAreaWidth = svgWidth - chartPadding.left - chartPadding.right;
const chartAreaHeight = svgHeight - chartPadding.top - chartPadding.bottom;
const hoveredIndex = ref(-1);

const maxChartRevenue = computed(() => {
  if (!props.chartData?.length) return 1;
  return Math.max(...props.chartData.map(d => d.revenue || 0), 1);
});

const totalChartRevenue = computed(() => {
  if (!props.chartData?.length) return 0;
  return props.chartData.reduce((sum, d) => sum + (d.revenue || 0), 0);
});

const chartPoints = computed(() => {
  if (!props.chartData?.length) return [];
  const len = props.chartData.length;
  return props.chartData.map((day, i) => ({
    x: chartPadding.left + (len > 1 ? (i / (len - 1)) * chartAreaWidth : chartAreaWidth / 2),
    y: chartPadding.top + chartAreaHeight - ((day.revenue || 0) / maxChartRevenue.value) * chartAreaHeight,
  }));
});

const linePoints = computed(() => chartPoints.value.map(p => `${p.x},${p.y}`).join(' '));

const areaPoints = computed(() => {
  if (!chartPoints.value.length) return '';
  const pts = chartPoints.value;
  const bottom = svgHeight - chartPadding.bottom;
  return `${pts[0].x},${bottom} ` + pts.map(p => `${p.x},${p.y}`).join(' ') + ` ${pts[pts.length - 1].x},${bottom}`;
});

const gridLines = computed(() => {
  const lines = [];
  const steps = 4;
  for (let i = 0; i <= steps; i++) {
    const value = (maxChartRevenue.value / steps) * (steps - i);
    const y = chartPadding.top + (i / steps) * chartAreaHeight;
    lines.push({ y, label: formatCompactNumber(value) });
  }
  return lines;
});

const xAxisLabels = computed(() => {
  if (!props.chartData?.length) return [];
  const len = props.chartData.length;
  const labels = [];
  const step = len <= 7 ? 1 : len <= 15 ? 3 : 5;
  for (let i = 0; i < len; i += step) {
    labels.push({
      x: chartPadding.left + (len > 1 ? (i / (len - 1)) * chartAreaWidth : chartAreaWidth / 2),
      text: props.chartData[i].date,
    });
  }
  // Always include last point
  if (len > 1 && (len - 1) % step !== 0) {
    labels.push({
      x: chartPadding.left + chartAreaWidth,
      text: props.chartData[len - 1].date,
    });
  }
  return labels;
});

// Formatting
const formatPrice = (value) => {
  if (!value && value !== 0) return "0 so'm";
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

const formatCompactPrice = (value) => {
  if (!value) return "0";
  if (value >= 1_000_000) return (value / 1_000_000).toFixed(1).replace('.0', '') + 'M';
  if (value >= 1_000) return (value / 1_000).toFixed(0) + 'K';
  return new Intl.NumberFormat('uz-UZ').format(value);
};

const formatCompactNumber = (value) => {
  if (!value) return '0';
  if (value >= 1_000_000_000) return (value / 1_000_000_000).toFixed(1).replace('.0', '') + 'B';
  if (value >= 1_000_000) return (value / 1_000_000).toFixed(1).replace('.0', '') + 'M';
  if (value >= 1_000) return (value / 1_000).toFixed(0) + 'K';
  return Math.round(value).toString();
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short' });
};

const timeAgo = (dateString) => {
  if (!dateString) return '';
  const now = new Date();
  const date = new Date(dateString);
  const mins = Math.floor((now - date) / 60000);
  if (mins < 1) return 'Hozirgina';
  if (mins < 60) return `${mins} daqiqa oldin`;
  const hours = Math.floor(mins / 60);
  if (hours < 24) return `${hours} soat oldin`;
  const days = Math.floor(hours / 24);
  if (days === 1) return 'Kecha';
  return `${days} kun oldin`;
};

// Status
const statusMap = {
  pending: { label: 'Kutilmoqda', class: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' },
  confirmed: { label: 'Tasdiqlangan', class: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' },
  processing: { label: 'Tayyorlanmoqda', class: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' },
  shipped: { label: 'Yetkazilmoqda', class: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' },
  delivered: { label: 'Yetkazildi', class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' },
  cancelled: { label: 'Bekor qilingan', class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
  refunded: { label: 'Qaytarilgan', class: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' },
};

const getStatusLabel = (status) => statusMap[status]?.label || status;
const getStatusClass = (status) => statusMap[status]?.class || 'bg-slate-100 text-slate-600';
</script>
