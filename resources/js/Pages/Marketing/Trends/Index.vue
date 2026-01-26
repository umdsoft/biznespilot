<template>
  <component :is="layoutComponent" title="Viral Trendlar">
    <!-- Compact Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/25">
            <FireIcon class="w-5 h-5 text-white" />
          </div>
          <div>
            <h1 class="text-xl font-bold text-white">Viral Trendlar</h1>
            <p class="text-xs text-slate-400">Instagram'dagi eng mashhur kontentlar</p>
          </div>
        </div>

        <!-- Refresh Button -->
        <button
          @click="refreshData"
          :disabled="!canRefresh || isRefreshing"
          :class="[
            'px-3 py-1.5 rounded-lg text-sm font-medium transition-all flex items-center gap-1.5',
            canRefresh && !isRefreshing
              ? 'bg-gradient-to-r from-purple-500 to-blue-500 text-white hover:from-purple-600 hover:to-blue-600 shadow-lg shadow-purple-500/25'
              : 'bg-slate-700/50 text-slate-500 cursor-not-allowed'
          ]"
        >
          <ArrowPathIcon :class="['w-4 h-4', { 'animate-spin': isRefreshing }]" />
          <span v-if="!canRefresh && currentCooldown > 0">{{ formatCooldown(currentCooldown) }}</span>
          <span v-else-if="isRefreshing">Yangilanmoqda...</span>
          <span v-else>Yangilash</span>
        </button>
      </div>
    </div>

    <!-- Compact Stats Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
      <!-- Total Viral -->
      <div class="bg-gradient-to-br from-orange-500/20 to-red-500/20 rounded-xl p-3 border border-orange-500/30">
        <div class="flex items-center gap-2 mb-1">
          <div class="w-7 h-7 bg-orange-500/20 rounded-lg flex items-center justify-center">
            <FireIcon class="h-4 w-4 text-orange-400" />
          </div>
          <span class="text-2xl font-bold text-white">{{ stats.total }}</span>
        </div>
        <p class="text-[11px] text-orange-300/80 font-medium">Jami viral</p>
      </div>

      <!-- Super Viral -->
      <div class="bg-gradient-to-br from-purple-500/20 to-pink-500/20 rounded-xl p-3 border border-purple-500/30">
        <div class="flex items-center gap-2 mb-1">
          <div class="w-7 h-7 bg-purple-500/20 rounded-lg flex items-center justify-center">
            <BoltIcon class="h-4 w-4 text-purple-400" />
          </div>
          <span class="text-2xl font-bold text-white">{{ stats.super_viral }}</span>
        </div>
        <p class="text-[11px] text-purple-300/80 font-medium">Super viral</p>
      </div>

      <!-- Hook Score -->
      <div class="bg-gradient-to-br from-blue-500/20 to-cyan-500/20 rounded-xl p-3 border border-blue-500/30">
        <div class="flex items-center gap-2 mb-1">
          <div class="w-7 h-7 bg-blue-500/20 rounded-lg flex items-center justify-center">
            <SparklesIcon class="h-4 w-4 text-blue-400" />
          </div>
          <span class="text-2xl font-bold text-white">{{ stats.avg_hook_score }}<span class="text-sm text-blue-300">/10</span></span>
        </div>
        <p class="text-[11px] text-blue-300/80 font-medium">O'rtacha Hook</p>
      </div>

      <!-- Top Niche -->
      <div class="bg-gradient-to-br from-emerald-500/20 to-green-500/20 rounded-xl p-3 border border-emerald-500/30">
        <div class="flex items-center gap-2 mb-1">
          <div class="w-7 h-7 bg-emerald-500/20 rounded-lg flex items-center justify-center">
            <HashtagIcon class="h-4 w-4 text-emerald-400" />
          </div>
          <span class="text-lg font-bold text-white truncate">#{{ stats.top_niche }}</span>
        </div>
        <p class="text-[11px] text-emerald-300/80 font-medium">Top nisha</p>
      </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700/50 p-3 mb-6">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <!-- Niche Filter -->
          <div class="flex items-center gap-2">
            <FunnelIcon class="w-4 h-4 text-slate-500" />
            <select
              v-model="selectedNiche"
              @change="applyFilters"
              class="px-3 py-1.5 rounded-lg bg-slate-700/50 border border-slate-600/50 text-slate-200 text-sm focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500/50 transition-all"
            >
              <option v-for="niche in niches" :key="niche.value" :value="niche.value">
                {{ niche.label }} ({{ niche.count }})
              </option>
            </select>
          </div>

          <!-- Sort Filter -->
          <div class="flex items-center gap-2">
            <ArrowsUpDownIcon class="w-4 h-4 text-slate-500" />
            <select
              v-model="selectedSort"
              @change="applyFilters"
              class="px-3 py-1.5 rounded-lg bg-slate-700/50 border border-slate-600/50 text-slate-200 text-sm focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500/50 transition-all"
            >
              <option value="play_count">Ko'rishlar</option>
              <option value="hook_score">Hook Score</option>
              <option value="likes">Layklar</option>
              <option value="comments">Izohlar</option>
              <option value="recent">Yangi</option>
            </select>
          </div>
        </div>

        <!-- Results Count -->
        <div class="flex items-center gap-2 text-xs text-slate-400">
          <span class="px-2 py-1 bg-slate-700/50 rounded-lg">
            {{ viralContents.total }} ta natija
          </span>
        </div>
      </div>
    </div>

    <!-- Polling indicator when refreshing with existing data -->
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0 -translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-2"
    >
      <div v-if="waitingForRefresh && viralContents.data.length > 0" class="mb-4 flex items-center justify-center gap-2 py-2 px-4 bg-purple-500/10 border border-purple-500/30 rounded-lg">
        <ArrowPathIcon class="w-4 h-4 text-purple-400 animate-spin" />
        <span class="text-sm text-purple-300">Yangi kontentlar yuklanmoqda...</span>
        <span class="text-xs text-purple-400/70">({{ pollAttempts }}/{{ maxPollAttempts }})</span>
      </div>
    </Transition>

    <!-- Content Grid - 5 Columns -->
    <div v-if="viralContents.data.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-6">
      <ViralPostCard
        v-for="post in viralContents.data"
        :key="post.id"
        :post="post"
      />
    </div>

    <!-- Empty State - Rate Limited -->
    <div v-else-if="isRateLimited" class="bg-gradient-to-br from-amber-500/10 to-orange-500/10 rounded-xl border border-amber-500/30 p-8 text-center">
      <div class="w-16 h-16 mx-auto bg-gradient-to-br from-amber-500/20 to-orange-500/20 rounded-full flex items-center justify-center mb-4">
        <ExclamationCircleIcon class="w-8 h-8 text-amber-400" />
      </div>
      <h3 class="text-lg font-semibold text-white mb-2">API limit tugadi</h3>
      <p class="text-sm text-slate-400 mb-4">
        Apify kunlik limit tugadi. Iltimos, 1 soatdan keyin qayta urinib ko'ring.
      </p>
      <p class="text-xs text-slate-500">
        Kategoriya: <span class="text-amber-400 font-medium capitalize">{{ businessCategory }}</span>
      </p>
    </div>

    <!-- Empty State - Auto Seeding in Progress -->
    <div v-else-if="autoSeedMessage || isPolling" class="bg-gradient-to-br from-purple-500/10 to-blue-500/10 rounded-xl border border-purple-500/30 p-8 text-center">
      <div class="w-16 h-16 mx-auto bg-gradient-to-br from-purple-500/20 to-blue-500/20 rounded-full flex items-center justify-center mb-4">
        <ArrowPathIcon class="w-8 h-8 text-purple-400 animate-spin" />
      </div>
      <h3 class="text-lg font-semibold text-white mb-2">{{ autoSeedMessage || 'Viral kontentlar yuklanmoqda...' }}</h3>
      <p class="text-sm text-slate-400 mb-3">
        Kategoriya: <span class="text-purple-400 font-medium capitalize">{{ businessCategory }}</span>
      </p>

      <!-- Hashtags -->
      <div v-if="recommendedHashtags.length > 0" class="flex flex-wrap justify-center gap-1.5 mb-4">
        <span
          v-for="tag in recommendedHashtags"
          :key="tag"
          class="px-2 py-0.5 bg-purple-500/20 text-purple-300 text-xs rounded-full"
        >
          #{{ tag }}
        </span>
      </div>

      <!-- Polling Status -->
      <div class="flex items-center justify-center gap-2 text-emerald-400 text-xs mb-3">
        <div class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></div>
        <span>Serverdan ma'lumot olinmoqda...</span>
      </div>

      <!-- Progress -->
      <div class="max-w-[200px] mx-auto">
        <div class="flex justify-between text-[10px] text-slate-500 mb-1">
          <span>Tekshiruv</span>
          <span>{{ pollAttempts }}/{{ maxPollAttempts }}</span>
        </div>
        <div class="h-1 bg-slate-700 rounded-full overflow-hidden">
          <div
            class="h-full bg-gradient-to-r from-purple-500 to-blue-500 transition-all duration-300"
            :style="{ width: `${(pollAttempts / maxPollAttempts) * 100}%` }"
          ></div>
        </div>
      </div>
    </div>

    <!-- Empty State - No Content -->
    <div v-else class="bg-slate-800/50 rounded-xl border border-slate-700/50 p-8 text-center">
      <div class="w-16 h-16 mx-auto bg-slate-700/50 rounded-full flex items-center justify-center mb-4">
        <FilmIcon class="w-8 h-8 text-slate-500" />
      </div>
      <h3 class="text-lg font-semibold text-slate-300 mb-2">Viral kontentlar topilmadi</h3>
      <p class="text-sm text-slate-500 mb-4">
        {{ businessCategory ? `"${businessCategory}" kategoriyasi uchun` : '' }} hali ma'lumot yo'q
      </p>
      <button
        v-if="canRefresh"
        @click="refreshData"
        class="px-4 py-2 bg-gradient-to-r from-purple-500 to-blue-500 text-white rounded-lg text-sm font-medium hover:from-purple-600 hover:to-blue-600 transition-all inline-flex items-center gap-2"
      >
        <ArrowPathIcon class="w-4 h-4" />
        Kontentlarni yuklash
      </button>
    </div>

    <!-- Pagination -->
    <div v-if="viralContents.data.length > 0 && viralContents.links.length > 3" class="flex items-center justify-center gap-1.5 mt-6">
      <Link
        v-for="link in viralContents.links"
        :key="link.label"
        :href="link.url || '#'"
        :class="[
          'px-3 py-1.5 rounded-lg text-xs font-medium transition-all',
          link.active
            ? 'bg-purple-500 text-white shadow-lg shadow-purple-500/25'
            : link.url
              ? 'bg-slate-700/50 text-slate-300 hover:bg-slate-600/50'
              : 'bg-slate-800/30 text-slate-600 cursor-not-allowed'
        ]"
        v-html="link.label"
        preserve-scroll
      />
    </div>

    <!-- Flash Messages -->
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0 translate-y-4"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-4"
    >
      <div
        v-if="$page.props.flash?.success"
        class="fixed bottom-4 right-4 bg-emerald-500 text-white px-4 py-2.5 rounded-lg shadow-lg flex items-center gap-2 z-50 text-sm"
      >
        <CheckCircleIcon class="w-4 h-4" />
        {{ $page.props.flash.success }}
      </div>
    </Transition>

    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0 translate-y-4"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-4"
    >
      <div
        v-if="$page.props.flash?.error"
        class="fixed bottom-4 right-4 bg-red-500 text-white px-4 py-2.5 rounded-lg shadow-lg flex items-center gap-2 z-50 text-sm"
      >
        <ExclamationCircleIcon class="w-4 h-4" />
        {{ $page.props.flash.error }}
      </div>
    </Transition>
  </component>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue'
import OperatorLayout from '@/layouts/OperatorLayout.vue'
import FinanceLayout from '@/layouts/FinanceLayout.vue'
import HRLayout from '@/layouts/HRLayout.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import ViralPostCard from '@/components/TrendSee/ViralPostCard.vue'
import {
  FireIcon,
  BoltIcon,
  SparklesIcon,
  HashtagIcon,
  ArrowPathIcon,
  FilmIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  FunnelIcon,
  ArrowsUpDownIcon,
} from '@heroicons/vue/24/solid'

const props = defineProps({
  viralContents: Object,
  niches: Array,
  filters: Object,
  canRefresh: Boolean,
  refreshCooldown: Number,
  stats: Object,
  panelType: {
    type: String,
    default: 'marketing',
  },
  businessCategory: {
    type: String,
    default: 'general',
  },
  recommendedHashtags: {
    type: Array,
    default: () => [],
  },
  autoSeedTriggered: {
    type: Boolean,
    default: false,
  },
  autoSeedMessage: {
    type: String,
    default: null,
  },
  isRateLimited: {
    type: Boolean,
    default: false,
  },
})

// Dynamic layout
const layoutComponent = computed(() => {
  switch (props.panelType) {
    case 'business': return BusinessLayout
    case 'saleshead': return SalesHeadLayout
    case 'operator': return OperatorLayout
    case 'finance': return FinanceLayout
    case 'hr': return HRLayout
    case 'admin': return AdminLayout
    default: return MarketingLayout
  }
})

const selectedNiche = ref(props.filters?.niche || 'all')
const selectedSort = ref(props.filters?.sort || 'play_count')
const isRefreshing = ref(false)
const currentCooldown = ref(props.refreshCooldown || 0)

// Polling
const POLL_INTERVAL = 3000
const MAX_POLL_ATTEMPTS = 40
const isPolling = ref(false)
const pollAttempts = ref(0)
const maxPollAttempts = ref(MAX_POLL_ATTEMPTS)

let cooldownInterval = null
let pollInterval = null

// Track if we're waiting for refresh job to complete
const waitingForRefresh = ref(false)

const shouldPoll = computed(() => {
  // Don't poll if rate limited
  if (props.isRateLimited) return false
  // Poll if we're waiting for refresh results OR if auto-seeding with no data
  return waitingForRefresh.value ||
         ((props.autoSeedTriggered || props.autoSeedMessage) && props.viralContents?.data?.length === 0)
})

const startPolling = () => {
  if (pollInterval || !shouldPoll.value) return

  isPolling.value = true
  pollAttempts.value = 0

  pollInterval = setInterval(() => {
    pollAttempts.value++

    if (pollAttempts.value >= MAX_POLL_ATTEMPTS) {
      stopPolling()
      waitingForRefresh.value = false
      return
    }

    router.reload({
      only: ['viralContents', 'stats', 'autoSeedMessage'],
      preserveScroll: true,
      onSuccess: () => {
        const currentTotal = props.viralContents?.total || 0
        const hasNewData = currentTotal > dataCountBeforeRefresh.value
        const hasAnyData = props.viralContents?.data?.length > 0

        // Stop polling if we got new data OR if we were waiting with no data and now have some
        if (hasNewData || (waitingForRefresh.value && hasAnyData && !props.autoSeedMessage)) {
          stopPolling()
          waitingForRefresh.value = false
        }
      },
    })
  }, POLL_INTERVAL)
}

const stopPolling = () => {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
  isPolling.value = false
}

watch(
  () => props.viralContents?.total,
  (newTotal) => {
    // Stop polling if we were waiting for refresh and got new data
    if (waitingForRefresh.value && newTotal > dataCountBeforeRefresh.value) {
      stopPolling()
      waitingForRefresh.value = false
    }
    // Stop polling if we were auto-seeding and now have data
    else if (isPolling.value && !waitingForRefresh.value && newTotal > 0) {
      stopPolling()
    }
  }
)

onMounted(() => {
  if (currentCooldown.value > 0) {
    startCooldownTimer()
  }
  if (shouldPoll.value) {
    setTimeout(() => startPolling(), 1000)
  }
})

onUnmounted(() => {
  if (cooldownInterval) clearInterval(cooldownInterval)
  stopPolling()
})

const startCooldownTimer = () => {
  cooldownInterval = setInterval(() => {
    if (currentCooldown.value > 0) {
      currentCooldown.value--
    } else {
      clearInterval(cooldownInterval)
    }
  }, 1000)
}

const applyFilters = () => {
  stopPolling()
  router.get(route('marketing.trends.index'), {
    niche: selectedNiche.value,
    sort: selectedSort.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

// Track original data count before refresh to detect when new data arrives
const dataCountBeforeRefresh = ref(0)

const refreshData = () => {
  if (!props.canRefresh || isRefreshing.value) return

  isRefreshing.value = true
  stopPolling()

  // Remember current data count to detect new data
  dataCountBeforeRefresh.value = props.viralContents?.total || 0

  router.post(route('marketing.trends.refresh'), {}, {
    preserveScroll: true,
    onFinish: () => {
      isRefreshing.value = false
      currentCooldown.value = 180 // 3 minutes cooldown
      startCooldownTimer()
      // Start polling for new data
      waitingForRefresh.value = true
      setTimeout(() => startPolling(), 1000)
    },
  })
}

const formatCooldown = (seconds) => {
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
}
</script>
