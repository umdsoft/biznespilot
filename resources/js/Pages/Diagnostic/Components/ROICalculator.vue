<template>
  <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
    <!-- Header -->
    <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-green-50 to-emerald-50">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-green-500 rounded-xl flex items-center justify-center">
            <CurrencyDollarIcon class="w-6 h-6 text-white" />
          </div>
          <div>
            <h3 class="font-bold text-gray-900">ROI Kalkulator</h3>
            <p class="text-sm text-gray-500">Har bir harakat uchun aniq qaytim</p>
          </div>
        </div>
        <div class="text-right">
          <p class="text-sm text-gray-500">Umumiy ROI</p>
          <p class="text-2xl font-bold text-green-600">{{ data?.summary?.overall_roi_percent || 0 }}%</p>
        </div>
      </div>

      <!-- Summary Stats -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
        <div class="bg-white/80 rounded-xl p-3 text-center">
          <p class="text-xs text-gray-500">Jami investitsiya</p>
          <p class="text-lg font-bold text-gray-900">{{ formatMoney(data?.summary?.total_investment?.total_uzs) }}</p>
        </div>
        <div class="bg-white/80 rounded-xl p-3 text-center">
          <p class="text-xs text-gray-500">Oylik foyda</p>
          <p class="text-lg font-bold text-green-600">{{ formatMoneySom(data?.summary?.total_monthly_return) }}</p>
        </div>
        <div class="bg-white/80 rounded-xl p-3 text-center">
          <p class="text-xs text-gray-500">ROI</p>
          <p class="text-lg font-bold text-green-600">{{ data?.summary?.overall_roi_percent }}%</p>
        </div>
        <div class="bg-white/80 rounded-xl p-3 text-center">
          <p class="text-xs text-gray-500">Qaytarilish</p>
          <p class="text-lg font-bold text-blue-600">{{ data?.summary?.payback_days }} kun</p>
        </div>
      </div>
    </div>

    <!-- Actions List -->
    <div class="divide-y divide-gray-100">
      <div
        v-for="item in sortedActions"
        :key="item.id"
        class="p-4 hover:bg-gray-50 transition-colors"
      >
        <div class="flex flex-col lg:flex-row lg:items-center gap-4">
          <!-- Priority Badge -->
          <div
            class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
            :class="getPriorityColor(item.priority)"
          >
            {{ item.priority }}
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <p class="font-medium text-gray-900">{{ item.action }}</p>
            <p class="text-sm text-gray-500">{{ item.expected_return?.description }}</p>
          </div>

          <!-- Investment -->
          <div class="text-center px-3">
            <p class="text-xs text-gray-400">Sarf</p>
            <p class="text-sm font-medium text-gray-700">{{ item.investment?.time }}</p>
          </div>

          <!-- Return -->
          <div class="text-center px-3">
            <p class="text-xs text-gray-400">Foyda/oy</p>
            <p class="text-sm font-bold text-green-600">+{{ formatMoneySom(item.expected_return?.monthly_gain) }}</p>
          </div>

          <!-- ROI -->
          <div class="text-center px-3">
            <p class="text-xs text-gray-400">ROI</p>
            <p class="text-lg font-bold" :class="getROIColor(item.roi_percent)">
              {{ formatROI(item.roi_percent) }}%
            </p>
          </div>

          <!-- CTA -->
          <Link
            :href="item.module_route"
            class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 whitespace-nowrap flex-shrink-0"
          >
            Boshlash
          </Link>
        </div>

        <!-- Progress Bar -->
        <div class="mt-3 flex items-center gap-2">
          <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
            <div
              class="h-full rounded-full bg-gradient-to-r from-green-400 to-emerald-500"
              :style="{ width: Math.min(item.roi_percent / 100, 100) + '%' }"
            />
          </div>
          <span
            class="text-xs font-medium px-2 py-0.5 rounded-full"
            :class="getDifficultyClass(item.difficulty)"
          >
            {{ item.difficulty }}
          </span>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!sortedActions.length" class="p-8 text-center">
      <CurrencyDollarIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
      <p class="text-gray-500">ROI hisoblari mavjud emas</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { CurrencyDollarIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  data: {
    type: Object,
    default: () => ({})
  }
});

const sortedActions = computed(() => {
  return [...(props.data?.per_action || [])].sort((a, b) => a.priority - b.priority);
});

function formatMoney(amount) {
  if (!amount) return '0 so\'m';
  return new Intl.NumberFormat('uz-UZ').format(amount) + ' so\'m';
}

function formatMoneySom(amount) {
  if (!amount) return '0 so\'m';
  return new Intl.NumberFormat('uz-UZ').format(amount) + ' so\'m';
}

function formatROI(roi) {
  if (!roi) return '0';
  return new Intl.NumberFormat('uz-UZ').format(roi);
}

function getPriorityColor(priority) {
  if (priority <= 2) return 'bg-red-500';
  if (priority <= 4) return 'bg-yellow-500';
  return 'bg-green-500';
}

function getROIColor(roi) {
  if (roi >= 1000) return 'text-green-600';
  if (roi >= 300) return 'text-emerald-600';
  if (roi >= 100) return 'text-blue-600';
  return 'text-gray-600';
}

function getDifficultyClass(difficulty) {
  const classes = {
    'oson': 'bg-green-100 text-green-700',
    "o'rta": 'bg-yellow-100 text-yellow-700',
    'qiyin': 'bg-red-100 text-red-700'
  };
  return classes[difficulty] || 'bg-gray-100 text-gray-700';
}
</script>
