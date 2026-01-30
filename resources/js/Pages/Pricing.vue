<template>
  <LandingLayout v-slot="{ urgencyBarVisible }">

    <!-- Hero Section -->
    <section class="pb-8 lg:pb-12 relative overflow-hidden" :class="urgencyBarVisible ? 'pt-[138px] lg:pt-[170px]' : 'pt-28 lg:pt-36'">
      <div class="absolute inset-0 bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/50"></div>
      <div class="absolute top-0 right-0 w-[800px] h-[800px] bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>

      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative">
        <!-- Social proof badge -->
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full shadow-md shadow-gray-200/50 border border-gray-100 mb-8">
          <div class="flex -space-x-2">
            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-xs font-bold border-2 border-white">A</div>
            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white text-xs font-bold border-2 border-white">S</div>
            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold border-2 border-white">M</div>
          </div>
          <span class="text-sm font-medium text-gray-700">500+ kompaniya bizga ishonadi</span>
          <div class="flex items-center text-amber-500">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            <span class="text-sm font-bold ml-1">4.9</span>
          </div>
        </div>

        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 mb-6 leading-tight">
          Biznesingiz uchun <span class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 bg-clip-text text-transparent">to'g'ri tarif</span>ni tanlang
        </h1>

        <p class="text-lg sm:text-xl text-gray-600 max-w-2xl mx-auto mb-10 leading-relaxed">
          Yashirin to'lovlar yo'q. Istalgan vaqt bekor qilish mumkin.
          <span class="text-blue-600 font-semibold">14 kun bepul sinab ko'ring!</span>
        </p>

        <!-- Billing Toggle -->
        <div class="inline-flex items-center p-1.5 bg-gray-100 rounded-2xl shadow-inner">
          <button
            @click="isYearly = false"
            class="relative px-8 py-3 text-sm font-semibold rounded-xl transition-all duration-300"
            :class="!isYearly ? 'bg-white text-gray-900 shadow-lg' : 'text-gray-500 hover:text-gray-700'"
          >
            Oylik to'lov
          </button>
          <button
            @click="isYearly = true"
            class="relative px-8 py-3 text-sm font-semibold rounded-xl transition-all duration-300 flex items-center gap-2"
            :class="isYearly ? 'bg-white text-gray-900 shadow-lg' : 'text-gray-500 hover:text-gray-700'"
          >
            Yillik to'lov
            <span class="px-2 py-0.5 bg-gradient-to-r from-green-500 to-emerald-500 text-white text-xs font-bold rounded-full shadow-sm">
              -20%
            </span>
          </button>
        </div>

        <!-- Guarantee micro-badge -->
        <div class="mt-6 flex items-center justify-center gap-2 text-sm text-gray-500">
          <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          <span>Karta talab qilinmaydi</span>
          <span class="text-gray-300">|</span>
          <span>30 kunlik kafolat</span>
          <span class="text-gray-300">|</span>
          <span>24/7 yordam</span>
        </div>
      </div>
    </section>

    <!-- Pricing Cards Section -->
    <section class="py-8 lg:py-12 relative">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 lg:gap-5 items-start">

          <!-- ============ PLAN 1: START (Anchor - arzon lekin cheklangan) ============ -->
          <div class="relative group">
            <div class="h-full bg-white rounded-3xl border-2 border-gray-100 p-6 lg:p-8 transition-all duration-300 hover:border-gray-200 hover:shadow-lg flex flex-col opacity-90">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center">
                  <svg class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-xl font-bold text-gray-900">Start</h3>
                  <p class="text-sm text-gray-400">Sinab ko'rish uchun</p>
                </div>
              </div>

              <div class="mb-6">
                <div class="flex items-baseline gap-1">
                  <span class="text-3xl lg:text-4xl font-extrabold text-gray-900">{{ formatPrice(getPrice(299000)) }}</span>
                  <span class="text-sm text-gray-400 font-medium">so'm/oy</span>
                </div>
                <p v-if="isYearly" class="text-sm text-green-600 font-medium mt-1">Yillik: {{ formatPrice(getPrice(299000) * 12) }} so'm</p>
              </div>

              <ul class="space-y-3 mb-8 flex-1">
                <li v-for="feature in startFeatures" :key="feature" class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  <span class="text-gray-500 text-sm">{{ feature }}</span>
                </li>
              </ul>

              <!-- Cheklangan badge -->
              <div class="mb-4 px-3 py-2 bg-amber-50 border border-amber-200 rounded-xl">
                <p class="text-xs text-amber-700 font-medium text-center">
                  <svg class="w-3.5 h-3.5 inline mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                  Faqat 2 xodim va 1 filial
                </p>
              </div>

              <button @click="selectPlan('start')" class="w-full py-3.5 px-6 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-all text-sm">
                Boshlash
              </button>
            </div>
          </div>

          <!-- ============ PLAN 2: STANDARD (Decoy - Business'ga yaqin narx, kam imkoniyat) ============ -->
          <div class="relative group">
            <div class="h-full bg-white rounded-3xl border-2 border-gray-100 p-6 lg:p-8 transition-all duration-300 hover:border-emerald-200 hover:shadow-lg flex flex-col">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-100 to-emerald-50 flex items-center justify-center">
                  <svg class="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-xl font-bold text-gray-900">Standard</h3>
                  <p class="text-sm text-gray-500">O'sish uchun</p>
                </div>
              </div>

              <div class="mb-6">
                <div class="flex items-baseline gap-1">
                  <span class="text-3xl lg:text-4xl font-extrabold text-gray-900">{{ formatPrice(getPrice(599000)) }}</span>
                  <span class="text-sm text-gray-400 font-medium">so'm/oy</span>
                </div>
                <p v-if="isYearly" class="text-sm text-green-600 font-medium mt-1">Yillik: {{ formatPrice(getPrice(599000) * 12) }} so'm</p>
              </div>

              <ul class="space-y-3 mb-8 flex-1">
                <li v-for="feature in standardFeatures" :key="feature" class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  <span class="text-gray-600 text-sm">{{ feature }}</span>
                </li>
              </ul>

              <!-- Decoy hint - shows Business is better value -->
              <div class="mb-4 px-3 py-2 bg-blue-50 border border-blue-100 rounded-xl">
                <p class="text-xs text-blue-600 font-medium text-center">
                  +200,000 so'm'ga 2x ko'p imkoniyat
                  <svg class="w-3.5 h-3.5 inline ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                </p>
              </div>

              <button @click="selectPlan('standard')" class="w-full py-3.5 px-6 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">
                Tanlash
              </button>
            </div>
          </div>

          <!-- ============ PLAN 3: BUSINESS (The HERO - 10% kattaroq, glow, pulsating CTA) ============ -->
          <div class="relative group xl:-mt-6 xl:mb-6">
            <!-- Animated glow effect -->
            <div class="absolute -inset-1 bg-gradient-to-b from-purple-500 via-violet-500 to-indigo-600 rounded-[28px] opacity-100 blur-[2px] animate-glow"></div>

            <div class="relative h-full bg-white rounded-3xl p-7 lg:p-9 flex flex-col">
              <!-- ENG XARIDORGIR Badge -->
              <div class="absolute -top-5 left-1/2 -translate-x-1/2 z-10">
                <span class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-gradient-to-r from-purple-600 via-violet-600 to-indigo-600 text-white text-sm font-extrabold rounded-full shadow-xl shadow-purple-500/40 animate-badge-pulse">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z"/></svg>
                  ENG XARIDORGIR
                </span>
              </div>

              <div class="flex items-center gap-3 mb-6 mt-5">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-100 to-violet-50 flex items-center justify-center">
                  <svg class="w-7 h-7 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-2xl font-extrabold text-gray-900">Business</h3>
                  <p class="text-sm text-purple-600 font-medium">Eng ko'p tanlanadi</p>
                </div>
              </div>

              <div class="mb-6">
                <div class="flex items-baseline gap-1">
                  <span class="text-4xl lg:text-5xl font-black text-gray-900">{{ formatPrice(getPrice(799000)) }}</span>
                  <span class="text-sm text-gray-400 font-medium">so'm/oy</span>
                </div>
                <p v-if="isYearly" class="text-sm text-green-600 font-semibold mt-1">
                  <svg class="w-4 h-4 inline mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                  Yillik: {{ formatPrice(getPrice(799000) * 12) }} so'm — 1,918,000 tejaysiz!
                </p>
                <p v-else class="text-sm text-purple-600 font-semibold mt-1">Kuniga atigi {{ formatPrice(Math.round(799000 / 30)) }} so'm</p>

                <!-- Value anchor: per employee -->
                <div class="mt-3 px-3 py-2 bg-gradient-to-r from-purple-50 to-violet-50 rounded-xl border border-purple-100">
                  <p class="text-xs text-purple-700 font-medium">
                    Har bir xodim uchun faqat <span class="font-bold text-purple-900">{{ formatPrice(Math.round(getPrice(799000) / 10)) }} so'm/oy</span>
                  </p>
                </div>
              </div>

              <ul class="space-y-3.5 mb-8 flex-1">
                <li v-for="feature in businessFeatures" :key="feature.text" class="flex items-start gap-3">
                  <svg class="w-5 h-5 flex-shrink-0 mt-0.5" :class="feature.hot ? 'text-amber-500' : 'text-purple-500'" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  <span class="text-gray-800 font-medium" :class="{ 'text-purple-900 font-bold': feature.hot }">{{ feature.text }}</span>
                </li>
              </ul>

              <!-- Pulsating CTA -->
              <button @click="selectPlan('business')" class="w-full py-4.5 px-6 bg-gradient-to-r from-purple-600 via-violet-600 to-indigo-600 text-white font-extrabold rounded-xl hover:from-purple-700 hover:to-indigo-700 transition-all shadow-xl shadow-purple-500/30 hover:shadow-2xl hover:shadow-purple-500/40 hover:-translate-y-0.5 active:scale-[0.98] text-lg animate-cta-pulse">
                14 Kun BEPUL Boshlash
                <svg class="w-5 h-5 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
              </button>
              <p class="text-center text-xs text-gray-400 mt-3">Karta talab qilinmaydi. Xavfsiz.</p>
            </div>
          </div>

          <!-- ============ PLAN 4: PREMIUM (Ceiling - Business'ni arzon ko'rsatadi) ============ -->
          <div class="relative group">
            <div class="absolute -inset-[2px] bg-gradient-to-b from-amber-400 via-orange-500 to-amber-500 rounded-[26px] opacity-100"></div>

            <div class="relative h-full bg-white rounded-3xl p-6 lg:p-8 flex flex-col">
              <!-- VIP Badge -->
              <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-sm font-bold rounded-full shadow-lg shadow-amber-500/30">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd" /></svg>
                  VIP PREMIUM
                </span>
              </div>

              <div class="flex items-center gap-3 mb-6 mt-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-100 to-amber-50 flex items-center justify-center">
                  <svg class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-xl font-bold text-gray-900">Premium</h3>
                  <p class="text-sm text-amber-600 font-medium">Katta jamoalar uchun</p>
                </div>
              </div>

              <div class="mb-6">
                <div class="flex items-baseline gap-1">
                  <span class="text-3xl lg:text-4xl font-extrabold text-gray-900">{{ formatPrice(getPrice(1499000)) }}</span>
                  <span class="text-sm text-gray-400 font-medium">so'm/oy</span>
                </div>
                <p v-if="isYearly" class="text-sm text-green-600 font-medium mt-1">Yillik: {{ formatPrice(getPrice(1499000) * 12) }} so'm</p>
                <p v-else class="text-sm text-amber-600 font-medium mt-1">Maxsus yechim kerakmi?</p>
              </div>

              <ul class="space-y-3 mb-8 flex-1">
                <li v-for="feature in premiumFeatures" :key="feature" class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  <span class="text-gray-700 font-medium text-sm">{{ feature }}</span>
                </li>
              </ul>

              <button @click="selectPlan('premium')" class="w-full py-3.5 px-6 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-bold rounded-xl hover:from-amber-600 hover:to-orange-600 transition-all shadow-lg shadow-amber-500/25 hover:shadow-xl text-sm">
                Premium olish
              </button>
              <a href="https://t.me/biznespilot_support" target="_blank" class="mt-3 text-center text-xs text-amber-600 hover:text-amber-700 font-medium transition-colors block">
                yoki konsultatsiya olish
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Quick Comparison Strip -->
    <section class="py-6 bg-gradient-to-r from-purple-50 via-violet-50 to-indigo-50 border-y border-purple-100">
      <div class="max-w-5xl mx-auto px-4">
        <div class="flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-sm">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
            <span class="text-gray-500">Start: 2 xodim</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
            <span class="text-gray-600">Standard: 5 xodim</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded-full bg-gradient-to-r from-purple-500 to-violet-500 ring-2 ring-purple-300"></div>
            <span class="text-purple-700 font-bold">Business: 10 xodim + HR Bot</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-amber-400"></div>
            <span class="text-gray-600">Premium: 15 xodim + Anti-Fraud</span>
          </div>
        </div>
      </div>
    </section>

    <!-- 30 Kunlik Kafolat (Risk Reversal) -->
    <section class="py-16 lg:py-20 bg-white">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="relative bg-gradient-to-br from-green-50 to-emerald-50 rounded-3xl p-8 lg:p-12 border-2 border-green-200 overflow-hidden">
          <!-- Background decoration -->
          <div class="absolute top-0 right-0 w-64 h-64 bg-green-200/30 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>

          <div class="relative flex flex-col lg:flex-row items-center gap-8">
            <!-- Shield icon -->
            <div class="flex-shrink-0">
              <div class="w-24 h-24 lg:w-28 lg:h-28 bg-gradient-to-br from-green-500 to-emerald-600 rounded-3xl flex items-center justify-center shadow-xl shadow-green-500/30 transform -rotate-3">
                <svg class="w-12 h-12 lg:w-14 lg:h-14 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
              </div>
            </div>

            <div class="text-center lg:text-left">
              <h3 class="text-2xl lg:text-3xl font-extrabold text-gray-900 mb-3">
                30 Kunlik Pulni Qaytarish Kafolati
              </h3>
              <p class="text-gray-600 text-lg leading-relaxed mb-6">
                Agar BiznesPilot sizga mos kelmasa — <span class="font-bold text-green-700">30 kun ichida to'liq pulni qaytaramiz</span>.
                Hech qanday savol yo'q, hech qanday murakkablik yo'q. Shunchaki Telegram orqali yozing — xolos.
              </p>

              <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                  <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                  Shart-sharoitsiz
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-600">
                  <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                  24 soat ichida qaytarish
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-600">
                  <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                  Telegram orqali ariza
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Feature Comparison Accordion Table -->
    <section class="py-16 lg:py-20 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <span class="inline-block px-4 py-1.5 bg-indigo-100 text-indigo-700 rounded-full text-sm font-semibold mb-4">Batafsil taqqoslash</span>
          <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">Barcha imkoniyatlarni solishtiring</h2>
          <p class="text-lg text-gray-600">Har bir tarifda nimalar bor — batafsil jadval</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-lg overflow-hidden">
          <ComparisonTable :plans="plans" />
        </div>

        <!-- Business CTA under comparison -->
        <div class="mt-8 text-center">
          <button @click="selectPlan('business')" class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-purple-600 to-violet-600 text-white font-bold rounded-xl hover:from-purple-700 hover:to-violet-700 transition-all shadow-lg shadow-purple-500/25 hover:shadow-xl hover:-translate-y-0.5 active:scale-[0.98]">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z"/></svg>
            Business tarifini 14 kun BEPUL sinash
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
          </button>
        </div>
      </div>
    </section>

    <!-- Social Proof Section -->
    <section class="py-16 lg:py-20 bg-white">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <span class="inline-block px-4 py-1.5 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold mb-4">Muvaffaqiyatli mijozlar</span>
          <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">Ular nima uchun Business tarifini tanladi?</h2>
        </div>

        <div class="grid md:grid-cols-3 gap-6 lg:gap-8">
          <div v-for="(testimonial, i) in testimonials" :key="i" class="bg-white rounded-2xl p-6 lg:p-8 shadow-lg shadow-gray-200/50 border border-gray-100">
            <div class="flex items-center gap-1 text-amber-400 mb-4">
              <svg v-for="j in 5" :key="j" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            </div>
            <p class="text-gray-700 mb-6 leading-relaxed">"{{ testimonial.text }}"</p>
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold" :class="testimonial.bgClass">{{ testimonial.initials }}</div>
              <div>
                <p class="font-semibold text-gray-900">{{ testimonial.name }}</p>
                <p class="text-sm text-gray-500">{{ testimonial.role }}</p>
              </div>
            </div>
            <div class="mt-4 inline-flex items-center gap-1.5 px-3 py-1 bg-purple-50 text-purple-700 rounded-full text-xs font-semibold">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
              Business tarifi foydalanuvchisi
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-16 lg:py-20 bg-gray-50" id="faq">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <span class="inline-block px-4 py-1.5 bg-purple-100 text-purple-700 rounded-full text-sm font-semibold mb-4">Savollar</span>
          <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">Ko'p so'raladigan savollar</h2>
        </div>

        <div class="space-y-4">
          <div v-for="(faq, index) in faqs" :key="index" class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm">
            <button @click="toggleFaq(index)" class="w-full flex items-center justify-between p-6 text-left hover:bg-gray-50 transition-colors">
              <span class="font-semibold text-gray-900 pr-4">{{ faq.question }}</span>
              <svg class="w-5 h-5 text-gray-400 flex-shrink-0 transition-transform duration-300" :class="{ 'rotate-180': openFaq === index }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div v-show="openFaq === index" class="px-6 pb-6 text-gray-600 leading-relaxed">
              {{ faq.answer }}
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Final CTA Section -->
    <section class="py-20 lg:py-24 bg-gradient-to-br from-purple-700 via-violet-700 to-indigo-800 relative overflow-hidden">
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-0 w-full h-full" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
      </div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-white/5 rounded-full blur-3xl"></div>

      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full border border-white/20 mb-8">
          <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
          <span class="text-white/90 text-sm font-medium">Hozir 500+ kompaniya foydalanmoqda</span>
        </div>

        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-6 leading-tight">
          Hali ham ikkilanayapsizmi?
          <br class="hidden sm:block" />
          <span class="text-purple-200">14 kun bepul sinab ko'ring</span>
        </h2>
        <p class="text-xl text-purple-200 mb-10 max-w-2xl mx-auto">
          Karta talab qilinmaydi. Xavf yo'q. 30 kunlik pulni qaytarish kafolati.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
          <button @click="selectPlan('business')" class="inline-flex items-center px-10 py-5 bg-white text-purple-700 text-lg font-extrabold rounded-xl hover:bg-purple-50 transition-all shadow-2xl shadow-black/20 hover:shadow-3xl transform hover:-translate-y-1 active:scale-[0.98]">
            <span>Business tarifini boshlash</span>
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </button>
          <a href="https://t.me/biznespilot_support" target="_blank" class="inline-flex items-center px-8 py-5 bg-white/10 backdrop-blur-sm text-white text-lg font-semibold rounded-xl hover:bg-white/20 transition-all border border-white/20">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
              <path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0a12 12 0 00-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
            </svg>
            Konsultatsiya olish
          </a>
        </div>

        <!-- Trust badges -->
        <div class="mt-12 flex flex-wrap items-center justify-center gap-6 text-white/70 text-sm">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>30 kunlik kafolat</span>
          </div>
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
              <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
            </svg>
            <span>Click & Payme</span>
          </div>
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
            </svg>
            <span>24/7 yordam</span>
          </div>
        </div>
      </div>
    </section>

  </LandingLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import LandingLayout from '@/layouts/LandingLayout.vue';
import ComparisonTable from '@/components/pricing/ComparisonTable.vue';

const isYearly = ref(true); // Default to yearly (better for conversions)
const openFaq = ref(null);

const formatPrice = (price) => {
  return Math.round(price).toLocaleString('uz-UZ');
};

const getPrice = (monthlyPrice) => {
  return isYearly.value ? Math.round(monthlyPrice * 0.8) : monthlyPrice;
};

const selectPlan = (planId) => {
  router.visit('/register', {
    data: { plan: planId }
  });
};

// Plans data for ComparisonTable
const plans = [
  { id: 'start', name: 'Start', monthlyPrice: 299000 },
  { id: 'standard', name: 'Standard', monthlyPrice: 599000 },
  { id: 'business', name: 'Business', monthlyPrice: 799000 },
  { id: 'premium', name: 'Premium', monthlyPrice: 1499000 }
];

// Feature lists per plan — designed for psychology
const startFeatures = [
  '2 ta xodim',
  '1 ta filial',
  'Instagram + Telegram bot',
  '500 ta lid/oy',
  '60 daqiqa Call Center AI',
  'Asosiy CRM',
];

const standardFeatures = [
  '5 ta xodim',
  '1 ta filial',
  'Flow Builder (Vizual)',
  '2,000 ta lid/oy',
  '150 daqiqa Call Center AI',
  'HR vazifalar',
];

const businessFeatures = [
  { text: '10 ta xodim', hot: false },
  { text: '2 ta filial', hot: false },
  { text: 'HR Bot + Marketing ROI', hot: true },
  { text: '10,000 ta lid/oy', hot: true },
  { text: '400 daqiqa Call Center AI', hot: false },
  { text: 'Flow Builder (Vizual)', hot: false },
  { text: '3 ta Instagram akkaunt', hot: true },
  { text: '8 soat ichida javob', hot: false },
];

const premiumFeatures = [
  '15 ta xodim',
  '5 ta filial',
  'AI Bot + Anti-Fraud SMS',
  'Cheksiz lid',
  '1,000 daqiqa Call Center AI',
  'Shaxsiy menejer',
  '10 ta Instagram akkaunt',
  '2 soat ichida javob',
];

// Testimonials - Business plan focused
const testimonials = [
  {
    text: 'Business tarifida HR Bot bilan yangi xodimlarni 50% tezroq topamiz. Vaqtimiz tejaldi, sifat oshdi!',
    name: 'Jasur Rahimov',
    role: 'BuildPro, HR Manager',
    initials: 'JR',
    bgClass: 'bg-gradient-to-br from-purple-500 to-pink-500',
  },
  {
    text: 'Instagram so\'rovlarini 3x tezroq qayta ishlay boshladik. 10 ta xodim uchun Business tarifi eng ideal narx!',
    name: 'Aziz Soliyev',
    role: 'TechMart.uz, CEO',
    initials: 'AS',
    bgClass: 'bg-gradient-to-br from-blue-500 to-indigo-500',
  },
  {
    text: 'Call Center AI juda foydali! 400 daqiqa har bir qo\'ng\'iroqni tahlil qilish uchun yetarli.',
    name: 'Nodira Karimova',
    role: 'MediPlus, Marketing Director',
    initials: 'NK',
    bgClass: 'bg-gradient-to-br from-emerald-500 to-teal-500',
  },
];

const faqs = [
  {
    question: '14 kunlik bepul sinov davri qanday ishlaydi?',
    answer: 'Ro\'yxatdan o\'tishingiz bilanoq 14 kun bepul sinov boshlanadi. Karta talab qilinmaydi. Sinov tugagach, davom etishni xohlasangiz to\'lovni amalga oshirasiz. Xohlamasangiz — hech narsa to\'lamaysiz.'
  },
  {
    question: '30 kunlik pulni qaytarish kafolati haqiqatmi?',
    answer: 'Ha, 100% haqiqat. To\'lovdan keyin 30 kun ichida tizim sizga mos kelmasa — Telegram orqali yozing va biz 24 soat ichida to\'liq pulni qaytaramiz. Hech qanday savol yo\'q.'
  },
  {
    question: 'Tarif o\'zgartirsa bo\'ladimi?',
    answer: 'Istalgan vaqt yuqoriroq tarifga o\'tishingiz mumkin. To\'langan summa proporsional hisoblanadi. Pastroq tarifga o\'tish esa keyingi to\'lov davridan boshlab amalga oshadi.'
  },
  {
    question: 'To\'lov turlari qanday?',
    answer: 'Click, Payme, bank o\'tkazmasi va naqd to\'lov qabul qilamiz. Korporativ mijozlar uchun shartnoma va schet-faktura tayyorlaymiz.'
  },
  {
    question: 'Botni o\'zim sozlay olamanmi?',
    answer: 'Ha, albatta! BiznesPilot\'da oddiy drag-and-drop Flow Builder mavjud. Hech qanday dasturlash bilimi talab qilinmaydi. Video qo\'llanmalar va Telegram support ham bor.'
  },
  {
    question: 'Ma\'lumotlarim xavfsizmi?',
    answer: 'Ha, biz SSL shifrlash, kunlik backup va serverlarimiz Yevropa data-centerlarida joylashgan. GDPR talablariga muvofiq ishlaymiz.'
  },
  {
    question: 'Call Center AI daqiqalari tugasa nima bo\'ladi?',
    answer: 'Daqiqalar tugagandan so\'ng, qo\'shimcha daqiqalar sotib olishingiz yoki keyingi oyga kutishingiz mumkin — limitlar avtomatik yangilanadi.'
  },
  {
    question: 'Qaysi tarifni tanlashim kerak?',
    answer: 'Ko\'pchilik mijozlarimiz Business tarifini tanlaydi — 10 ta xodim, HR Bot, Marketing ROI va 10,000 ta lid imkoniyati ko\'p bizneslar uchun ideal. Kichik jamoa bo\'lsa Start, katta jamoa uchun Premium tavsiya etamiz.'
  }
];

const toggleFaq = (index) => {
  openFaq.value = openFaq.value === index ? null : index;
};
</script>

<style scoped>
@keyframes glow {
  0%, 100% { opacity: 0.8; }
  50% { opacity: 1; }
}

@keyframes badge-pulse {
  0%, 100% { transform: translateX(-50%) scale(1); }
  50% { transform: translateX(-50%) scale(1.05); }
}

@keyframes cta-pulse {
  0%, 100% { box-shadow: 0 10px 25px -5px rgba(147, 51, 234, 0.3); }
  50% { box-shadow: 0 10px 35px -3px rgba(147, 51, 234, 0.5); }
}

.animate-glow {
  animation: glow 3s ease-in-out infinite;
}

.animate-badge-pulse {
  animation: badge-pulse 2s ease-in-out infinite;
}

.animate-cta-pulse {
  animation: cta-pulse 2s ease-in-out infinite;
}
</style>
