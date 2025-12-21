<template>
  <div
    :class="[
      'relative p-4 rounded-xl border-2 transition-all cursor-pointer hover:shadow-md',
      cardClass
    ]"
    @click="handleClick"
  >
    <!-- Lock overlay -->
    <div
      v-if="step.is_locked"
      class="absolute inset-0 bg-gray-100/80 rounded-xl flex items-center justify-center z-10"
    >
      <div class="text-center">
        <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
        </svg>
        <p class="text-sm text-gray-500">Avvalgi qadamni yakunlang</p>
      </div>
    </div>

    <!-- Content -->
    <div class="flex items-start gap-4">
      <!-- Icon -->
      <div
        :class="[
          'w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0',
          iconClass
        ]"
      >
        <component :is="getIcon(step.icon)" class="w-6 h-6" />
      </div>

      <!-- Info -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 mb-1">
          <h4 class="font-semibold text-gray-900 truncate">{{ step.name }}</h4>
          <span
            v-if="step.is_required"
            class="px-2 py-0.5 text-xs font-medium bg-red-100 text-red-600 rounded"
          >
            Majburiy
          </span>
        </div>
        <p class="text-sm text-gray-500 line-clamp-2">{{ step.description }}</p>

        <!-- Progress bar -->
        <div v-if="!step.is_completed && step.completion_percent > 0" class="mt-3">
          <div class="flex justify-between text-xs text-gray-500 mb-1">
            <span>Jarayon</span>
            <span>{{ step.completion_percent }}%</span>
          </div>
          <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
            <div
              class="h-full bg-indigo-500 rounded-full transition-all"
              :style="{ width: `${step.completion_percent}%` }"
            ></div>
          </div>
        </div>

        <!-- Validation errors -->
        <div v-if="step.validation?.errors?.length" class="mt-2">
          <ul class="space-y-1">
            <li
              v-for="(error, idx) in step.validation.errors.slice(0, 2)"
              :key="idx"
              class="flex items-center gap-1 text-xs text-red-500"
            >
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              {{ error }}
            </li>
          </ul>
        </div>

        <!-- Estimated time -->
        <div class="flex items-center gap-4 mt-3 text-xs text-gray-400">
          <span class="flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            ~{{ step.estimated_time }} daqiqa
          </span>
        </div>
      </div>

      <!-- Status icon -->
      <div class="flex-shrink-0">
        <div
          v-if="step.is_completed"
          class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center"
        >
          <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
        </div>
        <div
          v-else
          class="w-8 h-8 border-2 border-gray-200 rounded-full flex items-center justify-center"
        >
          <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  step: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['click']);

const cardClass = computed(() => {
  if (props.step.is_completed) {
    return 'border-green-200 bg-green-50/50';
  }
  if (props.step.is_locked) {
    return 'border-gray-200 bg-gray-50';
  }
  return 'border-gray-200 bg-white hover:border-indigo-300';
});

const iconClass = computed(() => {
  if (props.step.is_completed) {
    return 'bg-green-100 text-green-600';
  }
  if (props.step.is_locked) {
    return 'bg-gray-100 text-gray-400';
  }
  return 'bg-indigo-100 text-indigo-600';
});

function handleClick() {
  if (!props.step.is_locked) {
    emit('click', props.step);
  }
}

function getIcon(iconName) {
  // Map icon names to heroicons or return default
  const icons = {
    'building': 'BuildingOfficeIcon',
    'users': 'UsersIcon',
    'chart': 'ChartBarIcon',
    'instagram': 'CameraIcon',
    'telegram': 'ChatBubbleLeftRightIcon',
    'link': 'LinkIcon',
    'ads': 'MegaphoneIcon',
    'problem': 'ExclamationCircleIcon',
    'user': 'UserCircleIcon',
    'competitors': 'ScaleIcon',
    'hypothesis': 'LightBulbIcon',
    'research': 'DocumentSearchIcon'
  };
  return icons[iconName] || 'DocumentIcon';
}
</script>
