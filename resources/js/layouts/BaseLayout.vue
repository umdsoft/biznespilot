<template>
  <div :class="['min-h-screen', config.bgClass]">
    <!-- Mobile Menu Overlay -->
    <div
      v-if="showMobileMenu"
      @click="showMobileMenu = false"
      class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
    ></div>

    <!-- Sidebar -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 w-64 z-50 transform transition-transform duration-300 lg:translate-x-0 lg:z-30',
        config.sidebarClass,
        { '-translate-x-full': !showMobileMenu }
      ]"
    >
      <!-- Logo -->
      <div :class="['h-16 flex items-center px-6 border-b', config.logoBorderClass]">
        <Link :href="config.homeUrl" class="flex items-center space-x-3">
          <div :class="['w-9 h-9 rounded-xl flex items-center justify-center shadow-lg', config.logoGradient]">
            <component :is="config.logoIcon" class="w-5 h-5 text-white" />
          </div>
          <div>
            <span :class="['text-lg font-bold bg-clip-text text-transparent', config.logoTextGradient]">
              {{ config.brandName }}
            </span>
            <p v-if="config.brandSubtitle" class="text-[10px] text-gray-500 dark:text-gray-400 -mt-1">
              {{ config.brandSubtitle }}
            </p>
          </div>
        </Link>
      </div>

      <!-- Optional Badge (for admin) -->
      <div v-if="config.badge" :class="['px-4 py-3 border-b', config.badgeClass]">
        <div class="flex items-center space-x-2">
          <div :class="['w-2 h-2 rounded-full animate-pulse', config.badgeDotClass]"></div>
          <span :class="['text-xs font-semibold uppercase tracking-wider', config.badgeTextClass]">
            {{ config.badge }}
          </span>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="p-4 space-y-1 overflow-y-auto h-[calc(100vh-4rem)]">
        <slot name="navigation">
          <template v-for="(section, sectionIndex) in config.navigation" :key="sectionIndex">
            <!-- Section Divider -->
            <div v-if="sectionIndex > 0" :class="['pt-3 mt-3 border-t', config.sectionBorderClass]">
              <p v-if="section.title || section.titleKey" class="px-3 mb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                {{ translateSectionTitle(section) }}
              </p>
            </div>
            <div v-else-if="section.title || section.titleKey" class="mb-2">
              <p class="px-3 mb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                {{ translateSectionTitle(section) }}
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
              <span class="flex-1">{{ translateLabel(item) }}</span>
              <span
                v-if="item.badge && item.badge.value > 0"
                :class="[
                  'ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold text-white rounded-full',
                  item.badge.class || 'bg-red-500',
                  item.badge.pulse ? 'animate-pulse' : ''
                ]"
              >
                {{ item.badge.value > 99 ? '99+' : item.badge.value }}
              </span>
            </NavLink>
          </template>
        </slot>
      </nav>
    </aside>

    <!-- Main Content -->
    <div class="lg:ml-64">
      <!-- Header -->
      <header :class="['h-16 sticky top-0 z-20 shadow-sm', config.headerClass]">
        <div class="h-full px-4 lg:px-6 flex items-center justify-between">
          <!-- Left Side -->
          <div class="flex items-center space-x-3">
            <!-- Mobile Menu Button -->
            <button
              @click="showMobileMenu = !showMobileMenu"
              class="lg:hidden p-2 rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
            >
              <MenuIcon class="w-6 h-6" />
            </button>

            <!-- Title Icon (optional) -->
            <div v-if="config.showTitleIcon" :class="['w-8 h-8 rounded-lg flex items-center justify-center', config.titleIconBgClass]">
              <component :is="config.titleIcon" :class="['w-4 h-4', config.titleIconClass]" />
            </div>

            <h1 :class="['text-lg font-semibold', config.titleClass]">
              {{ title }}
            </h1>
          </div>

          <!-- Right Side -->
          <div class="flex items-center space-x-4">
            <!-- Custom Header Content -->
            <slot name="header-content">
              <!-- Quick Stats (for SalesHead) -->
              <template v-if="config.showQuickStats && quickStats">
                <div class="hidden lg:flex items-center space-x-4 mr-4">
                  <div v-for="stat in quickStats" :key="stat.label" :class="['flex items-center px-3 py-1.5 rounded-lg', stat.bgClass]">
                    <span :class="['text-xs font-medium', stat.labelClass]">{{ stat.label }}:</span>
                    <span :class="['ml-2 text-sm font-bold', stat.valueClass]">{{ stat.value }}</span>
                  </div>
                </div>
              </template>
            </slot>

            <!-- Business Selector (for Business panel) -->
            <div v-if="config.showBusinessSelector" class="relative">
              <button
                @click="showBusinessMenu = !showBusinessMenu"
                class="flex items-center space-x-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl px-4 py-2 transition-all duration-200 border border-gray-200 dark:border-gray-600"
              >
                <div :class="['w-8 h-8 rounded-lg flex items-center justify-center', config.businessAvatarClass]">
                  <span class="text-xs font-bold text-white">
                    {{ currentBusinessInitial }}
                  </span>
                </div>
                <div class="text-left hidden sm:block">
                  <p class="text-sm font-medium text-gray-900 dark:text-gray-100 max-w-[150px] truncate">
                    {{ $page.props.currentBusiness?.name || t('layout.select_business') }}
                  </p>
                </div>
                <ChevronDownIcon class="w-4 h-4 text-gray-400 dark:text-gray-500" />
              </button>

              <!-- Business Dropdown -->
              <div
                v-show="showBusinessMenu"
                class="absolute right-0 mt-2 w-72 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 py-2 z-50"
              >
                <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-700">
                  <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('layout.your_businesses') }}</p>
                </div>
                <div class="max-h-64 overflow-y-auto">
                  <Link
                    v-for="business in $page.props.businesses"
                    :key="business.id"
                    :href="`/switch-business/${business.id}`"
                    method="post"
                    as="button"
                    @click="showBusinessMenu = false"
                    class="w-full flex items-center px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    :class="{ 'bg-blue-50 dark:bg-blue-900/30': business.id === $page.props.currentBusiness?.id }"
                  >
                    <div
                      class="w-9 h-9 rounded-lg flex items-center justify-center mr-3"
                      :class="business.id === $page.props.currentBusiness?.id ? config.businessAvatarClass : 'bg-gray-100 dark:bg-gray-700'"
                    >
                      <span
                        class="text-sm font-bold"
                        :class="business.id === $page.props.currentBusiness?.id ? 'text-white' : 'text-gray-600 dark:text-gray-300'"
                      >
                        {{ business.name?.charAt(0)?.toUpperCase() || '?' }}
                      </span>
                    </div>
                    <div class="flex-1 text-left">
                      <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ business.name }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">{{ business.category }}</p>
                    </div>
                    <CheckCircleIcon v-if="business.id === $page.props.currentBusiness?.id" class="w-5 h-5 text-blue-600" />
                  </Link>
                </div>
                <!-- Yangi biznes yaratish - hozircha yashirin -->
              <!--
              <div class="border-t border-gray-100 dark:border-gray-700 mt-2 pt-2 px-3">
                  <Link
                    href="/new-business"
                    @click="showBusinessMenu = false"
                    class="flex items-center w-full px-3 py-2.5 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                  >
                    <PlusIcon class="w-5 h-5 mr-2" />
                    <span class="text-sm font-medium">{{ t('layout.new_business') }}</span>
                  </Link>
                </div>
              -->
              </div>
            </div>

            <!-- Business Info (non-selectable, for SalesHead) -->
            <div v-if="config.showBusinessInfo" class="flex items-center space-x-3 px-4 py-2 bg-gray-50 dark:bg-gray-700 rounded-xl">
              <div :class="['w-8 h-8 rounded-lg flex items-center justify-center', config.businessAvatarClass]">
                <span class="text-xs font-bold text-white">
                  {{ currentBusinessInitial }}
                </span>
              </div>
              <div class="text-left hidden sm:block">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 max-w-[150px] truncate">
                  {{ $page.props.currentBusiness?.name || t('common.select_business') }}
                </p>
                <p :class="['text-xs', config.businessRoleClass]">{{ config.businessRoleLabel }}</p>
              </div>
            </div>

            <!-- Notifications -->
            <slot name="notifications">
              <NotificationDropdown v-if="config.showNotifications" />
            </slot>

            <!-- Language Switcher - hozircha yashirin -->
            <!-- <LanguageSwitcher /> -->

            <!-- Dark Mode Toggle -->
            <button
              v-if="config.showDarkModeToggle"
              @click="toggleDarkMode"
              class="flex items-center justify-center w-10 h-10 rounded-xl border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200"
              :title="isDarkMode ? 'Yorug\' rejimga o\'tish' : 'Qorong\'i rejimga o\'tish'"
            >
              <SunIcon v-if="isDarkMode" class="w-5 h-5 text-yellow-400" />
              <MoonIcon v-else class="w-5 h-5 text-gray-600 dark:text-gray-400" />
            </button>

            <!-- User Dropdown -->
            <div class="relative">
              <button
                @click="showUserMenu = !showUserMenu"
                class="flex items-center space-x-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg px-3 py-2 transition-all duration-200"
              >
                <div :class="['w-8 h-8 rounded-full flex items-center justify-center', config.userAvatarClass]">
                  <span :class="['text-sm font-semibold bg-clip-text text-transparent', config.userAvatarTextClass]">
                    {{ userInitials }}
                  </span>
                </div>
                <div class="text-left hidden sm:block">
                  <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $page.props.auth?.user?.name }}</p>
                  <p :class="['text-xs', config.userRoleClass]">{{ config.userRoleLabel || '@' + $page.props.auth?.user?.login }}</p>
                </div>
                <ChevronDownIcon class="w-4 h-4 text-gray-400 dark:text-gray-500" />
              </button>

              <!-- User Dropdown Menu -->
              <div
                v-show="showUserMenu"
                @click="showUserMenu = false"
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 py-1 z-50"
              >
                <Link
                  v-for="item in config.userMenuItems"
                  :key="item.href"
                  :href="item.href"
                  :method="item.method || 'get'"
                  :as="item.as || 'a'"
                  :class="[
                    'block px-4 py-2 text-sm transition-colors',
                    item.class || 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                  ]"
                >
                  {{ translateMenuLabel(item.label) }}
                </Link>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main :class="config.mainClass">
        <div :class="config.contentClass">
          <slot />
        </div>
      </main>
    </div>

    <!-- Feedback Widget -->
    <FeedbackWidget v-if="config.showFeedbackWidget" />

    <!-- Upgrade Modal (global - subscription limit/feature errors) -->
    <UpgradeModal />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, h } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import NavLink from '@/components/NavLink.vue';
import FeedbackWidget from '@/components/FeedbackWidget.vue';
import NotificationDropdown from '@/components/NotificationDropdown.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import UpgradeModal from '@/components/UpgradeModal.vue';
import { useI18n } from '@/i18n';
import axios from 'axios';

// i18n - get translations reactive reference
const { t, translations } = useI18n();
import {
  Bars3Icon as MenuIcon,
  ChevronDownIcon,
  CheckCircleIcon,
  PlusIcon,
  SunIcon,
  MoonIcon,
} from '@heroicons/vue/24/outline';

// CSRF token refresh utility
const refreshCsrfToken = async () => {
  try {
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    if (match) {
      const token = decodeURIComponent(match[1]);
      axios.defaults.headers.common['X-XSRF-TOKEN'] = token;
      window.axios.defaults.headers.common['X-XSRF-TOKEN'] = token;
      let meta = document.head.querySelector('meta[name="csrf-token"]');
      if (meta) meta.content = token;
    }
    return true;
  } catch (e) {
    console.error('CSRF refresh failed:', e);
    return false;
  }
};

const props = defineProps({
  title: {
    type: String,
    default: 'Bosh sahifa',
  },
  config: {
    type: Object,
    required: true,
  },
  quickStats: {
    type: Array,
    default: () => [],
  },
});

const page = usePage();

const showUserMenu = ref(false);
const showBusinessMenu = ref(false);
const showMobileMenu = ref(false);

// Dark mode
const isDarkMode = ref(false);

const initDarkMode = () => {
  const stored = localStorage.getItem('darkMode');
  if (stored !== null) {
    isDarkMode.value = stored === 'true';
  } else {
    isDarkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
  }
  applyDarkMode();
};

const applyDarkMode = () => {
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }
};

const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value;
  localStorage.setItem('darkMode', isDarkMode.value.toString());
  applyDarkMode();
};

// Computed
const userInitials = computed(() => {
  const user = page.props.auth?.user;
  if (!user?.name) return '?';
  return user.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
});

const currentBusinessInitial = computed(() => {
  const business = page.props.currentBusiness;
  if (!business?.name) return '?';
  return business.name.charAt(0).toUpperCase();
});

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

// Translate label - uses labelKey if available, otherwise falls back to label
// Access translations.value to trigger reactivity
const translateLabel = (item) => {
  // Touch translations to make this reactive
  const _ = translations.value;
  if (item.labelKey) {
    const translated = t(item.labelKey);
    // If translation returns the key itself, use fallback label
    return translated !== item.labelKey ? translated : item.label;
  }
  return item.label;
};

// Translate section title
const translateSectionTitle = (section) => {
  // Touch translations to make this reactive
  const _ = translations.value;
  if (section.titleKey) {
    const translated = t(section.titleKey);
    return translated !== section.titleKey ? translated : section.title;
  }
  return section.title;
};

// Menu label translations mapping
const menuLabelMap = {
  'Sozlamalar': 'layout.settings',
  'Chiqish': 'layout.logout',
  'Profil': 'common.profile',
  'Biznes Paneliga': 'layout.to_business_panel',
};

// Translate user menu labels
const translateMenuLabel = (label) => {
  // Touch translations to make this reactive
  const _ = translations.value;
  const key = menuLabelMap[label];
  if (key) {
    const translated = t(key);
    return translated !== key ? translated : label;
  }
  return label;
};

// Close dropdowns
const closeDropdowns = (e) => {
  if (!e.target.closest('.relative')) {
    showUserMenu.value = false;
    showBusinessMenu.value = false;
  }
};

// Periodic CSRF refresh interval (every 10 minutes)
let csrfRefreshInterval = null;

onMounted(async () => {
  document.addEventListener('click', closeDropdowns);
  initDarkMode();

  // Refresh CSRF token on mount to ensure fresh state
  await refreshCsrfToken();

  // Setup periodic CSRF refresh (every 10 minutes)
  csrfRefreshInterval = setInterval(refreshCsrfToken, 10 * 60 * 1000);
});

onUnmounted(() => {
  document.removeEventListener('click', closeDropdowns);
  if (csrfRefreshInterval) {
    clearInterval(csrfRefreshInterval);
  }
});
</script>
