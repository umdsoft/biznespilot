<template>
  <Head :title="pageTitle" />
  <component :is="layoutComponent" :title="pageTitle">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <div class="flex items-center gap-3">
            <div
              v-if="typeConfig"
              class="w-10 h-10 rounded-xl flex items-center justify-center"
              :style="{ backgroundColor: typeConfig.bgColor }"
            >
              <component :is="typeIconComponent" class="w-5 h-5" :style="{ color: typeConfig.color }" />
            </div>
            <div>
              <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ dashboardTitle }}</h1>
              <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ dashboardSubtitle }}</p>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <Link
            :href="storeRoute('orders.index')"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-medium rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
          >
            <ClipboardDocumentListIcon class="w-4 h-4" />
            {{ ordersLabel }}
          </Link>
          <Link
            v-if="isBusinessPanel"
            :href="storeRoute('catalog.create')"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-white font-medium rounded-lg transition-colors"
            :style="{ backgroundColor: accentColor }"
          >
            <PlusIcon class="w-4 h-4" />
            {{ addNewLabel }}
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

        <!-- Stats Cards — 4 ta KPI -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div
            v-for="(kpi, idx) in kpiCards"
            :key="idx"
            class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md transition-shadow"
          >
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="kpi.bgClass">
                <component :is="kpi.icon" class="w-5 h-5" :class="kpi.iconClass" />
              </div>
              <span class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ kpi.label }}</span>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ kpi.formattedValue }}</p>
            <p
              v-if="kpi.change !== undefined && kpi.change !== null && kpi.change !== 0"
              class="text-xs mt-1.5 flex items-center gap-1"
              :class="kpi.change >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'"
            >
              <ArrowTrendingUpIcon v-if="kpi.change >= 0" class="w-3.5 h-3.5" />
              <ArrowTrendingDownIcon v-else class="w-3.5 h-3.5" />
              {{ Math.abs(kpi.change) }}% {{ periodCompareLabel }}
            </p>
            <p v-if="kpi.suffix" class="text-xs mt-1 text-slate-400 dark:text-slate-500">{{ kpi.suffix }}</p>
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

      <!-- Revenue Chart + Store Health -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Revenue Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <div>
              <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ chartTitle }}</h3>
              <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ formatPrice(totalChartRevenue) }} jami</p>
            </div>
          </div>
          <div class="p-5">
            <div v-if="chartData && chartData.length > 0">
              <div class="relative" @mouseleave="hoveredIndex = -1">
                <svg :viewBox="`0 0 ${svgWidth} ${svgHeight + 30}`" class="w-full h-64 sm:h-72" preserveAspectRatio="none">
                  <line v-for="(line, i) in gridLines" :key="'grid-'+i"
                    :x1="chartPadding.left" :y1="line.y" :x2="svgWidth - chartPadding.right" :y2="line.y"
                    stroke="currentColor" class="text-slate-100 dark:text-slate-700" stroke-width="1" stroke-dasharray="4,4"
                  />
                  <text v-for="(line, i) in gridLines" :key="'label-'+i"
                    :x="chartPadding.left - 8" :y="line.y + 4"
                    text-anchor="end" class="fill-slate-400 dark:fill-slate-500" font-size="11"
                  >{{ line.label }}</text>

                  <defs>
                    <linearGradient id="areaGradient" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" :stop-color="accentColor" stop-opacity="0.3" />
                      <stop offset="100%" :stop-color="accentColor" stop-opacity="0.02" />
                    </linearGradient>
                  </defs>

                  <polygon :points="areaPoints" fill="url(#areaGradient)" />

                  <polyline
                    :points="linePoints"
                    fill="none"
                    :stroke="accentColor"
                    stroke-width="2.5"
                    stroke-linejoin="round"
                    stroke-linecap="round"
                  />

                  <circle v-for="(point, i) in chartPoints" :key="'dot-'+i"
                    :cx="point.x" :cy="point.y"
                    :r="hoveredIndex === i ? 5 : 0"
                    :fill="accentColor" stroke="white" stroke-width="2"
                    class="transition-all duration-150"
                  />

                  <line v-if="hoveredIndex >= 0 && chartPoints[hoveredIndex]"
                    :x1="chartPoints[hoveredIndex].x" :y1="chartPadding.top"
                    :x2="chartPoints[hoveredIndex].x" :y2="svgHeight - chartPadding.bottom"
                    :stroke="accentColor" stroke-width="1" stroke-dasharray="3,3" opacity="0.5"
                  />

                  <rect v-for="(point, i) in chartPoints" :key="'zone-'+i"
                    :x="point.x - (chartAreaWidth / chartData.length / 2)"
                    :y="chartPadding.top"
                    :width="chartAreaWidth / chartData.length"
                    :height="svgHeight - chartPadding.top - chartPadding.bottom"
                    fill="transparent"
                    @mouseenter="hoveredIndex = i"
                    class="cursor-pointer"
                  />

                  <text v-for="(label, i) in xAxisLabels" :key="'x-'+i"
                    :x="label.x" :y="svgHeight + 20"
                    text-anchor="middle" class="fill-slate-400 dark:fill-slate-500" font-size="11"
                  >{{ label.text }}</text>
                </svg>

                <div v-if="hoveredIndex >= 0 && chartData[hoveredIndex]"
                  class="absolute top-2 right-2 bg-slate-900 dark:bg-slate-700 text-white rounded-lg px-3.5 py-2.5 shadow-xl pointer-events-none z-10"
                >
                  <p class="text-xs text-slate-300 mb-1">{{ chartData[hoveredIndex].date }}</p>
                  <p class="text-sm font-bold">{{ formatPrice(chartData[hoveredIndex].revenue) }}</p>
                  <p class="text-xs text-slate-300 mt-0.5">{{ chartData[hoveredIndex].orders_count }} ta {{ orderUnitLabel }}</p>
                </div>
              </div>
            </div>
            <div v-else class="flex flex-col items-center justify-center py-16 text-slate-400 dark:text-slate-500">
              <ChartBarIcon class="w-12 h-12 mb-3" />
              <p class="text-sm font-medium">Hozircha ma'lumot yo'q</p>
              <p class="text-xs mt-1">{{ emptyChartMessage }}</p>
            </div>
          </div>
        </div>

        <!-- Store Health + Top Items -->
        <div class="space-y-6">
          <!-- Store Health -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
              <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ healthTitle }}</h3>
            </div>
            <div class="p-5 grid grid-cols-2 gap-4">
              <div v-for="(item, idx) in healthItems" :key="idx" class="text-center">
                <p class="text-2xl font-bold" :class="item.colorClass">
                  {{ item.value }}{{ item.suffix || '' }}
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ item.label }}</p>
              </div>
            </div>
          </div>

          <!-- Top Items -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
              <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ topItemsTitle }}</h3>
            </div>
            <div class="p-4">
              <div v-if="topProducts && topProducts.length > 0" class="space-y-3">
                <div
                  v-for="(product, index) in topProducts.slice(0, 5)"
                  :key="product.id"
                  class="flex items-center gap-3"
                >
                  <span
                    class="text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0"
                    :style="index < 3 ? { backgroundColor: typeConfig?.bgColor || '#D1FAE5', color: typeConfig?.color || '#059669' } : {}"
                    :class="index >= 3 ? 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400' : ''"
                  >{{ index + 1 }}</span>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ product.name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ product.total_quantity }} ta {{ soldUnitLabel }}</p>
                  </div>
                  <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 whitespace-nowrap">{{ formatCompactPrice(product.total_revenue) }}</span>
                </div>
              </div>
              <div v-else class="text-center py-8 text-slate-400 dark:text-slate-500">
                <CubeIcon class="w-8 h-8 mx-auto mb-2" />
                <p class="text-sm">{{ emptyTopItemsMessage }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pending + Recent Orders -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Pending Orders -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ pendingTitle }}</h3>
              <span v-if="pendingOrders.length > 0" class="px-2 py-0.5 text-xs font-bold bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full">
                {{ pendingOrders.length }}
              </span>
            </div>
            <Link
              :href="storeRoute('orders.index', { status: 'pending' })"
              class="text-sm font-medium"
              :style="{ color: accentColor }"
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
            <p class="text-sm font-medium">{{ emptyPendingMessage }}</p>
            <p class="text-xs mt-1">{{ emptyPendingSubMessage }}</p>
          </div>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ recentTitle }}</h3>
            <Link
              :href="storeRoute('orders.index')"
              class="text-sm font-medium"
              :style="{ color: accentColor }"
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
                  <span class="text-xs text-slate-400 dark:text-slate-500">{{ order.items_count }} ta {{ itemUnitLabel }}</span>
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
            <p class="text-sm">{{ emptyRecentMessage }}</p>
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
  ShoppingBagIcon,
  TruckIcon,
  CalendarDaysIcon,
  WrenchScrewdriverIcon,
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
  ClockIcon,
  XCircleIcon,
  QueueListIcon,
  StarIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  store: { type: Object, default: () => ({}) },
  storeTypeConfig: { type: Object, default: null },
  stats: { type: Object, default: () => ({}) },
  weekStats: { type: Object, default: () => ({}) },
  monthStats: { type: Object, default: () => ({}) },
  recentOrders: { type: Array, default: () => [] },
  pendingOrders: { type: Array, default: () => [] },
  topProducts: { type: Array, default: () => [] },
  chartData: { type: Array, default: () => [] },
  statusDistribution: { type: Object, default: () => ({}) },
  storeHealth: { type: Object, default: () => ({}) },
  botTypeKpis: { type: Object, default: () => ({}) },
  panelType: { type: String, default: 'business' },
});

const { layoutComponent, storeRoute, isBusinessPanel } = useStorePanel(props.panelType);

// Bot turi konfiguratsiyasi
const storeType = computed(() => props.store?.store_type || props.storeTypeConfig?.type || 'ecommerce');
const typeConfig = computed(() => props.storeTypeConfig);
const accentColor = computed(() => typeConfig.value?.color || '#10B981');

// Bot turiga mos icon komponent
const typeIconMap = {
  ShoppingBagIcon,
  TruckIcon,
  CalendarDaysIcon,
  WrenchScrewdriverIcon,
};
const typeIconComponent = computed(() => {
  const iconName = typeConfig.value?.icon;
  return iconName ? (typeIconMap[iconName] || ShoppingBagIcon) : ShoppingBagIcon;
});

// --- Bot turiga mos dinamik labellar ---
const typeLabels = computed(() => {
  const t = storeType.value;
  return {
    ecommerce: {
      pageTitle: "Do'kon - Boshqaruv paneli",
      dashboardTitle: "Do'kon boshqaruvi",
      dashboardSubtitle: 'Statistika va buyurtmalar',
      ordersLabel: 'Buyurtmalar',
      addNewLabel: 'Yangi mahsulot',
      orderUnit: 'buyurtma',
      itemUnit: 'mahsulot',
      soldUnit: 'sotildi',
      chartTitle: 'Oxirgi 30 kun daromadi',
      emptyChart: 'Buyurtmalar kelib tushganda grafik paydo bo\'ladi',
      healthTitle: "Do'kon holati",
      topItemsTitle: 'Top mahsulotlar',
      emptyTopItems: 'Hozircha sotuvlar yo\'q',
      pendingTitle: 'Yangi buyurtmalar',
      emptyPending: 'Kutilayotgan buyurtmalar yo\'q',
      emptyPendingSub: 'Barcha buyurtmalar ko\'rib chiqilgan',
      recentTitle: 'Oxirgi buyurtmalar',
      emptyRecent: 'Hozircha buyurtmalar yo\'q',
    },
    delivery: {
      pageTitle: 'Yetkazish - Boshqaruv paneli',
      dashboardTitle: 'Yetkazish boshqaruvi',
      dashboardSubtitle: 'Buyurtmalar va yetkazish statistikasi',
      ordersLabel: 'Buyurtmalar',
      addNewLabel: 'Yangi taom',
      orderUnit: 'buyurtma',
      itemUnit: 'taom',
      soldUnit: 'buyurtma qilindi',
      chartTitle: 'Oxirgi 30 kun buyurtmalari',
      emptyChart: 'Buyurtmalar kelib tushganda grafik paydo bo\'ladi',
      healthTitle: 'Yetkazish holati',
      topItemsTitle: 'Top taomlar',
      emptyTopItems: 'Hozircha buyurtmalar yo\'q',
      pendingTitle: 'Yangi buyurtmalar',
      emptyPending: 'Kutilayotgan buyurtmalar yo\'q',
      emptyPendingSub: 'Barcha buyurtmalar ko\'rib chiqilgan',
      recentTitle: 'Oxirgi buyurtmalar',
      emptyRecent: 'Hozircha buyurtmalar yo\'q',
    },
    queue: {
      pageTitle: 'Navbat - Boshqaruv paneli',
      dashboardTitle: 'Navbat boshqaruvi',
      dashboardSubtitle: 'Bronlar va navbat statistikasi',
      ordersLabel: 'Bronlar',
      addNewLabel: 'Yangi xizmat',
      orderUnit: 'bron',
      itemUnit: 'xizmat',
      soldUnit: 'bron qilindi',
      chartTitle: 'Oxirgi 30 kun bronlari',
      emptyChart: 'Bronlar kelib tushganda grafik paydo bo\'ladi',
      healthTitle: 'Navbat holati',
      topItemsTitle: 'Top xizmatlar',
      emptyTopItems: 'Hozircha bronlar yo\'q',
      pendingTitle: 'Yangi bronlar',
      emptyPending: 'Kutilayotgan bronlar yo\'q',
      emptyPendingSub: 'Barcha bronlar ko\'rib chiqilgan',
      recentTitle: 'Oxirgi bronlar',
      emptyRecent: 'Hozircha bronlar yo\'q',
    },
    service: {
      pageTitle: 'Servis - Boshqaruv paneli',
      dashboardTitle: 'Servis boshqaruvi',
      dashboardSubtitle: 'Arizalar va xizmat statistikasi',
      ordersLabel: 'Arizalar',
      addNewLabel: 'Yangi xizmat turi',
      orderUnit: 'ariza',
      itemUnit: 'xizmat',
      soldUnit: 'so\'raldi',
      chartTitle: 'Oxirgi 30 kun arizalari',
      emptyChart: 'Arizalar kelib tushganda grafik paydo bo\'ladi',
      healthTitle: 'Servis holati',
      topItemsTitle: 'Top xizmatlar',
      emptyTopItems: 'Hozircha arizalar yo\'q',
      pendingTitle: 'Yangi arizalar',
      emptyPending: 'Kutilayotgan arizalar yo\'q',
      emptyPendingSub: 'Barcha arizalar ko\'rib chiqilgan',
      recentTitle: 'Oxirgi arizalar',
      emptyRecent: 'Hozircha arizalar yo\'q',
    },
  }[t] || {};
});

// Computed labellar
const pageTitle = computed(() => typeLabels.value.pageTitle || "Boshqaruv paneli");
const dashboardTitle = computed(() => typeLabels.value.dashboardTitle || "Boshqaruvi");
const dashboardSubtitle = computed(() => typeLabels.value.dashboardSubtitle || "Statistika");
const ordersLabel = computed(() => typeLabels.value.ordersLabel || "Buyurtmalar");
const addNewLabel = computed(() => typeLabels.value.addNewLabel || "Yangi qo'shish");
const orderUnitLabel = computed(() => typeLabels.value.orderUnit || 'buyurtma');
const itemUnitLabel = computed(() => typeLabels.value.itemUnit || 'mahsulot');
const soldUnitLabel = computed(() => typeLabels.value.soldUnit || 'sotildi');
const chartTitle = computed(() => typeLabels.value.chartTitle || 'Oxirgi 30 kun');
const emptyChartMessage = computed(() => typeLabels.value.emptyChart || "Ma'lumot yo'q");
const healthTitle = computed(() => typeLabels.value.healthTitle || 'Holat');
const topItemsTitle = computed(() => typeLabels.value.topItemsTitle || 'Top');
const emptyTopItemsMessage = computed(() => typeLabels.value.emptyTopItems || "Ma'lumot yo'q");
const pendingTitle = computed(() => typeLabels.value.pendingTitle || "Yangi");
const emptyPendingMessage = computed(() => typeLabels.value.emptyPending || "Yo'q");
const emptyPendingSubMessage = computed(() => typeLabels.value.emptyPendingSub || "");
const recentTitle = computed(() => typeLabels.value.recentTitle || "Oxirgi");
const emptyRecentMessage = computed(() => typeLabels.value.emptyRecent || "Yo'q");

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

// --- KPI kartochkalar: bot turiga mos 4 ta KPI ---
const kpiCards = computed(() => {
  const s = currentStats.value;
  const extra = props.botTypeKpis || {};
  const t = storeType.value;

  // Doimiy 1-2 KPIlar: Daromad va Buyurtmalar
  const kpi1 = {
    label: 'Daromad',
    icon: BanknotesIcon,
    bgClass: 'bg-emerald-100 dark:bg-emerald-900/30',
    iconClass: 'text-emerald-600 dark:text-emerald-400',
    formattedValue: formatPrice(s.revenue || 0),
    change: s.revenue_change,
  };

  const ordersKpiLabel = t === 'queue' ? 'Bronlar'
    : t === 'service' ? 'Arizalar'
    : 'Buyurtmalar';

  const kpi2 = {
    label: ordersKpiLabel,
    icon: t === 'queue' ? CalendarDaysIcon : t === 'service' ? ClipboardDocumentListIcon : ShoppingCartIcon,
    bgClass: 'bg-blue-100 dark:bg-blue-900/30',
    iconClass: 'text-blue-600 dark:text-blue-400',
    formattedValue: String(s.orders_count || 0),
    change: s.orders_change,
  };

  // Bot turiga mos 3-4 KPIlar
  let kpi3, kpi4;

  if (t === 'delivery') {
    kpi3 = {
      label: extra.extra_kpi_1?.label || "O'rt. yetkazish",
      icon: ClockIcon,
      bgClass: 'bg-orange-100 dark:bg-orange-900/30',
      iconClass: 'text-orange-600 dark:text-orange-400',
      formattedValue: `${extra.extra_kpi_1?.value || 0} daq`,
    };
    kpi4 = {
      label: extra.extra_kpi_2?.label || 'Bekor qilingan',
      icon: XCircleIcon,
      bgClass: 'bg-red-100 dark:bg-red-900/30',
      iconClass: 'text-red-600 dark:text-red-400',
      formattedValue: `${extra.extra_kpi_2?.value || 0}%`,
    };
  } else if (t === 'queue') {
    kpi3 = {
      label: extra.extra_kpi_1?.label || 'Navbatdagilar',
      icon: QueueListIcon,
      bgClass: 'bg-purple-100 dark:bg-purple-900/30',
      iconClass: 'text-purple-600 dark:text-purple-400',
      formattedValue: String(extra.extra_kpi_1?.value || 0),
    };
    kpi4 = {
      label: extra.extra_kpi_2?.label || 'Bajarilganlik',
      icon: CheckCircleIcon,
      bgClass: 'bg-emerald-100 dark:bg-emerald-900/30',
      iconClass: 'text-emerald-600 dark:text-emerald-400',
      formattedValue: `${extra.extra_kpi_2?.value || 0}%`,
    };
  } else if (t === 'service') {
    kpi3 = {
      label: extra.extra_kpi_1?.label || 'Faol arizalar',
      icon: ClipboardDocumentListIcon,
      bgClass: 'bg-sky-100 dark:bg-sky-900/30',
      iconClass: 'text-sky-600 dark:text-sky-400',
      formattedValue: String(extra.extra_kpi_1?.value || 0),
    };
    kpi4 = {
      label: extra.extra_kpi_2?.label || "O'rt. baho",
      icon: StarIcon,
      bgClass: 'bg-amber-100 dark:bg-amber-900/30',
      iconClass: 'text-amber-600 dark:text-amber-400',
      formattedValue: extra.extra_kpi_2?.value ? `${extra.extra_kpi_2.value}` : '0',
      suffix: extra.extra_kpi_2?.value ? '/ 5' : '',
    };
  } else {
    // Ecommerce (default)
    kpi3 = {
      label: "O'rtacha check",
      icon: ReceiptPercentIcon,
      bgClass: 'bg-violet-100 dark:bg-violet-900/30',
      iconClass: 'text-violet-600 dark:text-violet-400',
      formattedValue: formatPrice(s.avg_order_value || 0),
    };
    kpi4 = {
      label: 'Yangi mijozlar',
      icon: UserPlusIcon,
      bgClass: 'bg-amber-100 dark:bg-amber-900/30',
      iconClass: 'text-amber-600 dark:text-amber-400',
      formattedValue: String(s.new_customers || 0),
    };
  }

  return [kpi1, kpi2, kpi3, kpi4];
});

// --- Quick nav links: bot turiga mos ---
const quickLinksConfig = {
  ecommerce: [
    { suffix: 'categories.index', label: 'Kategoriyalar', icon: FolderIcon, bgColor: 'bg-amber-100 dark:bg-amber-900/30', iconColor: 'text-amber-600 dark:text-amber-400', businessOnly: true },
    { suffix: 'catalog.index', label: 'Katalog', icon: CubeIcon, bgColor: 'bg-emerald-100 dark:bg-emerald-900/30', iconColor: 'text-emerald-600 dark:text-emerald-400' },
    { suffix: 'orders.index', label: 'Buyurtmalar', icon: ShoppingCartIcon, bgColor: 'bg-blue-100 dark:bg-blue-900/30', iconColor: 'text-blue-600 dark:text-blue-400' },
    { suffix: 'customers.index', label: 'Mijozlar', icon: UsersIcon, bgColor: 'bg-violet-100 dark:bg-violet-900/30', iconColor: 'text-violet-600 dark:text-violet-400' },
    { suffix: 'promo-codes.index', label: 'Promo kodlar', icon: TagIcon, bgColor: 'bg-pink-100 dark:bg-pink-900/30', iconColor: 'text-pink-600 dark:text-pink-400', businessOnly: true },
    { suffix: 'settings', label: 'Sozlamalar', icon: CogIcon, bgColor: 'bg-slate-100 dark:bg-slate-700', iconColor: 'text-slate-600 dark:text-slate-400', businessOnly: true },
  ],
  delivery: [
    { suffix: 'catalog.index', label: 'Menyu', icon: ClipboardDocumentListIcon, bgColor: 'bg-orange-100 dark:bg-orange-900/30', iconColor: 'text-orange-600 dark:text-orange-400' },
    { suffix: 'orders.index', label: 'Buyurtmalar', icon: ShoppingCartIcon, bgColor: 'bg-blue-100 dark:bg-blue-900/30', iconColor: 'text-blue-600 dark:text-blue-400' },
    { suffix: 'categories.index', label: 'Kategoriyalar', icon: FolderIcon, bgColor: 'bg-amber-100 dark:bg-amber-900/30', iconColor: 'text-amber-600 dark:text-amber-400', businessOnly: true },
    { suffix: 'customers.index', label: 'Mijozlar', icon: UsersIcon, bgColor: 'bg-violet-100 dark:bg-violet-900/30', iconColor: 'text-violet-600 dark:text-violet-400' },
    { suffix: 'settings', label: 'Yetkazish zonalari', icon: CogIcon, bgColor: 'bg-slate-100 dark:bg-slate-700', iconColor: 'text-slate-600 dark:text-slate-400', businessOnly: true },
  ],
  queue: [
    { suffix: 'catalog.index', label: 'Xizmatlar', icon: CubeIcon, bgColor: 'bg-purple-100 dark:bg-purple-900/30', iconColor: 'text-purple-600 dark:text-purple-400' },
    { suffix: 'orders.index', label: 'Bronlar', icon: CalendarDaysIcon, bgColor: 'bg-blue-100 dark:bg-blue-900/30', iconColor: 'text-blue-600 dark:text-blue-400' },
    { suffix: 'categories.index', label: 'Filiallar', icon: FolderIcon, bgColor: 'bg-amber-100 dark:bg-amber-900/30', iconColor: 'text-amber-600 dark:text-amber-400', businessOnly: true },
    { suffix: 'customers.index', label: 'Mutaxassislar', icon: UserGroupIcon, bgColor: 'bg-violet-100 dark:bg-violet-900/30', iconColor: 'text-violet-600 dark:text-violet-400' },
    { suffix: 'settings', label: 'Sozlamalar', icon: CogIcon, bgColor: 'bg-slate-100 dark:bg-slate-700', iconColor: 'text-slate-600 dark:text-slate-400', businessOnly: true },
  ],
  service: [
    { suffix: 'categories.index', label: 'Kategoriyalar', icon: FolderIcon, bgColor: 'bg-sky-100 dark:bg-sky-900/30', iconColor: 'text-sky-600 dark:text-sky-400', businessOnly: true },
    { suffix: 'orders.index', label: 'Arizalar', icon: ClipboardDocumentListIcon, bgColor: 'bg-blue-100 dark:bg-blue-900/30', iconColor: 'text-blue-600 dark:text-blue-400' },
    { suffix: 'customers.index', label: 'Ustalar', icon: UserGroupIcon, bgColor: 'bg-violet-100 dark:bg-violet-900/30', iconColor: 'text-violet-600 dark:text-violet-400' },
    { suffix: 'catalog.index', label: 'Xizmat turlari', icon: CubeIcon, bgColor: 'bg-emerald-100 dark:bg-emerald-900/30', iconColor: 'text-emerald-600 dark:text-emerald-400' },
    { suffix: 'settings', label: 'Sozlamalar', icon: CogIcon, bgColor: 'bg-slate-100 dark:bg-slate-700', iconColor: 'text-slate-600 dark:text-slate-400', businessOnly: true },
  ],
};

const quickLinks = computed(() => {
  const links = quickLinksConfig[storeType.value] || quickLinksConfig.ecommerce;
  return links.filter(l => !l.businessOnly || isBusinessPanel);
});

// --- Store Health: bot turiga mos ---
const healthItems = computed(() => {
  const h = props.storeHealth || {};
  const extra = props.botTypeKpis || {};
  const t = storeType.value;

  if (t === 'delivery') {
    return [
      { label: 'Menyu itemlar', value: h.total_products || 0, colorClass: 'text-slate-900 dark:text-white' },
      { label: 'Mijozlar', value: h.total_customers || 0, colorClass: 'text-slate-900 dark:text-white' },
      { label: 'Kutilmoqda', value: h.pending_orders || 0, colorClass: h.pending_orders > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-slate-900 dark:text-white' },
      { label: 'Faol itemlar', value: h.active_products || 0, colorClass: 'text-emerald-600 dark:text-emerald-400' },
    ];
  }
  if (t === 'queue') {
    return [
      { label: 'Xizmatlar', value: h.total_products || 0, colorClass: 'text-slate-900 dark:text-white' },
      { label: 'Mijozlar', value: h.total_customers || 0, colorClass: 'text-slate-900 dark:text-white' },
      { label: 'Navbatda', value: extra.extra_kpi_1?.value || 0, colorClass: 'text-purple-600 dark:text-purple-400' },
      { label: "O'rt. kutish", value: extra.avg_wait || 0, suffix: ' daq', colorClass: 'text-slate-900 dark:text-white' },
    ];
  }
  if (t === 'service') {
    return [
      { label: 'Xizmat turlari', value: h.total_products || 0, colorClass: 'text-slate-900 dark:text-white' },
      { label: 'Mijozlar', value: h.total_customers || 0, colorClass: 'text-slate-900 dark:text-white' },
      { label: 'Faol arizalar', value: extra.extra_kpi_1?.value || 0, colorClass: 'text-sky-600 dark:text-sky-400' },
      { label: "O'rt. baho", value: extra.extra_kpi_2?.value || 0, suffix: ' / 5', colorClass: 'text-amber-600 dark:text-amber-400' },
    ];
  }
  // Ecommerce default
  return [
    { label: 'Mahsulotlar', value: h.total_products || 0, colorClass: 'text-slate-900 dark:text-white' },
    { label: 'Mijozlar', value: h.total_customers || 0, colorClass: 'text-slate-900 dark:text-white' },
    { label: 'Kutilmoqda', value: h.pending_orders || 0, colorClass: h.pending_orders > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-slate-900 dark:text-white' },
    { label: 'Faol mahsulot', value: h.active_products || 0, colorClass: 'text-emerald-600 dark:text-emerald-400' },
  ];
});

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
  if (!value) return '0';
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
