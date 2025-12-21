<template>
  <div class="min-h-[400px] flex flex-col items-center justify-center p-8">
    <!-- Main animation -->
    <div class="relative mb-8">
      <!-- Outer ring -->
      <div class="w-32 h-32 rounded-full border-4 border-gray-200 animate-pulse"></div>

      <!-- Spinning ring -->
      <div
        class="absolute inset-0 w-32 h-32 rounded-full border-4 border-transparent border-t-indigo-500 animate-spin"
        style="animation-duration: 1.5s"
      ></div>

      <!-- Center icon -->
      <div class="absolute inset-0 flex items-center justify-center">
        <component
          :is="currentStepIcon"
          class="w-12 h-12 text-indigo-500 animate-pulse"
        />
      </div>
    </div>

    <!-- Status text -->
    <h3 class="text-xl font-semibold text-gray-900 mb-2">
      {{ currentStepLabel }}
    </h3>
    <p class="text-gray-500 text-center max-w-md mb-8">
      AI biznesingizni chuqur tahlil qilmoqda. Bu jarayon bir necha daqiqa davom etishi mumkin.
    </p>

    <!-- Steps progress -->
    <div class="w-full max-w-md">
      <div class="flex items-center justify-between mb-2">
        <span class="text-sm text-gray-500">Jarayon</span>
        <span class="text-sm text-gray-500">{{ currentStepIndex + 1 }}/{{ steps.length }}</span>
      </div>

      <!-- Progress bar -->
      <div class="relative h-2 bg-gray-200 rounded-full overflow-hidden mb-6">
        <div
          class="absolute left-0 top-0 h-full bg-indigo-500 transition-all duration-500 rounded-full"
          :style="{ width: `${progressPercent}%` }"
        ></div>
      </div>

      <!-- Steps list -->
      <div class="space-y-3">
        <div
          v-for="(step, index) in steps"
          :key="step.code"
          class="flex items-center space-x-3"
        >
          <!-- Step indicator -->
          <div
            class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 transition-all duration-300"
            :class="stepIndicatorClass(index)"
          >
            <CheckIcon v-if="index < currentStepIndex" class="w-4 h-4 text-white" />
            <div
              v-else-if="index === currentStepIndex"
              class="w-3 h-3 bg-white rounded-full animate-pulse"
            ></div>
            <span v-else class="text-xs text-gray-400">{{ index + 1 }}</span>
          </div>

          <!-- Step label -->
          <span
            class="text-sm transition-all duration-300"
            :class="stepLabelClass(index)"
          >
            {{ step.label }}
          </span>

          <!-- Loading indicator for current step -->
          <div v-if="index === currentStepIndex" class="flex-1 flex justify-end">
            <div class="flex space-x-1">
              <div class="w-2 h-2 bg-indigo-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
              <div class="w-2 h-2 bg-indigo-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
              <div class="w-2 h-2 bg-indigo-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Cancel button (optional) -->
    <button
      v-if="showCancel"
      @click="$emit('cancel')"
      class="mt-8 text-sm text-gray-500 hover:text-gray-700 underline"
    >
      Bekor qilish
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  CheckIcon,
  CircleStackIcon,
  CalculatorIcon,
  ChartBarIcon,
  StarIcon,
  SparklesIcon,
  LightBulbIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  currentStep: {
    type: String,
    default: 'aggregating_data',
  },
  showCancel: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['cancel']);

const steps = [
  { code: 'aggregating_data', label: "Ma'lumotlar yig'ilmoqda", icon: CircleStackIcon },
  { code: 'calculating_kpis', label: 'KPI lar hisoblanmoqda', icon: CalculatorIcon },
  { code: 'comparing_benchmarks', label: 'Benchmark bilan taqqoslanmoqda', icon: ChartBarIcon },
  { code: 'calculating_scores', label: 'Ballar hisoblanmoqda', icon: StarIcon },
  { code: 'ai_analysis', label: 'AI tahlil qilmoqda', icon: SparklesIcon },
  { code: 'generating_recommendations', label: 'Tavsiyalar yaratilmoqda', icon: LightBulbIcon },
  { code: 'saving_results', label: 'Natijalar saqlanmoqda', icon: CheckCircleIcon },
];

const currentStepIndex = computed(() => {
  const index = steps.findIndex(s => s.code === props.currentStep);
  return index >= 0 ? index : 0;
});

const currentStepLabel = computed(() => {
  return steps[currentStepIndex.value]?.label || 'Tayyorlanmoqda...';
});

const currentStepIcon = computed(() => {
  return steps[currentStepIndex.value]?.icon || CircleStackIcon;
});

const progressPercent = computed(() => {
  return ((currentStepIndex.value + 1) / steps.length) * 100;
});

function stepIndicatorClass(index) {
  if (index < currentStepIndex.value) {
    return 'bg-green-500';
  }
  if (index === currentStepIndex.value) {
    return 'bg-indigo-500';
  }
  return 'bg-gray-200';
}

function stepLabelClass(index) {
  if (index < currentStepIndex.value) {
    return 'text-green-600 font-medium';
  }
  if (index === currentStepIndex.value) {
    return 'text-indigo-600 font-medium';
  }
  return 'text-gray-400';
}
</script>
