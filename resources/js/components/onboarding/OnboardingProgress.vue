<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <!-- Overall Progress -->
    <div class="mb-6">
      <div class="flex justify-between items-center mb-2">
        <h3 class="text-lg font-semibold text-gray-900">Onboarding Progress</h3>
        <span class="text-2xl font-bold text-indigo-600">{{ overallPercent }}%</span>
      </div>
      <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
        <div
          class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-500"
          :style="{ width: `${overallPercent}%` }"
        ></div>
      </div>
    </div>

    <!-- Phase Indicators -->
    <div class="grid grid-cols-4 gap-4 mb-6">
      <div
        v-for="phase in phases"
        :key="phase.number"
        :class="[
          'relative p-4 rounded-lg border-2 transition-all',
          getPhaseClass(phase)
        ]"
      >
        <div class="flex items-center gap-2 mb-1">
          <span
            :class="[
              'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold',
              getPhaseNumberClass(phase)
            ]"
          >
            {{ phase.number }}
          </span>
          <span class="text-sm font-medium text-gray-700">{{ phase.name }}</span>
        </div>
        <p class="text-xs text-gray-500">{{ phase.description }}</p>

        <!-- Lock icon -->
        <div
          v-if="phase.isLocked"
          class="absolute -top-2 -right-2 w-6 h-6 bg-gray-400 rounded-full flex items-center justify-center"
        >
          <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
          </svg>
        </div>

        <!-- Checkmark for completed -->
        <div
          v-if="phase.status === 'completed'"
          class="absolute -top-2 -right-2 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center"
        >
          <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Categories Progress -->
    <div class="space-y-4">
      <h4 class="text-sm font-medium text-gray-700">Kategoriyalar</h4>

      <div
        v-for="category in categories"
        :key="category.key"
        class="flex items-center gap-4"
      >
        <div class="w-28 flex items-center gap-2">
          <component :is="category.icon" class="w-5 h-5 text-gray-400" />
          <span class="text-sm text-gray-600">{{ category.name }}</span>
        </div>
        <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
          <div
            :class="[
              'h-full rounded-full transition-all duration-500',
              category.percent >= 100 ? 'bg-green-500' : 'bg-indigo-500'
            ]"
            :style="{ width: `${category.percent}%` }"
          ></div>
        </div>
        <span class="text-sm font-medium text-gray-700 w-12 text-right">
          {{ category.percent }}%
        </span>
      </div>
    </div>

    <!-- Start Phase 2 Button -->
    <div v-if="canStartPhase2 && !isPhase2Started" class="mt-6 pt-6 border-t border-gray-100">
      <button
        @click="$emit('start-phase-2')"
        class="w-full py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg font-medium hover:from-indigo-700 hover:to-purple-700 transition-all flex items-center justify-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
        AI Diagnostikani boshlash
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useOnboardingStore } from '@/stores/onboarding';

const store = useOnboardingStore();

const props = defineProps({
  progress: {
    type: Object,
    default: () => ({})
  }
});

defineEmits(['start-phase-2']);

const overallPercent = computed(() => props.progress?.overall_percent || 0);
const canStartPhase2 = computed(() => props.progress?.can_start_phase_2 || false);
const isPhase2Started = computed(() => props.progress?.phase_2?.status !== 'locked');

const phases = computed(() => [
  {
    number: 1,
    name: 'Ma\'lumotlar',
    description: 'Biznes profili',
    status: props.progress?.phase_1?.status || 'pending',
    isLocked: false,
    isCurrent: props.progress?.current_phase === 1
  },
  {
    number: 2,
    name: 'AI Diagnostika',
    description: 'Tahlil',
    status: props.progress?.phase_2?.status || 'locked',
    isLocked: !props.progress?.phase_2?.is_unlocked,
    isCurrent: props.progress?.current_phase === 2
  },
  {
    number: 3,
    name: 'Strategiya',
    description: 'Reja',
    status: props.progress?.phase_3?.status || 'locked',
    isLocked: !props.progress?.phase_3?.is_unlocked,
    isCurrent: props.progress?.current_phase === 3
  },
  {
    number: 4,
    name: 'Launch',
    description: 'Boshlash',
    status: props.progress?.phase_4?.status || 'locked',
    isLocked: !props.progress?.phase_4?.is_unlocked,
    isCurrent: props.progress?.current_phase === 4
  }
]);

const categories = computed(() => {
  const cats = props.progress?.categories || {};
  return [
    {
      key: 'profile',
      name: 'Profil',
      percent: cats.profile?.percent || 0,
      icon: 'UserIcon'
    },
    {
      key: 'integration',
      name: 'Integratsiya',
      percent: cats.integration?.percent || 0,
      icon: 'LinkIcon'
    },
    {
      key: 'framework',
      name: 'Framework',
      percent: cats.framework?.percent || 0,
      icon: 'CubeIcon'
    }
  ];
});

function getPhaseClass(phase) {
  if (phase.status === 'completed') {
    return 'border-green-300 bg-green-50';
  }
  if (phase.isCurrent) {
    return 'border-indigo-300 bg-indigo-50';
  }
  if (phase.isLocked) {
    return 'border-gray-200 bg-gray-50 opacity-60';
  }
  return 'border-gray-200 bg-white';
}

function getPhaseNumberClass(phase) {
  if (phase.status === 'completed') {
    return 'bg-green-500 text-white';
  }
  if (phase.isCurrent) {
    return 'bg-indigo-500 text-white';
  }
  if (phase.isLocked) {
    return 'bg-gray-300 text-gray-500';
  }
  return 'bg-gray-200 text-gray-600';
}
</script>
