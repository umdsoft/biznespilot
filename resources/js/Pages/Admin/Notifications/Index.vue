<template>
  <AdminLayout title="Bildirishnomalar">
    <div class="max-w-[1800px] mx-auto">
      <!-- Compact Header -->
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-lg font-semibold text-gray-900 dark:text-white">Bildirishnomalar</h1>
          <p class="text-xs text-gray-500 dark:text-gray-400">Tizim bildirishnomalari va e'lonlar</p>
        </div>
        <Link
          href="/dashboard/notifications/create"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          Yangi bildirishnoma
        </Link>
      </div>

      <!-- Compact Stats -->
      <div class="grid grid-cols-3 md:grid-cols-6 gap-2 mb-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2">
          <div class="text-lg font-bold text-gray-900 dark:text-white">{{ stats.total }}</div>
          <div class="text-xs text-gray-500 dark:text-gray-400">Jami</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2">
          <div class="text-lg font-bold text-blue-600">{{ stats.unread }}</div>
          <div class="text-xs text-gray-500 dark:text-gray-400">O'qilmagan</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2">
          <div class="text-lg font-bold text-green-600">{{ stats.read }}</div>
          <div class="text-xs text-gray-500 dark:text-gray-400">O'qilgan</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2">
          <div class="text-lg font-bold text-purple-600">{{ stats.broadcast }}</div>
          <div class="text-xs text-gray-500 dark:text-gray-400">Umumiy</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2">
          <div class="text-lg font-bold text-orange-600">{{ stats.personal }}</div>
          <div class="text-xs text-gray-500 dark:text-gray-400">Shaxsiy</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2">
          <div class="text-lg font-bold text-indigo-600">{{ stats.today }}</div>
          <div class="text-xs text-gray-500 dark:text-gray-400">Bugun</div>
        </div>
      </div>

      <!-- Filter Tabs & Search -->
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 mb-4">
        <div class="flex flex-wrap items-center justify-between gap-3 px-3 py-2 border-b border-gray-200 dark:border-gray-700">
          <!-- Filter Tabs -->
          <div class="flex items-center gap-1">
            <button
              @click="setFilter('all')"
              :class="[
                'px-3 py-1 text-xs font-medium rounded-md transition-colors',
                activeFilter === 'all'
                  ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300'
                  : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'
              ]"
            >
              Barchasi ({{ stats.total }})
            </button>
            <button
              @click="setFilter('unread')"
              :class="[
                'px-3 py-1 text-xs font-medium rounded-md transition-colors',
                activeFilter === 'unread'
                  ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300'
                  : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'
              ]"
            >
              O'qilmagan ({{ stats.unread }})
            </button>
            <button
              @click="setFilter('read')"
              :class="[
                'px-3 py-1 text-xs font-medium rounded-md transition-colors',
                activeFilter === 'read'
                  ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300'
                  : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'
              ]"
            >
              O'qilgan ({{ stats.read }})
            </button>
            <button
              @click="setFilter('broadcast')"
              :class="[
                'px-3 py-1 text-xs font-medium rounded-md transition-colors',
                activeFilter === 'broadcast'
                  ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300'
                  : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'
              ]"
            >
              Umumiy ({{ stats.broadcast }})
            </button>
            <button
              @click="setFilter('personal')"
              :class="[
                'px-3 py-1 text-xs font-medium rounded-md transition-colors',
                activeFilter === 'personal'
                  ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300'
                  : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'
              ]"
            >
              Shaxsiy ({{ stats.personal }})
            </button>
          </div>

          <!-- Search & Type Filter -->
          <div class="flex items-center gap-2">
            <select
              v-model="typeFilter"
              @change="applyFilters"
              class="px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-blue-500"
            >
              <option value="all">Barcha turlar</option>
              <option v-for="(type, key) in types" :key="key" :value="key">
                {{ type.label }}
              </option>
            </select>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Qidirish..."
              class="px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-1 focus:ring-blue-500 w-40"
              @input="debouncedSearch"
            />
          </div>
        </div>

        <!-- Compact Table -->
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 dark:bg-gray-900/50">
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bildirishnoma</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Turi</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Qabul qiluvchi</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Holat</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sana</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amallar</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr
                v-for="notification in filteredNotifications"
                :key="notification.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
              >
                <td class="px-3 py-2">
                  <div class="flex items-center gap-2">
                    <div
                      class="flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center"
                      :class="getTypeClass(notification.type).bg"
                    >
                      <svg class="w-3.5 h-3.5" :class="getTypeClass(notification.type).icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getTypeIcon(notification.type)" />
                      </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                      <p class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs">
                        {{ notification.title }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                        {{ notification.message }}
                      </p>
                    </div>
                  </div>
                </td>
                <td class="px-3 py-2">
                  <span
                    class="inline-flex px-2 py-0.5 rounded text-xs font-medium"
                    :class="getTypeClass(notification.type).badge"
                  >
                    {{ types[notification.type]?.label || notification.type }}
                  </span>
                </td>
                <td class="px-3 py-2">
                  <div v-if="notification.user" class="text-xs">
                    <p class="font-medium text-gray-900 dark:text-white">{{ notification.user.name }}</p>
                    <p class="text-gray-500 dark:text-gray-400">{{ notification.user.email }}</p>
                  </div>
                  <div v-else>
                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300">
                      Umumiy
                    </span>
                    <p v-if="notification.business" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                      {{ notification.business.name }}
                    </p>
                  </div>
                </td>
                <td class="px-3 py-2">
                  <span
                    v-if="notification.read_at"
                    class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300"
                  >
                    O'qilgan
                  </span>
                  <span
                    v-else
                    class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300"
                  >
                    Yangi
                  </span>
                </td>
                <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                  {{ formatDate(notification.created_at) }}
                </td>
                <td class="px-3 py-2 text-right">
                  <button
                    @click="deleteNotification(notification)"
                    class="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                    title="O'chirish"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </td>
              </tr>
              <tr v-if="filteredNotifications.length === 0">
                <td colspan="6" class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                  Bildirishnomalar topilmadi
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Compact Pagination -->
        <div v-if="notifications.last_page > 1" class="flex items-center justify-between px-3 py-2 border-t border-gray-200 dark:border-gray-700">
          <div class="text-xs text-gray-500 dark:text-gray-400">
            {{ notifications.from }}-{{ notifications.to }} / {{ notifications.total }}
          </div>
          <div class="flex items-center gap-1">
            <Link
              v-if="notifications.prev_page_url"
              :href="notifications.prev_page_url"
              class="px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300"
            >
              Oldingi
            </Link>
            <Link
              v-if="notifications.next_page_url"
              :href="notifications.next_page_url"
              class="px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300"
            >
              Keyingi
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import axios from 'axios';

const props = defineProps({
  notifications: Object,
  stats: Object,
  filters: Object,
  types: Object,
});

const searchQuery = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || 'all');
const activeFilter = ref(props.filters?.scope || 'all');

let searchTimeout = null;

const filteredNotifications = computed(() => {
  if (!props.notifications?.data) return [];

  let data = props.notifications.data;

  // Client-side filtering for quick tab switching
  if (activeFilter.value === 'unread') {
    data = data.filter(n => !n.read_at);
  } else if (activeFilter.value === 'read') {
    data = data.filter(n => n.read_at);
  } else if (activeFilter.value === 'broadcast') {
    data = data.filter(n => !n.user);
  } else if (activeFilter.value === 'personal') {
    data = data.filter(n => n.user);
  }

  return data;
});

const setFilter = (filter) => {
  activeFilter.value = filter;
  applyFilters();
};

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
  if (activeFilter.value !== 'all') params.scope = activeFilter.value;

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
    system: {
      bg: 'bg-gray-100 dark:bg-gray-700',
      icon: 'text-gray-600 dark:text-gray-400',
      badge: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
    },
    update: {
      bg: 'bg-green-100 dark:bg-green-900/50',
      icon: 'text-green-600 dark:text-green-400',
      badge: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300'
    },
    announcement: {
      bg: 'bg-indigo-100 dark:bg-indigo-900/50',
      icon: 'text-indigo-600 dark:text-indigo-400',
      badge: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300'
    },
    alert: {
      bg: 'bg-red-100 dark:bg-red-900/50',
      icon: 'text-red-600 dark:text-red-400',
      badge: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300'
    },
    celebration: {
      bg: 'bg-yellow-100 dark:bg-yellow-900/50',
      icon: 'text-yellow-600 dark:text-yellow-400',
      badge: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300'
    },
    insight: {
      bg: 'bg-blue-100 dark:bg-blue-900/50',
      icon: 'text-blue-600 dark:text-blue-400',
      badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300'
    },
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
  if (!confirm('Bildirishnomani o\'chirishni tasdiqlaysizmi?')) return;

  try {
    await axios.delete(`/dashboard/notifications/${notification.id}`);
    router.reload();
  } catch (error) {
    alert('Xatolik yuz berdi');
  }
};
</script>
