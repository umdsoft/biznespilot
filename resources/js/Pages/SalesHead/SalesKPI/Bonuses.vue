<template>
  <SalesHeadLayout title="Bonuslar">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Bonuslar</h2>
          <p class="mt-2 text-gray-600 dark:text-gray-400">Oylik bonuslarni ko'rish va tasdiqlash</p>
        </div>
        <div class="flex items-center gap-3">
          <select v-model="selectedMonth" @change="filterByMonth"
                  class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800">
            <option v-for="m in 12" :key="m" :value="m">{{ getMonthName(m) }}</option>
          </select>
          <select v-model="selectedYear" @change="filterByMonth"
                  class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800">
            <option :value="currentYear - 1">{{ currentYear - 1 }}</option>
            <option :value="currentYear">{{ currentYear }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 mb-1">Jami hisoblangan</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats.total_calculated) }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 mb-1">Tasdiqlangan</p>
        <p class="text-2xl font-bold text-green-600">{{ formatCurrency(stats.total_approved) }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 mb-1">Kutilmoqda</p>
        <p class="text-2xl font-bold text-yellow-600">{{ stats.pending_count }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 mb-1">Tasdiqlangan soni</p>
        <p class="text-2xl font-bold text-emerald-600">{{ stats.approved_count }}</p>
      </div>
    </div>

    <!-- Bonuses Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Xodim</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">KPI Ball</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tier</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bonus</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Jarimalar</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Jami</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Holat</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amal</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="bonus in bonuses" :key="bonus.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-700 dark:text-emerald-400 font-medium">
                    {{ getInitials(bonus.user?.name) }}
                  </div>
                  <span class="ml-3 font-medium text-gray-900 dark:text-white">{{ bonus.user?.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="text-lg font-bold" :class="getScoreColor(bonus.kpi_score)">{{ bonus.kpi_score }}%</span>
              </td>
              <td class="px-6 py-4 text-center">
                <span :class="getTierBadge(bonus.applied_tier)">{{ bonus.applied_tier }}</span>
              </td>
              <td class="px-6 py-4 text-right font-medium text-green-600">
                +{{ formatCurrency(bonus.base_amount) }}
              </td>
              <td class="px-6 py-4 text-right font-medium text-red-600">
                -{{ formatCurrency(bonus.total_penalties || 0) }}
              </td>
              <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">
                {{ formatCurrency(bonus.final_amount) }}
              </td>
              <td class="px-6 py-4 text-center">
                <span :class="getStatusBadge(bonus.status)">{{ getStatusLabel(bonus.status) }}</span>
              </td>
              <td class="px-6 py-4 text-center">
                <button v-if="bonus.status === 'pending'" @click="openApproveModal(bonus)"
                        class="px-3 py-1 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700">
                  Tasdiqlash
                </button>
                <span v-else class="text-gray-400 text-sm">-</span>
              </td>
            </tr>
            <tr v-if="bonuses.length === 0">
              <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                Bu oy uchun bonus ma'lumotlari yo'q
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Approve Modal -->
    <div v-if="approveModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Bonusni tasdiqlash</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-4">
          <strong>{{ selectedBonus?.user?.name }}</strong> uchun bonus tasdiqlash
        </p>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tasdiqlangan summa</label>
          <input v-model.number="approveForm.approved_bonus" type="number"
                 class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Izoh (ixtiyoriy)</label>
          <textarea v-model="approveForm.notes" rows="2"
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700"></textarea>
        </div>
        <div class="flex justify-end gap-3">
          <button @click="approveModal = false" class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
            Bekor qilish
          </button>
          <button @click="approveBonus" class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
            Tasdiqlash
          </button>
        </div>
      </div>
    </div>
  </SalesHeadLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';

const props = defineProps({
  bonuses: Array,
  stats: Object,
  month: Number,
  year: Number,
  currentStatus: String,
  panelType: String,
});

const currentYear = new Date().getFullYear();
const selectedMonth = ref(props.month);
const selectedYear = ref(props.year);
const approveModal = ref(false);
const selectedBonus = ref(null);
const approveForm = ref({ approved_bonus: 0, notes: '' });

const getMonthName = (m) => {
  const months = ['Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun', 'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr'];
  return months[m - 1];
};

const filterByMonth = () => {
  router.get('/sales-head/sales-kpi/bonuses', { month: selectedMonth.value, year: selectedYear.value }, { preserveState: true });
};

const formatCurrency = (value) => {
  if (!value) return "0 so'm";
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';

const getScoreColor = (score) => {
  if (score >= 100) return 'text-green-600';
  if (score >= 80) return 'text-blue-600';
  return 'text-yellow-600';
};

const getTierBadge = (tier) => {
  const badges = {
    standard: 'px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700',
    excellent: 'px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-700',
    accelerator: 'px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700',
  };
  return badges[tier] || badges.standard;
};

const getStatusBadge = (status) => {
  const badges = {
    pending: 'px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-700',
    approved: 'px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700',
    rejected: 'px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700',
  };
  return badges[status] || badges.pending;
};

const getStatusLabel = (status) => {
  const labels = { pending: 'Kutilmoqda', approved: 'Tasdiqlangan', rejected: 'Rad etilgan' };
  return labels[status] || status;
};

const openApproveModal = (bonus) => {
  selectedBonus.value = bonus;
  approveForm.value = { approved_bonus: bonus.final_amount, notes: '' };
  approveModal.value = true;
};

const approveBonus = () => {
  router.post(`/sales-head/sales-kpi/bonuses/${selectedBonus.value.id}/approve`, approveForm.value, {
    onSuccess: () => { approveModal.value = false; }
  });
};
</script>
