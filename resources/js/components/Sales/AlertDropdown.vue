<template>
  <div class="relative">
    <!-- Alert Button -->
    <button
      @click="toggleDropdown"
      class="relative flex items-center justify-center w-10 h-10 rounded-xl border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200"
      title="Ogohlantirishlar"
    >
      <BellAlertIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />

      <!-- Badge -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold bg-red-500 text-white rounded-full animate-pulse"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <div
      v-show="showDropdown"
      class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 z-50 overflow-hidden"
    >
      <!-- Header -->
      <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <h3 class="font-semibold text-gray-900 dark:text-white">Ogohlantirishlar</h3>
        <Link
          :href="alertsUrl"
          class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
          @click="showDropdown = false"
        >
          Barchasini ko'rish
        </Link>
      </div>

      <!-- Alerts List -->
      <div class="max-h-80 overflow-y-auto">
        <template v-if="alerts.length > 0">
          <div
            v-for="alert in alerts"
            :key="alert.id"
            class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-0 cursor-pointer"
            @click="handleAlertClick(alert)"
          >
            <div class="flex items-start gap-3">
              <!-- Priority Icon -->
              <div
                class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                :class="getPriorityClass(alert.priority)"
              >
                <component :is="getPriorityIcon(alert.priority)" class="w-4 h-4" />
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                  {{ alert.title }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                  {{ alert.message }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                  {{ formatTime(alert.created_at) }}
                </p>
              </div>
            </div>
          </div>
        </template>

        <!-- Empty State -->
        <div v-else class="px-4 py-8 text-center">
          <BellAlertIcon class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600" />
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Hozircha ogohlantirishlar yo'q
          </p>
        </div>
      </div>

      <!-- Footer -->
      <div v-if="alerts.length > 0" class="px-4 py-2 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
        <button
          @click="markAllAsRead"
          class="w-full text-center text-sm text-blue-600 dark:text-blue-400 hover:underline"
        >
          Barchasini o'qilgan deb belgilash
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import {
  BellAlertIcon,
  ExclamationTriangleIcon,
  ExclamationCircleIcon,
  InformationCircleIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const page = usePage();

const showDropdown = ref(false);
const alerts = ref([]);
const unreadCount = ref(0);
const loading = ref(false);

let pollingInterval = null;

// Determine alerts URL based on current path
const alertsUrl = computed(() => {
  const url = page.url;
  if (url.startsWith('/operator')) {
    return '/operator/alerts';
  }
  if (url.startsWith('/sales-head')) {
    return '/sales-head/alerts';
  }
  return '/business/alerts';
});

// Get active alerts API URL
const activeAlertsUrl = computed(() => {
  const url = page.url;
  if (url.startsWith('/operator')) {
    return '/operator/alerts/active';
  }
  if (url.startsWith('/sales-head')) {
    return '/sales-head/alerts/active';
  }
  return '/business/alerts/active';
});

const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value;
  if (showDropdown.value) {
    fetchAlerts();
  }
};

const fetchAlerts = async () => {
  try {
    loading.value = true;
    const response = await axios.get(activeAlertsUrl.value);
    if (response.data) {
      alerts.value = response.data.alerts || [];
      unreadCount.value = response.data.unreadCount || 0;
    }
  } catch (error) {
    console.error('Failed to fetch alerts:', error);
  } finally {
    loading.value = false;
  }
};

const handleAlertClick = (alert) => {
  showDropdown.value = false;
  router.visit(`${alertsUrl.value}/${alert.id}`);
};

const markAllAsRead = async () => {
  try {
    const markAllUrl = alertsUrl.value + '/mark-all-read';
    await axios.post(markAllUrl);
    unreadCount.value = 0;
    alerts.value = alerts.value.map(a => ({ ...a, status: 'read' }));
  } catch (error) {
    console.error('Failed to mark all as read:', error);
  }
};

const getPriorityClass = (priority) => {
  const classes = {
    urgent: 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
    high: 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400',
    medium: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
    low: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
  };
  return classes[priority] || classes.medium;
};

const getPriorityIcon = (priority) => {
  const icons = {
    urgent: ExclamationTriangleIcon,
    high: ExclamationCircleIcon,
    medium: InformationCircleIcon,
    low: CheckCircleIcon,
  };
  return icons[priority] || icons.medium;
};

const formatTime = (dateString) => {
  const date = new Date(dateString);
  const now = new Date();
  const diff = now - date;
  const minutes = Math.floor(diff / 60000);
  const hours = Math.floor(diff / 3600000);
  const days = Math.floor(diff / 86400000);

  if (minutes < 1) return 'Hozir';
  if (minutes < 60) return `${minutes} daqiqa oldin`;
  if (hours < 24) return `${hours} soat oldin`;
  if (days < 7) return `${days} kun oldin`;
  return date.toLocaleDateString('uz-UZ');
};

// Close dropdown when clicking outside
const closeDropdown = (e) => {
  if (!e.target.closest('.relative')) {
    showDropdown.value = false;
  }
};

// Start polling for unread count
const startPolling = () => {
  fetchAlerts();
  pollingInterval = setInterval(() => {
    if (!showDropdown.value) {
      fetchAlerts();
    }
  }, 60000); // Every minute
};

onMounted(() => {
  document.addEventListener('click', closeDropdown);
  startPolling();
});

onUnmounted(() => {
  document.removeEventListener('click', closeDropdown);
  if (pollingInterval) {
    clearInterval(pollingInterval);
  }
});
</script>
