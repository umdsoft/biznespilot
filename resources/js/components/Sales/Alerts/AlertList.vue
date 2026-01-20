<template>
  <div class="space-y-3">
    <!-- Header with filters -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <h3 class="text-lg font-semibold text-white">Bildirishnomalar</h3>
        <span
          v-if="unreadCount > 0"
          class="px-2 py-0.5 text-xs font-medium bg-red-500 text-white rounded-full"
        >
          {{ unreadCount }}
        </span>
      </div>

      <div class="flex items-center gap-2">
        <!-- Filter by type -->
        <select
          v-model="filterType"
          class="text-sm bg-gray-700 border-gray-600 text-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="all">Barchasi</option>
          <option v-for="(info, type) in alertTypes" :key="type" :value="type">
            {{ info.name }}
          </option>
        </select>

        <!-- Mark all as read -->
        <button
          v-if="unreadCount > 0"
          @click="$emit('mark-all-read')"
          class="text-sm text-gray-400 hover:text-white transition-colors"
        >
          Barchasini o'qish
        </button>
      </div>
    </div>

    <!-- Empty state -->
    <div
      v-if="filteredAlerts.length === 0"
      class="text-center py-12 bg-gray-800 rounded-xl"
    >
      <BellSlashIcon class="w-12 h-12 mx-auto text-gray-600 mb-3" />
      <p class="text-gray-400">
        {{ filterType === 'all' ? 'Hozircha bildirishnomalar yo\'q' : 'Bu turda bildirishnomalar yo\'q' }}
      </p>
    </div>

    <!-- Alerts list -->
    <TransitionGroup name="list" tag="div" class="space-y-3">
      <AlertCard
        v-for="alert in filteredAlerts"
        :key="alert.id"
        :alert="alert"
        @mark-read="$emit('mark-read', $event)"
        @dismiss="$emit('dismiss', $event)"
        @action="$emit('action', $event)"
      />
    </TransitionGroup>

    <!-- Load more -->
    <div v-if="hasMore" class="text-center pt-4">
      <button
        @click="$emit('load-more')"
        class="px-4 py-2 text-sm text-gray-400 hover:text-white transition-colors"
      >
        Ko'proq yuklash...
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { BellSlashIcon } from '@heroicons/vue/24/outline'
import AlertCard from './AlertCard.vue'

const props = defineProps({
  alerts: {
    type: Array,
    default: () => []
  },
  alertTypes: {
    type: Object,
    default: () => ({})
  },
  hasMore: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['mark-read', 'dismiss', 'action', 'mark-all-read', 'load-more'])

const filterType = ref('all')

const filteredAlerts = computed(() => {
  if (filterType.value === 'all') {
    return props.alerts
  }
  return props.alerts.filter(alert => alert.type === filterType.value)
})

const unreadCount = computed(() => {
  return props.alerts.filter(alert => alert.status === 'unread').length
})
</script>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}

.list-enter-from {
  opacity: 0;
  transform: translateX(-20px);
}

.list-leave-to {
  opacity: 0;
  transform: translateX(20px);
}
</style>
