<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <span class="text-xl font-bold text-gray-900">BiznesPilot AI</span>
          </div>
          <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-600">{{ $page.props.auth?.user?.name }}</span>
            <Link
              href="/logout"
              method="post"
              as="button"
              class="text-sm text-gray-500 hover:text-gray-700"
            >
              Chiqish
            </Link>
          </div>
        </div>
      </div>
    </header>

    <!-- Hero Section - Problem/Solution -->
    <section class="relative py-16 lg:py-20 overflow-hidden">
      <!-- Background decoration -->
      <div class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-96 h-96 bg-blue-200 rounded-full opacity-20 blur-3xl"></div>
      <div class="absolute bottom-0 left-0 translate-y-1/4 -translate-x-1/4 w-96 h-96 bg-indigo-200 rounded-full opacity-20 blur-3xl"></div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-4xl mx-auto">
          <!-- Welcome badge -->
          <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-700 rounded-full text-sm font-medium mb-6 animate-pulse">
            <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-ping"></span>
            <span class="w-2 h-2 bg-green-500 rounded-full mr-2 absolute"></span>
            Akkauntingiz tayyor!
          </div>

          <h1 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-6 leading-tight">
            Xush kelibsiz, <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">{{ $page.props.auth?.user?.name?.split(' ')[0] }}</span>!
          </h1>

          <!-- Problem statement -->
          <div class="bg-white/60 backdrop-blur-sm rounded-2xl p-6 mb-8 border border-gray-200/50">
            <p class="text-lg text-gray-600 leading-relaxed">
              <span class="font-semibold text-gray-800">Ko'pchilik tadbirkorlar</span> marketing, mijozlarni jalb qilish va sotuvlarni boshqarishda <span class="text-red-500 font-medium">qiyinchiliklarga</span> duch keladi.
              <br class="hidden md:block" />
              BiznesPilot AI <span class="text-blue-600 font-semibold">barchasini avtomatlashtiradi</span> va sizga faqat <span class="text-emerald-600 font-semibold">o'sishga e'tibor berish</span> imkonini beradi.
            </p>
          </div>

          <!-- CTA Buttons -->
          <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <Link
              href="/welcome/create-business"
              class="group inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transform transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-xl"
            >
              <svg class="w-5 h-5 mr-2 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Biznesni yaratish
              <svg class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </Link>
            <span class="text-sm text-gray-500">Atigi 2 daqiqada tayyor bo'ladi</span>
          </div>
        </div>

        <!-- Trust indicators -->
        <div class="mt-12 flex flex-wrap items-center justify-center gap-8 text-gray-400">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm text-gray-600">Kredit karta talab qilinmaydi</span>
          </div>
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm text-gray-600">14 kunlik bepul sinov</span>
          </div>
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm text-gray-600">Istalgan vaqtda bekor qilish</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Stats Section -->
    <section class="py-12 bg-white border-y border-gray-100">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
          <div class="text-center">
            <div class="text-3xl lg:text-4xl font-bold text-blue-600 mb-1">40%</div>
            <div class="text-sm text-gray-600">O'rtacha sotuv o'sishi</div>
          </div>
          <div class="text-center">
            <div class="text-3xl lg:text-4xl font-bold text-emerald-600 mb-1">10x</div>
            <div class="text-sm text-gray-600">Marketing samaradorligi</div>
          </div>
          <div class="text-center">
            <div class="text-3xl lg:text-4xl font-bold text-purple-600 mb-1">24/7</div>
            <div class="text-sm text-gray-600">AI chatbot xizmati</div>
          </div>
          <div class="text-center">
            <div class="text-3xl lg:text-4xl font-bold text-amber-600 mb-1">50+</div>
            <div class="text-sm text-gray-600">Mamnun bizneslar</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Problem/Pain Points Section -->
    <section class="py-16 bg-gradient-to-b from-white to-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <span class="inline-block px-3 py-1 bg-red-100 text-red-600 rounded-full text-sm font-medium mb-4">Tanish muammolar</span>
          <h2 class="text-3xl font-bold text-gray-900 mb-4">Bu muammolar sizga tanishmi?</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
          <div class="bg-white rounded-xl p-6 border border-red-100 hover:border-red-200 transition-colors">
            <div class="w-12 h-12 bg-red-100 text-red-500 rounded-xl flex items-center justify-center mb-4">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Vaqt yetishmaydi</h3>
            <p class="text-gray-600 text-sm">Marketing, mijozlar bilan ishlash va sotuvlarni boshqarishga vaqt topolmayapsizmi?</p>
          </div>

          <div class="bg-white rounded-xl p-6 border border-red-100 hover:border-red-200 transition-colors">
            <div class="w-12 h-12 bg-red-100 text-red-500 rounded-xl flex items-center justify-center mb-4">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Mijozlarni topolmayapsiz</h3>
            <p class="text-gray-600 text-sm">Ideal mijozingiz kim ekanini bilmaysiz va ularni qanday jalb qilishni tushunmayapsizmi?</p>
          </div>

          <div class="bg-white rounded-xl p-6 border border-red-100 hover:border-red-200 transition-colors">
            <div class="w-12 h-12 bg-red-100 text-red-500 rounded-xl flex items-center justify-center mb-4">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Natijalar ko'rinmaydi</h3>
            <p class="text-gray-600 text-sm">Marketing xarajatlaringiz natija bermayapti va ROI ni o'lchashning iloji yo'qmi?</p>
          </div>
        </div>

        <!-- Solution Arrow -->
        <div class="text-center mb-12">
          <div class="inline-flex flex-col items-center">
            <svg class="w-8 h-8 text-blue-500 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
            </svg>
            <span class="mt-2 text-blue-600 font-semibold">BiznesPilot AI bilan barchasi hal bo'ladi</span>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-16 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <span class="inline-block px-3 py-1 bg-blue-100 text-blue-600 rounded-full text-sm font-medium mb-4">Qanday ishlaydi</span>
          <h2 class="text-3xl font-bold text-gray-900 mb-4">4 oddiy qadamda muvaffaqiyatga erishing</h2>
          <p class="text-lg text-gray-600">Hech qanday texnik bilim talab qilinmaydi</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          <!-- Step 1 -->
          <div class="relative group">
            <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl blur opacity-0 group-hover:opacity-25 transition duration-300"></div>
            <div class="relative bg-white rounded-2xl p-6 border border-gray-200 h-full">
              <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl flex items-center justify-center text-xl font-bold shadow-lg">
                  1
                </div>
                <div class="ml-3 h-1 flex-1 bg-gradient-to-r from-blue-200 to-transparent rounded hidden lg:block"></div>
              </div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Biznesingizni kiriting</h3>
              <p class="text-gray-600 text-sm mb-4">
                Biznesingiz haqida asosiy ma'lumotlarni kiriting. AI sizning sohangizga mos strategiya tayyorlaydi.
              </p>
              <div class="flex items-center text-blue-600 text-sm font-medium">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                2 daqiqa
              </div>
            </div>
          </div>

          <!-- Step 2 -->
          <div class="relative group">
            <div class="absolute -inset-1 bg-gradient-to-r from-emerald-600 to-teal-600 rounded-2xl blur opacity-0 group-hover:opacity-25 transition duration-300"></div>
            <div class="relative bg-white rounded-2xl p-6 border border-gray-200 h-full">
              <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-emerald-600 text-white rounded-xl flex items-center justify-center text-xl font-bold shadow-lg">
                  2
                </div>
                <div class="ml-3 h-1 flex-1 bg-gradient-to-r from-emerald-200 to-transparent rounded hidden lg:block"></div>
              </div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Platformalarni ulang</h3>
              <p class="text-gray-600 text-sm mb-4">
                Instagram, Telegram, WhatsApp - bir tugma bilan ulang. AI chatbot avtomatik ishlay boshlaydi.
              </p>
              <div class="flex gap-2 mt-auto">
                <span class="px-2 py-1 bg-pink-100 text-pink-600 rounded text-xs font-medium">Instagram</span>
                <span class="px-2 py-1 bg-blue-100 text-blue-600 rounded text-xs font-medium">Telegram</span>
              </div>
            </div>
          </div>

          <!-- Step 3 -->
          <div class="relative group">
            <div class="absolute -inset-1 bg-gradient-to-r from-purple-600 to-violet-600 rounded-2xl blur opacity-0 group-hover:opacity-25 transition duration-300"></div>
            <div class="relative bg-white rounded-2xl p-6 border border-gray-200 h-full">
              <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl flex items-center justify-center text-xl font-bold shadow-lg">
                  3
                </div>
                <div class="ml-3 h-1 flex-1 bg-gradient-to-r from-purple-200 to-transparent rounded hidden lg:block"></div>
              </div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">AI tahlil qiladi</h3>
              <p class="text-gray-600 text-sm mb-4">
                Sun'iy intellekt biznesingizni tahlil qiladi, ideal mijozni aniqlaydi va strategiya taklif qiladi.
              </p>
              <div class="flex items-center text-purple-600 text-sm font-medium">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                AI Powered
              </div>
            </div>
          </div>

          <!-- Step 4 -->
          <div class="relative group">
            <div class="absolute -inset-1 bg-gradient-to-r from-amber-600 to-orange-600 rounded-2xl blur opacity-0 group-hover:opacity-25 transition duration-300"></div>
            <div class="relative bg-white rounded-2xl p-6 border border-gray-200 h-full">
              <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-amber-600 text-white rounded-xl flex items-center justify-center text-xl font-bold shadow-lg">
                  4
                </div>
              </div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">O'sishni kuzating</h3>
              <p class="text-gray-600 text-sm mb-4">
                Real vaqtda barcha ko'rsatkichlarni kuzating, AI tavsiyalariga amal qiling va o'sishni his eting.
              </p>
              <div class="flex items-center text-amber-600 text-sm font-medium">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                Real-time Analytics
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section with Benefits -->
    <section class="py-16 bg-gradient-to-b from-gray-50 to-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <span class="inline-block px-3 py-1 bg-emerald-100 text-emerald-600 rounded-full text-sm font-medium mb-4">Nima olasiz</span>
          <h2 class="text-3xl font-bold text-gray-900 mb-4">Biznesingizni o'stirish uchun hamma narsa</h2>
          <p class="text-lg text-gray-600">Bitta platformada barcha kerakli vositalar</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Feature 1 -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-lg hover:border-blue-200 transition-all duration-300 group">
            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Ideal Mijoz Profili</h3>
            <p class="text-gray-600 mb-4">AI sizning ideal mijozingizni topadi, ularning og'riqlarini aniqlaydi va qanday gaplashish kerakligini aytadi.</p>
            <div class="flex items-center text-blue-600 font-medium text-sm">
              <span class="mr-2">Natija:</span>
              <span class="text-emerald-600">Konversiya 45% oshadi</span>
            </div>
          </div>

          <!-- Feature 2 -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-lg hover:border-emerald-200 transition-all duration-300 group">
            <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-emerald-600 text-white rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">AI Chatbotlar</h3>
            <p class="text-gray-600 mb-4">Instagram, Telegram, WhatsApp uchun aqlli chatbotlar. Mijozlarga 24/7 javob beradi, savdoni o'zi yopadi.</p>
            <div class="flex items-center text-emerald-600 font-medium text-sm">
              <span class="mr-2">Natija:</span>
              <span class="text-emerald-600">24/7 sotuvlar</span>
            </div>
          </div>

          <!-- Feature 3 -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-lg hover:border-purple-200 transition-all duration-300 group">
            <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Marketing Analytics</h3>
            <p class="text-gray-600 mb-4">Barcha marketing kanallari bir joyda. Qaysi kanal ishlayotganini, ROI ni real vaqtda ko'ring.</p>
            <div class="flex items-center text-purple-600 font-medium text-sm">
              <span class="mr-2">Natija:</span>
              <span class="text-emerald-600">Xarajatlar 30% kamayadi</span>
            </div>
          </div>

          <!-- Feature 4 -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-lg hover:border-amber-200 transition-all duration-300 group">
            <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-amber-600 text-white rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Marketing Rejalashtirish</h3>
            <p class="text-gray-600 mb-4">Oylik, haftalik, kunlik marketing rejalar qulay tarzda tuziladi. Kontent kalendar va kampaniya boshqaruvi.</p>
            <div class="flex items-center text-amber-600 font-medium text-sm">
              <span class="mr-2">Natija:</span>
              <span class="text-emerald-600">Vaqt 70% tejaladi</span>
            </div>
          </div>

          <!-- Feature 5 -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-lg hover:border-rose-200 transition-all duration-300 group">
            <div class="w-14 h-14 bg-gradient-to-br from-rose-500 to-rose-600 text-white rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">CRM & Sotuvlar</h3>
            <p class="text-gray-600 mb-4">Barcha mijozlar, leadlar, buyurtmalar bir joyda. Har bir mijozni kuzatib boring va yo'qotmang.</p>
            <div class="flex items-center text-rose-600 font-medium text-sm">
              <span class="mr-2">Natija:</span>
              <span class="text-emerald-600">Mijoz qaytishi 60% oshadi</span>
            </div>
          </div>

          <!-- Feature 6 -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-lg hover:border-indigo-200 transition-all duration-300 group">
            <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-indigo-600 text-white rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Avtomatik Hisobotlar</h3>
            <p class="text-gray-600 mb-4">Kunlik, haftalik hisobotlar avtomatik tayyorlanadi. PDF formatida yuklab olish yoki emailga yuborish.</p>
            <div class="flex items-center text-indigo-600 font-medium text-sm">
              <span class="mr-2">Natija:</span>
              <span class="text-emerald-600">100% aniq ma'lumotlar</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-16 bg-gradient-to-r from-blue-600 to-indigo-600 relative overflow-hidden">
      <!-- Background pattern -->
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-0 w-full h-full" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-12">
          <span class="inline-block px-3 py-1 bg-white/20 text-white rounded-full text-sm font-medium mb-4">Mijozlar fikri</span>
          <h2 class="text-3xl font-bold text-white mb-4">Bizneslar bizga ishonadi</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
            <div class="flex items-center gap-1 mb-4">
              <svg v-for="i in 5" :key="i" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </div>
            <p class="text-white/90 mb-4 italic">"BiznesPilot AI bizning sotuvlarimizni 3 oyda 40% ga oshirdi. AI chatbot endi 24/7 mijozlarga javob beradi."</p>
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-emerald-500 rounded-full flex items-center justify-center text-white font-bold">AK</div>
              <div>
                <p class="text-white font-medium">Aziz Karimov</p>
                <p class="text-white/60 text-sm">TechStart CEO</p>
              </div>
            </div>
          </div>

          <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
            <div class="flex items-center gap-1 mb-4">
              <svg v-for="i in 5" :key="i" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </div>
            <p class="text-white/90 mb-4 italic">"Marketing xarajatlarimiz kamaydi, lekin natija oshdi. ROI ni endi aniq ko'rib turamiz."</p>
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-pink-500 rounded-full flex items-center justify-center text-white font-bold">MR</div>
              <div>
                <p class="text-white font-medium">Malika Rahimova</p>
                <p class="text-white/60 text-sm">FashionUz asoschisi</p>
              </div>
            </div>
          </div>

          <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
            <div class="flex items-center gap-1 mb-4">
              <svg v-for="i in 5" :key="i" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </div>
            <p class="text-white/90 mb-4 italic">"Ideal Mijoz funksiyasi bizga to'g'ri auditoriyani topishda juda yordam berdi. Targetlash endi aniqroq."</p>
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold">GK</div>
              <div>
                <p class="text-white font-medium">Gulnora Karimova</p>
                <p class="text-white/60 text-sm">TravelUz menejeri</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Final CTA Section -->
    <section class="py-20 bg-white">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="bg-gradient-to-br from-gray-50 to-blue-50 rounded-3xl p-10 border border-gray-200">
          <div class="inline-flex items-center px-4 py-2 bg-green-100 text-green-700 rounded-full text-sm font-medium mb-6">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            14 kunlik bepul sinov
          </div>
          <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
            Biznesingizni <span class="text-blue-600">hoziroq</span> o'stiring!
          </h2>
          <p class="text-xl text-gray-600 mb-8">
            Minglab tadbirkorlar allaqachon BiznesPilot AI bilan ishlayapti.
            <br class="hidden md:block" />
            Siz ham qo'shiling va farqni his eting.
          </p>
          <Link
            href="/welcome/create-business"
            class="group inline-flex items-center px-10 py-5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-lg font-semibold rounded-2xl hover:from-blue-700 hover:to-indigo-700 transform transition-all duration-300 hover:scale-105 shadow-xl hover:shadow-2xl"
          >
            Bepul boshlash
            <svg class="w-5 h-5 ml-3 transition-transform group-hover:translate-x-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </Link>
          <p class="mt-4 text-sm text-gray-500">Kredit karta talab qilinmaydi</p>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <div class="col-span-1 md:col-span-2">
            <div class="flex items-center space-x-3 mb-4">
              <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <span class="text-xl font-bold">BiznesPilot AI</span>
            </div>
            <p class="text-gray-400 text-sm max-w-md">
              O'zbekiston bizneslari uchun AI-powered marketing va sotuvni boshqarish platformasi. Biznesingizni yangi darajaga olib chiqing.
            </p>
          </div>
          <div>
            <h4 class="font-semibold mb-4">Mahsulot</h4>
            <ul class="space-y-2 text-gray-400 text-sm">
              <li>Ideal Mijoz</li>
              <li>AI Chatbotlar</li>
              <li>CRM</li>
              <li>Analytics</li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold mb-4">Bog'lanish</h4>
            <ul class="space-y-2 text-gray-400 text-sm">
              <li>support@biznespilot.uz</li>
              <li>+998 71 200 00 00</li>
              <li>Toshkent, O'zbekiston</li>
            </ul>
          </div>
        </div>
        <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400 text-sm">
          &copy; {{ new Date().getFullYear() }} BiznesPilot AI. Barcha huquqlar himoyalangan.
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>
