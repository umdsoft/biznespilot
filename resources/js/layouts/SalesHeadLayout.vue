<template>
  <BaseLayout :title="title || t('layout.home')" :config="layoutConfig" :quick-stats="formattedQuickStats">
    <!-- Alert Dropdown in Header -->
    <template #notifications>
      <AlertDropdown />
    </template>

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
        <template v-for="item in section.items" :key="item.href || item.label">
          <!-- Dropdown Menu (with children) -->
          <div v-if="item.children" class="mb-1">
            <button
              @click="toggleDropdown(item.label)"
              :class="[
                'w-full flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                isChildActive(item.children)
                  ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
                  : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50'
              ]"
            >
              <component :is="item.icon" class="w-5 h-5 mr-3" />
              <span class="flex-1 text-left">{{ item.label }}</span>
              <ChevronDownIcon
                :class="[
                  'w-4 h-4 transition-transform duration-200',
                  openDropdowns[item.label] ? 'rotate-180' : ''
                ]"
              />
            </button>

            <!-- Dropdown Children -->
            <div
              v-show="openDropdowns[item.label]"
              class="mt-1 ml-4 pl-4 border-l-2 border-gray-200 dark:border-gray-700 space-y-1"
            >
              <NavLink
                v-for="child in item.children"
                :key="child.href"
                :href="child.href"
                :active="isActive(child)"
                class="!py-1.5 !text-sm"
              >
                <span>{{ child.label }}</span>
              </NavLink>
            </div>
          </div>

          <!-- Regular Menu Item (no children) -->
          <NavLink
            v-else
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
    </template>

    <slot />
  </BaseLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import BaseLayout from './BaseLayout.vue';
import NavLink from '@/components/NavLink.vue';
import AlertDropdown from '@/components/Sales/AlertDropdown.vue';
import { salesHeadLayoutConfig } from '@/composables/useLayoutConfig';
import { useI18n } from '@/i18n';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';
import axios from 'axios';

const { t } = useI18n();

defineProps({
  title: {
    type: String,
    default: '',
  },
});

const page = usePage();
const layoutConfig = salesHeadLayoutConfig;

// Dropdown state
const openDropdowns = reactive({});

// Initialize dropdowns - open if any child is active
const initDropdowns = () => {
  layoutConfig.navigation.forEach(section => {
    section.items.forEach(item => {
      if (item.children) {
        // Auto-open if any child is active
        const hasActiveChild = item.children.some(child =>
          page.url.startsWith(child.href)
        );
        openDropdowns[item.label] = hasActiveChild;
      }
    });
  });
};

// Toggle dropdown
const toggleDropdown = (label) => {
  openDropdowns[label] = !openDropdowns[label];
};

// Check if any child is active
const isChildActive = (children) => {
  return children.some(child => page.url.startsWith(child.href));
};

// Stats
const leadStats = ref({ new: 0, total: 0 });
const taskStats = ref({ total: 0, overdue: 0 });
const callStats = ref({ missed: 0, total: 0 });
const inboxStats = ref({ unread: 0, total: 0 });
const todayStats = ref({ deals: 0, revenue: 0 });

let statsPollingInterval = null;

// Format currency
const formatCurrency = (value) => {
  if (!value) return '0 ' + t('common.currency');
  return new Intl.NumberFormat('uz-UZ').format(value) + ' ' + t('common.currency');
};

// Quick stats for header
const formattedQuickStats = computed(() => [
  {
    label: t('layout.today'),
    value: `${todayStats.value.deals} ${t('layout.deals')}`,
    bgClass: 'bg-emerald-50 dark:bg-emerald-900/30',
    labelClass: 'text-emerald-600 dark:text-emerald-400',
    valueClass: 'text-emerald-700 dark:text-emerald-300',
  },
  {
    label: t('layout.amount'),
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
  initDropdowns();
  startStatsPolling();
});

onUnmounted(() => {
  stopStatsPolling();
});
</script>
