<template>
  <component :is="layoutComponent" title="Smart Content Plan">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Smart Kontent Reja</h2>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ business?.name }} uchun haftalik kontent tavsiyalari
          </p>
        </div>
        <button
          @click="generatePlan"
          :disabled="generating"
          class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-sm font-medium rounded-xl hover:from-emerald-700 hover:to-teal-700 disabled:opacity-50 transition-all flex items-center gap-2 shadow-lg shadow-emerald-500/25"
        >
          <BoltIcon v-if="!generating" class="w-5 h-5" />
          <svg v-else class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ generating ? 'Yaratilmoqda...' : 'Haftalik reja yaratish' }}
        </button>
      </div>
    </div>

    <!-- Success -->
    <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
      <div v-if="successMessage" class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
        <div class="flex items-center gap-3">
          <CheckCircleIcon class="w-5 h-5 text-green-600 flex-shrink-0" />
          <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ successMessage }}</p>
        </div>
      </div>
    </Transition>

    <!-- Error -->
    <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
      <div v-if="errorMessage" class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
        <div class="flex items-center gap-3">
          <ExclamationCircleIcon class="w-5 h-5 text-red-600 flex-shrink-0" />
          <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ errorMessage }}</p>
        </div>
      </div>
    </Transition>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400">Tavsiyalar</p>
        <p class="text-2xl font-bold text-emerald-600">{{ recommendations.length }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400">Soha tahlili</p>
        <p class="text-2xl font-bold" :class="nicheCount > 0 ? 'text-blue-600' : 'text-gray-400'">{{ nicheCount }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400">Mijoz muammolari</p>
        <p class="text-2xl font-bold" :class="painCount > 0 ? 'text-purple-600' : 'text-gray-400'">{{ painCount }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400">Joylashtirish vaqti</p>
        <p class="text-2xl font-bold text-pink-600">{{ bestPostTime }}</p>
      </div>
    </div>

    <!-- Content mix bar -->
    <div v-if="igSchedule?.content_mix" class="mb-6 bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
      <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-3">Kontent turlari taqsimoti</p>
      <div class="flex gap-2">
        <div v-for="(ratio, type) in igSchedule.content_mix" :key="type" class="flex-1">
          <div class="h-2 rounded-full mb-1" :class="mixBarColors[type]" :style="{ opacity: 0.4 + ratio }"></div>
          <div class="flex items-center justify-between">
            <span class="text-xs text-gray-500">{{ typeLabels[type] || type }}</span>
            <span class="text-xs font-bold" :class="mixTextColors[type]">{{ (ratio * 100).toFixed(0) }}%</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!recommendations.length && !generating" class="text-center py-16 bg-white dark:bg-gray-800 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700">
      <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 flex items-center justify-center">
        <BoltIcon class="w-8 h-8 text-emerald-600 dark:text-emerald-400" />
      </div>
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Hali tavsiyalar yaratilmagan</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6">
        Sizning sohangiz va biznesingiz uchun maxsus kontent tavsiyalari yaratish uchun tugmani bosing
      </p>
      <button
        @click="generatePlan"
        class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-sm font-medium rounded-xl hover:from-emerald-700 hover:to-teal-700 transition-all inline-flex items-center gap-2 shadow-lg shadow-emerald-500/25"
      >
        <BoltIcon class="w-5 h-5" />
        Haftalik reja yaratish
      </button>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="generating && !recommendations.length" class="space-y-3">
      <div v-for="i in 5" :key="'skeleton-'+i" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 animate-pulse">
        <div class="flex items-start gap-4">
          <div class="w-10 h-10 rounded-xl bg-gray-200 dark:bg-gray-700"></div>
          <div class="flex-1 space-y-2">
            <div class="flex gap-2">
              <div class="w-16 h-5 rounded-full bg-gray-200 dark:bg-gray-700"></div>
              <div class="w-20 h-5 rounded-full bg-gray-200 dark:bg-gray-700"></div>
            </div>
            <div class="w-3/4 h-4 rounded bg-gray-200 dark:bg-gray-700"></div>
            <div class="w-1/2 h-3 rounded bg-gray-100 dark:bg-gray-700/50"></div>
          </div>
          <div class="w-10 h-10 rounded bg-gray-200 dark:bg-gray-700"></div>
        </div>
      </div>
    </div>

    <!-- Recommendations List -->
    <div v-if="recommendations.length" class="space-y-3">
      <div
        v-for="item in recommendations"
        :key="item.order"
        class="bg-white dark:bg-gray-800 rounded-xl border transition-all duration-200 cursor-pointer"
        :class="expandedItem === item.order ? 'border-emerald-400 dark:border-emerald-600 shadow-lg shadow-emerald-500/10' : 'border-gray-200 dark:border-gray-700 hover:border-emerald-300 dark:hover:border-emerald-700 hover:shadow-md'"
        @click="toggleItem(item.order)"
      >
        <div class="p-5">
          <div class="flex items-start gap-4">
            <!-- Day + Order -->
            <div class="flex-shrink-0 text-center w-16">
              <div class="w-10 h-10 mx-auto rounded-xl flex items-center justify-center text-white text-sm font-bold" :class="sourceColors[item.source] || 'bg-gray-500'">
                {{ item.order }}
              </div>
              <p class="text-xs text-gray-500 mt-1">{{ item.day }}</p>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="typeColors[item.type] || 'bg-gray-100 text-gray-700'">{{ typeLabels[item.type] || item.type }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full" :class="purposeColors[item.purpose] || 'bg-gray-100 text-gray-600'">{{ purposeLabels[item.purpose] || item.purpose }}</span>
                <span v-if="item.is_ai_generated" class="text-xs px-2 py-0.5 rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400 font-medium flex items-center gap-1">
                  <SparklesIcon class="w-3 h-3" />
                  AI
                </span>
                <span class="text-xs text-gray-400">{{ item.time }}</span>
              </div>
              <p class="font-medium text-gray-900 dark:text-white mb-1">{{ item.topic }}</p>
              <p v-if="item.hook && expandedItem !== item.order" class="text-sm text-gray-500 dark:text-gray-400 truncate">
                {{ item.hook }}
              </p>
            </div>

            <!-- Score + Chevron -->
            <div class="flex-shrink-0 text-right flex items-center gap-2">
              <div>
                <div class="text-lg font-bold" :class="item.score >= 75 ? 'text-emerald-600' : item.score >= 50 ? 'text-amber-600' : 'text-gray-500'">
                  {{ item.score }}
                </div>
                <p class="text-xs text-gray-400">ball</p>
              </div>
              <ChevronDownIcon class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="expandedItem === item.order ? 'rotate-180' : ''" />
            </div>
          </div>
        </div>

        <!-- Expanded Detail -->
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 max-h-0"
          enter-to-class="opacity-100 max-h-96"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 max-h-96"
          leave-to-class="opacity-0 max-h-0"
        >
          <div v-if="expandedItem === item.order" class="px-5 pb-5 border-t border-gray-100 dark:border-gray-700 pt-4 overflow-hidden">
            <div class="pl-20 space-y-3">
              <!-- AI Hooklar (AI bilan boyitilgan bo'lsa) -->
              <div v-if="item.ai_hooks?.length" class="bg-violet-50 dark:bg-violet-900/20 rounded-lg p-3">
                <p class="text-xs font-medium text-violet-800 dark:text-violet-300 mb-2 flex items-center gap-1">
                  <SparklesIcon class="w-3.5 h-3.5" />
                  AI hooklar (boshlash uchun g'oyalar):
                </p>
                <div class="space-y-1.5">
                  <p v-for="(hook, hi) in item.ai_hooks" :key="hi" class="text-sm text-violet-700 dark:text-violet-400 flex items-start gap-2">
                    <span class="text-violet-400 dark:text-violet-500 font-bold mt-0.5">{{ hi + 1 }}.</span>
                    <span class="italic">"{{ hook }}"</span>
                  </p>
                </div>
              </div>

              <!-- Oddiy hook (AI bo'lmasa) -->
              <div v-else-if="item.hook" class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-3">
                <p class="text-xs font-medium text-emerald-800 dark:text-emerald-300 mb-1">Boshlash uchun g'oya:</p>
                <p class="text-sm text-emerald-700 dark:text-emerald-400 italic">"{{ item.hook }}"</p>
              </div>

              <!-- AI Ssenariy (reel/carousel uchun) -->
              <div v-if="item.ai_script" class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3">
                <p class="text-xs font-medium text-amber-800 dark:text-amber-300 mb-2 flex items-center gap-1">
                  <SparklesIcon class="w-3.5 h-3.5" />
                  Ssenariy:
                </p>
                <p class="text-sm text-amber-700 dark:text-amber-400 whitespace-pre-line">{{ item.ai_script }}</p>
              </div>

              <!-- AI yozuv matni -->
              <div v-if="item.ai_caption && item.is_ai_generated" class="bg-sky-50 dark:bg-sky-900/20 rounded-lg p-3">
                <p class="text-xs font-medium text-sky-800 dark:text-sky-300 mb-2 flex items-center gap-1">
                  <SparklesIcon class="w-3.5 h-3.5" />
                  Post yozuvi:
                </p>
                <p class="text-sm text-sky-700 dark:text-sky-400 whitespace-pre-line">{{ item.ai_caption }}</p>
              </div>

              <!-- AI CTA -->
              <div v-if="item.ai_cta" class="bg-rose-50 dark:bg-rose-900/20 rounded-lg p-3">
                <p class="text-xs font-medium text-rose-800 dark:text-rose-300 mb-1 flex items-center gap-1">
                  <SparklesIcon class="w-3.5 h-3.5" />
                  Harakatga chaqiruv:
                </p>
                <p class="text-sm text-rose-700 dark:text-rose-400 italic">"{{ item.ai_cta }}"</p>
              </div>

              <!-- Muammo va Natija -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div v-if="item.problem" class="bg-red-50 dark:bg-red-900/10 rounded-lg p-3">
                  <p class="text-xs font-medium text-red-700 dark:text-red-400 mb-1">Qaysi muammoga javob:</p>
                  <p class="text-sm text-red-600 dark:text-red-300">{{ item.problem }}</p>
                </div>
                <div v-if="item.expected_result" class="bg-blue-50 dark:bg-blue-900/10 rounded-lg p-3">
                  <p class="text-xs font-medium text-blue-700 dark:text-blue-400 mb-1">Kutilayotgan natija:</p>
                  <p class="text-sm text-blue-600 dark:text-blue-300">{{ item.expected_result }}</p>
                </div>
              </div>

              <!-- Sabab -->
              <p v-if="item.reason" class="text-sm text-gray-600 dark:text-gray-400 flex items-start gap-1.5">
                <LightBulbIcon class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" />
                <span>{{ item.reason }}</span>
              </p>

              <!-- Platformalar va Meta -->
              <div class="flex items-center gap-2 flex-wrap text-xs text-gray-500">
                <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700">{{ item.day }}</span>
                <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700">{{ item.time }}</span>
                <span class="px-2 py-0.5 rounded text-white" :class="sourceColors[item.source] || 'bg-gray-500'">
                  {{ sourceLabels[item.source] || 'Tavsiya' }}
                </span>
                <template v-if="item.platforms?.length">
                  <span class="text-gray-300 dark:text-gray-600">|</span>
                  <span v-for="platform in item.platforms" :key="platform"
                    class="px-2 py-0.5 rounded-full text-xs font-medium"
                    :class="platformColors[platform] || 'bg-gray-100 text-gray-700'">
                    {{ platformLabels[platform] || platform }}
                  </span>
                </template>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </div>

    <!-- Performance section -->
    <div v-if="performanceSummary?.total_published" class="mt-8 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
      <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
        <ChartBarIcon class="w-4 h-4 text-blue-600" />
        Oxirgi 30 kun natijasi
      </h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="text-center p-2 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
          <p class="text-lg font-bold text-gray-900 dark:text-white">{{ performanceSummary.total_published }}</p>
          <p class="text-xs text-gray-500">Nashr qilingan</p>
        </div>
        <div class="text-center p-2 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
          <p class="text-lg font-bold text-blue-600">{{ (performanceSummary.avg_engagement_rate * 100).toFixed(2) }}%</p>
          <p class="text-xs text-gray-500">Faollik darajasi</p>
        </div>
        <div class="text-center p-2 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
          <p class="text-lg font-bold text-purple-600">{{ formatNumber(performanceSummary.total_reach) }}</p>
          <p class="text-xs text-gray-500">Ko'rganlar</p>
        </div>
        <div class="text-center p-2 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
          <p class="text-lg font-bold" :class="trendColors[performanceSummary.engagement_trend]">
            {{ trendLabels[performanceSummary.engagement_trend] || '—' }}
          </p>
          <p class="text-xs text-gray-500">Dinamika</p>
        </div>
      </div>
    </div>

  </component>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import {
  BoltIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  ChartBarIcon,
  LightBulbIcon,
  ChevronDownIcon,
  SparklesIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  recommendations: { type: Array, default: () => [] },
  igSchedule: { type: Object, default: () => ({}) },
  performanceSummary: { type: Object, default: () => ({}) },
  recentPlans: { type: Array, default: () => [] },
  business: { type: Object, default: null },
  panelType: { type: String, default: 'business' },
});

const layoutComponent = computed(() => {
  return props.panelType === 'marketing' ? MarketingLayout : BusinessLayout;
});

const page = usePage();
const generating = ref(false);
const expandedItem = ref(null);

const successMessage = computed(() => page.props.flash?.success);
const errorMessage = computed(() => page.props.flash?.error);

const toggleItem = (order) => {
  expandedItem.value = expandedItem.value === order ? null : order;
};

// Computed stats
const nicheCount = computed(() => props.recommendations.filter(r => ['niche', 'niche_learning', 'niche_and_pain'].includes(r.source)).length);
const painCount = computed(() => props.recommendations.filter(r => ['pain_point', 'niche_and_pain'].includes(r.source)).length);
const bestPostTime = computed(() => {
  const hours = props.igSchedule?.best_times?.best_hours;
  return hours?.length ? String(hours[0].hour).padStart(2, '0') + ':00' : '19:00';
});

// Colors & labels
const sourceColors = {
  niche: 'bg-blue-600',
  niche_learning: 'bg-emerald-600',
  pain_point: 'bg-purple-600',
  niche_and_pain: 'bg-indigo-600',
  performance: 'bg-cyan-600',
  algorithm: 'bg-cyan-500',
  template: 'bg-gray-500',
};
const sourceLabels = {
  niche: 'Soha tahlili',
  niche_learning: 'Soha tahlili',
  pain_point: "Mijoz so'rovnomasi",
  niche_and_pain: 'Soha + Mijoz',
  performance: 'Natijalar tahlili',
  algorithm: 'Ichki algoritm',
  template: 'Ichki shablon',
};

const typeColors = {
  reel: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
  carousel: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  post: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
  story: 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400',
};

const typeLabels = {
  reel: 'Qisqa video',
  carousel: 'Slaydli post',
  post: 'Post',
  story: 'Hikoya',
};

const purposeColors = {
  educational: 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400',
  engagement: 'bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400',
  behind_scenes: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
  promotional: 'bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400',
  testimonial: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400',
};

const purposeLabels = {
  educational: "Ta'limiy", engagement: 'Faollashtiruvchi',
  behind_scenes: 'Sahna ortida', promotional: 'Reklama', testimonial: 'Mijoz fikri',
};

const platformColors = {
  instagram: 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400',
  telegram: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
  youtube: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
  tiktok: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
  facebook: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
};
const platformLabels = {
  instagram: 'Instagram',
  telegram: 'Telegram',
  youtube: 'YouTube',
  tiktok: 'TikTok',
  facebook: 'Facebook',
};

const mixBarColors = {
  reel: 'bg-purple-500', carousel: 'bg-green-500', post: 'bg-blue-500', story: 'bg-pink-500',
};
const mixTextColors = {
  reel: 'text-purple-600', carousel: 'text-green-600', post: 'text-blue-600', story: 'text-pink-600',
};

const trendColors = {
  rising: 'text-green-600', stable: 'text-blue-600',
  falling: 'text-red-600', insufficient_data: 'text-gray-400',
};
const trendLabels = {
  rising: "O'sishda", stable: 'Barqaror',
  falling: 'Tushishda', insufficient_data: "Ma'lumot kam", no_baseline: "Ma'lumot kam",
};

const generatePlan = () => {
  generating.value = true;
  router.post(route('business.marketing.content-ai.smart-plan.generate-weekly'), {}, {
    preserveScroll: true,
    onFinish: () => {
      generating.value = false;
    },
  });
};

const formatNumber = (num) => {
  if (!num) return '0';
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
  return num.toString();
};
</script>
