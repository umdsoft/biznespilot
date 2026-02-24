<template>
  <Head :title="customer.name" />
  <BusinessLayout :title="customer.name">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

      <!-- Header -->
      <div class="mb-6">
        <Link
          :href="route('business.store.customers.index')"
          class="inline-flex items-center text-sm text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 transition-colors mb-3"
        >
          <ArrowLeftIcon class="w-4 h-4 mr-2" />
          Mijozlarga qaytish
        </Link>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Sidebar: Profile Card -->
        <div class="space-y-6">
          <!-- Profile -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="p-6 text-center">
              <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4"
                :class="getAvatarColor(customer.name)"
              >
                <span class="text-2xl font-bold text-white">{{ getInitials(customer.name) }}</span>
              </div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ customer.name }}</h2>
              <p v-if="customer.telegram_username" class="text-sm text-blue-500 mt-1">@{{ customer.telegram_username }}</p>
            </div>

            <div class="px-6 pb-6 space-y-3">
              <div v-if="customer.phone" class="flex items-center gap-3 text-sm">
                <PhoneIcon class="w-4 h-4 text-slate-400 flex-shrink-0" />
                <span class="text-slate-700 dark:text-slate-300">{{ customer.phone }}</span>
              </div>
              <div v-if="customer.email" class="flex items-center gap-3 text-sm">
                <EnvelopeIcon class="w-4 h-4 text-slate-400 flex-shrink-0" />
                <span class="text-slate-700 dark:text-slate-300">{{ customer.email }}</span>
              </div>
              <div v-if="customer.address" class="flex items-start gap-3 text-sm">
                <MapPinIcon class="w-4 h-4 text-slate-400 flex-shrink-0 mt-0.5" />
                <span class="text-slate-700 dark:text-slate-300">{{ customer.address }}</span>
              </div>
              <div class="flex items-center gap-3 text-sm">
                <CalendarIcon class="w-4 h-4 text-slate-400 flex-shrink-0" />
                <span class="text-slate-700 dark:text-slate-300">
                  Ro'yxatdan o'tgan: {{ formatDate(customer.created_at) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Stats -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
              <h3 class="text-base font-semibold text-slate-900 dark:text-white">Statistika</h3>
            </div>
            <div class="p-5 space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-sm text-slate-500 dark:text-slate-400">Buyurtmalar soni</span>
                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ customer.orders_count || customer.orders?.length || 0 }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-slate-500 dark:text-slate-400">Jami xarid</span>
                <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ formatPrice(customer.total_spent || 0) }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-slate-500 dark:text-slate-400">O'rtacha buyurtma</span>
                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ formatPrice(customer.avg_order || 0) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Main Content: Orders History -->
        <div class="lg:col-span-2">
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
              <h3 class="text-base font-semibold text-slate-900 dark:text-white">Buyurtmalar tarixi</h3>
            </div>

            <div v-if="ordersList.length > 0" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                <thead>
                  <tr class="bg-slate-50 dark:bg-slate-700/50">
                    <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Buyurtma</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Summa</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Holat</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Sana</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                  <tr
                    v-for="order in ordersList"
                    :key="order.id"
                    class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors cursor-pointer"
                    @click="router.visit(route('business.store.orders.show', order.id))"
                  >
                    <td class="px-5 py-3 whitespace-nowrap">
                      <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">#{{ order.order_number }}</span>
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap">
                      <span class="text-sm font-medium text-slate-900 dark:text-white">{{ formatPrice(order.total) }}</span>
                      <span class="block text-xs text-slate-500 dark:text-slate-400">{{ order.items_count || 0 }} ta mahsulot</span>
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

            <div v-else class="text-center py-12">
              <ShoppingCartIcon class="w-12 h-12 mx-auto mb-3 text-slate-300 dark:text-slate-600" />
              <p class="text-sm text-slate-500 dark:text-slate-400">Bu mijozning buyurtmalari yo'q</p>
            </div>
          </div>
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
  ArrowLeftIcon,
  PhoneIcon,
  EnvelopeIcon,
  MapPinIcon,
  CalendarIcon,
  ShoppingCartIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  customer: { type: Object, required: true },
  orders: { type: Object, default: () => ({ data: [] }) },
  orderStats: { type: Object, default: () => ({}) },
});

// Orders prop paginated format (data array) yoki oddiy array bo'lishi mumkin
const ordersList = computed(() => {
  if (Array.isArray(props.orders)) return props.orders;
  if (props.orders?.data) return props.orders.data;
  return [];
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

const getInitials = (name) => {
  if (!name) return '?';
  return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
};

const avatarColors = [
  'bg-emerald-500',
  'bg-blue-500',
  'bg-purple-500',
  'bg-amber-500',
  'bg-rose-500',
  'bg-cyan-500',
  'bg-indigo-500',
  'bg-orange-500',
];

const getAvatarColor = (name) => {
  if (!name) return avatarColors[0];
  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  }
  return avatarColors[Math.abs(hash) % avatarColors.length];
};

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
