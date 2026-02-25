<template>
  <Head title="Buyurtmalar" />
  <component :is="layoutComponent" title="Buyurtmalar">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Buyurtmalar</h1>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Jami: {{ orders?.total || 0 }} ta buyurtma
          </p>
        </div>
        <div class="flex items-center gap-3">
          <!-- Search -->
          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input
              v-model="searchQuery"
              @input="debouncedSearch"
              type="text"
              placeholder="Buyurtma yoki mijoz..."
              class="pl-9 pr-4 py-2.5 w-56 text-sm bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 transition-colors"
            />
          </div>
          <button
            @click="exportOrders"
            :disabled="exporting"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium text-sm rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors disabled:opacity-50"
          >
            <ArrowDownTrayIcon class="w-4 h-4" />
            <span>{{ exporting ? 'Yuklanmoqda...' : 'Excel export' }}</span>
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div v-if="stats" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
              <ClockIcon class="w-5 h-5 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
              <p class="text-xs text-slate-500 dark:text-slate-400">Kutilmoqda</p>
              <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ stats.pending || 0 }}</p>
            </div>
          </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
              <CubeIcon class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
            </div>
            <div>
              <p class="text-xs text-slate-500 dark:text-slate-400">Tayyorlanmoqda</p>
              <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400">{{ stats.processing || 0 }}</p>
            </div>
          </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
              <TruckIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-xs text-slate-500 dark:text-slate-400">Yetkazilmoqda</p>
              <p class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ stats.shipped || 0 }}</p>
            </div>
          </div>
        </div>
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 shadow-md">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
              <BanknotesIcon class="w-5 h-5 text-white" />
            </div>
            <div>
              <p class="text-xs text-emerald-100">Bugungi daromad</p>
              <p class="text-xl font-bold text-white">{{ formatPrice(stats.today_revenue || 0) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Status Filter Tabs + Table -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
        <!-- Tabs -->
        <div class="border-b border-slate-200 dark:border-slate-700">
          <nav class="flex -mb-px flex-wrap px-2">
            <button
              v-for="tab in statusTabs"
              :key="tab.value"
              @click="filterByStatus(tab.value)"
              class="relative px-4 py-3.5 text-sm font-medium transition-colors whitespace-nowrap"
              :class="currentStatus === tab.value
                ? 'text-emerald-600 dark:text-emerald-400'
                : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
            >
              {{ tab.label }}
              <span
                v-if="tab.count !== undefined && tab.count > 0"
                class="ml-1.5 inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold rounded-full"
                :class="currentStatus === tab.value ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400'"
              >
                {{ tab.count }}
              </span>
              <!-- Active indicator -->
              <span
                v-if="currentStatus === tab.value"
                class="absolute bottom-0 left-2 right-2 h-0.5 bg-emerald-500 rounded-full"
              />
            </button>
          </nav>
        </div>

        <!-- Orders Table -->
        <div v-if="orders.data && orders.data.length > 0">
          <table class="min-w-full">
            <thead>
              <tr class="border-b border-slate-100 dark:border-slate-700">
                <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Buyurtma</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Mijoz</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Summa</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Holat</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sana</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Amal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
              <tr
                v-for="order in orders.data"
                :key="order.id"
                class="group hover:bg-slate-50/80 dark:hover:bg-slate-700/20 transition-colors"
              >
                <!-- Order Number -->
                <td class="px-5 py-4 whitespace-nowrap">
                  <Link
                    :href="storeRoute('orders.show', order.id)"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors"
                  >
                    <span class="w-8 h-8 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg flex items-center justify-center flex-shrink-0">
                      <ShoppingBagIcon class="w-4 h-4" />
                    </span>
                    #{{ order.order_number }}
                  </Link>
                </td>

                <!-- Customer -->
                <td class="px-5 py-4 whitespace-nowrap">
                  <div v-if="order.customer?.name" class="flex items-center gap-2.5">
                    <span class="w-8 h-8 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center text-xs font-bold text-slate-600 dark:text-slate-300 flex-shrink-0">
                      {{ getInitials(order.customer.name) }}
                    </span>
                    <div>
                      <p class="text-sm font-medium text-slate-900 dark:text-white">{{ order.customer.name }}</p>
                      <p v-if="order.customer.phone" class="text-xs text-slate-500 dark:text-slate-400">{{ order.customer.phone }}</p>
                    </div>
                  </div>
                  <span v-else class="text-sm text-slate-400 dark:text-slate-500">—</span>
                </td>

                <!-- Amount -->
                <td class="px-5 py-4 whitespace-nowrap">
                  <div>
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ formatPrice(order.total) }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ order.items_count }} ta mahsulot</p>
                  </div>
                </td>

                <!-- Status -->
                <td class="px-5 py-4 whitespace-nowrap">
                  <span
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                    :class="getStatusClass(order.status)"
                  >
                    <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(order.status)" />
                    {{ getStatusLabel(order.status) }}
                  </span>
                </td>

                <!-- Date -->
                <td class="px-5 py-4 whitespace-nowrap">
                  <div>
                    <p class="text-sm text-slate-700 dark:text-slate-300">{{ formatDateShort(order.created_at) }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ formatTime(order.created_at) }}</p>
                  </div>
                </td>

                <!-- Actions -->
                <td class="px-5 py-4 whitespace-nowrap text-right">
                  <div class="flex items-center justify-end gap-2">
                    <!-- Quick status update -->
                    <div v-if="order.status !== 'delivered' && order.status !== 'cancelled'" class="relative">
                      <select
                        @change="updateStatus(order, $event.target.value)"
                        :value="order.status"
                        class="text-xs rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 pl-2.5 pr-7 py-1.5 text-slate-700 dark:text-slate-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 cursor-pointer appearance-none"
                      >
                        <option v-for="s in availableStatuses(order.status)" :key="s.value" :value="s.value">
                          {{ s.label }}
                        </option>
                      </select>
                    </div>
                    <!-- View button -->
                    <Link
                      :href="storeRoute('orders.show', order.id)"
                      class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20 transition-colors"
                    >
                      <EyeIcon class="w-4 h-4" />
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty state -->
        <div v-else class="text-center py-20 px-4">
          <div class="w-16 h-16 mx-auto mb-4 bg-slate-100 dark:bg-slate-700 rounded-2xl flex items-center justify-center">
            <ShoppingCartIcon class="w-8 h-8 text-slate-400 dark:text-slate-500" />
          </div>
          <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1.5">Buyurtmalar yo'q</h3>
          <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
            {{ currentStatus ? "Bu holatda hech qanday buyurtma topilmadi" : "MiniApp orqali birinchi buyurtmani qabul qiling" }}
          </p>
        </div>
      </div>

      <!-- Pagination -->
      <Pagination
        v-if="orders.links && orders.links.length > 3"
        :links="orders.links"
        :from="orders.from"
        :to="orders.to"
        :total="orders.total"
      />
    </div>
  </component>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useStorePanel } from '@/composables/useStorePanel';
import Pagination from '@/components/Pagination.vue';
import {
  ArrowDownTrayIcon,
  ShoppingCartIcon,
  ShoppingBagIcon,
  MagnifyingGlassIcon,
  ClockIcon,
  CubeIcon,
  TruckIcon,
  BanknotesIcon,
  EyeIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  orders: { type: Object, default: () => ({ data: [], links: [] }) },
  filters: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
  panelType: { type: String, default: 'business' },
});

const { layoutComponent, storeRoute } = useStorePanel(props.panelType);

const currentStatus = ref(props.filters?.status || '');
const searchQuery = ref(props.filters?.search || '');
const exporting = ref(false);

let searchTimeout = null;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get(storeRoute('orders.index'), {
      status: currentStatus.value || undefined,
      search: searchQuery.value || undefined,
    }, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  }, 400);
};

const statusTabs = computed(() => [
  { label: 'Barchasi', value: '', count: undefined },
  { label: 'Kutilmoqda', value: 'pending', count: props.stats?.pending },
  { label: 'Tasdiqlangan', value: 'confirmed', count: props.stats?.confirmed },
  { label: 'Tayyorlanmoqda', value: 'processing', count: props.stats?.processing },
  { label: 'Yetkazilmoqda', value: 'shipped', count: props.stats?.shipped },
  { label: 'Yetkazildi', value: 'delivered', count: undefined },
  { label: 'Bekor qilingan', value: 'cancelled', count: undefined },
]);

const statusMap = {
  pending: { label: 'Kutilmoqda', class: 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400', dot: 'bg-amber-500' },
  confirmed: { label: 'Tasdiqlangan', class: 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400', dot: 'bg-blue-500' },
  processing: { label: 'Tayyorlanmoqda', class: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400', dot: 'bg-indigo-500' },
  shipped: { label: 'Yetkazilmoqda', class: 'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-400', dot: 'bg-purple-500' },
  delivered: { label: 'Yetkazildi', class: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400', dot: 'bg-emerald-500' },
  cancelled: { label: 'Bekor qilingan', class: 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400', dot: 'bg-red-500' },
  refunded: { label: 'Qaytarilgan', class: 'bg-orange-50 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400', dot: 'bg-orange-500' },
};

const getStatusLabel = (status) => statusMap[status]?.label || status;
const getStatusClass = (status) => statusMap[status]?.class || 'bg-slate-100 text-slate-600';
const getStatusDotClass = (status) => statusMap[status]?.dot || 'bg-slate-400';

const getInitials = (name) => {
  if (!name) return '?';
  return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
};

const formatPrice = (value) => {
  if (!value && value !== 0) return "0 so'm";
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

const formatDateShort = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short' });
};

const formatTime = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleTimeString('uz-UZ', { hour: '2-digit', minute: '2-digit' });
};

const filterByStatus = (status) => {
  currentStatus.value = status;
  router.get(storeRoute('orders.index'), {
    status: status || undefined,
    search: searchQuery.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};

const availableStatuses = (currentOrderStatus) => {
  const flow = {
    pending: [
      { value: 'pending', label: 'Kutilmoqda' },
      { value: 'confirmed', label: 'Tasdiqlash' },
      { value: 'cancelled', label: 'Bekor qilish' },
    ],
    confirmed: [
      { value: 'confirmed', label: 'Tasdiqlangan' },
      { value: 'processing', label: 'Tayyorlash' },
      { value: 'cancelled', label: 'Bekor qilish' },
    ],
    processing: [
      { value: 'processing', label: 'Tayyorlanmoqda' },
      { value: 'shipped', label: 'Yetkazishga berish' },
    ],
    shipped: [
      { value: 'shipped', label: 'Yetkazilmoqda' },
      { value: 'delivered', label: 'Yetkazildi' },
    ],
  };
  return flow[currentOrderStatus] || [];
};

const updateStatus = (order, newStatus) => {
  if (newStatus === order.status) return;
  router.post(storeRoute('orders.update-status', order.id), {
    status: newStatus,
  }, {
    preserveScroll: true,
    preserveState: true,
  });
};

const exportOrders = () => {
  exporting.value = true;
  window.location.href = storeRoute('orders.export', {
    status: currentStatus.value || undefined,
  });
  setTimeout(() => {
    exporting.value = false;
  }, 3000);
};
</script>
