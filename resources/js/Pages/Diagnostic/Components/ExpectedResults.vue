<template>
  <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
    <!-- Header -->
    <div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-100">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
          <ChartBarIcon class="w-6 h-6 text-blue-600" />
        </div>
        <div>
          <h3 class="font-semibold text-gray-900">Kutilayotgan Natijalar</h3>
          <p class="text-sm text-gray-500">90 kunlik prognoz</p>
        </div>
      </div>
    </div>

    <!-- Timeline Results -->
    <div class="p-6">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Now -->
        <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
              <ClockIcon class="w-4 h-4 text-gray-600" />
            </div>
            <span class="font-medium text-gray-700">Hozir</span>
          </div>
          <div class="space-y-2">
            <div>
              <p class="text-xs text-gray-500">Ball</p>
              <p class="text-xl font-bold text-gray-700">{{ expectedResults.now?.score || 0 }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Haftalik leadlar</p>
              <p class="text-lg font-semibold text-gray-600">{{ expectedResults.now?.leads_weekly || 0 }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Konversiya</p>
              <p class="text-lg font-semibold text-gray-600">{{ expectedResults.now?.conversion || 0 }}%</p>
            </div>
          </div>
        </div>

        <!-- 30 Days -->
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border border-green-200">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
              <CalendarIcon class="w-4 h-4 text-green-600" />
            </div>
            <span class="font-medium text-green-700">30 kun</span>
          </div>
          <div class="space-y-2">
            <div>
              <p class="text-xs text-gray-500">Ball</p>
              <p class="text-xl font-bold text-green-600">{{ expectedResults['30_days']?.score || 0 }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Haftalik leadlar</p>
              <p class="text-lg font-semibold text-green-600">{{ expectedResults['30_days']?.leads_weekly || 0 }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Daromad o'sishi</p>
              <p class="text-lg font-semibold text-green-600">+{{ expectedResults['30_days']?.revenue_change || 0 }}%</p>
            </div>
          </div>
        </div>

        <!-- 60 Days -->
        <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-4 border border-blue-200">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
              <CalendarIcon class="w-4 h-4 text-blue-600" />
            </div>
            <span class="font-medium text-blue-700">60 kun</span>
          </div>
          <div class="space-y-2">
            <div>
              <p class="text-xs text-gray-500">Ball</p>
              <p class="text-xl font-bold text-blue-600">{{ expectedResults['60_days']?.score || 0 }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Haftalik leadlar</p>
              <p class="text-lg font-semibold text-blue-600">{{ expectedResults['60_days']?.leads_weekly || 0 }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Daromad o'sishi</p>
              <p class="text-lg font-semibold text-blue-600">+{{ expectedResults['60_days']?.revenue_change || 0 }}%</p>
            </div>
          </div>
        </div>

        <!-- 90 Days -->
        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-4 border border-indigo-200">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
              <RocketLaunchIcon class="w-4 h-4 text-indigo-600" />
            </div>
            <span class="font-medium text-indigo-700">90 kun</span>
          </div>
          <div class="space-y-2">
            <div>
              <p class="text-xs text-gray-500">Ball</p>
              <p class="text-xl font-bold text-indigo-600">{{ expectedResults['90_days']?.score || 0 }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Haftalik leadlar</p>
              <p class="text-lg font-semibold text-indigo-600">{{ expectedResults['90_days']?.leads_weekly || 0 }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Daromad o'sishi</p>
              <p class="text-lg font-semibold text-indigo-600">+{{ expectedResults['90_days']?.revenue_change || 0 }}%</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Growth Chart Visual -->
      <div class="mt-6 bg-gray-50 rounded-xl p-6">
        <h4 class="font-medium text-gray-900 mb-4">O'sish dinamikasi</h4>
        <div class="relative h-32">
          <!-- Y axis labels -->
          <div class="absolute left-0 top-0 bottom-0 flex flex-col justify-between text-xs text-gray-400 pr-2">
            <span>{{ maxScore }}</span>
            <span>{{ Math.round(maxScore / 2) }}</span>
            <span>0</span>
          </div>

          <!-- Chart area -->
          <div class="ml-8 h-full flex items-end justify-around gap-4">
            <div
              v-for="(period, key) in chartData"
              :key="key"
              class="flex-1 flex flex-col items-center"
            >
              <div
                class="w-full rounded-t-lg transition-all duration-500"
                :class="period.class"
                :style="{ height: `${(period.score / maxScore) * 100}%` }"
              ></div>
              <span class="text-xs text-gray-500 mt-2">{{ period.label }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { ChartBarIcon, ClockIcon, CalendarIcon, RocketLaunchIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  expectedResults: {
    type: Object,
    required: true,
    default: () => ({
      now: { score: 0, leads_weekly: 0, conversion: 0, revenue_change: 0 },
      '30_days': { score: 0, leads_weekly: 0, conversion: 0, revenue_change: 0 },
      '60_days': { score: 0, leads_weekly: 0, conversion: 0, revenue_change: 0 },
      '90_days': { score: 0, leads_weekly: 0, conversion: 0, revenue_change: 0 },
    }),
  },
});

const maxScore = computed(() => {
  const scores = [
    props.expectedResults.now?.score || 0,
    props.expectedResults['30_days']?.score || 0,
    props.expectedResults['60_days']?.score || 0,
    props.expectedResults['90_days']?.score || 0,
  ];
  return Math.max(100, ...scores);
});

const chartData = computed(() => ({
  now: {
    score: props.expectedResults.now?.score || 0,
    label: 'Hozir',
    class: 'bg-gray-400',
  },
  '30_days': {
    score: props.expectedResults['30_days']?.score || 0,
    label: '30 kun',
    class: 'bg-green-500',
  },
  '60_days': {
    score: props.expectedResults['60_days']?.score || 0,
    label: '60 kun',
    class: 'bg-blue-500',
  },
  '90_days': {
    score: props.expectedResults['90_days']?.score || 0,
    label: '90 kun',
    class: 'bg-indigo-500',
  },
}));
</script>
