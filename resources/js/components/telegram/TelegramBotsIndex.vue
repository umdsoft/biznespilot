<template>
  <div class="space-y-8">
    <!-- Hero Section -->
    <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 rounded-2xl p-8 text-white">
      <div class="absolute inset-0 bg-grid-white/10"></div>
      <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-blue-400/20 rounded-full blur-3xl"></div>

      <div class="relative flex items-center justify-between">
        <div class="max-w-2xl">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
              <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
              </svg>
            </div>
            <div>
              <h1 class="text-3xl font-bold">{{ t('telegram.bots.title') }}</h1>
              <p class="text-blue-100 mt-1">{{ t('telegram.bots.subtitle') }}</p>
            </div>
          </div>

          <p class="text-blue-100 text-lg mb-6 max-w-xl">
            {{ t('telegram.bots.description') }}
          </p>

          <div class="flex items-center gap-4">
            <Link
              :href="getRoute('telegram-funnels.create')"
              class="inline-flex items-center px-6 py-3 bg-white text-blue-600 font-semibold rounded-xl hover:bg-blue-50 transition-all duration-200 shadow-lg shadow-blue-900/20"
            >
              <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              {{ t('telegram.bots.add_new_bot') }}
            </Link>
            <a
              href="https://t.me/BotFather"
              target="_blank"
              class="inline-flex items-center px-6 py-3 bg-white/10 backdrop-blur-sm text-white font-medium rounded-xl hover:bg-white/20 transition-all duration-200 border border-white/20"
            >
              <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
              </svg>
              BotFather
            </a>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="hidden lg:grid grid-cols-2 gap-4">
          <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 min-w-[140px]">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
              </div>
              <div>
                <p class="text-2xl font-bold">{{ bots.length }}</p>
                <p class="text-blue-200 text-sm">{{ t('telegram.bots.bots') }}</p>
              </div>
            </div>
          </div>
          <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 min-w-[140px]">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>
              <div>
                <p class="text-2xl font-bold">{{ totalUsers }}</p>
                <p class="text-blue-200 text-sm">{{ t('telegram.bots.users') }}</p>
              </div>
            </div>
          </div>
          <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 min-w-[140px]">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
              <div>
                <p class="text-2xl font-bold">{{ totalFunnels }}</p>
                <p class="text-blue-200 text-sm">{{ t('telegram.bots.funnels') }}</p>
              </div>
            </div>
          </div>
          <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 min-w-[140px]">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
              </div>
              <div>
                <p class="text-2xl font-bold">{{ totalConversations }}</p>
                <p class="text-blue-200 text-sm">{{ t('telegram.bots.conversations') }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="bots.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="p-12 text-center">
        <!-- Animated Bot Icon -->
        <div class="relative w-32 h-32 mx-auto mb-8">
          <div class="absolute inset-0 bg-blue-500/20 rounded-full animate-ping"></div>
          <div class="absolute inset-2 bg-blue-500/30 rounded-full animate-pulse"></div>
          <div class="relative w-32 h-32 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center shadow-2xl shadow-blue-500/30">
            <svg class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
            </svg>
          </div>
        </div>

        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">{{ t('telegram.bots.no_bots') }}</h3>
        <p class="text-gray-600 dark:text-gray-400 max-w-lg mx-auto mb-8 text-lg">
          {{ t('telegram.bots.no_bots_description') }}
        </p>

        <Link
          :href="getRoute('telegram-funnels.create')"
          class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 hover:-translate-y-0.5"
        >
          <svg class="w-6 h-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          {{ t('telegram.bots.add_first_bot') }}
        </Link>
      </div>

      <!-- Features Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 border-t border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
          <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ t('telegram.bots.feature1_title') }}</h4>
          <p class="text-gray-600 dark:text-gray-400 text-sm">{{ t('telegram.bots.feature1_desc') }}</p>
        </div>
        <div class="p-6 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
          <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
          <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ t('telegram.bots.feature2_title') }}</h4>
          <p class="text-gray-600 dark:text-gray-400 text-sm">{{ t('telegram.bots.feature2_desc') }}</p>
        </div>
        <div class="p-6">
          <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
            </svg>
          </div>
          <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ t('telegram.bots.feature3_title') }}</h4>
          <p class="text-gray-600 dark:text-gray-400 text-sm">{{ t('telegram.bots.feature3_desc') }}</p>
        </div>
      </div>
    </div>

    <!-- Bots Table -->
    <div v-else>
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ t('telegram.bots.your_bots') }}</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ t('telegram.bots.manage_bots') }}</p>
        </div>
        <div class="flex items-center gap-3">
          <!-- Search -->
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="t('common.search') + '...'"
              class="w-64 pl-10 pr-4 py-2 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm"
            />
          </div>
          <!-- View Toggle -->
          <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
            <button
              @click="viewMode = 'table'"
              :class="[
                'p-2 rounded-md transition-all',
                viewMode === 'table' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'
              ]"
              :title="t('telegram.bots.table_view')"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
              </svg>
            </button>
            <button
              @click="viewMode = 'grid'"
              :class="[
                'p-2 rounded-md transition-all',
                viewMode === 'grid' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'
              ]"
              :title="t('telegram.bots.card_view')"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Professional Table View -->
      <div v-if="viewMode === 'table'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-700/30 border-b border-gray-200 dark:border-gray-700">
                <th class="px-6 py-4 text-left">
                  <span class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ t('telegram.bots.bot') }}</span>
                </th>
                <th class="px-4 py-4 text-center">
                  <span class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ t('telegram.bots.users') }}</span>
                </th>
                <th class="px-4 py-4 text-center">
                  <span class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ t('telegram.bots.funnels') }}</span>
                </th>
                <th class="px-4 py-4 text-center">
                  <span class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ t('telegram.bots.conversations') }}</span>
                </th>
                <th class="px-4 py-4 text-center">
                  <span class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ t('common.status') }}</span>
                </th>
                <th class="px-4 py-4 text-center">
                  <span class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ t('telegram.bots.webhook') }}</span>
                </th>
                <th class="px-6 py-4 text-center">
                  <span class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ t('common.actions') }}</span>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
              <tr
                v-for="(bot, index) in filteredBots"
                :key="bot.id"
                class="group hover:bg-blue-50/50 dark:hover:bg-blue-900/10 transition-colors"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center gap-4">
                    <div class="relative">
                      <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-blue-500/20">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                        </svg>
                      </div>
                      <div v-if="bot.is_active" class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white dark:border-gray-800"></div>
                    </div>
                    <div class="min-w-0">
                      <div class="flex items-center gap-2">
                        <p class="font-semibold text-gray-900 dark:text-white truncate">{{ bot.first_name }}</p>
                        <span class="text-xs text-gray-400">#{{ index + 1 }}</span>
                      </div>
                      <div class="flex items-center gap-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">@{{ bot.username }}</p>
                        <a :href="'https://t.me/' + bot.username" target="_blank" class="text-blue-500 hover:text-blue-600" @click.stop>
                          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                          </svg>
                        </a>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4 text-center">
                  <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <span class="font-bold text-blue-700 dark:text-blue-300">{{ bot.users_count || 0 }}</span>
                  </div>
                </td>
                <td class="px-4 py-4 text-center">
                  <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <span class="font-bold text-purple-700 dark:text-purple-300">{{ bot.funnels_count || 0 }}</span>
                  </div>
                </td>
                <td class="px-4 py-4 text-center">
                  <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                    <span class="font-bold text-orange-700 dark:text-orange-300">{{ bot.conversations_count || 0 }}</span>
                  </div>
                </td>
                <td class="px-4 py-4 text-center">
                  <span :class="['inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full', bot.is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400']">
                    <span :class="['w-2 h-2 rounded-full', bot.is_active ? 'bg-green-500 animate-pulse' : 'bg-gray-400']"></span>
                    {{ bot.is_active ? t('telegram.bots.active') : t('telegram.bots.inactive') }}
                  </span>
                </td>
                <td class="px-4 py-4 text-center">
                  <span :class="['inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full', bot.is_verified ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400']">
                    {{ bot.is_verified ? t('telegram.bots.webhook_connected') : t('telegram.bots.webhook_not_connected') }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center justify-center gap-2">
                    <Link :href="getRoute('telegram-funnels.show', bot.id)" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                      {{ t('telegram.bots.settings') }}
                    </Link>
                    <Link :href="getRoute('telegram-funnels.funnels.index', bot.id)" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 rounded-lg shadow-md">
                      {{ t('telegram.bots.funnels') }}
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Table Footer -->
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/30 border-t border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between text-sm">
            <p class="text-gray-500 dark:text-gray-400">
              {{ t('common.total') }} <span class="font-semibold text-gray-700 dark:text-gray-300">{{ filteredBots.length }}</span> {{ t('telegram.bots.bot_count') }}
            </p>
            <div class="flex items-center gap-4 text-gray-500 dark:text-gray-400">
              <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                {{ bots.filter(b => b.is_active).length }} {{ t('telegram.bots.active_count') }}
              </span>
              <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                {{ bots.filter(b => b.is_verified).length }} {{ t('telegram.bots.webhook_connected_count') }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Grid View -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="bot in filteredBots"
          :key="bot.id"
          class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1"
        >
          <div class="relative h-24 bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-700 p-4">
            <div class="absolute top-4 right-4">
              <span :class="['px-3 py-1 text-xs font-semibold rounded-full backdrop-blur-sm', bot.is_active ? 'bg-green-500/20 text-green-100 border border-green-400/30' : 'bg-gray-500/20 text-gray-200 border border-gray-400/30']">
                {{ bot.is_active ? t('telegram.bots.active') : t('telegram.bots.inactive') }}
              </span>
            </div>
          </div>

          <div class="relative px-6 -mt-10">
            <div class="w-20 h-20 bg-white dark:bg-gray-800 rounded-2xl shadow-lg flex items-center justify-center border-4 border-white dark:border-gray-800">
              <div class="w-full h-full bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                </svg>
              </div>
            </div>
          </div>

          <div class="p-6 pt-4">
            <div class="mb-4">
              <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ bot.first_name }}</h3>
              <p class="text-gray-500 dark:text-gray-400">@{{ bot.username }}</p>
            </div>

            <div class="grid grid-cols-3 gap-3 mb-4">
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 text-center">
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ bot.users_count || 0 }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('telegram.bots.users') }}</p>
              </div>
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 text-center">
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ bot.funnels_count || 0 }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('telegram.bots.funnels') }}</p>
              </div>
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 text-center">
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ bot.conversations_count || 0 }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('telegram.bots.conversations') }}</p>
              </div>
            </div>

            <div class="flex items-center gap-2 mb-5 p-3 rounded-xl" :class="bot.is_verified ? 'bg-green-50 dark:bg-green-900/20' : 'bg-yellow-50 dark:bg-yellow-900/20'">
              <div :class="['w-2 h-2 rounded-full', bot.is_verified ? 'bg-green-500 animate-pulse' : 'bg-yellow-500']"></div>
              <span :class="['text-sm font-medium', bot.is_verified ? 'text-green-700 dark:text-green-400' : 'text-yellow-700 dark:text-yellow-400']">
                {{ bot.is_verified ? t('telegram.bots.webhook_connected') : t('telegram.bots.webhook_not_connected') }}
              </span>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <Link :href="getRoute('telegram-funnels.show', bot.id)" class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors">
                {{ t('telegram.bots.manage') }}
              </Link>
              <Link :href="getRoute('telegram-funnels.funnels.index', bot.id)" class="flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium rounded-xl transition-all shadow-lg shadow-blue-500/20">
                {{ t('telegram.bots.funnels') }}
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
  bots: {
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

const viewMode = ref('table')
const searchQuery = ref('')

const filteredBots = computed(() => {
  if (!searchQuery.value.trim()) {
    return props.bots
  }
  const query = searchQuery.value.toLowerCase().trim()
  return props.bots.filter(bot =>
    bot.first_name?.toLowerCase().includes(query) ||
    bot.username?.toLowerCase().includes(query)
  )
})

const totalUsers = computed(() => props.bots.reduce((sum, bot) => sum + (bot.users_count || 0), 0))
const totalFunnels = computed(() => props.bots.reduce((sum, bot) => sum + (bot.funnels_count || 0), 0))
const totalConversations = computed(() => props.bots.reduce((sum, bot) => sum + (bot.conversations_count || 0), 0))
</script>

<style scoped>
.bg-grid-white\/10 {
  background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
</style>
