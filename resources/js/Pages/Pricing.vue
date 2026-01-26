<template>
  <div class="min-h-screen bg-white">
    <!-- Header -->
    <header class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 lg:h-20">
          <!-- Logo -->
          <Link href="/" class="flex items-center space-x-2">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <span class="text-xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">BiznesPilot</span>
          </Link>

          <!-- Desktop Navigation -->
          <nav class="hidden lg:flex items-center space-x-8">
            <Link href="/#features" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">Imkoniyatlar</Link>
            <Link href="/#how-it-works" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">Qanday ishlaydi</Link>
            <Link href="/#benefits" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">Afzalliklar</Link>
            <span class="text-blue-600 font-semibold">Narxlar</span>
            <Link href="/#faq" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">FAQ</Link>
          </nav>

          <!-- Right side actions -->
          <div class="hidden lg:flex items-center space-x-4">
            <!-- Language Switcher -->
            <div class="flex items-center space-x-1 bg-gray-100 rounded-full p-1">
              <a href="/language/uz" class="flex items-center justify-center w-9 h-9 rounded-full transition-all bg-white shadow-sm ring-2 ring-blue-500" title="O'zbekcha">
                <svg class="w-6 h-6 rounded-full shadow-sm" viewBox="0 0 512 512">
                  <mask id="uzMask"><circle cx="256" cy="256" r="256" fill="#fff"/></mask>
                  <g mask="url(#uzMask)">
                    <path fill="#0099b5" d="M0 0h512v167H0z"/>
                    <path fill="#fff" d="M0 178h512v156H0z"/>
                    <path fill="#1eb53a" d="M0 345h512v167H0z"/>
                    <path fill="#ce1126" d="M0 167h512v11H0zM0 334h512v11H0z"/>
                    <circle cx="163" cy="89" r="45" fill="#fff"/>
                    <circle cx="176" cy="89" r="40" fill="#0099b5"/>
                  </g>
                </svg>
              </a>
              <a href="/language/ru" class="flex items-center justify-center w-9 h-9 rounded-full transition-all hover:bg-gray-200" title="Русский">
                <svg class="w-6 h-6 rounded-full shadow-sm" viewBox="0 0 512 512">
                  <mask id="ruMask"><circle cx="256" cy="256" r="256" fill="#fff"/></mask>
                  <g mask="url(#ruMask)">
                    <path fill="#fff" d="M0 0h512v170.7H0z"/>
                    <path fill="#0039a6" d="M0 170.7h512v170.6H0z"/>
                    <path fill="#d52b1e" d="M0 341.3h512V512H0z"/>
                  </g>
                </svg>
              </a>
            </div>

            <Link href="/login" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">Kirish</Link>
            <Link href="/register" class="relative group bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-5 py-2.5 rounded-xl font-bold hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg shadow-blue-500/25 hover:shadow-xl hover:shadow-blue-500/30 hover:-translate-y-0.5">
              <span class="absolute -inset-0.5 bg-gradient-to-r from-blue-600 to-violet-600 rounded-xl blur opacity-30 group-hover:opacity-50 transition-opacity"></span>
              <span class="relative">Bepul sinash</span>
            </Link>
          </div>

          <!-- Mobile menu button -->
          <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors">
            <svg class="w-6 h-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
        </div>

        <!-- Mobile Menu -->
        <div v-show="mobileMenuOpen" class="lg:hidden">
          <div class="py-4 space-y-2 border-t border-gray-100">
            <Link href="/#features" class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">Imkoniyatlar</Link>
            <Link href="/#how-it-works" class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">Qanday ishlaydi</Link>
            <Link href="/#benefits" class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">Afzalliklar</Link>
            <span class="block px-4 py-2 text-blue-600 font-semibold bg-blue-50 rounded-lg">Narxlar</span>
            <Link href="/#faq" class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">FAQ</Link>

            <!-- Language switcher mobile -->
            <div class="px-4 py-2 flex items-center justify-center space-x-3">
              <a href="/language/uz" class="flex items-center justify-center w-12 h-12 rounded-xl transition-all bg-blue-100 ring-2 ring-blue-500" title="O'zbekcha">
                <svg class="w-8 h-8 rounded-full shadow-sm" viewBox="0 0 512 512">
                  <mask id="uzMaskM"><circle cx="256" cy="256" r="256" fill="#fff"/></mask>
                  <g mask="url(#uzMaskM)">
                    <path fill="#0099b5" d="M0 0h512v167H0z"/>
                    <path fill="#fff" d="M0 178h512v156H0z"/>
                    <path fill="#1eb53a" d="M0 345h512v167H0z"/>
                    <path fill="#ce1126" d="M0 167h512v11H0zM0 334h512v11H0z"/>
                    <circle cx="163" cy="89" r="45" fill="#fff"/>
                    <circle cx="176" cy="89" r="40" fill="#0099b5"/>
                  </g>
                </svg>
              </a>
              <a href="/language/ru" class="flex items-center justify-center w-12 h-12 rounded-xl transition-all bg-gray-100 hover:bg-gray-200" title="Русский">
                <svg class="w-8 h-8 rounded-full shadow-sm" viewBox="0 0 512 512">
                  <mask id="ruMaskM"><circle cx="256" cy="256" r="256" fill="#fff"/></mask>
                  <g mask="url(#ruMaskM)">
                    <path fill="#fff" d="M0 0h512v170.7H0z"/>
                    <path fill="#0039a6" d="M0 170.7h512v170.6H0z"/>
                    <path fill="#d52b1e" d="M0 341.3h512V512H0z"/>
                  </g>
                </svg>
              </a>
            </div>

            <div class="pt-4 space-y-2 border-t border-gray-100">
              <Link href="/login" class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">Kirish</Link>
              <Link href="/register" class="block mx-4 text-center bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-2.5 rounded-lg font-semibold">Bepul sinash</Link>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Hero Section - Premium Design -->
    <section class="pt-28 lg:pt-36 pb-8 lg:pb-12 relative overflow-hidden">
      <!-- Background -->
      <div class="absolute inset-0 bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/50"></div>
      <div class="absolute top-0 right-0 w-[800px] h-[800px] bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>

      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative">
        <!-- Trust Badge -->
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
          Biznesingizni <span class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 bg-clip-text text-transparent">avtomatlashtiring</span>
          <br class="hidden sm:block" />
          <span class="text-3xl sm:text-4xl lg:text-5xl">shaffof va arzon narxlarda</span>
        </h1>

        <p class="text-lg sm:text-xl text-gray-600 max-w-2xl mx-auto mb-10 leading-relaxed">
          Yashirin to'lovlar yo'q. Istalgan vaqt bekor qilish mumkin.
          <span class="text-blue-600 font-semibold">14 kun bepul sinab ko'ring!</span>
        </p>

        <!-- Billing Toggle - Premium -->
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
              2 OY BEPUL
            </span>
          </button>
        </div>

        <!-- Guarantee Badge -->
        <div class="mt-6 flex items-center justify-center gap-2 text-sm text-gray-500">
          <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          <span>Karta talab qilinmaydi</span>
          <span class="text-gray-300">|</span>
          <span>Istalgan vaqt bekor qilish</span>
          <span class="text-gray-300">|</span>
          <span>24/7 yordam</span>
        </div>
      </div>
    </section>

    <!-- Pricing Cards Section -->
    <section class="py-8 lg:py-12 relative">
      <div class="max-w-[1500px] mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 lg:gap-6">
          <!-- Start Plan -->
          <div class="relative group">
            <div class="h-full bg-white rounded-3xl border-2 border-gray-100 p-6 lg:p-8 transition-all duration-300 hover:border-blue-200 hover:shadow-xl flex flex-col">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center">
                  <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-xl font-bold text-gray-900">Start</h3>
                  <p class="text-sm text-gray-500">Boshlash uchun</p>
                </div>
              </div>

              <div class="mb-6">
                <div class="flex items-baseline gap-1">
                  <span class="text-4xl lg:text-5xl font-extrabold text-gray-900">{{ formatPrice(isYearly ? 249167 : 299000) }}</span>
                  <span class="text-gray-400 font-medium">so'm/oy</span>
                </div>
                <p v-if="isYearly" class="text-sm text-green-600 font-medium mt-1">Yillik to'lovda 599,000 so'm tejaysiz</p>
              </div>

              <ul class="space-y-3 mb-8 flex-1">
                <li v-for="feature in ['2 ta xodim', '1 ta filial', 'Instagram + Telegram bot', '500 ta lid/oy', '60 daqiqa Call Center AI']" :key="feature" class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  <span class="text-gray-600">{{ feature }}</span>
                </li>
              </ul>

              <button @click="selectPlan('start')" class="w-full py-4 px-6 bg-gray-900 text-white font-bold rounded-xl hover:bg-gray-800 transition-all hover:shadow-lg">
                Boshlash
              </button>
            </div>
          </div>

          <!-- Standard Plan -->
          <div class="relative group">
            <div class="h-full bg-white rounded-3xl border-2 border-gray-100 p-6 lg:p-8 transition-all duration-300 hover:border-emerald-200 hover:shadow-xl flex flex-col">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-100 to-emerald-50 flex items-center justify-center">
                  <svg class="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-xl font-bold text-gray-900">Standard</h3>
                  <p class="text-sm text-gray-500">Rivojlanish uchun</p>
                </div>
              </div>

              <div class="mb-6">
                <div class="flex items-baseline gap-1">
                  <span class="text-4xl lg:text-5xl font-extrabold text-gray-900">{{ formatPrice(isYearly ? 499167 : 599000) }}</span>
                  <span class="text-gray-400 font-medium">so'm/oy</span>
                </div>
                <p v-if="isYearly" class="text-sm text-green-600 font-medium mt-1">Yillik to'lovda 1,198,000 so'm tejaysiz</p>
              </div>

              <ul class="space-y-3 mb-8 flex-1">
                <li v-for="feature in ['5 ta xodim', '1 ta filial', 'Flow Builder (Vizual)', '2,000 ta lid/oy', '150 daqiqa Call Center AI']" :key="feature" class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  <span class="text-gray-600">{{ feature }}</span>
                </li>
              </ul>

              <button @click="selectPlan('standard')" class="w-full py-4 px-6 bg-gray-900 text-white font-bold rounded-xl hover:bg-gray-800 transition-all hover:shadow-lg">
                Tanlash
              </button>
            </div>
          </div>

          <!-- Business Plan - POPULAR -->
          <div class="relative group xl:-mt-4 xl:mb-4">
            <!-- Glow effect -->
            <div class="absolute -inset-[2px] bg-gradient-to-b from-purple-500 via-violet-500 to-purple-600 rounded-[26px] opacity-100"></div>

            <div class="relative h-full bg-white rounded-3xl p-6 lg:p-8 flex flex-col">
              <!-- Popular Badge -->
              <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-purple-600 to-violet-600 text-white text-sm font-bold rounded-full shadow-lg shadow-purple-500/30">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                  ENG FOYDALI
                </span>
              </div>

              <div class="flex items-center gap-3 mb-6 mt-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-100 to-purple-50 flex items-center justify-center">
                  <svg class="w-6 h-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-xl font-bold text-gray-900">Business</h3>
                  <p class="text-sm text-gray-500">Tizimlashish uchun</p>
                </div>
              </div>

              <div class="mb-6">
                <div class="flex items-baseline gap-1">
                  <span class="text-4xl lg:text-5xl font-extrabold text-gray-900">{{ formatPrice(isYearly ? 665833 : 799000) }}</span>
                  <span class="text-gray-400 font-medium">so'm/oy</span>
                </div>
                <p v-if="isYearly" class="text-sm text-green-600 font-medium mt-1">Yillik to'lovda 1,598,000 so'm tejaysiz</p>
                <p v-else class="text-sm text-purple-600 font-medium mt-1">Eng ko'p tanlanadigan tarif</p>
              </div>

              <ul class="space-y-3 mb-8 flex-1">
                <li v-for="feature in ['10 ta xodim', '2 ta filial', 'HR Bot + Marketing ROI', '10,000 ta lid/oy', '400 daqiqa Call Center AI']" :key="feature" class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-purple-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  <span class="text-gray-700 font-medium">{{ feature }}</span>
                </li>
              </ul>

              <button @click="selectPlan('business')" class="w-full py-4 px-6 bg-gradient-to-r from-purple-600 to-violet-600 text-white font-bold rounded-xl hover:from-purple-700 hover:to-violet-700 transition-all shadow-lg shadow-purple-500/25 hover:shadow-xl hover:shadow-purple-500/30 hover:-translate-y-0.5">
                Bepul Boshlash
              </button>
            </div>
          </div>

          <!-- Premium Plan -->
          <div class="relative group">
            <div class="absolute -inset-[2px] bg-gradient-to-b from-amber-400 via-orange-500 to-amber-500 rounded-[26px] opacity-100"></div>

            <div class="relative h-full bg-white rounded-3xl p-6 lg:p-8 flex flex-col">
              <!-- VIP Badge -->
              <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-sm font-bold rounded-full shadow-lg shadow-amber-500/30">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd" /></svg>
                  VIP
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
                  <p class="text-sm text-gray-500">Masshtablash uchun</p>
                </div>
              </div>

              <div class="mb-6">
                <div class="flex items-baseline gap-1">
                  <span class="text-4xl lg:text-5xl font-extrabold text-gray-900">{{ formatPrice(isYearly ? 1249167 : 1499000) }}</span>
                  <span class="text-gray-400 font-medium">so'm/oy</span>
                </div>
                <p v-if="isYearly" class="text-sm text-green-600 font-medium mt-1">Yillik to'lovda 2,998,000 so'm tejaysiz</p>
              </div>

              <ul class="space-y-3 mb-8 flex-1">
                <li v-for="feature in ['15 ta xodim', '5 ta filial', 'AI Bot + Anti-Fraud', 'Cheksiz lid', '1,000 daqiqa Call Center AI']" :key="feature" class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  <span class="text-gray-700 font-medium">{{ feature }}</span>
                </li>
              </ul>

              <button @click="selectPlan('premium')" class="w-full py-4 px-6 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-bold rounded-xl hover:from-amber-600 hover:to-orange-600 transition-all shadow-lg shadow-amber-500/25 hover:shadow-xl">
                Premium olish
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Trust Badges -->
    <section class="py-8 border-y border-gray-100">
      <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-wrap items-center justify-center gap-x-10 gap-y-4">
          <div class="flex items-center gap-2 text-gray-600">
            <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span class="font-medium">SSL himoya</span>
          </div>
          <div class="flex items-center gap-2 text-gray-600">
            <svg class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
              <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
              <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
            </svg>
            <span class="font-medium">Click & Payme</span>
          </div>
          <div class="flex items-center gap-2 text-gray-600">
            <svg class="w-6 h-6 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
            </svg>
            <span class="font-medium">24/7 Telegram yordam</span>
          </div>
          <div class="flex items-center gap-2 text-gray-600">
            <svg class="w-6 h-6 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
            </svg>
            <span class="font-medium">14 kun bepul sinov</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Comparison Table -->
    <section class="py-16 lg:py-20 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <span class="inline-block px-4 py-1.5 bg-indigo-100 text-indigo-700 rounded-full text-sm font-semibold mb-4">Batafsil taqqoslash</span>
          <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">Barcha imkoniyatlarni solishtiring</h2>
          <p class="text-lg text-gray-600">Har bir tarifda nimalar bor - batafsil jadval</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-lg overflow-hidden">
          <ComparisonTable :plans="plans" />
        </div>
      </div>
    </section>

    <!-- Social Proof Section -->
    <section class="py-16 lg:py-20 bg-gradient-to-b from-white to-gray-50">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <span class="inline-block px-4 py-1.5 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold mb-4">Muvaffaqiyatli mijozlar</span>
          <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">500+ kompaniya BiznesPilot bilan o'sdi</h2>
          <p class="text-lg text-gray-600">Ular qanday natijaga erishdi?</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6 lg:gap-8">
          <div class="bg-white rounded-2xl p-6 lg:p-8 shadow-lg shadow-gray-200/50 border border-gray-100">
            <div class="flex items-center gap-1 text-amber-400 mb-4">
              <svg v-for="i in 5" :key="i" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            </div>
            <p class="text-gray-700 mb-6 leading-relaxed">"BiznesPilot orqali Instagram so'rovlarini 3x tezroq qayta ishlay boshladik. Sotuvlar 40% oshdi!"</p>
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white font-bold">AS</div>
              <div>
                <p class="font-semibold text-gray-900">Aziz Soliyev</p>
                <p class="text-sm text-gray-500">TechMart.uz, CEO</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 lg:p-8 shadow-lg shadow-gray-200/50 border border-gray-100">
            <div class="flex items-center gap-1 text-amber-400 mb-4">
              <svg v-for="i in 5" :key="i" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            </div>
            <p class="text-gray-700 mb-6 leading-relaxed">"Call Center AI juda foydali! Endi har bir qo'ng'iroqni tahlil qilib, xodimlarni o'qitish osonlashdi."</p>
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center text-white font-bold">NK</div>
              <div>
                <p class="font-semibold text-gray-900">Nodira Karimova</p>
                <p class="text-sm text-gray-500">MediPlus, Marketing Director</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 lg:p-8 shadow-lg shadow-gray-200/50 border border-gray-100">
            <div class="flex items-center gap-1 text-amber-400 mb-4">
              <svg v-for="i in 5" :key="i" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            </div>
            <p class="text-gray-700 mb-6 leading-relaxed">"HR Bot bilan yangi xodimlarni 50% tezroq topamiz. Vaqtimiz tejaldi, sifat oshdi!"</p>
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold">JR</div>
              <div>
                <p class="font-semibold text-gray-900">Jasur Rahimov</p>
                <p class="text-sm text-gray-500">BuildPro, HR Manager</p>
              </div>
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
    <section class="py-20 lg:py-24 bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-700 relative overflow-hidden">
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-0 w-full h-full" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
      </div>

      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-white/5 rounded-full blur-3xl"></div>

      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full border border-white/20 mb-8">
          <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
          <span class="text-white/90 text-sm font-medium">500+ kompaniya allaqachon foydalanmoqda</span>
        </div>

        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-6 leading-tight">
          Biznesingizni bugundan <br class="hidden sm:block" />avtomatlashtirishni boshlang
        </h2>
        <p class="text-xl text-blue-100 mb-10 max-w-2xl mx-auto">
          14 kunlik bepul sinov. Karta talab qilinmaydi. Istalgan vaqt bekor qilish mumkin.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
          <Link href="/register" class="inline-flex items-center px-8 py-4 bg-white text-blue-600 text-lg font-bold rounded-xl hover:bg-blue-50 transition-all shadow-2xl shadow-black/20 hover:shadow-3xl transform hover:-translate-y-1">
            <span>Bepul Boshlash</span>
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </Link>
          <a href="https://t.me/biznespilot_support" target="_blank" class="inline-flex items-center px-8 py-4 bg-white/10 backdrop-blur-sm text-white text-lg font-semibold rounded-xl hover:bg-white/20 transition-all border border-white/20">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
              <path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0a12 12 0 00-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
            </svg>
            Demo olish
          </a>
        </div>

        <!-- Trust badges -->
        <div class="mt-12 flex flex-wrap items-center justify-center gap-6 text-white/70 text-sm">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>Ma'lumotlar xavfsiz</span>
          </div>
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
              <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
            </svg>
            <span>Qulay to'lov</span>
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

    <!-- Footer -->
    <footer class="relative bg-gray-900 text-gray-400 overflow-hidden">
      <!-- Background decorations -->
      <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-blue-500 rounded-full filter blur-[100px] opacity-10"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-violet-500 rounded-full filter blur-[100px] opacity-10"></div>
      </div>

      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-12">
        <!-- Main footer content -->
        <div class="grid md:grid-cols-2 lg:grid-cols-5 gap-12 mb-16">
          <!-- Brand -->
          <div class="lg:col-span-2">
            <Link href="/" class="flex items-center space-x-3 mb-6 group">
              <div class="relative">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300">
                  <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                </div>
                <div class="absolute inset-0 w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl filter blur-lg opacity-50 group-hover:opacity-70 transition-opacity"></div>
              </div>
              <span class="text-2xl font-black text-white">BiznesPilot</span>
            </Link>
            <p class="text-gray-400 mb-8 max-w-sm leading-relaxed">
              Instagram va Telegram savdolarini avtomatlashtiruvchi yagona platforma. CRM, chatbot va AI tahlil bir joyda.
            </p>

            <!-- Social links -->
            <div class="flex space-x-3">
              <a href="https://t.me/biznespilot" target="_blank" class="group relative w-11 h-11 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-[#0088cc] transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-[#0088cc] to-[#0077b5] opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <svg class="relative w-5 h-5 text-gray-400 group-hover:text-white transition-colors" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                </svg>
              </a>
              <a href="https://instagram.com/biznespilot" target="_blank" class="group relative w-11 h-11 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-[#E4405F] transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-[#833AB4] via-[#E4405F] to-[#FCAF45] opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <svg class="relative w-5 h-5 text-gray-400 group-hover:text-white transition-colors" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                </svg>
              </a>
              <a href="https://youtube.com/@biznespilot" target="_blank" class="group relative w-11 h-11 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-[#FF0000] transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-[#FF0000] to-[#CC0000] opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <svg class="relative w-5 h-5 text-gray-400 group-hover:text-white transition-colors" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                </svg>
              </a>
              <a href="https://linkedin.com/company/biznespilot" target="_blank" class="group relative w-11 h-11 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-[#0A66C2] transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-[#0A66C2] to-[#004182] opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <svg class="relative w-5 h-5 text-gray-400 group-hover:text-white transition-colors" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                </svg>
              </a>
            </div>
          </div>

          <!-- Product -->
          <div>
            <h4 class="text-white font-bold mb-6 flex items-center">
              <span class="w-8 h-0.5 bg-gradient-to-r from-blue-500 to-indigo-500 mr-3"></span>
              Mahsulot
            </h4>
            <ul class="space-y-4">
              <li>
                <Link href="/#features" class="group flex items-center text-gray-400 hover:text-white transition-colors">
                  <svg class="w-4 h-4 mr-2 text-gray-600 group-hover:text-blue-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                  Imkoniyatlar
                </Link>
              </li>
              <li>
                <a href="#" class="group flex items-center text-gray-400 hover:text-white transition-colors">
                  <svg class="w-4 h-4 mr-2 text-gray-600 group-hover:text-blue-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                  Integratsiyalar
                </a>
              </li>
              <li>
                <a href="#" class="group flex items-center text-gray-400 hover:text-white transition-colors">
                  <svg class="w-4 h-4 mr-2 text-gray-600 group-hover:text-blue-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                  Yangiliklar
                </a>
              </li>
            </ul>
          </div>

          <!-- Company -->
          <div>
            <h4 class="text-white font-bold mb-6 flex items-center">
              <span class="w-8 h-0.5 bg-gradient-to-r from-emerald-500 to-teal-500 mr-3"></span>
              Kompaniya
            </h4>
            <ul class="space-y-4">
              <li>
                <Link href="/about" class="group flex items-center text-gray-400 hover:text-white transition-colors">
                  <svg class="w-4 h-4 mr-2 text-gray-600 group-hover:text-emerald-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                  Biz haqimizda
                </Link>
              </li>
              <li>
                <Link href="/about#contact" class="group flex items-center text-gray-400 hover:text-white transition-colors">
                  <svg class="w-4 h-4 mr-2 text-gray-600 group-hover:text-emerald-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                  Aloqa
                </Link>
              </li>
              <li>
                <a href="https://t.me/biznespilot" target="_blank" class="group flex items-center text-gray-400 hover:text-white transition-colors">
                  <svg class="w-4 h-4 mr-2 text-gray-600 group-hover:text-emerald-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                  Vakansiyalar
                </a>
              </li>
            </ul>
          </div>

          <!-- Legal -->
          <div>
            <h4 class="text-white font-bold mb-6 flex items-center">
              <span class="w-8 h-0.5 bg-gradient-to-r from-violet-500 to-purple-500 mr-3"></span>
              Huquqiy
            </h4>
            <ul class="space-y-4">
              <li>
                <Link href="/privacy-policy" class="group flex items-center text-gray-400 hover:text-white transition-colors">
                  <svg class="w-4 h-4 mr-2 text-gray-600 group-hover:text-violet-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                  Maxfiylik siyosati
                </Link>
              </li>
              <li>
                <Link href="/terms" class="group flex items-center text-gray-400 hover:text-white transition-colors">
                  <svg class="w-4 h-4 mr-2 text-gray-600 group-hover:text-violet-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                  Foydalanish shartlari
                </Link>
              </li>
            </ul>
          </div>
        </div>

        <!-- Bottom bar -->
        <div class="pt-8 border-t border-gray-800">
          <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-gray-500 text-sm">
              © {{ new Date().getFullYear() }} BiznesPilot. Barcha huquqlar himoyalangan.
            </p>

            <div class="flex items-center gap-6">
              <!-- Language indicator -->
              <div class="flex items-center gap-2 text-sm text-gray-500">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
                <span>O'zbekcha</span>
              </div>

              <!-- Made in Uzbekistan -->
              <div class="flex items-center gap-2 text-gray-500 text-sm">
                <span>Made with</span>
                <svg class="w-4 h-4 text-red-500 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                </svg>
                <span>in</span>
                <span class="flex items-center gap-1">
                  <span class="w-4 h-3 rounded-sm overflow-hidden flex flex-col">
                    <span class="flex-1 bg-[#0099B5]"></span>
                    <span class="h-[2px] bg-[#CE1126]"></span>
                    <span class="flex-1 bg-white"></span>
                    <span class="h-[2px] bg-[#CE1126]"></span>
                    <span class="flex-1 bg-[#1EB53A]"></span>
                  </span>
                  <span class="font-medium text-white">Uzbekistan</span>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import ComparisonTable from '@/components/pricing/ComparisonTable.vue';

const isYearly = ref(false);
const openFaq = ref(null);
const mobileMenuOpen = ref(false);

const formatPrice = (price) => {
  return Math.round(price).toLocaleString('uz-UZ');
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

const faqs = [
  {
    question: 'Botni o\'zim sozlay olamanmi?',
    answer: 'Ha, albatta! BiznesPilot\'da oddiy drag-and-drop Flow Builder mavjud. Hech qanday dasturlash bilimi talab qilinmaydi. Video qo\'llanmalar va Telegram support ham bor.'
  },
  {
    question: 'Call Center AI daqiqalari tugasa nima bo\'ladi?',
    answer: 'Daqiqalar tugagandan so\'ng, qo\'shimcha daqiqalar sotib olishingiz yoki keyingi oyga kutishingiz mumkin - limitlar avtomatik yangilanadi.'
  },
  {
    question: 'To\'lov turlari qanday?',
    answer: 'Click, Payme, bank o\'tkazmasi va naqd to\'lov qabul qilamiz. Korporativ mijozlar uchun shartnoma va schet-faktura tayyorlaymiz.'
  },
  {
    question: 'Tarif o\'zgartirsa bo\'ladimi?',
    answer: 'Istalgan vaqt yuqoriroq tarifga o\'tishingiz mumkin. To\'langan summa proporsional hisoblanadi. Pastroq tarifga o\'tish esa keyingi to\'lov davridan boshlab amalga oshadi.'
  },
  {
    question: 'Ma\'lumotlarim xavfsizmi?',
    answer: 'Ha, biz SSL shifrlash, kunlik backup va serverlarimiz Yevropa data-centerlarida joylashgan. GDPR talablariga muvofiq ishlaymiz.'
  },
  {
    question: 'Bekor qilsam nima bo\'ladi?',
    answer: 'Istalgan vaqt bekor qilishingiz mumkin. To\'langan davr tugagunicha tizimdan foydalanishingiz mumkin. Ma\'lumotlaringizni eksport qilish imkoniyati bor.'
  }
];

const toggleFaq = (index) => {
  openFaq.value = openFaq.value === index ? null : index;
};
</script>
