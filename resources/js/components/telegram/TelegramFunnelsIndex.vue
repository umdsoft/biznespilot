<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <Link
          :href="getRoute('telegram-funnels.show', bot.id)"
          class="w-10 h-10 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl flex items-center justify-center transition-colors group"
        >
          <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </Link>
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Funnellar</h1>
          <p class="text-gray-500 dark:text-gray-400 flex items-center gap-2">
            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
            @{{ bot.username }}
          </p>
        </div>
      </div>
      <button
        @click="showCreateModal = true"
        class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-semibold rounded-xl transition-all shadow-lg shadow-blue-500/30 hover:shadow-xl"
      >
        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Yangi Funnel
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ funnels.length }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Jami funnellar</p>
          </div>
        </div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ activeFunnels }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Faol funnellar</p>
          </div>
        </div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ totalSteps }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Jami qadamlar</p>
          </div>
        </div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ bot.users_count || 0 }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Foydalanuvchilar</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="funnels.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="p-12 text-center">
        <div class="relative w-24 h-24 mx-auto mb-6">
          <div class="absolute inset-0 bg-green-500/20 rounded-full animate-ping"></div>
          <div class="relative w-24 h-24 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center shadow-xl shadow-green-500/30">
            <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
        </div>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Hozircha funnel yo'q</h3>
        <p class="text-gray-600 dark:text-gray-400 max-w-md mx-auto mb-6">
          Birinchi funnelni yarating va foydalanuvchilarni avtomatik suhbat orqali o'tkazing.
        </p>
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-semibold rounded-xl transition-all shadow-lg shadow-green-500/30"
        >
          <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Birinchi Funnelni Yaratish
        </button>
      </div>

      <!-- Tips -->
      <div class="grid grid-cols-1 md:grid-cols-3 border-t border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
          <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </div>
          <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Xush kelibsiz xabari</h4>
          <p class="text-sm text-gray-500 dark:text-gray-400">Yangi foydalanuvchilarni kutib oling</p>
        </div>
        <div class="p-6 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
          <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
          <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Ma'lumot yig'ish</h4>
          <p class="text-sm text-gray-500 dark:text-gray-400">Foydalanuvchilardan savollar so'rang</p>
        </div>
        <div class="p-6">
          <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
          </div>
          <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Konversiyani oshiring</h4>
          <p class="text-sm text-gray-500 dark:text-gray-400">Avtomatik savdo funnellari</p>
        </div>
      </div>
    </div>

    <!-- Funnels Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="funnel in funnels"
        :key="funnel.id"
        class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl hover:shadow-gray-200/50 dark:hover:shadow-gray-900/50 transition-all duration-300"
      >
        <!-- Card Header -->
        <div class="relative p-6 pb-4">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <div :class="['w-2 h-2 rounded-full', funnel.is_active ? 'bg-green-500 animate-pulse' : 'bg-gray-400']"></div>
                <span :class="['text-xs font-medium', funnel.is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400']">
                  {{ funnel.is_active ? 'Faol' : 'O\'chiq' }}
                </span>
              </div>
              <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                {{ funnel.name }}
              </h3>
              <p v-if="funnel.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                {{ funnel.description }}
              </p>
            </div>
            <div class="relative">
              <button
                @click="toggleMenu(funnel.id)"
                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                </svg>
              </button>
              <!-- Dropdown Menu -->
              <div
                v-if="openMenuId === funnel.id"
                class="absolute right-0 top-full mt-1 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-2 z-10"
              >
                <button
                  @click="duplicateFunnel(funnel); toggleMenu(null)"
                  class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                  Nusxalash
                </button>
                <button
                  @click="deleteFunnel(funnel); toggleMenu(null)"
                  class="w-full px-4 py-2 text-left text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  O'chirish
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Stats -->
        <div class="px-6 pb-4">
          <div class="flex items-center gap-4 text-sm">
            <div class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
              </svg>
              <span>{{ funnel.steps_count }} qadam</span>
            </div>
            <div class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <span>{{ funnel.users_count || 0 }}</span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="px-6 pb-6 flex items-center gap-3">
          <Link
            :href="getRoute('telegram-funnels.funnels.show', [bot.id, funnel.id])"
            class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-medium rounded-xl transition-all shadow-lg shadow-blue-500/20"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Tahrirlash
          </Link>
          <button
            @click="toggleFunnelActive(funnel)"
            :class="[
              'px-4 py-2.5 text-sm font-medium rounded-xl transition-colors',
              funnel.is_active
                ? 'bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/30 text-amber-600 dark:text-amber-400'
                : 'bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 text-green-600 dark:text-green-400'
            ]"
          >
            {{ funnel.is_active ? 'O\'chirish' : 'Yoqish' }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Create Modal -->
  <Teleport to="body">
    <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showCreateModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full shadow-2xl">
          <!-- Modal Header -->
          <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <h3 class="text-xl font-bold text-gray-900 dark:text-white">Yangi Funnel</h3>
              <button
                @click="showCreateModal = false"
                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Modal Body -->
          <form @submit.prevent="createFunnel" class="p-6 space-y-5">
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Nomi *</label>
              <input
                v-model="newFunnel.name"
                type="text"
                required
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white transition-all"
                placeholder="Masalan: Xush kelibsiz funnel"
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Tavsif</label>
              <textarea
                v-model="newFunnel.description"
                rows="3"
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white transition-all resize-none"
                placeholder="Funnel haqida qisqacha..."
              ></textarea>
            </div>

            <!-- Template Selection -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Shablon tanlang</label>
              <div class="grid grid-cols-2 gap-3 max-h-64 overflow-y-auto pr-1">
                <!-- Bo'sh -->
                <button
                  type="button"
                  @click="selectedTemplate = 'blank'"
                  :class="[
                    'p-4 rounded-xl border-2 text-left transition-all',
                    selectedTemplate === 'blank'
                      ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                      : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                  ]"
                >
                  <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center mb-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                  </div>
                  <p class="font-medium text-gray-900 dark:text-white text-sm">Bo'sh</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Noldan boshlang</p>
                </button>

                <!-- Lead Magnet -->
                <button
                  type="button"
                  @click="selectedTemplate = 'lead_magnet'"
                  :class="[
                    'p-4 rounded-xl border-2 text-left transition-all',
                    selectedTemplate === 'lead_magnet'
                      ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                      : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                  ]"
                >
                  <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mb-2">
                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                  </div>
                  <p class="font-medium text-gray-900 dark:text-white text-sm">Lead Magnet</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Bepul material + kontakt</p>
                </button>

                <!-- Konsultatsiya -->
                <button
                  type="button"
                  @click="selectedTemplate = 'consultation'"
                  :class="[
                    'p-4 rounded-xl border-2 text-left transition-all',
                    selectedTemplate === 'consultation'
                      ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                      : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                  ]"
                >
                  <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mb-2">
                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <p class="font-medium text-gray-900 dark:text-white text-sm">Konsultatsiya</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Bepul maslahat olish</p>
                </button>

                <!-- Quiz Funnel -->
                <button
                  type="button"
                  @click="selectedTemplate = 'quiz'"
                  :class="[
                    'p-4 rounded-xl border-2 text-left transition-all',
                    selectedTemplate === 'quiz'
                      ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                      : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                  ]"
                >
                  <div class="w-8 h-8 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center mb-2">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <p class="font-medium text-gray-900 dark:text-white text-sm">Quiz Funnel</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Savol-javob + segmentatsiya</p>
                </button>

                <!-- Subscribe Check -->
                <button
                  type="button"
                  @click="selectedTemplate = 'subscribe_gate'"
                  :class="[
                    'p-4 rounded-xl border-2 text-left transition-all',
                    selectedTemplate === 'subscribe_gate'
                      ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                      : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                  ]"
                >
                  <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mb-2">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                  </div>
                  <p class="font-medium text-gray-900 dark:text-white text-sm">Obuna Gate</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Kanalga obuna + bonus</p>
                </button>

                <!-- A/B Test Offer -->
                <button
                  type="button"
                  @click="selectedTemplate = 'ab_offer'"
                  :class="[
                    'p-4 rounded-xl border-2 text-left transition-all',
                    selectedTemplate === 'ab_offer'
                      ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                      : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                  ]"
                >
                  <div class="w-8 h-8 bg-pink-100 dark:bg-pink-900/30 rounded-lg flex items-center justify-center mb-2">
                    <svg class="w-4 h-4 text-pink-600 dark:text-pink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                  </div>
                  <p class="font-medium text-gray-900 dark:text-white text-sm">A/B Test</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Ikki taklif solishtiruv</p>
                </button>
              </div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
              <button
                type="button"
                @click="showCreateModal = false"
                class="px-5 py-2.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium rounded-xl transition-colors"
              >
                Bekor qilish
              </button>
              <button
                type="submit"
                :disabled="isCreating"
                class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 disabled:from-gray-400 disabled:to-gray-500 text-white font-semibold rounded-xl transition-all shadow-lg shadow-blue-500/30 disabled:shadow-none"
              >
                {{ isCreating ? 'Yaratilmoqda...' : 'Yaratish' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  bot: Object,
  funnels: {
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
  if (Array.isArray(params)) {
    return route(prefix + name, params);
  }
  return params ? route(prefix + name, params) : route(prefix + name);
};

const showCreateModal = ref(false)
const isCreating = ref(false)
const openMenuId = ref(null)
const selectedTemplate = ref('blank')
const newFunnel = reactive({
  name: '',
  description: ''
})

const activeFunnels = computed(() => props.funnels.filter(f => f.is_active).length)
const totalSteps = computed(() => props.funnels.reduce((sum, f) => sum + (f.steps_count || 0), 0))

const toggleMenu = (id) => {
  openMenuId.value = openMenuId.value === id ? null : id
}

const closeMenus = (e) => {
  if (!e.target.closest('.relative')) {
    openMenuId.value = null
  }
}

onMounted(() => {
  document.addEventListener('click', closeMenus)
})

onUnmounted(() => {
  document.removeEventListener('click', closeMenus)
})

const createFunnel = async () => {
  isCreating.value = true
  try {
    const response = await fetch(getRoute('telegram-funnels.funnels.store', props.bot.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        ...newFunnel,
        template: selectedTemplate.value
      })
    })
    const data = await response.json()
    if (data.success) {
      router.visit(getRoute('telegram-funnels.funnels.show', [props.bot.id, data.funnel.id]))
    }
  } finally {
    isCreating.value = false
  }
}

const toggleFunnelActive = async (funnel) => {
  await fetch(getRoute('telegram-funnels.funnels.toggle-active', [props.bot.id, funnel.id]), {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
  })
  router.reload()
}

const duplicateFunnel = async (funnel) => {
  await fetch(getRoute('telegram-funnels.funnels.duplicate', [props.bot.id, funnel.id]), {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
  })
  router.reload()
}

const deleteFunnel = async (funnel) => {
  if (confirm(`"${funnel.name}" funnelini o'chirishni xohlaysizmi?`)) {
    await fetch(getRoute('telegram-funnels.funnels.destroy', [props.bot.id, funnel.id]), {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })
    router.reload()
  }
}
</script>
