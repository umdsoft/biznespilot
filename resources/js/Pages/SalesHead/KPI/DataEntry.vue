<template>
  <SalesHeadLayout :title="t('kpi.daily_data')">
    <div class="max-w-3xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <a href="/sales-head/kpi" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 mb-4">
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          {{ t('kpi.back_to_kpi') }}
        </a>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
          {{ t('kpi.enter_daily_data') }}
        </h2>
        <p class="mt-1 text-gray-600 dark:text-gray-400">
          {{ t('kpi.enter_sales_indicators_daily') }}
        </p>
      </div>

      <!-- Date Selection -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex items-center justify-between">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('common.date') }}</label>
            <input
              v-model="selectedDate"
              type="date"
              :max="today"
              class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              @change="loadEntry"
            >
          </div>
          <div class="text-right">
            <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
              {{ formattedDate }}
            </span>
          </div>
        </div>
      </div>

      <!-- Data Entry Form -->
      <form @submit.prevent="saveEntry" class="space-y-6">
        <!-- Leads Section -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            {{ t('sales.leads') }}
          </h3>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              {{ t('kpi.total_incoming_leads') }}
            </label>
            <input
              v-model.number="form.leads_total"
              type="number"
              min="0"
              class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-lg"
              placeholder="0"
            >
          </div>
        </div>

        <!-- Sales Section -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 text-teal-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            {{ t('kpi.sales') }}
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ t('kpi.new_sales') }}
              </label>
              <input
                v-model.number="form.sales_new"
                type="number"
                min="0"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                placeholder="0"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ t('kpi.repeat_sales') }}
              </label>
              <input
                v-model.number="form.sales_repeat"
                type="number"
                min="0"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                placeholder="0"
              >
            </div>
          </div>
        </div>

        <!-- Revenue Section -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ t('saleshead.revenue') }}
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ t('kpi.new_sales_revenue') }}
              </label>
              <input
                v-model.number="form.revenue_new"
                type="number"
                min="0"
                step="1000"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                placeholder="0"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ t('kpi.repeat_sales_revenue') }}
              </label>
              <input
                v-model.number="form.revenue_repeat"
                type="number"
                min="0"
                step="1000"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                placeholder="0"
              >
            </div>
          </div>
        </div>

        <!-- Summary -->
        <div v-if="totalSales > 0 || totalRevenue > 0" class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800 p-6">
          <h3 class="text-lg font-semibold text-emerald-900 dark:text-emerald-100 mb-4">{{ t('kpi.calculated_indicators') }}</h3>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ t('kpi.total_sales') }}</p>
              <p class="text-xl font-bold text-emerald-900 dark:text-emerald-100">{{ totalSales }}</p>
            </div>
            <div>
              <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ t('analytics.total_revenue') }}</p>
              <p class="text-xl font-bold text-emerald-900 dark:text-emerald-100">{{ formatCurrency(totalRevenue) }}</p>
            </div>
            <div>
              <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ t('kpi.avg_check') }}</p>
              <p class="text-xl font-bold text-emerald-900 dark:text-emerald-100">{{ formatCurrency(avgCheck) }}</p>
            </div>
            <div>
              <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ t('saleshead.conversion') }}</p>
              <p class="text-xl font-bold text-emerald-900 dark:text-emerald-100">{{ conversionRate }}%</p>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3">
          <a
            href="/sales-head/kpi"
            class="px-6 py-3 rounded-lg font-medium bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600"
          >
            {{ t('common.cancel') }}
          </a>
          <button
            type="submit"
            class="px-6 py-3 rounded-lg font-medium bg-emerald-600 text-white hover:bg-emerald-700"
          >
            {{ t('common.save') }}
          </button>
        </div>
      </form>
    </div>
  </SalesHeadLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
  date: String,
  entry: Object,
  panelType: String,
});

const today = new Date().toISOString().split('T')[0];
const selectedDate = ref(props.date || today);

const form = ref({
  leads_total: props.entry?.leads_total || 0,
  sales_new: props.entry?.sales_new || 0,
  sales_repeat: props.entry?.sales_repeat || 0,
  revenue_new: props.entry?.revenue_new || 0,
  revenue_repeat: props.entry?.revenue_repeat || 0,
});

const formattedDate = computed(() => {
  const date = new Date(selectedDate.value);
  return date.toLocaleDateString('uz-UZ', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
});

const totalSales = computed(() => (form.value.sales_new || 0) + (form.value.sales_repeat || 0));
const totalRevenue = computed(() => (form.value.revenue_new || 0) + (form.value.revenue_repeat || 0));
const avgCheck = computed(() => totalSales.value > 0 ? totalRevenue.value / totalSales.value : 0);
const conversionRate = computed(() => {
  if (!form.value.leads_total || form.value.leads_total === 0) return 0;
  return Math.round((totalSales.value / form.value.leads_total) * 100);
});

const formatCurrency = (value) => {
  if (!value) return "0 so'm";
  if (value >= 1000000) {
    return (value / 1000000).toFixed(1) + " mln so'm";
  }
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

const loadEntry = () => {
  router.get('/sales-head/kpi/data-entry', { date: selectedDate.value }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const saveEntry = () => {
  router.post('/sales-head/kpi/daily-entry', {
    date: selectedDate.value,
    ...form.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      // Redirect to KPI page after save
      router.visit('/sales-head/kpi');
    },
  });
};
</script>
