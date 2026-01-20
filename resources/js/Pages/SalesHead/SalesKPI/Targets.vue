<template>
  <SalesHeadLayout title="Maqsadlar">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Maqsadlar</h2>
          <p class="mt-2 text-gray-600 dark:text-gray-400">Jamoa a'zolari uchun individual maqsadlarni belgilash</p>
        </div>
        <div class="flex items-center gap-3">
          <select v-model="selectedMonth" @change="filterByPeriod"
                  class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800">
            <option v-for="m in 12" :key="m" :value="m">{{ getMonthName(m) }}</option>
          </select>
          <select v-model="selectedYear" @change="filterByPeriod"
                  class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800">
            <option :value="currentYear - 1">{{ currentYear - 1 }}</option>
            <option :value="currentYear">{{ currentYear }}</option>
            <option :value="currentYear + 1">{{ currentYear + 1 }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 mb-1">Jami xodimlar</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ teamMembers.length }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 mb-1">Maqsad belgilangan</p>
        <p class="text-2xl font-bold text-green-600">{{ targetedCount }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 mb-1">Maqsad belgilanmagan</p>
        <p class="text-2xl font-bold text-yellow-600">{{ teamMembers.length - targetedCount }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 mb-1">Faol KPIlar</p>
        <p class="text-2xl font-bold text-emerald-600">{{ kpiSettings.length }}</p>
      </div>
    </div>

    <!-- Team Targets Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <h3 class="font-semibold text-gray-900 dark:text-white">Jamoa maqsadlari</h3>
        <button @click="openBulkModal"
                class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm">
          Hammaga birdan belgilash
        </button>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Xodim</th>
              <th v-for="kpi in kpiSettings" :key="kpi.id"
                  class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                {{ kpi.name }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amal</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="member in teamMembers" :key="member.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-700 dark:text-emerald-400 font-medium">
                    {{ getInitials(member.name) }}
                  </div>
                  <div class="ml-3">
                    <p class="font-medium text-gray-900 dark:text-white">{{ member.name }}</p>
                    <p class="text-sm text-gray-500">{{ member.position || 'Operator' }}</p>
                  </div>
                </div>
              </td>
              <td v-for="kpi in kpiSettings" :key="kpi.id" class="px-4 py-4 text-center">
                <div class="flex flex-col items-center">
                  <span class="font-medium text-gray-900 dark:text-white">
                    {{ getUserTarget(member.id, kpi.id)?.target_value || '-' }}
                  </span>
                  <span class="text-xs text-gray-500">{{ kpi.measurement_unit }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-center">
                <button @click="openEditModal(member)"
                        class="px-3 py-1 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                  Tahrirlash
                </button>
              </td>
            </tr>
            <tr v-if="teamMembers.length === 0">
              <td :colspan="kpiSettings.length + 2" class="px-6 py-12 text-center text-gray-500">
                Jamoa a'zolari topilmadi
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Edit Targets Modal -->
    <div v-if="editModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Maqsadlarni tahrirlash</h3>
        <p class="text-gray-500 mb-4">{{ selectedMember?.name }} uchun {{ getMonthName(selectedMonth) }} {{ selectedYear }}</p>

        <form @submit.prevent="saveTargets" class="space-y-4">
          <div v-for="kpi in kpiSettings" :key="kpi.id" class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div class="flex-1">
              <p class="font-medium text-gray-900 dark:text-white">{{ kpi.name }}</p>
              <p class="text-sm text-gray-500">Standart: {{ kpi.target_value }} {{ kpi.measurement_unit }}</p>
            </div>
            <div class="w-32">
              <input v-model.number="targetForm[kpi.id]" type="number"
                     :placeholder="kpi.target_value"
                     class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-center">
            </div>
            <span class="text-sm text-gray-500 w-12">{{ kpi.measurement_unit }}</span>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button type="button" @click="editModal = false"
                    class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
              Bekor qilish
            </button>
            <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
              Saqlash
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Bulk Set Modal -->
    <div v-if="bulkModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Ommaviy maqsad belgilash</h3>
        <p class="text-gray-500 mb-4">Barcha jamoa a'zolariga bir xil maqsadlarni belgilash</p>

        <form @submit.prevent="saveBulkTargets" class="space-y-4">
          <div v-for="kpi in kpiSettings" :key="kpi.id" class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div class="flex-1">
              <p class="font-medium text-gray-900 dark:text-white">{{ kpi.name }}</p>
              <p class="text-sm text-gray-500">Standart: {{ kpi.target_value }} {{ kpi.measurement_unit }}</p>
            </div>
            <div class="w-32">
              <input v-model.number="bulkForm[kpi.id]" type="number"
                     :placeholder="kpi.target_value"
                     class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-center">
            </div>
            <span class="text-sm text-gray-500 w-12">{{ kpi.measurement_unit }}</span>
          </div>

          <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
            <p class="text-sm text-yellow-700 dark:text-yellow-400">
              Bu amal {{ teamMembers.length }} ta xodimning {{ getMonthName(selectedMonth) }} {{ selectedYear }} uchun maqsadlarini yangilaydi.
            </p>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button type="button" @click="bulkModal = false"
                    class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
              Bekor qilish
            </button>
            <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
              Hammaga belgilash
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
  teamMembers: Array,
  kpiSettings: Array,
  targets: Array,
  month: Number,
  year: Number,
  panelType: String,
});

const currentYear = new Date().getFullYear();
const selectedMonth = ref(props.month || new Date().getMonth() + 1);
const selectedYear = ref(props.year || currentYear);
const editModal = ref(false);
const bulkModal = ref(false);
const selectedMember = ref(null);
const targetForm = ref({});
const bulkForm = ref({});

const targetedCount = computed(() => {
  const usersWithTargets = new Set(props.targets.map(t => t.user_id));
  return usersWithTargets.size;
});

const getMonthName = (m) => {
  const months = ['Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun', 'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr'];
  return months[m - 1];
};

const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';

const getUserTarget = (userId, kpiId) => {
  return props.targets.find(t => t.user_id === userId && t.kpi_setting_id === kpiId);
};

const filterByPeriod = () => {
  router.get('/sales-head/sales-kpi/targets', { month: selectedMonth.value, year: selectedYear.value }, { preserveState: true });
};

const openEditModal = (member) => {
  selectedMember.value = member;
  targetForm.value = {};
  props.kpiSettings.forEach(kpi => {
    const existing = getUserTarget(member.id, kpi.id);
    targetForm.value[kpi.id] = existing?.target_value || kpi.target_value;
  });
  editModal.value = true;
};

const openBulkModal = () => {
  bulkForm.value = {};
  props.kpiSettings.forEach(kpi => {
    bulkForm.value[kpi.id] = kpi.target_value;
  });
  bulkModal.value = true;
};

const saveTargets = () => {
  router.post('/sales-head/sales-kpi/targets', {
    user_id: selectedMember.value.id,
    month: selectedMonth.value,
    year: selectedYear.value,
    targets: targetForm.value,
  }, {
    onSuccess: () => { editModal.value = false; }
  });
};

const saveBulkTargets = () => {
  router.post('/sales-head/sales-kpi/targets/bulk', {
    month: selectedMonth.value,
    year: selectedYear.value,
    targets: bulkForm.value,
  }, {
    onSuccess: () => { bulkModal.value = false; }
  });
};
</script>
