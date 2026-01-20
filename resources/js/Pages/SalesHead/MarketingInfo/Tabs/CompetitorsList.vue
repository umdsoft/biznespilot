<template>
  <div class="space-y-4">
    <!-- Empty State -->
    <div v-if="!competitors || competitors.length === 0" class="text-center py-12">
      <ChartBarIcon class="w-12 h-12 mx-auto text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Raqobatchilar yo'q</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Biznes egasi hali raqobatchilarni qo'shmagan.
      </p>
    </div>

    <!-- Competitors Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="competitor in competitors"
        :key="competitor.id"
        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow"
      >
        <!-- Header -->
        <div class="flex items-start gap-4 mb-4">
          <!-- Logo/Avatar -->
          <div class="w-14 h-14 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center overflow-hidden flex-shrink-0">
            <img
              v-if="competitor.logo"
              :src="competitor.logo"
              :alt="competitor.name"
              class="w-full h-full object-cover"
            />
            <span v-else class="text-xl font-bold text-gray-500 dark:text-gray-400">
              {{ competitor.name?.charAt(0)?.toUpperCase() || 'R' }}
            </span>
          </div>

          <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-gray-900 dark:text-white truncate">{{ competitor.name }}</h3>
            <p v-if="competitor.website" class="text-sm text-gray-500 dark:text-gray-400 truncate">
              {{ competitor.website }}
            </p>
          </div>
        </div>

        <!-- Threat Level -->
        <div v-if="competitor.threat_level" class="mb-4">
          <div class="flex items-center justify-between text-sm mb-1">
            <span class="text-gray-500 dark:text-gray-400">Tahdid darajasi</span>
            <span :class="getThreatLevelClass(competitor.threat_level)">
              {{ getThreatLevelLabel(competitor.threat_level) }}
            </span>
          </div>
          <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
            <div
              :class="getThreatLevelBarClass(competitor.threat_level)"
              :style="{ width: getThreatLevelWidth(competitor.threat_level) }"
              class="h-full rounded-full transition-all"
            />
          </div>
        </div>

        <!-- Key Stats -->
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div v-if="competitor.market_share" class="text-center p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ competitor.market_share }}%</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Bozor ulushi</p>
          </div>
          <div v-if="competitor.employee_count" class="text-center p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ competitor.employee_count }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Xodimlar</p>
          </div>
        </div>

        <!-- Strengths -->
        <div v-if="competitor.strengths && competitor.strengths.length > 0" class="mb-4">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kuchli tomonlari:</p>
          <div class="flex flex-wrap gap-1">
            <span
              v-for="(strength, idx) in competitor.strengths.slice(0, 3)"
              :key="idx"
              class="px-2 py-0.5 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full"
            >
              {{ strength }}
            </span>
          </div>
        </div>

        <!-- View Button -->
        <a
          :href="route('sales-head.competitors.show', competitor.id)"
          class="block w-full text-center py-2 px-4 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors text-sm font-medium"
        >
          Batafsil ko'rish
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ChartBarIcon } from '@heroicons/vue/24/outline';

defineProps({
  competitors: { type: Array, default: () => [] },
  panelType: { type: String, default: 'saleshead' },
  readOnly: { type: Boolean, default: true },
});

function getThreatLevelLabel(level) {
  const labels = {
    low: 'Past',
    medium: "O'rtacha",
    high: 'Yuqori',
    critical: 'Jiddiy',
  };
  return labels[level] || level;
}

function getThreatLevelClass(level) {
  const classes = {
    low: 'text-green-600 dark:text-green-400',
    medium: 'text-yellow-600 dark:text-yellow-400',
    high: 'text-orange-600 dark:text-orange-400',
    critical: 'text-red-600 dark:text-red-400',
  };
  return classes[level] || 'text-gray-600';
}

function getThreatLevelBarClass(level) {
  const classes = {
    low: 'bg-green-500',
    medium: 'bg-yellow-500',
    high: 'bg-orange-500',
    critical: 'bg-red-500',
  };
  return classes[level] || 'bg-gray-500';
}

function getThreatLevelWidth(level) {
  const widths = {
    low: '25%',
    medium: '50%',
    high: '75%',
    critical: '100%',
  };
  return widths[level] || '0%';
}
</script>
