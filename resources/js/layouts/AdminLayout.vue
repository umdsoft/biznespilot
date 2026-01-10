<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-red-50/20 to-slate-50">
    <!-- Sidebar -->
    <aside class="fixed inset-y-0 left-0 w-64 bg-white/80 backdrop-blur-xl border-r border-red-200/50 z-30 shadow-lg">
      <!-- Logo -->
      <div class="h-16 flex items-center px-6 border-b border-red-200/50 bg-gradient-to-r from-red-50/50 to-transparent">
        <Link href="/dashboard" class="flex items-center space-x-3">
          <div class="w-9 h-9 bg-gradient-to-br from-red-600 to-rose-600 rounded-xl flex items-center justify-center shadow-lg">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
          <span class="text-lg font-bold bg-gradient-to-r from-red-600 to-rose-600 bg-clip-text text-transparent">
            Admin Panel
          </span>
        </Link>
      </div>

      <!-- Admin Badge -->
      <div class="px-4 py-3 bg-gradient-to-r from-red-50 to-rose-50 border-b border-red-200/50">
        <div class="flex items-center space-x-2">
          <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
          <span class="text-xs font-semibold text-red-700 uppercase tracking-wider">
            Platform Administrator
          </span>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="p-4 space-y-1">
        <!-- Main Dashboard -->
        <NavLink href="/dashboard" :active="$page.url === '/dashboard' || $page.url === '/dashboard/'">
          <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          Dashboard
        </NavLink>

        <!-- Platform Management -->
        <div class="pt-3 mt-3 border-t border-red-200/50">
          <p class="px-3 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
            Platform Boshqaruv
          </p>

          <NavLink href="/dashboard/businesses" :active="$page.url.startsWith('/dashboard/businesses')">
            <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            Bizneslar
          </NavLink>

          <NavLink href="/dashboard/users" :active="$page.url.startsWith('/dashboard/users')">
            <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            Foydalanuvchilar
          </NavLink>

          <NavLink href="/dashboard/feedback" :active="$page.url.startsWith('/dashboard/feedback')">
            <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
            Fikr-mulohazalar
          </NavLink>

          <NavLink href="/dashboard/notifications" :active="$page.url.startsWith('/dashboard/notifications')">
            <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            Bildirishnomalar
          </NavLink>

          <NavLink href="/dashboard/subscriptions" :active="$page.url.startsWith('/dashboard/subscriptions')">
            <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            Obunalar
          </NavLink>
        </div>

        <!-- Analytics & Monitoring -->
        <div class="pt-3 mt-3 border-t border-red-200/50">
          <p class="px-3 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
            Monitoring
          </p>

          <NavLink href="/dashboard/analytics" :active="$page.url.startsWith('/dashboard/analytics')">
            <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Analytics
          </NavLink>

          <NavLink href="/dashboard/system-health" :active="$page.url.startsWith('/dashboard/system-health')">
            <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Tizim Salomatligi
          </NavLink>

          <NavLink href="/dashboard/activity-logs" :active="$page.url.startsWith('/dashboard/activity-logs')">
            <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Activity Logs
          </NavLink>
        </div>

        <!-- System Settings -->
        <div class="pt-3 mt-3 border-t border-red-200/50">
          <p class="px-3 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
            Tizim
          </p>

          <NavLink href="/dashboard/settings" :active="$page.url.startsWith('/dashboard/settings')">
            <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Sozlamalar
          </NavLink>
        </div>

        <!-- Back to Business Panel -->
        <div class="pt-3 mt-3 border-t border-blue-200/50">
          <NavLink href="/business">
            <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span class="font-medium text-blue-600">Biznes Paneliga</span>
          </NavLink>
        </div>
      </nav>
    </aside>

    <!-- Main Content -->
    <div class="ml-64">
      <!-- Header -->
      <header class="h-16 bg-white/80 backdrop-blur-xl border-b border-red-200/50 sticky top-0 z-20 shadow-sm">
        <div class="h-full px-6 flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-gradient-to-br from-red-100 to-rose-100 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <h1 class="text-lg font-semibold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
              {{ title }}
            </h1>
          </div>

          <div class="flex items-center space-x-4">
            <!-- Admin Badge -->
            <div class="hidden sm:flex items-center px-3 py-1.5 bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 rounded-lg">
              <svg class="w-4 h-4 text-red-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              <span class="text-xs font-semibold text-red-700">Administrator</span>
            </div>

            <!-- User Dropdown -->
            <div class="relative">
              <button
                @click="showUserMenu = !showUserMenu"
                class="flex items-center space-x-3 hover:bg-gray-50 rounded-lg px-3 py-2 transition-all duration-200"
              >
                <div class="w-8 h-8 bg-gradient-to-br from-red-100 to-rose-100 rounded-full flex items-center justify-center">
                  <span class="text-sm font-semibold bg-gradient-to-r from-red-600 to-rose-600 bg-clip-text text-transparent">
                    {{ userInitials }}
                  </span>
                </div>
                <div class="text-left hidden lg:block">
                  <p class="text-sm font-medium text-gray-900">{{ $page.props.auth?.user?.name }}</p>
                  <p class="text-xs text-red-600 font-medium">Admin</p>
                </div>
                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <!-- Dropdown Menu -->
              <div
                v-show="showUserMenu"
                @click="showUserMenu = false"
                class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-200/50 py-1 z-50"
              >
                <Link
                  href="/dashboard/settings"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                >
                  Sozlamalar
                </Link>
                <Link
                  href="/business"
                  class="block px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 transition-colors"
                >
                  Biznes Paneliga
                </Link>
                <div class="border-t border-gray-200 my-1"></div>
                <Link
                  href="/logout"
                  method="post"
                  as="button"
                  class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
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
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import NavLink from '@/components/NavLink.vue';

defineProps({
  title: {
    type: String,
    default: 'Admin Dashboard',
  },
});

const page = usePage();

const showUserMenu = ref(false);

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
</script>
