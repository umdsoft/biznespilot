<template>
  <BaseLayout :title="title" :config="layoutConfig" :quick-stats="formattedQuickStats">
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
          <!-- Leads Badge -->
          <span
            v-if="item.href === '/sales-head/leads' && leadStats.new > 0"
            class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold bg-emerald-500 text-white rounded-full"
          >
            {{ leadStats.new > 99 ? '99+' : leadStats.new }}
          </span>
          <!-- Tasks Badge -->
          <span
            v-if="item.href === '/sales-head/tasks' && taskStats.overdue > 0"
            class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold bg-red-500 text-white rounded-full animate-pulse"
          >
            {{ taskStats.overdue > 99 ? '99+' : taskStats.overdue }}
          </span>
          <!-- Calls Badge -->
          <span
            v-if="item.href === '/sales-head/calls' && callStats.missed > 0"
            class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold bg-orange-500 text-white rounded-full"
          >
            {{ callStats.missed > 99 ? '99+' : callStats.missed }}
          </span>
          <!-- Messages Badge -->
          <span
            v-if="item.href === '/sales-head/messages' && messageStats.unread > 0"
            class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold bg-blue-500 text-white rounded-full"
          >
            {{ messageStats.unread > 99 ? '99+' : messageStats.unread }}
          </span>
          <!-- Inbox Badge -->
          <span
            v-if="item.href === '/sales-head/inbox' && inboxStats.unread > 0"
            class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold bg-red-500 text-white rounded-full animate-pulse"
          >
            {{ inboxStats.unread > 99 ? '99+' : inboxStats.unread }}
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
import { salesHeadLayoutConfig } from '@/composables/useLayoutConfig';
import axios from 'axios';

defineProps({
  title: {
    type: String,
    default: 'Dashboard',
  },
});

const page = usePage();
const layoutConfig = salesHeadLayoutConfig;

// Stats
const leadStats = ref({ new: 0, total: 0 });
const taskStats = ref({ total: 0, overdue: 0 });
const callStats = ref({ missed: 0, total: 0 });
const messageStats = ref({ unread: 0, total: 0 });
const inboxStats = ref({ unread: 0, total: 0 });
const todayStats = ref({ deals: 0, revenue: 0 });

let statsPollingInterval = null;

// Format currency
const formatCurrency = (value) => {
  if (!value) return '0 so\'m';
  return new Intl.NumberFormat('uz-UZ').format(value) + ' so\'m';
};

// Quick stats for header
const formattedQuickStats = computed(() => [
  {
    label: 'Bugun',
    value: `${todayStats.value.deals} bitim`,
    bgClass: 'bg-emerald-50 dark:bg-emerald-900/30',
    labelClass: 'text-emerald-600 dark:text-emerald-400',
    valueClass: 'text-emerald-700 dark:text-emerald-300',
  },
  {
    label: 'Summa',
    value: formatCurrency(todayStats.value.revenue),
    bgClass: 'bg-blue-50 dark:bg-blue-900/30',
    labelClass: 'text-blue-600 dark:text-blue-400',
    valueClass: 'text-blue-700 dark:text-blue-300',
  },
]);

// Fetch stats
const fetchStats = async () => {
  try {
    const response = await axios.get('/sales-head/api/stats');
    if (response.data) {
      leadStats.value = response.data.leads || { new: 0, total: 0 };
      taskStats.value = response.data.tasks || { total: 0, overdue: 0 };
      callStats.value = response.data.calls || { missed: 0, total: 0 };
      messageStats.value = response.data.messages || { unread: 0, total: 0 };
      inboxStats.value = response.data.inbox || { unread: 0, total: 0 };
      todayStats.value = response.data.today || { deals: 0, revenue: 0 };
    }
  } catch (error) {
    console.error('Failed to fetch stats:', error);
  }
};

// Start polling
const startStatsPolling = () => {
  fetchStats();
  statsPollingInterval = setInterval(fetchStats, 30000);
};

// Stop polling
const stopStatsPolling = () => {
  if (statsPollingInterval) {
    clearInterval(statsPollingInterval);
    statsPollingInterval = null;
  }
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
  startStatsPolling();
});

onUnmounted(() => {
  stopStatsPolling();
});
</script>
