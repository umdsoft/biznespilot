<template>
  <div class="relative">
    <!-- Step indicator -->
    <div class="flex items-center justify-between mb-8">
      <div
        v-for="(s, index) in steps"
        :key="s.id"
        class="flex items-center"
        :class="{ 'flex-1': index < steps.length - 1 }"
      >
        <!-- Step circle -->
        <div
          class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-colors"
          :class="stepClass(index + 1)"
        >
          <CheckIcon
            v-if="currentStep > index + 1"
            class="w-5 h-5 text-white"
          />
          <span v-else class="text-sm font-semibold">{{ index + 1 }}</span>
        </div>

        <!-- Step label -->
        <div class="ml-3 hidden sm:block">
          <p
            class="text-sm font-medium"
            :class="currentStep >= index + 1 ? 'text-gray-900' : 'text-gray-500'"
          >
            {{ s.title }}
          </p>
          <p class="text-xs text-gray-500">{{ s.description }}</p>
        </div>

        <!-- Connector line -->
        <div
          v-if="index < steps.length - 1"
          class="flex-1 h-0.5 mx-4"
          :class="currentStep > index + 1 ? 'bg-indigo-600' : 'bg-gray-200'"
        ></div>
      </div>
    </div>

    <!-- Step content -->
    <div class="bg-white rounded-lg border p-6">
      <slot></slot>
    </div>

    <!-- Navigation buttons -->
    <div class="flex items-center justify-between mt-6">
      <button
        v-if="currentStep > 1"
        @click="$emit('prev')"
        :disabled="loading"
        class="px-4 py-2 text-gray-700 bg-white border rounded-lg hover:bg-gray-50 disabled:opacity-50"
      >
        <ArrowLeftIcon class="w-5 h-5 inline mr-1" />
        Orqaga
      </button>
      <div v-else></div>

      <div class="flex items-center space-x-3">
        <button
          v-if="showSkip && currentStep < steps.length"
          @click="$emit('skip')"
          :disabled="loading"
          class="px-4 py-2 text-gray-600 hover:text-gray-800"
        >
          O'tkazib yuborish
        </button>

        <button
          v-if="currentStep < steps.length"
          @click="$emit('next')"
          :disabled="loading || !canProceed"
          class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="loading" class="flex items-center">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Yuklanmoqda...
          </span>
          <span v-else>
            Keyingisi
            <ArrowRightIcon class="w-5 h-5 inline ml-1" />
          </span>
        </button>

        <button
          v-else
          @click="$emit('complete')"
          :disabled="loading || !canProceed"
          class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="loading" class="flex items-center">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Yaratilmoqda...
          </span>
          <span v-else>
            <CheckIcon class="w-5 h-5 inline mr-1" />
            Yaratish
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  CheckIcon,
  ArrowLeftIcon,
  ArrowRightIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  steps: {
    type: Array,
    required: true,
  },
  currentStep: {
    type: Number,
    default: 1,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  canProceed: {
    type: Boolean,
    default: true,
  },
  showSkip: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['prev', 'next', 'skip', 'complete']);

function stepClass(step) {
  if (props.currentStep > step) {
    return 'bg-indigo-600 border-indigo-600 text-white';
  }
  if (props.currentStep === step) {
    return 'border-indigo-600 text-indigo-600';
  }
  return 'border-gray-300 text-gray-400';
}
</script>
