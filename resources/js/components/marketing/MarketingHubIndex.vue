<template>
  <div class="space-y-8">

    <!-- Page Header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Marketing Markazi</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Biznesingiz marketing strategiyasini boshqaring
        </p>
      </div>
      <Link
        :href="getHref('/reports')"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
      >
        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        Hisobotlar
      </Link>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Ideal Mijozlar</span>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.dream_buyers || 0 }}</p>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
          <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Raqobatchilar</span>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.competitors || 0 }}</p>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
          </div>
          <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Takliflar</span>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.offers || 0 }}</p>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
            </svg>
          </div>
          <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Kontentlar</span>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total_posts || 0 }}</p>
      </div>
    </div>

    <!-- Main Navigation Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

      <!-- Marketing Asoslari -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Marketing Asoslari</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Strategiyaning poydevori</p>
        </div>
        <div class="p-4 space-y-1">
          <Link
            :href="getHref('/dream-buyer')"
            class="flex items-center gap-3 px-3 py-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
          >
            <div class="w-9 h-9 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-4.5 h-4.5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Mijoz Portreti</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Ideal mijozingizni aniqlang</p>
            </div>
            <span class="text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2 py-1 rounded-md">{{ stats.dream_buyers || 0 }}</span>
            <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </Link>

          <Link
            :href="getHref('/competitors')"
            class="flex items-center gap-3 px-3 py-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
          >
            <div class="w-9 h-9 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-4.5 h-4.5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Raqobatchilar Tahlili</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Raqobatchilarni kuzating va tahlil qiling</p>
            </div>
            <span class="text-xs font-medium text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/20 px-2 py-1 rounded-md">{{ stats.competitors || 0 }}</span>
            <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </Link>

          <Link
            :href="getHref('/swot')"
            class="flex items-center gap-3 px-3 py-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
          >
            <div class="w-9 h-9 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-4.5 h-4.5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">SWOT Tahlil</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Strategik kuchli/zaif tomonlar tahlili</p>
            </div>
            <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </Link>

          <Link
            :href="getHref('/offers')"
            class="flex items-center gap-3 px-3 py-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
          >
            <div class="w-9 h-9 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-4.5 h-4.5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Takliflar</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Rad qilib bo'lmas takliflarni yarating</p>
            </div>
            <span class="text-xs font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 px-2 py-1 rounded-md">{{ stats.offers || 0 }}</span>
            <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </Link>
        </div>
      </div>

      <!-- Kontent va Kanallar -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Kontent va Kanallar</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kontentni rejalashtirish va tarqatish</p>
        </div>
        <div class="p-4 space-y-1">
          <Link
            :href="getHref('/marketing/content')"
            class="flex items-center gap-3 px-3 py-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
          >
            <div class="w-9 h-9 bg-pink-100 dark:bg-pink-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-4.5 h-4.5 text-pink-600 dark:text-pink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Kontent Kalendar</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                <span class="text-green-600 dark:text-green-400">{{ stats.published_posts || 0 }} nashr</span>
                <span class="mx-1 text-gray-300 dark:text-gray-600">&middot;</span>
                <span class="text-blue-600 dark:text-blue-400">{{ stats.scheduled_posts || 0 }} reja</span>
              </p>
            </div>
            <span class="text-xs font-medium text-pink-600 dark:text-pink-400 bg-pink-50 dark:bg-pink-900/20 px-2 py-1 rounded-md">{{ stats.total_posts || 0 }}</span>
            <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </Link>

          <Link
            :href="getHref('/marketing/channels')"
            class="flex items-center gap-3 px-3 py-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
          >
            <div class="w-9 h-9 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-4.5 h-4.5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Marketing Kanallari</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Instagram, Telegram, Facebook</p>
            </div>
            <span class="text-xs font-medium text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 px-2 py-1 rounded-md">{{ stats.total_channels || 0 }}</span>
            <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </Link>

          <Link
            :href="getHref('/marketing/campaigns')"
            class="flex items-center gap-3 px-3 py-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
          >
            <div class="w-9 h-9 bg-cyan-100 dark:bg-cyan-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-4.5 h-4.5 text-cyan-600 dark:text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Reklama Kampaniyalari</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                Oylik xarajat: <span class="font-medium text-gray-700 dark:text-gray-300">{{ formatCurrency(stats.total_spend || 0) }}</span>
              </p>
            </div>
            <span class="text-xs font-medium text-cyan-600 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-900/20 px-2 py-1 rounded-md">{{ stats.campaigns || 0 }}</span>
            <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </Link>
        </div>
      </div>

      <!-- Tadqiqot va Tahlil -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Tadqiqot va Tahlil</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Mijozlar tadqiqoti va bozor tahlili</p>
        </div>
        <div class="p-4 space-y-1">
          <Link
            :href="getHref('/marketing/custdev')"
            class="flex items-center gap-3 px-3 py-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
          >
            <div class="w-9 h-9 bg-teal-100 dark:bg-teal-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-4.5 h-4.5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">CustDev Tadqiqot</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Mijozlar bilan suhbatlar va tadqiqotlar</p>
            </div>
            <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </Link>
        </div>
      </div>

      <!-- Hisobotlar va KPI -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Hisobotlar va KPI</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Marketing samaradorligini kuzating</p>
        </div>
        <div class="p-4 space-y-1">
          <Link
            :href="getHref('/reports')"
            class="flex items-center gap-3 px-3 py-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
          >
            <div class="w-9 h-9 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-4.5 h-4.5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Marketing Hisobotlari</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Ko'rsatkichlarni kuzating va hisobotlarni yuklab oling</p>
            </div>
            <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </Link>

          <Link
            :href="getHref('/kpi')"
            class="flex items-center gap-3 px-3 py-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
          >
            <div class="w-9 h-9 bg-rose-100 dark:bg-rose-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-4.5 h-4.5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">KPI Reja</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Marketing maqsadlari va KPI ko'rsatkichlari</p>
            </div>
            <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </Link>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div>
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Tezkor harakatlar</h3>
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <Link
          :href="getHref('/dream-buyer/create')"
          class="flex flex-col items-center gap-2 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 hover:shadow-md transition-all text-center group"
        >
          <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
          </div>
          <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Mijoz qo'shish</span>
        </Link>

        <Link
          :href="getHref('/competitors/create')"
          class="flex flex-col items-center gap-2 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-orange-300 dark:hover:border-orange-600 hover:shadow-md transition-all text-center group"
        >
          <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
          </div>
          <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Raqobatchi</span>
        </Link>

        <Link
          :href="getHref('/offers/create')"
          class="flex flex-col items-center gap-2 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-green-300 dark:hover:border-green-600 hover:shadow-md transition-all text-center group"
        >
          <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
          </div>
          <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Taklif yaratish</span>
        </Link>

        <Link
          :href="getHref('/marketing/content')"
          class="flex flex-col items-center gap-2 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-pink-300 dark:hover:border-pink-600 hover:shadow-md transition-all text-center group"
        >
          <div class="w-10 h-10 bg-pink-100 dark:bg-pink-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-pink-600 dark:text-pink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </div>
          <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Kontent yaratish</span>
        </Link>

        <Link
          :href="getHref('/swot')"
          class="flex flex-col items-center gap-2 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-emerald-300 dark:hover:border-emerald-600 hover:shadow-md transition-all text-center group"
        >
          <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
            </svg>
          </div>
          <span class="text-xs font-medium text-gray-700 dark:text-gray-300">SWOT Tahlil</span>
        </Link>

        <Link
          :href="getHref('/reports')"
          class="flex flex-col items-center gap-2 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-amber-300 dark:hover:border-amber-600 hover:shadow-md transition-all text-center group"
        >
          <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Hisobotlar</span>
        </Link>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">So'nggi faoliyat</h3>
        <Link
          :href="getHref('/marketing/content')"
          class="text-sm text-blue-600 dark:text-blue-400 font-medium hover:underline"
        >
          Barchasini ko'rish
        </Link>
      </div>
      <div class="p-5">
        <div v-if="recentPosts && recentPosts.length > 0" class="space-y-3">
          <div
            v-for="post in recentPosts.slice(0, 5)"
            :key="post.id"
            class="flex items-start gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
          >
            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                 :class="getPostTypeClass(post.status)">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ post.title }}</p>
              <div class="flex items-center gap-2 mt-0.5">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ post.platform }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full"
                      :class="getPostStatusClass(post.status)">
                  {{ getPostStatusLabel(post.status) }}
                </span>
              </div>
            </div>
            <span class="text-xs text-gray-500 dark:text-gray-400 flex-shrink-0">
              {{ formatDate(post.published_at || post.scheduled_at || post.created_at) }}
            </span>
          </div>
        </div>
        <div v-else class="text-center py-10">
          <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
            </svg>
          </div>
          <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">Hali faoliyat yo'q</p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Birinchi kontentni yarating</p>
          <Link
            :href="getHref('/marketing/content')"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
          >
            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Kontent yaratish
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
  panelType: {
    type: String,
    required: true,
    validator: (value) => ['business', 'marketing'].includes(value),
  },
  channels: {
    type: Array,
    default: () => [],
  },
  recentPosts: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({}),
  },
  currentBusiness: {
    type: Object,
    required: true,
  },
});

// Helper to generate correct href based on panel type
const getHref = (path) => {
  const prefix = props.panelType === 'business' ? '/business' : '/marketing';
  return prefix + path;
};

const formatCurrency = (amount) => {
  if (!amount) return "0 " + t('common.currency');
  return new Intl.NumberFormat('uz-UZ').format(amount) + " " + t('common.currency');
};

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('uz-UZ', {
    day: 'numeric',
    month: 'short',
  });
};

const getPostTypeClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
    scheduled: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
    published: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
  };
  return classes[status] || classes.draft;
};

const getPostStatusClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
    scheduled: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
    published: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
  };
  return classes[status] || classes.draft;
};

const getPostStatusLabel = (status) => {
  const labels = {
    draft: t('marketing.status_draft'),
    scheduled: t('marketing.status_scheduled'),
    published: t('marketing.status_published'),
  };
  return labels[status] || status;
};
</script>
