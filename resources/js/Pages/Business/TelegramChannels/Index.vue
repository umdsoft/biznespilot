<template>
  <BusinessLayout title="Telegram Kanallar">
    <!-- Header -->
    <div class="mb-6 flex items-start justify-between flex-wrap gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
          <PaperAirplaneIcon class="w-6 h-6 text-sky-500 -rotate-45" />
          Telegram Kanallar
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          System Bot'ni kanalingizga admin qiling — har kuni statistik hisobot oling
        </p>
      </div>

      <button
        @click="openConnectModal"
        class="inline-flex items-center gap-2 px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white font-medium rounded-lg transition-colors shadow-sm"
      >
        <PlusIcon class="w-5 h-5" />
        Yangi kanal ulash
      </button>
    </div>

    <!-- Configuration warning -->
    <div
      v-if="!isSystemBotConfigured"
      class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl flex items-start gap-3"
    >
      <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
      <div class="text-sm">
        <p class="font-medium text-amber-800 dark:text-amber-300">System Bot sozlanmagan</p>
        <p class="text-amber-700 dark:text-amber-400 mt-1">
          Administrator <code class="px-1 py-0.5 bg-amber-100 dark:bg-amber-900/40 rounded">TELEGRAM_SYSTEM_BOT_TOKEN</code> va
          <code class="px-1 py-0.5 bg-amber-100 dark:bg-amber-900/40 rounded">TELEGRAM_SYSTEM_BOT_USERNAME</code> sozlamalarini kiritsin.
        </p>
      </div>
    </div>

    <div
      v-else-if="!userTelegramLinked"
      class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl flex items-start gap-3"
    >
      <InformationCircleIcon class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
      <div class="text-sm flex-1">
        <p class="font-medium text-blue-800 dark:text-blue-300">Telegram hisob ulanmagan</p>
        <p class="text-blue-700 dark:text-blue-400 mt-1">
          Kanal kuzatish uchun avval Telegram hisobingizni ulang.
          <Link href="/business/settings" class="underline font-medium">Sozlamalarga o'tish</Link>
        </p>
      </div>
    </div>

    <!-- Empty state -->
    <div
      v-if="channels.length === 0"
      class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center"
    >
      <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-sky-50 dark:bg-sky-900/20 flex items-center justify-center">
        <PaperAirplaneIcon class="w-8 h-8 text-sky-500 -rotate-45" />
      </div>
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">Hali kanal ulanmagan</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
        BiznesPilot System Bot'ni Telegram kanalingizga admin qilib qo'shing —
        har kuni ertalab 08:00 da obunachilar, ko'rishlar va reaksiyalar hisobotini olasiz.
      </p>
      <button
        v-if="userTelegramLinked && isSystemBotConfigured"
        @click="openConnectModal"
        class="inline-flex items-center gap-2 px-5 py-2.5 bg-sky-500 hover:bg-sky-600 text-white font-medium rounded-lg transition-colors shadow-sm"
      >
        <PlusIcon class="w-5 h-5" />
        Birinchi kanalni ulash
      </button>
    </div>

    <!-- Channel list -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="channel in channels"
        :key="channel.id"
        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow"
      >
        <!-- Header -->
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center flex-shrink-0 text-white font-bold text-sm">
            <img v-if="channel.photo_url" :src="channel.photo_url" class="w-full h-full rounded-full object-cover" :alt="channel.title" />
            <span v-else>{{ initials(channel.title) }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ channel.title }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
              <span v-if="channel.username">@{{ channel.username }}</span>
              <span v-else>Yopiq kanal</span>
            </p>
          </div>
          <span
            :class="[
              'inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full',
              channel.is_active
                ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400',
            ]"
          >
            <span :class="['w-1.5 h-1.5 rounded-full', channel.is_active ? 'bg-emerald-500' : 'bg-gray-400']"></span>
            {{ channel.is_active ? 'Faol' : 'Uzilgan' }}
          </span>
        </div>

        <!-- Stats -->
        <div class="px-5 py-4 grid grid-cols-2 gap-3">
          <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(channel.subscriber_count) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Obunachilar</p>
          </div>
          <div>
            <p
              class="text-2xl font-bold"
              :class="channel.week_net_growth >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'"
            >
              {{ channel.week_net_growth >= 0 ? '+' : '' }}{{ formatNumber(channel.week_net_growth) }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Haftalik o'sish</p>
          </div>
        </div>

        <!-- Today quick stats -->
        <div v-if="channel.today_posts > 0" class="px-5 py-3 bg-gray-50 dark:bg-gray-900/40 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-xs">
          <div class="flex items-center gap-3 text-gray-600 dark:text-gray-400">
            <span class="inline-flex items-center gap-1"><DocumentTextIcon class="w-3.5 h-3.5" />{{ channel.today_posts }} post</span>
            <span class="inline-flex items-center gap-1"><EyeIcon class="w-3.5 h-3.5" />{{ formatNumber(channel.today_views) }}</span>
          </div>
          <span class="font-medium text-gray-700 dark:text-gray-300">ER {{ channel.today_engagement_rate }}%</span>
        </div>

        <!-- Action row -->
        <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
          <p class="text-xs text-gray-400 dark:text-gray-500">
            Oxirgi sync: {{ channel.last_synced_at || 'hali yo\'q' }}
          </p>
          <div class="flex items-center gap-2">
            <Link
              :href="`/business/telegram-channels/${channel.id}`"
              class="text-sm font-medium text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300"
            >
              Batafsil &rarr;
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Connect Modal -->
    <Teleport to="body">
      <div
        v-if="showConnectModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="closeConnectModal"
      >
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
          <div class="flex items-start justify-between mb-4">
            <div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Kanalni ulash</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">BiznesPilot botni kanalga qo'shing</p>
            </div>
            <button @click="closeConnectModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <div v-if="connectLoading" class="py-8 flex items-center justify-center">
            <div class="animate-spin w-6 h-6 border-2 border-sky-500 border-t-transparent rounded-full"></div>
          </div>

          <div v-else-if="connectError" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-400">
            {{ connectError }}
          </div>

          <div v-else-if="connectData">
            <ol class="space-y-2 mb-4">
              <li v-for="(step, idx) in connectData.steps" :key="idx" class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-400 text-xs font-bold flex items-center justify-center">
                  {{ idx + 1 }}
                </span>
                <span>{{ step }}</span>
              </li>
            </ol>

            <a
              :href="connectData.link"
              target="_blank"
              rel="noopener"
              class="flex items-center justify-center gap-2 w-full px-4 py-3 bg-sky-500 hover:bg-sky-600 text-white font-medium rounded-lg transition-colors"
            >
              <PaperAirplaneIcon class="w-5 h-5 -rotate-45" />
              Telegram'da ochish
            </a>

            <p class="text-xs text-gray-400 dark:text-gray-500 mt-3 text-center">
              Bot Telegram'da kanalni tanlashni so'raydi.
              <br>
              Admin qilib qo'shganingizdan so'ng shu sahifani yangilang.
            </p>
          </div>
        </div>
      </div>
    </Teleport>
  </BusinessLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import {
  PaperAirplaneIcon,
  PlusIcon,
  DocumentTextIcon,
  EyeIcon,
  XMarkIcon,
  InformationCircleIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  channels: { type: Array, default: () => [] },
  botUsername: { type: String, default: null },
  isSystemBotConfigured: { type: Boolean, default: false },
  userTelegramLinked: { type: Boolean, default: false },
});

const showConnectModal = ref(false);
const connectLoading = ref(false);
const connectData = ref(null);
const connectError = ref(null);

const openConnectModal = async () => {
  showConnectModal.value = true;
  connectData.value = null;
  connectError.value = null;
  connectLoading.value = true;

  try {
    const response = await axios.post('/business/telegram-channels/connect-link');
    connectData.value = response.data;
  } catch (err) {
    connectError.value = err.response?.data?.message || 'Havolani olishda xatolik';
  } finally {
    connectLoading.value = false;
  }
};

const closeConnectModal = () => {
  showConnectModal.value = false;
};

const formatNumber = (n) => {
  if (!n && n !== 0) return '0';
  return new Intl.NumberFormat('uz-UZ').format(n);
};

const initials = (name) => {
  if (!name) return '?';
  return name.trim().split(/\s+/).slice(0, 2).map(s => s[0]).join('').toUpperCase();
};
</script>
