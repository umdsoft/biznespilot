<template>
  <Head title="Buyurtmalar" />
  <BusinessLayout title="Buyurtmalar">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Buyurtmalar</h1>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Jami: {{ orders?.total || 0 }} ta buyurtma
          </p>
        </div>
        <button
          @click="exportOrders"
          :disabled="exporting"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors disabled:opacity-50"
        >
          <ArrowDownTrayIcon class="w-4 h-4" />
          <span>{{ exporting ? 'Yuklanmoqda...' : 'Export' }}</span>
        </button>
      </div>

      <!-- Stats Mini Cards -->
      <div v-if="stats" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 px-4 py-3">
          <p class="text-xs text-slate-500 dark:text-slate-400">Kutilmoqda</p>
          <p class="text-lg font-bold text-yellow-600 dark:text-yellow-400">{{ stats.pending || 0 }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 px-4 py-3">
          <p class="text-xs text-slate-500 dark:text-slate-400">Tayyorlanmoqda</p>
          <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ stats.preparing || 0 }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 px-4 py-3">
          <p class="text-xs text-slate-500 dark:text-slate-400">Yetkazilmoqda</p>
          <p class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ stats.shipping || 0 }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 px-4 py-3">
          <p class="text-xs text-slate-500 dark:text-slate-400">Bugungi daromad</p>
          <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ formatPrice(stats.today_revenue || 0) }}</p>
        </div>
      </div>

      <!-- Status Filter Tabs -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="border-b border-slate-200 dark:border-slate-700 overflow-x-auto">
          <nav class="flex -mb-px min-w-max">
            <button
              v-for="tab in statusTabs"
              :key="tab.value"
              @click="filterByStatus(tab.value)"
              class="px-5 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
              :class="currentStatus === tab.value
                ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400'
                : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300'"
            >
              {{ tab.label }}
              <span
                v-if="tab.count !== undefined && tab.count > 0"
                class="ml-1.5 inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold rounded-full"
                :class="currentStatus === tab.value ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400'"
              >
                {{ tab.count }}
              </span>
            </button>
          </nav>
        </div>

        <!-- Orders Table -->
        <div v-if="orders.data && orders.data.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50">
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Buyurtma</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Mijoz</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Summa</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Holat</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Sana</th>
                <th class="px-5 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Amal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
              <tr
                v-for="order in orders.data"
                :key="order.id"
                class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
              >
                <td class="px-5 py-3 whitespace-nowrap">
                  <Link
                    :href="route('business.store.orders.show', order.id)"
                    class="text-sm font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                  >
                    #{{ order.order_number }}
                  </Link>
                </td>
                <td class="px-5 py-3 whitespace-nowrap">
                  <div>
                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ order.customer_name }}</p>
                    <p v-if="order.customer_phone" class="text-xs text-slate-500 dark:text-slate-400">{{ order.customer_phone }}</p>
                  </div>
                </td>
                <td class="px-5 py-3 whitespace-nowrap">
                  <span class="text-sm font-medium text-slate-900 dark:text-white">{{ formatPrice(order.total) }}</span>
                  <span class="block text-xs text-slate-500 dark:text-slate-400">{{ order.items_count }} ta mahsulot</span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getStatusClass(order.status)">
                    {{ getStatusLabel(order.status) }}
                  </span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap">
                  <span class="text-sm text-slate-500 dark:text-slate-400">{{ formatDate(order.created_at) }}</span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap text-right">
                  <!-- Quick status update -->
                  <div class="relative inline-block" v-if="order.status !== 'delivered' && order.status !== 'cancelled'">
                    <select
                      @change="updateStatus(order, $event.target.value)"
                      :value="order.status"
                      class="text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 pl-2 pr-7 py-1.5 text-slate-700 dark:text-slate-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 cursor-pointer"
                    >
                      <option v-for="s in availableStatuses(order.status)" :key="s.value" :value="s.value">
                        {{ s.label }}
                      </option>
                    </select>
                  </div>
                  <span v-else class="text-xs text-slate-400">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty state -->
        <div v-else class="text-center py-16">
          <ShoppingCartIcon class="w-16 h-16 mx-auto mb-4 text-slate-300 dark:text-slate-600" />
          <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Buyurtmalar yo'q</h3>
          <p class="text-sm text-slate-500 dark:text-slate-400">
            {{ currentStatus ? "Bu holatda buyurtmalar yo'q" : "Hozircha buyurtmalar yo'q" }}
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
  </BusinessLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import Pagination from '@/components/Pagination.vue';
import {
  ArrowDownTrayIcon,
  ShoppingCartIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  orders: { type: Object, default: () => ({ data: [], links: [] }) },
  filters: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
});

const currentStatus = ref(props.filters?.status || '');
const exporting = ref(false);

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

const formatPrice = (value) => {
  if (!value && value !== 0) return "0 so'm";
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
};

const filterByStatus = (status) => {
  currentStatus.value = status;
  router.get(route('business.store.orders.index'), {
    status: status || undefined,
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
  router.post(route('business.store.orders.update-status', order.id), {
    status: newStatus,
  }, {
    preserveScroll: true,
    preserveState: true,
  });
};

const exportOrders = () => {
  exporting.value = true;
  window.location.href = route('business.store.orders.export', {
    status: currentStatus.value || undefined,
  });
  setTimeout(() => {
    exporting.value = false;
  }, 3000);
};
</script>
