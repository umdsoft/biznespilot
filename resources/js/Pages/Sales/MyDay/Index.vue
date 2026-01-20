<template>
  <component :is="layoutComponent">
    <Head title="Bugungi kun" />

    <div class="py-6">
      <div class="w-full px-4 sm:px-6 lg:px-8">
        <!-- Greeting & Date -->
        <div class="mb-6">
          <h1 class="text-2xl font-bold text-white">{{ data.greeting }}</h1>
          <p class="text-gray-400">{{ data.date }}</p>
        </div>

        <!-- Urgent Alerts Banner -->
        <AlertBanner
          v-if="data.alerts?.length > 0"
          :alerts="data.alerts"
          class="mb-6"
        />

        <!-- Today's Targets -->
        <div class="mb-6">
          <h2 class="text-lg font-semibold text-white mb-4">Bugungi maqsadlar</h2>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <TargetCard
              v-for="target in data.targets"
              :key="target.kpi_type"
              :target="target"
            />
          </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid lg:grid-cols-3 gap-6">
          <!-- Left Column - Tasks & Schedule -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Today's Tasks -->
            <div class="bg-gray-800 rounded-xl p-6">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                  <ClockIcon class="w-5 h-5 text-blue-400" />
                  Bugungi vazifalar
                </h2>
                <span class="text-sm text-gray-400">
                  {{ data.tasks?.length || 0 }} ta
                </span>
              </div>

              <div v-if="data.tasks?.length > 0" class="space-y-3">
                <TaskItem
                  v-for="task in data.tasks"
                  :key="task.id"
                  :task="task"
                  @complete="completeTask"
                />
              </div>
              <div v-else class="text-center py-8 text-gray-500">
                <CheckCircleIcon class="w-12 h-12 mx-auto mb-2 opacity-50" />
                <p>Bugungi vazifalar yo'q</p>
              </div>
            </div>

            <!-- Weekly Progress -->
            <div class="bg-gray-800 rounded-xl p-6">
              <h2 class="text-lg font-semibold text-white mb-4">Haftalik progress</h2>
              <WeeklyProgressBar :progress="weeklyProgress" />
            </div>
          </div>

          <!-- Right Column - Hot Leads & Stats -->
          <div class="space-y-6">
            <!-- Hot Leads -->
            <div class="bg-gray-800 rounded-xl p-6">
              <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <FireIcon class="w-5 h-5 text-red-400" />
                Issiq lidlar
              </h2>

              <div v-if="data.hot_leads?.length > 0" class="space-y-3">
                <HotLeadCard
                  v-for="lead in data.hot_leads"
                  :key="lead.id"
                  :lead="lead"
                  @click="openLead(lead.id)"
                />
              </div>
              <div v-else class="text-center py-6 text-gray-500">
                <UserGroupIcon class="w-10 h-10 mx-auto mb-2 opacity-50" />
                <p class="text-sm">Issiq lidlar yo'q</p>
              </div>
            </div>

            <!-- Today's Stats -->
            <div class="bg-gray-800 rounded-xl p-6">
              <h2 class="text-lg font-semibold text-white mb-4">Bugungi natijalar</h2>
              <div class="grid grid-cols-2 gap-4">
                <StatCard label="Qo'ng'iroqlar" :value="data.stats?.calls_made || 0" icon="phone" />
                <StatCard label="Vazifalar" :value="data.stats?.tasks_completed || 0" icon="check" />
                <StatCard label="Lidlar" :value="data.stats?.leads_contacted || 0" icon="users" />
                <StatCard label="Sotuvlar" :value="data.stats?.deals_closed || 0" icon="currency" />
              </div>
            </div>

            <!-- Active Streaks -->
            <div v-if="data.streaks?.length > 0" class="bg-gray-800 rounded-xl p-6">
              <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <SparklesIcon class="w-5 h-5 text-yellow-400" />
                Faol streaklaringiz
              </h2>
              <div class="space-y-3">
                <StreakItem
                  v-for="streak in data.streaks"
                  :key="streak.type"
                  :streak="streak"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Follow-ups Section -->
        <div v-if="followups?.length > 0" class="mt-6">
          <div class="bg-gradient-to-r from-orange-500/10 to-yellow-500/10 rounded-xl p-6 border border-orange-500/20">
            <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
              <ExclamationTriangleIcon class="w-5 h-5 text-orange-400" />
              Follow-up kerak
            </h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-3">
              <FollowupCard
                v-for="followup in followups"
                :key="followup.id"
                :followup="followup"
                @click="openLead(followup.id)"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import {
  ClockIcon,
  CheckCircleIcon,
  FireIcon,
  UserGroupIcon,
  SparklesIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

// Layouts
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import OperatorLayout from '@/layouts/OperatorLayout.vue'

// Components
import AlertBanner from '@/components/Sales/Alerts/AlertBanner.vue'
import TargetCard from '@/components/Sales/MyDay/TargetCard.vue'
import TaskItem from '@/components/Sales/MyDay/TaskItem.vue'
import HotLeadCard from '@/components/Sales/MyDay/HotLeadCard.vue'
import StatCard from '@/components/Sales/MyDay/StatCard.vue'
import StreakItem from '@/components/Sales/MyDay/StreakItem.vue'
import FollowupCard from '@/components/Sales/MyDay/FollowupCard.vue'
import WeeklyProgressBar from '@/components/Sales/MyDay/WeeklyProgressBar.vue'

const props = defineProps({
  data: {
    type: Object,
    default: () => ({})
  },
  role: {
    type: String,
    default: 'sales_operator'
  },
  followups: {
    type: Array,
    default: () => []
  },
  schedule: {
    type: Array,
    default: () => []
  },
  weeklyProgress: {
    type: Object,
    default: () => ({ days: [], average: 0 })
  }
})

// Dynamic layout
const layoutComponent = computed(() => {
  const layouts = {
    owner: BusinessLayout,
    sales_head: SalesHeadLayout,
    sales_operator: OperatorLayout
  }
  return layouts[props.role] || SalesHeadLayout
})

// Handlers
const openLead = (leadId) => {
  router.visit(route('sales-head.leads.show', leadId))
}

const completeTask = (taskId) => {
  router.post(route('sales-head.tasks.complete', taskId), {}, {
    preserveScroll: true,
    preserveState: true
  })
}
</script>
