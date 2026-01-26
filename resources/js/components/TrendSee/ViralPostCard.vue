<template>
  <div
    class="group relative bg-slate-800/80 rounded-xl overflow-hidden shadow-lg hover:shadow-xl hover:shadow-purple-500/10 transition-all duration-300 border border-slate-700/50 hover:border-purple-500/40 cursor-pointer"
    @click="openVideo"
  >
    <!-- Thumbnail -->
    <div class="relative aspect-square overflow-hidden">
      <img
        v-if="post.thumbnail_url"
        :src="post.thumbnail_url"
        :alt="post.caption_summary"
        class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500"
        loading="lazy"
      />
      <div v-else class="w-full h-full bg-gradient-to-br from-slate-700 to-slate-800 flex items-center justify-center">
        <FilmIcon class="w-10 h-10 text-slate-600" />
      </div>

      <!-- Gradient Overlay -->
      <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-60 group-hover:opacity-80 transition-opacity" />

      <!-- Play Button (appears on hover) -->
      <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center transform scale-75 group-hover:scale-100 transition-transform">
          <PlayIcon class="w-5 h-5 text-white ml-0.5" />
        </div>
      </div>

      <!-- Hook Score Badge (top right) -->
      <div class="absolute top-2 right-2">
        <div
          :class="hookScoreClass"
          class="px-1.5 py-0.5 rounded-md text-[10px] font-bold flex items-center gap-0.5 shadow-lg backdrop-blur-sm"
        >
          <SparklesIcon class="w-2.5 h-2.5" />
          {{ post.hook_score || '—' }}/10
        </div>
      </div>

      <!-- Super Viral Badge (top left) -->
      <div v-if="post.is_super_viral" class="absolute top-2 left-2">
        <span class="px-1.5 py-0.5 bg-gradient-to-r from-red-500 to-orange-500 text-white text-[10px] font-bold rounded-md flex items-center gap-0.5 shadow-lg">
          <FireIcon class="w-2.5 h-2.5" />
          VIRAL
        </span>
      </div>

      <!-- Bottom Info Overlay -->
      <div class="absolute bottom-0 left-0 right-0 p-2">
        <!-- Username & Niche -->
        <div class="flex items-center justify-between gap-1 mb-1.5">
          <span v-if="post.platform_username" class="text-[10px] text-white/90 font-medium truncate max-w-[60%]">
            @{{ post.platform_username }}
          </span>
          <span class="px-1.5 py-0.5 bg-purple-500/30 backdrop-blur-sm text-purple-200 text-[9px] font-medium rounded">
            #{{ post.niche }}
          </span>
        </div>

        <!-- Stats Row -->
        <div class="flex items-center justify-between text-[10px] text-white/80">
          <div class="flex items-center gap-0.5">
            <EyeIcon class="w-3 h-3 text-blue-400" />
            <span>{{ post.formatted_plays }}</span>
          </div>
          <div class="flex items-center gap-0.5">
            <HeartIcon class="w-3 h-3 text-red-400" />
            <span>{{ post.formatted_likes }}</span>
          </div>
          <div class="flex items-center gap-0.5">
            <ChatBubbleLeftIcon class="w-3 h-3 text-green-400" />
            <span>{{ post.formatted_comments }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Caption & AI Summary (below image) -->
    <div class="p-2.5">
      <!-- Caption -->
      <p class="text-slate-300 text-xs leading-relaxed line-clamp-2 mb-2">
        {{ post.caption_summary || 'Viral kontent tahlili...' }}
      </p>

      <!-- AI Insight Preview -->
      <div
        v-if="post.ai_summary || post.ai_analysis?.hook_analysis"
        class="flex items-start gap-1.5 p-2 bg-gradient-to-r from-purple-500/10 to-blue-500/10 rounded-lg border border-purple-500/20"
      >
        <LightBulbIcon class="w-3.5 h-3.5 text-purple-400 mt-0.5 flex-shrink-0" />
        <p class="text-[11px] text-purple-200/90 leading-relaxed line-clamp-2">
          {{ post.ai_summary || post.ai_analysis?.hook_analysis }}
        </p>
      </div>

      <!-- Music tag if exists -->
      <div v-if="post.music_title" class="mt-2 flex items-center gap-1">
        <MusicalNoteIcon class="w-3 h-3 text-pink-400" />
        <span class="text-[10px] text-pink-300 truncate">{{ post.music_title }}</span>
      </div>
    </div>

    <!-- Hover Action Bar -->
    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-purple-600/95 to-purple-600/80 backdrop-blur-sm p-2 translate-y-full group-hover:translate-y-0 transition-transform duration-300">
      <div class="flex items-center justify-between">
        <span class="text-white text-xs font-medium">Ko'rish</span>
        <div class="flex items-center gap-2">
          <button
            @click.stop="openInstagram"
            class="p-1 bg-white/20 rounded hover:bg-white/30 transition-colors"
            title="Instagram'da ochish"
          >
            <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5 text-white" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  PlayIcon,
  EyeIcon,
  HeartIcon,
  ChatBubbleLeftIcon,
  FireIcon,
  SparklesIcon,
  LightBulbIcon,
  ArrowTopRightOnSquareIcon,
  MusicalNoteIcon,
  FilmIcon,
} from '@heroicons/vue/24/solid'

const props = defineProps({
  post: {
    type: Object,
    required: true,
  },
})

const openVideo = () => {
  if (props.post.video_url) {
    window.open(props.post.video_url, '_blank')
  } else if (props.post.permalink) {
    window.open(props.post.permalink, '_blank')
  }
}

const openInstagram = () => {
  if (props.post.permalink) {
    window.open(props.post.permalink, '_blank')
  }
}

const hookScoreClass = computed(() => {
  const score = props.post.hook_score || 0
  if (score >= 8) return 'bg-emerald-500/90 text-white'
  if (score >= 6) return 'bg-amber-500/90 text-white'
  if (score >= 4) return 'bg-orange-500/90 text-white'
  return 'bg-slate-600/90 text-slate-300'
})
</script>
