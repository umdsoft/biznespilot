<template>
  <MarketingLayout title="Kontent Shablonlari">
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
                Kontent Shablonlari
              </h2>
              <p class="mt-1 text-gray-600 dark:text-gray-400">
                AI uchun namuna kontentlar - ko'proq shablon = yaxshiroq natija
              </p>
            </div>
          </div>
        </div>
        <button
          @click="showAddModal = true"
          class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-medium rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all flex items-center gap-2"
        >
          <PlusIcon class="w-5 h-5" />
          Shablon qo'shish
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
      <div class="flex flex-wrap gap-4">
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
            <option value="carousel">Carousel</option>
            <option value="article">Maqola</option>
          </select>
        </div>

        <!-- Purpose Filter -->
        <div>
          <select
            v-model="filters.purpose"
            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
          >
            <option value="">Barcha maqsadlar</option>
            <option value="engage">Faollashtirish</option>
            <option value="sell">Sotish</option>
            <option value="educate">Ta'lim</option>
            <option value="inspire">Ilhomlantirish</option>
            <option value="announce">E'lon</option>
            <option value="entertain">Ko'ngil ochar</option>
          </select>
        </div>

        <!-- Top Performers -->
        <label class="flex items-center gap-2 cursor-pointer">
          <input
            type="checkbox"
            v-model="filters.top_only"
            class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500"
          />
          <span class="text-sm text-gray-700 dark:text-gray-300">Faqat top natijalar</span>
        </label>

        <!-- Search -->
        <div class="flex-1 min-w-[200px]">
          <input
            type="text"
            v-model="filters.search"
            placeholder="Qidirish..."
            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
          />
        </div>
      </div>
    </div>

    <!-- Templates Grid -->
    <div v-if="filteredTemplates.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="template in filteredTemplates" :key="template.id"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-all">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span :class="[
                'px-2 py-1 text-xs rounded-full font-medium',
                contentTypeColors[template.content_type]
              ]">
                {{ contentTypeLabels[template.content_type] }}
              </span>
              <span :class="[
                'px-2 py-1 text-xs rounded-full font-medium',
                purposeColors[template.purpose]
              ]">
                {{ purposeLabels[template.purpose] }}
              </span>
            </div>
            <div v-if="template.is_top_performer" class="flex items-center gap-1 text-amber-500">
              <FireIcon class="w-4 h-4" />
              <span class="text-xs font-medium">Top</span>
            </div>
          </div>
        </div>

        <!-- Content -->
        <div class="p-4">
          <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-4">
            {{ template.content }}
          </p>

          <!-- Hashtags -->
          <div v-if="template.hashtags?.length" class="mt-3 flex flex-wrap gap-1">
            <span v-for="tag in template.hashtags.slice(0, 3)" :key="tag"
                  class="px-1.5 py-0.5 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded">
              #{{ tag }}
            </span>
            <span v-if="template.hashtags.length > 3" class="text-xs text-gray-500">
              +{{ template.hashtags.length - 3 }}
            </span>
          </div>
        </div>

        <!-- Stats -->
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-4">
              <span class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                <HeartIcon class="w-4 h-4" />
                {{ formatNumber(template.likes_count) }}
              </span>
              <span class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                <ChatBubbleLeftIcon class="w-4 h-4" />
                {{ formatNumber(template.comments_count) }}
              </span>
              <span class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                <ShareIcon class="w-4 h-4" />
                {{ formatNumber(template.shares_count) }}
              </span>
            </div>
            <div class="flex items-center gap-1">
              <span class="text-xs text-gray-500">Score:</span>
              <span :class="[
                'font-medium',
                template.performance_score >= 50 ? 'text-green-600' :
                template.performance_score >= 25 ? 'text-amber-600' : 'text-gray-600'
              ]">
                {{ template.performance_score?.toFixed(0) || 0 }}
              </span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex justify-between">
          <button
            @click="editTemplate(template)"
            class="text-sm text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition-colors"
          >
            Tahrirlash
          </button>
          <button
            @click="deleteTemplate(template)"
            class="text-sm text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors"
          >
            O'chirish
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-12 text-center">
      <DocumentTextIcon class="w-16 h-16 mx-auto text-gray-400 mb-4" />
      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Hali shablon yo'q</h3>
      <p class="text-gray-500 dark:text-gray-400 mb-6">
        Eng yaxshi natijaga erishgan postlaringizni shablon sifatida qo'shing.<br/>
        AI shu namunalar asosida yangi kontentlar yaratadi.
      </p>
      <button
        @click="showAddModal = true"
        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-medium rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all"
      >
        Birinchi shablonni qo'shish
      </button>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50" @click="closeModal"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl p-6">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
            {{ editingTemplate ? 'Shablonni tahrirlash' : 'Yangi shablon qo\'shish' }}
          </h3>

          <form @submit.prevent="saveTemplate" class="space-y-4">
            <!-- Content -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Kontent *
              </label>
              <textarea
                v-model="templateForm.content"
                rows="5"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                placeholder="Post matnini kiriting..."
                required
              ></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <!-- Content Type -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Kontent turi
                </label>
                <select
                  v-model="templateForm.content_type"
                  class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
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
                  v-model="templateForm.purpose"
                  class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                >
                  <option value="engage">Faollashtirish</option>
                  <option value="sell">Sotish</option>
                  <option value="educate">Ta'lim</option>
                  <option value="inspire">Ilhomlantirish</option>
                  <option value="announce">E'lon</option>
                  <option value="entertain">Ko'ngil ochar</option>
                </select>
              </div>
            </div>

            <!-- Engagement Stats -->
            <div class="grid grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Likes</label>
                <input
                  type="number"
                  v-model.number="templateForm.likes_count"
                  min="0"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Comments</label>
                <input
                  type="number"
                  v-model.number="templateForm.comments_count"
                  min="0"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Shares</label>
                <input
                  type="number"
                  v-model.number="templateForm.shares_count"
                  min="0"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Saves</label>
                <input
                  type="number"
                  v-model.number="templateForm.saves_count"
                  min="0"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                />
              </div>
            </div>

            <!-- Engagement Rate -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Engagement Rate (%)
              </label>
              <input
                type="number"
                v-model.number="templateForm.engagement_rate"
                min="0"
                max="100"
                step="0.1"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              />
            </div>

            <!-- Source URL -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Manba URL (ixtiyoriy)
              </label>
              <input
                type="url"
                v-model="templateForm.source_url"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                placeholder="https://instagram.com/p/..."
              />
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-4">
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all"
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
  </MarketingLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import {
  ArrowLeftIcon,
  PlusIcon,
  DocumentTextIcon,
  HeartIcon,
  ChatBubbleLeftIcon,
  ShareIcon,
  FireIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  templates: {
    type: Array,
    default: () => [],
  },
});

const showAddModal = ref(false);
const editingTemplate = ref(null);
const saving = ref(false);

const filters = ref({
  content_type: '',
  purpose: '',
  top_only: false,
  search: '',
});

const templateForm = ref({
  content: '',
  content_type: 'post',
  purpose: 'engage',
  source_url: '',
  likes_count: 0,
  comments_count: 0,
  shares_count: 0,
  saves_count: 0,
  engagement_rate: 0,
});

const contentTypeLabels = {
  post: 'Post',
  story: 'Story',
  reel: 'Reel',
  ad: 'Reklama',
  carousel: 'Carousel',
  article: 'Maqola',
};

const contentTypeColors = {
  post: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
  story: 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400',
  reel: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
  ad: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
  carousel: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  article: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
};

const purposeLabels = {
  engage: 'Faollashtirish',
  sell: 'Sotish',
  educate: 'Ta\'lim',
  inspire: 'Ilhomlantirish',
  announce: 'E\'lon',
  entertain: 'Ko\'ngil ochar',
};

const purposeColors = {
  engage: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
  sell: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
  educate: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
  inspire: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
  announce: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
  entertain: 'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-900/30 dark:text-fuchsia-400',
};

const filteredTemplates = computed(() => {
  let result = [...props.templates];

  if (filters.value.content_type) {
    result = result.filter(t => t.content_type === filters.value.content_type);
  }

  if (filters.value.purpose) {
    result = result.filter(t => t.purpose === filters.value.purpose);
  }

  if (filters.value.top_only) {
    result = result.filter(t => t.is_top_performer);
  }

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase();
    result = result.filter(t =>
      t.content.toLowerCase().includes(search) ||
      t.hashtags?.some(h => h.toLowerCase().includes(search))
    );
  }

  return result.sort((a, b) => (b.performance_score || 0) - (a.performance_score || 0));
});

const formatNumber = (num) => {
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
  return num?.toString() || '0';
};

const editTemplate = (template) => {
  editingTemplate.value = template;
  templateForm.value = {
    content: template.content,
    content_type: template.content_type,
    purpose: template.purpose,
    source_url: template.source_url || '',
    likes_count: template.likes_count || 0,
    comments_count: template.comments_count || 0,
    shares_count: template.shares_count || 0,
    saves_count: template.saves_count || 0,
    engagement_rate: template.engagement_rate || 0,
  };
  showAddModal.value = true;
};

const closeModal = () => {
  showAddModal.value = false;
  editingTemplate.value = null;
  templateForm.value = {
    content: '',
    content_type: 'post',
    purpose: 'engage',
    source_url: '',
    likes_count: 0,
    comments_count: 0,
    shares_count: 0,
    saves_count: 0,
    engagement_rate: 0,
  };
};

const saveTemplate = async () => {
  saving.value = true;
  try {
    await axios.post(route('business.marketing.content-ai.templates.store'), templateForm.value);
    closeModal();
    router.reload({ only: ['templates'] });
  } catch (error) {
    console.error('Save failed:', error);
    alert('Xatolik: ' + (error.response?.data?.message || error.message));
  } finally {
    saving.value = false;
  }
};

const deleteTemplate = async (template) => {
  if (!confirm('Bu shablonni o\'chirmoqchimisiz?')) return;

  try {
    await axios.delete(route('business.marketing.content-ai.templates.destroy', template.id));
    router.reload({ only: ['templates'] });
  } catch (error) {
    console.error('Delete failed:', error);
    alert('Xatolik: ' + (error.response?.data?.message || error.message));
  }
};
</script>
