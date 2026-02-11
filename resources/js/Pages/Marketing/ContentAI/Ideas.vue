<template>
  <component :is="layoutComponent" title="G'oyalar Banki">
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
                G'oyalar Banki
              </h2>
              <p class="mt-1 text-gray-600 dark:text-gray-400">
                Tayyor g'oyalar - AI tokenlarni tejang, sifatni oshiring
              </p>
            </div>
          </div>
        </div>
        <button
          @click="showCreateModal = true"
          class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-medium rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all flex items-center gap-2"
        >
          <PlusIcon class="w-5 h-5" />
          Yangi g'oya
        </button>
      </div>
    </div>

    <!-- Season Banner -->
    <div v-if="currentSeason" class="mb-6 p-4 rounded-xl bg-gradient-to-r from-amber-500/10 to-orange-500/10 border border-amber-200 dark:border-amber-800">
      <div class="flex items-center gap-3">
        <span class="text-2xl">{{ seasonEmojis[currentSeason.key] || '🗓️' }}</span>
        <div>
          <p class="font-medium text-amber-900 dark:text-amber-100">{{ currentSeason.label }} mavsumi</p>
          <p class="text-sm text-amber-700 dark:text-amber-300">Bu mavsum uchun maxsus g'oyalar tavsiya etilmoqda</p>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">Ishlatilgan g'oyalar</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_ideas_used || 0 }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">Nashr qilingan</p>
        <p class="text-2xl font-bold text-green-600">{{ stats.published_count || 0 }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">O'rtacha engagement</p>
        <p class="text-2xl font-bold text-blue-600">{{ (stats.avg_engagement || 0).toFixed(1) }}%</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">Foydalilik darajasi</p>
        <p class="text-2xl font-bold text-purple-600">{{ stats.helpful_rate || 0 }}%</p>
      </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
      <!-- Sidebar - Collections -->
      <div class="lg:col-span-1 space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-4">
          <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <FolderIcon class="w-5 h-5 text-amber-500" />
            To'plamlar
          </h3>
          <div class="space-y-2">
            <button
              v-for="collection in collections"
              :key="collection.id"
              @click="loadCollection(collection)"
              :class="[
                'w-full text-left p-3 rounded-lg transition-all flex items-center gap-3',
                activeCollection?.id === collection.id
                  ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300'
                  : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300'
              ]"
            >
              <span class="text-xl">{{ collection.icon || '📁' }}</span>
              <div class="flex-1 min-w-0">
                <p class="font-medium truncate">{{ collection.name }}</p>
                <p class="text-xs text-gray-500">{{ collection.ideas_count }} g'oya</p>
              </div>
            </button>
          </div>
        </div>

        <!-- Categories -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-4">
          <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <TagIcon class="w-5 h-5 text-green-500" />
            Kategoriyalar
          </h3>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="(label, key) in categories"
              :key="key"
              @click="loadCategory(key)"
              :class="[
                'px-3 py-1.5 text-sm rounded-full transition-all',
                activeCategory === key
                  ? 'bg-green-600 text-white'
                  : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
              ]"
            >
              {{ label }}
            </button>
          </div>
        </div>
      </div>

      <!-- Main Ideas Grid -->
      <div class="lg:col-span-3 space-y-6">
        <!-- Search -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-4">
          <div class="flex gap-4">
            <div class="flex-1">
              <input
                type="text"
                v-model="searchQuery"
                @input="debouncedSearch"
                placeholder="G'oya qidirish..."
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              />
            </div>
            <select
              v-model="filterType"
              class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            >
              <option value="">Barcha turlar</option>
              <option v-for="(label, key) in contentTypes" :key="key" :value="key">{{ label }}</option>
            </select>
            <select
              v-model="filterPurpose"
              class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            >
              <option value="">Barcha maqsadlar</option>
              <option v-for="(label, key) in purposes" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>
        </div>

        <!-- Section Tabs -->
        <div class="flex gap-2 overflow-x-auto pb-2">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            :class="[
              'px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-all',
              activeTab === tab.key
                ? 'bg-purple-600 text-white'
                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
            ]"
          >
            {{ tab.icon }} {{ tab.label }}
          </button>
        </div>

        <!-- Ideas List -->
        <div v-if="currentIdeas.length" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="idea in currentIdeas"
            :key="idea.id"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-all"
          >
            <!-- Header -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
              <div class="flex items-start justify-between gap-2">
                <h4 class="font-semibold text-gray-900 dark:text-white line-clamp-2">{{ idea.title }}</h4>
                <div class="flex items-center gap-1 shrink-0">
                  <span v-if="idea.is_verified" class="text-blue-500" title="Tasdiqlangan">
                    <CheckBadgeIcon class="w-5 h-5" />
                  </span>
                  <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                    {{ idea.quality_score?.toFixed(0) }}
                  </span>
                </div>
              </div>
              <div class="flex items-center gap-2 mt-2">
                <span :class="contentTypeColors[idea.content_type]" class="text-xs px-2 py-0.5 rounded-full">
                  {{ contentTypes[idea.content_type] }}
                </span>
                <span :class="purposeColors[idea.purpose]" class="text-xs px-2 py-0.5 rounded-full">
                  {{ purposes[idea.purpose] }}
                </span>
                <span v-if="idea.is_seasonal" class="text-xs px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400">
                  {{ seasons[idea.season] || 'Mavsumiy' }}
                </span>
              </div>
            </div>

            <!-- Content -->
            <div class="p-4">
              <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-3">{{ idea.description }}</p>

              <!-- Key Points -->
              <div v-if="idea.key_points?.length" class="mt-3 flex flex-wrap gap-1">
                <span v-for="point in idea.key_points.slice(0, 3)" :key="point"
                      class="text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                  {{ point }}
                </span>
                <span v-if="idea.key_points.length > 3" class="text-xs text-gray-500">
                  +{{ idea.key_points.length - 3 }}
                </span>
              </div>

              <!-- Stats -->
              <div class="mt-3 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                <span>{{ idea.times_used || 0 }} marta ishlatilgan</span>
                <span>{{ (idea.avg_engagement_rate || 0).toFixed(1) }}% engagement</span>
              </div>
            </div>

            <!-- Actions -->
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex justify-between">
              <button
                @click="previewIdea(idea)"
                class="text-sm text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition-colors"
              >
                Ko'rish
              </button>
              <button
                @click="useIdea(idea)"
                class="text-sm px-3 py-1 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
              >
                Ishlatish
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-12 text-center">
          <LightBulbIcon class="w-16 h-16 mx-auto text-gray-400 mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">G'oyalar topilmadi</h3>
          <p class="text-gray-500 dark:text-gray-400 mb-4">
            Bu kategoriyada hali g'oyalar yo'q yoki qidiruv natijalari bo'sh.
          </p>
          <button
            @click="showCreateModal = true"
            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-all"
          >
            Yangi g'oya yarating
          </button>
        </div>
      </div>
    </div>

    <!-- Preview Modal -->
    <div v-if="previewingIdea" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50" @click="previewingIdea = null"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl p-6">
          <div class="flex items-start justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ previewingIdea.title }}</h3>
            <button @click="previewingIdea = null" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <XMarkIcon class="w-5 h-5 text-gray-500" />
            </button>
          </div>

          <div class="space-y-4">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Tavsif</p>
              <p class="text-gray-700 dark:text-gray-300">{{ previewingIdea.description }}</p>
            </div>

            <div v-if="previewingIdea.example_content">
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Namuna</p>
              <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ previewingIdea.example_content }}</p>
              </div>
            </div>

            <div v-if="previewingIdea.key_points?.length">
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Asosiy fikrlar</p>
              <ul class="list-disc list-inside text-gray-700 dark:text-gray-300">
                <li v-for="point in previewingIdea.key_points" :key="point">{{ point }}</li>
              </ul>
            </div>

            <div v-if="previewingIdea.suggested_hashtags?.length">
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Tavsiya etiladigan hashtaglar</p>
              <div class="flex flex-wrap gap-2">
                <span v-for="tag in previewingIdea.suggested_hashtags" :key="tag"
                      class="px-2 py-1 text-sm bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-full">
                  #{{ tag }}
                </span>
              </div>
            </div>

            <div v-if="previewingIdea.suggested_emojis?.length">
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Tavsiya etiladigan emojilar</p>
              <div class="flex gap-2 text-2xl">
                <span v-for="emoji in previewingIdea.suggested_emojis" :key="emoji">{{ emoji }}</span>
              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-end gap-3">
            <button
              @click="previewingIdea = null"
              class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
            >
              Yopish
            </button>
            <button
              @click="useIdea(previewingIdea); previewingIdea = null"
              class="px-6 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-medium rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all"
            >
              Ishlatish va generatsiya
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Idea Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50" @click="showCreateModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl p-6">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Yangi g'oya yaratish</h3>

          <form @submit.prevent="createIdea" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sarlavha *</label>
              <input
                type="text"
                v-model="newIdea.title"
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                placeholder="Masalan: Yangi yil aksiyasi"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tavsif *</label>
              <textarea
                v-model="newIdea.description"
                rows="3"
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                placeholder="G'oya haqida batafsil..."
                required
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Namuna matn (ixtiyoriy)</label>
              <textarea
                v-model="newIdea.example_content"
                rows="4"
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                placeholder="Namuna post matni..."
              ></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kontent turi</label>
                <select
                  v-model="newIdea.content_type"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                >
                  <option v-for="(label, key) in contentTypes" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Maqsad</label>
                <select
                  v-model="newIdea.purpose"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                >
                  <option v-for="(label, key) in purposes" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
              <button
                type="button"
                @click="showCreateModal = false"
                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
              >
                Bekor qilish
              </button>
              <button
                type="submit"
                :disabled="saving"
                class="px-6 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-medium rounded-lg hover:from-purple-700 hover:to-indigo-700 disabled:opacity-50 transition-all"
              >
                {{ saving ? 'Saqlanmoqda...' : 'Saqlash' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </component>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import {
  ArrowLeftIcon,
  PlusIcon,
  FolderIcon,
  TagIcon,
  LightBulbIcon,
  CheckBadgeIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';

const props = defineProps({
  recommendations: Object,
  stats: Object,
  currentSeason: Object,
  collections: Array,
  categories: Object,
  recentUsages: Array,
  contentTypes: Object,
  purposes: Object,
  seasons: Object,
  panelType: { type: String, default: 'business' },
});

const layoutComponent = computed(() => {
  const layouts = { business: BusinessLayout, marketing: MarketingLayout };
  return layouts[props.panelType] || BaseLayout;
});

const tabs = [
  { key: 'top_picks', label: 'Tavsiyalar', icon: '⭐' },
  { key: 'trending', label: 'Trending', icon: '🔥' },
  { key: 'seasonal', label: 'Mavsumiy', icon: '🗓️' },
  { key: 'from_similar', label: "O'xshash bizneslar", icon: '🏢' },
  { key: 'your_best', label: 'Sizning top', icon: '👑' },
];

const seasonEmojis = {
  winter: '❄️',
  spring: '🌸',
  summer: '☀️',
  autumn: '🍂',
  ramadan: '🌙',
  new_year: '🎄',
  independence: '🇺🇿',
  "navro'z": '🌷',
};

const contentTypeColors = {
  post: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
  story: 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400',
  reel: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
  ad: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
  carousel: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  article: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
};

const purposeColors = {
  engage: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
  sell: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
  educate: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
  inspire: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
  announce: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
  entertain: 'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-900/30 dark:text-fuchsia-400',
};

const activeTab = ref('top_picks');
const activeCollection = ref(null);
const activeCategory = ref(null);
const searchQuery = ref('');
const filterType = ref('');
const filterPurpose = ref('');
const previewingIdea = ref(null);
const showCreateModal = ref(false);
const saving = ref(false);
const loadedIdeas = ref({});

const newIdea = ref({
  title: '',
  description: '',
  example_content: '',
  content_type: 'post',
  purpose: 'engage',
});

const currentIdeas = computed(() => {
  // If we have loaded ideas from category/collection/search, show those
  if (activeCategory.value && loadedIdeas.value.category) {
    return loadedIdeas.value.category;
  }
  if (activeCollection.value && loadedIdeas.value.collection) {
    return loadedIdeas.value.collection;
  }
  if (searchQuery.value && loadedIdeas.value.search) {
    return loadedIdeas.value.search;
  }

  // Otherwise show from recommendations based on active tab
  return props.recommendations?.[activeTab.value] || [];
});

const loadCollection = async (collection) => {
  activeCollection.value = collection;
  activeCategory.value = null;
  searchQuery.value = '';

  try {
    const response = await axios.get(route('business.marketing.content-ai.ideas.collection', collection.id));
    loadedIdeas.value.collection = response.data.ideas;
  } catch (error) {
    console.error('Failed to load collection:', error);
  }
};

const loadCategory = async (category) => {
  activeCategory.value = category;
  activeCollection.value = null;
  searchQuery.value = '';

  try {
    const response = await axios.get(route('business.marketing.content-ai.ideas.category', category));
    loadedIdeas.value.category = response.data.ideas;
  } catch (error) {
    console.error('Failed to load category:', error);
  }
};

const searchIdeas = async () => {
  if (searchQuery.value.length < 2) {
    loadedIdeas.value.search = null;
    return;
  }

  activeCollection.value = null;
  activeCategory.value = null;

  try {
    const response = await axios.get(route('business.marketing.content-ai.ideas.search'), {
      params: {
        query: searchQuery.value,
        content_type: filterType.value || undefined,
        purpose: filterPurpose.value || undefined,
      },
    });
    loadedIdeas.value.search = response.data.ideas;
  } catch (error) {
    console.error('Search failed:', error);
  }
};

const debouncedSearch = debounce(searchIdeas, 300);

const previewIdea = (idea) => {
  previewingIdea.value = idea;
};

const useIdea = async (idea) => {
  try {
    const response = await axios.post(route('business.marketing.content-ai.ideas.use', idea.id), {
      generate_now: true,
    });

    if (response.data.generation) {
      // Redirect to main Content AI page with the generation
      router.visit(route('business.marketing.content-ai.index'), {
        data: { generation_id: response.data.generation.id },
      });
    }
  } catch (error) {
    console.error('Failed to use idea:', error);
    alert('Xatolik: ' + (error.response?.data?.message || error.message));
  }
};

const createIdea = async () => {
  saving.value = true;
  try {
    await axios.post(route('business.marketing.content-ai.ideas.store'), newIdea.value);
    showCreateModal.value = false;
    newIdea.value = {
      title: '',
      description: '',
      example_content: '',
      content_type: 'post',
      purpose: 'engage',
    };
    router.reload({ only: ['recommendations'] });
  } catch (error) {
    console.error('Create failed:', error);
    alert('Xatolik: ' + (error.response?.data?.message || error.message));
  } finally {
    saving.value = false;
  }
};

// Reset loaded ideas when switching tabs
watch(activeTab, () => {
  activeCollection.value = null;
  activeCategory.value = null;
  searchQuery.value = '';
  loadedIdeas.value = {};
});
</script>
