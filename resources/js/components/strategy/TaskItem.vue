<template>
  <div
    class="flex items-start space-x-3 p-3 rounded-lg transition-colors"
    :class="task.status === 'completed' ? 'bg-green-50' : 'bg-gray-50 hover:bg-gray-100'"
  >
    <!-- Checkbox -->
    <button
      @click="toggleComplete"
      class="mt-0.5 flex-shrink-0"
      :disabled="loading"
    >
      <div
        class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
        :class="task.status === 'completed'
          ? 'bg-green-500 border-green-500'
          : 'border-gray-300 hover:border-indigo-500'"
      >
        <CheckIcon
          v-if="task.status === 'completed'"
          class="w-3 h-3 text-white"
        />
      </div>
    </button>

    <!-- Content -->
    <div class="flex-1 min-w-0">
      <div class="flex items-center space-x-2">
        <h4
          class="font-medium"
          :class="task.status === 'completed' ? 'text-gray-500 line-through' : 'text-gray-900'"
        >
          {{ task.title }}
        </h4>
        <span
          v-if="task.priority !== undefined"
          class="px-1.5 py-0.5 text-xs font-medium rounded"
          :class="priorityClass"
        >
          {{ priorityLabel }}
        </span>
      </div>

      <p
        v-if="task.description"
        class="text-sm mt-1"
        :class="task.status === 'completed' ? 'text-gray-400' : 'text-gray-600'"
      >
        {{ task.description }}
      </p>

      <div class="flex items-center space-x-3 mt-2 text-xs text-gray-500">
        <span v-if="task.day" class="flex items-center">
          <CalendarIcon class="w-3.5 h-3.5 mr-1" />
          {{ dayLabel }}
        </span>
        <span v-if="task.completed_at" class="flex items-center text-green-600">
          <CheckCircleIcon class="w-3.5 h-3.5 mr-1" />
          {{ formatDateTime(task.completed_at) }}
        </span>
      </div>
    </div>

    <!-- Actions -->
    <div v-if="editable && task.status !== 'completed'" class="flex items-center space-x-1">
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
import { ref, computed } from 'vue';
import {
  CheckIcon,
  CalendarIcon,
  CheckCircleIcon,
  PencilIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  task: {
    type: Object,
    required: true,
  },
  editable: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['complete', 'edit', 'delete']);

const loading = ref(false);

const priorityLabel = computed(() => {
  const labels = {
    0: 'Past',
    1: 'O\'rta',
    2: 'Yuqori',
    3: 'Shoshilinch',
  };
  return labels[props.task.priority] || 'O\'rta';
});

const priorityClass = computed(() => {
  const classes = {
    0: 'bg-gray-100 text-gray-700',
    1: 'bg-blue-100 text-blue-700',
    2: 'bg-orange-100 text-orange-700',
    3: 'bg-red-100 text-red-700',
  };
  return classes[props.task.priority] || 'bg-gray-100 text-gray-700';
});

const dayLabel = computed(() => {
  const labels = {
    monday: 'Dushanba',
    tuesday: 'Seshanba',
    wednesday: 'Chorshanba',
    thursday: 'Payshanba',
    friday: 'Juma',
    saturday: 'Shanba',
    sunday: 'Yakshanba',
  };
  return labels[props.task.day] || props.task.day;
});

async function toggleComplete() {
  if (props.task.status === 'completed') return;

  loading.value = true;
  emit('complete');
  loading.value = false;
}

function formatDateTime(dateString) {
  return new Date(dateString).toLocaleString('uz-UZ', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>
