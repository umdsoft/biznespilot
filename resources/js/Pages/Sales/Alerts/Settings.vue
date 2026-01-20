<template>
  <SalesHeadLayout>
    <Head title="Alert sozlamalari" />

    <div class="py-6">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page header -->
        <div class="mb-6">
          <div class="flex items-center gap-3">
            <Link
              :href="route('sales-head.alerts.index')"
              class="p-2 rounded-lg hover:bg-gray-700 text-gray-400 transition-colors"
            >
              <ArrowLeftIcon class="w-5 h-5" />
            </Link>
            <div>
              <h1 class="text-2xl font-bold text-white">Alert sozlamalari</h1>
              <p class="text-gray-400 mt-1">
                Bildirishnoma turlarini sozlash
              </p>
            </div>
          </div>
        </div>

        <!-- Settings list -->
        <div class="space-y-4">
          <div
            v-for="(setting, alertType) in settings"
            :key="alertType"
            class="bg-gray-800 rounded-xl overflow-hidden"
          >
            <!-- Header -->
            <div
              class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-700/50 transition-colors"
              @click="toggleExpand(alertType)"
            >
              <div class="flex items-center gap-3">
                <div
                  class="w-10 h-10 rounded-full flex items-center justify-center"
                  :class="getIconBgClass(alertType)"
                >
                  <component
                    :is="getAlertIcon(alertType)"
                    class="w-5 h-5"
                    :class="getIconColorClass(alertType)"
                  />
                </div>
                <div>
                  <h3 class="font-medium text-white">
                    {{ alertTypes[alertType]?.name || alertType }}
                  </h3>
                  <p class="text-sm text-gray-400">
                    {{ alertTypes[alertType]?.description || '' }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-3">
                <!-- Toggle -->
                <button
                  @click.stop="toggleEnabled(alertType, setting)"
                  class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                  :class="setting.is_enabled ? 'bg-blue-600' : 'bg-gray-600'"
                >
                  <span
                    class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                    :class="setting.is_enabled ? 'translate-x-6' : 'translate-x-1'"
                  />
                </button>

                <ChevronDownIcon
                  class="w-5 h-5 text-gray-400 transition-transform"
                  :class="{ 'rotate-180': expandedType === alertType }"
                />
              </div>
            </div>

            <!-- Expanded settings -->
            <Transition name="expand">
              <div
                v-if="expandedType === alertType"
                class="border-t border-gray-700 p-4 space-y-4"
              >
                <!-- Channels -->
                <div>
                  <label class="block text-sm font-medium text-gray-300 mb-2">
                    Yuborish kanallari
                  </label>
                  <div class="flex flex-wrap gap-2">
                    <button
                      v-for="(channelName, channelKey) in channels"
                      :key="channelKey"
                      @click="toggleChannel(alertType, setting, channelKey)"
                      class="px-3 py-1.5 text-sm rounded-lg border transition-colors"
                      :class="
                        setting.channels?.includes(channelKey)
                          ? 'bg-blue-500/20 border-blue-500 text-blue-400'
                          : 'bg-gray-700 border-gray-600 text-gray-400 hover:border-gray-500'
                      "
                    >
                      {{ channelName }}
                    </button>
                  </div>
                </div>

                <!-- Frequency -->
                <div>
                  <label class="block text-sm font-medium text-gray-300 mb-2">
                    Chastotasi
                  </label>
                  <select
                    :value="setting.frequency"
                    @change="updateFrequency(alertType, setting, $event.target.value)"
                    class="w-full bg-gray-700 border-gray-600 text-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option v-for="(freqName, freqKey) in frequencies" :key="freqKey" :value="freqKey">
                      {{ freqName }}
                    </option>
                  </select>
                </div>

                <!-- Schedule time (for daily frequency) -->
                <div v-if="setting.frequency === 'daily'">
                  <label class="block text-sm font-medium text-gray-300 mb-2">
                    Vaqti
                  </label>
                  <input
                    type="time"
                    :value="setting.schedule_time"
                    @change="updateScheduleTime(alertType, setting, $event.target.value)"
                    class="w-full bg-gray-700 border-gray-600 text-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>

                <!-- Conditions (type-specific) -->
                <div v-if="hasConditions(alertType)">
                  <label class="block text-sm font-medium text-gray-300 mb-2">
                    Qoidalar
                  </label>
                  <div class="space-y-3">
                    <!-- Lead followup conditions -->
                    <div v-if="alertType === 'lead_followup'" class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="block text-xs text-gray-400 mb-1">
                          Jarimadan necha soat oldin ogohlantirish
                        </label>
                        <input
                          type="number"
                          :value="setting.conditions?.hours_before_penalty || 4"
                          @change="updateCondition(alertType, setting, 'hours_before_penalty', parseInt($event.target.value))"
                          class="w-full bg-gray-700 border-gray-600 text-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                          min="1"
                          max="24"
                        />
                      </div>
                    </div>

                    <!-- KPI warning conditions -->
                    <div v-if="alertType === 'kpi_warning'" class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="block text-xs text-gray-400 mb-1">
                          KPI chegarasi (%)
                        </label>
                        <input
                          type="number"
                          :value="setting.conditions?.kpi_threshold || 50"
                          @change="updateCondition(alertType, setting, 'kpi_threshold', parseInt($event.target.value))"
                          class="w-full bg-gray-700 border-gray-600 text-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                          min="10"
                          max="100"
                        />
                      </div>
                      <div>
                        <label class="block text-xs text-gray-400 mb-1">
                          Ketma-ket kunlar
                        </label>
                        <input
                          type="number"
                          :value="setting.conditions?.consecutive_days || 3"
                          @change="updateCondition(alertType, setting, 'consecutive_days', parseInt($event.target.value))"
                          class="w-full bg-gray-700 border-gray-600 text-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                          min="1"
                          max="7"
                        />
                      </div>
                    </div>

                    <!-- Leaderboard change conditions -->
                    <div v-if="alertType === 'leaderboard_change'" class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="block text-xs text-gray-400 mb-1">
                          Minimal pozitsiya o'zgarishi
                        </label>
                        <input
                          type="number"
                          :value="setting.conditions?.min_position_change || 3"
                          @change="updateCondition(alertType, setting, 'min_position_change', parseInt($event.target.value))"
                          class="w-full bg-gray-700 border-gray-600 text-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                          min="1"
                          max="10"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </div>
  </SalesHeadLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import {
  ArrowLeftIcon,
  ChevronDownIcon,
  UserPlusIcon,
  ChartBarIcon,
  CalendarIcon,
  ExclamationTriangleIcon,
  TrophyIcon,
  FireIcon,
  ArrowTrendingUpIcon,
  BellIcon
} from '@heroicons/vue/24/outline'

import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue'

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({})
  },
  alertTypes: {
    type: Object,
    default: () => ({})
  },
  channels: {
    type: Object,
    default: () => ({})
  },
  frequencies: {
    type: Object,
    default: () => ({})
  }
})

const expandedType = ref(null)

const toggleExpand = (alertType) => {
  expandedType.value = expandedType.value === alertType ? null : alertType
}

// Icon helpers
const iconMap = {
  lead_followup: UserPlusIcon,
  kpi_warning: ChartBarIcon,
  target_reminder: CalendarIcon,
  penalty_warning: ExclamationTriangleIcon,
  daily_summary: CalendarIcon,
  achievement: TrophyIcon,
  streak_warning: FireIcon,
  leaderboard_change: ArrowTrendingUpIcon
}

const getAlertIcon = (type) => iconMap[type] || BellIcon

const getIconBgClass = (type) => {
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
  return classes[type] || 'bg-gray-500/20'
}

const getIconColorClass = (type) => {
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
  return classes[type] || 'text-gray-400'
}

const hasConditions = (type) => {
  return ['lead_followup', 'kpi_warning', 'leaderboard_change'].includes(type)
}

// Update handlers
const saveSetting = (alertType, setting) => {
  router.post(route('sales-head.alerts.settings.update'), {
    alert_type: alertType,
    is_enabled: setting.is_enabled,
    conditions: setting.conditions,
    recipients: setting.recipients,
    channels: setting.channels,
    frequency: setting.frequency,
    schedule_time: setting.schedule_time
  }, {
    preserveScroll: true,
    preserveState: true
  })
}

const toggleEnabled = (alertType, setting) => {
  setting.is_enabled = !setting.is_enabled
  saveSetting(alertType, setting)
}

const toggleChannel = (alertType, setting, channel) => {
  if (!setting.channels) setting.channels = []

  const index = setting.channels.indexOf(channel)
  if (index > -1) {
    setting.channels.splice(index, 1)
  } else {
    setting.channels.push(channel)
  }
  saveSetting(alertType, setting)
}

const updateFrequency = (alertType, setting, frequency) => {
  setting.frequency = frequency
  saveSetting(alertType, setting)
}

const updateScheduleTime = (alertType, setting, time) => {
  setting.schedule_time = time
  saveSetting(alertType, setting)
}

const updateCondition = (alertType, setting, key, value) => {
  if (!setting.conditions) setting.conditions = {}
  setting.conditions[key] = value
  saveSetting(alertType, setting)
}
</script>

<style scoped>
.expand-enter-active,
.expand-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
  opacity: 0;
  max-height: 0;
}

.expand-enter-to,
.expand-leave-from {
  opacity: 1;
  max-height: 500px;
}
</style>
