<template>
  <div class="min-h-screen bg-slate-50">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-b border-slate-200/50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <!-- Logo -->
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-indigo-700 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/25">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <span class="text-xl font-bold text-slate-900">BiznesPilot</span>
          </div>

          <!-- Desktop Navigation -->
          <div class="hidden md:flex items-center space-x-8">
            <a href="#modules" class="text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">Imkoniyatlar</a>
            <a href="#pricing" class="text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">Narxlar</a>
            <Link href="/pricing" class="text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">Taqqoslash</Link>
          </div>

          <!-- CTA Buttons -->
          <div class="flex items-center space-x-3">
            <Link href="/login" class="hidden sm:inline-flex text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
              Kirish
            </Link>
            <Link
              href="/register"
              class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/25 hover:shadow-xl hover:shadow-indigo-500/30"
            >
              Bepul Boshlash
            </Link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 lg:pt-40 lg:pb-32 overflow-hidden">
      <!-- Background -->
      <div class="absolute inset-0 bg-gradient-to-br from-slate-50 via-white to-indigo-50"></div>
      <div class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-[600px] h-[600px] bg-indigo-200 rounded-full opacity-20 blur-3xl"></div>
      <div class="absolute bottom-0 left-0 translate-y-1/4 -translate-x-1/4 w-[500px] h-[500px] bg-indigo-300 rounded-full opacity-15 blur-3xl"></div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-center">
          <!-- Left Content -->
          <div class="text-center lg:text-left">
            <!-- Badge -->
            <div class="inline-flex items-center px-4 py-2 bg-indigo-100 text-indigo-700 rounded-full text-sm font-medium mb-6">
              <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
              50+ biznes allaqachon ishlatmoqda
            </div>

            <!-- Headline -->
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-slate-900 leading-tight mb-6">
              Biznesingizni
              <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-indigo-500">Avtopilotga</span>
              qo'ying.
            </h1>

            <!-- Subheadline -->
            <p class="text-lg sm:text-xl text-slate-600 mb-8 leading-relaxed max-w-xl mx-auto lg:mx-0">
              Sotuv, Marketing, HR va Call Center — barchasi bitta tizimda.
              <span class="text-slate-900 font-medium">Xodimlaringiz ishlamasa ham, Tizim ishlaydi.</span>
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 mb-8">
              <Link
                href="/register"
                class="group w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 bg-indigo-600 text-white text-lg font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-500/30 hover:shadow-2xl hover:shadow-indigo-500/40 hover:scale-[1.02]"
              >
                Bepul Boshlash
                <svg class="w-5 h-5 ml-2 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
              </Link>
              <button
                @click="openDemo"
                class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 bg-white text-slate-700 text-lg font-semibold rounded-xl border-2 border-slate-200 hover:border-slate-300 hover:bg-slate-50 transition-all"
              >
                <svg class="w-5 h-5 mr-2 text-indigo-600" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z"/>
                </svg>
                Demoni Ko'rish
              </button>
            </div>

            <!-- Trust Indicators -->
            <div class="flex flex-wrap items-center justify-center lg:justify-start gap-6 text-sm text-slate-500">
              <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Karta talab qilinmaydi
              </div>
              <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                14 kun bepul sinov
              </div>
              <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Istalgan vaqt bekor qilish
              </div>
            </div>
          </div>

          <!-- Right Visual - Dashboard Mockup -->
          <div class="relative">
            <div class="relative z-10">
              <!-- Dashboard Frame -->
              <div class="bg-white rounded-2xl shadow-2xl shadow-slate-900/10 border border-slate-200/50 overflow-hidden">
                <!-- Browser Bar -->
                <div class="flex items-center gap-2 px-4 py-3 bg-slate-100 border-b border-slate-200">
                  <div class="flex gap-1.5">
                    <div class="w-3 h-3 rounded-full bg-red-400"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                    <div class="w-3 h-3 rounded-full bg-green-400"></div>
                  </div>
                  <div class="flex-1 flex justify-center">
                    <div class="px-4 py-1 bg-white rounded-lg text-xs text-slate-400 border border-slate-200">
                      app.biznespilot.uz
                    </div>
                  </div>
                </div>

                <!-- Dashboard Content -->
                <div class="p-6 bg-slate-50">
                  <!-- Stats Row -->
                  <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="bg-white p-4 rounded-xl border border-slate-200">
                      <div class="text-2xl font-bold text-slate-900">156</div>
                      <div class="text-xs text-slate-500">Yangi Lidlar</div>
                      <div class="text-xs text-green-600 font-medium mt-1">+23%</div>
                    </div>
                    <div class="bg-white p-4 rounded-xl border border-slate-200">
                      <div class="text-2xl font-bold text-slate-900">89%</div>
                      <div class="text-xs text-slate-500">Konversiya</div>
                      <div class="text-xs text-green-600 font-medium mt-1">+12%</div>
                    </div>
                    <div class="bg-white p-4 rounded-xl border border-slate-200">
                      <div class="text-2xl font-bold text-slate-900">24/7</div>
                      <div class="text-xs text-slate-500">AI Chatbot</div>
                      <div class="text-xs text-indigo-600 font-medium mt-1">Online</div>
                    </div>
                  </div>

                  <!-- Chat Preview -->
                  <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <div class="flex items-center gap-3 mb-4">
                      <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-rose-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                          <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073z"/>
                        </svg>
                      </div>
                      <div>
                        <div class="text-sm font-medium text-slate-900">Instagram Chatbot</div>
                        <div class="text-xs text-green-600">3 ta yangi xabar</div>
                      </div>
                    </div>

                    <!-- Chat Messages -->
                    <div class="space-y-3">
                      <div class="flex justify-end">
                        <div class="bg-indigo-600 text-white text-sm px-4 py-2 rounded-2xl rounded-br-md max-w-[200px]">
                          Narxi qancha?
                        </div>
                      </div>
                      <div class="flex">
                        <div class="bg-slate-100 text-slate-700 text-sm px-4 py-2 rounded-2xl rounded-bl-md max-w-[220px]">
                          Salom! Bizda 3 ta tarif bor. Qaysi biri sizga mos?
                          <div class="flex gap-2 mt-2">
                            <span class="px-2 py-1 bg-white rounded text-xs border border-slate-200">Start</span>
                            <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded text-xs">Biznes</span>
                            <span class="px-2 py-1 bg-white rounded text-xs border border-slate-200">Premium</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Floating Elements -->
            <div class="absolute -top-4 -right-4 bg-white p-3 rounded-xl shadow-lg border border-slate-200 animate-float">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                  <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <div class="text-sm font-medium text-slate-900">Lead yaratildi!</div>
              </div>
            </div>

            <div class="absolute -bottom-4 -left-4 bg-white p-3 rounded-xl shadow-lg border border-slate-200 animate-float-delayed">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                  <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                </div>
                <div class="text-sm font-medium text-slate-900">AI tahlil</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Pain Points Section -->
    <section class="py-20 bg-white" id="problems">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <span class="inline-block px-4 py-2 bg-red-100 text-red-600 rounded-full text-sm font-medium mb-4">Tanish muammolar</span>
          <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">Bu muammolar tanishmi?</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <!-- Pain Point 1 -->
          <div class="group p-8 bg-gradient-to-br from-red-50 to-orange-50 rounded-2xl border border-red-100 hover:shadow-lg transition-all">
            <div class="w-14 h-14 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-3">Mijozlar javob kutaverib ketib qolyaptimi?</h3>
            <p class="text-slate-600">Instagram va Telegram xabarlariga tez javob bera olmayapsiz. Mijozlar raqobatchilarga ketib qolmoqda.</p>
          </div>

          <!-- Pain Point 2 -->
          <div class="group p-8 bg-gradient-to-br from-amber-50 to-yellow-50 rounded-2xl border border-amber-100 hover:shadow-lg transition-all">
            <div class="w-14 h-14 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-3">Xodimlar telefonda nima deyayotganini bilmaysizmi?</h3>
            <p class="text-slate-600">Operatorlar mijozlar bilan qanday gaplashayotganini nazorat qilib bo'lmaydi. Sifat tushib ketmoqda.</p>
          </div>

          <!-- Pain Point 3 -->
          <div class="group p-8 bg-gradient-to-br from-purple-50 to-indigo-50 rounded-2xl border border-purple-100 hover:shadow-lg transition-all">
            <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-3">Qaysi reklama pul keltirayotganini bilmaysizmi?</h3>
            <p class="text-slate-600">Reklama byudjetini sarflaysiz, lekin qaysi kanal ishlayotganini aniq bilmaysiz. Pul isrof bo'lmoqda.</p>
          </div>
        </div>

        <!-- Arrow to solution -->
        <div class="text-center mt-12">
          <div class="inline-flex flex-col items-center">
            <svg class="w-8 h-8 text-indigo-500 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
            </svg>
            <span class="mt-2 text-indigo-600 font-semibold">Barchasiga yechim bor</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Features/Modules Section -->
    <section class="py-20 bg-slate-50" id="modules">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <span class="inline-block px-4 py-2 bg-indigo-100 text-indigo-600 rounded-full text-sm font-medium mb-4">4 ta Modul</span>
          <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">Bitta platformada hamma narsa</h2>
          <p class="text-lg text-slate-600 max-w-2xl mx-auto">Har bir modul alohida muammoni hal qiladi va bir-biri bilan integratsiya qilingan</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Module 1: Smart Chatbot -->
          <div class="group bg-white rounded-2xl p-8 shadow-sm border border-slate-200 hover:shadow-xl hover:border-indigo-200 transition-all duration-300">
            <div class="flex items-start gap-6">
              <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-rose-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-pink-500/25 group-hover:scale-110 transition-transform">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
              </div>
              <div class="flex-1">
                <h3 class="text-xl font-bold text-slate-900 mb-2">Smart Chatbot</h3>
                <p class="text-slate-600 mb-4">Instagram va Telegramda 24/7 avto-sotuv. Mijozlarga tugmali menyular orqali javob bering.</p>
                <ul class="space-y-2">
                  <li class="flex items-center gap-2 text-sm text-slate-600">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Tugmali menyular
                  </li>
                  <li class="flex items-center gap-2 text-sm text-slate-600">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Avto-lead yaratish
                  </li>
                  <li class="flex items-center gap-2 text-sm text-slate-600">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Operatorga uzatish
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Module 2: Marketing ROI -->
          <div class="group bg-white rounded-2xl p-8 shadow-sm border border-slate-200 hover:shadow-xl hover:border-indigo-200 transition-all duration-300">
            <div class="flex items-start gap-6">
              <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-500/25 group-hover:scale-110 transition-transform">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
              <div class="flex-1">
                <h3 class="text-xl font-bold text-slate-900 mb-2">Marketing ROI</h3>
                <p class="text-slate-600 mb-4">Qaysi reklama pul keltirayotganini real vaqtda ko'ring. Byudjetni samarali sarflang.</p>
                <ul class="space-y-2">
                  <li class="flex items-center gap-2 text-sm text-slate-600">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Kanal bo'yicha ROI
                  </li>
                  <li class="flex items-center gap-2 text-sm text-slate-600">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    UTM tracking
                  </li>
                  <li class="flex items-center gap-2 text-sm text-slate-600">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Kontent performance
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Module 3: HR & Jamoa -->
          <div class="group bg-white rounded-2xl p-8 shadow-sm border border-slate-200 hover:shadow-xl hover:border-indigo-200 transition-all duration-300">
            <div class="flex items-start gap-6">
              <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-emerald-500/25 group-hover:scale-110 transition-transform">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>
              <div class="flex-1">
                <h3 class="text-xl font-bold text-slate-900 mb-2">HR & Jamoa</h3>
                <p class="text-slate-600 mb-4">Xodimlarni boshqaring, KPI qo'ying va samaradorlikni nazorat qiling.</p>
                <ul class="space-y-2">
                  <li class="flex items-center gap-2 text-sm text-slate-600">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Rol va ruxsatlar
                  </li>
                  <li class="flex items-center gap-2 text-sm text-slate-600">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    KPI dashboard
                  </li>
                  <li class="flex items-center gap-2 text-sm text-slate-600">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Team leaderboard
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Module 4: AI Call Center -->
          <div class="group bg-white rounded-2xl p-8 shadow-sm border border-slate-200 hover:shadow-xl hover:border-indigo-200 transition-all duration-300">
            <div class="flex items-start gap-6">
              <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-amber-500/25 group-hover:scale-110 transition-transform">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
              </div>
              <div class="flex-1">
                <h3 class="text-xl font-bold text-slate-900 mb-2">AI Call Center</h3>
                <p class="text-slate-600 mb-4">Sun'iy intellekt suhbatlarni tahlil qiladi va operatorlarni baholaydi.</p>
                <ul class="space-y-2">
                  <li class="flex items-center gap-2 text-sm text-slate-600">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Suhbat transkripsiyasi
                  </li>
                  <li class="flex items-center gap-2 text-sm text-slate-600">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    AI baholash (0-100)
                  </li>
                  <li class="flex items-center gap-2 text-sm text-slate-600">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Tavsiyalar berish
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Mini Pricing Section -->
    <section class="py-20 bg-white" id="pricing">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <span class="inline-block px-4 py-2 bg-green-100 text-green-600 rounded-full text-sm font-medium mb-4">Shaffof narxlar</span>
          <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">Biznesingizga mos tarifni tanlang</h2>
          <p class="text-lg text-slate-600">Barcha tariflar 14 kunlik bepul sinov bilan boshlanadi</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
          <!-- Start Plan -->
          <div class="relative bg-white rounded-2xl p-8 border-2 border-slate-200 hover:border-slate-300 transition-all">
            <div class="mb-6">
              <h3 class="text-xl font-bold text-slate-900 mb-2">Start</h3>
              <p class="text-slate-500 text-sm">Kichik jamoa uchun</p>
            </div>
            <div class="mb-6">
              <span class="text-4xl font-bold text-slate-900">299,000</span>
              <span class="text-slate-500">so'm/oy</span>
            </div>
            <ul class="space-y-3 mb-8">
              <li class="flex items-center gap-2 text-sm text-slate-600">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                1 ta Instagram account
              </li>
              <li class="flex items-center gap-2 text-sm text-slate-600">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                CRM va Kanban doska
              </li>
              <li class="flex items-center gap-2 text-sm text-slate-600">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                2 ta foydalanuvchi
              </li>
            </ul>
            <Link href="/register" class="block w-full text-center px-6 py-3 bg-slate-100 text-slate-700 font-medium rounded-xl hover:bg-slate-200 transition-colors">
              Boshlash
            </Link>
          </div>

          <!-- Business Plan (Popular) -->
          <div class="relative bg-indigo-600 rounded-2xl p-8 border-2 border-indigo-600 shadow-2xl shadow-indigo-500/25 scale-105 z-10">
            <!-- Popular Badge -->
            <div class="absolute -top-4 left-1/2 -translate-x-1/2">
              <span class="inline-flex items-center px-4 py-1.5 bg-amber-400 text-amber-900 text-sm font-bold rounded-full shadow-lg">
                ENG OMMABOP
              </span>
            </div>

            <div class="mb-6">
              <h3 class="text-xl font-bold text-white mb-2">Biznes</h3>
              <p class="text-indigo-200 text-sm">O'sib kelayotgan kompaniyalar uchun</p>
            </div>
            <div class="mb-6">
              <span class="text-4xl font-bold text-white">799,000</span>
              <span class="text-indigo-200">so'm/oy</span>
            </div>
            <ul class="space-y-3 mb-8">
              <li class="flex items-center gap-2 text-sm text-indigo-100">
                <svg class="w-5 h-5 text-indigo-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                3 ta Instagram account
              </li>
              <li class="flex items-center gap-2 text-sm text-indigo-100">
                <svg class="w-5 h-5 text-indigo-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Marketing ROI + HR modul
              </li>
              <li class="flex items-center gap-2 text-sm text-indigo-100">
                <svg class="w-5 h-5 text-indigo-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                10 ta foydalanuvchi
              </li>
            </ul>
            <Link href="/register" class="block w-full text-center px-6 py-3 bg-white text-indigo-600 font-semibold rounded-xl hover:bg-indigo-50 transition-colors shadow-lg">
              Bepul Boshlash
            </Link>
          </div>

          <!-- Premium Plan -->
          <div class="relative bg-slate-900 rounded-2xl p-8 border-2 border-slate-700">
            <div class="mb-6">
              <h3 class="text-xl font-bold text-white mb-2">Premium</h3>
              <p class="text-slate-400 text-sm">Katta kompaniyalar uchun</p>
            </div>
            <div class="mb-6">
              <span class="text-4xl font-bold text-white">1,499,000</span>
              <span class="text-slate-400">so'm/oy</span>
            </div>
            <ul class="space-y-3 mb-8">
              <li class="flex items-center gap-2 text-sm text-slate-300">
                <svg class="w-5 h-5 text-amber-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                10 ta Instagram account
              </li>
              <li class="flex items-center gap-2 text-sm text-slate-300">
                <svg class="w-5 h-5 text-amber-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                AI Call Center (50 ta/oy)
              </li>
              <li class="flex items-center gap-2 text-sm text-slate-300">
                <svg class="w-5 h-5 text-amber-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Cheksiz foydalanuvchi
              </li>
            </ul>
            <Link href="/register" class="block w-full text-center px-6 py-3 bg-amber-400 text-slate-900 font-semibold rounded-xl hover:bg-amber-300 transition-colors">
              Premium olish
            </Link>
          </div>
        </div>

        <!-- Link to full pricing -->
        <div class="text-center mt-12">
          <Link href="/pricing" class="inline-flex items-center text-indigo-600 hover:text-indigo-700 font-medium">
            Barcha imkoniyatlarni solishtirish
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
          </Link>
        </div>
      </div>
    </section>

    <!-- Testimonials -->
    <section class="py-20 bg-gradient-to-r from-indigo-600 to-indigo-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <span class="inline-block px-4 py-2 bg-white/20 text-white rounded-full text-sm font-medium mb-4">Mijozlar fikri</span>
          <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">Ular allaqachon ishlatmoqda</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
            <div class="flex items-center gap-1 mb-4">
              <svg v-for="i in 5" :key="i" class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </div>
            <p class="text-white/90 mb-4">"Chatbot orqali 40% ko'proq lead yig'a boshladik. Endi mijozlar tungi paytda ham javob oladi."</p>
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-emerald-500 rounded-full flex items-center justify-center text-white font-bold">AK</div>
              <div>
                <p class="text-white font-medium">Akmal Karimov</p>
                <p class="text-white/60 text-sm">TechShop.uz</p>
              </div>
            </div>
          </div>

          <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
            <div class="flex items-center gap-1 mb-4">
              <svg v-for="i in 5" :key="i" class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </div>
            <p class="text-white/90 mb-4">"Marketing ROI moduli tufayli reklama byudjetini 30% kamaytirdik, lekin sotuvlar oshdi."</p>
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-pink-500 rounded-full flex items-center justify-center text-white font-bold">MR</div>
              <div>
                <p class="text-white font-medium">Malika Rahimova</p>
                <p class="text-white/60 text-sm">BeautyBox UZ</p>
              </div>
            </div>
          </div>

          <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
            <div class="flex items-center gap-1 mb-4">
              <svg v-for="i in 5" :key="i" class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </div>
            <p class="text-white/90 mb-4">"Call Center AI operatorlarimizni o'qitishga yordam berdi. Suhbat sifati sezilarli yaxshilandi."</p>
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold">GK</div>
              <div>
                <p class="text-white font-medium">G'affor Kamolov</p>
                <p class="text-white/60 text-sm">CallPro Agency</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Final CTA -->
    <section class="py-20 bg-white">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="bg-gradient-to-br from-slate-50 to-indigo-50 rounded-3xl p-12 border border-slate-200">
          <div class="inline-flex items-center px-4 py-2 bg-green-100 text-green-700 rounded-full text-sm font-medium mb-6">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            14 kun bepul sinov
          </div>
          <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">
            Hoziroq <span class="text-indigo-600">bepul</span> boshlang
          </h2>
          <p class="text-xl text-slate-600 mb-8">
            Karta talab qilinmaydi. 2 daqiqada ro'yxatdan o'ting.
          </p>
          <Link
            href="/register"
            class="group inline-flex items-center px-10 py-5 bg-indigo-600 text-white text-lg font-semibold rounded-2xl hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-500/30 hover:shadow-2xl hover:shadow-indigo-500/40 hover:scale-[1.02]"
          >
            Bepul Boshlash
            <svg class="w-5 h-5 ml-3 transition-transform group-hover:translate-x-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </Link>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 text-white py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
          <div class="col-span-1 md:col-span-2">
            <div class="flex items-center space-x-3 mb-6">
              <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <span class="text-xl font-bold">BiznesPilot</span>
            </div>
            <p class="text-slate-400 text-sm max-w-md mb-6">
              Biznesingizni avtomatlashtiring. Sotuv, Marketing, HR va Call Center — barchasi bitta tizimda.
            </p>
            <div class="flex space-x-4">
              <a href="#" class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center hover:bg-slate-700 transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12c0-5.523-4.477-10-10-10z"/>
                </svg>
              </a>
              <a href="#" class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center hover:bg-slate-700 transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                </svg>
              </a>
              <a href="#" class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center hover:bg-slate-700 transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0a12 12 0 00-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                </svg>
              </a>
            </div>
          </div>

          <div>
            <h4 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-4">Mahsulot</h4>
            <ul class="space-y-3 text-slate-400 text-sm">
              <li><a href="#" class="hover:text-white transition-colors">Imkoniyatlar</a></li>
              <li><Link href="/pricing" class="hover:text-white transition-colors">Narxlar</Link></li>
              <li><a href="#" class="hover:text-white transition-colors">Integratsiyalar</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Yangiliklar</a></li>
            </ul>
          </div>

          <div>
            <h4 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-4">Aloqa</h4>
            <ul class="space-y-3 text-slate-400 text-sm">
              <li>support@biznespilot.uz</li>
              <li>+998 50 504 86 68</li>
              <li>Toshkent, O'zbekiston</li>
            </ul>
          </div>
        </div>

        <div class="border-t border-slate-800 mt-12 pt-8 text-center text-slate-400 text-sm">
          &copy; {{ new Date().getFullYear() }} BiznesPilot. Barcha huquqlar himoyalangan.
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const openDemo = () => {
  // Demo modal yoki video ochish
  alert('Demo video tez orada!');
};
</script>

<style scoped>
@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
}

@keyframes float-delayed {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-8px); }
}

.animate-float {
  animation: float 3s ease-in-out infinite;
}

.animate-float-delayed {
  animation: float-delayed 3s ease-in-out infinite;
  animation-delay: 0.5s;
}
</style>
