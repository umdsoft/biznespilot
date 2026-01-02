<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Sidebar -->
    <aside class="fixed inset-y-0 left-0 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 z-30 shadow-sm">
      <!-- Logo -->
      <div class="h-16 flex items-center px-6 border-b border-gray-200 dark:border-gray-700">
        <Link href="/business" class="flex items-center space-x-3">
          <div class="w-9 h-9 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <span class="text-lg font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
            BiznesPilot AI
          </span>
        </Link>
      </div>

      <!-- Navigation -->
      <nav class="p-4 space-y-1">
        <NavLink href="/business" :active="$page.url === '/business' || $page.url === '/business/'">
          <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          Dashboard
        </NavLink>

        <NavLink href="/business/dream-buyer" :active="$page.url.startsWith('/business/dream-buyer')">
          <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          Ideal Mijoz
        </NavLink>

        <NavLink href="/business/marketing" :active="$page.url.startsWith('/business/marketing') && !$page.url.startsWith('/business/marketing/campaigns')">
          <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
          </svg>
          Marketing
        </NavLink>

        <NavLink href="/business/marketing/content" :active="$page.url.startsWith('/business/marketing/content')">
          <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          Kontent Reja
        </NavLink>

        <NavLink href="/business/inbox" :active="$page.url.startsWith('/business/inbox')">
          <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
          </svg>
          Yagona Inbox
        </NavLink>

        <NavLink href="/business/sales" :active="$page.url.startsWith('/business/sales')">
          <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
          </svg>
          Sotuv / Leadlar
        </NavLink>

        <NavLink href="/business/offers" :active="$page.url.startsWith('/business/offers')">
          <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
          </svg>
          Takliflar
        </NavLink>

        <NavLink href="/business/competitors" :active="$page.url.startsWith('/business/competitors')">
          <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          Raqobatchilar
        </NavLink>

        <!-- AI Section -->
        <div class="pt-3 mt-3 border-t border-gray-200 dark:border-gray-700">
          <p class="px-3 mb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">AI Yordamchilar</p>

          <NavLink href="/business/ai" :active="$page.url.startsWith('/business/ai') && !$page.url.startsWith('/business/ai/chat') && !$page.url.startsWith('/business/ai/insights') && !$page.url.startsWith('/business/ai/strategy')">
            <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            AI Tahlil
          </NavLink>

          <NavLink href="/business/target-analysis" :active="$page.url.startsWith('/business/target-analysis')">
            <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24" fill="currentColor">
              <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
            Facebook analiz
          </NavLink>

          <NavLink href="/business/instagram-analysis" :active="$page.url.startsWith('/business/instagram-analysis')">
            <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
            </svg>
            Instagram Tahlili
          </NavLink>
        </div>

        <!-- Analytics & Reports -->
        <div class="pt-3 mt-3 border-t border-gray-200 dark:border-gray-700">
          <p class="px-3 mb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tahlillar</p>

          <NavLink href="/business/analytics" :active="$page.url.startsWith('/business/analytics')">
            <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Sotuv Tahlili
          </NavLink>

          <NavLink href="/business/reports" :active="$page.url.startsWith('/business/reports')">
            <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Hisobotlar
          </NavLink>

          <NavLink href="/business/kpi" :active="$page.url.startsWith('/business/kpi')">
            <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            KPI Reja
          </NavLink>
        </div>

        <!-- Settings -->
        <div class="pt-3 mt-3 border-t border-gray-200 dark:border-gray-700">
          <NavLink href="/business/settings" :active="$page.url.startsWith('/business/settings')">
            <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Sozlamalar
          </NavLink>
        </div>
      </nav>
    </aside>

    <!-- Main Content -->
    <div class="ml-64">
      <!-- Header -->
      <header class="h-16 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-20 shadow-sm">
        <div class="h-full px-6 flex items-center justify-between">
          <div>
            <h1 class="text-lg font-semibold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-gray-100 dark:to-gray-300 bg-clip-text text-transparent">
              {{ title }}
            </h1>
          </div>

          <div class="flex items-center space-x-4">
            <!-- Business Selector Dropdown -->
            <div class="relative">
              <button
                @click="showBusinessMenu = !showBusinessMenu"
                class="flex items-center space-x-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl px-4 py-2 transition-all duration-200 border border-gray-200 dark:border-gray-600"
              >
                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                  <span class="text-xs font-bold text-white">
                    {{ currentBusinessInitial }}
                  </span>
                </div>
                <div class="text-left hidden sm:block">
                  <p class="text-sm font-medium text-gray-900 dark:text-gray-100 max-w-[150px] truncate">
                    {{ $page.props.currentBusiness?.name || 'Biznes tanlang' }}
                  </p>
                </div>
                <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <!-- Business Dropdown Menu -->
              <div
                v-show="showBusinessMenu"
                class="absolute right-0 mt-2 w-72 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 py-2 z-50"
              >
                <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-700">
                  <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bizneslaringiz</p>
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
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center mr-3"
                      :class="business.id === $page.props.currentBusiness?.id ? 'bg-gradient-to-br from-blue-500 to-indigo-600' : 'bg-gray-100 dark:bg-gray-700'"
                    >
                      <span class="text-sm font-bold" :class="business.id === $page.props.currentBusiness?.id ? 'text-white' : 'text-gray-600 dark:text-gray-300'">
                        {{ business.name?.charAt(0)?.toUpperCase() || '?' }}
                      </span>
                    </div>
                    <div class="flex-1 text-left">
                      <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ business.name }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">{{ business.category }}</p>
                    </div>
                    <svg v-if="business.id === $page.props.currentBusiness?.id" class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                  </Link>
                </div>

                <div class="border-t border-gray-100 dark:border-gray-700 mt-2 pt-2 px-3">
                  <Link
                    href="/new-business"
                    @click="showBusinessMenu = false"
                    class="flex items-center w-full px-3 py-2.5 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                  >
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span class="text-sm font-medium">Yangi biznes yaratish</span>
                  </Link>
                </div>
              </div>
            </div>

            <!-- Dark Mode Toggle -->
            <button
              @click="toggleDarkMode"
              class="flex items-center justify-center w-10 h-10 rounded-xl border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200"
              :title="isDarkMode ? 'Yorug\' rejimga o\'tish' : 'Qorong\'i rejimga o\'tish'"
            >
              <!-- Sun icon (shown in dark mode) -->
              <svg
                v-if="isDarkMode"
                class="w-5 h-5 text-yellow-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                />
              </svg>
              <!-- Moon icon (shown in light mode) -->
              <svg
                v-else
                class="w-5 h-5 text-gray-600 dark:text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                />
              </svg>
            </button>

            <!-- User Dropdown -->
            <div class="relative">
              <button
                @click="showUserMenu = !showUserMenu"
                class="flex items-center space-x-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg px-3 py-2 transition-all duration-200"
              >
                <div class="w-8 h-8 bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/50 dark:to-indigo-900/50 rounded-full flex items-center justify-center">
                  <span class="text-sm font-semibold bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400 bg-clip-text text-transparent">
                    {{ userInitials }}
                  </span>
                </div>
                <div class="text-left hidden sm:block">
                  <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $page.props.auth?.user?.name }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">@{{ $page.props.auth?.user?.login }}</p>
                </div>
                <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <!-- Dropdown Menu -->
              <div
                v-show="showUserMenu"
                @click="showUserMenu = false"
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 py-1 z-50"
              >
                <Link
                  href="/business/settings"
                  class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                >
                  Sozlamalar
                </Link>
                <Link
                  href="/logout"
                  method="post"
                  as="button"
                  class="block w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors"
                >
                  Chiqish
                </Link>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="p-6">
        <div class="max-w-screen-2xl mx-auto">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import NavLink from '@/components/NavLink.vue';

defineProps({
  title: {
    type: String,
    default: 'Dashboard',
  },
});

const page = usePage();

const showUserMenu = ref(false);
const showBusinessMenu = ref(false);

// Dark mode state
const isDarkMode = ref(false);

// Initialize dark mode from localStorage or system preference
const initDarkMode = () => {
  const stored = localStorage.getItem('darkMode');
  if (stored !== null) {
    isDarkMode.value = stored === 'true';
  } else {
    // Check system preference
    isDarkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
  }
  applyDarkMode();
};

// Apply dark mode to document
const applyDarkMode = () => {
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }
};

// Toggle dark mode
const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value;
  localStorage.setItem('darkMode', isDarkMode.value.toString());
  applyDarkMode();
};

const userInitials = computed(() => {
  const user = page.props.auth?.user;
  if (!user?.name) return '?';
  return user.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
});

const currentBusinessInitial = computed(() => {
  const business = page.props.currentBusiness;
  if (!business?.name) return '?';
  return business.name.charAt(0).toUpperCase();
});

// Close dropdowns when clicking outside
const closeDropdowns = (e) => {
  if (!e.target.closest('.relative')) {
    showUserMenu.value = false;
    showBusinessMenu.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', closeDropdowns);
  initDarkMode();
});

onUnmounted(() => {
  document.removeEventListener('click', closeDropdowns);
});
</script>
