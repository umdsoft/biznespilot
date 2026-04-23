<template>
  <AdminLayout :title="`Partner: ${partner.full_name}`">
    <div class="min-h-screen">
      <div class="max-w-[1400px] mx-auto px-6 py-6">
        <!-- Breadcrumb -->
        <div class="mb-4">
          <Link :href="route('admin.partners.index')" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 inline-flex items-center gap-1">
            <ArrowLeftIcon class="w-4 h-4" />
            Barcha partnerlar
          </Link>
        </div>

        <!-- Partner header -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
          <div class="flex items-start justify-between flex-wrap gap-4">
            <div class="flex items-start gap-4">
              <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-2xl font-bold">
                {{ partner.full_name?.charAt(0) || 'P' }}
              </div>
              <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ partner.full_name }}</h1>
                <div class="flex items-center gap-2 mt-2 flex-wrap">
                  <span class="px-2 py-0.5 text-xs font-mono bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded">{{ partner.code }}</span>
                  <span :class="statusBadge(partner.status)" class="px-2 py-0.5 text-xs font-medium rounded-md">{{ partner.status }}</span>
                  <span :class="tierBadge(partner.tier)" class="px-2 py-0.5 text-xs font-semibold rounded-md">{{ tierIcon(partner.tier) }} {{ partner.tier }}</span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                  {{ partner.user?.email }} · {{ partner.phone || '—' }}
                </p>
              </div>
            </div>
            <div class="flex gap-2">
              <button @click="showStatusModal = true" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm font-medium rounded-lg inline-flex items-center gap-1.5">
                <PencilIcon class="w-4 h-4" />
                Status
              </button>
              <button @click="showTierModal = true" class="px-3 py-2 bg-indigo-100 dark:bg-indigo-900/40 hover:bg-indigo-200 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 text-sm font-medium rounded-lg inline-flex items-center gap-1.5">
                <TrophyIcon class="w-4 h-4" />
                Tier
              </button>
            </div>
          </div>

          <!-- KPI row -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400">Jami referral</p>
              <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ partner.referrals_count_cached || 0 }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400">Faol referral</p>
              <p class="text-xl font-bold text-green-600 dark:text-green-400 mt-1">{{ partner.active_referrals_count_cached || 0 }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400">Lifetime daromad</p>
              <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ formatMoney(partner.lifetime_earned_cached) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400">Available</p>
              <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ formatMoney(partner.available_balance_cached) }}</p>
            </div>
          </div>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 dark:border-gray-700 mb-5">
          <nav class="-mb-px flex gap-6">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              @click="active = tab.key"
              :class="[
                'whitespace-nowrap border-b-2 pb-3 pt-2 text-sm font-medium transition-colors',
                active === tab.key
                  ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                  : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
              ]"
            >
              {{ tab.label }}
            </button>
          </nav>
        </div>

        <!-- Profile tab -->
        <div v-if="active === 'profile'" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
          <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div v-for="field in profileFields" :key="field.label">
              <dt class="text-gray-500 dark:text-gray-400">{{ field.label }}</dt>
              <dd class="font-medium text-gray-900 dark:text-white mt-0.5">{{ field.value || '—' }}</dd>
            </div>
          </dl>
        </div>

        <!-- Referrals tab -->
        <div v-if="active === 'referrals'" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <table v-if="referrals.length" class="w-full">
            <thead>
              <tr class="bg-gray-50 dark:bg-gray-900/40">
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Biznes</th>
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Status</th>
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Kanal</th>
                <th class="text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Daromad</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
              <tr v-for="r in referrals" :key="r.id">
                <td class="px-5 py-3 text-sm text-gray-900 dark:text-gray-100">{{ r.business?.name || '—' }}</td>
                <td class="px-5 py-3"><span :class="refStatusBadge(r.status)" class="px-2 py-0.5 text-xs font-medium rounded-md">{{ r.status }}</span></td>
                <td class="px-5 py-3 text-sm text-gray-500">{{ r.referred_via || '—' }}</td>
                <td class="px-5 py-3 text-sm font-semibold text-gray-900 dark:text-white text-right">{{ formatMoney(r.lifetime_commission_earned) }}</td>
              </tr>
            </tbody>
          </table>
          <p v-else class="text-center py-10 text-sm text-gray-500">Referral yo'q</p>
        </div>

        <!-- Commissions tab -->
        <div v-if="active === 'commissions'" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <table v-if="commissions.length" class="w-full">
            <thead>
              <tr class="bg-gray-50 dark:bg-gray-900/40">
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Biznes</th>
                <th class="text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Gross</th>
                <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Stavka</th>
                <th class="text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Komissiya</th>
                <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
              <tr v-for="c in commissions" :key="c.id">
                <td class="px-5 py-3 text-sm text-gray-900 dark:text-gray-100">{{ c.business?.name || '—' }}</td>
                <td class="px-5 py-3 text-sm text-right">{{ formatMoney(c.gross_amount) }}</td>
                <td class="px-5 py-3 text-center text-xs">{{ (Number(c.rate_applied) * 100).toFixed(0) }}%</td>
                <td class="px-5 py-3 text-sm font-bold text-right">{{ formatMoney(c.commission_amount) }}</td>
                <td class="px-5 py-3 text-center"><span :class="commStatusBadge(c.status)" class="px-2 py-0.5 text-xs font-medium rounded-md">{{ c.status }}</span></td>
              </tr>
            </tbody>
          </table>
          <p v-else class="text-center py-10 text-sm text-gray-500">Komissiya yo'q</p>
        </div>

        <!-- Payouts tab -->
        <div v-if="active === 'payouts'" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <table v-if="payouts.length" class="w-full">
            <thead>
              <tr class="bg-gray-50 dark:bg-gray-900/40">
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Sana</th>
                <th class="text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Summa</th>
                <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Status</th>
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Reference</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
              <tr v-for="p in payouts" :key="p.id">
                <td class="px-5 py-3 text-sm">{{ p.created_at }}</td>
                <td class="px-5 py-3 text-sm font-bold text-right">{{ formatMoney(p.total_amount) }}</td>
                <td class="px-5 py-3 text-center"><span :class="payoutStatusBadge(p.status)" class="px-2 py-0.5 text-xs font-medium rounded-md">{{ p.status }}</span></td>
                <td class="px-5 py-3 text-sm text-gray-500 font-mono">{{ p.payout_reference || '—' }}</td>
              </tr>
            </tbody>
          </table>
          <p v-else class="text-center py-10 text-sm text-gray-500">Payout yo'q</p>
        </div>
      </div>
    </div>

    <!-- Status modal -->
    <Modal v-model="showStatusModal" title="Statusni o'zgartirish">
      <form @submit.prevent="submitStatus" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Yangi status</label>
          <select v-model="statusForm.status" class="w-full px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm">
            <option value="pending">Pending</option>
            <option value="active">Active</option>
            <option value="suspended">Suspended</option>
            <option value="terminated">Terminated</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Admin izoh</label>
          <textarea v-model="statusForm.admin_notes" rows="3" class="w-full px-3 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm"></textarea>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" @click="showStatusModal = false" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-300">Bekor qilish</button>
          <button type="submit" :disabled="statusForm.processing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Saqlash</button>
        </div>
      </form>
    </Modal>

    <!-- Tier modal -->
    <Modal v-model="showTierModal" title="Tier o'zgartirish">
      <form @submit.prevent="submitTier" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Yangi tier</label>
          <select v-model="tierForm.tier" class="w-full px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm">
            <option value="bronze">🥉 Bronze</option>
            <option value="silver">🥈 Silver</option>
            <option value="gold">🥇 Gold</option>
            <option value="platinum">💎 Platinum</option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Custom 1-yil stavka (0-1)</label>
            <input v-model.number="tierForm.custom_year_one_rate" type="number" step="0.01" min="0" max="1" class="w-full px-3 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Custom lifetime stavka</label>
            <input v-model.number="tierForm.custom_lifetime_rate" type="number" step="0.01" min="0" max="1" class="w-full px-3 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm" />
          </div>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" @click="showTierModal = false" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-300">Bekor qilish</button>
          <button type="submit" :disabled="tierForm.processing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Saqlash</button>
        </div>
      </form>
    </Modal>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/components/Modal.vue';
import { ArrowLeftIcon, PencilIcon, TrophyIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  partner: { type: Object, required: true },
  referrals: { type: Array, default: () => [] },
  commissions: { type: Array, default: () => [] },
  payouts: { type: Array, default: () => [] },
});

const active = ref('profile');
const showStatusModal = ref(false);
const showTierModal = ref(false);

const tabs = [
  { key: 'profile', label: 'Profile' },
  { key: 'referrals', label: 'Referrallar' },
  { key: 'commissions', label: 'Komissiyalar' },
  { key: 'payouts', label: 'Payouts' },
];

const statusForm = useForm({
  status: props.partner.status,
  admin_notes: '',
});

const tierForm = useForm({
  tier: props.partner.tier,
  custom_year_one_rate: props.partner.custom_year_one_rate || null,
  custom_lifetime_rate: props.partner.custom_lifetime_rate || null,
});

const submitStatus = () => {
  statusForm.put(route('admin.partners.update-status', props.partner.id), {
    preserveScroll: true,
    onSuccess: () => (showStatusModal.value = false),
  });
};

const submitTier = () => {
  tierForm.put(route('admin.partners.update-tier', props.partner.id), {
    preserveScroll: true,
    onSuccess: () => (showTierModal.value = false),
  });
};

const formatMoney = (v) => new Intl.NumberFormat('uz-UZ').format(Math.round(v || 0)) + " so'm";

const profileFields = computed(() => [
  { label: 'Telefon', value: props.partner.phone },
  { label: 'Telegram', value: props.partner.telegram_id },
  { label: 'Turi', value: props.partner.partner_type },
  { label: 'Kompaniya', value: props.partner.company_name },
  { label: 'INN/STIR', value: props.partner.inn_stir },
  { label: 'Bank', value: props.partner.bank_name },
  { label: 'Hisob', value: props.partner.bank_account },
  { label: 'To\'lov usuli', value: props.partner.preferred_payout_method },
]);

const statusBadge = (s) => ({
  pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
  active: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
  suspended: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  terminated: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
}[s] || 'bg-gray-100 text-gray-700');

const tierBadge = (t) => ({
  bronze: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
  silver: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
  gold: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
  platinum: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
}[t] || 'bg-gray-100 text-gray-700');

const tierIcon = (t) => ({ bronze: '🥉', silver: '🥈', gold: '🥇', platinum: '💎' }[t] || '');

const refStatusBadge = (s) => ({
  pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
  attributed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
  active: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
  churned: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
}[s] || 'bg-gray-100 text-gray-700');

const commStatusBadge = (s) => ({
  pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
  available: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
  paid: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
  reversed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
}[s] || 'bg-gray-100 text-gray-700');

const payoutStatusBadge = (s) => ({
  pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
  approved: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
  paid: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
  failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
}[s] || 'bg-gray-100 text-gray-700');
</script>
