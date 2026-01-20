<template>
  <div
    class="flex items-center justify-between p-3 rounded-lg"
    :class="streak.is_at_risk ? 'bg-orange-500/10 border border-orange-500/20' : 'bg-gray-700/50'"
  >
    <div class="flex items-center gap-3">
      <div class="text-2xl">
        {{ streak.current >= 7 ? '🔥' : streak.current >= 3 ? '✨' : '⭐' }}
      </div>
      <div>
        <div class="font-medium text-white">{{ streak.type_name }}</div>
        <div class="text-sm text-gray-400">
          <span v-if="streak.next_milestone">
            {{ streak.days_to_milestone }} kun → {{ streak.next_milestone }} kun
          </span>
          <span v-else>Rekord: {{ streak.best }} kun</span>
        </div>
      </div>
    </div>

    <div class="text-right">
      <div class="text-xl font-bold text-white">{{ streak.current }}</div>
      <div class="text-xs text-gray-500">kun</div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  streak: {
    type: Object,
    required: true
  }
})
</script>
