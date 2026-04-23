<template>
  <AppLayout title="Payouts">
    <div class="max-w-[1600px] mx-auto">
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Payouts</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Komissiyalarni bankingizga chiqarish</p>
      </div>


      <!-- Bank warning -->
      <div v-if="!bank_configured" class="mb-5 flex items-start gap-3 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
        <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
        <div class="flex-1 text-sm">
          <p class="font-medium text-amber-800 dark:text-amber-200">Bank ma'lumotlari kiritilmagan</p>
          <p class="text-amber-700 dark:text-amber-300 mt-0.5">Avval bank ma'lumotlaringizni kiriting →
            <Link :href="route('partner.settings')" class="underline font-semibold">Sozlamalar</Link>
          </p>
        </div>
      </div>

      <!-- Request card -->
      <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl p-6 text-white mb-6 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div>
            <p class="text-emerald-100 text-sm">Mavjud summa</p>
            <p class="text-4xl font-bold mt-1">{{ formatMoney(available_balance) }}</p>
            <p class="text-xs text-emerald-100 mt-2">
              Minimum payout: <span class="font-semibold text-white">{{ formatMoney(min_payout) }}</span>
            </p>
          </div>
          <button
            @click="requestPayout"
            :disabled="!canRequest || form.processing"
            :class="[
              'px-6 py-3 font-semibold rounded-lg transition-all shadow-md flex items-center gap-2',
              canRequest && !form.processing
                ? 'bg-white text-emerald-700 hover:bg-emerald-50'
                : 'bg-white/30 text-white cursor-not-allowed'
            ]"
          >
            <BanknotesIcon class="w-5 h-5" />
            {{ form.processing ? 'Yuborilmoqda...' : "So'rov yuborish" }}
          </button>
        </div>
        <p v-if="!canRequest && bank_configured" class="mt-3 text-xs text-emerald-100">
          Payout so'rovi uchun minimum {{ formatMoney(min_payout) }} kerak.
        </p>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Payouts tarixi</h3>
        </div>
        <div v-if="payouts.data.length" class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 dark:bg-gray-900/40">
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">So'rov sanasi</th>
                <th class="text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Summa</th>
                <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden sm:table-cell">Komissiya soni</th>
                <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Status</th>
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden md:table-cell">Reference</th>
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden lg:table-cell">To'langan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
              <tr v-for="p in payouts.data" :key="p.id" class="hover:bg-gray-50 dark:hover:bg-gray-900/30">
                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ p.requested_at }}</td>
                <td class="px-5 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">{{ formatMoney(p.total_amount) }}</td>
                <td class="px-5 py-4 text-center text-sm text-gray-500 dark:text-gray-400 hidden sm:table-cell">{{ p.commissions_count }}</td>
                <td class="px-5 py-4 text-center">
                  <span :class="statusBadge(p.status)" class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md">
                    {{ statusLabel(p.status) }}
                  </span>
                </td>
                <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 font-mono hidden md:table-cell">{{ p.payout_reference || '—' }}</td>
                <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 hidden lg:table-cell">{{ p.paid_at || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="text-center py-16">
          <BanknotesIcon class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
          <p class="text-sm text-gray-500 dark:text-gray-400">Hali payout so'rovi yo'q</p>
        </div>
      </div>

      <div v-if="payouts.data.length && payouts.links" class="mt-4">
        <Pagination :links="payouts.links" :from="payouts.from" :to="payouts.to" :total="payouts.total" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/PartnerLayout.vue';
import Pagination from '@/components/Pagination.vue';
import { BanknotesIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  payouts: { type: Object, required: true },
  available_balance: { type: Number, default: 0 },
  min_payout: { type: Number, default: 0 },
  payout_method: { type: String, default: null },
  bank_configured: { type: Boolean, default: false },
});

const form = useForm({});

const formatMoney = (v) => new Intl.NumberFormat('uz-UZ').format(Math.round(v || 0)) + " so'm";

const canRequest = computed(() =>
  props.bank_configured && props.available_balance >= props.min_payout
);

const requestPayout = () => {
  if (!canRequest.value) return;
  form.post(route('partner.payouts.request'), {
    preserveScroll: true,
  });
};

const statusLabel = (s) => ({
  pending: 'Kutilmoqda',
  approved: 'Tasdiqlangan',
  processing: 'Jarayonda',
  paid: 'To\'langan',
  failed: 'Rad etildi',
}[s] || s);

const statusBadge = (s) => ({
  pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
  approved: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
  processing: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
  paid: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
  failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
}[s] || 'bg-gray-100 text-gray-700');
</script>
