<template>
  <div
    class="flex items-center gap-3 p-3 rounded-lg bg-gradient-to-r from-red-500/10 to-orange-500/10 border border-red-500/20 hover:border-red-500/40 transition-colors cursor-pointer"
    @click="$emit('click')"
  >
    <!-- Score badge -->
    <div
      class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold"
      :class="scoreClass"
    >
      {{ lead.score }}
    </div>

    <!-- Content -->
    <div class="flex-1 min-w-0">
      <div class="font-medium text-white truncate">{{ lead.name }}</div>
      <div class="text-sm text-gray-400 truncate">
        {{ lead.company || lead.source || 'Manba noma\'lum' }}
      </div>
    </div>

    <!-- Value -->
    <div v-if="lead.estimated_value" class="text-right">
      <div class="text-sm font-medium text-green-400">
        {{ formatValue(lead.estimated_value) }}
      </div>
      <div class="text-xs text-gray-500">so'm</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  lead: {
    type: Object,
    required: true
  }
})

defineEmits(['click'])

const scoreClass = computed(() => {
  const score = props.lead.score
  if (score >= 90) return 'bg-red-500 text-white'
  if (score >= 80) return 'bg-orange-500 text-white'
  return 'bg-yellow-500 text-black'
})

const formatValue = (value) => {
  if (value >= 1000000) {
    return (value / 1000000).toFixed(1) + 'M'
  }
  if (value >= 1000) {
    return (value / 1000).toFixed(0) + 'K'
  }
  return value
}
</script>
