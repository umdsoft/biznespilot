<template>
  <div>
    <!-- Days grid -->
    <div class="flex justify-between gap-2">
      <div
        v-for="day in progress.days"
        :key="day.date"
        class="flex-1 text-center"
      >
        <!-- Day label -->
        <div
          class="text-xs mb-2"
          :class="day.is_today ? 'text-blue-400 font-medium' : 'text-gray-500'"
        >
          {{ day.day_name }}
        </div>

        <!-- Score bar -->
        <div
          class="h-24 rounded-lg flex items-end justify-center p-2 transition-all"
          :class="[
            day.is_today ? 'bg-blue-500/20 border-2 border-blue-500' : 'bg-gray-700',
            !day.is_past && !day.is_today ? 'opacity-50' : ''
          ]"
        >
          <div
            v-if="day.score > 0"
            class="w-full rounded transition-all"
            :class="getScoreBarClass(day.color)"
            :style="{ height: `${Math.min(day.score, 100)}%` }"
          />
        </div>

        <!-- Score label -->
        <div
          class="text-sm mt-2 font-medium"
          :class="day.is_today ? 'text-white' : 'text-gray-400'"
        >
          {{ day.is_past || day.is_today ? `${day.score}%` : '-' }}
        </div>
      </div>
    </div>

    <!-- Average -->
    <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-700">
      <span class="text-sm text-gray-400">Haftalik o'rtacha</span>
      <span
        class="text-lg font-bold"
        :class="progress.average >= 80 ? 'text-green-400' : progress.average >= 50 ? 'text-yellow-400' : 'text-red-400'"
      >
        {{ progress.average }}%
      </span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  progress: {
    type: Object,
    default: () => ({ days: [], average: 0 })
  }
})

const getScoreBarClass = (color) => {
  const classes = {
    green: 'bg-green-500',
    blue: 'bg-blue-500',
    yellow: 'bg-yellow-500',
    orange: 'bg-orange-500',
    red: 'bg-red-500',
    gray: 'bg-gray-600'
  }
  return classes[color] || 'bg-gray-600'
}
</script>
