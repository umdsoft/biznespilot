<template>
  <BaseLayout :title="title || t('layout.home')" :config="layoutConfig">
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
          <!-- Pending Orders Badge -->
          <span
            v-if="item.href === '/business/store/orders' && pendingOrdersCount > 0"
            class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold bg-red-500 text-white rounded-full animate-pulse"
          >
            {{ pendingOrdersCount > 99 ? '99+' : pendingOrdersCount }}
          </span>
        </NavLink>
      </template>

      <!-- Dynamic Bot Store Menu -->
      <template v-if="activeStore">
        <div class="pt-3 mt-3 border-t-2" :style="{ borderColor: activeStore.store_type_color }">
          <div class="flex items-center justify-between px-3 mb-1">
            <div class="flex items-center gap-2 min-w-0">
              <span
                class="w-2 h-2 rounded-full flex-shrink-0"
                :style="{ backgroundColor: activeStore.store_type_color }"
              ></span>
              <span
                class="text-xs font-bold uppercase tracking-wider truncate"
                :style="{ color: activeStore.store_type_color }"
              >
                {{ activeStore.name }}
              </span>
            </div>
            <Link
              :href="route('business.store.deselect')"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 flex-shrink-0 p-0.5"
              title="Yopish"
            >
              <XMarkIcon class="w-4 h-4" />
            </Link>
          </div>
          <p class="px-3 mb-2 text-[10px] text-gray-500 dark:text-gray-400">
            {{ activeStore.store_type_label }}
          </p>
        </div>

        <NavLink
          v-for="item in botMenuItems"
          :key="item.href"
          :href="item.href"
          :active="isActive(item)"
        >
          <component :is="item.icon" class="w-5 h-5 mr-3" />
          <span class="flex-1">{{ item.label }}</span>
          <span
            v-if="item.badgeKey === 'pending_orders' && pendingOrdersCount > 0"
            class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold bg-red-500 text-white rounded-full animate-pulse"
          >
            {{ pendingOrdersCount > 99 ? '99+' : pendingOrdersCount }}
          </span>
        </NavLink>
      </template>
    </template>

    <slot />

    <!-- Flash Toast Notifications -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-2 opacity-0"
      >
        <div
          v-if="flashVisible"
          class="fixed bottom-6 right-6 z-[9999] max-w-sm w-full"
        >
          <div
            class="flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg border backdrop-blur-sm"
            :class="flashType === 'success'
              ? 'bg-emerald-50 dark:bg-emerald-900/80 border-emerald-200 dark:border-emerald-700 text-emerald-800 dark:text-emerald-200'
              : flashType === 'error'
                ? 'bg-red-50 dark:bg-red-900/80 border-red-200 dark:border-red-700 text-red-800 dark:text-red-200'
                : 'bg-amber-50 dark:bg-amber-900/80 border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-200'"
          >
            <div class="flex-shrink-0">
              <svg v-if="flashType === 'success'" class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <svg v-else-if="flashType === 'error'" class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
              </svg>
              <svg v-else class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
              </svg>
            </div>
            <p class="text-sm font-medium flex-1">{{ flashMessage }}</p>
            <button @click="flashVisible = false" class="flex-shrink-0 opacity-60 hover:opacity-100 transition-opacity">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </BaseLayout>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import BaseLayout from './BaseLayout.vue';
import NavLink from '@/components/NavLink.vue';
import { businessLayoutConfig } from '@/composables/useLayoutConfig';
import { useI18n } from '@/i18n';
import axios from 'axios';
import {
  ChartBarSquareIcon,
  BuildingStorefrontIcon,
  ClipboardDocumentListIcon as ClipDocIcon,
  UsersIcon as UsersIconSb,
  CubeIcon as CubeIconSb,
  TagIcon as TagIconSb,
  CogIcon as CogIconSb,
  ShoppingCartIcon as CartIconSb,
  FolderIcon,
  MapPinIcon,
  CalendarDaysIcon,
  WrenchIcon,
  BuildingOfficeIcon as BuildOfficeIcon,
  UserGroupIcon as UserGroupSb,
  DocumentTextIcon as DocTextIcon,
  ListBulletIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

defineProps({
  title: {
    type: String,
    default: '',
  },
});

const page = usePage();
const layoutConfig = businessLayoutConfig;

// Active store from Inertia shared props
const activeStore = computed(() => page.props?.activeStore || null);

// Icon map for dynamic sidebar
const sidebarIconMap = {
  ChartBarSquareIcon,
  BuildingStorefrontIcon,
  ClipboardDocumentListIcon: ClipDocIcon,
  UsersIcon: UsersIconSb,
  CubeIcon: CubeIconSb,
  TagIcon: TagIconSb,
  CogIcon: CogIconSb,
  ShoppingCartIcon: CartIconSb,
  FolderIcon,
  MapPinIcon,
  CalendarDaysIcon,
  WrenchIcon,
  BuildingOfficeIcon: BuildOfficeIcon,
  UserGroupIcon: UserGroupSb,
  DocumentTextIcon: DocTextIcon,
  ListBulletIcon,
};

// Map sidebar_menu items to NavLink-compatible items
const botMenuItems = computed(() => {
  if (!activeStore.value?.sidebar_menu) return [];
  return activeStore.value.sidebar_menu.map(item => ({
    href: `/business/store/${item.routeSuffix}`,
    label: item.label,
    icon: sidebarIconMap[item.icon] || CubeIconSb,
    badgeKey: item.badge || null,
  }));
});

// Flash toast
const flashVisible = ref(false);
const flashMessage = ref('');
const flashType = ref('success');
let flashTimeout = null;
let removeListeners = [];

const showFlash = (message, type) => {
  if (flashTimeout) clearTimeout(flashTimeout);
  flashMessage.value = message;
  flashType.value = type;
  flashVisible.value = true;
  flashTimeout = setTimeout(() => { flashVisible.value = false; }, 4000);
};

const checkFlash = () => {
  const flash = page.props?.flash;
  if (flash?.success) showFlash(flash.success, 'success');
  else if (flash?.error) showFlash(flash.error, 'error');
  else if (flash?.warning) showFlash(flash.warning, 'warning');
};

// Stats
const inboxUnreadCount = ref(0);
const newLeadsCount = ref(0);
const taskStats = ref({ total: 0, overdue: 0 });
const todoStats = ref({ total: 0, overdue: 0 });
const pendingOrdersCount = ref(0);

// Polling intervals
let inboxPollingInterval = null;
let leadsPollingInterval = null;
let taskPollingInterval = null;
let todoPollingInterval = null;
let ordersPollingInterval = null;

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

const fetchPendingOrdersCount = async () => {
  try {
    const response = await axios.get('/business/store/orders/pending-count');
    if (response.data?.count !== undefined) {
      pendingOrdersCount.value = response.data.count;
    }
  } catch (error) {
    // Silently fail - store may not be set up
  }
};

// Start polling
const startPolling = () => {
  fetchInboxUnreadCount();
  fetchNewLeadsCount();
  fetchTaskStats();
  fetchTodoStats();
  fetchPendingOrdersCount();

  inboxPollingInterval = setInterval(fetchInboxUnreadCount, 10000);
  leadsPollingInterval = setInterval(fetchNewLeadsCount, 15000);
  taskPollingInterval = setInterval(fetchTaskStats, 10000);
  todoPollingInterval = setInterval(fetchTodoStats, 30000);
  ordersPollingInterval = setInterval(fetchPendingOrdersCount, 20000);
};

// Stop polling
const stopPolling = () => {
  if (inboxPollingInterval) clearInterval(inboxPollingInterval);
  if (leadsPollingInterval) clearInterval(leadsPollingInterval);
  if (taskPollingInterval) clearInterval(taskPollingInterval);
  if (todoPollingInterval) clearInterval(todoPollingInterval);
  if (ordersPollingInterval) clearInterval(ordersPollingInterval);
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
  // Sahifa birinchi yuklanganda flash tekshirish
  checkFlash();
  // Inertia har bir muvaffaqiyatli navigatsiyada flash tekshirish
  removeListeners.push(
    router.on('success', () => nextTick(checkFlash)),
    router.on('navigate', () => nextTick(checkFlash)),
  );
});

onUnmounted(() => {
  stopPolling();
  removeListeners.forEach(fn => fn && fn());
});
</script>
