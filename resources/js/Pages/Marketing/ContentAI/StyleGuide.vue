<template>
  <MarketingLayout title="Style Guide">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <div class="flex items-center gap-3">
            <Link :href="route('marketing.content-ai.index')"
                  class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all">
              <ArrowLeftIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
            </Link>
            <div>
              <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                Style Guide
              </h2>
              <p class="mt-1 text-gray-600 dark:text-gray-400">
                Biznesingiz uchun kontent uslubini sozlang
              </p>
            </div>
          </div>
        </div>
        <button
          @click="analyzeExisting"
          :disabled="analyzing"
          class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-medium rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all flex items-center gap-2 disabled:opacity-50"
        >
          <SparklesIcon v-if="!analyzing" class="w-5 h-5" />
          <svg v-else class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ analyzing ? 'Tahlil qilinmoqda...' : 'Avtomatik tahlil' }}
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Form -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Basic Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <PaintBrushIcon class="w-5 h-5 text-pink-600" />
            Asosiy uslub
          </h3>

          <form @submit.prevent="saveStyleGuide" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Tone -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Ohang (Tone)
                </label>
                <select
                  v-model="form.tone"
                  class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                >
                  <option value="formal">Rasmiy (Formal)</option>
                  <option value="casual">Oddiy (Casual)</option>
                  <option value="professional">Professional</option>
                  <option value="friendly">Do'stona (Friendly)</option>
                  <option value="playful">O'ynoqi (Playful)</option>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  {{ toneDescriptions[form.tone] }}
                </p>
              </div>

              <!-- Language Style -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Til uslubi
                </label>
                <select
                  v-model="form.language_style"
                  class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                >
                  <option value="simple">Sodda</option>
                  <option value="technical">Texnik</option>
                  <option value="creative">Ijodiy</option>
                  <option value="persuasive">Ishontiruvchi</option>
                </select>
              </div>

              <!-- Emoji Frequency -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Emoji ishlatish
                </label>
                <select
                  v-model="form.emoji_frequency"
                  class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                >
                  <option value="none">Ishlatilmasin</option>
                  <option value="low">Kam (1-2 ta)</option>
                  <option value="medium">O'rtacha (3-5 ta)</option>
                  <option value="high">Ko'p (5+ ta)</option>
                </select>
              </div>

              <!-- Avg Post Length -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  O'rtacha post uzunligi (belgi)
                </label>
                <input
                  type="number"
                  v-model.number="form.avg_post_length"
                  min="50"
                  max="2200"
                  class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                />
              </div>
            </div>

            <!-- Common Emojis -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Sevimli emojilar
              </label>
              <input
                type="text"
                v-model="emojiInput"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                placeholder="🚀 💡 ✨ 🎯 (vergul bilan ajrating)"
              />
              <div v-if="form.common_emojis?.length" class="mt-2 flex flex-wrap gap-2">
                <span v-for="emoji in form.common_emojis" :key="emoji"
                      class="px-2 py-1 text-lg bg-gray-100 dark:bg-gray-700 rounded-lg">
                  {{ emoji }}
                </span>
              </div>
            </div>

            <!-- Common Hashtags -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Doimiy hashtaglar
              </label>
              <input
                type="text"
                v-model="hashtagInput"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                placeholder="#biznespilot #marketing #tips (vergul bilan ajrating)"
              />
              <div v-if="form.common_hashtags?.length" class="mt-2 flex flex-wrap gap-2">
                <span v-for="tag in form.common_hashtags" :key="tag"
                      class="px-2 py-1 text-sm bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-full">
                  #{{ tag }}
                </span>
              </div>
            </div>

            <!-- CTA Patterns -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                CTA namunalari
              </label>
              <textarea
                v-model="ctaInput"
                rows="3"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                placeholder="Har bir qatorga bitta CTA yozing:
Hoziroq bog'laning!
Batafsil: link
Izoh qoldiring 👇"
              ></textarea>
            </div>

            <!-- Content Pillars -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Kontent ustunlari (Content Pillars)
              </label>
              <textarea
                v-model="pillarsInput"
                rows="3"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                placeholder="Har bir qatorga bitta mavzu:
Marketing tips
Biznes hikoyalari
Mahsulot yangiliklari"
              ></textarea>
            </div>

            <!-- Submit -->
            <div class="flex justify-end">
              <button
                type="submit"
                :disabled="saving"
                class="px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-medium rounded-lg hover:from-purple-700 hover:to-indigo-700 focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-50 transition-all"
              >
                {{ saving ? 'Saqlanmoqda...' : 'Saqlash' }}
              </button>
            </div>
          </form>
        </div>

        <!-- AI Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <CpuChipIcon class="w-5 h-5 text-indigo-600" />
            AI Sozlamalari
          </h3>

          <div class="space-y-6">
            <!-- AI Model -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                AI Model
              </label>
              <select
                v-model="form.ai_model"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
              >
                <option value="claude-3-haiku">Claude 3 Haiku (Tez, arzon)</option>
                <option value="claude-3-sonnet">Claude 3 Sonnet (O'rtacha)</option>
              </select>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Haiku: ~$0.002/so'rov | Sonnet: ~$0.012/so'rov
              </p>
            </div>

            <!-- Creativity Level -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Ijodkorlik darajasi: {{ (form.creativity_level * 100).toFixed(0) }}%
              </label>
              <input
                type="range"
                v-model.number="form.creativity_level"
                min="0"
                max="1"
                step="0.1"
                class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer"
              />
              <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                <span>Aniq</span>
                <span>Balans</span>
                <span>Ijodiy</span>
              </div>
            </div>

            <!-- Max Tokens Per Month -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Oylik token limiti
              </label>
              <input
                type="number"
                v-model.number="form.max_tokens_per_month"
                min="10000"
                max="10000000"
                step="10000"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
              />
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                ~{{ Math.round(form.max_tokens_per_month / 3000) }} generatsiya (o'rtacha)
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Preview -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <EyeIcon class="w-5 h-5 text-green-600" />
            Ko'rinish
          </h3>
          <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
              {{ previewText }}
            </p>
          </div>
        </div>

        <!-- Usage Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <ChartBarIcon class="w-5 h-5 text-blue-600" />
            Oy statistikasi
          </h3>
          <div class="space-y-4">
            <div>
              <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600 dark:text-gray-400">Tokenlar</span>
                <span class="font-medium text-gray-900 dark:text-white">
                  {{ formatNumber(usage.tokens_used) }} / {{ formatNumber(form.max_tokens_per_month) }}
                </span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full" :style="{ width: tokenUsagePercent + '%' }"></div>
              </div>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600 dark:text-gray-400">Generatsiyalar</span>
              <span class="font-medium text-gray-900 dark:text-white">{{ usage.generations_count }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600 dark:text-gray-400">Xarajat</span>
              <span class="font-medium text-gray-900 dark:text-white">${{ usage.cost?.toFixed(2) || '0.00' }}</span>
            </div>
          </div>
        </div>

        <!-- Tips -->
        <div class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl border border-purple-200 dark:border-purple-800 p-6">
          <h3 class="text-lg font-semibold text-purple-900 dark:text-purple-100 mb-3 flex items-center gap-2">
            <LightBulbIcon class="w-5 h-5 text-purple-600" />
            Maslahatlar
          </h3>
          <ul class="space-y-2 text-sm text-purple-800 dark:text-purple-200">
            <li class="flex items-start gap-2">
              <span class="text-purple-600">•</span>
              "Avtomatik tahlil" tugmasini bosib, mavjud kontentlaringizdan uslub aniqlang
            </li>
            <li class="flex items-start gap-2">
              <span class="text-purple-600">•</span>
              Ijodkorlik darajasini 0.7 atrofida qoldiring eng yaxshi natija uchun
            </li>
            <li class="flex items-start gap-2">
              <span class="text-purple-600">•</span>
              Content Pillars ni to'ldiring - AI shu mavzularga mos kontent yaratadi
            </li>
          </ul>
        </div>
      </div>
    </div>
  </MarketingLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import {
  ArrowLeftIcon,
  PaintBrushIcon,
  SparklesIcon,
  CpuChipIcon,
  EyeIcon,
  ChartBarIcon,
  LightBulbIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  styleGuide: Object,
  usage: {
    type: Object,
    default: () => ({
      tokens_used: 0,
      generations_count: 0,
      cost: 0,
    }),
  },
});

const form = ref({
  tone: props.styleGuide?.tone || 'professional',
  language_style: props.styleGuide?.language_style || 'simple',
  emoji_frequency: props.styleGuide?.emoji_frequency || 'medium',
  avg_post_length: props.styleGuide?.avg_post_length || 200,
  common_emojis: props.styleGuide?.common_emojis || [],
  common_hashtags: props.styleGuide?.common_hashtags || [],
  cta_patterns: props.styleGuide?.cta_patterns || [],
  content_pillars: props.styleGuide?.content_pillars || [],
  ai_model: props.styleGuide?.ai_model || 'claude-3-haiku',
  creativity_level: props.styleGuide?.creativity_level || 0.7,
  max_tokens_per_month: props.styleGuide?.max_tokens_per_month || 100000,
});

const emojiInput = ref(form.value.common_emojis?.join(' ') || '');
const hashtagInput = ref(form.value.common_hashtags?.join(', ') || '');
const ctaInput = ref(form.value.cta_patterns?.join('\n') || '');
const pillarsInput = ref(form.value.content_pillars?.join('\n') || '');

const saving = ref(false);
const analyzing = ref(false);

const toneDescriptions = {
  formal: 'Rasmiy, hurmatli ohang. Biznes va korporativ auditoriya uchun.',
  casual: 'Oddiy, samimiy ohang. Yosh auditoriya uchun.',
  professional: 'Professional, ishonchli ohang. B2B va ekspert kontenti uchun.',
  friendly: 'Do\'stona, iliq ohang. Mijozlar bilan yaqin munosabat uchun.',
  playful: 'O\'ynoqi, qiziqarli ohang. Brend shaxsiyatini ko\'rsatish uchun.',
};

const previewText = computed(() => {
  const emojis = form.value.emoji_frequency === 'none' ? '' :
    form.value.emoji_frequency === 'low' ? '✨' :
    form.value.emoji_frequency === 'medium' ? '✨🚀' : '✨🚀💡🎯';

  const toneText = {
    formal: 'Hurmatli mijozlarimiz, sizga yangiligimizni taqdim etamiz.',
    casual: 'Hey! Yangilik bor, gap borku!',
    professional: 'Bugun muhim yangilik bilan keldik.',
    friendly: 'Salom do\'stlar! Yangilik bor sizlarga!',
    playful: 'Oho-ho! Nimalar bo\'lyapti bilasizmi?',
  };

  return `${emojis} ${toneText[form.value.tone]}\n\n[~${form.value.avg_post_length} belgi kontent...]\n\n${form.value.cta_patterns?.[0] || 'Batafsil: link'}\n\n${form.value.common_hashtags?.slice(0, 3).map(t => '#' + t).join(' ') || '#hashtag'}`;
});

const tokenUsagePercent = computed(() => {
  return Math.min(100, (props.usage.tokens_used / form.value.max_tokens_per_month) * 100);
});

const formatNumber = (num) => {
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
  return num?.toString() || '0';
};

// Watch for input changes
watch(emojiInput, (val) => {
  form.value.common_emojis = val.split(/[\s,]+/).filter(e => e.match(/\p{Emoji}/u));
});

watch(hashtagInput, (val) => {
  form.value.common_hashtags = val.split(/[\s,]+/)
    .map(t => t.replace(/^#/, '').trim())
    .filter(t => t);
});

watch(ctaInput, (val) => {
  form.value.cta_patterns = val.split('\n').map(t => t.trim()).filter(t => t);
});

watch(pillarsInput, (val) => {
  form.value.content_pillars = val.split('\n').map(t => t.trim()).filter(t => t);
});

const saveStyleGuide = async () => {
  saving.value = true;
  try {
    await axios.put(route('marketing.content-ai.style-guide.update'), form.value);
    // Success notification could be added here
  } catch (error) {
    console.error('Save failed:', error);
    alert('Xatolik: ' + (error.response?.data?.message || error.message));
  } finally {
    saving.value = false;
  }
};

const analyzeExisting = async () => {
  analyzing.value = true;
  try {
    const response = await axios.post(route('marketing.content-ai.style-guide.analyze'));
    // Update form with analyzed data
    if (response.data.styleGuide) {
      const sg = response.data.styleGuide;
      form.value.tone = sg.tone || form.value.tone;
      form.value.language_style = sg.language_style || form.value.language_style;
      form.value.emoji_frequency = sg.emoji_frequency || form.value.emoji_frequency;
      form.value.avg_post_length = sg.avg_post_length || form.value.avg_post_length;
      form.value.common_emojis = sg.common_emojis || form.value.common_emojis;
      form.value.common_hashtags = sg.common_hashtags || form.value.common_hashtags;
      form.value.cta_patterns = sg.cta_patterns || form.value.cta_patterns;
      form.value.content_pillars = sg.content_pillars || form.value.content_pillars;

      // Update input fields
      emojiInput.value = form.value.common_emojis?.join(' ') || '';
      hashtagInput.value = form.value.common_hashtags?.join(', ') || '';
      ctaInput.value = form.value.cta_patterns?.join('\n') || '';
      pillarsInput.value = form.value.content_pillars?.join('\n') || '';
    }
  } catch (error) {
    console.error('Analysis failed:', error);
    alert('Tahlil xatosi: ' + (error.response?.data?.message || error.message));
  } finally {
    analyzing.value = false;
  }
};
</script>
