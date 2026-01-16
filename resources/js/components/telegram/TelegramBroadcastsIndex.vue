<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="relative overflow-hidden bg-gradient-to-br from-purple-600 via-purple-700 to-indigo-800 dark:from-purple-800 dark:via-purple-900 dark:to-indigo-950 rounded-2xl p-6 md:p-8">
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
            :href="getRoute('telegram-funnels.show', bot.id)"
            class="text-white/70 hover:text-white mr-4 transition-colors"
          >
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </Link>
          <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
            </svg>
          </div>
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-white">Broadcast</h1>
            <p class="text-purple-100">@{{ bot.username }} - Ommaviy xabarlar</p>
          </div>
        </div>

        <Link
          :href="getRoute('telegram-funnels.broadcasts.create', bot.id)"
          class="inline-flex items-center px-5 py-2.5 bg-white text-purple-700 font-semibold rounded-xl hover:bg-purple-50 transition-all duration-200 shadow-lg hover:shadow-xl"
        >
          <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Yangi Broadcast
        </Link>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-2">
          <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ broadcasts.length }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Jami</p>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-2">
          <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ scheduledCount }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Rejalashtirilgan</p>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-2">
          <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ completedCount }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Yakunlangan</p>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-2">
          <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ sendingCount }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Yuborilmoqda</p>
      </div>
    </div>

    <!-- Broadcasts List -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div v-if="broadcasts.length > 0" class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-900/50">
            <tr>
              <th class="text-left py-3 px-6 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nomi</th>
              <th class="text-center py-3 px-6 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
              <th class="text-right py-3 px-6 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Qabul qiluvchilar</th>
              <th class="text-right py-3 px-6 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Yuborilgan</th>
              <th class="text-right py-3 px-6 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Yetkazilgan</th>
              <th class="text-center py-3 px-6 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sana</th>
              <th class="text-right py-3 px-6 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amallar</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="broadcast in broadcasts" :key="broadcast.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
              <td class="py-4 px-6">
                <div class="flex items-center">
                  <div class="w-10 h-10 rounded-lg flex items-center justify-center mr-3" :class="getStatusBgClass(broadcast.status)">
                    <svg class="w-5 h-5" :class="getStatusIconClass(broadcast.status)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                  </div>
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ broadcast.name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ broadcast.creator }}</p>
                  </div>
                </div>
              </td>
              <td class="py-4 px-6 text-center">
                <span :class="getStatusClass(broadcast.status)">
                  {{ getStatusLabel(broadcast.status) }}
                </span>
              </td>
              <td class="py-4 px-6 text-right text-gray-900 dark:text-white font-medium">
                {{ broadcast.total_recipients }}
              </td>
              <td class="py-4 px-6 text-right">
                <div class="flex items-center justify-end gap-2">
                  <span class="text-gray-900 dark:text-white">{{ broadcast.sent_count }}</span>
                  <span v-if="broadcast.progress > 0" class="text-xs text-gray-500 dark:text-gray-400">({{ broadcast.progress }}%)</span>
                </div>
              </td>
              <td class="py-4 px-6 text-right">
                <div class="flex items-center justify-end gap-2">
                  <span class="text-green-600 dark:text-green-400">{{ broadcast.delivered_count }}</span>
                  <span v-if="broadcast.delivery_rate > 0" class="text-xs text-gray-500 dark:text-gray-400">({{ broadcast.delivery_rate }}%)</span>
                </div>
              </td>
              <td class="py-4 px-6 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ broadcast.scheduled_at || broadcast.started_at || broadcast.created_at }}
              </td>
              <td class="py-4 px-6 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    v-if="broadcast.status === 'draft' || broadcast.status === 'scheduled'"
                    @click="startBroadcast(broadcast)"
                    class="p-2 text-green-600 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors"
                    title="Boshlash"
                  >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </button>
                  <button
                    v-if="broadcast.status === 'sending'"
                    @click="pauseBroadcast(broadcast)"
                    class="p-2 text-yellow-600 hover:bg-yellow-100 dark:hover:bg-yellow-900/30 rounded-lg transition-colors"
                    title="To'xtatish"
                  >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </button>
                  <button
                    v-if="broadcast.status === 'paused'"
                    @click="resumeBroadcast(broadcast)"
                    class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                    title="Davom ettirish"
                  >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </button>
                  <button
                    v-if="['draft', 'scheduled', 'sending', 'paused'].includes(broadcast.status)"
                    @click="cancelBroadcast(broadcast)"
                    class="p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                    title="Bekor qilish"
                  >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                  <button
                    @click="deleteBroadcast(broadcast)"
                    class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                    title="O'chirish"
                  >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-else class="p-12 text-center">
        <div class="w-20 h-20 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="w-10 h-10 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
          </svg>
        </div>
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Hali broadcast yo'q</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-sm mx-auto">
          Barcha obunachilarga ommaviy xabar yuborish uchun yangi broadcast yarating.
        </p>
        <Link
          :href="getRoute('telegram-funnels.broadcasts.create', bot.id)"
          class="inline-flex items-center px-5 py-2.5 bg-purple-600 text-white font-semibold rounded-xl hover:bg-purple-700 transition-all duration-200"
        >
          <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Yangi Broadcast yaratish
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  bot: Object,
  broadcasts: {
    type: Array,
    default: () => []
  },
  panelType: {
    type: String,
    required: true,
    validator: (value) => ['business', 'marketing'].includes(value),
  },
})

// Route helpers based on panel type
const getRoute = (name, params = null) => {
  const prefix = props.panelType === 'business' ? 'business.' : 'marketing.';
  if (Array.isArray(params)) {
    return route(prefix + name, params);
  }
  return params ? route(prefix + name, params) : route(prefix + name);
};

const scheduledCount = computed(() => props.broadcasts.filter(b => b.status === 'scheduled').length)
const completedCount = computed(() => props.broadcasts.filter(b => b.status === 'completed').length)
const sendingCount = computed(() => props.broadcasts.filter(b => b.status === 'sending').length)

const getStatusClass = (status) => {
  const classes = {
    draft: 'px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
    scheduled: 'px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    sending: 'px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 animate-pulse',
    paused: 'px-2.5 py-1 rounded-full text-xs font-medium bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
    completed: 'px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
    cancelled: 'px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
    failed: 'px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
  }
  return classes[status] || classes.draft
}

const getStatusLabel = (status) => {
  const labels = {
    draft: 'Qoralama',
    scheduled: 'Rejalashtirilgan',
    sending: 'Yuborilmoqda',
    paused: 'To\'xtatilgan',
    completed: 'Yakunlangan',
    cancelled: 'Bekor qilingan',
    failed: 'Xatolik',
  }
  return labels[status] || status
}

const getStatusBgClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 dark:bg-gray-700',
    scheduled: 'bg-blue-100 dark:bg-blue-900/30',
    sending: 'bg-yellow-100 dark:bg-yellow-900/30',
    paused: 'bg-orange-100 dark:bg-orange-900/30',
    completed: 'bg-green-100 dark:bg-green-900/30',
    cancelled: 'bg-red-100 dark:bg-red-900/30',
    failed: 'bg-red-100 dark:bg-red-900/30',
  }
  return classes[status] || classes.draft
}

const getStatusIconClass = (status) => {
  const classes = {
    draft: 'text-gray-600 dark:text-gray-400',
    scheduled: 'text-blue-600 dark:text-blue-400',
    sending: 'text-yellow-600 dark:text-yellow-400',
    paused: 'text-orange-600 dark:text-orange-400',
    completed: 'text-green-600 dark:text-green-400',
    cancelled: 'text-red-600 dark:text-red-400',
    failed: 'text-red-600 dark:text-red-400',
  }
  return classes[status] || classes.draft
}

const startBroadcast = async (broadcast) => {
  if (confirm('Broadcastni boshlashni xohlaysizmi?')) {
    await axios.post(getRoute('telegram-funnels.broadcasts.start', [props.bot.id, broadcast.id]))
    router.reload()
  }
}

const pauseBroadcast = async (broadcast) => {
  await axios.post(getRoute('telegram-funnels.broadcasts.pause', [props.bot.id, broadcast.id]))
  router.reload()
}

const resumeBroadcast = async (broadcast) => {
  await axios.post(getRoute('telegram-funnels.broadcasts.resume', [props.bot.id, broadcast.id]))
  router.reload()
}

const cancelBroadcast = async (broadcast) => {
  if (confirm('Broadcastni bekor qilishni xohlaysizmi?')) {
    await axios.post(getRoute('telegram-funnels.broadcasts.cancel', [props.bot.id, broadcast.id]))
    router.reload()
  }
}

const deleteBroadcast = async (broadcast) => {
  if (confirm('Broadcastni o\'chirishni xohlaysizmi?')) {
    await axios.delete(getRoute('telegram-funnels.broadcasts.destroy', [props.bot.id, broadcast.id]))
    router.reload()
  }
}
</script>
