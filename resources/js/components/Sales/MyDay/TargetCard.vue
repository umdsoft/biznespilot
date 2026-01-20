<template>
  <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
    <div class="flex items-center justify-between mb-2">
      <span class="text-sm text-gray-400">{{ target.name }}</span>
      <span
        class="text-xs font-medium px-2 py-0.5 rounded-full"
        :class="percentageClass"
      >
        {{ target.percentage }}%
      </span>
    </div>

    <div class="flex items-end justify-between">
      <div class="text-2xl font-bold text-white">
        {{ formatValue(target.current) }}
      </div>
      <div class="text-sm text-gray-500">
        / {{ formatValue(target.target) }}
      </div>
    </div>

    <!-- Progress bar -->
    <div class="mt-3 h-2 bg-gray-700 rounded-full overflow-hidden">
      <div
        class="h-full rounded-full transition-all duration-500"
        :class="progressBarClass"
        :style="{ width: `${Math.min(target.percentage, 100)}%` }"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  target: {
    type: Object,
    required: true
  }
})

const percentageClass = computed(() => {
  const p = props.target.percentage
  if (p >= 100) return 'bg-green-500/20 text-green-400'
  if (p >= 80) return 'bg-blue-500/20 text-blue-400'
  if (p >= 50) return 'bg-yellow-500/20 text-yellow-400'
  return 'bg-red-500/20 text-red-400'
})

const progressBarClass = computed(() => {
  const p = props.target.percentage
  if (p >= 100) return 'bg-green-500'
  if (p >= 80) return 'bg-blue-500'
  if (p >= 50) return 'bg-yellow-500'
  return 'bg-red-500'
})

const formatValue = (value) => {
  if (value >= 1000000) {
    return (value / 1000000).toFixed(1) + 'M'
  }
  if (value >= 1000) {
    return (value / 1000).toFixed(1) + 'K'
  }
  return value
}
</script>
