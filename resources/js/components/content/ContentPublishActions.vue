<template>
  <div class="space-y-3">
    <!-- Hashtag preview banner -->
    <div v-if="autoHashtag" class="flex items-start gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 dark:border-blue-800/40 dark:bg-blue-900/20">
      <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <div class="text-xs leading-relaxed">
        <p class="text-blue-900 dark:text-blue-100">
          Sistema bu rejaning brendingiz tagini avtomatik qo'shgan:
          <code class="rounded bg-white px-1 py-0.5 font-mono text-blue-700 dark:bg-blue-950 dark:text-blue-300">{{ autoHashtag }}</code>
        </p>
        <p class="mt-1 text-blue-700/80 dark:text-blue-300/80">
          Bu tag post qachon va qaerda chiqarilganini avtomatik aniqlash uchun zarur — uni saqlab qoling.
        </p>
      </div>
    </div>

    <!-- Action buttons -->
    <div class="flex flex-wrap gap-2">
      <!-- Avtomatik post — sistema o'zi yuboradi -->
      <button
        type="button"
        :disabled="!canAutoPublish || publishing"
        @click="handleAutoPublish"
        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:cursor-not-allowed disabled:opacity-50"
      >
        <svg v-if="publishing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
          <path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
        </svg>
        <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <span>{{ publishing ? "Yuborilmoqda..." : "Avtomatik post" }}</span>
      </button>

      <!-- Copy text — qo'lda paste qilish uchun -->
      <button
        type="button"
        :disabled="copying"
        @click="handleCopyText"
        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
        </svg>
        <span>{{ copying ? "Nusxa olinyapti..." : "Matnni nusxa olish" }}</span>
      </button>

      <!-- Already published indicator -->
      <a
        v-if="item.status === 'published' && item.post_url"
        :href="item.post_url"
        target="_blank"
        rel="noopener"
        class="inline-flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-100 dark:border-green-800/40 dark:bg-green-900/20 dark:text-green-400"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
        </svg>
        <span>Postga o'tish</span>
      </a>
    </div>

    <!-- Match status -->
    <div
      v-if="item.status === 'published' && item.match_method"
      class="rounded-lg bg-gray-50 p-2 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-400"
    >
      <span class="font-medium">Aniqlash usuli:</span>
      {{ matchMethodLabel(item.match_method) }}
      <span v-if="item.match_score" class="ml-1">
        ({{ Math.round(item.match_score * 100) }}% ishonch)
      </span>
    </div>

    <!-- Mediafiles preview if any -->
    <div v-if="mediaUrls.length > 0" class="space-y-1">
      <p class="text-xs font-medium text-gray-600 dark:text-gray-400">
        📎 Yuklangan fayllar ({{ mediaUrls.length }}):
      </p>
      <ul class="space-y-1">
        <li v-for="(url, i) in mediaUrls" :key="i" class="text-xs">
          <a :href="url" target="_blank" rel="noopener" class="text-blue-600 hover:underline dark:text-blue-400 break-all">
            {{ shortUrl(url) }}
          </a>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['updated', 'toast'])

const publishing = ref(false)
const copying = ref(false)
const autoHashtag = ref(props.item.auto_hashtag || null)

const mediaUrls = computed(() => {
  const urls = props.item.media_urls
  if (!urls) return []
  return Array.isArray(urls) ? urls.filter(Boolean) : []
})

const canAutoPublish = computed(() => {
  return props.item.status !== 'published'
    && (props.item.platform === 'telegram' || props.item.channel === 'telegram')
})

// Hashtag preview — agar DB'da yo'q bo'lsa, sistema beradi
onMounted(async () => {
  if (autoHashtag.value) return
  try {
    const { data } = await axios.get(`/business/content-calendar/${props.item.id}/preview-hashtag`)
    if (data?.success) autoHashtag.value = data.hashtag
  } catch (e) {
    // Silent — hashtag preview majburiy emas
  }
})

async function handleAutoPublish() {
  if (publishing.value) return
  if (!confirm("Hozir Telegram kanalga avtomatik post qilamizmi? Bu amalni qaytarib bo'lmaydi.")) return

  publishing.value = true
  try {
    const { data } = await axios.post(`/business/content-calendar/${props.item.id}/auto-publish`)
    if (data.success) {
      emit('toast', { type: 'success', message: data.message || "Muvaffaqiyatli chop etildi!" })
      emit('updated', data.item)
    } else {
      emit('toast', { type: 'error', message: data.message || "Publish qilinmadi" })
    }
  } catch (e) {
    const msg = e?.response?.data?.message || "Texnik xatolik"
    emit('toast', { type: 'error', message: msg })
  } finally {
    publishing.value = false
  }
}

async function handleCopyText() {
  if (copying.value) return
  copying.value = true
  try {
    const { data } = await axios.get(`/business/content-calendar/${props.item.id}/publish-text`)
    if (!data?.success) throw new Error(data?.message || "Xatolik")

    await navigator.clipboard.writeText(data.text)
    emit('toast', {
      type: 'success',
      message: `Nusxa olindi! ${data.has_watermark ? "(yashirin tracking faol)" : ""}`,
    })

    // Agar media fayllar bo'lsa — ularni ham ko'rsatamiz
    if (data.media_urls?.length > 0) {
      setTimeout(() => {
        emit('toast', {
          type: 'info',
          message: `📎 ${data.media_urls.length} ta fayl ham bor — pastdagi havolalardan yuklab oling`,
        })
      }, 1500)
    }
  } catch (e) {
    const msg = e?.response?.data?.message || e?.message || "Nusxa olishda xatolik"
    emit('toast', { type: 'error', message: msg })
  } finally {
    copying.value = false
  }
}

function shortUrl(url) {
  try {
    const u = new URL(url, window.location.origin)
    const path = u.pathname.split('/').pop() || u.hostname
    return path.length > 40 ? path.slice(0, 37) + '...' : path
  } catch {
    return url.length > 40 ? url.slice(0, 37) + '...' : url
  }
}

function matchMethodLabel(method) {
  const map = {
    direct: "🎯 Avtomatik post (sistema yubordi)",
    hashtag: "🏷 Hashtag orqali aniqlandi",
    watermark: "🔍 Yashirin watermark orqali",
    fuzzy: "🔮 Smart match",
    manual: "✋ Qo'lda belgilangan",
  }
  return map[method] || method
}
</script>
