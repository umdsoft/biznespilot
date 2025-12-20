<template>
  <BusinessLayout title="Kontent Kalendari">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div>
          <Link href="/marketing" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Marketing
          </Link>
          <h2 class="text-2xl font-bold text-gray-900">Kontent Kalendari</h2>
          <p class="mt-1 text-sm text-gray-600">
            Kontentlaringizni rejalashtiring va boshqaring
          </p>
        </div>
        <button
          @click="openCreateModal"
          class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
        >
          <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Post Qo'shish
        </button>
      </div>

      <!-- Filter Tabs -->
      <div class="mb-6">
        <div class="border-b border-gray-200">
          <nav class="-mb-px flex space-x-8">
            <button
              v-for="tab in tabs"
              :key="tab.value"
              @click="activeTab = tab.value"
              :class="[
                activeTab === tab.value
                  ? 'border-primary-500 text-primary-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
              ]"
            >
              {{ tab.label }}
              <span
                :class="[
                  activeTab === tab.value
                    ? 'bg-primary-100 text-primary-600'
                    : 'bg-gray-100 text-gray-600',
                  'ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium'
                ]"
              >
                {{ getTabCount(tab.value) }}
              </span>
            </button>
          </nav>
        </div>
      </div>

      <!-- Posts List -->
      <div v-if="filteredPosts.length > 0" class="space-y-4">
        <Card
          v-for="post in filteredPosts"
          :key="post.id"
          class="hover:shadow-md transition-shadow"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center space-x-3 mb-2">
                <h3 class="text-lg font-semibold text-gray-900">{{ post.title }}</h3>
                <span
                  :class="getStatusClass(post.status)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ getStatusLabel(post.status) }}
                </span>
                <span
                  :class="getTypeClass(post.type)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ getTypeLabel(post.type) }}
                </span>
              </div>

              <p class="text-gray-600 mb-3 line-clamp-2">{{ post.content }}</p>

              <div class="flex items-center space-x-4 text-sm text-gray-600">
                <div class="flex items-center">
                  <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                  {{ post.platform }}
                </div>

                <div v-if="post.scheduled_at" class="flex items-center">
                  <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  {{ post.scheduled_at }}
                </div>

                <div v-if="post.published_at" class="flex items-center">
                  <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  {{ post.published_at }}
                </div>
              </div>

              <!-- Stats -->
              <div v-if="post.status === 'published' && (post.views || post.likes || post.comments || post.shares)" class="mt-3 flex items-center space-x-4 text-sm">
                <div v-if="post.views" class="flex items-center text-gray-600">
                  <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  {{ formatNumber(post.views) }}
                </div>
                <div v-if="post.likes" class="flex items-center text-gray-600">
                  <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                  </svg>
                  {{ formatNumber(post.likes) }}
                </div>
                <div v-if="post.comments" class="flex items-center text-gray-600">
                  <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                  </svg>
                  {{ formatNumber(post.comments) }}
                </div>
                <div v-if="post.shares" class="flex items-center text-gray-600">
                  <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                  </svg>
                  {{ formatNumber(post.shares) }}
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center space-x-2 ml-4">
              <button
                @click="viewPost(post)"
                class="p-1.5 text-gray-600 hover:text-primary-600 hover:bg-primary-50 rounded transition-colors"
                title="Ko'rish"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
              <button
                v-if="post.status !== 'published'"
                class="p-1.5 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                title="O'chirish"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </Card>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
          <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
          </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Hech qanday post mavjud emas</h3>
        <p class="text-gray-600 mb-6">Birinchi kontentingizni yarating</p>
        <button
          @click="openCreateModal"
          class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
        >
          <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Post Qo'shish
        </button>
      </div>

      <!-- Create Modal -->
      <Modal v-model="showCreateModal" @close="closeCreateModal" size="lg">
        <div class="p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Yangi Post Yaratish</h3>

          <form @submit.prevent="submitPost" class="space-y-4">
            <Input
              v-model="postForm.title"
              label="Sarlavha"
              placeholder="Post sarlavhasi"
              :error="postForm.errors.title"
              required
            />

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Kontent *
              </label>
              <textarea
                v-model="postForm.content"
                rows="6"
                class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
                placeholder="Post matni..."
                required
              ></textarea>
              <p v-if="postForm.errors.content" class="mt-1 text-sm text-red-600">
                {{ postForm.errors.content }}
              </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <Input
                v-model="postForm.platform"
                label="Platforma"
                placeholder="Instagram, Facebook..."
                :error="postForm.errors.platform"
                required
              />

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Turi *
                </label>
                <select
                  v-model="postForm.type"
                  class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
                  required
                >
                  <option value="">Tanlang</option>
                  <option value="post">Post</option>
                  <option value="story">Story</option>
                  <option value="reel">Reel</option>
                  <option value="video">Video</option>
                  <option value="article">Maqola</option>
                </select>
                <p v-if="postForm.errors.type" class="mt-1 text-sm text-red-600">
                  {{ postForm.errors.type }}
                </p>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Holat *
                </label>
                <select
                  v-model="postForm.status"
                  class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
                  required
                >
                  <option value="draft">Qoralama</option>
                  <option value="scheduled">Rejalashtirilgan</option>
                  <option value="published">Nashr qilingan</option>
                </select>
                <p v-if="postForm.errors.status" class="mt-1 text-sm text-red-600">
                  {{ postForm.errors.status }}
                </p>
              </div>

              <div v-if="postForm.status === 'scheduled'">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Rejalashtirilgan vaqt
                </label>
                <input
                  v-model="postForm.scheduled_at"
                  type="datetime-local"
                  class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
                />
                <p v-if="postForm.errors.scheduled_at" class="mt-1 text-sm text-red-600">
                  {{ postForm.errors.scheduled_at }}
                </p>
              </div>
            </div>

            <TagInput
              v-model="postForm.hashtags"
              label="Hashtaglar"
              placeholder="#biznes #marketing"
              :error="postForm.errors.hashtags"
            />

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-3 pt-4">
              <button
                type="button"
                @click="closeCreateModal"
                class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition-colors"
              >
                Bekor qilish
              </button>
              <Button
                type="submit"
                variant="primary"
                :loading="postForm.processing"
              >
                Qo'shish
              </Button>
            </div>
          </form>
        </div>
      </Modal>

      <!-- View Modal -->
      <Modal v-model="showViewModal" @close="closeViewModal" size="lg">
        <div v-if="viewingPost" class="p-6">
          <div class="flex items-start justify-between mb-4">
            <div>
              <h3 class="text-xl font-semibold text-gray-900">{{ viewingPost.title }}</h3>
              <div class="flex items-center space-x-3 mt-2">
                <span :class="getStatusClass(viewingPost.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                  {{ getStatusLabel(viewingPost.status) }}
                </span>
                <span :class="getTypeClass(viewingPost.type)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                  {{ getTypeLabel(viewingPost.type) }}
                </span>
                <span class="text-sm text-gray-600">{{ viewingPost.platform }}</span>
              </div>
            </div>
          </div>

          <div class="prose max-w-none mb-6">
            <p class="text-gray-700 whitespace-pre-wrap">{{ viewingPost.content }}</p>
          </div>

          <div v-if="viewingPost.hashtags && viewingPost.hashtags.length" class="mb-6">
            <div class="flex flex-wrap gap-2">
              <span
                v-for="(tag, index) in viewingPost.hashtags"
                :key="index"
                class="px-2 py-1 bg-blue-100 text-blue-700 text-sm rounded"
              >
                {{ tag }}
              </span>
            </div>
          </div>

          <div class="border-t border-gray-200 pt-4">
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div v-if="viewingPost.scheduled_at">
                <span class="text-gray-600">Rejalashtirilgan:</span>
                <p class="font-medium text-gray-900">{{ viewingPost.scheduled_at }}</p>
              </div>
              <div v-if="viewingPost.published_at">
                <span class="text-gray-600">Nashr qilingan:</span>
                <p class="font-medium text-gray-900">{{ viewingPost.published_at }}</p>
              </div>
            </div>
          </div>

          <div v-if="viewingPost.status === 'published' && (viewingPost.views || viewingPost.likes || viewingPost.comments || viewingPost.shares)" class="border-t border-gray-200 mt-4 pt-4">
            <h4 class="text-sm font-medium text-gray-900 mb-3">Statistika</h4>
            <div class="grid grid-cols-4 gap-4">
              <div v-if="viewingPost.views" class="text-center">
                <p class="text-2xl font-semibold text-gray-900">{{ formatNumber(viewingPost.views) }}</p>
                <p class="text-xs text-gray-600 mt-1">Ko'rishlar</p>
              </div>
              <div v-if="viewingPost.likes" class="text-center">
                <p class="text-2xl font-semibold text-gray-900">{{ formatNumber(viewingPost.likes) }}</p>
                <p class="text-xs text-gray-600 mt-1">Yoqtirishlar</p>
              </div>
              <div v-if="viewingPost.comments" class="text-center">
                <p class="text-2xl font-semibold text-gray-900">{{ formatNumber(viewingPost.comments) }}</p>
                <p class="text-xs text-gray-600 mt-1">Izohlar</p>
              </div>
              <div v-if="viewingPost.shares" class="text-center">
                <p class="text-2xl font-semibold text-gray-900">{{ formatNumber(viewingPost.shares) }}</p>
                <p class="text-xs text-gray-600 mt-1">Ulashishlar</p>
              </div>
            </div>
          </div>
        </div>
      </Modal>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import Card from '@/components/Card.vue';
import Input from '@/components/Input.vue';
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';
import TagInput from '@/components/TagInput.vue';

const props = defineProps({
  posts: {
    type: Array,
    default: () => [],
  },
});

const activeTab = ref('all');
const showCreateModal = ref(false);
const showViewModal = ref(false);
const viewingPost = ref(null);

const tabs = [
  { label: 'Barchasi', value: 'all' },
  { label: 'Qoralama', value: 'draft' },
  { label: 'Rejalashtirilgan', value: 'scheduled' },
  { label: 'Nashr qilingan', value: 'published' },
];

const postForm = useForm({
  title: '',
  content: '',
  platform: '',
  type: '',
  status: 'draft',
  scheduled_at: null,
  hashtags: [],
});

const filteredPosts = computed(() => {
  if (activeTab.value === 'all') {
    return props.posts;
  }
  return props.posts.filter(post => post.status === activeTab.value);
});

const getTabCount = (tab) => {
  if (tab === 'all') {
    return props.posts.length;
  }
  return props.posts.filter(post => post.status === tab).length;
};

const openCreateModal = () => {
  postForm.reset();
  postForm.clearErrors();
  showCreateModal.value = true;
};

const closeCreateModal = () => {
  showCreateModal.value = false;
  postForm.reset();
};

const submitPost = () => {
  postForm.post('/marketing/content', {
    onSuccess: () => {
      closeCreateModal();
    },
  });
};

const viewPost = (post) => {
  viewingPost.value = post;
  showViewModal.value = true;
};

const closeViewModal = () => {
  showViewModal.value = false;
  viewingPost.value = null;
};

const formatNumber = (num) => {
  return new Intl.NumberFormat('uz-UZ').format(num);
};

const getStatusClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    scheduled: 'bg-blue-100 text-blue-800',
    published: 'bg-green-100 text-green-800',
  };
  return classes[status] || classes.draft;
};

const getStatusLabel = (status) => {
  const labels = {
    draft: 'Qoralama',
    scheduled: 'Rejalashtirilgan',
    published: 'Nashr qilingan',
  };
  return labels[status] || status;
};

const getTypeClass = (type) => {
  const classes = {
    post: 'bg-purple-100 text-purple-800',
    story: 'bg-pink-100 text-pink-800',
    reel: 'bg-orange-100 text-orange-800',
    video: 'bg-red-100 text-red-800',
    article: 'bg-indigo-100 text-indigo-800',
  };
  return classes[type] || classes.post;
};

const getTypeLabel = (type) => {
  const labels = {
    post: 'Post',
    story: 'Story',
    reel: 'Reel',
    video: 'Video',
    article: 'Maqola',
  };
  return labels[type] || type;
};
</script>
