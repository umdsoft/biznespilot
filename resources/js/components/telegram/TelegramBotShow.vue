<template>
  <div class="space-y-6">
    <!-- Hero Section with Gradient -->
    <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 dark:from-blue-800 dark:via-blue-900 dark:to-indigo-950 rounded-2xl p-6 md:p-8">
      <!-- Background Pattern -->
      <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
          <defs>
            <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
              <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
            </pattern>
          </defs>
          <rect width="100" height="100" fill="url(#grid)"/>
        </svg>
      </div>

      <!-- Content -->
      <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center">
          <Link
            :href="getRoute('telegram-funnels.index')"
            class="text-white/70 hover:text-white mr-4 transition-colors"
          >
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </Link>
          <div class="w-16 h-16 md:w-20 md:h-20 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mr-4 md:mr-6 shadow-lg">
            <svg class="w-10 h-10 md:w-12 md:h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
            </svg>
          </div>
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-white">{{ bot.first_name }}</h1>
            <p class="text-blue-100 text-lg">@{{ bot.username }}</p>
            <div class="flex items-center mt-2 gap-2">
              <span :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                bot.is_active
                  ? 'bg-green-400/20 text-green-100'
                  : 'bg-red-400/20 text-red-100'
              ]">
                <span :class="['w-1.5 h-1.5 rounded-full mr-1.5', bot.is_active ? 'bg-green-400' : 'bg-red-400']"></span>
                {{ bot.is_active ? 'Faol' : 'Nofaol' }}
              </span>
              <!-- Webhook holati -->
              <span v-if="bot.is_verified" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-400/20 text-green-100">
                <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Webhook ulangan
              </span>
              <button
                v-else
                @click="setupWebhook"
                :disabled="isSettingUpWebhook"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-400/20 text-yellow-100 hover:bg-yellow-400/30 transition-colors cursor-pointer"
              >
                <svg v-if="isSettingUpWebhook" class="animate-spin w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
                {{ isSettingUpWebhook ? 'Ulanmoqda...' : 'Webhookni ulash' }}
              </button>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3 mt-4 md:mt-0">
          <button
            @click="toggleActive"
            :class="[
              'px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 flex items-center gap-2',
              bot.is_active
                ? 'bg-white/10 hover:bg-white/20 text-white border border-white/20'
                : 'bg-green-500 hover:bg-green-600 text-white'
            ]"
          >
            <svg v-if="bot.is_active" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ bot.is_active ? 'To\'xtatish' : 'Yoqish' }}
          </button>
          <button
            @click="deleteBot"
            class="px-4 py-2.5 bg-red-500/80 hover:bg-red-600 text-white text-sm font-medium rounded-xl transition-all duration-200 flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            O'chirish
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg transition-all duration-200 group">
        <div class="flex items-center justify-between mb-2">
          <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
            </svg>
          </div>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{{ bot.users_count }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Foydalanuvchilar</p>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg transition-all duration-200 group">
        <div class="flex items-center justify-between mb-2">
          <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{{ bot.funnels_count }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Funnellar</p>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg transition-all duration-200 group">
        <div class="flex items-center justify-between mb-2">
          <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ bot.active_funnels_count }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Faol Funnellar</p>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg transition-all duration-200 group">
        <div class="flex items-center justify-between mb-2">
          <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
          </div>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{{ bot.triggers_count }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Triggerlar</p>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg transition-all duration-200 group">
        <div class="flex items-center justify-between mb-2">
          <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </div>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{{ bot.conversations_count }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Suhbatlar</p>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg transition-all duration-200 group">
        <div class="flex items-center justify-between mb-2">
          <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
            </svg>
          </div>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{{ bot.broadcasts_count }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Broadcastlar</p>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
      <Link
        :href="getRoute('telegram-funnels.funnels.index', bot.id)"
        class="group relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-5 text-white hover:shadow-xl transition-all duration-300 hover:-translate-y-1"
      >
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
        <div class="relative">
          <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold mb-1">Funnellar</h3>
          <p class="text-green-100 text-sm">Avtomatik suhbat oqimlarini yaratish va boshqarish</p>
          <div class="mt-3 flex items-center text-sm font-medium">
            <span>Ochish</span>
            <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </div>
      </Link>

      <Link
        :href="getRoute('telegram-funnels.broadcasts.index', bot.id)"
        class="group relative overflow-hidden bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl p-5 text-white hover:shadow-xl transition-all duration-300 hover:-translate-y-1"
      >
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
        <div class="relative">
          <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold mb-1">Broadcast</h3>
          <p class="text-purple-100 text-sm">Barcha foydalanuvchilarga ommaviy xabar yuborish</p>
          <div class="mt-3 flex items-center text-sm font-medium">
            <span>Ochish</span>
            <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </div>
      </Link>

      <Link
        :href="getRoute('telegram-funnels.users.index', bot.id)"
        class="group relative overflow-hidden bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl p-5 text-white hover:shadow-xl transition-all duration-300 hover:-translate-y-1"
      >
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
        <div class="relative">
          <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold mb-1">Foydalanuvchilar</h3>
          <p class="text-blue-100 text-sm">Bot obunachilari ro'yxati va boshqaruvi</p>
          <div class="mt-3 flex items-center text-sm font-medium">
            <span>Ochish</span>
            <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </div>
      </Link>

      <Link
        :href="getRoute('telegram-funnels.conversations.index', bot.id)"
        class="group relative overflow-hidden bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl p-5 text-white hover:shadow-xl transition-all duration-300 hover:-translate-y-1"
      >
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
        <div class="relative">
          <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold mb-1">Suhbatlar</h3>
          <p class="text-orange-100 text-sm">Live chat va operator handoff boshqaruvi</p>
          <div class="mt-3 flex items-center text-sm font-medium">
            <span>Ochish</span>
            <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </div>
      </Link>

      <Link
        :href="getRoute('telegram-funnels.triggers.index', bot.id)"
        class="group relative overflow-hidden bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl p-5 text-white hover:shadow-xl transition-all duration-300 hover:-translate-y-1"
      >
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
        <div class="relative">
          <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold mb-1">Triggerlar</h3>
          <p class="text-yellow-100 text-sm">Buyruqlar va kalit so'zlarga avtomatik javob</p>
          <div class="mt-3 flex items-center text-sm font-medium">
            <span>Ochish</span>
            <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </div>
      </Link>
    </div>

    <!-- Stats & Settings Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Recent Stats Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            So'nggi 7 kun statistikasi
          </h3>
        </div>

        <div v-if="recentStats.length > 0" class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
              <tr>
                <th class="text-left py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sana</th>
                <th class="text-right py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Yangi</th>
                <th class="text-right py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kiruvchi</th>
                <th class="text-right py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Chiquvchi</th>
                <th class="text-right py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lidlar</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="stat in recentStats" :key="stat.date" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="py-3 px-4 text-sm font-medium text-gray-900 dark:text-white">{{ stat.date }}</td>
                <td class="py-3 px-4 text-sm text-right">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                    +{{ stat.new_users }}
                  </span>
                </td>
                <td class="py-3 px-4 text-sm text-right text-gray-900 dark:text-white">{{ stat.messages_in }}</td>
                <td class="py-3 px-4 text-sm text-right text-gray-900 dark:text-white">{{ stat.messages_out }}</td>
                <td class="py-3 px-4 text-sm text-right">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400">
                    {{ stat.leads_captured }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="p-8 text-center">
          <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
          <p class="text-gray-500 dark:text-gray-400 font-medium">Hali statistika yo'q</p>
          <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Bot ishlay boshlaganda ko'rinadi</p>
        </div>
      </div>

      <!-- Bot Settings Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Bot Sozlamalari
          </h3>
        </div>

        <div class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Salomlash xabari</label>
            <textarea
              v-model="settings.welcome_message"
              rows="2"
              class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-200"
              placeholder="Assalomu alaykum! Botimizga xush kelibsiz!"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tushunmadim xabari</label>
            <textarea
              v-model="settings.fallback_message"
              rows="2"
              class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-200"
              placeholder="Kechirasiz, tushunmadim. Iltimos qaytadan urinib ko'ring."
            ></textarea>
          </div>

          <!-- Default Funnel Selector -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Boshlang'ich Funnel
              <span class="text-xs text-gray-400 ml-1">(Start buyrug'ida avtomatik ishga tushadi)</span>
            </label>
            <select
              v-model="defaultFunnelId"
              class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-200"
            >
              <option value="">Tanlanmagan (avtomatik birinchi faol funnel)</option>
              <option v-for="funnel in funnels" :key="funnel.id" :value="funnel.id">
                {{ funnel.name }}
              </option>
            </select>
            <p v-if="funnels.length === 0" class="mt-2 text-xs text-yellow-600 dark:text-yellow-400">
              Faol funnel yo'q. Avval funnel yarating va faollashtiring.
            </p>
          </div>

          <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
            <div>
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Yozish ko'rsatish</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Xabar yuborilayotganda "typing..." ko'rsatish</p>
            </div>
            <button
              @click="settings.typing_action = !settings.typing_action"
              :class="[
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                settings.typing_action ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'
              ]"
            >
              <span
                :class="[
                  'inline-block h-4 w-4 transform rounded-full bg-white shadow-lg transition-transform',
                  settings.typing_action ? 'translate-x-6' : 'translate-x-1'
                ]"
              ></span>
            </button>
          </div>

          <button
            @click="saveSettings"
            :disabled="isSavingSettings"
            class="w-full py-3 px-4 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white font-medium rounded-xl transition-all duration-200 flex items-center justify-center gap-2"
          >
            <svg v-if="isSavingSettings" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ isSavingSettings ? 'Saqlanmoqda...' : 'Sozlamalarni Saqlash' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  bot: Object,
  funnels: {
    type: Array,
    default: () => []
  },
  recentStats: {
    type: Array,
    default: () => []
  },
  panelType: {
    type: String,
    required: true,
    validator: (value) => ['business', 'marketing'].includes(value),
  },
})

// Route helpers based on panel type
const getRoute = (name, params = null) => {
  const prefix = props.panelType === 'business' ? 'business.' : 'marketing.';
  return params ? route(prefix + name, params) : route(prefix + name);
};

const settings = reactive({
  welcome_message: props.bot.settings?.welcome_message || 'Assalomu alaykum!',
  fallback_message: props.bot.settings?.fallback_message || 'Tushunmadim, iltimos qaytadan urinib ko\'ring.',
  typing_action: props.bot.settings?.typing_action ?? true
})

const defaultFunnelId = ref(props.bot.default_funnel_id || '')

const isSettingUpWebhook = ref(false)
const isSavingSettings = ref(false)

const setupWebhook = async () => {
  isSettingUpWebhook.value = true

  try {
    const response = await axios.post(getRoute('telegram-funnels.setup-webhook', props.bot.id))

    if (response.data.success) {
      router.reload()
    } else {
      alert(response.data.error || 'Webhook o\'rnatishda xatolik yuz berdi')
    }
  } catch (error) {
    if (error.response?.status === 419) {
      alert('Sessiya tugagan. Sahifani yangilang.')
    } else if (error.response?.data?.error) {
      alert(error.response.data.error)
    } else {
      alert('Webhook o\'rnatishda xatolik yuz berdi')
    }
  } finally {
    isSettingUpWebhook.value = false
  }
}

const saveSettings = async () => {
  isSavingSettings.value = true
  try {
    const response = await axios.put(getRoute('telegram-funnels.update', props.bot.id), {
      settings,
      default_funnel_id: defaultFunnelId.value || null
    })
    if (!response.data.success) {
      alert(response.data.message || 'Xatolik yuz berdi')
    }
  } catch (error) {
    if (error.response?.status === 419) {
      alert('Sessiya tugagan. Sahifani yangilang.')
    } else {
      alert('Server bilan bog\'lanishda xatolik')
    }
  } finally {
    isSavingSettings.value = false
  }
}

const toggleActive = async () => {
  if (confirm(props.bot.is_active ? 'Botni o\'chirishni xohlaysizmi?' : 'Botni yoqishni xohlaysizmi?')) {
    try {
      await axios.post(getRoute('telegram-funnels.toggle-active', props.bot.id))
      router.reload()
    } catch (error) {
      if (error.response?.status === 419) {
        alert('Sessiya tugagan. Sahifani yangilang.')
      }
    }
  }
}

const deleteBot = async () => {
  if (confirm('Botni o\'chirishni xohlaysizmi? Bu amalni ortga qaytarib bo\'lmaydi!')) {
    try {
      await axios.delete(getRoute('telegram-funnels.destroy', props.bot.id))
      router.visit(getRoute('telegram-funnels.index'))
    } catch (error) {
      if (error.response?.status === 419) {
        alert('Sessiya tugagan. Sahifani yangilang.')
      }
    }
  }
}
</script>
