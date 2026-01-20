<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-3">
        <div class="text-3xl">{{ icon }}</div>
        <h3 class="text-sm font-medium text-gray-600">{{ name }}</h3>
      </div>
      <div v-if="changePercent !== null" class="flex items-center gap-1">
        <svg
          v-if="changePercent > 0"
          class="w-4 h-4 text-green-500"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
        </svg>
        <svg
          v-else-if="changePercent < 0"
          class="w-4 h-4 text-red-500"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
        </svg>
        <span :class="changeClass" class="text-sm font-medium">
          {{ Math.abs(changePercent) }}%
        </span>
      </div>
    </div>

    <!-- Value -->
    <div class="mb-4">
      <div class="text-3xl font-bold text-gray-900 mb-1">{{ value }}</div>
      <div class="text-sm text-gray-500">{{ t('kpi.target') }}: {{ target }}</div>
    </div>

    <!-- Progress Bar -->
    <div class="mb-3">
      <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
        <span>{{ performanceStatus }}</span>
        <span>{{ performancePercent }}%</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
        <div
          :class="progressBarClass"
          :style="{ width: `${Math.min(performancePercent, 100)}%` }"
          class="h-2 rounded-full transition-all duration-500"
        ></div>
      </div>
    </div>

    <!-- Status Badge -->
    <div class="flex items-center gap-2">
      <span
        :class="statusBadgeClass"
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
      >
        {{ performanceStatus }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
  icon: {
    type: String,
    required: true,
  },
  name: {
    type: String,
    required: true,
  },
  value: {
    type: String,
    required: true,
  },
  target: {
    type: String,
    required: true,
  },
  performancePercent: {
    type: Number,
    required: true,
  },
  performanceStatus: {
    type: String,
    required: true,
  },
  performanceColor: {
    type: String,
    required: true,
  },
  changePercent: {
    type: Number,
    default: null,
  },
});

const progressBarClass = computed(() => {
  const colorMap = {
    green: 'bg-green-500',
    yellow: 'bg-yellow-500',
    red: 'bg-red-500',
  };
  return colorMap[props.performanceColor] || 'bg-gray-500';
});

const statusBadgeClass = computed(() => {
  const colorMap = {
    green: 'bg-green-100 text-green-800',
    yellow: 'bg-yellow-100 text-yellow-800',
    red: 'bg-red-100 text-red-800',
  };
  return colorMap[props.performanceColor] || 'bg-gray-100 text-gray-800';
});

const changeClass = computed(() => {
  if (props.changePercent > 0) return 'text-green-600';
  if (props.changePercent < 0) return 'text-red-600';
  return 'text-gray-600';
});
</script>
