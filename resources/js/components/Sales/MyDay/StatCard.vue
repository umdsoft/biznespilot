<template>
  <div class="text-center p-3 bg-gray-700/50 rounded-lg">
    <div class="text-2xl font-bold text-white">{{ value }}</div>
    <div class="text-xs text-gray-400 mt-1">{{ label }}</div>
  </div>
</template>

<script setup>
defineProps({
  label: {
    type: String,
    required: true
  },
  value: {
    type: [Number, String],
    default: 0
  },
  icon: {
    type: String,
    default: 'chart'
  }
})
</script>
