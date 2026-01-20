<template>
  <div
    class="flex items-center gap-3 p-3 rounded-lg bg-gray-700/50 hover:bg-gray-700 transition-colors cursor-pointer"
    :class="{ 'border-l-4 border-red-500': task.is_overdue }"
    @click="$emit('complete', task.id)"
  >
    <!-- Checkbox -->
    <div
      class="w-5 h-5 rounded border-2 flex items-center justify-center flex-shrink-0 transition-colors"
      :class="task.status === 'completed' ? 'bg-green-500 border-green-500' : 'border-gray-500 hover:border-blue-500'"
    >
      <CheckIcon v-if="task.status === 'completed'" class="w-3 h-3 text-white" />
    </div>

    <!-- Content -->
    <div class="flex-1 min-w-0">
      <div class="flex items-center gap-2">
        <span class="font-medium text-white truncate">{{ task.title }}</span>
        <span
          v-if="task.priority === 'high'"
          class="px-1.5 py-0.5 text-xs bg-red-500/20 text-red-400 rounded"
        >
          Muhim
        </span>
      </div>
      <div class="flex items-center gap-2 text-sm text-gray-400 mt-0.5">
        <span>{{ task.due_time }}</span>
        <span v-if="task.lead_name">{{ task.lead_name }}</span>
      </div>
    </div>

    <!-- Type badge -->
    <span
      class="px-2 py-1 text-xs rounded-full"
      :class="typeClass"
    >
      {{ typeLabel }}
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { CheckIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
  task: {
    type: Object,
    required: true
  }
})

defineEmits(['complete'])

const typeClass = computed(() => {
  const classes = {
    call: 'bg-purple-500/20 text-purple-400',
    meeting: 'bg-blue-500/20 text-blue-400',
    email: 'bg-green-500/20 text-green-400',
    demo: 'bg-orange-500/20 text-orange-400',
    proposal: 'bg-yellow-500/20 text-yellow-400'
  }
  return classes[props.task.type] || 'bg-gray-500/20 text-gray-400'
})

const typeLabel = computed(() => {
  const labels = {
    call: "Qo'ng'iroq",
    meeting: 'Uchrashuv',
    email: 'Email',
    demo: 'Demo',
    proposal: 'Taklif'
  }
  return labels[props.task.type] || props.task.type
})
</script>
