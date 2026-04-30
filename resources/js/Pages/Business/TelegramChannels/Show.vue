<template>
  <component :is="layoutComponent" :title="`${channel.title} — Telegram Kanal`">
    <!-- Header -->
    <div class="mb-6">
      <Link :href="`${routePrefix}/telegram-channels`" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 inline-flex items-center gap-1 mb-3">
        <ChevronLeftIcon class="w-4 h-4" /> Barcha kanallar
      </Link>

      <div class="flex items-start justify-between flex-wrap gap-4">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-full bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
            <img v-if="channel.photo_url" :src="channel.photo_url" class="w-full h-full rounded-full object-cover" :alt="channel.title" />
            <span v-else>{{ initials(channel.title) }}</span>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ channel.title }}</h1>
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mt-1">
              <span v-if="channel.chat_username">@{{ channel.chat_username }}</span>
              <span v-if="channel.public_link">
                <a :href="channel.public_link" target="_blank" rel="noopener" class="text-sky-600 dark:text-sky-400 hover:underline inline-flex items-center gap-1">
                  <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" /> Telegram'da ochish
                </a>
              </span>
              <span v-if="channel.connected_at_human" class="text-gray-400">
                • {{ channel.connected_at_human }} ulangan
              </span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="refreshChannel"
            :disabled="isRefreshing"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
          >
            <ArrowPathIcon class="w-4 h-4" :class="{ 'animate-spin': isRefreshing }" />
            Yangilash
          </button>
          <button
            @click="confirmDisconnect"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors"
          >
            <TrashIcon class="w-4 h-4" />
            Uzish
          </button>
        </div>
      </div>
    </div>

    <!-- Inactive warning -->
    <div v-if="!channel.is_active" class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
      <p class="text-sm text-red-700 dark:text-red-400 font-medium">
        ⚠️ Bot bu kanaldan chiqarilgan — yangi ma'lumot yig'ilmayapti.
        Qayta yoqish uchun bot'ni kanalga admin qilib qo'shing.
      </p>
    </div>

    <!-- Main stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Obunachilar</p>
        <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">{{ formatNumber(channel.subscriber_count) }}</p>
        <p class="text-xs mt-2" :class="summary.week.net_growth >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
          {{ summary.week.net_growth >= 0 ? '+' : '' }}{{ formatNumber(summary.week.net_growth) }} haftalik
        </p>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">7 kun ko'rishlar</p>
        <p class="text-3xl font-bold text-sky-600 dark:text-sky-400 mt-2">{{ formatNumber(summary.week.views) }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
          {{ summary.week.posts }} ta post
        </p>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Reaksiyalar</p>
        <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-2">{{ formatNumber(summary.week.reactions) }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">haftalik</p>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Engagement</p>
        <p class="text-3xl font-bold text-amber-600 dark:text-amber-400 mt-2">{{ summary.week.engagement_rate }}%</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">7 kunlik o'rtacha</p>
      </div>
    </div>

    <!-- Subscriber trend chart -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 mb-6 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Obunachilar dinamikasi</h3>
        <span class="text-xs text-gray-400 dark:text-gray-500">Oxirgi 30 kun</span>
      </div>
      <div class="p-4">
        <div v-if="hasTrendData" class="h-64">
          <apexchart type="area" height="250" :options="subsChartOptions" :series="subsChartSeries" />
        </div>
        <div v-else class="h-64 flex flex-col items-center justify-center text-center">
          <ChartBarIcon class="w-10 h-10 text-gray-300 dark:text-gray-600 mb-2" />
          <p class="text-sm text-gray-400 dark:text-gray-500">Hali kunlik hisobot yig'ilmagan</p>
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Birinchi rollup 23:55 da avtomatik yoziladi</p>
        </div>
      </div>
    </div>

    <!-- Views + posts chart -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 mb-6 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Ko'rishlar va postlar</h3>
      </div>
      <div class="p-4">
        <div v-if="hasTrendData" class="h-64">
          <apexchart type="line" height="250" :options="viewsChartOptions" :series="viewsChartSeries" />
        </div>
        <div v-else class="h-32 flex items-center justify-center">
          <p class="text-sm text-gray-400 dark:text-gray-500">Ma'lumot yetarli emas</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <!-- Top post -->
      <div class="lg:col-span-1">
        <div class="relative bg-gradient-to-br from-amber-50 via-white to-orange-50 dark:from-amber-900/10 dark:via-gray-800 dark:to-orange-900/10 rounded-xl border border-amber-200/60 dark:border-amber-700/40 overflow-hidden h-full shadow-sm">
          <!-- Decorative gradient -->
          <div class="absolute -top-12 -right-12 w-32 h-32 bg-gradient-to-br from-amber-400/20 to-orange-500/20 rounded-full blur-2xl pointer-events-none"></div>

          <div class="relative px-5 py-4 border-b border-amber-200/50 dark:border-amber-700/30 flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-sm">
              <FireIcon class="w-4 h-4 text-white" />
            </div>
            <div>
              <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 leading-tight">Oyning top posti</h3>
              <p class="text-xs text-amber-700/70 dark:text-amber-400/70">Eng ko'p ko'rishli</p>
            </div>
          </div>

          <div v-if="summary.top_post_30d" class="relative p-5 flex flex-col h-[calc(100%-65px)]">
            <!-- Type badge + date -->
            <div class="flex items-center gap-2 mb-3 text-xs">
              <span :class="contentTypeBadge(summary.top_post_30d.content_type)" class="inline-flex items-center gap-1 px-2 py-1 rounded-md font-medium">
                <component :is="contentTypeIcon(summary.top_post_30d.content_type)" class="w-3.5 h-3.5" />
                {{ contentTypeLabel(summary.top_post_30d.content_type) }}
              </span>
              <span class="text-gray-400 dark:text-gray-500">·</span>
              <span class="text-gray-500 dark:text-gray-400">{{ summary.top_post_30d.posted_at_human }}</span>
            </div>

            <!-- Text preview -->
            <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed mb-4 line-clamp-5 flex-1">
              {{ summary.top_post_30d.text_preview || '(media post — matn yo\'q)' }}
            </p>

            <!-- Stats grid -->
            <div class="grid grid-cols-2 gap-2 mb-4">
              <div class="bg-white/70 dark:bg-gray-900/30 rounded-lg p-3 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-1.5 text-sky-600 dark:text-sky-400 mb-1">
                  <EyeIcon class="w-4 h-4" />
                  <span class="text-xs font-medium uppercase tracking-wide">Ko'rish</span>
                </div>
                <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(summary.top_post_30d.views) }}</p>
              </div>
              <div class="bg-white/70 dark:bg-gray-900/30 rounded-lg p-3 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-1.5 text-purple-600 dark:text-purple-400 mb-1">
                  <HeartIcon class="w-4 h-4" />
                  <span class="text-xs font-medium uppercase tracking-wide">Reaksiya</span>
                </div>
                <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(summary.top_post_30d.reactions_count) }}</p>
              </div>
            </div>

            <!-- CTA -->
            <a v-if="summary.top_post_30d.telegram_link"
               :href="summary.top_post_30d.telegram_link" target="_blank" rel="noopener"
               class="inline-flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white text-sm font-semibold rounded-lg shadow-sm transition-all hover:shadow-md">
              <PaperAirplaneIcon class="w-4 h-4" />
              Telegram'da ochish
            </a>
          </div>

          <div v-else class="relative p-8 text-center flex flex-col items-center justify-center h-[calc(100%-65px)]">
            <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-3">
              <DocumentTextIcon class="w-7 h-7 text-gray-400 dark:text-gray-500" />
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Hali postlar yo'q</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 max-w-[200px]">
              Kanalga post chiqaring — top post avtomatik aniqlanadi
            </p>
          </div>
        </div>
      </div>

      <!-- Month summary -->
      <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden h-full">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">30 kunlik xulosa</h3>
          </div>
          <div class="p-5 grid grid-cols-2 md:grid-cols-3 gap-4">
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Yangi obunachilar</p>
              <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">+{{ formatNumber(summary.month.new_subscribers) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Obunadan chiqdi</p>
              <p class="text-2xl font-bold text-red-500 dark:text-red-400 mt-1">−{{ formatNumber(summary.month.left_subscribers) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Sof o'sish</p>
              <p class="text-2xl font-bold mt-1" :class="summary.month.net_growth >= 0 ? 'text-gray-900 dark:text-gray-100' : 'text-red-600 dark:text-red-400'">
                {{ summary.month.net_growth >= 0 ? '+' : '' }}{{ formatNumber(summary.month.net_growth) }}
              </p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Postlar</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ formatNumber(summary.month.posts) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Ko'rishlar</p>
              <p class="text-2xl font-bold text-sky-600 dark:text-sky-400 mt-1">{{ formatNumber(summary.month.views) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Engagement</p>
              <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ summary.month.engagement_rate }}%</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent posts -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <div>
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">So'nggi postlar</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ recentPosts.length }} ta post · ko'rish, reaksiya va engagement</p>
        </div>
        <span class="text-xs text-gray-400 dark:text-gray-500 hidden sm:inline-flex items-center gap-1.5">
          <ClockIcon class="w-3.5 h-3.5" />
          Yangidan eski tartibida
        </span>
      </div>

      <!-- Empty state -->
      <div v-if="recentPosts.length === 0" class="p-12 text-center">
        <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-3">
          <DocumentTextIcon class="w-7 h-7 text-gray-400 dark:text-gray-500" />
        </div>
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Hali postlar yo'q</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
          Kanalga birinchi post chiqaring — bir necha soniyada bu yerda paydo bo'ladi
        </p>
      </div>

      <!-- Post cards -->
      <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700">
        <li v-for="post in recentPosts" :key="post.id"
            class="group relative px-5 py-4 hover:bg-gray-50/60 dark:hover:bg-gray-700/20 transition-colors">
          <div class="flex items-start gap-4">
            <!-- Content type icon -->
            <div :class="contentTypeIconWrapper(post.content_type)" class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center">
              <component :is="contentTypeIcon(post.content_type)" class="w-5 h-5" />
            </div>

            <!-- Body -->
            <div class="flex-1 min-w-0">
              <!-- Top row: type label + date + delta + telegram link -->
              <div class="flex items-center flex-wrap gap-2 mb-1.5 text-xs">
                <span :class="contentTypeBadge(post.content_type)" class="inline-flex items-center px-1.5 py-0.5 rounded font-medium uppercase tracking-wide">
                  {{ contentTypeLabel(post.content_type) }}
                </span>
                <span class="text-gray-400 dark:text-gray-500">·</span>
                <time class="text-gray-500 dark:text-gray-400" :title="post.posted_at">
                  {{ post.posted_at_human }}
                </time>
                <span v-if="post.views_delta_24h > 0"
                      class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 font-medium">
                  <ArrowTrendingUpIcon class="w-3 h-3" />
                  +{{ formatNumber(post.views_delta_24h) }} 24s
                </span>
                <a v-if="post.telegram_link" :href="post.telegram_link" target="_blank" rel="noopener"
                   class="ml-auto text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300 inline-flex items-center gap-1 opacity-70 group-hover:opacity-100 transition-opacity">
                  <span class="hidden sm:inline">Ochish</span>
                  <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                </a>
              </div>

              <!-- Text preview -->
              <p class="text-sm text-gray-800 dark:text-gray-200 leading-relaxed line-clamp-2 mb-3">
                {{ post.text_preview || '(media post — matn yo\'q)' }}
              </p>

              <!-- Stats row -->
              <div class="flex items-center flex-wrap gap-x-5 gap-y-1.5 text-xs">
                <span class="inline-flex items-center gap-1.5 text-sky-600 dark:text-sky-400">
                  <EyeIcon class="w-4 h-4" />
                  <span class="font-semibold tabular-nums">{{ formatNumber(post.views) }}</span>
                  <span class="text-gray-500 dark:text-gray-400">ko'rish</span>
                </span>
                <span class="inline-flex items-center gap-1.5"
                      :class="post.reactions_count > 0 ? 'text-purple-600 dark:text-purple-400' : 'text-gray-400 dark:text-gray-500'">
                  <HeartIcon class="w-4 h-4" />
                  <span class="font-semibold tabular-nums">{{ formatNumber(post.reactions_count) }}</span>
                  <span class="text-gray-500 dark:text-gray-400">reaksiya</span>
                </span>
                <span v-if="post.forwards_count > 0" class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                  <ShareIcon class="w-4 h-4" />
                  <span class="font-semibold tabular-nums">{{ formatNumber(post.forwards_count) }}</span>
                  <span class="text-gray-500 dark:text-gray-400">forward</span>
                </span>
                <span v-if="post.engagement_rate > 0" class="inline-flex items-center gap-1.5 text-amber-600 dark:text-amber-400 ml-auto">
                  <BoltIcon class="w-4 h-4" />
                  <span class="font-semibold tabular-nums">{{ post.engagement_rate }}%</span>
                  <span class="text-gray-500 dark:text-gray-400">engagement</span>
                </span>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- Disconnect confirm modal -->
    <Teleport to="body">
      <div v-if="showDisconnectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showDisconnectModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Kanalni uzishni tasdiqlang</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">
            «{{ channel.title }}» — statistika yig'ilishi to'xtaydi va bot kanaldan chiqadi.
            Avval yig'ilgan ma'lumot saqlanib qoladi.
          </p>
          <div class="flex items-center justify-end gap-2">
            <button @click="showDisconnectModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              Bekor qilish
            </button>
            <button @click="disconnectChannel" :disabled="isDisconnecting" class="px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-lg disabled:opacity-50">
              Uzish
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </component>
</template>

<script setup>
import { ref, computed, defineAsyncComponent } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import HRLayout from '@/layouts/HRLayout.vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import {
  ChevronLeftIcon,
  ArrowPathIcon,
  TrashIcon,
  ChartBarIcon,
  DocumentTextIcon,
  ArrowTopRightOnSquareIcon,
  EyeIcon,
  HeartIcon,
  ShareIcon,
  BoltIcon,
  FireIcon,
  PaperAirplaneIcon,
  PhotoIcon,
  VideoCameraIcon,
  MusicalNoteIcon,
  MicrophoneIcon,
  PaperClipIcon,
  ChartBarSquareIcon,
  MapPinIcon,
  FilmIcon,
  ArrowTrendingUpIcon,
  ClockIcon,
  CubeIcon,
} from '@heroicons/vue/24/outline';

const apexchart = defineAsyncComponent(() => import('vue3-apexcharts').then(m => m.default || m));

const props = defineProps({
  channel: { type: Object, required: true },
  dailyStats: { type: Array, default: () => [] },
  recentPosts: { type: Array, default: () => [] },
  summary: { type: Object, required: true },
  panelType: { type: String, default: 'business' },
});

const layoutComponent = computed(() => {
  const map = {
    business: BusinessLayout,
    marketing: MarketingLayout,
    saleshead: SalesHeadLayout,
    operator: OperatorLayout,
    hr: HRLayout,
    finance: FinanceLayout,
  };
  return map[props.panelType] || BusinessLayout;
});

const routePrefix = computed(() => `/${props.panelType === 'marketing' ? 'marketing' : 'business'}`);

const isRefreshing = ref(false);
const isDisconnecting = ref(false);
const showDisconnectModal = ref(false);

const hasTrendData = computed(() => props.dailyStats.length > 0);

const subsChartSeries = computed(() => [{
  name: 'Obunachilar',
  data: props.dailyStats.map(d => d.subscriber_count),
}]);

const subsChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'Inter, system-ui, sans-serif', background: 'transparent' },
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2 },
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
  colors: ['#0ea5e9'],
  xaxis: {
    categories: props.dailyStats.map(d => formatDateShort(d.date)),
    labels: { style: { colors: '#9ca3af', fontSize: '11px' } },
    axisBorder: { show: false }, axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      style: { colors: '#9ca3af', fontSize: '11px' },
      formatter: (v) => v >= 1000 ? (v / 1000).toFixed(1) + 'K' : v,
    },
  },
  grid: { borderColor: '#e5e7eb', strokeDashArray: 4, xaxis: { lines: { show: false } } },
  tooltip: { theme: 'dark' },
}));

const viewsChartSeries = computed(() => [
  { name: "Ko'rishlar", type: 'column', data: props.dailyStats.map(d => d.total_views) },
  { name: 'Postlar', type: 'line', data: props.dailyStats.map(d => d.posts_count) },
]);

const viewsChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'Inter, system-ui, sans-serif', background: 'transparent' },
  stroke: { curve: 'smooth', width: [0, 2] },
  colors: ['#a855f7', '#f59e0b'],
  plotOptions: { bar: { columnWidth: '55%', borderRadius: 3 } },
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.dailyStats.map(d => formatDateShort(d.date)),
    labels: { style: { colors: '#9ca3af', fontSize: '11px' } },
    axisBorder: { show: false }, axisTicks: { show: false },
  },
  yaxis: [
    { labels: { style: { colors: '#9ca3af', fontSize: '11px' }, formatter: (v) => v >= 1000 ? (v/1000).toFixed(1)+'K' : v } },
    { opposite: true, labels: { style: { colors: '#9ca3af', fontSize: '11px' } } },
  ],
  grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
  legend: { labels: { colors: '#6b7280' } },
  tooltip: { theme: 'dark' },
}));

const refreshChannel = async () => {
  isRefreshing.value = true;
  try {
    await axios.post(`${routePrefix.value}/telegram-channels/${props.channel.id}/refresh`);
    router.reload({ only: ['channel', 'summary'] });
  } catch (err) {
    alert('Yangilashda xatolik: ' + (err.response?.data?.message || err.message));
  } finally {
    isRefreshing.value = false;
  }
};

const confirmDisconnect = () => {
  showDisconnectModal.value = true;
};

const disconnectChannel = async () => {
  isDisconnecting.value = true;
  try {
    await axios.delete(`${routePrefix.value}/telegram-channels/${props.channel.id}`);
    router.visit(`${routePrefix.value}/telegram-channels`);
  } catch (err) {
    alert('Uzishda xatolik: ' + (err.response?.data?.message || err.message));
  } finally {
    isDisconnecting.value = false;
    showDisconnectModal.value = false;
  }
};

const formatNumber = (n) => {
  if (!n && n !== 0) return '0';
  return new Intl.NumberFormat('uz-UZ').format(n);
};

const formatDateShort = (date) => {
  if (!date) return '';
  const d = typeof date === 'string' ? date.substring(0, 10) : date;
  const [_, m, dd] = d.split('-');
  return `${dd}/${m}`;
};

const initials = (name) => {
  if (!name) return '?';
  return name.trim().split(/\s+/).slice(0, 2).map(s => s[0]).join('').toUpperCase();
};

const contentTypeLabel = (type) => {
  const map = { text: 'Matn', photo: 'Rasm', video: 'Video', animation: 'GIF', audio: 'Audio', voice: 'Ovoz', document: 'Fayl', poll: 'So\'rov', location: 'Joylashuv', other: 'Boshqa' };
  return map[type] || type;
};

const contentTypeBadge = (type) => {
  const map = {
    text: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
    photo: 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
    video: 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
    animation: 'bg-pink-50 dark:bg-pink-900/30 text-pink-700 dark:text-pink-300',
    audio: 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
    voice: 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
    document: 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
    poll: 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300',
    location: 'bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300',
  };
  return map[type] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
};

// Returns the Heroicon component for a content type (used in post cards & top post)
const contentTypeIcon = (type) => {
  const map = {
    text: DocumentTextIcon,
    photo: PhotoIcon,
    video: VideoCameraIcon,
    animation: FilmIcon,
    audio: MusicalNoteIcon,
    voice: MicrophoneIcon,
    document: PaperClipIcon,
    poll: ChartBarSquareIcon,
    location: MapPinIcon,
    other: CubeIcon,
  };
  return map[type] || CubeIcon;
};

// Returns Tailwind classes for the icon wrapper (post list leading icon)
const contentTypeIconWrapper = (type) => {
  const map = {
    text: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
    photo: 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
    video: 'bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
    animation: 'bg-pink-50 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400',
    audio: 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
    voice: 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
    document: 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
    poll: 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400',
    location: 'bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400',
  };
  return map[type] || 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300';
};
</script>
