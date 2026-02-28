<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-lg bg-emerald-600 flex items-center justify-center">
          <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
          </svg>
        </div>
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ t('telegram.bots.title') }}</h1>
          <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('telegram.bots.subtitle') }}</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <!-- Bot limit badge -->
        <span v-if="!botLimit.unlimited" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg border"
          :class="botLimit.can_add
            ? 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400'
            : 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-400'"
        >
          {{ botLimit.current }}/{{ botLimit.max }} bot
        </span>
        <a
          href="https://t.me/BotFather"
          target="_blank"
          class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
        >
          <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
          </svg>
          BotFather
        </a>
        <Link
          v-if="botLimit.can_add"
          :href="getRoute('store.setup.wizard')"
          class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors shadow-sm"
        >
          <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          {{ t('telegram.bots.add_new_bot') }}
        </Link>
        <Link
          v-else
          :href="getRoute('subscription.index')"
          class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-colors shadow-sm"
        >
          <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
          </svg>
          Tarif oshirish
        </Link>
      </div>
    </div>

    <!-- Stats Grid -->
    <div v-if="bots.length > 0" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ bots.length }}</p>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('telegram.bots.bots') }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ totalUsers }}</p>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('telegram.bots.users') }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
            <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ totalFunnels }}</p>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('telegram.bots.funnels') }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ totalConversations }}</p>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('telegram.bots.conversations') }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="bots.length === 0" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
      <div class="p-12 text-center">
        <div class="w-20 h-20 mx-auto mb-6 bg-emerald-100 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center">
          <svg class="w-10 h-10 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
          </svg>
        </div>

        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">{{ t('telegram.bots.no_bots') }}</h3>
        <p class="text-slate-500 dark:text-slate-400 max-w-md mx-auto mb-6">
          {{ t('telegram.bots.no_bots_description') }}
        </p>

        <Link
          :href="getRoute('store.setup.wizard')"
          class="inline-flex items-center px-6 py-3 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors shadow-sm"
        >
          <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          {{ t('telegram.bots.add_first_bot') }}
        </Link>
      </div>

      <!-- Features Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 border-t border-slate-200 dark:border-slate-700">
        <div class="p-6 border-b md:border-b-0 md:border-r border-slate-200 dark:border-slate-700">
          <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <h4 class="font-semibold text-slate-900 dark:text-white mb-1 text-sm">{{ t('telegram.bots.feature1_title') }}</h4>
          <p class="text-slate-500 dark:text-slate-400 text-xs leading-relaxed">{{ t('telegram.bots.feature1_desc') }}</p>
        </div>
        <div class="p-6 border-b md:border-b-0 md:border-r border-slate-200 dark:border-slate-700">
          <div class="w-10 h-10 bg-violet-100 dark:bg-violet-900/30 rounded-lg flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
          <h4 class="font-semibold text-slate-900 dark:text-white mb-1 text-sm">{{ t('telegram.bots.feature2_title') }}</h4>
          <p class="text-slate-500 dark:text-slate-400 text-xs leading-relaxed">{{ t('telegram.bots.feature2_desc') }}</p>
        </div>
        <div class="p-6">
          <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
            </svg>
          </div>
          <h4 class="font-semibold text-slate-900 dark:text-white mb-1 text-sm">{{ t('telegram.bots.feature3_title') }}</h4>
          <p class="text-slate-500 dark:text-slate-400 text-xs leading-relaxed">{{ t('telegram.bots.feature3_desc') }}</p>
        </div>
      </div>
    </div>

    <!-- Bots List -->
    <div v-else>
      <!-- Toolbar -->
      <div class="flex items-center justify-between mb-4">
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="t('common.search') + '...'"
            class="w-64 pl-10 pr-4 py-2 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
          />
        </div>
        <!-- View Toggle -->
        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 rounded-lg p-1">
          <button
            @click="viewMode = 'table'"
            :class="[
              'p-2 rounded-md transition-colors',
              viewMode === 'table' ? 'bg-white dark:bg-slate-700 shadow-sm text-emerald-600 dark:text-emerald-400' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'
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
              'p-2 rounded-md transition-colors',
              viewMode === 'grid' ? 'bg-white dark:bg-slate-700 shadow-sm text-emerald-600 dark:text-emerald-400' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'
            ]"
            :title="t('telegram.bots.card_view')"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Table View -->
      <div v-if="viewMode === 'table'" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700">
                <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ t('telegram.bots.bot') }}</th>
                <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Turi</th>
                <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ t('telegram.bots.users') }}</th>
                <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ t('telegram.bots.funnels') }}</th>
                <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ t('telegram.bots.conversations') }}</th>
                <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ t('common.status') }}</th>
                <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ t('telegram.bots.webhook') }}</th>
                <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ t('common.actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
              <tr
                v-for="bot in filteredBots"
                :key="bot.id"
                class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="relative">
                      <div class="w-10 h-10 bg-emerald-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                        </svg>
                      </div>
                      <div v-if="bot.is_active" class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-emerald-500 rounded-full border-2 border-white dark:border-slate-800"></div>
                    </div>
                    <div class="min-w-0">
                      <p class="font-semibold text-sm text-slate-900 dark:text-white truncate">{{ bot.first_name }}</p>
                      <div class="flex items-center gap-1.5">
                        <p class="text-xs text-slate-500 dark:text-slate-400">@{{ bot.username }}</p>
                        <a :href="'https://t.me/' + bot.username" target="_blank" class="text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" @click.stop>
                          <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                          </svg>
                        </a>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4 text-center">
                  <BotTypeBadge
                    v-if="bot.store_type"
                    :type="bot.store_type"
                    :label="bot.store_type_label"
                    :color="bot.store_type_color"
                    :bg-color="bot.store_type_bg_color"
                    :icon="bot.store_type_icon"
                  />
                  <!-- Do'konsiz botlar uchun default ecommerce badge -->
                  <BotTypeBadge
                    v-else
                    type="ecommerce"
                    label="Online do'kon"
                    color="#2563EB"
                    bg-color="#DBEAFE"
                    icon="ShoppingBagIcon"
                  />
                </td>
                <td class="px-4 py-4 text-center">
                  <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-md bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300">{{ bot.users_count || 0 }}</span>
                </td>
                <td class="px-4 py-4 text-center">
                  <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-md bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300">{{ bot.funnels_count || 0 }}</span>
                </td>
                <td class="px-4 py-4 text-center">
                  <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-md bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300">{{ bot.conversations_count || 0 }}</span>
                </td>
                <td class="px-4 py-4 text-center">
                  <span :class="[
                    'inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full',
                    bot.is_active ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400'
                  ]">
                    <span :class="['w-1.5 h-1.5 rounded-full', bot.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                    {{ bot.is_active ? t('telegram.bots.active') : t('telegram.bots.inactive') }}
                  </span>
                </td>
                <td class="px-4 py-4 text-center">
                  <span :class="[
                    'inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full',
                    bot.is_verified ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400' : 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400'
                  ]">
                    {{ bot.is_verified ? t('telegram.bots.webhook_connected') : t('telegram.bots.webhook_not_connected') }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center justify-center gap-2">
                    <!-- Do'koni bor bot — dashboard ga o'tish -->
                    <Link
                      v-if="bot.has_store"
                      :href="route('business.store.select', bot.store_id)"
                      class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white rounded-lg transition-colors"
                      :style="{ backgroundColor: bot.store_type_color || '#2563EB' }"
                    >
                      <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                      </svg>
                      {{ bot.store_type_action || "Do'kon" }}
                    </Link>
                    <!-- Do'koni yo'q bot — setup wizard ga yo'naltirish -->
                    <Link
                      v-else
                      :href="getRoute('store.setup.wizard')"
                      class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
                    >
                      <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                      </svg>
                      Sozlash
                    </Link>
                    <Link :href="getRoute('telegram-funnels.show', bot.id)" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg transition-colors">
                      {{ t('telegram.bots.settings') }}
                    </Link>
                    <Link :href="getRoute('telegram-funnels.funnels.index', bot.id)" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors">
                      {{ t('telegram.bots.funnels') }}
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Table Footer -->
        <div class="px-6 py-3 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-700">
          <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            <p>{{ t('common.total') }} <span class="font-semibold text-slate-700 dark:text-slate-300">{{ filteredBots.length }}</span> {{ t('telegram.bots.bot_count') }}</p>
            <div class="flex items-center gap-4">
              <span class="flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                {{ bots.filter(b => b.is_active).length }} {{ t('telegram.bots.active_count') }}
              </span>
              <span class="flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                {{ bots.filter(b => b.is_verified).length }} {{ t('telegram.bots.webhook_connected_count') }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Grid View -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="bot in filteredBots"
          :key="bot.id"
          class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:border-slate-300 dark:hover:border-slate-600 transition-colors"
        >
          <!-- Card Header -->
          <div class="h-16 bg-emerald-600 relative">
            <div class="absolute top-3 right-3">
              <span :class="[
                'px-2 py-0.5 text-xs font-medium rounded-full',
                bot.is_active ? 'bg-white/20 text-white' : 'bg-black/20 text-white/70'
              ]">
                {{ bot.is_active ? t('telegram.bots.active') : t('telegram.bots.inactive') }}
              </span>
            </div>
          </div>

          <!-- Avatar -->
          <div class="relative px-5 -mt-7">
            <div class="w-14 h-14 bg-white dark:bg-slate-800 rounded-xl shadow-sm flex items-center justify-center border-2 border-white dark:border-slate-800">
              <div class="w-full h-full bg-emerald-600 rounded-lg flex items-center justify-center">
                <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                </svg>
              </div>
            </div>
          </div>

          <!-- Content -->
          <div class="p-5 pt-3">
            <div class="mb-4">
              <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ bot.first_name }}</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">@{{ bot.username }}</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-2 mb-4">
              <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-2.5 text-center">
                <p class="text-lg font-bold text-slate-900 dark:text-white">{{ bot.users_count || 0 }}</p>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ t('telegram.bots.users') }}</p>
              </div>
              <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-2.5 text-center">
                <p class="text-lg font-bold text-slate-900 dark:text-white">{{ bot.funnels_count || 0 }}</p>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ t('telegram.bots.funnels') }}</p>
              </div>
              <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-2.5 text-center">
                <p class="text-lg font-bold text-slate-900 dark:text-white">{{ bot.conversations_count || 0 }}</p>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ t('telegram.bots.conversations') }}</p>
              </div>
            </div>

            <!-- Webhook Status -->
            <div :class="[
              'flex items-center gap-2 mb-4 p-2.5 rounded-lg text-xs font-medium',
              bot.is_verified ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400' : 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400'
            ]">
              <span :class="['w-1.5 h-1.5 rounded-full', bot.is_verified ? 'bg-emerald-500' : 'bg-amber-500']"></span>
              {{ bot.is_verified ? t('telegram.bots.webhook_connected') : t('telegram.bots.webhook_not_connected') }}
            </div>

            <!-- Do'koni bor bot — dashboard ga o'tish -->
            <Link
              v-if="bot.has_store"
              :href="route('business.store.select', bot.store_id)"
              class="flex items-center gap-2 mb-3 p-2.5 rounded-lg text-xs font-semibold transition-colors"
              :style="{
                backgroundColor: (bot.store_type_bg_color || '#DBEAFE'),
                color: (bot.store_type_color || '#2563EB'),
              }"
            >
              <BotTypeBadge
                v-if="bot.store_type"
                :type="bot.store_type"
                :label="bot.store_type_label"
                :color="bot.store_type_color"
                :bg-color="'transparent'"
                :icon="bot.store_type_icon"
              />
              <span v-else>{{ bot.store_name || "Do'kon" }}</span>
              <span class="ml-auto text-[10px] opacity-70">Admin panel</span>
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </Link>
            <!-- Do'koni yo'q bot — setup wizard ga yo'naltirish -->
            <Link
              v-else
              :href="getRoute('store.setup.wizard')"
              class="flex items-center gap-2 mb-3 p-2.5 rounded-lg text-xs font-semibold transition-colors bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              <span>Do'kon sozlash</span>
              <svg class="w-3.5 h-3.5 ml-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </Link>

            <!-- Actions -->
            <div class="grid grid-cols-2 gap-2">
              <Link :href="getRoute('telegram-funnels.show', bot.id)" class="flex items-center justify-center px-3 py-2 text-xs font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg transition-colors">
                {{ t('telegram.bots.manage') }}
              </Link>
              <Link :href="getRoute('telegram-funnels.funnels.index', bot.id)" class="flex items-center justify-center px-3 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors">
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
import BotTypeBadge from '@/components/telegram/BotTypeBadge.vue'

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
  botLimit: {
    type: Object,
    default: () => ({ current: 0, max: 1, unlimited: false, can_add: true, plan_name: 'Trial' }),
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
