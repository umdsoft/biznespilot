<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ t('kpi.daily_data_history') }}</h3>
      <div class="flex items-center gap-4">
        <!-- Date Range Filter -->
        <div class="flex items-center gap-2">
          <input
            type="date"
            v-model="filters.startDate"
            class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
          />
          <span class="text-gray-500">-</span>
          <input
            type="date"
            v-model="filters.endDate"
            class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
          />
        </div>
        <button
          @click="loadData"
          class="px-4 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors"
        >
          {{ t('kpi.search') }}
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.date') }}</th>
            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider" colspan="4">{{ t('kpi.leads') }}</th>
            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider" colspan="2">{{ t('kpi.expense') }}</th>
            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider" colspan="2">{{ t('kpi.sales') }}</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.revenue') }}</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.conv') }}</th>
            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.actions') }}</th>
          </tr>
          <tr class="bg-gray-100 dark:bg-gray-600">
            <th class="px-4 py-2"></th>
            <th class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">Dig</th>
            <th class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">Off</th>
            <th class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">Ref</th>
            <th class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">Org</th>
            <th class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">Dig</th>
            <th class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">Off</th>
            <th class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">{{ t('kpi.new_short') }}</th>
            <th class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">{{ t('kpi.repeat_short') }}</th>
            <th class="px-4 py-2"></th>
            <th class="px-4 py-2"></th>
            <th class="px-4 py-2"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          <tr
            v-for="entry in entries"
            :key="entry.id"
            class="hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            <td class="px-4 py-3 whitespace-nowrap">
              <div class="font-medium text-gray-900 dark:text-gray-100">{{ formatDate(entry.date) }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">{{ entry.day_name }}</div>
            </td>
            <td class="px-4 py-3 text-center text-sm text-blue-600 dark:text-blue-400">{{ entry.leads_digital }}</td>
            <td class="px-4 py-3 text-center text-sm text-orange-600 dark:text-orange-400">{{ entry.leads_offline }}</td>
            <td class="px-4 py-3 text-center text-sm text-green-600 dark:text-green-400">{{ entry.leads_referral }}</td>
            <td class="px-4 py-3 text-center text-sm text-purple-600 dark:text-purple-400">{{ entry.leads_organic }}</td>
            <td class="px-4 py-3 text-center text-sm text-gray-900 dark:text-gray-100">{{ formatMoney(entry.spend_digital) }}</td>
            <td class="px-4 py-3 text-center text-sm text-gray-900 dark:text-gray-100">{{ formatMoney(entry.spend_offline) }}</td>
            <td class="px-4 py-3 text-center text-sm text-gray-900 dark:text-gray-100">{{ entry.sales_new }}</td>
            <td class="px-4 py-3 text-center text-sm text-gray-900 dark:text-gray-100">{{ entry.sales_repeat }}</td>
            <td class="px-4 py-3 text-right text-sm font-medium text-gray-900 dark:text-gray-100">{{ formatMoney(entry.revenue_total) }}</td>
            <td class="px-4 py-3 text-right">
              <span
                class="px-2 py-1 rounded-full text-xs font-medium"
                :class="getConversionClass(entry.conversion_rate)"
              >
                {{ parseFloat(entry.conversion_rate || 0).toFixed(1) }}%
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              <div class="flex items-center justify-center gap-2">
                <button
                  @click="editEntry(entry)"
                  class="p-1.5 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900 rounded-lg transition-colors"
                  :title="t('common.edit')"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button
                  @click="deleteEntry(entry)"
                  class="p-1.5 text-red-600 hover:bg-red-100 dark:hover:bg-red-900 rounded-lg transition-colors"
                  :title="t('common.delete')"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="entries.length === 0">
            <td colspan="12" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
              {{ t('kpi.no_data_found') }}
            </td>
          </tr>
        </tbody>
        <!-- Totals Row -->
        <tfoot v-if="entries.length > 0" class="bg-gray-100 dark:bg-gray-700">
          <tr class="font-semibold">
            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ t('kpi.total') }}</td>
            <td class="px-4 py-3 text-center text-blue-600">{{ totals.leads_digital }}</td>
            <td class="px-4 py-3 text-center text-orange-600">{{ totals.leads_offline }}</td>
            <td class="px-4 py-3 text-center text-green-600">{{ totals.leads_referral }}</td>
            <td class="px-4 py-3 text-center text-purple-600">{{ totals.leads_organic }}</td>
            <td class="px-4 py-3 text-center text-gray-900 dark:text-gray-100">{{ formatMoney(totals.spend_digital) }}</td>
            <td class="px-4 py-3 text-center text-gray-900 dark:text-gray-100">{{ formatMoney(totals.spend_offline) }}</td>
            <td class="px-4 py-3 text-center text-gray-900 dark:text-gray-100">{{ totals.sales_new }}</td>
            <td class="px-4 py-3 text-center text-gray-900 dark:text-gray-100">{{ totals.sales_repeat }}</td>
            <td class="px-4 py-3 text-right text-gray-900 dark:text-gray-100">{{ formatMoney(totals.revenue_total) }}</td>
            <td class="px-4 py-3 text-right">
              <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {{ avgConversion.toFixed(1) }}%
              </span>
            </td>
            <td class="px-4 py-3"></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.lastPage > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
      <div class="text-sm text-gray-500 dark:text-gray-400">
        {{ pagination.from }} - {{ pagination.to }} / {{ pagination.total }}
      </div>
      <div class="flex gap-2">
        <button
          @click="changePage(pagination.currentPage - 1)"
          :disabled="pagination.currentPage === 1"
          class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          {{ t('common.previous') }}
        </button>
        <button
          @click="changePage(pagination.currentPage + 1)"
          :disabled="pagination.currentPage === pagination.lastPage"
          class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          {{ t('common.next') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
  businessId: {
    type: [String, Number],
    required: true
  }
});

const emit = defineEmits(['edit']);

// State
const isLoading = ref(false);
const entries = ref([]);
const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  from: 0,
  to: 0,
  total: 0
});

// Filters
const filters = ref({
  startDate: new Date(new Date().setDate(new Date().getDate() - 30)).toISOString().split('T')[0],
  endDate: new Date().toISOString().split('T')[0]
});

// Computed
const totals = computed(() => {
  return entries.value.reduce((acc, entry) => {
    acc.leads_digital += entry.leads_digital || 0;
    acc.leads_offline += entry.leads_offline || 0;
    acc.leads_referral += entry.leads_referral || 0;
    acc.leads_organic += entry.leads_organic || 0;
    acc.spend_digital += parseFloat(entry.spend_digital) || 0;
    acc.spend_offline += parseFloat(entry.spend_offline) || 0;
    acc.sales_new += entry.sales_new || 0;
    acc.sales_repeat += entry.sales_repeat || 0;
    acc.revenue_total += parseFloat(entry.revenue_total) || 0;
    return acc;
  }, {
    leads_digital: 0,
    leads_offline: 0,
    leads_referral: 0,
    leads_organic: 0,
    spend_digital: 0,
    spend_offline: 0,
    sales_new: 0,
    sales_repeat: 0,
    revenue_total: 0
  });
});

const avgConversion = computed(() => {
  const totalLeads = totals.value.leads_digital + totals.value.leads_offline + totals.value.leads_referral + totals.value.leads_organic;
  const totalSales = totals.value.sales_new + totals.value.sales_repeat;
  if (totalLeads === 0) return 0;
  return (totalSales / totalLeads) * 100;
});

// Methods
const formatDate = (dateStr) => {
  const date = new Date(dateStr);
  return date.toLocaleDateString('uz-UZ', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatMoney = (value) => {
  if (!value) return "0";
  return new Intl.NumberFormat('uz-UZ').format(value);
};

const getConversionClass = (rate) => {
  const r = parseFloat(rate) || 0;
  if (r >= 15) return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
  if (r >= 10) return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
  if (r >= 5) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
  return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
};

const loadData = async () => {
  if (!props.businessId) {
    isLoading.value = false;
    return;
  }
  isLoading.value = true;
  try {
    const response = await axios.get(`/api/v1/businesses/${props.businessId}/kpi-entry/daily`, {
      params: {
        start_date: filters.value.startDate,
        end_date: filters.value.endDate,
        page: pagination.value.currentPage,
        per_page: 15
      }
    });

    if (response.data.success) {
      entries.value = response.data.data.data || [];
      pagination.value = {
        currentPage: response.data.data.current_page,
        lastPage: response.data.data.last_page,
        from: response.data.data.from || 0,
        to: response.data.data.to || 0,
        total: response.data.data.total || 0
      };
    }
  } catch (error) {
    console.error('History load error:', error);
  } finally {
    isLoading.value = false;
  }
};

const changePage = (page) => {
  pagination.value.currentPage = page;
  loadData();
};

const editEntry = (entry) => {
  emit('edit', entry);
};

const deleteEntry = async (entry) => {
  if (!confirm('Bu ma\'lumotni o\'chirmoqchimisiz?')) return;

  try {
    const dateStr = entry.date.split('T')[0];
    await axios.delete(`/api/v1/businesses/${props.businessId}/kpi-entry/daily/${dateStr}`);
    loadData();
  } catch (error) {
    console.error('Error deleting:', error);
  }
};

// Lifecycle
onMounted(() => {
  loadData();
});
</script>
