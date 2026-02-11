<template>
  <component :is="layoutComponent" title="Content AI">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
            Content AI
          </h2>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            AI yordamida professional kontent yarating
          </p>
        </div>
        <div class="flex gap-3">
          <Link :href="route('business.marketing.content-ai.ideas.index')"
                class="px-4 py-2 rounded-lg font-medium bg-gradient-to-r from-amber-500 to-orange-500 text-white hover:from-amber-600 hover:to-orange-600 transition-all flex items-center gap-2">
            <LightBulbIcon class="w-4 h-4" />
            G'oyalar
          </Link>
          <Link :href="route('business.marketing.content-ai.style-guide')"
                class="px-4 py-2 rounded-lg font-medium bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
            Style Guide
          </Link>
          <Link :href="route('business.marketing.content-ai.templates')"
                class="px-4 py-2 rounded-lg font-medium bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
            Shablonlar
          </Link>
          <Link :href="route('business.marketing.content-ai.history')"
                class="px-4 py-2 rounded-lg font-medium bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
            Tarix
          </Link>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
            <SparklesIcon class="h-6 w-6" />
          </div>
        </div>
        <p class="text-purple-100 text-sm font-medium mb-1">Jami generatsiya</p>
        <span class="text-3xl font-bold">{{ stats.total_generations }}</span>
      </div>

      <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
            <DocumentTextIcon class="h-6 w-6" />
          </div>
        </div>
        <p class="text-blue-100 text-sm font-medium mb-1">Shablonlar</p>
        <span class="text-3xl font-bold">{{ stats.templates_count }}</span>
      </div>

      <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
            <StarIcon class="h-6 w-6" />
          </div>
        </div>
        <p class="text-green-100 text-sm font-medium mb-1">O'rtacha reyting</p>
        <span class="text-3xl font-bold">{{ stats.avg_rating?.toFixed(1) || '—' }}</span>
      </div>

      <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
            <CurrencyDollarIcon class="h-6 w-6" />
          </div>
        </div>
        <p class="text-amber-100 text-sm font-medium mb-1">Oylik xarajat</p>
        <span class="text-3xl font-bold">${{ stats.monthly_cost?.toFixed(2) || '0.00' }}</span>
      </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Generator Form -->
      <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <SparklesIcon class="w-5 h-5 text-purple-600" />
            Yangi Kontent Yaratish
          </h3>

          <form @submit.prevent="generateContent" class="space-y-6">
            <!-- Offer Selector -->
            <div v-if="activeOffers?.length > 0">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Taklif asosida yaratish (ixtiyoriy)
              </label>
              <select
                v-model="form.offer_id"
                @change="handleOfferSelect"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
              >
                <option value="">Taklifsiz yaratish</option>
                <option v-for="offer in activeOffers" :key="offer.id" :value="offer.id">
                  {{ offer.name }}{{ offer.pricing ? ` — ${formatPrice(offer.pricing)} so'm` : '' }}
                </option>
              </select>
              <p v-if="form.offer_id" class="mt-1 text-xs text-purple-600 dark:text-purple-400">
                AI taklif ma'lumotlari asosida promosion kontent yaratadi
              </p>
            </div>

            <!-- Topic -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Mavzu yoki tavsif *
              </label>
              <textarea
                v-model="form.topic"
                rows="3"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                placeholder="Masalan: Yangi yil aksiyasi haqida post yozing, 20% chegirma, faqat 31-dekabrgacha..."
                required
              ></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Content Type -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Kontent turi
                </label>
                <select
                  v-model="form.content_type"
                  class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                >
                  <option value="post">Post</option>
                  <option value="story">Story</option>
                  <option value="reel">Reel</option>
                  <option value="ad">Reklama</option>
                  <option value="carousel">Carousel</option>
                  <option value="article">Maqola</option>
                </select>
              </div>

              <!-- Purpose -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Maqsad
                </label>
                <select
                  v-model="form.purpose"
                  class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                >
                  <option value="engage">Faollashtirish</option>
                  <option value="sell">Sotish</option>
                  <option value="educate">Ta'lim</option>
                  <option value="inspire">Ilhomlantirish</option>
                  <option value="announce">E'lon</option>
                  <option value="entertain">Ko'ngil ochar</option>
                </select>
              </div>

              <!-- Channel -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Kanal
                </label>
                <select
                  v-model="form.target_channel"
                  class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                >
                  <option value="">Umumiy</option>
                  <option value="instagram">Instagram</option>
                  <option value="telegram">Telegram</option>
                  <option value="facebook">Facebook</option>
                </select>
              </div>

              <!-- Variations -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Variantlar soni
                </label>
                <select
                  v-model="form.variations_count"
                  class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                >
                  <option :value="1">1 variant</option>
                  <option :value="2">2 variant</option>
                  <option :value="3">3 variant</option>
                </select>
              </div>
            </div>

            <!-- Additional prompt -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Qo'shimcha ko'rsatmalar (ixtiyoriy)
              </label>
              <textarea
                v-model="form.additional_prompt"
                rows="2"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                placeholder="Masalan: Emoji ko'proq ishlating, formal ohang..."
              ></textarea>
            </div>

            <!-- Submit -->
            <div class="flex justify-end">
              <button
                type="submit"
                :disabled="generating"
                class="px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-medium rounded-lg hover:from-purple-700 hover:to-indigo-700 focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2"
              >
                <SparklesIcon v-if="!generating" class="w-5 h-5" />
                <svg v-else class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ generating ? 'Generatsiya...' : 'Yaratish' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Generated Result -->
        <div v-if="generatedContent" class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <CheckCircleIcon class="w-5 h-5 text-green-600" />
              Natija
            </h3>
            <div class="flex gap-2">
              <button @click="copyContent" class="px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-all">
                Nusxalash
              </button>
              <button @click="regenerate" class="px-3 py-1.5 text-sm bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-all">
                Qayta yaratish
              </button>
            </div>
          </div>

          <!-- Variations Tabs -->
          <div v-if="generatedContent.variations?.length > 1" class="flex gap-2 mb-4">
            <button
              v-for="(v, i) in generatedContent.variations"
              :key="i"
              @click="activeVariation = i"
              :class="[
                'px-3 py-1.5 text-sm rounded-lg font-medium transition-all',
                activeVariation === i
                  ? 'bg-purple-600 text-white'
                  : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
              ]"
            >
              Variant {{ i + 1 }}
            </button>
          </div>

          <div class="prose dark:prose-invert max-w-none">
            <div class="whitespace-pre-wrap text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
              {{ currentVariation }}
            </div>
          </div>

          <!-- Hashtags -->
          <div v-if="generatedContent.hashtags?.length" class="mt-4 flex flex-wrap gap-2">
            <span v-for="tag in generatedContent.hashtags" :key="tag"
                  class="px-2 py-1 text-sm bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-full">
              #{{ tag }}
            </span>
          </div>

          <!-- Rating -->
          <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Bu kontent qanchalik yoqdi?</p>
            <div class="flex gap-1">
              <button
                v-for="star in 5"
                :key="star"
                @click="rateContent(star)"
                class="p-1 hover:scale-110 transition-transform"
              >
                <StarIcon
                  :class="[
                    'w-6 h-6',
                    star <= rating ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300 dark:text-gray-600'
                  ]"
                />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Style Guide Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <PaintBrushIcon class="w-5 h-5 text-pink-600" />
            Style Guide
          </h3>
          <div v-if="styleGuide">
            <div class="space-y-3">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Ohang:</span>
                <span class="font-medium text-gray-900 dark:text-white capitalize">{{ styleGuide.tone }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Uslub:</span>
                <span class="font-medium text-gray-900 dark:text-white capitalize">{{ styleGuide.language_style }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Emoji:</span>
                <span class="font-medium text-gray-900 dark:text-white capitalize">{{ styleGuide.emoji_frequency }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">O'rtacha uzunlik:</span>
                <span class="font-medium text-gray-900 dark:text-white">~{{ styleGuide.avg_post_length }} belgi</span>
              </div>
            </div>
            <Link :href="route('business.marketing.content-ai.style-guide')"
                  class="mt-4 block w-full text-center px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-all">
              Tahrirlash
            </Link>
          </div>
          <div v-else class="text-center py-4">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Style Guide hali sozlanmagan</p>
            <Link :href="route('business.marketing.content-ai.style-guide')"
                  class="px-4 py-2 text-sm bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-all">
              Sozlash
            </Link>
          </div>
        </div>

        <!-- Recent Generations -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <ClockIcon class="w-5 h-5 text-blue-600" />
            So'nggi generatsiyalar
          </h3>
          <div v-if="recentGenerations?.length" class="space-y-3">
            <div v-for="gen in recentGenerations" :key="gen.id"
                 class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-900 transition-all"
                 @click="loadGeneration(gen)">
              <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ gen.topic }}</p>
              <div class="flex items-center justify-between mt-1">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(gen.created_at) }}</span>
                <span :class="[
                  'text-xs px-2 py-0.5 rounded-full',
                  gen.status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' :
                  gen.status === 'published' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' :
                  'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                ]">
                  {{ gen.status }}
                </span>
              </div>
            </div>
          </div>
          <p v-else class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
            Hali generatsiya yo'q
          </p>
        </div>

        <!-- Top Templates -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <FireIcon class="w-5 h-5 text-orange-600" />
            Top shablonlar
          </h3>
          <div v-if="topTemplates?.length" class="space-y-3">
            <div v-for="tmpl in topTemplates" :key="tmpl.id"
                 class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
              <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2">{{ tmpl.content }}</p>
              <div class="flex items-center justify-between mt-2">
                <span class="text-xs px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded-full capitalize">
                  {{ tmpl.content_type }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  Score: {{ tmpl.performance_score?.toFixed(0) }}
                </span>
              </div>
            </div>
          </div>
          <p v-else class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
            Hali shablon yo'q
          </p>
        </div>
      </div>
    </div>
  </component>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import {
  SparklesIcon,
  DocumentTextIcon,
  StarIcon,
  CurrencyDollarIcon,
  CheckCircleIcon,
  PaintBrushIcon,
  ClockIcon,
  FireIcon,
  LightBulbIcon,
} from '@heroicons/vue/24/outline';
import { StarIcon as StarIconSolid } from '@heroicons/vue/24/solid';

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      total_generations: 0,
      templates_count: 0,
      avg_rating: null,
      monthly_cost: 0,
    }),
  },
  styleGuide: Object,
  recentGenerations: Array,
  topTemplates: Array,
  activeOffers: { type: Array, default: () => [] },
  panelType: { type: String, default: 'business' },
});

const layoutComponent = computed(() => {
  const layouts = { business: BusinessLayout, marketing: MarketingLayout };
  return layouts[props.panelType] || BaseLayout;
});

const form = ref({
  topic: '',
  content_type: 'post',
  purpose: 'engage',
  target_channel: '',
  additional_prompt: '',
  variations_count: 1,
  offer_id: '',
});

const generating = ref(false);
const generatedContent = ref(null);
const activeVariation = ref(0);
const rating = ref(0);

const currentVariation = computed(() => {
  if (!generatedContent.value) return '';
  if (generatedContent.value.variations?.length) {
    return generatedContent.value.variations[activeVariation.value];
  }
  return generatedContent.value.content;
});

const generateContent = async () => {
  generating.value = true;
  try {
    const response = await axios.post(route('business.marketing.content-ai.generate'), form.value);
    generatedContent.value = response.data.generation;
    activeVariation.value = 0;
    rating.value = 0;
  } catch (error) {
    console.error('Generation failed:', error);
    alert('Xatolik yuz berdi: ' + (error.response?.data?.message || error.message));
  } finally {
    generating.value = false;
  }
};

const regenerate = () => {
  generateContent();
};

const copyContent = () => {
  navigator.clipboard.writeText(currentVariation.value);
  // Could add toast notification here
};

const rateContent = async (stars) => {
  rating.value = stars;
  if (generatedContent.value?.id) {
    try {
      await axios.post(route('business.marketing.content-ai.history.rate', generatedContent.value.id), {
        rating: stars,
      });
    } catch (error) {
      console.error('Rating failed:', error);
    }
  }
};

const loadGeneration = (gen) => {
  generatedContent.value = {
    id: gen.id,
    content: gen.generated_content,
    variations: gen.generated_variations,
    hashtags: gen.generated_hashtags,
  };
  activeVariation.value = 0;
  rating.value = gen.rating || 0;
};

const handleOfferSelect = () => {
  if (form.value.offer_id) {
    const selected = props.activeOffers?.find(o => o.id === form.value.offer_id);
    if (selected) {
      form.value.topic = selected.name;
      form.value.purpose = 'sell';
    }
  }
};

const formatPrice = (price) => {
  return new Intl.NumberFormat('uz-UZ').format(price);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('uz-UZ', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });
};
</script>
