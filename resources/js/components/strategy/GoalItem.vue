<template>
  <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
    <div
      class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
      :class="priorityBgClass"
    >
      <span class="text-sm font-medium" :class="priorityTextClass">{{ index + 1 }}</span>
    </div>

    <div class="flex-1 min-w-0">
      <div class="flex items-center justify-between">
        <h4 class="font-medium text-gray-900">{{ goal.name }}</h4>
        <span
          v-if="goal.target"
          class="text-sm font-medium text-indigo-600"
        >
          {{ goal.target }} {{ goal.metric }}
        </span>
      </div>

      <p v-if="goal.description" class="text-sm text-gray-600 mt-1">
        {{ goal.description }}
      </p>

      <!-- Progress bar if has target -->
      <div v-if="goal.target && goal.current !== undefined" class="mt-2">
        <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
          <span>{{ goal.current }} / {{ goal.target }}</span>
          <span>{{ progressPercent }}%</span>
        </div>
        <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
          <div
            class="h-full bg-indigo-500 rounded-full transition-all"
            :style="{ width: `${Math.min(progressPercent, 100)}%` }"
          ></div>
        </div>
      </div>

      <!-- Deadline -->
      <div v-if="goal.deadline" class="mt-2 flex items-center text-xs text-gray-500">
        <CalendarIcon class="w-3.5 h-3.5 mr-1" />
        {{ formatDate(goal.deadline) }}
      </div>
    </div>

    <!-- Actions -->
    <div v-if="editable" class="flex items-center space-x-1">
      <button
        @click="$emit('edit')"
        class="p-1 text-gray-400 hover:text-gray-600"
      >
        <PencilIcon class="w-4 h-4" />
      </button>
      <button
        @click="$emit('delete')"
        class="p-1 text-gray-400 hover:text-red-600"
      >
        <TrashIcon class="w-4 h-4" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { CalendarIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  goal: {
    type: Object,
    required: true,
  },
  index: {
    type: Number,
    default: 0,
  },
  editable: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['edit', 'delete']);

const priorityBgClass = computed(() => {
  const priority = props.goal.priority || props.index + 1;
  if (priority <= 1) return 'bg-red-100';
  if (priority <= 2) return 'bg-orange-100';
  if (priority <= 3) return 'bg-yellow-100';
  return 'bg-gray-100';
});

const priorityTextClass = computed(() => {
  const priority = props.goal.priority || props.index + 1;
  if (priority <= 1) return 'text-red-700';
  if (priority <= 2) return 'text-orange-700';
  if (priority <= 3) return 'text-yellow-700';
  return 'text-gray-700';
});

const progressPercent = computed(() => {
  if (!props.goal.target || props.goal.current === undefined) return 0;
  return Math.round((props.goal.current / props.goal.target) * 100);
});

function formatDate(date) {
  return new Date(date).toLocaleDateString('uz-UZ', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
}
</script>
