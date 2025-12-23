<template>
  <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
    <!-- Header -->
    <div class="p-6 bg-gradient-to-r from-indigo-50 to-purple-50 border-b border-gray-100">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
            <ClipboardDocumentListIcon class="w-6 h-6 text-indigo-600" />
          </div>
          <div>
            <h3 class="font-semibold text-gray-900">90 kunlik Harakat Rejasi</h3>
            <p class="text-sm text-gray-500">{{ totalSteps }} qadam, ~{{ totalTime }} soat</p>
          </div>
        </div>
        <div v-if="potentialSavings" class="text-right">
          <p class="text-sm text-gray-500">Potensial tejash</p>
          <p class="text-lg font-bold text-green-600">{{ formatCurrency(potentialSavings) }}</p>
        </div>
      </div>
    </div>

    <!-- Steps -->
    <div class="divide-y divide-gray-100">
      <div
        v-for="(step, index) in displaySteps"
        :key="index"
        class="p-4 hover:bg-gray-50 transition-colors"
      >
        <div class="flex items-start gap-4">
          <!-- Step number -->
          <div
            class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
            :class="stepNumberClass(step.priority)"
          >
            {{ index + 1 }}
          </div>

          <!-- Step content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <h4 class="font-medium text-gray-900">{{ step.title }}</h4>
              <span
                v-if="step.priority"
                class="px-2 py-0.5 text-xs rounded-full"
                :class="priorityBadgeClass(step.priority)"
              >
                {{ priorityLabel(step.priority) }}
              </span>
            </div>
            <p class="text-sm text-gray-600 line-clamp-2">{{ step.description }}</p>
            <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
              <span v-if="step.time_hours" class="flex items-center gap-1">
                <ClockIcon class="w-3.5 h-3.5" />
                {{ step.time_hours }} soat
              </span>
              <span v-if="step.module" class="flex items-center gap-1">
                <CubeIcon class="w-3.5 h-3.5" />
                {{ step.module }}
              </span>
              <span v-if="step.potential_savings" class="flex items-center gap-1 text-green-600">
                <ArrowTrendingUpIcon class="w-3.5 h-3.5" />
                +{{ formatCurrency(step.potential_savings) }}
              </span>
            </div>
          </div>

          <!-- Arrow -->
          <ChevronRightIcon class="w-5 h-5 text-gray-400 flex-shrink-0" />
        </div>
      </div>
    </div>

    <!-- Show more -->
    <div v-if="steps.length > maxSteps" class="p-4 border-t border-gray-100 text-center">
      <button
        @click="showAll = !showAll"
        class="text-sm text-indigo-600 hover:text-indigo-700 font-medium"
      >
        {{ showAll ? 'Kamroq ko\'rsatish' : `Yana ${steps.length - maxSteps} qadam ko'rsatish` }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import {
  ClipboardDocumentListIcon,
  ClockIcon,
  CubeIcon,
  ChevronRightIcon,
  ArrowTrendingUpIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  actionPlan: {
    type: Object,
    default: () => ({}),
  },
  maxSteps: {
    type: Number,
    default: 5,
  },
});

const showAll = ref(false);

const steps = computed(() => props.actionPlan?.steps || []);
const totalTime = computed(() => props.actionPlan?.total_time_hours || 0);
const potentialSavings = computed(() => props.actionPlan?.total_potential_savings || 0);
const totalSteps = computed(() => steps.value.length);

const displaySteps = computed(() => {
  if (showAll.value || steps.value.length <= props.maxSteps) {
    return steps.value;
  }
  return steps.value.slice(0, props.maxSteps);
});

function formatCurrency(amount) {
  return new Intl.NumberFormat('uz-UZ', {
    style: 'decimal',
    maximumFractionDigits: 0,
  }).format(amount) + ' UZS';
}

function stepNumberClass(priority) {
  if (priority === 'critical') return 'bg-red-100 text-red-700';
  if (priority === 'high') return 'bg-orange-100 text-orange-700';
  if (priority === 'medium') return 'bg-yellow-100 text-yellow-700';
  return 'bg-gray-100 text-gray-700';
}

function priorityBadgeClass(priority) {
  if (priority === 'critical') return 'bg-red-100 text-red-700';
  if (priority === 'high') return 'bg-orange-100 text-orange-700';
  if (priority === 'medium') return 'bg-yellow-100 text-yellow-700';
  return 'bg-gray-100 text-gray-700';
}

function priorityLabel(priority) {
  const labels = {
    critical: 'Juda muhim',
    high: 'Muhim',
    medium: 'O\'rta',
    low: 'Past',
  };
  return labels[priority] || priority;
}
</script>
