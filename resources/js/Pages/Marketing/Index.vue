<template>
  <BusinessLayout title="Marketing">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Marketing</h2>
          <p class="mt-1 text-sm text-gray-600">
            Marketing kanallaringiz va kontent kalendari
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <Link
            href="/marketing/content"
            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Kontent Kalendari
          </Link>
          <Link
            href="/marketing/channels"
            class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            Kanallar
          </Link>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Jami Kanallar</p>
              <p class="mt-2 text-3xl font-semibold text-gray-900">{{ stats.total_channels }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Faol Kanallar</p>
              <p class="mt-2 text-3xl font-semibold text-green-600">{{ stats.active_channels }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Oylik Xarajat</p>
              <p class="mt-2 text-3xl font-semibold text-gray-900">{{ formatCurrency(stats.total_spend) }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Jami Postlar</p>
              <p class="mt-2 text-3xl font-semibold text-gray-900">{{ stats.total_posts }}</p>
              <p class="mt-1 text-xs text-green-600">{{ stats.published_posts }} nashr qilingan</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Marketing Channels -->
        <Card title="Marketing Kanallari">
          <template #action>
            <Link
              href="/marketing/channels"
              class="text-sm text-primary-600 hover:text-primary-700 font-medium"
            >
              Barchasini ko'rish
            </Link>
          </template>

          <div v-if="channels.length > 0" class="space-y-3">
            <div
              v-for="channel in channels.slice(0, 5)"
              :key="channel.id"
              class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition-colors"
            >
              <div class="flex items-center space-x-3">
                <div :class="getChannelIconClass(channel.type)" class="w-10 h-10 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ channel.name }}</p>
                  <p class="text-sm text-gray-600">{{ channel.platform }}</p>
                </div>
              </div>
              <div class="text-right">
                <span
                  :class="getStatusClass(channel.status)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ getStatusLabel(channel.status) }}
                </span>
                <p v-if="channel.followers_count" class="mt-1 text-sm text-gray-600">
                  {{ formatNumber(channel.followers_count) }} kuzatuvchi
                </p>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            <p class="mt-2 text-sm text-gray-600">Hech qanday kanal mavjud emas</p>
            <Link
              href="/marketing/channels"
              class="mt-3 inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors"
            >
              Birinchi kanalni qo'shish
            </Link>
          </div>
        </Card>

        <!-- Recent Posts -->
        <Card title="So'nggi Postlar">
          <template #action>
            <Link
              href="/marketing/content"
              class="text-sm text-primary-600 hover:text-primary-700 font-medium"
            >
              Barchasini ko'rish
            </Link>
          </template>

          <div v-if="recentPosts.length > 0" class="space-y-3">
            <div
              v-for="post in recentPosts"
              :key="post.id"
              class="p-3 hover:bg-gray-50 rounded-lg transition-colors"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <p class="font-medium text-gray-900">{{ post.title }}</p>
                  <p class="text-sm text-gray-600 mt-1">{{ post.platform }}</p>
                </div>
                <span
                  :class="getPostStatusClass(post.status)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ getPostStatusLabel(post.status) }}
                </span>
              </div>
              <p v-if="post.scheduled_at || post.published_at" class="text-xs text-gray-500 mt-2">
                {{ post.published_at || post.scheduled_at }}
              </p>
            </div>
          </div>

          <div v-else class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
            </svg>
            <p class="mt-2 text-sm text-gray-600">Hech qanday post mavjud emas</p>
            <Link
              href="/marketing/content"
              class="mt-3 inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors"
            >
              Birinchi postni yaratish
            </Link>
          </div>
        </Card>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import Card from '@/components/Card.vue';

defineProps({
  channels: {
    type: Array,
    default: () => [],
  },
  recentPosts: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    required: true,
  },
  currentBusiness: {
    type: Object,
    required: true,
  },
});

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('uz-UZ', {
    style: 'decimal',
    minimumFractionDigits: 0,
  }).format(amount) + ' so\'m';
};

const formatNumber = (num) => {
  return new Intl.NumberFormat('uz-UZ').format(num);
};

const getChannelIconClass = (type) => {
  const classes = {
    social_media: 'bg-blue-100 text-blue-600',
    email: 'bg-green-100 text-green-600',
    sms: 'bg-yellow-100 text-yellow-600',
    advertising: 'bg-red-100 text-red-600',
    other: 'bg-gray-100 text-gray-600',
  };
  return classes[type] || classes.other;
};

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
    paused: 'bg-yellow-100 text-yellow-800',
  };
  return classes[status] || classes.inactive;
};

const getStatusLabel = (status) => {
  const labels = {
    active: 'Faol',
    inactive: 'Faol emas',
    paused: 'To\'xtatilgan',
  };
  return labels[status] || status;
};

const getPostStatusClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    scheduled: 'bg-blue-100 text-blue-800',
    published: 'bg-green-100 text-green-800',
  };
  return classes[status] || classes.draft;
};

const getPostStatusLabel = (status) => {
  const labels = {
    draft: 'Qoralama',
    scheduled: 'Rejalashtirilgan',
    published: 'Nashr qilingan',
  };
  return labels[status] || status;
};
</script>
