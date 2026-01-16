<template>
  <section class="py-20 bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 overflow-hidden relative">
    <!-- Background Effects -->
    <div class="absolute inset-0">
      <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
      <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl"></div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-gradient-to-r from-blue-500/5 to-purple-500/5 rounded-full blur-3xl"></div>
    </div>

    <!-- Grid Pattern -->
    <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M60 0H0v60h60V0zM59 1v58H1V1h58z\' fill=\'%23ffffff\' fill-opacity=\'1\'/%3E%3C/svg%3E');"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
      <!-- Section Header -->
      <div class="text-center mb-16">
        <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-500/20 to-purple-500/20 border border-blue-500/30 rounded-full text-sm font-medium text-blue-300 mb-6 backdrop-blur-sm">
          <svg class="w-4 h-4 mr-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
          AI Powered Modullar
        </div>
        <h2 class="text-4xl lg:text-5xl font-bold text-white mb-6">
          Biznesingiz uchun <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400">kuchli vositalar</span>
        </h2>
        <p class="text-xl text-slate-400 max-w-2xl mx-auto">
          Har bir modul biznesingizni yangi darajaga olib chiqish uchun yaratilgan
        </p>
      </div>

      <!-- Carousel Container -->
      <div class="relative">
        <!-- Navigation Arrows -->
        <button
          @click="prevSlide"
          class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 lg:-translate-x-12 z-20 w-12 h-12 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all duration-300 group"
        >
          <svg class="w-6 h-6 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <button
          @click="nextSlide"
          class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 lg:translate-x-12 z-20 w-12 h-12 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all duration-300 group"
        >
          <svg class="w-6 h-6 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <!-- Carousel Track -->
        <div class="overflow-hidden rounded-3xl">
          <div
            class="flex transition-transform duration-500 ease-out"
            :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
          >
            <!-- Slide 1: Instagram Sotuv Voronka -->
            <div class="w-full flex-shrink-0 px-4">
              <div class="bg-gradient-to-br from-pink-500/10 to-purple-600/10 backdrop-blur-xl border border-white/10 rounded-3xl p-8 lg:p-12">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                  <div>
                    <div class="inline-flex items-center px-3 py-1 bg-pink-500/20 border border-pink-500/30 rounded-full text-sm text-pink-300 mb-6">
                      <span class="w-2 h-2 bg-pink-400 rounded-full mr-2 animate-pulse"></span>
                      Instagram Integration
                    </div>
                    <h3 class="text-3xl lg:text-4xl font-bold text-white mb-6">
                      Instagram Sotuv Voronkasi
                    </h3>
                    <p class="text-slate-300 text-lg mb-8 leading-relaxed">
                      Instagramdagi har bir follow, like va commentni avtomatik ravishda potentsial mijozga aylantiring. AI sizning ideal mijozingizni aniqlaydi va ularni sotuvga olib boradi.
                    </p>
                    <ul class="space-y-4">
                      <li class="flex items-center text-slate-300">
                        <div class="w-8 h-8 bg-pink-500/20 rounded-lg flex items-center justify-center mr-4">
                          <svg class="w-4 h-4 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        Avtomatik DM kampaniyalar
                      </li>
                      <li class="flex items-center text-slate-300">
                        <div class="w-8 h-8 bg-pink-500/20 rounded-lg flex items-center justify-center mr-4">
                          <svg class="w-4 h-4 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        Story va Post engagement tracking
                      </li>
                      <li class="flex items-center text-slate-300">
                        <div class="w-8 h-8 bg-pink-500/20 rounded-lg flex items-center justify-center mr-4">
                          <svg class="w-4 h-4 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        Lead scoring va segmentatsiya
                      </li>
                    </ul>
                  </div>
                  <!-- SVG Illustration: Sales Funnel -->
                  <div class="relative">
                    <svg viewBox="0 0 400 400" class="w-full max-w-md mx-auto" xmlns="http://www.w3.org/2000/svg">
                      <!-- Background glow -->
                      <defs>
                        <linearGradient id="funnelGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                          <stop offset="0%" style="stop-color:#ec4899;stop-opacity:0.8" />
                          <stop offset="50%" style="stop-color:#a855f7;stop-opacity:0.6" />
                          <stop offset="100%" style="stop-color:#6366f1;stop-opacity:0.4" />
                        </linearGradient>
                        <filter id="glow">
                          <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                          <feMerge>
                            <feMergeNode in="coloredBlur"/>
                            <feMergeNode in="SourceGraphic"/>
                          </feMerge>
                        </filter>
                        <linearGradient id="instagramGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                          <stop offset="0%" style="stop-color:#f09433" />
                          <stop offset="25%" style="stop-color:#e6683c" />
                          <stop offset="50%" style="stop-color:#dc2743" />
                          <stop offset="75%" style="stop-color:#cc2366" />
                          <stop offset="100%" style="stop-color:#bc1888" />
                        </linearGradient>
                      </defs>

                      <!-- Instagram Icon at top -->
                      <g transform="translate(170, 30)">
                        <rect x="0" y="0" width="60" height="60" rx="15" fill="url(#instagramGradient)" filter="url(#glow)"/>
                        <circle cx="30" cy="30" r="12" fill="none" stroke="white" stroke-width="3"/>
                        <circle cx="45" cy="15" r="4" fill="white"/>
                      </g>

                      <!-- Funnel Shape -->
                      <path d="M100 120 L300 120 L260 200 L140 200 Z" fill="url(#funnelGradient)" opacity="0.9" filter="url(#glow)"/>
                      <path d="M140 210 L260 210 L230 280 L170 280 Z" fill="url(#funnelGradient)" opacity="0.7" filter="url(#glow)"/>
                      <path d="M170 290 L230 290 L210 350 L190 350 Z" fill="url(#funnelGradient)" opacity="0.5" filter="url(#glow)"/>

                      <!-- Funnel labels -->
                      <text x="200" y="165" text-anchor="middle" fill="white" font-size="14" font-weight="bold">FOLLOWERS</text>
                      <text x="200" y="250" text-anchor="middle" fill="white" font-size="12" font-weight="bold">LEADS</text>
                      <text x="200" y="330" text-anchor="middle" fill="white" font-size="11" font-weight="bold">SALES</text>

                      <!-- Animated dots flowing down -->
                      <circle cx="150" cy="130" r="4" fill="#f472b6">
                        <animate attributeName="cy" values="130;180;250;340" dur="3s" repeatCount="indefinite"/>
                        <animate attributeName="opacity" values="1;0.8;0.6;0" dur="3s" repeatCount="indefinite"/>
                      </circle>
                      <circle cx="200" cy="130" r="4" fill="#c084fc">
                        <animate attributeName="cy" values="130;180;250;340" dur="3s" repeatCount="indefinite" begin="0.5s"/>
                        <animate attributeName="opacity" values="1;0.8;0.6;0" dur="3s" repeatCount="indefinite" begin="0.5s"/>
                      </circle>
                      <circle cx="250" cy="130" r="4" fill="#818cf8">
                        <animate attributeName="cy" values="130;180;250;340" dur="3s" repeatCount="indefinite" begin="1s"/>
                        <animate attributeName="opacity" values="1;0.8;0.6;0" dur="3s" repeatCount="indefinite" begin="1s"/>
                      </circle>

                      <!-- Stats badges -->
                      <g transform="translate(310, 140)">
                        <rect x="0" y="0" width="70" height="30" rx="8" fill="#ec4899" opacity="0.2"/>
                        <text x="35" y="20" text-anchor="middle" fill="#f472b6" font-size="11" font-weight="bold">+3x Lead</text>
                      </g>
                      <g transform="translate(310, 240)">
                        <rect x="0" y="0" width="70" height="30" rx="8" fill="#a855f7" opacity="0.2"/>
                        <text x="35" y="20" text-anchor="middle" fill="#c084fc" font-size="11" font-weight="bold">+45% CR</text>
                      </g>
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <!-- Slide 2: AI Chatbot -->
            <div class="w-full flex-shrink-0 px-4">
              <div class="bg-gradient-to-br from-emerald-500/10 to-teal-600/10 backdrop-blur-xl border border-white/10 rounded-3xl p-8 lg:p-12">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                  <div>
                    <div class="inline-flex items-center px-3 py-1 bg-emerald-500/20 border border-emerald-500/30 rounded-full text-sm text-emerald-300 mb-6">
                      <span class="w-2 h-2 bg-emerald-400 rounded-full mr-2 animate-pulse"></span>
                      AI Powered
                    </div>
                    <h3 class="text-3xl lg:text-4xl font-bold text-white mb-6">
                      AI Sotuv Chatbot
                    </h3>
                    <p class="text-slate-300 text-lg mb-8 leading-relaxed">
                      Sotuv skripti asosida ishlaydi gan AI chatbot. Mijozlarning savollariga 24/7 javob beradi, e'tirozlarni bartaraf etadi va sotuvni yopadi.
                    </p>
                    <ul class="space-y-4">
                      <li class="flex items-center text-slate-300">
                        <div class="w-8 h-8 bg-emerald-500/20 rounded-lg flex items-center justify-center mr-4">
                          <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        Sotuv skripti bo'yicha javob beradi
                      </li>
                      <li class="flex items-center text-slate-300">
                        <div class="w-8 h-8 bg-emerald-500/20 rounded-lg flex items-center justify-center mr-4">
                          <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        E'tirozlarni aqlli qayta ishlaydi
                      </li>
                      <li class="flex items-center text-slate-300">
                        <div class="w-8 h-8 bg-emerald-500/20 rounded-lg flex items-center justify-center mr-4">
                          <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        Operatorga avtomatik uzatish
                      </li>
                    </ul>
                  </div>
                  <!-- SVG Illustration: AI Chatbot -->
                  <div class="relative">
                    <svg viewBox="0 0 400 400" class="w-full max-w-md mx-auto" xmlns="http://www.w3.org/2000/svg">
                      <defs>
                        <linearGradient id="chatGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                          <stop offset="0%" style="stop-color:#10b981;stop-opacity:1" />
                          <stop offset="100%" style="stop-color:#14b8a6;stop-opacity:1" />
                        </linearGradient>
                        <linearGradient id="aiGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                          <stop offset="0%" style="stop-color:#6366f1;stop-opacity:1" />
                          <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:1" />
                        </linearGradient>
                      </defs>

                      <!-- Chat Window Background -->
                      <rect x="50" y="40" width="300" height="320" rx="20" fill="#1e293b" stroke="#334155" stroke-width="2"/>

                      <!-- Chat Header -->
                      <rect x="50" y="40" width="300" height="50" rx="20" fill="#334155"/>
                      <rect x="50" y="70" width="300" height="20" fill="#334155"/>
                      <circle cx="85" cy="65" r="15" fill="url(#aiGradient)"/>
                      <text x="85" y="70" text-anchor="middle" fill="white" font-size="12" font-weight="bold">AI</text>
                      <text x="115" y="62" fill="white" font-size="14" font-weight="bold">Sotuv Assistenti</text>
                      <circle cx="115" cy="75" r="4" fill="#22c55e"/>
                      <text x="130" y="78" fill="#94a3b8" font-size="10">Online</text>

                      <!-- Chat Messages -->
                      <!-- User message -->
                      <g transform="translate(180, 110)">
                        <rect x="0" y="0" width="150" height="40" rx="15" fill="#3b82f6"/>
                        <text x="15" y="25" fill="white" font-size="11">Narxi qancha?</text>
                      </g>

                      <!-- AI Response -->
                      <g transform="translate(70, 165)">
                        <rect x="0" y="0" width="200" height="55" rx="15" fill="url(#chatGradient)"/>
                        <text x="15" y="22" fill="white" font-size="11">Ajoyib savol! Bizda 3 ta</text>
                        <text x="15" y="38" fill="white" font-size="11">tarif mavjud. Sizga qaysi</text>
                        <text x="15" y="54" fill="white" font-size="11">biri mos keladi?</text>
                      </g>

                      <!-- Typing indicator -->
                      <g transform="translate(70, 240)">
                        <rect x="0" y="0" width="80" height="35" rx="15" fill="#334155"/>
                        <circle cx="25" cy="17" r="4" fill="#94a3b8">
                          <animate attributeName="opacity" values="0.3;1;0.3" dur="1s" repeatCount="indefinite"/>
                        </circle>
                        <circle cx="40" cy="17" r="4" fill="#94a3b8">
                          <animate attributeName="opacity" values="0.3;1;0.3" dur="1s" repeatCount="indefinite" begin="0.2s"/>
                        </circle>
                        <circle cx="55" cy="17" r="4" fill="#94a3b8">
                          <animate attributeName="opacity" values="0.3;1;0.3" dur="1s" repeatCount="indefinite" begin="0.4s"/>
                        </circle>
                      </g>

                      <!-- Script indicator -->
                      <g transform="translate(270, 290)">
                        <rect x="0" y="0" width="70" height="50" rx="10" fill="#6366f1" opacity="0.2" stroke="#6366f1" stroke-width="1"/>
                        <text x="10" y="20" fill="#a5b4fc" font-size="8">SCRIPT</text>
                        <line x1="10" y1="28" x2="60" y2="28" stroke="#6366f1" stroke-width="2"/>
                        <line x1="10" y1="36" x2="50" y2="36" stroke="#6366f1" stroke-width="2"/>
                        <line x1="10" y1="44" x2="40" y2="44" stroke="#6366f1" stroke-width="2"/>
                      </g>

                      <!-- AI Brain Icon -->
                      <g transform="translate(55, 290)">
                        <circle cx="25" cy="25" r="25" fill="url(#aiGradient)" opacity="0.2"/>
                        <path d="M25 12 C18 12 13 18 13 25 C13 32 18 38 25 38 C32 38 37 32 37 25" fill="none" stroke="#a5b4fc" stroke-width="2">
                          <animate attributeName="stroke-dasharray" values="0,100;100,0" dur="2s" repeatCount="indefinite"/>
                        </path>
                        <circle cx="20" cy="22" r="3" fill="#a5b4fc"/>
                        <circle cx="30" cy="22" r="3" fill="#a5b4fc"/>
                        <path d="M20 30 Q25 35 30 30" fill="none" stroke="#a5b4fc" stroke-width="2"/>
                      </g>
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <!-- Slide 3: Marketing Algorithm -->
            <div class="w-full flex-shrink-0 px-4">
              <div class="bg-gradient-to-br from-blue-500/10 to-indigo-600/10 backdrop-blur-xl border border-white/10 rounded-3xl p-8 lg:p-12">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                  <div>
                    <div class="inline-flex items-center px-3 py-1 bg-blue-500/20 border border-blue-500/30 rounded-full text-sm text-blue-300 mb-6">
                      <span class="w-2 h-2 bg-blue-400 rounded-full mr-2 animate-pulse"></span>
                      Smart Marketing
                    </div>
                    <h3 class="text-3xl lg:text-4xl font-bold text-white mb-6">
                      Marketing Algoritmi
                    </h3>
                    <p class="text-slate-300 text-lg mb-8 leading-relaxed">
                      AI sizning biznesingizga mos marketing strategiyasini ishlab chiqadi. Qaysi kontent, qaysi vaqtda, qaysi auditoriyaga - hammasini avtomatik aniqlaydi.
                    </p>
                    <ul class="space-y-4">
                      <li class="flex items-center text-slate-300">
                        <div class="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center mr-4">
                          <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        A/B testing avtomatizatsiya
                      </li>
                      <li class="flex items-center text-slate-300">
                        <div class="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center mr-4">
                          <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        Kontent kalendar generatsiyasi
                      </li>
                      <li class="flex items-center text-slate-300">
                        <div class="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center mr-4">
                          <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        Optimal posting vaqti tahlili
                      </li>
                    </ul>
                  </div>
                  <!-- SVG Illustration: Marketing Algorithm Network -->
                  <div class="relative">
                    <svg viewBox="0 0 400 400" class="w-full max-w-md mx-auto" xmlns="http://www.w3.org/2000/svg">
                      <defs>
                        <linearGradient id="nodeGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                          <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
                          <stop offset="100%" style="stop-color:#6366f1;stop-opacity:1" />
                        </linearGradient>
                        <filter id="nodeShadow">
                          <feDropShadow dx="0" dy="4" stdDeviation="4" flood-color="#3b82f6" flood-opacity="0.5"/>
                        </filter>
                      </defs>

                      <!-- Connection Lines -->
                      <g stroke="#3b82f6" stroke-width="2" opacity="0.4">
                        <line x1="200" y1="200" x2="100" y2="100">
                          <animate attributeName="stroke-dasharray" values="0,200;200,0" dur="2s" repeatCount="indefinite"/>
                        </line>
                        <line x1="200" y1="200" x2="300" y2="100">
                          <animate attributeName="stroke-dasharray" values="0,200;200,0" dur="2s" repeatCount="indefinite" begin="0.3s"/>
                        </line>
                        <line x1="200" y1="200" x2="100" y2="300">
                          <animate attributeName="stroke-dasharray" values="0,200;200,0" dur="2s" repeatCount="indefinite" begin="0.6s"/>
                        </line>
                        <line x1="200" y1="200" x2="300" y2="300">
                          <animate attributeName="stroke-dasharray" values="0,200;200,0" dur="2s" repeatCount="indefinite" begin="0.9s"/>
                        </line>
                        <line x1="200" y1="200" x2="200" y2="50">
                          <animate attributeName="stroke-dasharray" values="0,200;200,0" dur="2s" repeatCount="indefinite" begin="1.2s"/>
                        </line>
                        <line x1="200" y1="200" x2="200" y2="350">
                          <animate attributeName="stroke-dasharray" values="0,200;200,0" dur="2s" repeatCount="indefinite" begin="1.5s"/>
                        </line>
                      </g>

                      <!-- Central AI Node -->
                      <g transform="translate(200, 200)" filter="url(#nodeShadow)">
                        <circle cx="0" cy="0" r="45" fill="url(#nodeGradient)"/>
                        <text x="0" y="5" text-anchor="middle" fill="white" font-size="14" font-weight="bold">AI</text>
                        <circle cx="0" cy="0" r="50" fill="none" stroke="#60a5fa" stroke-width="2" opacity="0.5">
                          <animate attributeName="r" values="50;60;50" dur="2s" repeatCount="indefinite"/>
                          <animate attributeName="opacity" values="0.5;0.2;0.5" dur="2s" repeatCount="indefinite"/>
                        </circle>
                      </g>

                      <!-- Surrounding Nodes -->
                      <!-- Content Node -->
                      <g transform="translate(100, 100)">
                        <circle cx="0" cy="0" r="30" fill="#1e293b" stroke="#3b82f6" stroke-width="2"/>
                        <text x="0" y="4" text-anchor="middle" fill="#60a5fa" font-size="10">Content</text>
                      </g>

                      <!-- Audience Node -->
                      <g transform="translate(300, 100)">
                        <circle cx="0" cy="0" r="30" fill="#1e293b" stroke="#8b5cf6" stroke-width="2"/>
                        <text x="0" y="4" text-anchor="middle" fill="#a78bfa" font-size="10">Audience</text>
                      </g>

                      <!-- Time Node -->
                      <g transform="translate(200, 50)">
                        <circle cx="0" cy="0" r="25" fill="#1e293b" stroke="#f59e0b" stroke-width="2"/>
                        <text x="0" y="4" text-anchor="middle" fill="#fbbf24" font-size="10">Time</text>
                      </g>

                      <!-- Channel Node -->
                      <g transform="translate(100, 300)">
                        <circle cx="0" cy="0" r="30" fill="#1e293b" stroke="#10b981" stroke-width="2"/>
                        <text x="0" y="4" text-anchor="middle" fill="#34d399" font-size="10">Channel</text>
                      </g>

                      <!-- Budget Node -->
                      <g transform="translate(300, 300)">
                        <circle cx="0" cy="0" r="30" fill="#1e293b" stroke="#f43f5e" stroke-width="2"/>
                        <text x="0" y="4" text-anchor="middle" fill="#fb7185" font-size="10">Budget</text>
                      </g>

                      <!-- Results Node -->
                      <g transform="translate(200, 350)">
                        <circle cx="0" cy="0" r="25" fill="#1e293b" stroke="#22c55e" stroke-width="2"/>
                        <text x="0" y="4" text-anchor="middle" fill="#4ade80" font-size="10">ROI</text>
                      </g>

                      <!-- Data particles -->
                      <circle r="3" fill="#60a5fa">
                        <animateMotion dur="2s" repeatCount="indefinite" path="M200,200 L100,100"/>
                      </circle>
                      <circle r="3" fill="#a78bfa">
                        <animateMotion dur="2s" repeatCount="indefinite" begin="0.5s" path="M200,200 L300,100"/>
                      </circle>
                      <circle r="3" fill="#34d399">
                        <animateMotion dur="2s" repeatCount="indefinite" begin="1s" path="M200,200 L100,300"/>
                      </circle>
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <!-- Slide 4: Call Operator AI Analysis -->
            <div class="w-full flex-shrink-0 px-4">
              <div class="bg-gradient-to-br from-amber-500/10 to-orange-600/10 backdrop-blur-xl border border-white/10 rounded-3xl p-8 lg:p-12">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                  <div>
                    <div class="inline-flex items-center px-3 py-1 bg-amber-500/20 border border-amber-500/30 rounded-full text-sm text-amber-300 mb-6">
                      <span class="w-2 h-2 bg-amber-400 rounded-full mr-2 animate-pulse"></span>
                      Call Analytics
                    </div>
                    <h3 class="text-3xl lg:text-4xl font-bold text-white mb-6">
                      Call Operatorlar Tahlili
                    </h3>
                    <p class="text-slate-300 text-lg mb-8 leading-relaxed">
                      AI har bir suhbatni tahlil qiladi: operatorning samaradorligini, mijoz qoniqishini va sotuv imkoniyatlarini aniqlaydi. Real-time coaching.
                    </p>
                    <ul class="space-y-4">
                      <li class="flex items-center text-slate-300">
                        <div class="w-8 h-8 bg-amber-500/20 rounded-lg flex items-center justify-center mr-4">
                          <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        Suhbat transkripsiyasi va tahlili
                      </li>
                      <li class="flex items-center text-slate-300">
                        <div class="w-8 h-8 bg-amber-500/20 rounded-lg flex items-center justify-center mr-4">
                          <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        Sentiment va emotion detection
                      </li>
                      <li class="flex items-center text-slate-300">
                        <div class="w-8 h-8 bg-amber-500/20 rounded-lg flex items-center justify-center mr-4">
                          <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        Operator performance scoring
                      </li>
                    </ul>
                  </div>
                  <!-- SVG Illustration: Call Analysis -->
                  <div class="relative">
                    <svg viewBox="0 0 400 400" class="w-full max-w-md mx-auto" xmlns="http://www.w3.org/2000/svg">
                      <defs>
                        <linearGradient id="waveGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                          <stop offset="0%" style="stop-color:#f59e0b;stop-opacity:1" />
                          <stop offset="100%" style="stop-color:#ea580c;stop-opacity:1" />
                        </linearGradient>
                      </defs>

                      <!-- Phone/Call Interface -->
                      <rect x="80" y="50" width="240" height="300" rx="25" fill="#1e293b" stroke="#334155" stroke-width="2"/>

                      <!-- Call Status Bar -->
                      <rect x="100" y="70" width="200" height="40" rx="10" fill="#334155"/>
                      <circle cx="130" cy="90" r="12" fill="#22c55e">
                        <animate attributeName="opacity" values="1;0.5;1" dur="1s" repeatCount="indefinite"/>
                      </circle>
                      <text x="155" y="95" fill="white" font-size="12" font-weight="bold">Live Call - 03:45</text>

                      <!-- Waveform -->
                      <g transform="translate(100, 130)">
                        <rect x="0" y="0" width="200" height="60" rx="10" fill="#1e293b" stroke="#f59e0b" stroke-width="1" opacity="0.3"/>
                        <!-- Animated waveform bars -->
                        <rect x="15" y="30" width="8" height="20" rx="2" fill="url(#waveGradient)">
                          <animate attributeName="height" values="20;40;20;30;20" dur="0.5s" repeatCount="indefinite"/>
                          <animate attributeName="y" values="20;10;20;15;20" dur="0.5s" repeatCount="indefinite"/>
                        </rect>
                        <rect x="30" y="25" width="8" height="30" rx="2" fill="url(#waveGradient)">
                          <animate attributeName="height" values="30;15;35;20;30" dur="0.5s" repeatCount="indefinite" begin="0.1s"/>
                          <animate attributeName="y" values="15;23;13;20;15" dur="0.5s" repeatCount="indefinite" begin="0.1s"/>
                        </rect>
                        <rect x="45" y="20" width="8" height="40" rx="2" fill="url(#waveGradient)">
                          <animate attributeName="height" values="40;25;40;30;40" dur="0.5s" repeatCount="indefinite" begin="0.2s"/>
                          <animate attributeName="y" values="10;18;10;15;10" dur="0.5s" repeatCount="indefinite" begin="0.2s"/>
                        </rect>
                        <rect x="60" y="28" width="8" height="24" rx="2" fill="url(#waveGradient)">
                          <animate attributeName="height" values="24;40;24;30;24" dur="0.5s" repeatCount="indefinite" begin="0.3s"/>
                          <animate attributeName="y" values="18;10;18;15;18" dur="0.5s" repeatCount="indefinite" begin="0.3s"/>
                        </rect>
                        <rect x="75" y="22" width="8" height="36" rx="2" fill="url(#waveGradient)">
                          <animate attributeName="height" values="36;20;36;28;36" dur="0.5s" repeatCount="indefinite" begin="0.15s"/>
                          <animate attributeName="y" values="12;20;12;16;12" dur="0.5s" repeatCount="indefinite" begin="0.15s"/>
                        </rect>
                        <rect x="90" y="25" width="8" height="30" rx="2" fill="url(#waveGradient)">
                          <animate attributeName="height" values="30;45;30;20;30" dur="0.5s" repeatCount="indefinite" begin="0.25s"/>
                          <animate attributeName="y" values="15;8;15;20;15" dur="0.5s" repeatCount="indefinite" begin="0.25s"/>
                        </rect>
                        <rect x="105" y="30" width="8" height="20" rx="2" fill="url(#waveGradient)">
                          <animate attributeName="height" values="20;35;20;25;20" dur="0.5s" repeatCount="indefinite" begin="0.35s"/>
                          <animate attributeName="y" values="20;13;20;18;20" dur="0.5s" repeatCount="indefinite" begin="0.35s"/>
                        </rect>
                        <rect x="120" y="23" width="8" height="34" rx="2" fill="url(#waveGradient)">
                          <animate attributeName="height" values="34;18;34;26;34" dur="0.5s" repeatCount="indefinite" begin="0.05s"/>
                          <animate attributeName="y" values="13;21;13;17;13" dur="0.5s" repeatCount="indefinite" begin="0.05s"/>
                        </rect>
                        <rect x="135" y="27" width="8" height="26" rx="2" fill="url(#waveGradient)">
                          <animate attributeName="height" values="26;38;26;32;26" dur="0.5s" repeatCount="indefinite" begin="0.4s"/>
                          <animate attributeName="y" values="17;11;17;14;17" dur="0.5s" repeatCount="indefinite" begin="0.4s"/>
                        </rect>
                        <rect x="150" y="30" width="8" height="20" rx="2" fill="url(#waveGradient)">
                          <animate attributeName="height" values="20;30;20;25;20" dur="0.5s" repeatCount="indefinite" begin="0.45s"/>
                          <animate attributeName="y" values="20;15;20;18;20" dur="0.5s" repeatCount="indefinite" begin="0.45s"/>
                        </rect>
                        <rect x="165" y="25" width="8" height="30" rx="2" fill="url(#waveGradient)">
                          <animate attributeName="height" values="30;20;30;40;30" dur="0.5s" repeatCount="indefinite" begin="0.2s"/>
                          <animate attributeName="y" values="15;20;15;10;15" dur="0.5s" repeatCount="indefinite" begin="0.2s"/>
                        </rect>
                        <rect x="180" y="28" width="8" height="24" rx="2" fill="url(#waveGradient)">
                          <animate attributeName="height" values="24;35;24;18;24" dur="0.5s" repeatCount="indefinite" begin="0.3s"/>
                          <animate attributeName="y" values="18;13;18;21;18" dur="0.5s" repeatCount="indefinite" begin="0.3s"/>
                        </rect>
                      </g>

                      <!-- AI Analysis Panel -->
                      <g transform="translate(100, 210)">
                        <rect x="0" y="0" width="200" height="120" rx="10" fill="#334155"/>
                        <text x="15" y="25" fill="#f59e0b" font-size="11" font-weight="bold">AI TAHLIL</text>

                        <!-- Sentiment -->
                        <text x="15" y="50" fill="#94a3b8" font-size="10">Sentiment:</text>
                        <rect x="80" y="40" width="100" height="12" rx="4" fill="#1e293b"/>
                        <rect x="80" y="40" width="78" height="12" rx="4" fill="#22c55e">
                          <animate attributeName="width" values="60;78;70;78" dur="2s" repeatCount="indefinite"/>
                        </rect>
                        <text x="185" y="50" fill="#22c55e" font-size="9">78%</text>

                        <!-- Script adherence -->
                        <text x="15" y="75" fill="#94a3b8" font-size="10">Skript:</text>
                        <rect x="80" y="65" width="100" height="12" rx="4" fill="#1e293b"/>
                        <rect x="80" y="65" width="85" height="12" rx="4" fill="#3b82f6">
                          <animate attributeName="width" values="75;85;80;85" dur="2s" repeatCount="indefinite"/>
                        </rect>
                        <text x="185" y="75" fill="#3b82f6" font-size="9">85%</text>

                        <!-- Performance -->
                        <text x="15" y="100" fill="#94a3b8" font-size="10">Baholash:</text>
                        <rect x="80" y="90" width="100" height="12" rx="4" fill="#1e293b"/>
                        <rect x="80" y="90" width="92" height="12" rx="4" fill="#f59e0b">
                          <animate attributeName="width" values="85;92;88;92" dur="2s" repeatCount="indefinite"/>
                        </rect>
                        <text x="185" y="100" fill="#f59e0b" font-size="9">92%</text>
                      </g>
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <!-- Slide 5: Reports Module -->
            <div class="w-full flex-shrink-0 px-4">
              <div class="bg-gradient-to-br from-violet-500/10 to-purple-600/10 backdrop-blur-xl border border-white/10 rounded-3xl p-8 lg:p-12">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                  <div>
                    <div class="inline-flex items-center px-3 py-1 bg-violet-500/20 border border-violet-500/30 rounded-full text-sm text-violet-300 mb-6">
                      <span class="w-2 h-2 bg-violet-400 rounded-full mr-2 animate-pulse"></span>
                      Auto Reports
                    </div>
                    <h3 class="text-3xl lg:text-4xl font-bold text-white mb-6">
                      Hisobot Moduli
                    </h3>
                    <p class="text-slate-300 text-lg mb-8 leading-relaxed">
                      Kunlik, haftalik va oylik hisobotlar avtomatik tayyorlanadi. PDF, Excel formatlarida yuklab oling yoki to'g'ridan-to'g'ri emailga yuboring.
                    </p>
                    <ul class="space-y-4">
                      <li class="flex items-center text-slate-300">
                        <div class="w-8 h-8 bg-violet-500/20 rounded-lg flex items-center justify-center mr-4">
                          <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        Avtomatik hisobot generatsiyasi
                      </li>
                      <li class="flex items-center text-slate-300">
                        <div class="w-8 h-8 bg-violet-500/20 rounded-lg flex items-center justify-center mr-4">
                          <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        Custom dashboardlar yaratish
                      </li>
                      <li class="flex items-center text-slate-300">
                        <div class="w-8 h-8 bg-violet-500/20 rounded-lg flex items-center justify-center mr-4">
                          <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        Scheduled email delivery
                      </li>
                    </ul>
                  </div>
                  <!-- SVG Illustration: Reports -->
                  <div class="relative">
                    <svg viewBox="0 0 400 400" class="w-full max-w-md mx-auto" xmlns="http://www.w3.org/2000/svg">
                      <defs>
                        <linearGradient id="reportGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                          <stop offset="0%" style="stop-color:#8b5cf6;stop-opacity:1" />
                          <stop offset="100%" style="stop-color:#a855f7;stop-opacity:1" />
                        </linearGradient>
                      </defs>

                      <!-- Main Report Document -->
                      <g transform="translate(100, 30)">
                        <rect x="0" y="0" width="200" height="260" rx="15" fill="#1e293b" stroke="#8b5cf6" stroke-width="2"/>

                        <!-- Header -->
                        <rect x="0" y="0" width="200" height="50" rx="15" fill="url(#reportGradient)"/>
                        <rect x="0" y="35" width="200" height="15" fill="url(#reportGradient)"/>
                        <text x="20" y="32" fill="white" font-size="14" font-weight="bold">Oylik Hisobot</text>
                        <text x="145" y="32" fill="white" font-size="10">2024</text>

                        <!-- Chart Area -->
                        <g transform="translate(20, 70)">
                          <!-- Bar Chart -->
                          <rect x="0" y="60" width="25" height="40" rx="3" fill="#8b5cf6" opacity="0.6">
                            <animate attributeName="height" values="30;40;30" dur="2s" repeatCount="indefinite"/>
                            <animate attributeName="y" values="70;60;70" dur="2s" repeatCount="indefinite"/>
                          </rect>
                          <rect x="35" y="40" width="25" height="60" rx="3" fill="#8b5cf6" opacity="0.7">
                            <animate attributeName="height" values="50;60;50" dur="2s" repeatCount="indefinite" begin="0.2s"/>
                            <animate attributeName="y" values="50;40;50" dur="2s" repeatCount="indefinite" begin="0.2s"/>
                          </rect>
                          <rect x="70" y="30" width="25" height="70" rx="3" fill="#8b5cf6" opacity="0.8">
                            <animate attributeName="height" values="60;70;60" dur="2s" repeatCount="indefinite" begin="0.4s"/>
                            <animate attributeName="y" values="40;30;40" dur="2s" repeatCount="indefinite" begin="0.4s"/>
                          </rect>
                          <rect x="105" y="20" width="25" height="80" rx="3" fill="#8b5cf6">
                            <animate attributeName="height" values="70;80;70" dur="2s" repeatCount="indefinite" begin="0.6s"/>
                            <animate attributeName="y" values="30;20;30" dur="2s" repeatCount="indefinite" begin="0.6s"/>
                          </rect>
                          <rect x="140" y="10" width="25" height="90" rx="3" fill="#a855f7">
                            <animate attributeName="height" values="80;90;80" dur="2s" repeatCount="indefinite" begin="0.8s"/>
                            <animate attributeName="y" values="20;10;20" dur="2s" repeatCount="indefinite" begin="0.8s"/>
                          </rect>
                        </g>

                        <!-- Stats Lines -->
                        <g transform="translate(20, 190)">
                          <rect x="0" y="0" width="160" height="8" rx="4" fill="#334155"/>
                          <rect x="0" y="0" width="120" height="8" rx="4" fill="#8b5cf6" opacity="0.6"/>
                          <rect x="0" y="18" width="160" height="8" rx="4" fill="#334155"/>
                          <rect x="0" y="18" width="140" height="8" rx="4" fill="#a855f7" opacity="0.6"/>
                          <rect x="0" y="36" width="160" height="8" rx="4" fill="#334155"/>
                          <rect x="0" y="36" width="100" height="8" rx="4" fill="#c084fc" opacity="0.6"/>
                        </g>
                      </g>

                      <!-- Floating Export Icons -->
                      <g transform="translate(320, 80)">
                        <rect x="0" y="0" width="55" height="35" rx="8" fill="#dc2626" opacity="0.2" stroke="#dc2626" stroke-width="1"/>
                        <text x="28" y="22" text-anchor="middle" fill="#f87171" font-size="11" font-weight="bold">PDF</text>
                      </g>
                      <g transform="translate(320, 130)">
                        <rect x="0" y="0" width="55" height="35" rx="8" fill="#16a34a" opacity="0.2" stroke="#16a34a" stroke-width="1"/>
                        <text x="28" y="22" text-anchor="middle" fill="#4ade80" font-size="11" font-weight="bold">Excel</text>
                      </g>
                      <g transform="translate(320, 180)">
                        <rect x="0" y="0" width="55" height="35" rx="8" fill="#2563eb" opacity="0.2" stroke="#2563eb" stroke-width="1"/>
                        <text x="28" y="22" text-anchor="middle" fill="#60a5fa" font-size="11" font-weight="bold">Email</text>
                      </g>

                      <!-- Growth Arrow -->
                      <g transform="translate(40, 310)">
                        <path d="M0 50 L100 50 L100 40 L130 60 L100 80 L100 70 L0 70 Z" fill="url(#reportGradient)" opacity="0.8"/>
                        <text x="35" y="63" fill="white" font-size="12" font-weight="bold">+47%</text>
                      </g>

                      <!-- Auto-generate indicator -->
                      <g transform="translate(200, 320)">
                        <circle cx="0" cy="0" r="25" fill="#8b5cf6" opacity="0.2"/>
                        <path d="M-10 0 A10 10 0 1 1 10 0" fill="none" stroke="#a855f7" stroke-width="3" stroke-linecap="round">
                          <animateTransform attributeName="transform" type="rotate" from="0 0 0" to="360 0 0" dur="2s" repeatCount="indefinite"/>
                        </path>
                        <text x="40" y="5" fill="#c084fc" font-size="10">Auto</text>
                      </g>
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <!-- Slide 6: KPI Module -->
            <div class="w-full flex-shrink-0 px-4">
              <div class="bg-gradient-to-br from-cyan-500/10 to-blue-600/10 backdrop-blur-xl border border-white/10 rounded-3xl p-8 lg:p-12">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                  <div>
                    <div class="inline-flex items-center px-3 py-1 bg-cyan-500/20 border border-cyan-500/30 rounded-full text-sm text-cyan-300 mb-6">
                      <span class="w-2 h-2 bg-cyan-400 rounded-full mr-2 animate-pulse"></span>
                      Performance Tracking
                    </div>
                    <h3 class="text-3xl lg:text-4xl font-bold text-white mb-6">
                      KPI Moduli
                    </h3>
                    <p class="text-slate-300 text-lg mb-8 leading-relaxed">
                      Biznesingizning asosiy ko'rsatkichlarini real vaqtda kuzating. Maqsadlar qo'ying, progressni tracking qiling va jamoa samaradorligini oshiring.
                    </p>
                    <ul class="space-y-4">
                      <li class="flex items-center text-slate-300">
                        <div class="w-8 h-8 bg-cyan-500/20 rounded-lg flex items-center justify-center mr-4">
                          <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        Real-time KPI dashboardlar
                      </li>
                      <li class="flex items-center text-slate-300">
                        <div class="w-8 h-8 bg-cyan-500/20 rounded-lg flex items-center justify-center mr-4">
                          <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        Goal setting va tracking
                      </li>
                      <li class="flex items-center text-slate-300">
                        <div class="w-8 h-8 bg-cyan-500/20 rounded-lg flex items-center justify-center mr-4">
                          <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        Team leaderboards va gamification
                      </li>
                    </ul>
                  </div>
                  <!-- SVG Illustration: KPI Dashboard -->
                  <div class="relative">
                    <svg viewBox="0 0 400 400" class="w-full max-w-md mx-auto" xmlns="http://www.w3.org/2000/svg">
                      <defs>
                        <linearGradient id="kpiGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                          <stop offset="0%" style="stop-color:#06b6d4;stop-opacity:1" />
                          <stop offset="100%" style="stop-color:#3b82f6;stop-opacity:1" />
                        </linearGradient>
                        <linearGradient id="gaugeGradient" x1="0%" y1="100%" x2="0%" y2="0%">
                          <stop offset="0%" style="stop-color:#ef4444" />
                          <stop offset="50%" style="stop-color:#f59e0b" />
                          <stop offset="100%" style="stop-color:#22c55e" />
                        </linearGradient>
                      </defs>

                      <!-- Dashboard Background -->
                      <rect x="40" y="40" width="320" height="320" rx="20" fill="#1e293b" stroke="#334155" stroke-width="2"/>

                      <!-- Header -->
                      <text x="60" y="75" fill="white" font-size="16" font-weight="bold">KPI Dashboard</text>
                      <circle cx="330" cy="68" r="8" fill="#22c55e">
                        <animate attributeName="opacity" values="1;0.5;1" dur="2s" repeatCount="indefinite"/>
                      </circle>

                      <!-- Main Gauge -->
                      <g transform="translate(200, 180)">
                        <!-- Gauge background -->
                        <circle cx="0" cy="0" r="70" fill="none" stroke="#334155" stroke-width="15"/>
                        <!-- Gauge progress -->
                        <circle cx="0" cy="0" r="70" fill="none" stroke="url(#gaugeGradient)" stroke-width="15"
                                stroke-dasharray="330 440" stroke-linecap="round" transform="rotate(-90)">
                          <animate attributeName="stroke-dasharray" values="0 440;330 440" dur="2s" fill="freeze"/>
                        </circle>
                        <!-- Center value -->
                        <text x="0" y="10" text-anchor="middle" fill="white" font-size="28" font-weight="bold">87%</text>
                        <text x="0" y="30" text-anchor="middle" fill="#94a3b8" font-size="10">Overall Score</text>
                      </g>

                      <!-- Mini KPI Cards -->
                      <!-- Sales KPI -->
                      <g transform="translate(60, 280)">
                        <rect x="0" y="0" width="85" height="55" rx="10" fill="#334155"/>
                        <text x="10" y="20" fill="#94a3b8" font-size="9">Sotuvlar</text>
                        <text x="10" y="40" fill="#22c55e" font-size="16" font-weight="bold">+23%</text>
                        <path d="M55 35 L60 28 L65 32 L70 20 L75 25" fill="none" stroke="#22c55e" stroke-width="2"/>
                      </g>

                      <!-- Leads KPI -->
                      <g transform="translate(155, 280)">
                        <rect x="0" y="0" width="85" height="55" rx="10" fill="#334155"/>
                        <text x="10" y="20" fill="#94a3b8" font-size="9">Leadlar</text>
                        <text x="10" y="40" fill="#06b6d4" font-size="16" font-weight="bold">156</text>
                        <path d="M55 25 L60 30 L65 22 L70 28 L75 20" fill="none" stroke="#06b6d4" stroke-width="2"/>
                      </g>

                      <!-- Conversion KPI -->
                      <g transform="translate(250, 280)">
                        <rect x="0" y="0" width="85" height="55" rx="10" fill="#334155"/>
                        <text x="10" y="20" fill="#94a3b8" font-size="9">Konversiya</text>
                        <text x="10" y="40" fill="#f59e0b" font-size="16" font-weight="bold">12.4%</text>
                        <path d="M55 30 L60 25 L65 28 L70 22 L75 25" fill="none" stroke="#f59e0b" stroke-width="2"/>
                      </g>

                      <!-- Target Indicator -->
                      <g transform="translate(280, 100)">
                        <rect x="0" y="0" width="60" height="50" rx="8" fill="#06b6d4" opacity="0.2" stroke="#06b6d4" stroke-width="1"/>
                        <text x="30" y="20" text-anchor="middle" fill="#22d3ee" font-size="9">TARGET</text>
                        <text x="30" y="38" text-anchor="middle" fill="#22d3ee" font-size="14" font-weight="bold">92%</text>
                      </g>

                      <!-- Progress bars on left -->
                      <g transform="translate(60, 100)">
                        <text x="0" y="12" fill="#94a3b8" font-size="9">Q1 Goal</text>
                        <rect x="0" y="18" width="80" height="8" rx="4" fill="#334155"/>
                        <rect x="0" y="18" width="72" height="8" rx="4" fill="#22c55e">
                          <animate attributeName="width" values="0;72" dur="1.5s" fill="freeze"/>
                        </rect>

                        <text x="0" y="45" fill="#94a3b8" font-size="9">Q2 Goal</text>
                        <rect x="0" y="51" width="80" height="8" rx="4" fill="#334155"/>
                        <rect x="0" y="51" width="65" height="8" rx="4" fill="#f59e0b">
                          <animate attributeName="width" values="0;65" dur="1.5s" fill="freeze" begin="0.3s"/>
                        </rect>

                        <text x="0" y="78" fill="#94a3b8" font-size="9">Q3 Goal</text>
                        <rect x="0" y="84" width="80" height="8" rx="4" fill="#334155"/>
                        <rect x="0" y="84" width="48" height="8" rx="4" fill="#06b6d4">
                          <animate attributeName="width" values="0;48" dur="1.5s" fill="freeze" begin="0.6s"/>
                        </rect>
                      </g>
                    </svg>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination Dots -->
        <div class="flex justify-center mt-8 gap-3">
          <button
            v-for="(slide, index) in slides"
            :key="index"
            @click="goToSlide(index)"
            class="group relative"
          >
            <div
              class="w-3 h-3 rounded-full transition-all duration-300"
              :class="currentSlide === index ? 'bg-blue-500 scale-125' : 'bg-white/30 hover:bg-white/50'"
            ></div>
            <span
              class="absolute -bottom-8 left-1/2 -translate-x-1/2 text-xs text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap"
            >
              {{ slide.name }}
            </span>
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const currentSlide = ref(0);
const autoplayInterval = ref(null);

const slides = [
  { name: 'Instagram Voronka' },
  { name: 'AI Chatbot' },
  { name: 'Marketing' },
  { name: 'Call Tahlili' },
  { name: 'Hisobotlar' },
  { name: 'KPI' }
];

const nextSlide = () => {
  currentSlide.value = (currentSlide.value + 1) % slides.length;
};

const prevSlide = () => {
  currentSlide.value = (currentSlide.value - 1 + slides.length) % slides.length;
};

const goToSlide = (index) => {
  currentSlide.value = index;
};

const startAutoplay = () => {
  autoplayInterval.value = setInterval(() => {
    nextSlide();
  }, 6000);
};

const stopAutoplay = () => {
  if (autoplayInterval.value) {
    clearInterval(autoplayInterval.value);
  }
};

onMounted(() => {
  startAutoplay();
});

onUnmounted(() => {
  stopAutoplay();
});
</script>

<style scoped>
/* Smooth transitions */
.transition-transform {
  transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
