<template>
  <Head title="Do'kon - Boshqaruv paneli" />
  <BusinessLayout title="Do'kon">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Do'kon boshqaruvi</h1>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Bugungi holat va statistikalar</p>
        </div>
        <Link
          :href="route('business.store.products.create')"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors"
        >
          <PlusIcon class="w-4 h-4" />
          Yangi mahsulot
        </Link>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Bugungi sotuvlar -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
              <BanknotesIcon class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
            </div>
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Bugungi sotuvlar</span>
          </div>
          <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatPrice(stats?.today_revenue || 0) }}</p>
          <p v-if="stats?.revenue_change" class="text-xs mt-1" :class="stats.revenue_change >= 0 ? 'text-emerald-600' : 'text-red-500'">
            {{ stats.revenue_change >= 0 ? '+' : '' }}{{ stats.revenue_change }}% kechagiga nisbatan
          </p>
        </div>

        <!-- Buyurtmalar soni -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
              <ShoppingCartIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
            </div>
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Buyurtmalar soni</span>
          </div>
          <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats?.today_orders || 0 }}</p>
          <p v-if="stats?.orders_change" class="text-xs mt-1" :class="stats.orders_change >= 0 ? 'text-emerald-600' : 'text-red-500'">
            {{ stats.orders_change >= 0 ? '+' : '' }}{{ stats.orders_change }}% kechagiga nisbatan
          </p>
        </div>

        <!-- O'rtacha check -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 bg-violet-100 dark:bg-violet-900/30 rounded-lg flex items-center justify-center">
              <CalculatorIcon class="w-5 h-5 text-violet-600 dark:text-violet-400" />
            </div>
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">O'rtacha check</span>
          </div>
          <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatPrice(stats?.avg_order || 0) }}</p>
        </div>

        <!-- Yangi mijozlar -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
              <UsersIcon class="w-5 h-5 text-amber-600 dark:text-amber-400" />
            </div>
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Yangi mijozlar</span>
          </div>
          <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats?.new_customers || 0 }}</p>
        </div>
      </div>

      <!-- Quick Navigation -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <Link
          :href="route('business.store.categories.index')"
          class="group bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-emerald-300 dark:hover:border-emerald-700 transition-colors text-center"
        >
          <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center mx-auto mb-2">
            <FolderIcon class="w-5 h-5 text-amber-600 dark:text-amber-400" />
          </div>
          <p class="text-sm font-semibold text-slate-900 dark:text-white">Kategoriyalar</p>
          <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 opacity-0 group-hover:opacity-100 transition-opacity">Ochish →</p>
        </Link>

        <Link
          :href="route('business.store.catalog.index')"
          class="group bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-emerald-300 dark:hover:border-emerald-700 transition-colors text-center"
        >
          <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center mx-auto mb-2">
            <CubeIcon class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
          </div>
          <p class="text-sm font-semibold text-slate-900 dark:text-white">Katalog</p>
          <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 opacity-0 group-hover:opacity-100 transition-opacity">Ochish →</p>
        </Link>

        <Link
          :href="route('business.store.orders.index')"
          class="group bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-emerald-300 dark:hover:border-emerald-700 transition-colors text-center"
        >
          <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mx-auto mb-2">
            <ShoppingCartIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
          </div>
          <p class="text-sm font-semibold text-slate-900 dark:text-white">Buyurtmalar</p>
          <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 opacity-0 group-hover:opacity-100 transition-opacity">Ochish →</p>
        </Link>

        <Link
          :href="route('business.store.customers.index')"
          class="group bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-emerald-300 dark:hover:border-emerald-700 transition-colors text-center"
        >
          <div class="w-10 h-10 bg-violet-100 dark:bg-violet-900/30 rounded-lg flex items-center justify-center mx-auto mb-2">
            <UsersIcon class="w-5 h-5 text-violet-600 dark:text-violet-400" />
          </div>
          <p class="text-sm font-semibold text-slate-900 dark:text-white">Mijozlar</p>
          <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 opacity-0 group-hover:opacity-100 transition-opacity">Ochish →</p>
        </Link>

        <Link
          :href="route('business.store.promo-codes.index')"
          class="group bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-emerald-300 dark:hover:border-emerald-700 transition-colors text-center"
        >
          <div class="w-10 h-10 bg-pink-100 dark:bg-pink-900/30 rounded-lg flex items-center justify-center mx-auto mb-2">
            <TagIcon class="w-5 h-5 text-pink-600 dark:text-pink-400" />
          </div>
          <p class="text-sm font-semibold text-slate-900 dark:text-white">Promo kodlar</p>
          <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 opacity-0 group-hover:opacity-100 transition-opacity">Ochish →</p>
        </Link>

        <Link
          :href="route('business.store.settings')"
          class="group bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-emerald-300 dark:hover:border-emerald-700 transition-colors text-center"
        >
          <div class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center mx-auto mb-2">
            <CogIcon class="w-5 h-5 text-slate-600 dark:text-slate-400" />
          </div>
          <p class="text-sm font-semibold text-slate-900 dark:text-white">Sozlamalar</p>
          <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 opacity-0 group-hover:opacity-100 transition-opacity">Ochish →</p>
        </Link>
      </div>

      <!-- Charts & Tables -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Revenue Chart (last 30 days) -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Oxirgi 30 kun daromadi</h3>
          </div>
          <div class="p-5">
            <div v-if="chartData && chartData.length > 0" class="space-y-2">
              <!-- Chart using div bars -->
              <div class="flex items-end gap-1 h-48">
                <div
                  v-for="(day, i) in chartData"
                  :key="i"
                  class="flex-1 group relative"
                >
                  <div
                    class="w-full bg-emerald-500 dark:bg-emerald-400 rounded-t-sm hover:bg-emerald-600 dark:hover:bg-emerald-300 transition-colors cursor-pointer"
                    :style="{ height: getBarHeight(day.revenue) + '%', minHeight: day.revenue > 0 ? '4px' : '1px' }"
                  ></div>
                  <!-- Tooltip -->
                  <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block z-10">
                    <div class="bg-slate-800 text-white text-xs rounded-lg px-3 py-2 whitespace-nowrap shadow-lg">
                      <p class="font-semibold">{{ day.date }}</p>
                      <p>{{ formatPrice(day.revenue) }}</p>
                      <p>{{ day.orders }} ta buyurtma</p>
                    </div>
                  </div>
                </div>
              </div>
              <!-- X-axis labels -->
              <div class="flex gap-1 text-xs text-slate-400 dark:text-slate-500">
                <div class="flex-1 text-left">{{ chartData[0]?.date }}</div>
                <div class="flex-1 text-center" v-if="chartData.length > 15">{{ chartData[Math.floor(chartData.length / 2)]?.date }}</div>
                <div class="flex-1 text-right">{{ chartData[chartData.length - 1]?.date }}</div>
              </div>
            </div>
            <div v-else class="flex flex-col items-center justify-center py-12 text-slate-400 dark:text-slate-500">
              <ChartBarIcon class="w-10 h-10 mb-2" />
              <p class="text-sm">Hozircha ma'lumot yo'q</p>
            </div>
          </div>
        </div>

        <!-- Top Products -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Top mahsulotlar</h3>
          </div>
          <div class="p-5">
            <div v-if="topProducts && topProducts.length > 0" class="space-y-4">
              <div
                v-for="(product, index) in topProducts"
                :key="product.id"
                class="flex items-center gap-3"
              >
                <span class="text-sm font-bold text-slate-400 dark:text-slate-500 w-6 text-center">{{ index + 1 }}</span>
                <div v-if="product.image" class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0 bg-slate-100 dark:bg-slate-700">
                  <img :src="product.image" :alt="product.name" class="w-full h-full object-cover" />
                </div>
                <div v-else class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0">
                  <CubeIcon class="w-5 h-5 text-slate-400" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ product.name }}</p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">{{ product.sold_count }} ta sotildi</p>
                </div>
                <span class="text-sm font-semibold text-slate-900 dark:text-white whitespace-nowrap">{{ formatPrice(product.revenue) }}</span>
              </div>
            </div>
            <div v-else class="text-center py-8 text-slate-400 dark:text-slate-500">
              <CubeIcon class="w-10 h-10 mx-auto mb-2" />
              <p class="text-sm">Hozircha sotuvlar yo'q</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Orders Table -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
          <h3 class="text-base font-semibold text-slate-900 dark:text-white">Oxirgi buyurtmalar</h3>
          <Link
            :href="route('business.store.orders.index')"
            class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 font-medium"
          >
            Barchasini ko'rish
          </Link>
        </div>
        <div v-if="recentOrders && recentOrders.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50">
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Buyurtma</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Mijoz</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Summa</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Holat</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Sana</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
              <tr
                v-for="order in recentOrders"
                :key="order.id"
                class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors cursor-pointer"
                @click="router.visit(route('business.store.orders.show', order.id))"
              >
                <td class="px-5 py-3 whitespace-nowrap">
                  <span class="text-sm font-semibold text-slate-900 dark:text-white">#{{ order.order_number }}</span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap">
                  <span class="text-sm text-slate-700 dark:text-slate-300">{{ order.customer_name }}</span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap">
                  <span class="text-sm font-medium text-slate-900 dark:text-white">{{ formatPrice(order.total) }}</span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getStatusClass(order.status)">
                    {{ getStatusLabel(order.status) }}
                  </span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap">
                  <span class="text-sm text-slate-500 dark:text-slate-400">{{ formatDate(order.created_at) }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="text-center py-12 text-slate-400 dark:text-slate-500">
          <ShoppingCartIcon class="w-10 h-10 mx-auto mb-2" />
          <p class="text-sm">Hozircha buyurtmalar yo'q</p>
        </div>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import {
  BanknotesIcon,
  ShoppingCartIcon,
  CalculatorIcon,
  UsersIcon,
  ChartBarIcon,
  CubeIcon,
  PlusIcon,
  FolderIcon,
  TagIcon,
  CogIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  stats: { type: Object, default: () => ({}) },
  recentOrders: { type: Array, default: () => [] },
  topProducts: { type: Array, default: () => [] },
  chartData: { type: Array, default: () => [] },
});

const formatPrice = (value) => {
  if (!value && value !== 0) return "0 so'm";
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short', year: 'numeric' });
};

const maxRevenue = computed(() => {
  if (!props.chartData || props.chartData.length === 0) return 1;
  return Math.max(...props.chartData.map(d => d.revenue || 0), 1);
});

const getBarHeight = (revenue) => {
  return Math.max((revenue / maxRevenue.value) * 100, 1);
};

const statusMap = {
  pending: { label: 'Kutilmoqda', class: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' },
  confirmed: { label: 'Tasdiqlangan', class: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' },
  preparing: { label: 'Tayyorlanmoqda', class: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' },
  shipping: { label: 'Yetkazilmoqda', class: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' },
  delivered: { label: 'Yetkazildi', class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' },
  cancelled: { label: 'Bekor qilingan', class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
};

const getStatusLabel = (status) => statusMap[status]?.label || status;
const getStatusClass = (status) => statusMap[status]?.class || 'bg-slate-100 text-slate-600';
</script>
