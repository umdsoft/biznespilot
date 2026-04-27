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
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden h-full">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">🔥 Oyning top posti</h3>
          </div>
          <div v-if="summary.top_post_30d" class="p-5">
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-4 line-clamp-4">
              {{ summary.top_post_30d.text_preview || '(media post)' }}
            </p>
            <div class="grid grid-cols-2 gap-3 mb-4">
              <div>
                <p class="text-lg font-bold text-sky-600 dark:text-sky-400">{{ formatNumber(summary.top_post_30d.views) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">ko'rish</p>
              </div>
              <div>
                <p class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ formatNumber(summary.top_post_30d.reactions_count) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">reaksiya</p>
              </div>
            </div>
            <div class="flex items-center justify-between text-xs">
              <span class="text-gray-400">{{ summary.top_post_30d.posted_at_human }}</span>
              <a v-if="summary.top_post_30d.telegram_link"
                 :href="summary.top_post_30d.telegram_link" target="_blank" rel="noopener"
                 class="text-sky-600 dark:text-sky-400 hover:underline inline-flex items-center gap-1">
                Ochish <ArrowTopRightOnSquareIcon class="w-3 h-3" />
              </a>
            </div>
          </div>
          <div v-else class="p-8 text-center">
            <DocumentTextIcon class="w-10 h-10 mx-auto mb-2 text-gray-300 dark:text-gray-600" />
            <p class="text-sm text-gray-400 dark:text-gray-500">Hali postlar yo'q</p>
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

    <!-- Recent posts table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">So'nggi postlar</h3>
      </div>
      <div v-if="recentPosts.length === 0" class="p-8 text-center">
        <DocumentTextIcon class="w-10 h-10 mx-auto mb-2 text-gray-300 dark:text-gray-600" />
        <p class="text-sm text-gray-400 dark:text-gray-500">Hali postlar yo'q</p>
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Post</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ko'rish</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">24s Δ</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">❤️</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">↗️</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ER</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Vaqt</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="post in recentPosts" :key="post.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
              <td class="px-4 py-3 max-w-md">
                <div class="flex items-center gap-2">
                  <span :class="contentTypeBadge(post.content_type)" class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium">
                    {{ contentTypeLabel(post.content_type) }}
                  </span>
                  <a v-if="post.telegram_link" :href="post.telegram_link" target="_blank" rel="noopener" class="text-sm text-gray-900 dark:text-gray-100 hover:text-sky-600 dark:hover:text-sky-400 truncate">
                    {{ post.text_preview || '(media)' }}
                  </a>
                  <span v-else class="text-sm text-gray-900 dark:text-gray-100 truncate">
                    {{ post.text_preview || '(media)' }}
                  </span>
                </div>
              </td>
              <td class="px-4 py-3 text-right text-sm font-medium text-gray-900 dark:text-gray-100">{{ formatNumber(post.views) }}</td>
              <td class="px-4 py-3 text-right text-xs" :class="post.views_delta_24h > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400'">
                {{ post.views_delta_24h > 0 ? '+' + formatNumber(post.views_delta_24h) : '—' }}
              </td>
              <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">{{ formatNumber(post.reactions_count) }}</td>
              <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">{{ formatNumber(post.forwards_count) }}</td>
              <td class="px-4 py-3 text-right text-sm font-medium text-amber-600 dark:text-amber-400">{{ post.engagement_rate }}%</td>
              <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ post.posted_at_human }}</td>
            </tr>
          </tbody>
        </table>
      </div>
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
  const map = { text: 'matn', photo: 'rasm', video: 'video', animation: 'gif', audio: 'audio', voice: 'ovoz', document: 'fayl', poll: 'so\'rov', location: 'joy', other: 'boshqa' };
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
  };
  return map[type] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
};
</script>
