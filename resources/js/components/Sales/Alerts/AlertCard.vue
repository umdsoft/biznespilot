<template>
  <div
    class="relative rounded-lg border p-4 transition-all duration-200"
    :class="[
      alertClasses,
      { 'opacity-60': alert.status === 'read' }
    ]"
  >
    <!-- Priority indicator -->
    <div
      class="absolute left-0 top-0 bottom-0 w-1 rounded-l-lg"
      :class="priorityColor"
    />

    <div class="flex items-start gap-3 pl-2">
      <!-- Icon -->
      <div
        class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
        :class="iconBgClass"
      >
        <component :is="alertIcon" class="w-5 h-5" :class="iconColorClass" />
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between gap-2">
          <div class="flex-1">
            <!-- Title -->
            <h4 class="font-medium text-white text-sm">
              {{ alert.title }}
            </h4>

            <!-- Message -->
            <p class="text-gray-400 text-sm mt-1">
              {{ alert.message }}
            </p>

            <!-- Meta info -->
            <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
              <span class="flex items-center gap-1">
                <ClockIcon class="w-3.5 h-3.5" />
                {{ timeAgo }}
              </span>
              <span
                class="px-2 py-0.5 rounded-full text-xs"
                :class="typeTagClass"
              >
                {{ typeLabel }}
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-1">
            <button
              v-if="alert.status === 'unread'"
              @click="$emit('mark-read', alert)"
              class="p-1.5 rounded-lg hover:bg-gray-700 text-gray-400 hover:text-white transition-colors"
              title="O'qilgan deb belgilash"
            >
              <CheckIcon class="w-4 h-4" />
            </button>
            <button
              @click="$emit('dismiss', alert)"
              class="p-1.5 rounded-lg hover:bg-gray-700 text-gray-400 hover:text-red-400 transition-colors"
              title="Yopish"
            >
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Action button (if applicable) -->
        <div v-if="hasActionButton" class="mt-3">
          <button
            @click="handleAction"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-blue-500/20 text-blue-400 hover:bg-blue-500/30 transition-colors"
          >
            {{ actionLabel }}
            <ArrowRightIcon class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  CheckIcon,
  XMarkIcon,
  ArrowRightIcon,
  ClockIcon,
  UserPlusIcon,
  ChartBarIcon,
  ExclamationTriangleIcon,
  CalendarIcon,
  TrophyIcon,
  FireIcon,
  ArrowTrendingUpIcon,
  BellIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  alert: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['mark-read', 'dismiss', 'action'])

// Priority color mapping
const priorityColor = computed(() => {
  const colors = {
    low: 'bg-gray-500',
    medium: 'bg-blue-500',
    high: 'bg-orange-500',
    urgent: 'bg-red-500'
  }
  return colors[props.alert.priority] || colors.medium
})

// Alert card background classes
const alertClasses = computed(() => {
  if (props.alert.priority === 'urgent') {
    return 'bg-red-500/10 border-red-500/30'
  }
  if (props.alert.priority === 'high') {
    return 'bg-orange-500/10 border-orange-500/30'
  }
  return 'bg-gray-800 border-gray-700'
})

// Icon mapping
const alertIcon = computed(() => {
  const icons = {
    lead_followup: UserPlusIcon,
    kpi_warning: ChartBarIcon,
    target_reminder: CalendarIcon,
    penalty_warning: ExclamationTriangleIcon,
    daily_summary: CalendarIcon,
    achievement: TrophyIcon,
    streak_warning: FireIcon,
    leaderboard_change: ArrowTrendingUpIcon
  }
  return icons[props.alert.type] || BellIcon
})

// Icon background class
const iconBgClass = computed(() => {
  const classes = {
    lead_followup: 'bg-purple-500/20',
    kpi_warning: 'bg-orange-500/20',
    target_reminder: 'bg-blue-500/20',
    penalty_warning: 'bg-red-500/20',
    daily_summary: 'bg-green-500/20',
    achievement: 'bg-yellow-500/20',
    streak_warning: 'bg-orange-500/20',
    leaderboard_change: 'bg-blue-500/20'
  }
  return classes[props.alert.type] || 'bg-gray-500/20'
})

// Icon color class
const iconColorClass = computed(() => {
  const classes = {
    lead_followup: 'text-purple-400',
    kpi_warning: 'text-orange-400',
    target_reminder: 'text-blue-400',
    penalty_warning: 'text-red-400',
    daily_summary: 'text-green-400',
    achievement: 'text-yellow-400',
    streak_warning: 'text-orange-400',
    leaderboard_change: 'text-blue-400'
  }
  return classes[props.alert.type] || 'text-gray-400'
})

// Type tag class
const typeTagClass = computed(() => {
  const classes = {
    lead_followup: 'bg-purple-500/20 text-purple-400',
    kpi_warning: 'bg-orange-500/20 text-orange-400',
    target_reminder: 'bg-blue-500/20 text-blue-400',
    penalty_warning: 'bg-red-500/20 text-red-400',
    daily_summary: 'bg-green-500/20 text-green-400',
    achievement: 'bg-yellow-500/20 text-yellow-400',
    streak_warning: 'bg-orange-500/20 text-orange-400',
    leaderboard_change: 'bg-blue-500/20 text-blue-400'
  }
  return classes[props.alert.type] || 'bg-gray-500/20 text-gray-400'
})

// Type label
const typeLabel = computed(() => {
  const labels = {
    lead_followup: 'Follow-up',
    kpi_warning: 'KPI',
    target_reminder: 'Eslatma',
    penalty_warning: 'Jarima',
    daily_summary: 'Xulosa',
    achievement: 'Yutuq',
    streak_warning: 'Streak',
    leaderboard_change: 'Reyting'
  }
  return labels[props.alert.type] || props.alert.type
})

// Time ago
const timeAgo = computed(() => {
  const date = new Date(props.alert.created_at)
  const now = new Date()
  const diff = Math.floor((now - date) / 1000) // seconds

  if (diff < 60) return 'Hozirgina'
  if (diff < 3600) return `${Math.floor(diff / 60)} daqiqa oldin`
  if (diff < 86400) return `${Math.floor(diff / 3600)} soat oldin`
  if (diff < 604800) return `${Math.floor(diff / 86400)} kun oldin`

  return date.toLocaleDateString('uz-UZ')
})

// Action button logic
const hasActionButton = computed(() => {
  return ['lead_followup', 'penalty_warning', 'streak_warning'].includes(props.alert.type)
})

const actionLabel = computed(() => {
  const labels = {
    lead_followup: "Lidga o'tish",
    penalty_warning: "Tafsilotlar",
    streak_warning: "Davom etish"
  }
  return labels[props.alert.type] || "Ko'rish"
})

const handleAction = () => {
  emit('action', props.alert)

  // Navigate based on alert type
  if (props.alert.type === 'lead_followup' && props.alert.data?.lead_id) {
    router.visit(route('sales-head.leads.show', props.alert.data.lead_id))
  } else if (props.alert.type === 'penalty_warning') {
    router.visit(route('sales-head.sales-kpi.penalties'))
  }
}
</script>
