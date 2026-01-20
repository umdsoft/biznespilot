<template>
  <div
    class="flex items-center gap-3 p-3 rounded-lg bg-gray-800 border border-gray-700 hover:border-orange-500/50 transition-colors cursor-pointer"
    @click="$emit('click')"
  >
    <div
      class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
      :class="followup.is_urgent ? 'bg-red-500 text-white' : 'bg-orange-500/20 text-orange-400'"
    >
      {{ followup.days_since_contact }}
    </div>

    <div class="flex-1 min-w-0">
      <div class="font-medium text-white truncate">{{ followup.name }}</div>
      <div class="text-sm text-gray-400 truncate">{{ followup.company || 'Kompaniya yo\'q' }}</div>
    </div>

    <div class="text-xs text-gray-500">
      {{ followup.days_since_contact }} kun
    </div>
  </div>
</template>

<script setup>
defineProps({
  followup: {
    type: Object,
    required: true
  }
})

defineEmits(['click'])
</script>
