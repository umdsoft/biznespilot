<template>
  <SalesHeadLayout title="Jarimalar">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Jarimalar</h2>
          <p class="mt-2 text-gray-600 dark:text-gray-400">Jarimalarni ko'rish va shikoyatlarni ko'rib chiqish</p>
        </div>
        <div class="flex items-center gap-3">
          <select v-model="selectedStatus" @change="filterPenalties"
                  class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800">
            <option value="">Barchasi</option>
            <option value="pending">Kutilmoqda</option>
            <option value="confirmed">Tasdiqlangan</option>
            <option value="appealed">Shikoyat qilingan</option>
            <option value="cancelled">Bekor qilingan</option>
          </select>
          <select v-model="selectedMonth" @change="filterPenalties"
                  class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800">
            <option v-for="m in 12" :key="m" :value="m">{{ getMonthName(m) }}</option>
          </select>
          <button @click="openManualPenaltyModal"
                  class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Qo'lda jarima
          </button>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 mb-1">Jami jarimalar</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_count }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 mb-1">Jami summa</p>
        <p class="text-2xl font-bold text-red-600">{{ formatCurrency(stats.total_amount) }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 mb-1">Kutilmoqda</p>
        <p class="text-2xl font-bold text-yellow-600">{{ stats.pending_count }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 mb-1">Shikoyatlar</p>
        <p class="text-2xl font-bold text-orange-600">{{ stats.appealed_count }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 mb-1">Avtomatik</p>
        <p class="text-2xl font-bold text-purple-600">{{ stats.auto_count }}</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
      <nav class="flex gap-8">
        <button @click="activeTab = 'all'"
                :class="['pb-4 px-1 font-medium text-sm border-b-2 transition-colors',
                         activeTab === 'all' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
          Barcha jarimalar
        </button>
        <button @click="activeTab = 'appeals'"
                :class="['pb-4 px-1 font-medium text-sm border-b-2 transition-colors flex items-center gap-2',
                         activeTab === 'appeals' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
          Shikoyatlar
          <span v-if="stats.appealed_count > 0" class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded-full text-xs">
            {{ stats.appealed_count }}
          </span>
        </button>
        <button @click="activeTab = 'warnings'"
                :class="['pb-4 px-1 font-medium text-sm border-b-2 transition-colors',
                         activeTab === 'warnings' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
          Ogohlantirishlar
        </button>
      </nav>
    </div>

    <!-- All Penalties Tab -->
    <div v-if="activeTab === 'all'" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Xodim</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sabab</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Summa</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Turi</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Holat</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sana</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amal</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="penalty in penalties" :key="penalty.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-700 dark:text-red-400 font-medium">
                    {{ getInitials(penalty.user?.name) }}
                  </div>
                  <span class="ml-3 font-medium text-gray-900 dark:text-white">{{ penalty.user?.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="font-medium text-gray-900 dark:text-white">{{ penalty.penalty_rule?.name || 'Qo\'lda jarima' }}</div>
                <div class="text-sm text-gray-500">{{ penalty.reason }}</div>
              </td>
              <td class="px-6 py-4 text-right font-bold text-red-600">
                -{{ formatCurrency(penalty.penalty_amount) }}
              </td>
              <td class="px-6 py-4 text-center">
                <span :class="penalty.is_auto ? 'px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs' : 'px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs'">
                  {{ penalty.is_auto ? 'Avtomatik' : "Qo'lda" }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <span :class="getStatusBadge(penalty.status)">{{ getStatusLabel(penalty.status) }}</span>
              </td>
              <td class="px-6 py-4 text-center text-sm text-gray-500">
                {{ formatDate(penalty.triggered_at) }}
              </td>
              <td class="px-6 py-4 text-center">
                <div class="flex items-center justify-center gap-2">
                  <button v-if="penalty.status === 'pending'" @click="confirmPenalty(penalty)"
                          class="px-2 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700">
                    Tasdiqlash
                  </button>
                  <button v-if="penalty.status === 'pending'" @click="cancelPenalty(penalty)"
                          class="px-2 py-1 bg-gray-200 text-gray-700 rounded text-xs hover:bg-gray-300">
                    Bekor
                  </button>
                  <button v-if="penalty.status === 'appealed'" @click="openAppealModal(penalty)"
                          class="px-2 py-1 bg-orange-600 text-white rounded text-xs hover:bg-orange-700">
                    Ko'rib chiqish
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="penalties.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                Jarimalar topilmadi
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Appeals Tab -->
    <div v-if="activeTab === 'appeals'" class="space-y-4">
      <div v-for="appeal in appealedPenalties" :key="appeal.id"
           class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-orange-200 dark:border-orange-700">
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-700 dark:text-red-400 font-medium text-lg">
              {{ getInitials(appeal.user?.name) }}
            </div>
            <div>
              <h3 class="font-bold text-gray-900 dark:text-white">{{ appeal.user?.name }}</h3>
              <p class="text-sm text-gray-500">{{ appeal.penalty_rule?.name || "Qo'lda jarima" }}</p>
            </div>
          </div>
          <div class="text-right">
            <p class="text-xl font-bold text-red-600">-{{ formatCurrency(appeal.penalty_amount) }}</p>
            <p class="text-sm text-gray-500">{{ formatDate(appeal.triggered_at) }}</p>
          </div>
        </div>

        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 mb-4">
          <p class="text-sm font-medium text-orange-700 dark:text-orange-400 mb-1">Shikoyat sababi:</p>
          <p class="text-gray-700 dark:text-gray-300">{{ appeal.appeal_reason || 'Sabab ko\'rsatilmagan' }}</p>
        </div>

        <div class="flex justify-end gap-3">
          <button @click="rejectAppeal(appeal)"
                  class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
            Rad etish
          </button>
          <button @click="acceptAppeal(appeal)"
                  class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            Qabul qilish
          </button>
        </div>
      </div>
      <div v-if="appealedPenalties.length === 0"
           class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center text-gray-500 border border-gray-200 dark:border-gray-700">
        Ko'rib chiqilishi kerak bo'lgan shikoyatlar yo'q
      </div>
    </div>

    <!-- Warnings Tab -->
    <div v-if="activeTab === 'warnings'" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Xodim</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ogohlantirish</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ketma-ketlik</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Qolgan</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sana</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="warning in warnings" :key="warning.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-10 h-10 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center text-yellow-700 dark:text-yellow-400 font-medium">
                    {{ getInitials(warning.user?.name) }}
                  </div>
                  <span class="ml-3 font-medium text-gray-900 dark:text-white">{{ warning.user?.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="font-medium text-gray-900 dark:text-white">{{ warning.penalty_rule?.name }}</div>
                <div class="text-sm text-gray-500">{{ warning.message }}</div>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="font-bold text-yellow-600">{{ warning.sequence_count }}</span>
              </td>
              <td class="px-6 py-4 text-center">
                <span :class="warning.warnings_remaining <= 1 ? 'text-red-600 font-bold' : 'text-gray-600'">
                  {{ warning.warnings_remaining }}
                </span>
              </td>
              <td class="px-6 py-4 text-center text-sm text-gray-500">
                {{ formatDate(warning.created_at) }}
              </td>
            </tr>
            <tr v-if="warnings.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                Ogohlantirishlar topilmadi
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Manual Penalty Modal -->
    <div v-if="manualPenaltyModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Qo'lda jarima berish</h3>
        <form @submit.prevent="submitManualPenalty" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Xodim</label>
            <select v-model="penaltyForm.user_id" required
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
              <option value="">Tanlang...</option>
              <option v-for="member in teamMembers" :key="member.id" :value="member.id">
                {{ member.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jarima qoidasi (ixtiyoriy)</label>
            <select v-model="penaltyForm.rule_id"
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
              <option value="">Qoidasiz</option>
              <option v-for="rule in penaltyRules" :key="rule.id" :value="rule.id">
                {{ rule.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Summa</label>
            <input v-model.number="penaltyForm.amount" type="number" required
                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sabab</label>
            <textarea v-model="penaltyForm.reason" rows="2" required
                      class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700"></textarea>
          </div>
          <div class="flex justify-end gap-3">
            <button type="button" @click="manualPenaltyModal = false"
                    class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
              Bekor qilish
            </button>
            <button type="submit" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">
              Jarima berish
            </button>
          </div>
        </form>
      </div>
    </div>
  </SalesHeadLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';

const props = defineProps({
  penalties: Array,
  warnings: Array,
  penaltyRules: Array,
  teamMembers: Array,
  stats: Object,
  currentStatus: String,
  month: Number,
  year: Number,
  panelType: String,
});

const currentYear = new Date().getFullYear();
const selectedMonth = ref(props.month || new Date().getMonth() + 1);
const selectedStatus = ref(props.currentStatus || '');
const activeTab = ref('all');
const manualPenaltyModal = ref(false);
const penaltyForm = ref({ user_id: '', rule_id: '', amount: 0, reason: '' });

const appealedPenalties = computed(() => props.penalties.filter(p => p.status === 'appealed'));

const getMonthName = (m) => {
  const months = ['Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun', 'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr'];
  return months[m - 1];
};

const formatCurrency = (value) => {
  if (!value) return "0 so'm";
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('uz-UZ');
};

const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';

const getStatusBadge = (status) => {
  const badges = {
    pending: 'px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-700',
    confirmed: 'px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700',
    appealed: 'px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-700',
    cancelled: 'px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700',
  };
  return badges[status] || badges.pending;
};

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Kutilmoqda',
    confirmed: 'Tasdiqlangan',
    appealed: 'Shikoyat',
    cancelled: 'Bekor qilingan',
  };
  return labels[status] || status;
};

const filterPenalties = () => {
  router.get('/sales-head/sales-kpi/penalties', {
    month: selectedMonth.value,
    status: selectedStatus.value,
  }, { preserveState: true });
};

const openManualPenaltyModal = () => {
  penaltyForm.value = { user_id: '', rule_id: '', amount: 0, reason: '' };
  manualPenaltyModal.value = true;
};

const submitManualPenalty = () => {
  router.post('/sales-head/sales-kpi/penalties/manual', penaltyForm.value, {
    onSuccess: () => { manualPenaltyModal.value = false; }
  });
};

const confirmPenalty = (penalty) => {
  router.post(`/sales-head/sales-kpi/penalties/${penalty.id}/confirm`);
};

const cancelPenalty = (penalty) => {
  router.post(`/sales-head/sales-kpi/penalties/${penalty.id}/cancel`);
};

const acceptAppeal = (penalty) => {
  router.post(`/sales-head/sales-kpi/penalties/${penalty.id}/appeal`, { decision: 'accepted' });
};

const rejectAppeal = (penalty) => {
  router.post(`/sales-head/sales-kpi/penalties/${penalty.id}/appeal`, { decision: 'rejected' });
};

const openAppealModal = (penalty) => {
  // Navigate to appeal review or open modal
};
</script>
