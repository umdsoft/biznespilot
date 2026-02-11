<template>
  <component :is="layoutComponent" title="Generatsiya Tarixi">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <div class="flex items-center gap-3">
            <Link :href="route('business.marketing.content-ai.index')"
                  class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all">
              <ArrowLeftIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
            </Link>
            <div>
              <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                Generatsiya Tarixi
              </h2>
              <p class="mt-1 text-gray-600 dark:text-gray-400">
                Barcha yaratilgan kontentlar
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">Jami generatsiya</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">Nashr qilingan</p>
        <p class="text-2xl font-bold text-green-600">{{ stats.published }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">Jami tokenlar</p>
        <p class="text-2xl font-bold text-blue-600">{{ formatNumber(stats.total_tokens) }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">Jami xarajat</p>
        <p class="text-2xl font-bold text-amber-600">${{ stats.total_cost?.toFixed(2) || '0.00' }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
      <div class="flex flex-wrap gap-4">
        <!-- Status Filter -->
        <div>
          <select
            v-model="filters.status"
            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
          >
            <option value="">Barcha holatlar</option>
            <option value="completed">Yaratilgan</option>
            <option value="published">Nashr qilingan</option>
            <option value="failed">Xatolik</option>
          </select>
        </div>

        <!-- Content Type Filter -->
        <div>
          <select
            v-model="filters.content_type"
            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
          >
            <option value="">Barcha turlar</option>
            <option value="post">Post</option>
            <option value="story">Story</option>
            <option value="reel">Reel</option>
            <option value="ad">Reklama</option>
          </select>
        </div>

        <!-- Date Range -->
        <div class="flex items-center gap-2">
          <input
            type="date"
            v-model="filters.date_from"
            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
          />
          <span class="text-gray-500">—</span>
          <input
            type="date"
            v-model="filters.date_to"
            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
          />
        </div>

        <!-- Search -->
        <div class="flex-1 min-w-[200px]">
          <input
            type="text"
            v-model="filters.search"
            placeholder="Mavzu bo'yicha qidirish..."
            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
          />
        </div>
      </div>
    </div>

    <!-- History List -->
    <div v-if="filteredGenerations.length" class="space-y-4">
      <div v-for="gen in filteredGenerations" :key="gen.id"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div :class="[
              'w-10 h-10 rounded-lg flex items-center justify-center',
              statusColors[gen.status]
            ]">
              <component :is="statusIcons[gen.status]" class="w-5 h-5" />
            </div>
            <div>
              <h4 class="font-medium text-gray-900 dark:text-white">{{ gen.topic }}</h4>
              <div class="flex items-center gap-2 mt-1">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  {{ formatDate(gen.created_at) }}
                </span>
                <span class="text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded-full">
                  {{ contentTypeLabels[gen.content_type] }}
                </span>
                <span class="text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded-full">
                  {{ purposeLabels[gen.purpose] }}
                </span>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-4">
            <!-- Rating -->
            <div v-if="gen.rating" class="flex items-center gap-1">
              <StarIcon v-for="star in 5" :key="star"
                        :class="[
                          'w-4 h-4',
                          star <= gen.rating ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300'
                        ]" />
            </div>

            <!-- Tokens/Cost -->
            <div class="text-right">
              <p class="text-sm font-medium text-gray-900 dark:text-white">
                {{ formatNumber(gen.input_tokens + gen.output_tokens) }} tokens
              </p>
              <p class="text-xs text-gray-500">${{ gen.cost_usd?.toFixed(4) || '0.0000' }}</p>
            </div>

            <!-- Toggle -->
            <button
              @click="toggleExpand(gen.id)"
              class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all"
            >
              <ChevronDownIcon :class="[
                'w-5 h-5 text-gray-400 transition-transform',
                expandedIds.includes(gen.id) ? 'rotate-180' : ''
              ]" />
            </button>
          </div>
        </div>

        <!-- Expanded Content -->
        <div v-if="expandedIds.includes(gen.id)" class="p-4 bg-gray-50 dark:bg-gray-900/50">
          <!-- Generated Content -->
          <div v-if="gen.generated_content" class="mb-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Yaratilgan kontent:</p>
            <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
              <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                {{ gen.generated_content }}
              </p>
            </div>
          </div>

          <!-- Variations -->
          <div v-if="gen.generated_variations?.length > 1" class="mb-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Variantlar:</p>
            <div class="space-y-2">
              <div v-for="(variation, i) in gen.generated_variations" :key="i"
                   class="p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <p class="text-xs font-medium text-purple-600 mb-1">Variant {{ i + 1 }}</p>
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ variation }}</p>
              </div>
            </div>
          </div>

          <!-- Hashtags -->
          <div v-if="gen.generated_hashtags?.length" class="mb-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hashtaglar:</p>
            <div class="flex flex-wrap gap-2">
              <span v-for="tag in gen.generated_hashtags" :key="tag"
                    class="px-2 py-1 text-sm bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-full">
                #{{ tag }}
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex gap-2">
              <button
                @click="copyContent(gen.generated_content)"
                class="px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-all"
              >
                Nusxalash
              </button>
              <button
                v-if="gen.status !== 'published'"
                @click="markAsPublished(gen)"
                class="px-3 py-1.5 text-sm bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition-all"
              >
                Nashr qilindi
              </button>
            </div>

            <!-- Rate -->
            <div class="flex items-center gap-2">
              <span class="text-sm text-gray-500">Baholash:</span>
              <div class="flex gap-1">
                <button
                  v-for="star in 5"
                  :key="star"
                  @click="rateGeneration(gen, star)"
                  class="p-0.5 hover:scale-110 transition-transform"
                >
                  <StarIcon :class="[
                    'w-5 h-5',
                    star <= (gen.rating || 0) ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300 dark:text-gray-600'
                  ]" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-12 text-center">
      <ClockIcon class="w-16 h-16 mx-auto text-gray-400 mb-4" />
      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Hali generatsiya yo'q</h3>
      <p class="text-gray-500 dark:text-gray-400 mb-6">
        Content AI orqali yangi kontent yarating va tarix shu yerda ko'rinadi.
      </p>
      <Link :href="route('business.marketing.content-ai.index')"
            class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-medium rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all">
        <SparklesIcon class="w-5 h-5" />
        Kontent yaratish
      </Link>
    </div>

    <!-- Pagination -->
    <div v-if="generations.links?.length > 3" class="mt-6 flex justify-center">
      <nav class="flex gap-1">
        <Link v-for="link in generations.links" :key="link.label"
              :href="link.url || '#'"
              :class="[
                'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                link.active
                  ? 'bg-purple-600 text-white'
                  : link.url
                    ? 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                    : 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
              ]"
              v-html="link.label"
        />
      </nav>
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
  ArrowLeftIcon,
  ClockIcon,
  SparklesIcon,
  ChevronDownIcon,
  CheckCircleIcon,
  XCircleIcon,
  PaperAirplaneIcon,
  StarIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  generations: {
    type: Object,
    default: () => ({ data: [] }),
  },
  stats: {
    type: Object,
    default: () => ({
      total: 0,
      published: 0,
      total_tokens: 0,
      total_cost: 0,
    }),
  },
  panelType: { type: String, default: 'business' },
});

const layoutComponent = computed(() => {
  const layouts = { business: BusinessLayout, marketing: MarketingLayout };
  return layouts[props.panelType] || BaseLayout;
});

const expandedIds = ref([]);

const filters = ref({
  status: '',
  content_type: '',
  date_from: '',
  date_to: '',
  search: '',
});

const statusColors = {
  pending: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
  generating: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
  completed: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
  published: 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
  failed: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
};

const statusIcons = {
  pending: ClockIcon,
  generating: SparklesIcon,
  completed: CheckCircleIcon,
  published: PaperAirplaneIcon,
  failed: XCircleIcon,
};

const contentTypeLabels = {
  post: 'Post',
  story: 'Story',
  reel: 'Reel',
  ad: 'Reklama',
  carousel: 'Carousel',
  article: 'Maqola',
};

const purposeLabels = {
  engage: 'Faollashtirish',
  sell: 'Sotish',
  educate: 'Ta\'lim',
  inspire: 'Ilhomlantirish',
  announce: 'E\'lon',
  entertain: 'Ko\'ngil ochar',
};

const filteredGenerations = computed(() => {
  let result = [...(props.generations.data || [])];

  if (filters.value.status) {
    result = result.filter(g => g.status === filters.value.status);
  }

  if (filters.value.content_type) {
    result = result.filter(g => g.content_type === filters.value.content_type);
  }

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase();
    result = result.filter(g => g.topic.toLowerCase().includes(search));
  }

  if (filters.value.date_from) {
    result = result.filter(g => new Date(g.created_at) >= new Date(filters.value.date_from));
  }

  if (filters.value.date_to) {
    result = result.filter(g => new Date(g.created_at) <= new Date(filters.value.date_to));
  }

  return result;
});

const formatNumber = (num) => {
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
  return num?.toString() || '0';
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('uz-UZ', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const toggleExpand = (id) => {
  const index = expandedIds.value.indexOf(id);
  if (index === -1) {
    expandedIds.value.push(id);
  } else {
    expandedIds.value.splice(index, 1);
  }
};

const copyContent = (content) => {
  navigator.clipboard.writeText(content);
};

const markAsPublished = async (gen) => {
  try {
    await axios.post(route('business.marketing.content-ai.history.rate', gen.id), {
      status: 'published',
    });
    gen.status = 'published';
  } catch (error) {
    console.error('Update failed:', error);
  }
};

const rateGeneration = async (gen, stars) => {
  try {
    await axios.post(route('business.marketing.content-ai.history.rate', gen.id), {
      rating: stars,
    });
    gen.rating = stars;
  } catch (error) {
    console.error('Rating failed:', error);
  }
};
</script>
