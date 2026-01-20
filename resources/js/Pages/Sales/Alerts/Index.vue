<template>
  <component :is="layoutComponent">
    <Head title="Bildirishnomalar" />

    <div class="py-6">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page header -->
        <div class="mb-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-white">Bildirishnomalar</h1>
              <p class="text-gray-400 mt-1">
                Muhim xabarlar va ogohlantirishlar
              </p>
            </div>

            <!-- Settings button (for managers only) -->
            <Link
              v-if="canManageSettings"
              :href="route('sales-head.alerts.settings')"
              class="inline-flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
            >
              <Cog6ToothIcon class="w-5 h-5" />
              Sozlamalar
            </Link>
          </div>
        </div>

        <!-- Stats cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-gray-800 rounded-xl p-4">
            <div class="text-2xl font-bold text-white">{{ unreadCount }}</div>
            <div class="text-sm text-gray-400">O'qilmagan</div>
          </div>
          <div class="bg-gray-800 rounded-xl p-4">
            <div class="text-2xl font-bold text-red-400">{{ urgentCount }}</div>
            <div class="text-sm text-gray-400">Shoshilinch</div>
          </div>
          <div class="bg-gray-800 rounded-xl p-4">
            <div class="text-2xl font-bold text-orange-400">{{ highCount }}</div>
            <div class="text-sm text-gray-400">Muhim</div>
          </div>
          <div class="bg-gray-800 rounded-xl p-4">
            <div class="text-2xl font-bold text-gray-300">{{ alerts.length }}</div>
            <div class="text-sm text-gray-400">Jami</div>
          </div>
        </div>

        <!-- Urgent alerts banner -->
        <AlertBanner
          v-if="urgentAlerts.length > 0"
          :alerts="urgentAlerts"
          @dismiss="handleDismiss"
          class="mb-6"
        />

        <!-- Alerts list -->
        <div class="bg-gray-800/50 rounded-xl p-6">
          <AlertList
            :alerts="alerts"
            :alert-types="alertTypes"
            @mark-read="handleMarkRead"
            @dismiss="handleDismiss"
            @mark-all-read="handleMarkAllRead"
            @action="handleAction"
          />
        </div>
      </div>
    </div>
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Cog6ToothIcon } from '@heroicons/vue/24/outline'

// Layouts
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import OperatorLayout from '@/layouts/OperatorLayout.vue'

// Components
import AlertList from '@/components/Sales/Alerts/AlertList.vue'
import AlertBanner from '@/components/Sales/Alerts/AlertBanner.vue'

const props = defineProps({
  alerts: {
    type: Array,
    default: () => []
  },
  role: {
    type: String,
    default: 'sales_operator'
  },
  unreadCount: {
    type: Number,
    default: 0
  },
  alertTypes: {
    type: Object,
    default: () => ({})
  },
  priorities: {
    type: Object,
    default: () => ({})
  }
})

// Dynamic layout based on role
const layoutComponent = computed(() => {
  const layouts = {
    owner: BusinessLayout,
    sales_head: SalesHeadLayout,
    sales_operator: OperatorLayout
  }
  return layouts[props.role] || SalesHeadLayout
})

// Can manage settings (only managers)
const canManageSettings = computed(() => {
  return ['owner', 'sales_head'].includes(props.role)
})

// Computed stats
const urgentCount = computed(() => {
  return props.alerts.filter(a => a.priority === 'urgent' && a.status === 'unread').length
})

const highCount = computed(() => {
  return props.alerts.filter(a => a.priority === 'high' && a.status === 'unread').length
})

const urgentAlerts = computed(() => {
  return props.alerts.filter(a => ['urgent', 'high'].includes(a.priority) && a.status === 'unread').slice(0, 3)
})

// Handlers
const handleMarkRead = (alert) => {
  router.post(route('sales-head.alerts.read', alert.id), {}, {
    preserveScroll: true,
    preserveState: true
  })
}

const handleDismiss = (alert) => {
  router.post(route('sales-head.alerts.dismiss', alert.id), {}, {
    preserveScroll: true,
    preserveState: true
  })
}

const handleMarkAllRead = () => {
  router.post(route('sales-head.alerts.mark-all-read'), {}, {
    preserveScroll: true,
    preserveState: true
  })
}

const handleAction = (alert) => {
  // Mark as read when action is taken
  handleMarkRead(alert)
}
</script>
