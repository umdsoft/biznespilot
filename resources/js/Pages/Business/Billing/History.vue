<template>
  <BusinessLayout title="To'lov tarixi">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">To'lov tarixi</h1>
        <p class="text-gray-600 dark:text-gray-400">Barcha tranzaksiyalar va obuna holatini ko'ring</p>
      </div>

      <!-- Current Subscription Card -->
      <div v-if="subscriptionStatus?.has_subscription" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
              <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ subscriptionStatus.plan?.name }}</h3>
              <div class="flex items-center gap-2 mt-1">
                <span class="px-2 py-0.5 text-xs font-semibold rounded-md"
                  :class="subscriptionStatus.status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'">
                  {{ subscriptionStatus.status === 'active' ? 'Aktiv' : subscriptionStatus.status === 'trialing' ? 'Sinov' : subscriptionStatus.status }}
                </span>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                  {{ subscriptionStatus.billing_cycle === 'yearly' ? 'Yillik' : 'Oylik' }} obuna
                </span>
              </div>
            </div>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-500 dark:text-gray-400">Tugash sanasi</p>
            <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ subscriptionStatus.days_remaining }} kun qoldi</p>
          </div>
        </div>
      </div>

      <!-- Filter -->
      <div class="flex items-center gap-3 mb-6">
        <select
          v-model="statusFilter"
          @change="applyFilter"
          class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">Barcha holatlar</option>
          <option value="paid">To'langan</option>
          <option value="created">Yaratildi</option>
          <option value="waiting">Kutilmoqda</option>
          <option value="processing">Jarayonda</option>
          <option value="cancelled">Bekor qilindi</option>
          <option value="failed">Xatolik</option>
        </select>
      </div>

      <!-- Transactions Table -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div v-if="transactions.data && transactions.data.length > 0" class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sana</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tarif</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">To'lov usuli</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Summa</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Holat</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Order ID</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr v-for="t in transactions.data" :key="t.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ t.created_at }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ t.plan_name }}</span>
                    <span class="ml-1 text-xs text-gray-500 dark:text-gray-400">({{ t.billing_cycle === 'yearly' ? 'Yillik' : 'Oylik' }})</span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2.5 py-1 text-xs font-semibold rounded-md"
                    :class="t.provider === 'click' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400'">
                    {{ t.provider === 'click' ? 'Click' : 'Payme' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                  {{ formatPrice(t.amount) }} {{ t.currency }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2.5 py-1 text-xs font-semibold rounded-md" :class="statusClass(t.status)">
                    {{ statusLabel(t.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm font-mono text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ t.order_id }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-16">
          <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          <p class="text-gray-500 dark:text-gray-400 text-lg mb-2">Hali tranzaksiyalar yo'q</p>
          <a href="/business/billing/plans" class="text-blue-600 dark:text-blue-400 font-medium hover:underline">
            Tariflarni ko'rish &rarr;
          </a>
        </div>

        <!-- Pagination -->
        <div v-if="transactions.links && transactions.last_page > 1" class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ transactions.from }}-{{ transactions.to }} / {{ transactions.total }}
          </p>
          <div class="flex gap-2">
            <a v-for="link in transactions.links" :key="link.label"
              :href="link.url"
              class="px-3 py-1.5 text-sm rounded-lg transition-colors"
              :class="link.active
                ? 'bg-blue-600 text-white'
                : link.url
                  ? 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                  : 'text-gray-300 dark:text-gray-600 cursor-not-allowed'"
              v-html="link.label"
            ></a>
          </div>
        </div>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';

const props = defineProps({
  subscriptionStatus: { type: Object, default: () => null },
  transactions: { type: Object, default: () => ({ data: [] }) },
  filters: { type: Object, default: () => ({}) },
});

const statusFilter = ref(props.filters.status || '');

const applyFilter = () => {
  router.get('/business/billing/history', {
    status: statusFilter.value || undefined,
  }, { preserveState: true });
};

const formatPrice = (price) => {
  if (!price) return '0';
  return new Intl.NumberFormat('uz-UZ').format(price);
};

const statusLabel = (status) => {
  const labels = {
    created: 'Yaratildi',
    waiting: 'Kutilmoqda',
    processing: 'Jarayonda',
    paid: 'To\'langan',
    cancelled: 'Bekor qilindi',
    failed: 'Xatolik',
    refunded: 'Qaytarildi',
  };
  return labels[status] || status;
};

const statusClass = (status) => {
  const classes = {
    paid: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    created: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    waiting: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    processing: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    refunded: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
  };
  return classes[status] || 'bg-gray-100 text-gray-700';
};
</script>
