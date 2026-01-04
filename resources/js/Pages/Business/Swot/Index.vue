<template>
  <BusinessLayout title="SWOT Tahlil">
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 rounded-2xl p-6 lg:p-8 text-white shadow-xl relative overflow-hidden">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>

        <div class="relative z-10">
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
              <div class="inline-flex items-center px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium mb-3">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Strategik Tahlil
              </div>
              <h1 class="text-2xl lg:text-4xl font-bold">SWOT Tahlil</h1>
              <p class="mt-2 text-white/80 text-lg max-w-2xl">
                Raqobatchilar tahlili asosida avtomatik yaratilgan strategik tahlil
              </p>
            </div>
            <div class="flex flex-wrap gap-3">
              <button
                @click="generateSwot"
                :disabled="generating"
                class="inline-flex items-center px-5 py-3 bg-white text-emerald-600 hover:bg-white/90 rounded-xl font-semibold transition-all shadow-lg disabled:opacity-50"
              >
                <svg v-if="generating" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                {{ generating ? 'Yangilanmoqda...' : 'Qayta Yangilash' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Competitor Info Banner -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ competitorCount }} ta raqobatchi</span>
                <span v-if="competitorCount > 0" class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-medium rounded-full">
                  Avtomatik yangilanadi
                </span>
              </div>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                <template v-if="competitorCount > 0">
                  SWOT tahlil raqobatchilar qo'shilganda avtomatik yangilanadi
                </template>
                <template v-else>
                  SWOT tahlil yaratish uchun avval raqobatchi qo'shing
                </template>
              </p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <div v-if="lastUpdated" class="text-sm text-gray-500 dark:text-gray-400">
              <span class="font-medium">Oxirgi yangilanish:</span> {{ formatDate(lastUpdated) }}
            </div>
            <Link
              href="/business/competitors"
              class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-500 text-white text-sm font-medium rounded-lg transition-colors"
            >
              <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Raqobatchi Qo'shish
            </Link>
          </div>
        </div>

        <!-- Competitor List -->
        <div v-if="competitors?.length" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Tahlil qilingan raqobatchilar:</p>
          <div class="flex flex-wrap gap-2">
            <Link
              v-for="comp in competitors"
              :key="comp.id"
              :href="`/business/competitors/${comp.id}`"
              class="inline-flex items-center px-3 py-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-full text-sm text-gray-700 dark:text-gray-300 transition-colors"
            >
              <span
                class="w-2 h-2 rounded-full mr-2"
                :class="{
                  'bg-red-500': comp.threat_level === 'critical',
                  'bg-orange-500': comp.threat_level === 'high',
                  'bg-yellow-500': comp.threat_level === 'medium',
                  'bg-green-500': comp.threat_level === 'low',
                }"
              ></span>
              {{ comp.name }}
            </Link>
          </div>
        </div>
      </div>

      <!-- Empty State when no competitors -->
      <div v-if="competitorCount === 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-12 text-center">
        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
        </div>
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">SWOT tahlil raqobatchilar asosida yaratiladi</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
          Raqobatchilarni qo'shganingizda, tizim avtomatik ravishda ularni tahlil qilib, sizning biznesingiz uchun SWOT yaratadi
        </p>
        <Link
          href="/business/competitors"
          class="inline-flex items-center px-6 py-3 bg-orange-600 hover:bg-orange-500 text-white font-semibold rounded-xl transition-colors"
        >
          <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Birinchi Raqobatchini Qo'shing
        </Link>
      </div>

      <!-- SWOT Grid -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Strengths -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                  <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-lg font-bold text-white">Kuchli Tomonlar</h2>
                  <p class="text-white/70 text-sm">Raqobatchilarga nisbatan afzalliklar</p>
                </div>
              </div>
              <span class="px-3 py-1 bg-white/20 rounded-full text-white text-sm font-medium">
                {{ swotData.strengths?.length || 0 }}
              </span>
            </div>
          </div>
          <div class="p-6">
            <div v-if="swotData.strengths?.length" class="space-y-3">
              <div
                v-for="(item, index) in swotData.strengths"
                :key="'s-' + index"
                class="flex items-start gap-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-xl"
              >
                <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs font-bold">
                  {{ index + 1 }}
                </span>
                <p class="text-gray-700 dark:text-gray-300">{{ item }}</p>
              </div>
            </div>
            <div v-else class="text-center py-8">
              <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <p class="text-gray-500 dark:text-gray-400">Kuchli tomonlar topilmadi</p>
              <p class="text-sm text-gray-400 mt-1">Ko'proq raqobatchi qo'shing</p>
            </div>
          </div>
        </div>

        <!-- Weaknesses -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="bg-gradient-to-r from-red-500 to-rose-600 px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                  <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-lg font-bold text-white">Zaif Tomonlar</h2>
                  <p class="text-white/70 text-sm">Raqobatchilarga nisbatan kamchiliklar</p>
                </div>
              </div>
              <span class="px-3 py-1 bg-white/20 rounded-full text-white text-sm font-medium">
                {{ swotData.weaknesses?.length || 0 }}
              </span>
            </div>
          </div>
          <div class="p-6">
            <div v-if="swotData.weaknesses?.length" class="space-y-3">
              <div
                v-for="(item, index) in swotData.weaknesses"
                :key="'w-' + index"
                class="flex items-start gap-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-xl"
              >
                <span class="flex-shrink-0 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs font-bold">
                  {{ index + 1 }}
                </span>
                <p class="text-gray-700 dark:text-gray-300">{{ item }}</p>
              </div>
            </div>
            <div v-else class="text-center py-8">
              <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              <p class="text-gray-500 dark:text-gray-400">Zaif tomonlar topilmadi</p>
            </div>
          </div>
        </div>

        <!-- Opportunities -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                  <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-lg font-bold text-white">Imkoniyatlar</h2>
                  <p class="text-white/70 text-sm">Raqobatchilarning zaifliklaridan foydalanish</p>
                </div>
              </div>
              <span class="px-3 py-1 bg-white/20 rounded-full text-white text-sm font-medium">
                {{ swotData.opportunities?.length || 0 }}
              </span>
            </div>
          </div>
          <div class="p-6">
            <div v-if="swotData.opportunities?.length" class="space-y-3">
              <div
                v-for="(item, index) in swotData.opportunities"
                :key="'o-' + index"
                class="flex items-start gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl"
              >
                <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold">
                  {{ index + 1 }}
                </span>
                <p class="text-gray-700 dark:text-gray-300">{{ item }}</p>
              </div>
            </div>
            <div v-else class="text-center py-8">
              <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
              <p class="text-gray-500 dark:text-gray-400">Imkoniyatlar topilmadi</p>
            </div>
          </div>
        </div>

        <!-- Threats -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="bg-gradient-to-r from-orange-500 to-amber-600 px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                  <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-lg font-bold text-white">Tahdidlar</h2>
                  <p class="text-white/70 text-sm">Raqobatchilardan keladigan xavflar</p>
                </div>
              </div>
              <span class="px-3 py-1 bg-white/20 rounded-full text-white text-sm font-medium">
                {{ swotData.threats?.length || 0 }}
              </span>
            </div>
          </div>
          <div class="p-6">
            <div v-if="swotData.threats?.length" class="space-y-3">
              <div
                v-for="(item, index) in swotData.threats"
                :key="'t-' + index"
                class="flex items-start gap-3 p-3 bg-orange-50 dark:bg-orange-900/20 rounded-xl"
              >
                <span class="flex-shrink-0 w-6 h-6 bg-orange-500 text-white rounded-full flex items-center justify-center text-xs font-bold">
                  {{ index + 1 }}
                </span>
                <p class="text-gray-700 dark:text-gray-300">{{ item }}</p>
              </div>
            </div>
            <div v-else class="text-center py-8">
              <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              <p class="text-gray-500 dark:text-gray-400">Tahdidlar topilmadi</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Recommendations -->
      <div v-if="swotData.recommendations?.length" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
          </div>
          <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">AI Tavsiyalar</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Raqobatchilar tahlili asosida</p>
          </div>
        </div>
        <div class="space-y-3">
          <div
            v-for="(rec, index) in swotData.recommendations"
            :key="'rec-' + index"
            class="flex items-start gap-3 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl"
          >
            <span class="flex-shrink-0 w-8 h-8 bg-purple-500 text-white rounded-lg flex items-center justify-center text-sm font-bold">
              {{ index + 1 }}
            </span>
            <p class="text-gray-700 dark:text-gray-300">{{ rec }}</p>
          </div>
        </div>
      </div>

      <!-- Strategic Actions -->
      <div v-if="competitorCount > 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
          </div>
          <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Strategik Yo'nalishlar</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">SWOT tahlili asosida harakatlar</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="p-4 bg-gradient-to-br from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 rounded-xl border border-green-200 dark:border-green-800">
            <h3 class="font-semibold text-green-800 dark:text-green-300 mb-2 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
              SO Strategiyasi
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Kuchli tomonlardan foydalanib imkoniyatlarni qo'lga kiritish
            </p>
          </div>

          <div class="p-4 bg-gradient-to-br from-red-50 to-blue-50 dark:from-red-900/20 dark:to-blue-900/20 rounded-xl border border-red-200 dark:border-red-800">
            <h3 class="font-semibold text-red-800 dark:text-red-300 mb-2 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              WO Strategiyasi
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Zaif tomonlarni bartaraf etib imkoniyatlardan foydalanish
            </p>
          </div>

          <div class="p-4 bg-gradient-to-br from-green-50 to-orange-50 dark:from-green-900/20 dark:to-orange-900/20 rounded-xl border border-green-200 dark:border-green-800">
            <h3 class="font-semibold text-green-800 dark:text-green-300 mb-2 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              ST Strategiyasi
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Kuchli tomonlar orqali tahdidlarni bartaraf etish
            </p>
          </div>

          <div class="p-4 bg-gradient-to-br from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 rounded-xl border border-red-200 dark:border-red-800">
            <h3 class="font-semibold text-red-800 dark:text-red-300 mb-2 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              WT Strategiyasi
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Zaif tomonlar va tahdidlarni minimallashtirish
            </p>
          </div>
        </div>
      </div>

      <!-- Related Links -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Link
          href="/business/competitors"
          class="flex items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-orange-400 dark:hover:border-orange-500 transition-colors group"
        >
          <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 dark:text-white">Raqobatchilar</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ competitorCount }} ta raqobatchi</p>
          </div>
        </Link>

        <Link
          href="/business/dream-buyer"
          class="flex items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-500 transition-colors group"
        >
          <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 dark:text-white">Mijoz Portreti</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Ideal mijozni aniqlang</p>
          </div>
        </Link>

        <Link
          href="/business/marketing"
          class="flex items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-purple-400 dark:hover:border-purple-500 transition-colors group"
        >
          <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
            </svg>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 dark:text-white">Marketing Markazi</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Barcha marketing vositalari</p>
          </div>
        </Link>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import axios from 'axios';

const props = defineProps({
  currentBusiness: {
    type: Object,
    required: true,
  },
  swot: {
    type: Object,
    default: () => ({}),
  },
  competitorCount: {
    type: Number,
    default: 0,
  },
  lastUpdated: {
    type: String,
    default: null,
  },
  competitors: {
    type: Array,
    default: () => [],
  },
});

const generating = ref(false);

const swotData = ref({
  strengths: props.swot?.strengths || [],
  weaknesses: props.swot?.weaknesses || [],
  opportunities: props.swot?.opportunities || [],
  threats: props.swot?.threats || [],
  recommendations: props.swot?.recommendations || [],
});

const generateSwot = async () => {
  generating.value = true;

  try {
    const response = await axios.post('/business/swot/generate');

    if (response.data.swot) {
      swotData.value = response.data.swot;
    }
  } catch (error) {
    console.error('SWOT generation failed:', error);
    alert('SWOT tahlilini yaratishda xatolik yuz berdi');
  } finally {
    generating.value = false;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('uz-UZ', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
};
</script>
