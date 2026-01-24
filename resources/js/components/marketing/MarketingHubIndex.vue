<template>
  <div class="space-y-8">
    <!-- Page Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl p-6 lg:p-8 text-white shadow-xl relative overflow-hidden">
      <div class="absolute inset-0 bg-black/10"></div>
      <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
      <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/2"></div>

      <div class="relative z-10">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <div>
            <div class="inline-flex items-center px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium mb-3">
              <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              Marketing Strategiyasi
            </div>
            <h1 class="text-2xl lg:text-4xl font-bold">Marketing Markazi</h1>
            <p class="mt-2 text-white/80 text-lg max-w-2xl">
              Biznesingiz marketing strategiyasini yarating, raqobatchilarni tahlil qiling va mijozlaringizni chuqur tushuning
            </p>
          </div>
          <div class="flex flex-wrap gap-3">
            <Link
              :href="getHref('/reports')"
              class="inline-flex items-center px-5 py-3 bg-white text-indigo-600 hover:bg-white/90 rounded-xl font-semibold transition-all shadow-lg"
            >
              <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Hisobotlarni Ko'rish
            </Link>
          </div>
        </div>

        <!-- Quick Stats in Header -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
          <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
            <p class="text-white/70 text-sm">Ideal Mijozlar</p>
            <p class="text-3xl font-bold mt-1">{{ stats.dream_buyers || 0 }}</p>
          </div>
          <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
            <p class="text-white/70 text-sm">Raqobatchilar</p>
            <p class="text-3xl font-bold mt-1">{{ stats.competitors || 0 }}</p>
          </div>
          <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
            <p class="text-white/70 text-sm">Takliflar</p>
            <p class="text-3xl font-bold mt-1">{{ stats.offers || 0 }}</p>
          </div>
          <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
            <p class="text-white/70 text-sm">Kontentlar</p>
            <p class="text-3xl font-bold mt-1">{{ stats.total_posts || 0 }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 1: Marketing Asoslari -->
    <div>
      <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
        </div>
        <div>
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">Marketing Asoslari</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400">Muvaffaqiyatli marketing strategiyasining poydevori</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Mijoz Portreti / Ideal Mijoz -->
        <Link
          :href="getHref('/dream-buyer')"
          class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-xl hover:border-blue-400 dark:hover:border-blue-500 transition-all duration-300 hover:-translate-y-1"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-medium rounded-lg">
              {{ stats.dream_buyers || 0 }} ta
            </span>
          </div>
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
            Mijoz Portreti
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Ideal mijozingizni aniqlang: demografiya, muammolar, maqsadlar va xarid motivatsiyasi
          </p>
          <div class="flex items-center text-blue-600 dark:text-blue-400 text-sm font-medium group-hover:gap-2 transition-all">
            <span>Batafsil</span>
            <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </Link>

        <!-- Raqobatchilar Tahlili -->
        <Link
          :href="getHref('/competitors')"
          class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-xl hover:border-orange-400 dark:hover:border-orange-500 transition-all duration-300 hover:-translate-y-1"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <span class="px-2 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 text-xs font-medium rounded-lg">
              {{ stats.competitors || 0 }} ta
            </span>
          </div>
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">
            Raqobatchilar Tahlili
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Raqobatchilarni kuzating, kuchli va zaif tomonlarini aniqlang, ustunlik yarating
          </p>
          <div class="flex items-center text-orange-600 dark:text-orange-400 text-sm font-medium group-hover:gap-2 transition-all">
            <span>Batafsil</span>
            <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </Link>

        <!-- SWOT Tahlil -->
        <Link
          :href="getHref('/swot')"
          class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-xl hover:border-emerald-400 dark:hover:border-emerald-500 transition-all duration-300 hover:-translate-y-1"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
              </svg>
            </div>
            <span class="px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-xs font-medium rounded-lg">
              Tahlil
            </span>
          </div>
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
            SWOT Tahlil
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Kuchli, zaif tomonlar, imkoniyatlar va tahdidlarni strategik tahlil qiling
          </p>
          <div class="flex items-center text-emerald-600 dark:text-emerald-400 text-sm font-medium group-hover:gap-2 transition-all">
            <span>Batafsil</span>
            <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </Link>

        <!-- Takliflar -->
        <Link
          :href="getHref('/offers')"
          class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-xl hover:border-green-400 dark:hover:border-green-500 transition-all duration-300 hover:-translate-y-1"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-lime-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
              </svg>
            </div>
            <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-xs font-medium rounded-lg">
              {{ stats.offers || 0 }} ta
            </span>
          </div>
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">
            Rad qilib bo'lmas takliflar
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Mijozlarni jalb qiladigan kuchli va qaytarib bo'lmas takliflarni yarating
          </p>
          <div class="flex items-center text-green-600 dark:text-green-400 text-sm font-medium group-hover:gap-2 transition-all">
            <span>Batafsil</span>
            <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </Link>
      </div>
    </div>

    <!-- Section 2: Kontent va Kanallar -->
    <div>
      <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
          </svg>
        </div>
        <div>
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">Kontent va Kanallar</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400">Kontentni rejalashtirish va tarqatish</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <!-- Kontent Kalendar -->
        <Link
          :href="getHref('/marketing/content')"
          class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-xl hover:border-pink-400 dark:hover:border-pink-500 transition-all duration-300 hover:-translate-y-1"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="w-14 h-14 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <div class="flex gap-2">
              <span class="px-2 py-1 bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400 text-xs font-medium rounded-lg">
                {{ stats.total_posts || 0 }} post
              </span>
            </div>
          </div>
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 group-hover:text-pink-600 dark:group-hover:text-pink-400 transition-colors">
            Kontent Kalendar
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Kontentlaringizni rejalashtiring, tayyorlang va nashr vaqtini belgilang
          </p>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3 text-xs">
              <span class="flex items-center gap-1 text-green-600 dark:text-green-400">
                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                {{ stats.published_posts || 0 }} nashr
              </span>
              <span class="flex items-center gap-1 text-blue-600 dark:text-blue-400">
                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                {{ stats.scheduled_posts || 0 }} reja
              </span>
            </div>
            <svg class="w-5 h-5 text-gray-400 group-hover:text-pink-500 group-hover:translate-x-1 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </Link>

        <!-- Marketing Kanallari -->
        <Link
          :href="getHref('/marketing/channels')"
          class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-xl hover:border-purple-400 dark:hover:border-purple-500 transition-all duration-300 hover:-translate-y-1"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
              </svg>
            </div>
            <div class="flex gap-2">
              <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-xs font-medium rounded-lg">
                {{ stats.total_channels || 0 }} kanal
              </span>
            </div>
          </div>
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
            Marketing Kanallari
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Instagram, Telegram, Facebook va boshqa ijtimoiy tarmoqlarni ulang
          </p>
          <div class="flex items-center justify-between">
            <div class="flex -space-x-2">
              <div class="w-8 h-8 bg-gradient-to-br from-pink-500 to-purple-600 rounded-full flex items-center justify-center border-2 border-white dark:border-gray-800">
                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069z"/>
                </svg>
              </div>
              <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center border-2 border-white dark:border-gray-800">
                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                </svg>
              </div>
              <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center border-2 border-white dark:border-gray-800">
                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
              </div>
            </div>
            <svg class="w-5 h-5 text-gray-400 group-hover:text-purple-500 group-hover:translate-x-1 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </Link>

        <!-- Reklama Kampaniyalari -->
        <Link
          :href="getHref('/marketing/campaigns')"
          class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-xl hover:border-cyan-400 dark:hover:border-cyan-500 transition-all duration-300 hover:-translate-y-1"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="w-14 h-14 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
              </svg>
            </div>
            <span class="px-2 py-1 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400 text-xs font-medium rounded-lg">
              {{ stats.campaigns || 0 }} ta
            </span>
          </div>
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
            Reklama Kampaniyalari
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Facebook, Google Ads va boshqa platformalardagi kampaniyalar
          </p>
          <div class="flex items-center justify-between">
            <span class="text-xs text-gray-500 dark:text-gray-400">
              Oylik xarajat: <span class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(stats.total_spend || 0) }}</span>
            </span>
            <svg class="w-5 h-5 text-gray-400 group-hover:text-cyan-500 group-hover:translate-x-1 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </Link>
      </div>
    </div>

    <!-- Section 3: Hisobotlar va KPI -->
    <div>
      <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <div>
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">Hisobotlar va KPI</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400">Marketing samaradorligini kuzating</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-5">
        <!-- Hisobotlar -->
        <Link
          :href="getHref('/reports')"
          class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-xl hover:border-amber-400 dark:hover:border-amber-500 transition-all duration-300 hover:-translate-y-1"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <span class="px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-xs font-medium rounded-lg">
              Hisobot
            </span>
          </div>
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
            Marketing Hisobotlari
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Marketing ko'rsatkichlarini kuzating va hisobotlarni yuklab oling
          </p>
          <div class="flex items-center text-amber-600 dark:text-amber-400 text-sm font-medium">
            <span>Ko'rish</span>
            <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </Link>

        <!-- KPI Reja -->
        <Link
          :href="getHref('/kpi')"
          class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-xl hover:border-rose-400 dark:hover:border-rose-500 transition-all duration-300 hover:-translate-y-1"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="w-14 h-14 bg-gradient-to-br from-rose-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
            </div>
            <span class="px-2 py-1 bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 text-xs font-medium rounded-lg">
              KPI
            </span>
          </div>
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors">
            KPI Reja
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Marketing maqsadlari va KPI ko'rsatkichlarini belgilang
          </p>
          <div class="flex items-center text-rose-600 dark:text-rose-400 text-sm font-medium">
            <span>Sozlash</span>
            <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </Link>
      </div>
    </div>

    <!-- Quick Actions Bar -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Tezkor harakatlar</h3>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
        <Link
          :href="getHref('/dream-buyer/create')"
          class="flex flex-col items-center justify-center p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors group"
        >
          <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
          </div>
          <span class="text-xs font-medium text-gray-700 dark:text-gray-300 text-center">Mijoz qo'shish</span>
        </Link>

        <Link
          :href="getHref('/competitors/create')"
          class="flex flex-col items-center justify-center p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-colors group"
        >
          <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
          </div>
          <span class="text-xs font-medium text-gray-700 dark:text-gray-300 text-center">Raqobatchi</span>
        </Link>

        <Link
          :href="getHref('/offers/builder')"
          class="flex flex-col items-center justify-center p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors group"
        >
          <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
          </div>
          <span class="text-xs font-medium text-gray-700 dark:text-gray-300 text-center">Taklif yaratish</span>
        </Link>

        <Link
          :href="getHref('/marketing/content')"
          class="flex flex-col items-center justify-center p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-pink-50 dark:hover:bg-pink-900/20 transition-colors group"
        >
          <div class="w-10 h-10 bg-pink-100 dark:bg-pink-900/30 rounded-lg flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-pink-600 dark:text-pink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </div>
          <span class="text-xs font-medium text-gray-700 dark:text-gray-300 text-center">Kontent yaratish</span>
        </Link>

        <Link
          :href="getHref('/swot')"
          class="flex flex-col items-center justify-center p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors group"
        >
          <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
            </svg>
          </div>
          <span class="text-xs font-medium text-gray-700 dark:text-gray-300 text-center">SWOT Tahlil</span>
        </Link>

        <Link
          :href="getHref('/reports')"
          class="flex flex-col items-center justify-center p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors group"
        >
          <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <span class="text-xs font-medium text-gray-700 dark:text-gray-300 text-center">Hisobotlar</span>
        </Link>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">So'nggi faoliyat</h2>
        <Link
          :href="getHref('/marketing/content')"
          class="text-sm text-indigo-600 dark:text-indigo-400 font-medium hover:underline"
        >
          Barchasini ko'rish
        </Link>
      </div>
      <div class="p-6">
        <div v-if="recentPosts && recentPosts.length > 0" class="space-y-4">
          <div
            v-for="post in recentPosts.slice(0, 5)"
            :key="post.id"
            class="flex items-start gap-4 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
          >
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                 :class="getPostTypeClass(post.status)">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-gray-900 dark:text-white truncate">{{ post.title }}</p>
              <div class="flex items-center gap-2 mt-1">
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
        <div v-else class="text-center py-12">
          <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Hali faoliyat yo'q</h3>
          <p class="text-gray-500 dark:text-gray-400 mb-4">Marketing faoliyatingizni boshlash uchun birinchi kontentni yarating</p>
          <Link
            :href="getHref('/marketing/content')"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 transition-colors"
          >
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Birinchi kontentni yarating
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
