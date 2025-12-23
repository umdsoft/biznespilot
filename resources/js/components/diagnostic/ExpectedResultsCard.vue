<template>
  <div class="bg-gradient-to-br from-green-50 via-emerald-50 to-teal-50 rounded-2xl border border-green-200/50 p-6 overflow-hidden relative">
    <!-- Background decoration -->
    <div class="absolute top-0 right-0 w-32 h-32 bg-green-500/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>

    <div class="relative">
      <!-- Header -->
      <div class="flex items-center gap-3 mb-6">
        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
          <RocketLaunchIcon class="w-6 h-6 text-green-600" />
        </div>
        <div>
          <h3 class="font-semibold text-gray-900">Kutilgan natijalar</h3>
          <p class="text-sm text-gray-500">Rejani bajarganingizda</p>
        </div>
      </div>

      <!-- Timeline -->
      <div class="relative">
        <!-- Progress line -->
        <div class="absolute left-4 top-6 bottom-6 w-0.5 bg-green-200"></div>

        <!-- 30 days -->
        <div class="relative flex items-start gap-4 mb-6">
          <div class="w-8 h-8 rounded-full bg-green-200 border-4 border-green-50 flex items-center justify-center z-10">
            <span class="text-xs font-bold text-green-700">30</span>
          </div>
          <div class="flex-1">
            <h4 class="font-medium text-gray-900 mb-2">30 kundan keyin</h4>
            <div class="grid grid-cols-2 gap-3">
              <div v-if="results30Days.health_score_improvement" class="bg-white/70 rounded-lg p-3">
                <p class="text-xs text-gray-500">Sog'liq balli</p>
                <p class="font-semibold text-green-600">+{{ results30Days.health_score_improvement }} ball</p>
              </div>
              <div v-if="results30Days.conversion_improvement" class="bg-white/70 rounded-lg p-3">
                <p class="text-xs text-gray-500">Konversiya</p>
                <p class="font-semibold text-green-600">+{{ results30Days.conversion_improvement }}%</p>
              </div>
            </div>
            <p v-if="results30Days.description" class="text-sm text-gray-600 mt-2">{{ results30Days.description }}</p>
          </div>
        </div>

        <!-- 60 days -->
        <div class="relative flex items-start gap-4 mb-6">
          <div class="w-8 h-8 rounded-full bg-green-300 border-4 border-green-50 flex items-center justify-center z-10">
            <span class="text-xs font-bold text-green-800">60</span>
          </div>
          <div class="flex-1">
            <h4 class="font-medium text-gray-900 mb-2">60 kundan keyin</h4>
            <div class="grid grid-cols-2 gap-3">
              <div v-if="results60Days.health_score_improvement" class="bg-white/70 rounded-lg p-3">
                <p class="text-xs text-gray-500">Sog'liq balli</p>
                <p class="font-semibold text-green-600">+{{ results60Days.health_score_improvement }} ball</p>
              </div>
              <div v-if="results60Days.revenue_improvement" class="bg-white/70 rounded-lg p-3">
                <p class="text-xs text-gray-500">Daromad</p>
                <p class="font-semibold text-green-600">+{{ results60Days.revenue_improvement }}%</p>
              </div>
            </div>
            <p v-if="results60Days.description" class="text-sm text-gray-600 mt-2">{{ results60Days.description }}</p>
          </div>
        </div>

        <!-- 90 days -->
        <div class="relative flex items-start gap-4">
          <div class="w-8 h-8 rounded-full bg-green-500 border-4 border-green-50 flex items-center justify-center z-10">
            <span class="text-xs font-bold text-white">90</span>
          </div>
          <div class="flex-1">
            <h4 class="font-medium text-gray-900 mb-2">90 kundan keyin</h4>
            <div class="grid grid-cols-2 gap-3">
              <div v-if="results90Days.health_score_improvement" class="bg-white/70 rounded-lg p-3">
                <p class="text-xs text-gray-500">Sog'liq balli</p>
                <p class="font-semibold text-green-600">+{{ results90Days.health_score_improvement }} ball</p>
              </div>
              <div v-if="results90Days.total_revenue_increase" class="bg-white/70 rounded-lg p-3">
                <p class="text-xs text-gray-500">Umumiy o'sish</p>
                <p class="font-semibold text-green-600">{{ formatCurrency(results90Days.total_revenue_increase) }}</p>
              </div>
            </div>
            <p v-if="results90Days.description" class="text-sm text-gray-600 mt-2">{{ results90Days.description }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { RocketLaunchIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  expectedResults: {
    type: Object,
    default: () => ({}),
  },
});

const results30Days = computed(() => props.expectedResults?.['30_days'] || {});
const results60Days = computed(() => props.expectedResults?.['60_days'] || {});
const results90Days = computed(() => props.expectedResults?.['90_days'] || {});

function formatCurrency(amount) {
  if (!amount) return '0 UZS';
  return new Intl.NumberFormat('uz-UZ', {
    style: 'decimal',
    maximumFractionDigits: 0,
  }).format(amount) + ' UZS';
}
</script>
