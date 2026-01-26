<template>
  <component :is="layoutComponent" :title="'Viral: ' + (content.caption_summary || 'Kontent')">
    <!-- Back Button -->
    <div class="mb-6">
      <Link
        :href="route('marketing.trends.index')"
        class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors"
      >
        <ArrowLeftIcon class="w-4 h-4" />
        Orqaga
      </Link>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Video Section -->
      <div>
        <div class="bg-slate-800 rounded-2xl overflow-hidden shadow-xl border border-slate-700">
          <!-- Thumbnail with Play -->
          <div class="relative aspect-[9/16] overflow-hidden cursor-pointer" @click="openVideo">
            <img
              v-if="content.thumbnail_url"
              :src="content.thumbnail_url"
              :alt="content.caption_summary"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full bg-gradient-to-br from-slate-700 to-slate-800 flex items-center justify-center">
              <FilmIcon class="w-24 h-24 text-slate-600" />
            </div>

            <!-- Play Overlay -->
            <div class="absolute inset-0 bg-black/40 hover:bg-black/50 transition-colors flex items-center justify-center">
              <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center hover:scale-110 transition-transform">
                <PlayIcon class="w-10 h-10 text-white ml-1" />
              </div>
            </div>

            <!-- Badges -->
            <div v-if="content.is_super_viral" class="absolute top-4 left-4">
              <span class="px-3 py-1.5 bg-gradient-to-r from-red-500 to-orange-500 text-white text-sm font-bold rounded-full flex items-center gap-1.5 shadow-lg">
                <FireIcon class="w-4 h-4" />
                SUPER VIRAL
              </span>
            </div>
          </div>

          <!-- Stats -->
          <div class="p-6 space-y-4">
            <div class="grid grid-cols-3 gap-4">
              <div class="text-center">
                <div class="text-2xl font-bold text-white">{{ content.formatted_plays }}</div>
                <div class="text-sm text-slate-400 flex items-center justify-center gap-1">
                  <EyeIcon class="w-4 h-4 text-blue-400" />
                  Ko'rishlar
                </div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-white">{{ content.formatted_likes }}</div>
                <div class="text-sm text-slate-400 flex items-center justify-center gap-1">
                  <HeartIcon class="w-4 h-4 text-red-400" />
                  Layklar
                </div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-white">{{ content.formatted_comments }}</div>
                <div class="text-sm text-slate-400 flex items-center justify-center gap-1">
                  <ChatBubbleLeftIcon class="w-4 h-4 text-green-400" />
                  Izohlar
                </div>
              </div>
            </div>

            <!-- Username & Link -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-700">
              <div v-if="content.platform_username" class="flex items-center gap-2">
                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold">
                  {{ content.platform_username.charAt(0).toUpperCase() }}
                </div>
                <span class="text-slate-300 font-medium">@{{ content.platform_username }}</span>
              </div>
              <a
                v-if="content.permalink"
                :href="content.permalink"
                target="_blank"
                class="px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg font-medium hover:from-purple-600 hover:to-pink-600 transition-all flex items-center gap-2"
              >
                <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                Instagram'da ochish
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Details Section -->
      <div class="space-y-6">
        <!-- Hook Score Card -->
        <div class="bg-slate-800 rounded-2xl shadow-xl border border-slate-700 p-6">
          <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
            <SparklesIcon class="w-5 h-5 text-purple-400" />
            Hook Score
          </h3>

          <div class="flex items-center gap-6">
            <div
              :class="hookScoreClass"
              class="w-24 h-24 rounded-2xl flex flex-col items-center justify-center"
            >
              <span class="text-4xl font-bold text-white">{{ content.hook_score || '—' }}</span>
              <span class="text-white/80 text-sm">/10</span>
            </div>

            <div class="flex-1">
              <div class="text-slate-400 text-sm mb-2">Viral darajasi:</div>
              <span
                :class="viralLevelClass"
                class="px-3 py-1 rounded-full text-sm font-medium"
              >
                {{ viralLevelLabel }}
              </span>
            </div>
          </div>
        </div>

        <!-- Caption -->
        <div class="bg-slate-800 rounded-2xl shadow-xl border border-slate-700 p-6">
          <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
            <DocumentTextIcon class="w-5 h-5 text-blue-400" />
            Caption
          </h3>
          <p class="text-slate-300 whitespace-pre-wrap">{{ content.caption || 'Caption mavjud emas' }}</p>
        </div>

        <!-- Music -->
        <div v-if="content.music_title" class="bg-slate-800 rounded-2xl shadow-xl border border-slate-700 p-6">
          <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
            <MusicalNoteIcon class="w-5 h-5 text-pink-400" />
            Musiqa
          </h3>
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-purple-500 rounded-xl flex items-center justify-center">
              <MusicalNoteIcon class="w-6 h-6 text-white" />
            </div>
            <div>
              <div class="text-white font-medium">{{ content.music_title }}</div>
              <div v-if="content.music_artist" class="text-slate-400 text-sm">{{ content.music_artist }}</div>
            </div>
          </div>
        </div>

        <!-- AI Analysis -->
        <div class="bg-gradient-to-br from-purple-500/10 to-blue-500/10 rounded-2xl shadow-xl border border-purple-500/30 p-6">
          <h3 class="text-lg font-semibold text-white mb-6 flex items-center gap-2">
            <LightBulbIcon class="w-5 h-5 text-purple-400" />
            AI Tahlili
          </h3>

          <div class="space-y-6">
            <!-- AI Summary -->
            <div v-if="content.ai_summary" class="bg-slate-800/50 rounded-xl p-4 border border-slate-700">
              <div class="text-purple-400 text-sm font-medium mb-2">Xulosa</div>
              <p class="text-slate-200 italic">"{{ content.ai_summary }}"</p>
            </div>

            <!-- Hook Analysis -->
            <div v-if="content.ai_analysis?.hook_analysis">
              <div class="text-purple-400 text-sm font-medium mb-2">Nima ushlab qoldi?</div>
              <p class="text-slate-300">{{ content.ai_analysis.hook_analysis }}</p>
            </div>

            <!-- Psychology -->
            <div v-if="content.ai_analysis?.psychology">
              <div class="text-blue-400 text-sm font-medium mb-2">Psixologiya</div>
              <div class="flex items-center gap-2 mb-2">
                <span
                  :class="psychologyBadgeClass(content.ai_analysis.psychology.primary_trigger)"
                  class="px-3 py-1 text-sm font-bold rounded-full"
                >
                  {{ content.ai_analysis.psychology.primary_trigger }}
                </span>
              </div>
              <p class="text-slate-400">{{ content.ai_analysis.psychology.explanation }}</p>
            </div>

            <!-- Viral Factors -->
            <div v-if="content.ai_analysis?.viral_factors?.length">
              <div class="text-green-400 text-sm font-medium mb-2">Viral omillar</div>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="factor in content.ai_analysis.viral_factors"
                  :key="factor"
                  class="px-3 py-1 bg-green-500/20 text-green-400 text-sm rounded-full"
                >
                  {{ factor }}
                </span>
              </div>
            </div>

            <!-- Replication Tip -->
            <div v-if="content.ai_analysis?.replication_tip" class="bg-amber-500/10 rounded-xl p-4 border border-amber-500/30">
              <div class="text-amber-400 text-sm font-medium mb-2 flex items-center gap-1">
                <LightBulbIcon class="w-4 h-4" />
                Pro Maslahat
              </div>
              <p class="text-amber-200">{{ content.ai_analysis.replication_tip }}</p>
            </div>
          </div>
        </div>

        <!-- Meta Info -->
        <div class="bg-slate-800 rounded-2xl shadow-xl border border-slate-700 p-6">
          <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
            <InformationCircleIcon class="w-5 h-5 text-slate-400" />
            Ma'lumot
          </h3>
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <div class="text-slate-500">Platforma</div>
              <div class="text-slate-300 capitalize">{{ content.platform }}</div>
            </div>
            <div>
              <div class="text-slate-500">Nisha</div>
              <div class="text-slate-300">#{{ content.niche }}</div>
            </div>
            <div>
              <div class="text-slate-500">Yuklangan</div>
              <div class="text-slate-300">{{ content.fetched_at }}</div>
            </div>
            <div>
              <div class="text-slate-500">Tahlil qilingan</div>
              <div class="text-slate-300">{{ content.analyzed_at }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue'
import OperatorLayout from '@/layouts/OperatorLayout.vue'
import FinanceLayout from '@/layouts/FinanceLayout.vue'
import HRLayout from '@/layouts/HRLayout.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import {
  ArrowLeftIcon,
  PlayIcon,
  EyeIcon,
  HeartIcon,
  ChatBubbleLeftIcon,
  FireIcon,
  SparklesIcon,
  LightBulbIcon,
  MusicalNoteIcon,
  FilmIcon,
  DocumentTextIcon,
  InformationCircleIcon,
  ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/24/solid'

const props = defineProps({
  content: Object,
  panelType: {
    type: String,
    default: 'marketing',
  },
})

// Dynamic layout based on user's panel type
const layoutComponent = computed(() => {
  switch (props.panelType) {
    case 'business':
      return BusinessLayout
    case 'saleshead':
      return SalesHeadLayout
    case 'operator':
      return OperatorLayout
    case 'finance':
      return FinanceLayout
    case 'hr':
      return HRLayout
    case 'admin':
      return AdminLayout
    case 'marketing':
    default:
      return MarketingLayout
  }
})

const openVideo = () => {
  if (props.content.video_url) {
    window.open(props.content.video_url, '_blank')
  } else if (props.content.permalink) {
    window.open(props.content.permalink, '_blank')
  }
}

const hookScoreClass = computed(() => {
  const score = props.content.hook_score || 0
  if (score >= 8) return 'bg-gradient-to-br from-green-500 to-emerald-500'
  if (score >= 6) return 'bg-gradient-to-br from-yellow-500 to-orange-500'
  if (score >= 4) return 'bg-gradient-to-br from-orange-500 to-red-500'
  return 'bg-gradient-to-br from-slate-600 to-slate-700'
})

const viralLevelLabel = computed(() => {
  const level = props.content.viral_level
  const labels = {
    'mega_viral': 'Mega Viral (1M+)',
    'super_viral': 'Super Viral (500K+)',
    'viral': 'Viral (100K+)',
    'trending': 'Trending (50K+)',
    'normal': 'Normal',
  }
  return labels[level] || level
})

const viralLevelClass = computed(() => {
  const level = props.content.viral_level
  const classes = {
    'mega_viral': 'bg-red-500/20 text-red-400',
    'super_viral': 'bg-orange-500/20 text-orange-400',
    'viral': 'bg-yellow-500/20 text-yellow-400',
    'trending': 'bg-blue-500/20 text-blue-400',
    'normal': 'bg-slate-500/20 text-slate-400',
  }
  return classes[level] || 'bg-slate-500/20 text-slate-400'
})

const psychologyBadgeClass = (trigger) => {
  const classes = {
    'FOMO': 'bg-red-500/20 text-red-400',
    'CURIOSITY': 'bg-blue-500/20 text-blue-400',
    'GREED': 'bg-green-500/20 text-green-400',
    'FEAR': 'bg-purple-500/20 text-purple-400',
    'JOY': 'bg-yellow-500/20 text-yellow-400',
    'ANGER': 'bg-orange-500/20 text-orange-400',
  }
  return classes[trigger] || 'bg-slate-500/20 text-slate-400'
}
</script>
