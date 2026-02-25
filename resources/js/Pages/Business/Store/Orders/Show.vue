<template>
  <Head :title="'Buyurtma #' + order.order_number" />
  <component :is="layoutComponent" :title="'Buyurtma #' + order.order_number">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

      <!-- Header -->
      <div class="mb-6">
        <Link
          :href="storeRoute('orders.index')"
          class="inline-flex items-center text-sm text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 transition-colors mb-3"
        >
          <ArrowLeftIcon class="w-4 h-4 mr-2" />
          Buyurtmalarga qaytish
        </Link>
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
              Buyurtma #{{ order.order_number }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
              {{ formatDateTime(order.created_at) }}
            </p>
          </div>
          <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium" :class="getStatusClass(order.status)">
            {{ getStatusLabel(order.status) }}
          </span>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">

          <!-- Order Items -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
              <h3 class="text-base font-semibold text-slate-900 dark:text-white">Mahsulotlar</h3>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                <thead>
                  <tr class="bg-slate-50 dark:bg-slate-700/50">
                    <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Mahsulot</th>
                    <th class="px-5 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Miqdor</th>
                    <th class="px-5 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Narx</th>
                    <th class="px-5 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Jami</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                  <tr v-for="item in order.items" :key="item.id">
                    <td class="px-5 py-3">
                      <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0 bg-slate-100 dark:bg-slate-700">
                          <img v-if="item.product_image" :src="item.product_image" :alt="item.product_name" class="w-full h-full object-cover" />
                          <div v-else class="w-full h-full flex items-center justify-center">
                            <CubeIcon class="w-5 h-5 text-slate-400" />
                          </div>
                        </div>
                        <div class="min-w-0">
                          <p class="text-sm font-medium text-slate-900 dark:text-white">{{ item.product_name }}</p>
                          <p v-if="item.variant_name" class="text-xs text-slate-500 dark:text-slate-400">{{ item.variant_name }}</p>
                        </div>
                      </div>
                    </td>
                    <td class="px-5 py-3 text-center">
                      <span class="text-sm text-slate-700 dark:text-slate-300">{{ item.quantity }}</span>
                    </td>
                    <td class="px-5 py-3 text-right">
                      <span class="text-sm text-slate-700 dark:text-slate-300">{{ formatPrice(item.price) }}</span>
                    </td>
                    <td class="px-5 py-3 text-right">
                      <span class="text-sm font-medium text-slate-900 dark:text-white">{{ formatPrice(item.quantity * item.price) }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Totals -->
            <div class="px-5 py-4 bg-slate-50 dark:bg-slate-700/30 border-t border-slate-100 dark:border-slate-700">
              <div class="space-y-2 max-w-xs ml-auto">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-slate-500 dark:text-slate-400">Oraliq summa:</span>
                  <span class="text-slate-700 dark:text-slate-300">{{ formatPrice(order.subtotal) }}</span>
                </div>
                <div v-if="order.delivery_fee" class="flex items-center justify-between text-sm">
                  <span class="text-slate-500 dark:text-slate-400">Yetkazish:</span>
                  <span class="text-slate-700 dark:text-slate-300">{{ formatPrice(order.delivery_fee) }}</span>
                </div>
                <div v-if="order.discount_amount" class="flex items-center justify-between text-sm">
                  <span class="text-slate-500 dark:text-slate-400 flex items-center gap-2">
                    Chegirma
                    <span v-if="order.promo_code" class="font-mono text-xs bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700 px-1.5 py-0.5 rounded text-emerald-700 dark:text-emerald-400 tracking-wide">
                      {{ order.promo_code }}
                    </span>
                  </span>
                  <span class="text-emerald-600 dark:text-emerald-400 font-medium">-{{ formatPrice(order.discount_amount) }}</span>
                </div>
                <div class="flex items-center justify-between text-base font-bold pt-2 border-t border-slate-200 dark:border-slate-600">
                  <span class="text-slate-900 dark:text-white">Jami:</span>
                  <span class="text-slate-900 dark:text-white">{{ formatPrice(order.total) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Status Timeline -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
              <h3 class="text-base font-semibold text-slate-900 dark:text-white">Holat tarixi</h3>
            </div>
            <div class="p-5">
              <div v-if="order.status_history && order.status_history.length > 0" class="relative">
                <!-- Timeline line -->
                <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-slate-200 dark:bg-slate-700"></div>

                <div
                  v-for="(entry, index) in order.status_history"
                  :key="index"
                  class="relative flex items-start gap-4 pb-6 last:pb-0"
                >
                  <!-- Dot -->
                  <div class="relative z-10 flex items-center justify-center w-8 h-8 rounded-full flex-shrink-0"
                    :class="index === 0 ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-slate-100 dark:bg-slate-700'"
                  >
                    <div class="w-3 h-3 rounded-full" :class="index === 0 ? 'bg-emerald-500' : 'bg-slate-400 dark:bg-slate-500'"></div>
                  </div>

                  <div class="flex-1 min-w-0 pt-1">
                    <p class="text-sm font-medium text-slate-900 dark:text-white">
                      {{ getStatusLabel(entry.to_status) }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                      {{ formatDateTime(entry.created_at) }}
                      <span v-if="entry.changed_by"> &middot; {{ entry.changed_by }}</span>
                      <span v-if="entry.comment"> &middot; {{ entry.comment }}</span>
                    </p>
                  </div>
                </div>
              </div>
              <p v-else class="text-sm text-slate-400 dark:text-slate-500 text-center py-4">Tarix yo'q</p>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

          <!-- Customer Info -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
              <h3 class="text-base font-semibold text-slate-900 dark:text-white">Mijoz ma'lumotlari</h3>
            </div>
            <div class="p-5 space-y-3">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                  <UserIcon class="w-5 h-5 text-slate-400" />
                </div>
                <div>
                  <p class="text-sm font-medium text-slate-900 dark:text-white">{{ order.customer?.name || order.customer_name }}</p>
                  <p v-if="order.customer?.telegram_username" class="text-xs text-blue-500">@{{ order.customer.telegram_username }}</p>
                </div>
              </div>

              <div v-if="order.customer?.phone || order.customer_phone" class="flex items-center gap-3 text-sm">
                <PhoneIcon class="w-4 h-4 text-slate-400 flex-shrink-0" />
                <span class="text-slate-700 dark:text-slate-300">{{ order.customer?.phone || order.customer_phone }}</span>
              </div>

              <div v-if="order.delivery_address" class="flex items-start gap-3 text-sm">
                <MapPinIcon class="w-4 h-4 text-slate-400 flex-shrink-0 mt-0.5" />
                <span class="text-slate-700 dark:text-slate-300">{{ formattedAddress }}</span>
              </div>

              <div v-if="order.customer?.id">
                <Link
                  :href="storeRoute('customers.show', order.customer.id)"
                  class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 font-medium"
                >
                  Mijoz profilini ko'rish
                </Link>
              </div>
            </div>
          </div>

          <!-- Payment Info -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
              <h3 class="text-base font-semibold text-slate-900 dark:text-white">To'lov</h3>
            </div>
            <div class="p-5 space-y-3">
              <div class="flex items-center justify-between text-sm">
                <span class="text-slate-500 dark:text-slate-400">Usul:</span>
                <span class="font-medium text-slate-900 dark:text-white">{{ getPaymentLabel(order.payment_method) }}</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-slate-500 dark:text-slate-400">Holat:</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                  :class="order.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'"
                >
                  {{ order.payment_status === 'paid' ? "To'langan" : "Kutilmoqda" }}
                </span>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div v-if="!['delivered', 'cancelled', 'refunded'].includes(order.status)" class="space-y-3">
            <button
              v-if="order.status === 'pending'"
              @click="changeStatus('confirmed')"
              :disabled="updating"
              class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
            >
              <CheckIcon class="w-4 h-4" />
              Tasdiqlash
            </button>
            <button
              v-if="order.status === 'confirmed'"
              @click="changeStatus('processing')"
              :disabled="updating"
              class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
            >
              <ClipboardDocumentListIcon class="w-4 h-4" />
              Tayyorlashni boshlash
            </button>
            <button
              v-if="order.status === 'processing'"
              @click="changeStatus('shipped')"
              :disabled="updating"
              class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
            >
              <TruckIcon class="w-4 h-4" />
              Yetkazishga berish
            </button>
            <button
              v-if="order.status === 'shipped'"
              @click="changeStatus('delivered')"
              :disabled="updating"
              class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
            >
              <CheckCircleIcon class="w-4 h-4" />
              Yetkazildi
            </button>
            <button
              v-if="['pending', 'confirmed', 'processing'].includes(order.status)"
              @click="changeStatus('cancelled')"
              :disabled="updating"
              class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 font-medium rounded-lg hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors disabled:opacity-50"
            >
              <XCircleIcon class="w-4 h-4" />
              Bekor qilish
            </button>
          </div>

          <!-- Notes -->
          <div v-if="order.notes" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
              <h3 class="text-base font-semibold text-slate-900 dark:text-white">Izoh</h3>
            </div>
            <div class="p-5">
              <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ order.notes }}</p>
            </div>
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
  ArrowLeftIcon,
  UserIcon,
  PhoneIcon,
  MapPinIcon,
  CubeIcon,
  CheckIcon,
  CheckCircleIcon,
  XCircleIcon,
  ClipboardDocumentListIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  order: { type: Object, required: true },
  panelType: { type: String, default: 'business' },
});

const { layoutComponent, storeRoute } = useStorePanel(props.panelType);

const updating = ref(false);

const formattedAddress = computed(() => {
  const addr = props.order.delivery_address;
  if (!addr) return '';
  if (typeof addr === 'string') return addr;
  const parts = [];
  if (addr.city) parts.push(addr.city);
  if (addr.district) parts.push(addr.district);
  if (addr.street) parts.push(addr.street);
  if (addr.apartment) parts.push(`kv. ${addr.apartment}`);
  if (addr.entrance) parts.push(`${addr.entrance}-kirish`);
  if (addr.floor) parts.push(`${addr.floor}-qavat`);
  const address = parts.join(', ');
  if (addr.comment) return `${address} (${addr.comment})`;
  return address;
});

const paymentLabels = {
  cash: 'Naqd pul',
  card: 'Plastik karta',
  click: 'Click',
  payme: 'Payme',
};
const getPaymentLabel = (method) => paymentLabels[method] || method || 'Naqd pul';

const formatPrice = (value) => {
  if (!value && value !== 0) return "0 so'm";
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

const formatDateTime = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('uz-UZ', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
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

const changeStatus = (newStatus) => {
  updating.value = true;
  router.post(storeRoute('orders.update-status', props.order.id), {
    status: newStatus,
  }, {
    preserveScroll: true,
    onFinish: () => {
      updating.value = false;
    },
  });
};
</script>
