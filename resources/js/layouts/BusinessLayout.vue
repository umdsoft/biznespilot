<template>
  <BaseLayout :title="title" :config="layoutConfig">
    <template #navigation>
      <template v-for="(section, sectionIndex) in layoutConfig.navigation" :key="sectionIndex">
        <!-- Section Divider -->
        <div v-if="sectionIndex > 0" class="pt-3 mt-3 border-t border-gray-200 dark:border-gray-700">
          <p v-if="section.title" class="px-3 mb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            {{ section.title }}
          </p>
        </div>
        <div v-else-if="section.title" class="mb-2">
          <p class="px-3 mb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            {{ section.title }}
          </p>
        </div>

        <!-- Navigation Items -->
        <NavLink
          v-for="item in section.items"
          :key="item.href"
          :href="item.href"
          :active="isActive(item)"
        >
          <component :is="item.icon" class="w-5 h-5 mr-3" />
          <span class="flex-1">{{ item.label }}</span>
          <!-- Inbox Badge -->
          <span
            v-if="item.href === '/business/inbox' && inboxUnreadCount > 0"
            class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold bg-red-500 text-white rounded-full animate-pulse"
          >
            {{ inboxUnreadCount > 99 ? '99+' : inboxUnreadCount }}
          </span>
          <!-- New Leads Badge -->
          <span
            v-if="item.href === '/business/sales' && newLeadsCount > 0"
            class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold bg-red-500 text-white rounded-full animate-pulse"
          >
            {{ newLeadsCount > 99 ? '99+' : newLeadsCount }}
          </span>
          <!-- Tasks Badge -->
          <span
            v-if="item.href === '/business/tasks' && taskStats.overdue > 0"
            class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold bg-red-500 text-white rounded-full animate-pulse"
          >
            {{ taskStats.overdue > 99 ? '99+' : taskStats.overdue }}
          </span>
          <!-- Todos Badge -->
          <span
            v-if="item.href === '/business/todos' && todoStats.overdue > 0"
            class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold bg-orange-500 text-white rounded-full"
          >
            {{ todoStats.overdue > 99 ? '99+' : todoStats.overdue }}
          </span>
        </NavLink>
      </template>
    </template>

    <slot />
  </BaseLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import BaseLayout from './BaseLayout.vue';
import NavLink from '@/components/NavLink.vue';
import { businessLayoutConfig } from '@/composables/useLayoutConfig';
import axios from 'axios';

defineProps({
  title: {
    type: String,
    default: 'Bosh sahifa',
  },
});

const page = usePage();
const layoutConfig = businessLayoutConfig;

// Stats
const inboxUnreadCount = ref(0);
const newLeadsCount = ref(0);
const taskStats = ref({ total: 0, overdue: 0 });
const todoStats = ref({ total: 0, overdue: 0 });

// Polling intervals
let inboxPollingInterval = null;
let leadsPollingInterval = null;
let taskPollingInterval = null;
let todoPollingInterval = null;

// Fetch functions
const fetchInboxUnreadCount = async () => {
  try {
    const response = await axios.get('/business/inbox', {
      headers: { 'Accept': 'application/json' }
    });
    if (response.data.stats?.unread?.total !== undefined) {
      inboxUnreadCount.value = response.data.stats.unread.total;
    }
  } catch (error) {
    // Silently fail - stats are optional UI enhancement
    if (error.response?.status !== 404) {
      console.error('Failed to fetch inbox stats:', error);
    }
  }
};

const fetchNewLeadsCount = async () => {
  try {
    const response = await axios.get('/business/api/sales/stats');
    if (response.data?.new_leads !== undefined) {
      newLeadsCount.value = response.data.new_leads;
    }
  } catch (error) {
    // Silently fail - stats are optional UI enhancement
    if (error.response?.status !== 404) {
      console.error('Failed to fetch leads stats:', error);
    }
  }
};

const fetchTaskStats = async () => {
  try {
    const response = await axios.get('/business/tasks/stats');
    if (response.data) {
      taskStats.value = {
        total: response.data.total || 0,
        overdue: response.data.overdue || 0,
      };
    }
  } catch (error) {
    // Silently fail - stats are optional UI enhancement
    if (error.response?.status !== 404) {
      console.error('Failed to fetch task stats:', error);
    }
  }
};

const fetchTodoStats = async () => {
  try {
    const response = await axios.get('/business/todos/api/dashboard');
    if (response.data?.stats) {
      todoStats.value = {
        total: response.data.stats.total_today || 0,
        overdue: response.data.stats.overdue || 0,
      };
    }
  } catch (error) {
    // Silently fail - stats are optional UI enhancement
    if (error.response?.status !== 404) {
      console.error('Failed to fetch todo stats:', error);
    }
  }
};

// Start polling
const startPolling = () => {
  fetchInboxUnreadCount();
  fetchNewLeadsCount();
  fetchTaskStats();
  fetchTodoStats();

  inboxPollingInterval = setInterval(fetchInboxUnreadCount, 10000);
  leadsPollingInterval = setInterval(fetchNewLeadsCount, 15000);
  taskPollingInterval = setInterval(fetchTaskStats, 10000);
  todoPollingInterval = setInterval(fetchTodoStats, 30000);
};

// Stop polling
const stopPolling = () => {
  if (inboxPollingInterval) clearInterval(inboxPollingInterval);
  if (leadsPollingInterval) clearInterval(leadsPollingInterval);
  if (taskPollingInterval) clearInterval(taskPollingInterval);
  if (todoPollingInterval) clearInterval(todoPollingInterval);
};

// Check if nav item is active
const isActive = (item) => {
  const url = page.url;
  if (item.exact) {
    return url === item.href || url === item.href + '/';
  }
  if (item.activeMatch) {
    return item.activeMatch(url);
  }
  return url.startsWith(item.href);
};

onMounted(() => {
  startPolling();
});

onUnmounted(() => {
  stopPolling();
});
</script>
