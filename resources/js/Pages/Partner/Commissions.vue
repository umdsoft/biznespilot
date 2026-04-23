<template>
  <AppLayout title="Komissiyalar">
    <div class="max-w-[1600px] mx-auto">
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Komissiyalar</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Har bir mijoz to'lovidan olingan komissiyalar tarixi</p>
      </div>


      <!-- Summary cards -->
      <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
        <button
          v-for="(count, key) in summary"
          :key="key"
          @click="setStatus(key === 'all' ? '' : key)"
          :class="[
            'rounded-xl p-4 border-2 transition-all text-left',
            (filters.status === key) || (key === 'all' && !filters.status)
              ? statusActiveBorder(key)
              : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300'
          ]"
        >
          <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-medium">{{ statusLabel(key) }}</p>
          <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">{{ count }}</p>
        </button>
      </div>

      <!-- Status filter dropdown -->
      <div class="flex gap-3 mb-4">
        <select
          :value="filters.status || ''"
          @change="setStatus($event.target.value)"
          class="px-4 py-2.5 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white"
        >
          <option value="">Barcha statuslar</option>
          <option value="pending">Pending</option>
          <option value="available">Available</option>
          <option value="paid">Paid</option>
          <option value="reversed">Reversed</option>
          <option value="clawback">Clawback</option>
        </select>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div v-if="commissions.data.length" class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 dark:bg-gray-900/40">
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Biznes</th>
                <th class="text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden md:table-cell">Plan to'lovi</th>
                <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Stavka</th>
                <th class="text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Komissiya</th>
                <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Status</th>
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden lg:table-cell">Available</th>
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden lg:table-cell">Paid</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
              <tr v-for="c in commissions.data" :key="c.id" class="hover:bg-gray-50 dark:hover:bg-gray-900/30">
                <td class="px-5 py-4">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{ c.business_name }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ c.created_at }}</p>
                </td>
                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300 text-right hidden md:table-cell">{{ formatMoney(c.gross_amount) }}</td>
                <td class="px-5 py-4 text-center">
                  <span :class="rateBadge(c.rate_type)" class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-md">
                    {{ (c.rate_applied * 100).toFixed(0) }}% · {{ c.rate_type === 'first_payment' ? '1-to\'lov' : 'Keyingi' }}
                  </span>
                </td>
                <td class="px-5 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">{{ formatMoney(c.commission_amount) }}</td>
                <td class="px-5 py-4 text-center">
                  <span :class="statusBadge(c.status)" class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md">
                    {{ c.status }}
                  </span>
                </td>
                <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 hidden lg:table-cell">{{ c.available_at || '—' }}</td>
                <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 hidden lg:table-cell">{{ c.paid_at || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="text-center py-16">
          <CurrencyDollarIcon class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
          <p class="text-sm text-gray-500 dark:text-gray-400">Hozircha komissiya yo'q</p>
        </div>
      </div>

      <div v-if="commissions.data.length && commissions.links" class="mt-4">
        <Pagination :links="commissions.links" :from="commissions.from" :to="commissions.to" :total="commissions.total" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/PartnerLayout.vue';
import Pagination from '@/components/Pagination.vue';
import { CurrencyDollarIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  commissions: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const formatMoney = (v) => new Intl.NumberFormat('uz-UZ').format(Math.round(v || 0)) + " so'm";

const summary = computed(() => ({
  all: props.commissions.total ?? 0,
  pending: 0, available: 0, paid: 0, reversed: 0,
}));

const setStatus = (s) => {
  router.get(
    route('partner.commissions'),
    { status: s || undefined },
    { preserveState: true, preserveScroll: true, replace: true }
  );
};

const statusLabel = (s) => ({
  all: 'Jami', pending: 'Kutilmoqda', available: 'Mavjud',
  paid: 'To\'langan', reversed: 'Qaytarildi', clawback: 'Clawback',
}[s] || s);

const statusBadge = (s) => ({
  pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
  available: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
  paid: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
  reversed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
  clawback: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
}[s] || 'bg-gray-100 text-gray-700');

const rateBadge = (t) => t === 'first_payment'
  ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
  : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';

const statusActiveBorder = (s) => ({
  all: 'border-gray-500 bg-gray-50 dark:bg-gray-900/40',
  pending: 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20',
  available: 'border-blue-500 bg-blue-50 dark:bg-blue-900/20',
  paid: 'border-green-500 bg-green-50 dark:bg-green-900/20',
  reversed: 'border-red-500 bg-red-50 dark:bg-red-900/20',
}[s] || 'border-gray-400');
</script>
