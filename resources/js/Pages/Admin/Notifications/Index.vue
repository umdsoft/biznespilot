<template>
  <AdminLayout :title="t('admin.notifications.title')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ t('admin.notifications.title') }}</h1>
          <p class="mt-1 text-sm text-gray-500">
            {{ t('admin.notifications.subtitle') }}
          </p>
        </div>
        <Link
          href="/dashboard/notifications/create"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          {{ t('admin.notifications.new_notification') }}
        </Link>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
          <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
          <div class="text-sm text-gray-500">{{ t('admin.notifications.stats.total') }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
          <div class="text-2xl font-bold text-blue-600">{{ stats.unread }}</div>
          <div class="text-sm text-gray-500">{{ t('admin.notifications.stats.unread') }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
          <div class="text-2xl font-bold text-green-600">{{ stats.read }}</div>
          <div class="text-sm text-gray-500">{{ t('admin.notifications.stats.read') }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
          <div class="text-2xl font-bold text-purple-600">{{ stats.broadcast }}</div>
          <div class="text-sm text-gray-500">{{ t('admin.notifications.stats.broadcast') }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
          <div class="text-2xl font-bold text-orange-600">{{ stats.personal }}</div>
          <div class="text-sm text-gray-500">{{ t('admin.notifications.stats.personal') }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
          <div class="text-2xl font-bold text-indigo-600">{{ stats.today }}</div>
          <div class="text-sm text-gray-500">{{ t('admin.notifications.stats.today') }}</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex flex-wrap items-center gap-4">
          <!-- Search -->
          <div class="flex-1 min-w-[200px]">
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="t('common.search') + '...'"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              @input="debouncedSearch"
            />
          </div>

          <!-- Type Filter -->
          <select
            v-model="typeFilter"
            @change="applyFilters"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="all">{{ t('admin.notifications.all_types') }}</option>
            <option v-for="(type, key) in types" :key="key" :value="key">
              {{ type.label }}
            </option>
          </select>

          <!-- Scope Filter -->
          <select
            v-model="scopeFilter"
            @change="applyFilters"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="all">{{ t('admin.common.all') }}</option>
            <option value="broadcast">{{ t('admin.notifications.stats.broadcast') }}</option>
            <option value="personal">{{ t('admin.notifications.stats.personal') }}</option>
          </select>
        </div>
      </div>

      <!-- Notifications Table -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('admin.notifications.notification') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('admin.notifications.type') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('admin.notifications.recipient') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.status') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.date') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ t('common.actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="notification in notifications.data" :key="notification.id" class="hover:bg-gray-50">
                <td class="px-6 py-4">
                  <div class="flex items-start space-x-3">
                    <div
                      class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                      :class="getTypeClass(notification.type).bg"
                    >
                      <svg class="w-5 h-5" :class="getTypeClass(notification.type).icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getTypeIcon(notification.type)" />
                      </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900 truncate">
                        {{ notification.title }}
                      </p>
                      <p class="text-sm text-gray-500 line-clamp-1">
                        {{ notification.message }}
                      </p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="getTypeClass(notification.type).badge"
                  >
                    {{ types[notification.type]?.label || notification.type }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div v-if="notification.user">
                    <p class="text-sm font-medium text-gray-900">{{ notification.user.name }}</p>
                    <p class="text-xs text-gray-500">{{ notification.user.email }}</p>
                  </div>
                  <div v-else>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                      {{ t('admin.notifications.stats.broadcast') }}
                    </span>
                    <p v-if="notification.business" class="text-xs text-gray-500 mt-1">
                      {{ notification.business.name }}
                    </p>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    v-if="notification.read_at"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                  >
                    {{ t('admin.notifications.status.read') }}
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                  >
                    {{ t('admin.notifications.status.new') }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(notification.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button
                    @click="deleteNotification(notification)"
                    class="text-red-600 hover:text-red-900"
                    :title="t('common.delete')"
                  >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </td>
              </tr>
              <tr v-if="notifications.data.length === 0">
                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                  {{ t('admin.notifications.not_found') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="notifications.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
          <div class="text-sm text-gray-500">
            {{ t('admin.common.showing') }} {{ notifications.from }}-{{ notifications.to }} {{ t('admin.common.of') }} {{ notifications.total }} {{ t('admin.common.results') }}
          </div>
          <div class="flex items-center space-x-2">
            <Link
              v-if="notifications.prev_page_url"
              :href="notifications.prev_page_url"
              class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50"
            >
              {{ t('common.previous') }}
            </Link>
            <Link
              v-if="notifications.next_page_url"
              :href="notifications.next_page_url"
              class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50"
            >
              {{ t('common.next') }}
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import axios from 'axios';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
  notifications: Object,
  stats: Object,
  filters: Object,
  types: Object,
});

const searchQuery = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || 'all');
const scopeFilter = ref(props.filters?.scope || 'all');

let searchTimeout = null;

const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 300);
};

const applyFilters = () => {
  const params = {};
  if (searchQuery.value) params.search = searchQuery.value;
  if (typeFilter.value !== 'all') params.type = typeFilter.value;
  if (scopeFilter.value !== 'all') params.scope = scopeFilter.value;

  router.get('/dashboard/notifications', params, { preserveState: true });
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('uz-UZ', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const getTypeClass = (type) => {
  const classes = {
    system: { bg: 'bg-gray-100', icon: 'text-gray-600', badge: 'bg-gray-100 text-gray-800' },
    update: { bg: 'bg-green-100', icon: 'text-green-600', badge: 'bg-green-100 text-green-800' },
    announcement: { bg: 'bg-indigo-100', icon: 'text-indigo-600', badge: 'bg-indigo-100 text-indigo-800' },
    alert: { bg: 'bg-red-100', icon: 'text-red-600', badge: 'bg-red-100 text-red-800' },
    celebration: { bg: 'bg-yellow-100', icon: 'text-yellow-600', badge: 'bg-yellow-100 text-yellow-800' },
    insight: { bg: 'bg-blue-100', icon: 'text-blue-600', badge: 'bg-blue-100 text-blue-800' },
  };
  return classes[type] || classes.system;
};

const getTypeIcon = (type) => {
  const icons = {
    system: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    update: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
    announcement: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
    alert: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
    celebration: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
    insight: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
  };
  return icons[type] || icons.system;
};

const deleteNotification = async (notification) => {
  if (!confirm(t('admin.notifications.delete_confirm'))) return;

  try {
    await axios.delete(`/dashboard/notifications/${notification.id}`);
    router.reload();
  } catch (error) {
    alert('Xatolik yuz berdi');
  }
};
</script>
