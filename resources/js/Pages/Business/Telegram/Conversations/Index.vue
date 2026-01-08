<template>
  <BusinessLayout :title="`Suhbatlar - @${bot.username}`">
    <div class="space-y-6">
      <!-- Header -->
      <div class="relative overflow-hidden bg-gradient-to-br from-orange-600 via-orange-700 to-amber-800 dark:from-orange-800 dark:via-orange-900 dark:to-amber-950 rounded-2xl p-6 md:p-8">
        <div class="absolute inset-0 opacity-10">
          <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
            <defs>
              <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
              </pattern>
            </defs>
            <rect width="100" height="100" fill="url(#grid)"/>
          </svg>
        </div>

        <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div class="flex items-center">
            <Link
              :href="route('business.telegram-funnels.show', bot.id)"
              class="text-white/70 hover:text-white mr-4 transition-colors"
            >
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
            </Link>
            <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
              <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
            </div>
            <div>
              <h1 class="text-2xl md:text-3xl font-bold text-white">Suhbatlar</h1>
              <p class="text-orange-100">@{{ bot.username }} - Live Chat & Handoff</p>
            </div>
          </div>

          <div v-if="stats.unassigned_handoff > 0" class="flex items-center px-4 py-2 bg-red-500/20 border border-red-400/30 rounded-xl">
            <svg class="w-5 h-5 text-red-300 mr-2 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span class="text-white font-medium">{{ stats.unassigned_handoff }} ta kutayotgan suhbat</span>
          </div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
          <div class="flex items-center justify-between mb-2">
            <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
            </div>
          </div>
          <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
          <p class="text-sm text-gray-500 dark:text-gray-400">Jami</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
          <div class="flex items-center justify-between mb-2">
            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
          </div>
          <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.active }}</p>
          <p class="text-sm text-gray-500 dark:text-gray-400">Faol</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
          <div class="flex items-center justify-between mb-2">
            <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
          </div>
          <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.handoff }}</p>
          <p class="text-sm text-gray-500 dark:text-gray-400">Handoff</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
          <div class="flex items-center justify-between mb-2">
            <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.unassigned_handoff }}</p>
          <p class="text-sm text-gray-500 dark:text-gray-400">Kutayotgan</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <select
              v-model="statusFilter"
              @change="applyFilters"
              class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent text-gray-900 dark:text-white"
            >
              <option value="">Barcha status</option>
              <option value="active">Faol</option>
              <option value="handoff">Handoff</option>
              <option value="closed">Yopilgan</option>
            </select>
          </div>

          <button
            @click="filterUnassigned"
            :class="[
              'px-4 py-2.5 rounded-xl font-medium transition-colors flex items-center justify-center gap-2',
              onlyUnassigned
                ? 'bg-orange-600 text-white'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
            ]"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Kutayotganlar
          </button>
        </div>
      </div>

      <!-- Conversations List -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div v-if="conversations.data && conversations.data.length > 0">
          <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <Link
              v-for="conv in conversations.data"
              :key="conv.id"
              :href="route('business.telegram-funnels.conversations.show', [bot.id, conv.id])"
              class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
            >
              <div class="flex items-start justify-between">
                <div class="flex items-center">
                  <div class="relative">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" :class="getStatusBgClass(conv.status)">
                      <span class="text-lg font-semibold" :class="getStatusTextClass(conv.status)">
                        {{ getInitials(conv.user?.full_name) }}
                      </span>
                    </div>
                    <span
                      v-if="conv.unread_count > 0"
                      class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center"
                    >
                      {{ conv.unread_count > 9 ? '9+' : conv.unread_count }}
                    </span>
                  </div>
                  <div class="ml-4">
                    <div class="flex items-center gap-2">
                      <p class="font-medium text-gray-900 dark:text-white">{{ conv.user?.full_name || 'Noma\'lum' }}</p>
                      <span :class="getStatusBadgeClass(conv.status)">
                        {{ getStatusLabel(conv.status) }}
                      </span>
                    </div>
                    <p v-if="conv.user?.username" class="text-sm text-gray-500 dark:text-gray-400">@{{ conv.user.username }}</p>
                    <p v-if="conv.handoff_reason" class="text-sm text-orange-600 dark:text-orange-400 mt-1">
                      <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      {{ conv.handoff_reason }}
                    </p>
                  </div>
                </div>

                <div class="text-right">
                  <p class="text-sm text-gray-500 dark:text-gray-400">{{ conv.last_message_at }}</p>
                  <p v-if="conv.assigned_operator" class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                    <svg class="w-3.5 h-3.5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ conv.assigned_operator }}
                  </p>
                  <div v-if="conv.tags && conv.tags.length > 0" class="flex flex-wrap gap-1 mt-2 justify-end">
                    <span
                      v-for="tag in conv.tags.slice(0, 2)"
                      :key="tag"
                      class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs rounded-full"
                    >
                      {{ tag }}
                    </span>
                  </div>
                </div>
              </div>
            </Link>
          </div>

          <!-- Pagination -->
          <div v-if="conversations.links && conversations.links.length > 3" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="text-sm text-gray-500 dark:text-gray-400">
              {{ conversations.from }} - {{ conversations.to }} / {{ conversations.total }} ta suhbat
            </div>
            <div class="flex gap-2">
              <Link
                v-for="link in conversations.links"
                :key="link.label"
                :href="link.url || '#'"
                :class="[
                  'px-3 py-1 rounded-lg text-sm font-medium transition-colors',
                  link.active
                    ? 'bg-orange-600 text-white'
                    : link.url
                      ? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                      : 'bg-gray-50 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
                ]"
                v-html="link.label"
              />
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="p-12 text-center">
          <div class="w-20 h-20 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </div>
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Hali suhbat yo'q</h3>
          <p class="text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
            Foydalanuvchilar bot bilan suhbatni boshlaganda bu yerda ko'rinadi.
          </p>
        </div>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import BusinessLayout from '@/Layouts/BusinessLayout.vue'

const props = defineProps({
  bot: Object,
  conversations: Object,
  stats: {
    type: Object,
    default: () => ({ total: 0, active: 0, handoff: 0, unassigned_handoff: 0 })
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

const statusFilter = ref(props.filters.status || '')
const onlyUnassigned = ref(props.filters.operator_id === 'unassigned')

const getInitials = (name) => {
  if (!name) return '?'
  const parts = name.split(' ')
  return parts.map(p => p[0]).join('').toUpperCase().slice(0, 2)
}

const getStatusLabel = (status) => {
  const labels = {
    active: 'Faol',
    handoff: 'Handoff',
    closed: 'Yopilgan',
  }
  return labels[status] || status
}

const getStatusBgClass = (status) => {
  const classes = {
    active: 'bg-green-100 dark:bg-green-900/30',
    handoff: 'bg-yellow-100 dark:bg-yellow-900/30',
    closed: 'bg-gray-100 dark:bg-gray-700',
  }
  return classes[status] || classes.active
}

const getStatusTextClass = (status) => {
  const classes = {
    active: 'text-green-600 dark:text-green-400',
    handoff: 'text-yellow-600 dark:text-yellow-400',
    closed: 'text-gray-600 dark:text-gray-400',
  }
  return classes[status] || classes.active
}

const getStatusBadgeClass = (status) => {
  const classes = {
    active: 'px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
    handoff: 'px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 animate-pulse',
    closed: 'px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
  }
  return classes[status] || classes.active
}

const applyFilters = () => {
  router.get(route('business.telegram-funnels.conversations.index', props.bot.id), {
    status: statusFilter.value || undefined,
    operator_id: onlyUnassigned.value ? 'unassigned' : undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const filterUnassigned = () => {
  onlyUnassigned.value = !onlyUnassigned.value
  applyFilters()
}
</script>
