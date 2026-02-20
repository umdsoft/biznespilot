<template>
  <component :is="layoutComponent" title="Video → Kontent">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Video → Kontent
          </h2>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Video linkini tashlang — AI transkript va tahlil asosida kuchli kontent yaratadi
          </p>
        </div>
        <Link :href="route('business.marketing.content-ai.index')"
              class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
          Content AI
        </Link>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
      <!-- Left: Form (2/5) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- URL Input -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Video URL</h3>

          <div class="space-y-4">
            <div>
              <input
                v-model="form.video_url"
                type="url"
                placeholder="https://youtube.com/shorts/... yoki https://instagram.com/reel/..."
                class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-1 focus:ring-gray-900 dark:focus:ring-gray-400 focus:border-gray-900 dark:focus:border-gray-400"
                @keyup.enter="submitVideo"
              />
              <p v-if="detectedPlatform" class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                Platform: <span class="font-medium text-gray-700 dark:text-gray-300">{{ platformLabels[detectedPlatform] || detectedPlatform }}</span>
              </p>
            </div>

            <!-- Content Type -->
            <div>
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Kontent turi</label>
              <select v-model="form.content_type"
                      class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-gray-900 dark:focus:ring-gray-400">
                <option value="post">Post</option>
                <option value="carousel">Carousel</option>
                <option value="reel">Reel / Shorts</option>
                <option value="story">Story</option>
                <option value="ad">Reklama</option>
              </select>
            </div>

            <!-- Purpose -->
            <div>
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Maqsad</label>
              <select v-model="form.purpose"
                      class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-gray-900 dark:focus:ring-gray-400">
                <option value="engage">Jalb qilish</option>
                <option value="educate">O'rgatish</option>
                <option value="sell">Sotish</option>
                <option value="inspire">Ilhomlantirish</option>
                <option value="announce">E'lon</option>
                <option value="entertain">Ko'ngil ochar</option>
              </select>
            </div>

            <!-- Target Channel -->
            <div>
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Kanal</label>
              <select v-model="form.target_channel"
                      class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-gray-900 dark:focus:ring-gray-400">
                <option value="instagram">Instagram</option>
                <option value="telegram">Telegram</option>
                <option value="youtube">YouTube</option>
              </select>
            </div>

            <button
              @click="submitVideo"
              :disabled="!form.video_url || submitting"
              class="w-full py-2.5 px-4 text-sm font-medium rounded-lg text-white bg-gray-900 dark:bg-gray-100 dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
            >
              <span v-if="submitting" class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Yuborilmoqda...
              </span>
              <span v-else>Kontent yaratish</span>
            </button>
          </div>
        </div>

        <!-- Processing Status -->
        <div v-if="currentRequest" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Jarayon holati</h3>

          <div class="space-y-3">
            <div v-for="(step, idx) in processingSteps" :key="step.key"
                 class="flex items-center gap-3">
              <!-- Status icon -->
              <div class="flex-shrink-0">
                <div v-if="step.status === 'completed'"
                     class="w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                  <CheckIcon class="w-3.5 h-3.5 text-green-600 dark:text-green-400" />
                </div>
                <div v-else-if="step.status === 'active'"
                     class="w-6 h-6 rounded-full bg-gray-900 dark:bg-gray-100 flex items-center justify-center">
                  <svg class="animate-spin h-3.5 w-3.5 text-white dark:text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                  </svg>
                </div>
                <div v-else-if="step.status === 'failed'"
                     class="w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                  <XMarkIcon class="w-3.5 h-3.5 text-red-600 dark:text-red-400" />
                </div>
                <div v-else
                     class="w-6 h-6 rounded-full border-2 border-gray-200 dark:border-gray-600">
                </div>
              </div>
              <!-- Label -->
              <span class="text-sm" :class="{
                'text-gray-900 dark:text-gray-100 font-medium': step.status === 'active',
                'text-gray-600 dark:text-gray-400': step.status === 'completed',
                'text-red-600 dark:text-red-400': step.status === 'failed',
                'text-gray-400 dark:text-gray-500': step.status === 'pending',
              }">
                {{ step.label }}
              </span>
            </div>
          </div>

          <!-- Error -->
          <div v-if="currentRequest.status === 'failed'" class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <p class="text-xs text-red-700 dark:text-red-300">{{ currentRequest.error_message }}</p>
          </div>

          <!-- Cost -->
          <div v-if="currentRequest.status === 'completed'" class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
              <span>Jami xarajat</span>
              <span class="font-medium text-gray-700 dark:text-gray-300">${{ currentRequest.total_cost?.toFixed(4) || '0' }}</span>
            </div>
            <div v-if="currentRequest.processing_time_ms" class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
              <span>Vaqt</span>
              <span class="font-medium text-gray-700 dark:text-gray-300">{{ (currentRequest.processing_time_ms / 1000).toFixed(1) }}s</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Result + History (3/5) -->
      <div class="lg:col-span-3 space-y-6">
        <!-- Generated Content -->
        <div v-if="generatedContent" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Yaratilgan kontent</h3>
            <button @click="copyContent"
                    class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
              {{ copied ? 'Nusxalandi!' : 'Nusxalash' }}
            </button>
          </div>

          <!-- Video info -->
          <div v-if="currentRequest?.video_title" class="mb-4 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Video</p>
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ currentRequest.video_title }}</p>
            <p v-if="currentRequest.video_duration" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
              {{ formatDuration(currentRequest.video_duration) }}
            </p>
          </div>

          <!-- Content -->
          <div class="prose prose-sm dark:prose-invert max-w-none">
            <pre class="whitespace-pre-wrap text-sm text-gray-800 dark:text-gray-200 font-sans bg-gray-50 dark:bg-gray-900 p-4 rounded-lg border border-gray-100 dark:border-gray-700">{{ generatedContent.generated_content }}</pre>
          </div>

          <!-- Hashtags -->
          <div v-if="generatedContent.generated_hashtags?.length" class="mt-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Hashtaglar</p>
            <div class="flex flex-wrap gap-1.5">
              <span v-for="tag in generatedContent.generated_hashtags" :key="tag"
                    class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded">
                {{ tag }}
              </span>
            </div>
          </div>

          <!-- Key Points from video -->
          <div v-if="currentRequest?.key_points" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
            <details class="group">
              <summary class="text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-300">
                Video tahlili (kalit nuqtalar)
              </summary>
              <div class="mt-3 space-y-3">
                <div v-if="currentRequest.key_points.hooks?.length">
                  <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Hook'lar:</p>
                  <ul class="space-y-1">
                    <li v-for="hook in currentRequest.key_points.hooks" :key="hook" class="text-xs text-gray-700 dark:text-gray-300 pl-3 border-l-2 border-gray-200 dark:border-gray-600">
                      {{ hook }}
                    </li>
                  </ul>
                </div>
                <div v-if="currentRequest.key_points.facts?.length">
                  <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Faktlar:</p>
                  <ul class="space-y-1">
                    <li v-for="fact in currentRequest.key_points.facts" :key="fact" class="text-xs text-gray-700 dark:text-gray-300 pl-3 border-l-2 border-gray-200 dark:border-gray-600">
                      {{ fact }}
                    </li>
                  </ul>
                </div>
                <div v-if="currentRequest.key_points.content_angles?.length">
                  <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Kontent burchaklari:</p>
                  <ul class="space-y-1">
                    <li v-for="angle in currentRequest.key_points.content_angles" :key="angle" class="text-xs text-gray-700 dark:text-gray-300 pl-3 border-l-2 border-gray-200 dark:border-gray-600">
                      {{ angle }}
                    </li>
                  </ul>
                </div>
              </div>
            </details>
          </div>
        </div>

        <!-- Transcript -->
        <div v-if="currentRequest?.transcript" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
          <details class="group">
            <summary class="text-sm font-semibold text-gray-900 dark:text-gray-100 cursor-pointer hover:text-gray-600 dark:hover:text-gray-400">
              Transkript
            </summary>
            <pre class="mt-3 whitespace-pre-wrap text-xs text-gray-700 dark:text-gray-300 font-sans bg-gray-50 dark:bg-gray-900 p-4 rounded-lg max-h-96 overflow-y-auto">{{ currentRequest.transcript }}</pre>
          </details>
        </div>

        <!-- Recent Requests -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Oxirgi so'rovlar</h3>

          <div v-if="recentRequests?.length" class="space-y-2">
            <button v-for="req in recentRequests" :key="req.id"
                    @click="loadRequest(req)"
                    class="w-full text-left p-3 rounded-lg border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
              <div class="flex items-center justify-between">
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                    {{ req.video_title || truncateUrl(req.video_url) }}
                  </p>
                  <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                      {{ platformLabels[req.platform] || '—' }}
                    </span>
                    <span class="text-xs text-gray-300 dark:text-gray-600">|</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                      {{ formatDate(req.created_at) }}
                    </span>
                  </div>
                </div>
                <span class="flex-shrink-0 ml-3 px-2 py-0.5 text-xs rounded-full"
                      :class="statusClasses[req.status] || 'bg-gray-100 text-gray-600'">
                  {{ statusLabels[req.status] || req.status }}
                </span>
              </div>
            </button>
          </div>
          <p v-else class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
            Hali so'rov yo'q
          </p>
        </div>
      </div>
    </div>
  </component>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import { CheckIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  recentRequests: { type: Array, default: () => [] },
  panelType: { type: String, default: 'business' },
});

const layoutComponent = computed(() => {
  const layouts = { business: BusinessLayout, marketing: MarketingLayout };
  return layouts[props.panelType] || BaseLayout;
});

const form = ref({
  video_url: '',
  content_type: 'post',
  purpose: 'engage',
  target_channel: 'instagram',
});

const submitting = ref(false);
const currentRequest = ref(null);
const generatedContent = ref(null);
const copied = ref(false);
let pollInterval = null;

const platformLabels = {
  youtube: 'YouTube',
  instagram: 'Instagram',
  tiktok: 'TikTok',
};

const statusLabels = {
  pending: 'Kutilmoqda',
  extracting: 'Audio...',
  transcribing: 'STT...',
  analyzing: 'Tahlil...',
  generating: 'Yaratilmoqda',
  completed: 'Tayyor',
  failed: 'Xato',
};

const statusClasses = {
  pending: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
  extracting: 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
  transcribing: 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
  analyzing: 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
  generating: 'bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
  completed: 'bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400',
  failed: 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400',
};

const stepKeys = ['extracting', 'transcribing', 'analyzing', 'generating', 'completed'];
const stepLabels = {
  extracting: 'Videodan audio ajratish',
  transcribing: 'Audio → Matn (Whisper STT)',
  analyzing: 'Transkript tahlili (Claude AI)',
  generating: 'Kontent yaratish',
  completed: 'Tayyor',
};

const detectedPlatform = computed(() => {
  const url = form.value.video_url;
  if (!url) return null;
  if (/youtube\.com|youtu\.be/i.test(url)) return 'youtube';
  if (/instagram\.com/i.test(url)) return 'instagram';
  if (/tiktok\.com/i.test(url)) return 'tiktok';
  return null;
});

const processingSteps = computed(() => {
  if (!currentRequest.value) return [];
  const currentStatus = currentRequest.value.status;
  const currentIdx = stepKeys.indexOf(currentStatus);
  const isFailed = currentStatus === 'failed';

  return stepKeys.map((key, idx) => {
    let status = 'pending';
    if (isFailed) {
      // Failed durumda — oxirgi active step ni failed qilish
      if (idx < currentIdx) status = 'completed';
      else if (idx === currentIdx) status = 'failed';
      // Agar hech bir step active bo'lmagan bo'lsa (pending da failed), birinchisini failed qilish
      else if (currentIdx === -1 && idx === 0) status = 'failed';
    } else if (currentIdx >= 0) {
      if (idx < currentIdx) status = 'completed';
      else if (idx === currentIdx) {
        status = currentStatus === 'completed' ? 'completed' : 'active';
      }
    }
    return { key, label: stepLabels[key], status };
  });
});

const submitVideo = async () => {
  if (!form.value.video_url || submitting.value) return;

  submitting.value = true;
  generatedContent.value = null;

  try {
    const response = await axios.post(route('business.marketing.content-ai.video-content.submit'), form.value);
    currentRequest.value = response.data.request;
    startPolling(currentRequest.value.id);
  } catch (error) {
    const msg = error.response?.data?.error || error.response?.data?.message || error.message;
    alert('Xatolik: ' + msg);
  } finally {
    submitting.value = false;
  }
};

const startPolling = (requestId) => {
  stopPolling();
  pollInterval = setInterval(async () => {
    try {
      const response = await axios.get(route('business.marketing.content-ai.video-content.status', requestId));
      currentRequest.value = response.data.request;

      if (['completed', 'failed'].includes(response.data.request.status)) {
        stopPolling();
        if (response.data.request.status === 'completed' && response.data.request.content_generation) {
          generatedContent.value = response.data.request.content_generation;
        }
      }
    } catch (error) {
      console.error('Polling error:', error);
      stopPolling();
    }
  }, 3000);
};

const stopPolling = () => {
  if (pollInterval) {
    clearInterval(pollInterval);
    pollInterval = null;
  }
};

const loadRequest = (req) => {
  currentRequest.value = req;
  if (req.status === 'completed' && req.content_generation) {
    generatedContent.value = req.content_generation;
  } else if (['pending', 'extracting', 'transcribing', 'analyzing', 'generating'].includes(req.status)) {
    generatedContent.value = null;
    startPolling(req.id);
  } else {
    generatedContent.value = null;
  }
};

const copyContent = () => {
  if (generatedContent.value?.generated_content) {
    navigator.clipboard.writeText(generatedContent.value.generated_content);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
  }
};

const truncateUrl = (url) => {
  if (!url) return '';
  return url.length > 50 ? url.substring(0, 50) + '...' : url;
};

const formatDuration = (seconds) => {
  if (!seconds) return '';
  const m = Math.floor(seconds / 60);
  const s = seconds % 60;
  return `${m}:${String(s).padStart(2, '0')}`;
};

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('uz-UZ', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });
};

onUnmounted(() => {
  stopPolling();
});
</script>
