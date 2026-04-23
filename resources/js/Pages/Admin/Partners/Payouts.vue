<template>
  <AdminLayout title="Payouts queue">
    <div class="min-h-screen">
      <!-- Header -->
      <div class="border-b border-gray-200 dark:border-gray-700/50 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm sticky top-0 z-10">
        <div class="max-w-[1600px] mx-auto px-6 py-5">
          <Link :href="route('admin.partners.index')" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 inline-flex items-center gap-1 mb-1">
            <ArrowLeftIcon class="w-4 h-4" /> Partnerlar
          </Link>
          <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Payouts queue</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Partner to'lov so'rovlari</p>
        </div>
      </div>

      <div class="max-w-[1600px] mx-auto px-6 py-6">
        <!-- Status tabs -->
        <div class="flex gap-1 mb-5 p-1 bg-gray-100 dark:bg-gray-800 rounded-lg w-fit">
          <button
            v-for="tab in statusTabs"
            :key="tab.key"
            @click="setStatus(tab.key)"
            :class="[
              'px-4 py-2 text-sm font-medium rounded-md transition-colors',
              activeStatus === tab.key
                ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm'
                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'
            ]"
          >
            {{ tab.label }}
          </button>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div v-if="payouts.data.length" class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="bg-gray-50 dark:bg-gray-900/30">
                  <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5">Partner</th>
                  <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5 hidden md:table-cell">Bank</th>
                  <th class="text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5">Summa</th>
                  <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5 hidden sm:table-cell">Kom. soni</th>
                  <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5 hidden lg:table-cell">Sana</th>
                  <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5">Status</th>
                  <th class="text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5">Amallar</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                <tr v-for="p in payouts.data" :key="p.id" class="hover:bg-gray-50 dark:hover:bg-gray-900/30">
                  <td class="px-5 py-4">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ p.partner_name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ p.partner_code }}</p>
                  </td>
                  <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300 hidden md:table-cell">
                    <p>{{ p.bank_name || '—' }}</p>
                    <p class="text-xs font-mono text-gray-500">{{ p.bank_account || '—' }}</p>
                  </td>
                  <td class="px-5 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">{{ formatMoney(p.total_amount) }}</td>
                  <td class="px-5 py-4 text-center text-sm text-gray-500 dark:text-gray-400 hidden sm:table-cell">{{ p.commissions_count }}</td>
                  <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 hidden lg:table-cell">{{ p.created_at }}</td>
                  <td class="px-5 py-4 text-center">
                    <span :class="statusBadge(p.status)" class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md">
                      {{ statusLabel(p.status) }}
                    </span>
                  </td>
                  <td class="px-5 py-4 text-right">
                    <div class="flex gap-1.5 justify-end">
                      <button
                        v-if="p.status === 'pending'"
                        @click="approve(p)"
                        class="px-2.5 py-1 text-xs font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-md inline-flex items-center gap-1"
                      >
                        <CheckIcon class="w-3 h-3" /> Tasdiqlash
                      </button>
                      <button
                        v-if="p.status === 'approved' || p.status === 'processing'"
                        @click="openMarkPaid(p)"
                        class="px-2.5 py-1 text-xs font-medium bg-green-600 hover:bg-green-700 text-white rounded-md inline-flex items-center gap-1"
                      >
                        <BanknotesIcon class="w-3 h-3" /> To'landi
                      </button>
                      <button
                        v-if="p.status === 'pending' || p.status === 'approved'"
                        @click="openReject(p)"
                        class="px-2.5 py-1 text-xs font-medium bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 hover:bg-red-100 rounded-md inline-flex items-center gap-1"
                      >
                        <XMarkIcon class="w-3 h-3" /> Rad etish
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="text-center py-16">
            <BanknotesIcon class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
            <p class="text-sm text-gray-500 dark:text-gray-400">Bu statusda payout yo'q</p>
          </div>
        </div>

        <div v-if="payouts.data.length && payouts.links" class="mt-4">
          <Pagination :links="payouts.links" :from="payouts.from" :to="payouts.to" :total="payouts.total" />
        </div>
      </div>
    </div>

    <!-- Mark Paid Modal -->
    <Modal v-model="markPaidOpen" title="To'landi deb belgilash">
      <form @submit.prevent="submitMarkPaid" class="space-y-4">
        <p class="text-sm text-gray-600 dark:text-gray-300">
          Partner: <span class="font-semibold">{{ selected?.partner_name }}</span><br />
          Summa: <span class="font-semibold">{{ formatMoney(selected?.total_amount) }}</span>
        </p>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Reference (to'lov raqami) *</label>
          <input v-model="markPaidForm.payout_reference" type="text" required placeholder="TXN123456" class="w-full px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Izoh</label>
          <textarea v-model="markPaidForm.note" rows="2" class="w-full px-3 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm"></textarea>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" @click="markPaidOpen = false" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">Bekor</button>
          <button type="submit" :disabled="markPaidForm.processing" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg">Tasdiqlash</button>
        </div>
      </form>
    </Modal>

    <!-- Reject Modal -->
    <Modal v-model="rejectOpen" title="Payoutni rad etish">
      <form @submit.prevent="submitReject" class="space-y-4">
        <p class="text-sm text-gray-600 dark:text-gray-300">
          Partner: <span class="font-semibold">{{ selected?.partner_name }}</span>
        </p>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Rad etish sababi *</label>
          <textarea v-model="rejectForm.failure_reason" rows="3" required class="w-full px-3 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm"></textarea>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" @click="rejectOpen = false" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">Bekor</button>
          <button type="submit" :disabled="rejectForm.processing" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg">Rad etish</button>
        </div>
      </form>
    </Modal>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/components/Pagination.vue';
import Modal from '@/components/Modal.vue';
import {
  ArrowLeftIcon,
  BanknotesIcon,
  CheckIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  payouts: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const activeStatus = computed(() => props.filters.status || 'pending');

const statusTabs = [
  { key: 'pending', label: 'Kutilmoqda' },
  { key: 'approved', label: 'Tasdiqlangan' },
  { key: 'paid', label: "To'langan" },
  { key: 'failed', label: 'Rad etilgan' },
];

const selected = ref(null);
const markPaidOpen = ref(false);
const rejectOpen = ref(false);

const markPaidForm = useForm({ payout_reference: '', note: '' });
const rejectForm = useForm({ failure_reason: '' });

const formatMoney = (v) => new Intl.NumberFormat('uz-UZ').format(Math.round(v || 0)) + " so'm";

const setStatus = (s) => {
  router.get(
    route('admin.partners.payouts'),
    { status: s },
    { preserveState: true, preserveScroll: true, replace: true }
  );
};

const approve = (p) => {
  if (!confirm(`${p.partner_name} uchun ${formatMoney(p.total_amount)} tasdiqlaysizmi?`)) return;
  router.post(
    route('admin.partners.payouts.approve', p.id),
    {},
    { preserveScroll: true }
  );
};

const openMarkPaid = (p) => {
  selected.value = p;
  markPaidForm.reset();
  markPaidOpen.value = true;
};

const submitMarkPaid = () => {
  markPaidForm.post(route('admin.partners.payouts.mark-paid', selected.value.id), {
    preserveScroll: true,
    onSuccess: () => (markPaidOpen.value = false),
  });
};

const openReject = (p) => {
  selected.value = p;
  rejectForm.reset();
  rejectOpen.value = true;
};

const submitReject = () => {
  rejectForm.post(route('admin.partners.payouts.reject', selected.value.id), {
    preserveScroll: true,
    onSuccess: () => (rejectOpen.value = false),
  });
};

const statusLabel = (s) => ({
  pending: 'Kutilmoqda',
  approved: 'Tasdiqlangan',
  processing: 'Jarayonda',
  paid: "To'langan",
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
