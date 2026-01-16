<!-- AI Features Carousel Section -->
<section id="ai-features" class="py-20 lg:py-28 bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 overflow-hidden relative">
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
        <div class="text-center mb-16 animate-on-scroll">
            <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-500/20 to-purple-500/20 border border-blue-500/30 rounded-full text-sm font-medium text-blue-300 mb-6 backdrop-blur-sm">
                <svg class="w-4 h-4 mr-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                {{ $locale === 'ru' ? 'AI Модули' : 'AI Powered Modullar' }}
            </div>
            <h2 class="text-4xl lg:text-5xl font-bold text-white mb-6">
                {{ $locale === 'ru' ? 'Мощные инструменты для' : 'Biznesingiz uchun' }}
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400">
                    {{ $locale === 'ru' ? 'вашего бизнеса' : 'kuchli vositalar' }}
                </span>
            </h2>
            <p class="text-xl text-slate-400 max-w-2xl mx-auto">
                {{ $locale === 'ru' ? 'Каждый модуль создан для роста вашего бизнеса' : 'Har bir modul biznesingizni yangi darajaga olib chiqish uchun yaratilgan' }}
            </p>
        </div>

        <!-- Carousel Container -->
        <div class="relative" x-data="featuresCarousel()">
            <!-- Navigation Arrows -->
            <button
                @click="prevSlide()"
                class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 lg:-translate-x-12 z-20 w-12 h-12 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all duration-300 group"
            >
                <svg class="w-6 h-6 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button
                @click="nextSlide()"
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
                                        {{ $locale === 'ru' ? 'Instagram Воронка Продаж' : 'Instagram Sotuv Voronkasi' }}
                                    </h3>
                                    <p class="text-slate-300 text-lg mb-8 leading-relaxed">
                                        {{ $locale === 'ru' ? 'Автоматически превращайте каждого подписчика, лайк и комментарий в потенциального клиента. AI определяет вашего идеального клиента и ведет его к продаже.' : 'Instagramdagi har bir follow, like va commentni avtomatik ravishda potentsial mijozga aylantiring. AI sizning ideal mijozingizni aniqlaydi va ularni sotuvga olib boradi.' }}
                                    </p>
                                    <ul class="space-y-4">
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-pink-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Автоматические DM кампании' : 'Avtomatik DM kampaniyalar' }}
                                        </li>
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-pink-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Отслеживание вовлеченности' : 'Story va Post engagement tracking' }}
                                        </li>
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-pink-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Lead scoring и сегментация' : 'Lead scoring va segmentatsiya' }}
                                        </li>
                                    </ul>
                                </div>
                                <!-- SVG Illustration: Instagram Sales Funnel -->
                                <div class="relative">
                                    <svg viewBox="0 0 400 400" class="w-full max-w-md mx-auto" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="funnelGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                                <stop offset="0%" style="stop-color:#ec4899;stop-opacity:0.9" />
                                                <stop offset="50%" style="stop-color:#a855f7;stop-opacity:0.7" />
                                                <stop offset="100%" style="stop-color:#6366f1;stop-opacity:0.5" />
                                            </linearGradient>
                                            <filter id="glow">
                                                <feGaussianBlur stdDeviation="4" result="coloredBlur"/>
                                                <feMerge>
                                                    <feMergeNode in="coloredBlur"/>
                                                    <feMergeNode in="SourceGraphic"/>
                                                </feMerge>
                                            </filter>
                                            <filter id="softShadow">
                                                <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="#ec4899" flood-opacity="0.4"/>
                                            </filter>
                                            <linearGradient id="instagramGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" style="stop-color:#feda75" />
                                                <stop offset="20%" style="stop-color:#fa7e1e" />
                                                <stop offset="40%" style="stop-color:#d62976" />
                                                <stop offset="60%" style="stop-color:#962fbf" />
                                                <stop offset="80%" style="stop-color:#4f5bd5" />
                                                <stop offset="100%" style="stop-color:#4f5bd5" />
                                            </linearGradient>
                                            <linearGradient id="cardGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" style="stop-color:#1e293b" />
                                                <stop offset="100%" style="stop-color:#334155" />
                                            </linearGradient>
                                        </defs>

                                        <!-- Background glow effects -->
                                        <circle cx="200" cy="200" r="150" fill="#ec4899" opacity="0.05"/>
                                        <circle cx="200" cy="200" r="100" fill="#a855f7" opacity="0.05"/>

                                        <!-- Instagram Icon with enhanced design -->
                                        <g transform="translate(155, 15)" filter="url(#softShadow)">
                                            <rect x="0" y="0" width="90" height="90" rx="22" fill="url(#instagramGradient)"/>
                                            <rect x="8" y="8" width="74" height="74" rx="18" fill="none" stroke="white" stroke-width="3" opacity="0.3"/>
                                            <circle cx="45" cy="45" r="20" fill="none" stroke="white" stroke-width="4"/>
                                            <circle cx="45" cy="45" r="8" fill="white" opacity="0.3"/>
                                            <circle cx="68" cy="22" r="6" fill="white"/>
                                            <circle cx="68" cy="22" r="3" fill="url(#instagramGradient)"/>
                                        </g>

                                        <!-- Enhanced Funnel Shape -->
                                        <g filter="url(#glow)">
                                            <!-- Top funnel layer -->
                                            <path d="M80 125 L320 125 L280 185 L120 185 Z" fill="url(#funnelGradient)" opacity="0.95"/>
                                            <path d="M80 125 L320 125 L300 145 L100 145 Z" fill="white" opacity="0.15"/>

                                            <!-- Middle funnel layer -->
                                            <path d="M120 195 L280 195 L245 260 L155 260 Z" fill="url(#funnelGradient)" opacity="0.8"/>
                                            <path d="M120 195 L280 195 L265 215 L135 215 Z" fill="white" opacity="0.1"/>

                                            <!-- Bottom funnel layer -->
                                            <path d="M155 270 L245 270 L215 340 L185 340 Z" fill="url(#funnelGradient)" opacity="0.65"/>
                                            <path d="M155 270 L245 270 L235 290 L165 290 Z" fill="white" opacity="0.1"/>
                                        </g>

                                        <!-- Funnel labels with icons -->
                                        <g transform="translate(200, 155)">
                                            <text x="0" y="0" text-anchor="middle" fill="white" font-size="13" font-weight="bold" letter-spacing="1">FOLLOWERS</text>
                                            <text x="0" y="16" text-anchor="middle" fill="white" font-size="20" font-weight="bold" opacity="0.9">10,000+</text>
                                        </g>
                                        <g transform="translate(200, 228)">
                                            <text x="0" y="0" text-anchor="middle" fill="white" font-size="12" font-weight="bold" letter-spacing="1">LEADS</text>
                                            <text x="0" y="15" text-anchor="middle" fill="white" font-size="17" font-weight="bold" opacity="0.85">2,500</text>
                                        </g>
                                        <g transform="translate(200, 310)">
                                            <text x="0" y="0" text-anchor="middle" fill="white" font-size="11" font-weight="bold" letter-spacing="1">SALES</text>
                                            <text x="0" y="14" text-anchor="middle" fill="white" font-size="15" font-weight="bold" opacity="0.8">850</text>
                                        </g>

                                        <!-- Animated particles flowing through funnel -->
                                        <circle cx="140" cy="130" r="5" fill="#f472b6">
                                            <animate attributeName="cy" values="130;160;220;305;360" dur="4s" repeatCount="indefinite"/>
                                            <animate attributeName="cx" values="140;150;170;190;195" dur="4s" repeatCount="indefinite"/>
                                            <animate attributeName="r" values="5;4;3.5;3;2" dur="4s" repeatCount="indefinite"/>
                                            <animate attributeName="opacity" values="1;0.9;0.7;0.5;0" dur="4s" repeatCount="indefinite"/>
                                        </circle>
                                        <circle cx="200" cy="130" r="5" fill="#c084fc">
                                            <animate attributeName="cy" values="130;160;220;305;360" dur="4s" repeatCount="indefinite" begin="0.8s"/>
                                            <animate attributeName="r" values="5;4;3.5;3;2" dur="4s" repeatCount="indefinite" begin="0.8s"/>
                                            <animate attributeName="opacity" values="1;0.9;0.7;0.5;0" dur="4s" repeatCount="indefinite" begin="0.8s"/>
                                        </circle>
                                        <circle cx="260" cy="130" r="5" fill="#818cf8">
                                            <animate attributeName="cy" values="130;160;220;305;360" dur="4s" repeatCount="indefinite" begin="1.6s"/>
                                            <animate attributeName="cx" values="260;250;230;210;205" dur="4s" repeatCount="indefinite" begin="1.6s"/>
                                            <animate attributeName="r" values="5;4;3.5;3;2" dur="4s" repeatCount="indefinite" begin="1.6s"/>
                                            <animate attributeName="opacity" values="1;0.9;0.7;0.5;0" dur="4s" repeatCount="indefinite" begin="1.6s"/>
                                        </circle>
                                        <circle cx="180" cy="130" r="4" fill="#f9a8d4">
                                            <animate attributeName="cy" values="130;160;220;305;360" dur="4s" repeatCount="indefinite" begin="2.4s"/>
                                            <animate attributeName="cx" values="180;185;195;200;200" dur="4s" repeatCount="indefinite" begin="2.4s"/>
                                            <animate attributeName="r" values="4;3.5;3;2.5;2" dur="4s" repeatCount="indefinite" begin="2.4s"/>
                                            <animate attributeName="opacity" values="1;0.9;0.7;0.5;0" dur="4s" repeatCount="indefinite" begin="2.4s"/>
                                        </circle>

                                        <!-- Stats badges -->
                                        <g transform="translate(330, 130)">
                                            <rect x="0" y="0" width="65" height="45" rx="12" fill="url(#cardGradient)" stroke="#ec4899" stroke-width="1"/>
                                            <text x="32" y="18" text-anchor="middle" fill="#f9a8d4" font-size="9">REACH</text>
                                            <text x="32" y="35" text-anchor="middle" fill="#f472b6" font-size="14" font-weight="bold">+3x</text>
                                        </g>
                                        <g transform="translate(330, 195)">
                                            <rect x="0" y="0" width="65" height="45" rx="12" fill="url(#cardGradient)" stroke="#a855f7" stroke-width="1"/>
                                            <text x="32" y="18" text-anchor="middle" fill="#d8b4fe" font-size="9">CONVERT</text>
                                            <text x="32" y="35" text-anchor="middle" fill="#c084fc" font-size="14" font-weight="bold">+45%</text>
                                        </g>
                                        <g transform="translate(330, 260)">
                                            <rect x="0" y="0" width="65" height="45" rx="12" fill="url(#cardGradient)" stroke="#22c55e" stroke-width="1"/>
                                            <text x="32" y="18" text-anchor="middle" fill="#86efac" font-size="9">ROI</text>
                                            <text x="32" y="35" text-anchor="middle" fill="#4ade80" font-size="14" font-weight="bold">+280%</text>
                                        </g>

                                        <!-- Left side mini icons -->
                                        <g transform="translate(15, 150)">
                                            <circle cx="20" cy="20" r="18" fill="#1e293b" stroke="#f472b6" stroke-width="1.5"/>
                                            <path d="M14 20 L20 14 L26 20 L20 26 Z" fill="#f472b6"/>
                                            <text x="20" y="50" text-anchor="middle" fill="#94a3b8" font-size="8">DM</text>
                                        </g>
                                        <g transform="translate(15, 220)">
                                            <circle cx="20" cy="20" r="18" fill="#1e293b" stroke="#a855f7" stroke-width="1.5"/>
                                            <rect x="12" y="14" width="16" height="12" rx="2" fill="none" stroke="#a855f7" stroke-width="2"/>
                                            <path d="M12 17 L20 23 L28 17" fill="none" stroke="#a855f7" stroke-width="2"/>
                                            <text x="20" y="50" text-anchor="middle" fill="#94a3b8" font-size="8">CRM</text>
                                        </g>
                                        <g transform="translate(15, 290)">
                                            <circle cx="20" cy="20" r="18" fill="#1e293b" stroke="#22c55e" stroke-width="1.5"/>
                                            <text x="20" y="26" text-anchor="middle" fill="#22c55e" font-size="16" font-weight="bold">$</text>
                                            <text x="20" y="50" text-anchor="middle" fill="#94a3b8" font-size="8">SALE</text>
                                        </g>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 2: Telegram Sotuv Voronka -->
                    <div class="w-full flex-shrink-0 px-4">
                        <div class="bg-gradient-to-br from-sky-500/10 to-blue-600/10 backdrop-blur-xl border border-white/10 rounded-3xl p-8 lg:p-12">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                                <div>
                                    <div class="inline-flex items-center px-3 py-1 bg-sky-500/20 border border-sky-500/30 rounded-full text-sm text-sky-300 mb-6">
                                        <span class="w-2 h-2 bg-sky-400 rounded-full mr-2 animate-pulse"></span>
                                        Telegram Integration
                                    </div>
                                    <h3 class="text-3xl lg:text-4xl font-bold text-white mb-6">
                                        {{ $locale === 'ru' ? 'Telegram Воронка Продаж' : 'Telegram Sotuv Voronkasi' }}
                                    </h3>
                                    <p class="text-slate-300 text-lg mb-8 leading-relaxed">
                                        {{ $locale === 'ru' ? 'Превращайте подписчиков Telegram канала в покупателей. Автоматические воронки, рассылки и AI-бот для продаж 24/7.' : 'Telegram kanaldagi obunachilarni xaridorlarga aylantiring. Avtomatik voronkalar, broadcast va 24/7 sotuv uchun AI-bot.' }}
                                    </p>
                                    <ul class="space-y-4">
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-sky-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Автоматические воронки в боте' : 'Botda avtomatik voronkalar' }}
                                        </li>
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-sky-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Массовые и сегментированные рассылки' : 'Ommaviy va segmentlangan broadcast' }}
                                        </li>
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-sky-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Интеграция с CRM и оплатой' : 'CRM va to\'lov integratsiyasi' }}
                                        </li>
                                    </ul>
                                </div>
                                <!-- SVG Illustration: Telegram Bot Interface -->
                                <div class="relative">
                                    <svg viewBox="0 0 400 400" class="w-full max-w-md mx-auto" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="telegramGradient2" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" style="stop-color:#2AABEE" />
                                                <stop offset="100%" style="stop-color:#229ED9" />
                                            </linearGradient>
                                            <linearGradient id="tgChatGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" style="stop-color:#1e293b" />
                                                <stop offset="100%" style="stop-color:#0f172a" />
                                            </linearGradient>
                                            <filter id="tgShadow">
                                                <feDropShadow dx="0" dy="8" stdDeviation="12" flood-color="#0ea5e9" flood-opacity="0.3"/>
                                            </filter>
                                            <filter id="tgGlow">
                                                <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                                                <feMerge>
                                                    <feMergeNode in="coloredBlur"/>
                                                    <feMergeNode in="SourceGraphic"/>
                                                </feMerge>
                                            </filter>
                                        </defs>

                                        <!-- Background glow -->
                                        <circle cx="200" cy="200" r="180" fill="#0ea5e9" opacity="0.03"/>
                                        <circle cx="200" cy="200" r="120" fill="#0ea5e9" opacity="0.05"/>

                                        <!-- Telegram Icon - Enhanced -->
                                        <g transform="translate(155, 10)" filter="url(#tgShadow)">
                                            <circle cx="45" cy="45" r="42" fill="url(#telegramGradient2)"/>
                                            <circle cx="45" cy="45" r="38" fill="none" stroke="white" stroke-width="2" opacity="0.2"/>
                                            <!-- Telegram paper plane icon -->
                                            <path d="M25 45 L58 30 L50 62 L40 48 L25 45 Z" fill="white"/>
                                            <path d="M40 48 L50 62 L45 42 Z" fill="#c8e6ff" opacity="0.8"/>
                                            <path d="M40 48 L58 30" stroke="white" stroke-width="1.5" opacity="0.5"/>
                                        </g>

                                        <!-- Phone/Bot Interface Frame -->
                                        <g transform="translate(60, 100)" filter="url(#tgShadow)">
                                            <rect x="0" y="0" width="280" height="280" rx="24" fill="url(#tgChatGradient)" stroke="#334155" stroke-width="2"/>

                                            <!-- Header -->
                                            <rect x="0" y="0" width="280" height="60" rx="24" fill="#0ea5e9" opacity="0.15"/>
                                            <rect x="0" y="40" width="280" height="20" fill="#0ea5e9" opacity="0.15"/>

                                            <!-- Bot avatar -->
                                            <circle cx="35" cy="30" r="18" fill="url(#telegramGradient2)"/>
                                            <text x="35" y="36" text-anchor="middle" fill="white" font-size="14" font-weight="bold">🤖</text>

                                            <!-- Bot info -->
                                            <text x="62" y="26" fill="white" font-size="13" font-weight="bold">Sotuv Bot</text>
                                            <circle cx="62" cy="38" r="4" fill="#22c55e"/>
                                            <text x="74" y="42" fill="#94a3b8" font-size="10">Online</text>

                                            <!-- Notification badge -->
                                            <g transform="translate(245, 15)">
                                                <circle cx="0" cy="0" r="14" fill="#22c55e">
                                                    <animate attributeName="r" values="14;17;14" dur="2s" repeatCount="indefinite"/>
                                                </circle>
                                                <text x="0" y="5" text-anchor="middle" fill="white" font-size="11" font-weight="bold">+1</text>
                                            </g>

                                            <!-- Chat Messages -->
                                            <!-- Bot message 1 -->
                                            <g transform="translate(15, 75)">
                                                <rect x="0" y="0" width="170" height="42" rx="16" fill="#0ea5e9" opacity="0.25"/>
                                                <rect x="0" y="0" width="170" height="42" rx="16" fill="none" stroke="#0ea5e9" stroke-width="1" opacity="0.3"/>
                                                <text x="15" y="18" fill="#7dd3fc" font-size="11" font-weight="500">{{ $locale === 'ru' ? 'Привет! Чем помочь?' : 'Salom! Qanday yordam?' }}</text>
                                                <text x="15" y="34" fill="#38bdf8" font-size="9" opacity="0.7">12:01</text>
                                            </g>

                                            <!-- User message -->
                                            <g transform="translate(85, 130)">
                                                <rect x="0" y="0" width="180" height="38" rx="16" fill="#334155"/>
                                                <text x="15" y="24" fill="#e2e8f0" font-size="11">{{ $locale === 'ru' ? 'Хочу купить курс' : 'Kurs sotib olmoqchiman' }}</text>
                                            </g>

                                            <!-- Bot message 2 with button -->
                                            <g transform="translate(15, 180)">
                                                <rect x="0" y="0" width="200" height="58" rx="16" fill="#0ea5e9" opacity="0.25"/>
                                                <rect x="0" y="0" width="200" height="58" rx="16" fill="none" stroke="#0ea5e9" stroke-width="1" opacity="0.3"/>
                                                <text x="15" y="18" fill="#7dd3fc" font-size="11" font-weight="500">{{ $locale === 'ru' ? 'Отлично! Вот ссылка' : 'Ajoyib! Mana havola' }}</text>
                                                <!-- Payment button -->
                                                <rect x="15" y="30" width="120" height="22" rx="8" fill="#22c55e"/>
                                                <text x="75" y="45" text-anchor="middle" fill="white" font-size="10" font-weight="bold">💳 {{ $locale === 'ru' ? 'Оплатить' : 'To\'lov qilish' }}</text>
                                            </g>

                                            <!-- Stats row -->
                                            <g transform="translate(15, 250)">
                                                <rect x="0" y="0" width="80" height="24" rx="8" fill="#22c55e" opacity="0.2" stroke="#22c55e" stroke-width="1"/>
                                                <text x="40" y="16" text-anchor="middle" fill="#4ade80" font-size="10" font-weight="bold">+85% CR</text>
                                            </g>
                                            <g transform="translate(95, 250)">
                                                <rect x="0" y="0" width="70" height="24" rx="8" fill="#0ea5e9" opacity="0.2" stroke="#0ea5e9" stroke-width="1"/>
                                                <text x="35" y="16" text-anchor="middle" fill="#38bdf8" font-size="10" font-weight="bold">24/7</text>
                                            </g>
                                            <g transform="translate(180, 250)">
                                                <rect x="0" y="0" width="85" height="24" rx="8" fill="#a855f7" opacity="0.2" stroke="#a855f7" stroke-width="1"/>
                                                <text x="42" y="16" text-anchor="middle" fill="#c084fc" font-size="10" font-weight="bold">Auto Reply</text>
                                            </g>
                                        </g>

                                        <!-- Floating elements -->
                                        <g transform="translate(350, 150)">
                                            <circle cx="20" cy="20" r="25" fill="#1e293b" stroke="#0ea5e9" stroke-width="2">
                                                <animate attributeName="r" values="25;28;25" dur="3s" repeatCount="indefinite"/>
                                            </circle>
                                            <text x="20" y="25" text-anchor="middle" fill="#0ea5e9" font-size="18">📊</text>
                                        </g>
                                        <g transform="translate(350, 230)">
                                            <circle cx="20" cy="20" r="22" fill="#1e293b" stroke="#22c55e" stroke-width="2">
                                                <animate attributeName="r" values="22;25;22" dur="3s" repeatCount="indefinite" begin="0.5s"/>
                                            </circle>
                                            <text x="20" y="25" text-anchor="middle" fill="#22c55e" font-size="16">💰</text>
                                        </g>
                                        <g transform="translate(350, 305)">
                                            <circle cx="20" cy="20" r="20" fill="#1e293b" stroke="#f59e0b" stroke-width="2">
                                                <animate attributeName="r" values="20;23;20" dur="3s" repeatCount="indefinite" begin="1s"/>
                                            </circle>
                                            <text x="20" y="26" text-anchor="middle" fill="#f59e0b" font-size="14">🎯</text>
                                        </g>

                                        <!-- Connection lines -->
                                        <path d="M340 175 L360 175" stroke="#0ea5e9" stroke-width="2" stroke-dasharray="4 2" opacity="0.5"/>
                                        <path d="M340 255 L360 255" stroke="#22c55e" stroke-width="2" stroke-dasharray="4 2" opacity="0.5"/>
                                        <path d="M340 330 L360 330" stroke="#f59e0b" stroke-width="2" stroke-dasharray="4 2" opacity="0.5"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 3: Facebook Ads Manager -->
                    <div class="w-full flex-shrink-0 px-4">
                        <div class="bg-gradient-to-br from-blue-600/10 to-indigo-700/10 backdrop-blur-xl border border-white/10 rounded-3xl p-8 lg:p-12">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                                <div>
                                    <div class="inline-flex items-center px-3 py-1 bg-blue-600/20 border border-blue-500/30 rounded-full text-sm text-blue-300 mb-6">
                                        <span class="w-2 h-2 bg-blue-400 rounded-full mr-2 animate-pulse"></span>
                                        Meta Ads Integration
                                    </div>
                                    <h3 class="text-3xl lg:text-4xl font-bold text-white mb-6">
                                        {{ $locale === 'ru' ? 'Facebook Ads Менеджер' : 'Facebook Ads Menejer' }}
                                    </h3>
                                    <p class="text-slate-300 text-lg mb-8 leading-relaxed">
                                        {{ $locale === 'ru' ? 'Управляйте рекламными кампаниями Facebook и Instagram из одной панели. AI оптимизирует бюджет и таргетинг автоматически.' : 'Facebook va Instagram reklama kampaniyalarini bitta paneldan boshqaring. AI byudjet va targetingni avtomatik optimizatsiya qiladi.' }}
                                    </p>
                                    <ul class="space-y-4">
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-blue-600/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Автоматическая оптимизация бюджета' : 'Avtomatik byudjet optimizatsiyasi' }}
                                        </li>
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-blue-600/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'A/B тестирование креативов' : 'Kreativlar A/B testing' }}
                                        </li>
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-blue-600/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'ROI отслеживание в реальном времени' : 'Real-time ROI tracking' }}
                                        </li>
                                    </ul>
                                </div>
                                <!-- SVG Illustration: Facebook Ads Dashboard -->
                                <div class="relative">
                                    <svg viewBox="0 0 400 400" class="w-full max-w-md mx-auto" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="fbGradient2" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" style="stop-color:#1877f2" />
                                                <stop offset="100%" style="stop-color:#0866ff" />
                                            </linearGradient>
                                            <linearGradient id="metaGradient2" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" style="stop-color:#0668e1" />
                                                <stop offset="50%" style="stop-color:#0081fb" />
                                                <stop offset="100%" style="stop-color:#00c2ff" />
                                            </linearGradient>
                                            <linearGradient id="chartLine" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" style="stop-color:#22c55e" />
                                                <stop offset="100%" style="stop-color:#3b82f6" />
                                            </linearGradient>
                                            <filter id="fbShadow">
                                                <feDropShadow dx="0" dy="8" stdDeviation="12" flood-color="#1877f2" flood-opacity="0.3"/>
                                            </filter>
                                            <filter id="cardShadow">
                                                <feDropShadow dx="0" dy="4" stdDeviation="6" flood-color="#000000" flood-opacity="0.2"/>
                                            </filter>
                                        </defs>

                                        <!-- Background glow -->
                                        <circle cx="200" cy="200" r="180" fill="#1877f2" opacity="0.03"/>
                                        <circle cx="200" cy="200" r="120" fill="#0866ff" opacity="0.05"/>

                                        <!-- Main Dashboard Container -->
                                        <g filter="url(#fbShadow)">
                                            <rect x="30" y="20" width="340" height="360" rx="24" fill="#0f172a" stroke="#334155" stroke-width="2"/>

                                            <!-- Header -->
                                            <rect x="30" y="20" width="340" height="55" rx="24" fill="url(#fbGradient2)"/>
                                            <rect x="30" y="55" width="340" height="20" fill="url(#fbGradient2)"/>

                                            <!-- Meta Logo -->
                                            <g transform="translate(50, 35)">
                                                <circle cx="15" cy="15" r="14" fill="white" opacity="0.2"/>
                                                <text x="15" y="20" text-anchor="middle" fill="white" font-size="14" font-weight="bold">f</text>
                                            </g>
                                            <text x="85" y="52" fill="white" font-size="14" font-weight="bold">Meta Ads Manager</text>

                                            <!-- AI Badge -->
                                            <g transform="translate(305, 35)">
                                                <rect x="0" y="0" width="50" height="24" rx="12" fill="#8b5cf6"/>
                                                <text x="17" y="16" fill="white" font-size="9" font-weight="bold">🤖 AI</text>
                                            </g>
                                        </g>

                                        <!-- Campaign Cards Row -->
                                        <g transform="translate(45, 90)" filter="url(#cardShadow)">
                                            <!-- Campaign 1 - Active -->
                                            <rect x="0" y="0" width="145" height="90" rx="16" fill="#1e293b" stroke="#22c55e" stroke-width="1.5"/>
                                            <circle cx="125" cy="20" r="8" fill="#22c55e" opacity="0.3"/>
                                            <circle cx="125" cy="20" r="4" fill="#22c55e">
                                                <animate attributeName="opacity" values="1;0.5;1" dur="2s" repeatCount="indefinite"/>
                                            </circle>
                                            <text x="15" y="22" fill="#94a3b8" font-size="10">Campaign 1</text>
                                            <text x="15" y="48" fill="#22c55e" font-size="22" font-weight="bold">$2,450</text>
                                            <g transform="translate(15, 60)">
                                                <rect x="0" y="0" width="55" height="20" rx="6" fill="#22c55e" opacity="0.15"/>
                                                <text x="8" y="14" fill="#4ade80" font-size="10" font-weight="bold">+24% ↑</text>
                                            </g>
                                            <g transform="translate(78, 60)">
                                                <rect x="0" y="0" width="52" height="20" rx="6" fill="#1877f2" opacity="0.15"/>
                                                <text x="8" y="14" fill="#60a5fa" font-size="10">ROI</text>
                                            </g>
                                        </g>

                                        <g transform="translate(205, 90)" filter="url(#cardShadow)">
                                            <!-- Campaign 2 -->
                                            <rect x="0" y="0" width="145" height="90" rx="16" fill="#1e293b" stroke="#3b82f6" stroke-width="1.5"/>
                                            <circle cx="125" cy="20" r="8" fill="#3b82f6" opacity="0.3"/>
                                            <circle cx="125" cy="20" r="4" fill="#3b82f6">
                                                <animate attributeName="opacity" values="1;0.5;1" dur="2s" repeatCount="indefinite" begin="0.5s"/>
                                            </circle>
                                            <text x="15" y="22" fill="#94a3b8" font-size="10">Campaign 2</text>
                                            <text x="15" y="48" fill="#3b82f6" font-size="22" font-weight="bold">$1,890</text>
                                            <g transform="translate(15, 60)">
                                                <rect x="0" y="0" width="55" height="20" rx="6" fill="#3b82f6" opacity="0.15"/>
                                                <text x="8" y="14" fill="#60a5fa" font-size="10" font-weight="bold">+18% ↑</text>
                                            </g>
                                            <g transform="translate(78, 60)">
                                                <rect x="0" y="0" width="52" height="20" rx="6" fill="#f59e0b" opacity="0.15"/>
                                                <text x="8" y="14" fill="#fbbf24" font-size="10">A/B</text>
                                            </g>
                                        </g>

                                        <!-- Chart Area -->
                                        <g transform="translate(45, 195)">
                                            <rect x="0" y="0" width="305" height="115" rx="16" fill="#1e293b"/>
                                            <text x="15" y="22" fill="#94a3b8" font-size="11" font-weight="500">{{ $locale === 'ru' ? 'Расход и конверсии' : 'Xarajat va konversiya' }}</text>

                                            <!-- Chart grid lines -->
                                            <line x1="15" y1="40" x2="290" y2="40" stroke="#334155" stroke-width="1" stroke-dasharray="4 2"/>
                                            <line x1="15" y1="60" x2="290" y2="60" stroke="#334155" stroke-width="1" stroke-dasharray="4 2"/>
                                            <line x1="15" y1="80" x2="290" y2="80" stroke="#334155" stroke-width="1" stroke-dasharray="4 2"/>

                                            <!-- Animated chart bars with better design -->
                                            <rect x="25" y="100" width="28" height="0" rx="4" fill="#1877f2">
                                                <animate attributeName="height" values="0;50" dur="0.8s" fill="freeze"/>
                                                <animate attributeName="y" values="100;50" dur="0.8s" fill="freeze"/>
                                            </rect>
                                            <rect x="62" y="100" width="28" height="0" rx="4" fill="#1877f2">
                                                <animate attributeName="height" values="0;60" dur="0.8s" fill="freeze" begin="0.1s"/>
                                                <animate attributeName="y" values="100;40" dur="0.8s" fill="freeze" begin="0.1s"/>
                                            </rect>
                                            <rect x="99" y="100" width="28" height="0" rx="4" fill="#1877f2">
                                                <animate attributeName="height" values="0;40" dur="0.8s" fill="freeze" begin="0.2s"/>
                                                <animate attributeName="y" values="100;60" dur="0.8s" fill="freeze" begin="0.2s"/>
                                            </rect>
                                            <rect x="136" y="100" width="28" height="0" rx="4" fill="#22c55e">
                                                <animate attributeName="height" values="0;65" dur="0.8s" fill="freeze" begin="0.3s"/>
                                                <animate attributeName="y" values="100;35" dur="0.8s" fill="freeze" begin="0.3s"/>
                                            </rect>
                                            <rect x="173" y="100" width="28" height="0" rx="4" fill="#22c55e">
                                                <animate attributeName="height" values="0;55" dur="0.8s" fill="freeze" begin="0.4s"/>
                                                <animate attributeName="y" values="100;45" dur="0.8s" fill="freeze" begin="0.4s"/>
                                            </rect>
                                            <rect x="210" y="100" width="28" height="0" rx="4" fill="#22c55e">
                                                <animate attributeName="height" values="0;70" dur="0.8s" fill="freeze" begin="0.5s"/>
                                                <animate attributeName="y" values="100;30" dur="0.8s" fill="freeze" begin="0.5s"/>
                                            </rect>
                                            <rect x="247" y="100" width="28" height="0" rx="4" fill="#22c55e">
                                                <animate attributeName="height" values="0;75" dur="0.8s" fill="freeze" begin="0.6s"/>
                                                <animate attributeName="y" values="100;25" dur="0.8s" fill="freeze" begin="0.6s"/>
                                            </rect>

                                            <!-- Trend line -->
                                            <path d="M40 75 Q80 65, 125 55 T210 40 T275 25" fill="none" stroke="url(#chartLine)" stroke-width="3" stroke-linecap="round" opacity="0.8">
                                                <animate attributeName="stroke-dasharray" values="0,500;300,0" dur="1.5s" fill="freeze"/>
                                            </path>
                                        </g>

                                        <!-- Bottom Stats Row -->
                                        <g transform="translate(45, 322)">
                                            <rect x="0" y="0" width="95" height="50" rx="12" fill="#1e293b" stroke="#1877f2" stroke-width="1"/>
                                            <text x="48" y="18" text-anchor="middle" fill="#60a5fa" font-size="9">{{ $locale === 'ru' ? 'Показы' : 'Ko\'rishlar' }}</text>
                                            <text x="48" y="38" text-anchor="middle" fill="#93c5fd" font-size="16" font-weight="bold">125K</text>
                                        </g>
                                        <g transform="translate(150, 322)">
                                            <rect x="0" y="0" width="95" height="50" rx="12" fill="#1e293b" stroke="#22c55e" stroke-width="1"/>
                                            <text x="48" y="18" text-anchor="middle" fill="#4ade80" font-size="9">{{ $locale === 'ru' ? 'Клики' : 'Kliklar' }}</text>
                                            <text x="48" y="38" text-anchor="middle" fill="#86efac" font-size="16" font-weight="bold">8.2K</text>
                                        </g>
                                        <g transform="translate(255, 322)">
                                            <rect x="0" y="0" width="95" height="50" rx="12" fill="#1e293b" stroke="#f59e0b" stroke-width="1"/>
                                            <text x="48" y="18" text-anchor="middle" fill="#fbbf24" font-size="9">{{ $locale === 'ru' ? 'Конверсии' : 'Konversiya' }}</text>
                                            <text x="48" y="38" text-anchor="middle" fill="#fcd34d" font-size="16" font-weight="bold">342</text>
                                        </g>

                                        <!-- Floating notification -->
                                        <g transform="translate(365, 100)">
                                            <circle cx="0" cy="0" r="18" fill="#22c55e">
                                                <animate attributeName="r" values="18;22;18" dur="2s" repeatCount="indefinite"/>
                                            </circle>
                                            <text x="0" y="1" text-anchor="middle" fill="white" font-size="10" font-weight="bold">↑</text>
                                            <text x="0" y="10" text-anchor="middle" fill="white" font-size="8" font-weight="bold">ROI</text>
                                        </g>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 4: AI Chatbot -->
                    <div class="w-full flex-shrink-0 px-4">
                        <div class="bg-gradient-to-br from-emerald-500/10 to-teal-600/10 backdrop-blur-xl border border-white/10 rounded-3xl p-8 lg:p-12">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                                <div>
                                    <div class="inline-flex items-center px-3 py-1 bg-emerald-500/20 border border-emerald-500/30 rounded-full text-sm text-emerald-300 mb-6">
                                        <span class="w-2 h-2 bg-emerald-400 rounded-full mr-2 animate-pulse"></span>
                                        AI Powered
                                    </div>
                                    <h3 class="text-3xl lg:text-4xl font-bold text-white mb-6">
                                        {{ $locale === 'ru' ? 'AI Чатбот Продаж' : 'AI Sotuv Chatbot' }}
                                    </h3>
                                    <p class="text-slate-300 text-lg mb-8 leading-relaxed">
                                        {{ $locale === 'ru' ? 'Работает на основе скрипта продаж. Отвечает клиентам 24/7, обрабатывает возражения и закрывает сделки.' : 'Sotuv skripti asosida ishlaydigan AI chatbot. Mijozlarning savollariga 24/7 javob beradi, e\'tirozlarni bartaraf etadi va sotuvni yopadi.' }}
                                    </p>
                                    <ul class="space-y-4">
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-emerald-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Ответы по скрипту продаж' : 'Sotuv skripti bo\'yicha javob beradi' }}
                                        </li>
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-emerald-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Умная обработка возражений' : 'E\'tirozlarni aqlli qayta ishlaydi' }}
                                        </li>
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-emerald-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Автоматическая передача оператору' : 'Operatorga avtomatik uzatish' }}
                                        </li>
                                    </ul>
                                </div>
                                <!-- SVG Illustration: AI Sales Chatbot -->
                                <div class="relative">
                                    <svg viewBox="0 0 400 400" class="w-full max-w-md mx-auto" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="chatGradient2" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" style="stop-color:#10b981;stop-opacity:1" />
                                                <stop offset="100%" style="stop-color:#059669;stop-opacity:1" />
                                            </linearGradient>
                                            <linearGradient id="aiGradient2" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" style="stop-color:#8b5cf6;stop-opacity:1" />
                                                <stop offset="100%" style="stop-color:#6366f1;stop-opacity:1" />
                                            </linearGradient>
                                            <linearGradient id="brainGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" style="stop-color:#a855f7" />
                                                <stop offset="100%" style="stop-color:#6366f1" />
                                            </linearGradient>
                                            <filter id="aiShadow">
                                                <feDropShadow dx="0" dy="8" stdDeviation="12" flood-color="#10b981" flood-opacity="0.3"/>
                                            </filter>
                                            <filter id="glowPulse">
                                                <feGaussianBlur stdDeviation="4" result="coloredBlur"/>
                                                <feMerge>
                                                    <feMergeNode in="coloredBlur"/>
                                                    <feMergeNode in="SourceGraphic"/>
                                                </feMerge>
                                            </filter>
                                        </defs>

                                        <!-- Background glow -->
                                        <circle cx="200" cy="200" r="180" fill="#10b981" opacity="0.03"/>
                                        <circle cx="200" cy="200" r="120" fill="#8b5cf6" opacity="0.05"/>

                                        <!-- AI Brain Icon floating -->
                                        <g transform="translate(175, 10)" filter="url(#glowPulse)">
                                            <circle cx="25" cy="30" r="28" fill="url(#brainGradient)">
                                                <animate attributeName="r" values="28;32;28" dur="3s" repeatCount="indefinite"/>
                                            </circle>
                                            <text x="25" y="36" text-anchor="middle" fill="white" font-size="24">🤖</text>
                                        </g>

                                        <!-- Main Chat Window -->
                                        <g transform="translate(40, 75)" filter="url(#aiShadow)">
                                            <rect x="0" y="0" width="320" height="305" rx="24" fill="#0f172a" stroke="#334155" stroke-width="2"/>

                                            <!-- Header -->
                                            <rect x="0" y="0" width="320" height="60" rx="24" fill="#1e293b"/>
                                            <rect x="0" y="40" width="320" height="20" fill="#1e293b"/>

                                            <!-- AI Avatar -->
                                            <circle cx="40" cy="30" r="20" fill="url(#aiGradient2)"/>
                                            <text x="40" y="36" text-anchor="middle" fill="white" font-size="16" font-weight="bold">AI</text>

                                            <!-- Bot info with pulse -->
                                            <text x="70" y="25" fill="white" font-size="14" font-weight="bold">{{ $locale === 'ru' ? 'Ассистент' : 'Sotuv Assistenti' }}</text>
                                            <g transform="translate(70, 35)">
                                                <circle cx="5" cy="5" r="5" fill="#22c55e">
                                                    <animate attributeName="opacity" values="1;0.5;1" dur="2s" repeatCount="indefinite"/>
                                                </circle>
                                                <text x="15" y="9" fill="#94a3b8" font-size="10">Online • AI Powered</text>
                                            </g>

                                            <!-- Script indicator -->
                                            <g transform="translate(240, 15)">
                                                <rect x="0" y="0" width="65" height="28" rx="14" fill="#8b5cf6" opacity="0.2" stroke="#8b5cf6" stroke-width="1"/>
                                                <text x="12" y="18" fill="#a5b4fc" font-size="9" font-weight="bold">📝 Script</text>
                                            </g>

                                            <!-- User Message -->
                                            <g transform="translate(120, 75)">
                                                <rect x="0" y="0" width="185" height="42" rx="18" fill="#3b82f6"/>
                                                <text x="18" y="26" fill="white" font-size="12">{{ $locale === 'ru' ? 'Сколько стоит?' : 'Narxi qancha?' }}</text>
                                            </g>

                                            <!-- AI Response with typing effect feel -->
                                            <g transform="translate(15, 130)">
                                                <rect x="0" y="0" width="230" height="70" rx="18" fill="url(#chatGradient2)"/>
                                                <rect x="0" y="0" width="230" height="70" rx="18" fill="none" stroke="#34d399" stroke-width="1" opacity="0.5"/>
                                                <text x="18" y="22" fill="white" font-size="11" font-weight="500">{{ $locale === 'ru' ? 'Отличный вопрос! У нас' : 'Ajoyib savol! Bizda 3 ta' }}</text>
                                                <text x="18" y="40" fill="white" font-size="11" font-weight="500">{{ $locale === 'ru' ? '3 тарифа. Какой вам' : 'tarif mavjud. Sizga qaysi' }}</text>
                                                <text x="18" y="58" fill="white" font-size="11" font-weight="500">{{ $locale === 'ru' ? 'подходит?' : 'biri mos keladi?' }}</text>
                                            </g>

                                            <!-- Quick Reply Buttons -->
                                            <g transform="translate(15, 210)">
                                                <rect x="0" y="0" width="90" height="32" rx="12" fill="#1e293b" stroke="#10b981" stroke-width="1.5"/>
                                                <text x="45" y="21" text-anchor="middle" fill="#34d399" font-size="10" font-weight="bold">💼 Biznes</text>
                                            </g>
                                            <g transform="translate(110, 210)">
                                                <rect x="0" y="0" width="90" height="32" rx="12" fill="#1e293b" stroke="#3b82f6" stroke-width="1.5"/>
                                                <text x="45" y="21" text-anchor="middle" fill="#60a5fa" font-size="10" font-weight="bold">🚀 Pro</text>
                                            </g>
                                            <g transform="translate(205, 210)">
                                                <rect x="0" y="0" width="100" height="32" rx="12" fill="#1e293b" stroke="#f59e0b" stroke-width="1.5"/>
                                                <text x="50" y="21" text-anchor="middle" fill="#fbbf24" font-size="10" font-weight="bold">👑 Enterprise</text>
                                            </g>

                                            <!-- Typing Indicator -->
                                            <g transform="translate(15, 255)">
                                                <rect x="0" y="0" width="70" height="30" rx="15" fill="#1e293b"/>
                                                <circle cx="20" cy="15" r="4" fill="#10b981">
                                                    <animate attributeName="opacity" values="0.3;1;0.3" dur="1s" repeatCount="indefinite"/>
                                                </circle>
                                                <circle cx="35" cy="15" r="4" fill="#10b981">
                                                    <animate attributeName="opacity" values="0.3;1;0.3" dur="1s" repeatCount="indefinite" begin="0.2s"/>
                                                </circle>
                                                <circle cx="50" cy="15" r="4" fill="#10b981">
                                                    <animate attributeName="opacity" values="0.3;1;0.3" dur="1s" repeatCount="indefinite" begin="0.4s"/>
                                                </circle>
                                            </g>

                                            <!-- Stats badges -->
                                            <g transform="translate(120, 255)">
                                                <rect x="0" y="0" width="75" height="30" rx="10" fill="#22c55e" opacity="0.15" stroke="#22c55e" stroke-width="1"/>
                                                <text x="38" y="20" text-anchor="middle" fill="#4ade80" font-size="9" font-weight="bold">24/7 Active</text>
                                            </g>
                                            <g transform="translate(205, 255)">
                                                <rect x="0" y="0" width="100" height="30" rx="10" fill="#8b5cf6" opacity="0.15" stroke="#8b5cf6" stroke-width="1"/>
                                                <text x="50" y="20" text-anchor="middle" fill="#a5b4fc" font-size="9" font-weight="bold">+85% Conv</text>
                                            </g>
                                        </g>

                                        <!-- Floating Script Document -->
                                        <g transform="translate(365, 120)">
                                            <rect x="0" y="0" width="30" height="40" rx="6" fill="#1e293b" stroke="#8b5cf6" stroke-width="1.5">
                                                <animate attributeName="y" values="0;-5;0" dur="3s" repeatCount="indefinite"/>
                                            </rect>
                                            <rect x="5" y="8" width="20" height="2" rx="1" fill="#8b5cf6"/>
                                            <rect x="5" y="14" width="16" height="2" rx="1" fill="#8b5cf6" opacity="0.7"/>
                                            <rect x="5" y="20" width="18" height="2" rx="1" fill="#8b5cf6" opacity="0.5"/>
                                            <rect x="5" y="26" width="12" height="2" rx="1" fill="#8b5cf6" opacity="0.3"/>
                                        </g>

                                        <!-- Floating conversation bubbles -->
                                        <g transform="translate(365, 200)">
                                            <circle cx="15" cy="15" r="18" fill="#10b981" opacity="0.2" stroke="#10b981" stroke-width="1">
                                                <animate attributeName="r" values="18;22;18" dur="2.5s" repeatCount="indefinite"/>
                                            </circle>
                                            <text x="15" y="20" text-anchor="middle" fill="#10b981" font-size="14">💬</text>
                                        </g>
                                        <g transform="translate(365, 270)">
                                            <circle cx="15" cy="15" r="16" fill="#f59e0b" opacity="0.2" stroke="#f59e0b" stroke-width="1">
                                                <animate attributeName="r" values="16;20;16" dur="2.5s" repeatCount="indefinite" begin="0.5s"/>
                                            </circle>
                                            <text x="15" y="20" text-anchor="middle" fill="#f59e0b" font-size="12">⚡</text>
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
                                        {{ $locale === 'ru' ? 'Маркетинговый Алгоритм' : 'Marketing Algoritmi' }}
                                    </h3>
                                    <p class="text-slate-300 text-lg mb-8 leading-relaxed">
                                        {{ $locale === 'ru' ? 'AI разрабатывает маркетинговую стратегию для вашего бизнеса. Какой контент, когда, какой аудитории - все определяется автоматически.' : 'AI sizning biznesingizga mos marketing strategiyasini ishlab chiqadi. Qaysi kontent, qaysi vaqtda, qaysi auditoriyaga - hammasini avtomatik aniqlaydi.' }}
                                    </p>
                                    <ul class="space-y-4">
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Автоматизация A/B тестирования' : 'A/B testing avtomatizatsiya' }}
                                        </li>
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Генерация контент-календаря' : 'Kontent kalendar generatsiyasi' }}
                                        </li>
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Анализ оптимального времени постинга' : 'Optimal posting vaqti tahlili' }}
                                        </li>
                                    </ul>
                                </div>
                                <!-- SVG Illustration: Marketing Algorithm Network -->
                                <div class="relative">
                                    <svg viewBox="0 0 400 400" class="w-full max-w-md mx-auto" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="nodeGradient2" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
                                                <stop offset="100%" style="stop-color:#6366f1;stop-opacity:1" />
                                            </linearGradient>
                                            <linearGradient id="networkLine" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:0.6" />
                                                <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:0.3" />
                                            </linearGradient>
                                            <filter id="nodeShadow2">
                                                <feDropShadow dx="0" dy="6" stdDeviation="8" flood-color="#3b82f6" flood-opacity="0.4"/>
                                            </filter>
                                            <filter id="centerGlow">
                                                <feGaussianBlur stdDeviation="8" result="coloredBlur"/>
                                                <feMerge>
                                                    <feMergeNode in="coloredBlur"/>
                                                    <feMergeNode in="SourceGraphic"/>
                                                </feMerge>
                                            </filter>
                                        </defs>

                                        <!-- Background glow effects -->
                                        <circle cx="200" cy="200" r="180" fill="#3b82f6" opacity="0.03"/>
                                        <circle cx="200" cy="200" r="120" fill="#6366f1" opacity="0.05"/>

                                        <!-- Animated connection lines -->
                                        <g stroke="url(#networkLine)" stroke-width="2">
                                            <line x1="200" y1="200" x2="90" y2="90">
                                                <animate attributeName="stroke-dasharray" values="0,200;200,0" dur="2s" repeatCount="indefinite"/>
                                            </line>
                                            <line x1="200" y1="200" x2="310" y2="90">
                                                <animate attributeName="stroke-dasharray" values="0,200;200,0" dur="2s" repeatCount="indefinite" begin="0.3s"/>
                                            </line>
                                            <line x1="200" y1="200" x2="55" y2="200">
                                                <animate attributeName="stroke-dasharray" values="0,200;200,0" dur="2s" repeatCount="indefinite" begin="0.6s"/>
                                            </line>
                                            <line x1="200" y1="200" x2="345" y2="200">
                                                <animate attributeName="stroke-dasharray" values="0,200;200,0" dur="2s" repeatCount="indefinite" begin="0.9s"/>
                                            </line>
                                            <line x1="200" y1="200" x2="90" y2="310">
                                                <animate attributeName="stroke-dasharray" values="0,200;200,0" dur="2s" repeatCount="indefinite" begin="1.2s"/>
                                            </line>
                                            <line x1="200" y1="200" x2="310" y2="310">
                                                <animate attributeName="stroke-dasharray" values="0,200;200,0" dur="2s" repeatCount="indefinite" begin="1.5s"/>
                                            </line>
                                        </g>

                                        <!-- Center AI Node -->
                                        <g transform="translate(200, 200)" filter="url(#centerGlow)">
                                            <circle cx="0" cy="0" r="55" fill="#0f172a" stroke="url(#nodeGradient2)" stroke-width="3"/>
                                            <circle cx="0" cy="0" r="45" fill="url(#nodeGradient2)"/>
                                            <text x="0" y="-5" text-anchor="middle" fill="white" font-size="22" font-weight="bold">AI</text>
                                            <text x="0" y="12" text-anchor="middle" fill="#c7d2fe" font-size="9">MARKETING</text>
                                            <!-- Pulse rings -->
                                            <circle cx="0" cy="0" r="60" fill="none" stroke="#60a5fa" stroke-width="2" opacity="0.4">
                                                <animate attributeName="r" values="55;75;55" dur="2.5s" repeatCount="indefinite"/>
                                                <animate attributeName="opacity" values="0.4;0;0.4" dur="2.5s" repeatCount="indefinite"/>
                                            </circle>
                                            <circle cx="0" cy="0" r="60" fill="none" stroke="#8b5cf6" stroke-width="2" opacity="0.3">
                                                <animate attributeName="r" values="55;85;55" dur="2.5s" repeatCount="indefinite" begin="0.5s"/>
                                                <animate attributeName="opacity" values="0.3;0;0.3" dur="2.5s" repeatCount="indefinite" begin="0.5s"/>
                                            </circle>
                                        </g>

                                        <!-- Content Node -->
                                        <g transform="translate(90, 90)" filter="url(#nodeShadow2)">
                                            <circle cx="0" cy="0" r="35" fill="#0f172a" stroke="#3b82f6" stroke-width="2"/>
                                            <text x="0" y="-5" text-anchor="middle" fill="#3b82f6" font-size="16">📝</text>
                                            <text x="0" y="12" text-anchor="middle" fill="#60a5fa" font-size="9" font-weight="bold">Content</text>
                                        </g>

                                        <!-- Audience Node -->
                                        <g transform="translate(310, 90)" filter="url(#nodeShadow2)">
                                            <circle cx="0" cy="0" r="35" fill="#0f172a" stroke="#8b5cf6" stroke-width="2"/>
                                            <text x="0" y="-5" text-anchor="middle" fill="#8b5cf6" font-size="16">👥</text>
                                            <text x="0" y="12" text-anchor="middle" fill="#a78bfa" font-size="9" font-weight="bold">Audience</text>
                                        </g>

                                        <!-- Time Node -->
                                        <g transform="translate(55, 200)" filter="url(#nodeShadow2)">
                                            <circle cx="0" cy="0" r="32" fill="#0f172a" stroke="#f59e0b" stroke-width="2"/>
                                            <text x="0" y="-5" text-anchor="middle" fill="#f59e0b" font-size="16">⏰</text>
                                            <text x="0" y="12" text-anchor="middle" fill="#fbbf24" font-size="9" font-weight="bold">Time</text>
                                        </g>

                                        <!-- Channel Node -->
                                        <g transform="translate(345, 200)" filter="url(#nodeShadow2)">
                                            <circle cx="0" cy="0" r="32" fill="#0f172a" stroke="#10b981" stroke-width="2"/>
                                            <text x="0" y="-5" text-anchor="middle" fill="#10b981" font-size="16">📢</text>
                                            <text x="0" y="12" text-anchor="middle" fill="#34d399" font-size="9" font-weight="bold">Channel</text>
                                        </g>

                                        <!-- Budget Node -->
                                        <g transform="translate(90, 310)" filter="url(#nodeShadow2)">
                                            <circle cx="0" cy="0" r="35" fill="#0f172a" stroke="#f43f5e" stroke-width="2"/>
                                            <text x="0" y="-5" text-anchor="middle" fill="#f43f5e" font-size="16">💰</text>
                                            <text x="0" y="12" text-anchor="middle" fill="#fb7185" font-size="9" font-weight="bold">Budget</text>
                                        </g>

                                        <!-- ROI Node -->
                                        <g transform="translate(310, 310)" filter="url(#nodeShadow2)">
                                            <circle cx="0" cy="0" r="35" fill="#0f172a" stroke="#22c55e" stroke-width="2"/>
                                            <text x="0" y="-5" text-anchor="middle" fill="#22c55e" font-size="16">📈</text>
                                            <text x="0" y="12" text-anchor="middle" fill="#4ade80" font-size="9" font-weight="bold">ROI</text>
                                        </g>

                                        <!-- Animated data particles -->
                                        <circle r="5" fill="#60a5fa">
                                            <animateMotion dur="2s" repeatCount="indefinite" path="M200,200 L90,90"/>
                                            <animate attributeName="opacity" values="1;0.5;1" dur="2s" repeatCount="indefinite"/>
                                        </circle>
                                        <circle r="5" fill="#a78bfa">
                                            <animateMotion dur="2s" repeatCount="indefinite" begin="0.4s" path="M200,200 L310,90"/>
                                            <animate attributeName="opacity" values="1;0.5;1" dur="2s" repeatCount="indefinite" begin="0.4s"/>
                                        </circle>
                                        <circle r="5" fill="#fbbf24">
                                            <animateMotion dur="2s" repeatCount="indefinite" begin="0.8s" path="M200,200 L55,200"/>
                                            <animate attributeName="opacity" values="1;0.5;1" dur="2s" repeatCount="indefinite" begin="0.8s"/>
                                        </circle>
                                        <circle r="5" fill="#34d399">
                                            <animateMotion dur="2s" repeatCount="indefinite" begin="1.2s" path="M200,200 L345,200"/>
                                            <animate attributeName="opacity" values="1;0.5;1" dur="2s" repeatCount="indefinite" begin="1.2s"/>
                                        </circle>
                                        <circle r="5" fill="#fb7185">
                                            <animateMotion dur="2s" repeatCount="indefinite" begin="1.6s" path="M200,200 L90,310"/>
                                            <animate attributeName="opacity" values="1;0.5;1" dur="2s" repeatCount="indefinite" begin="1.6s"/>
                                        </circle>
                                        <circle r="5" fill="#4ade80">
                                            <animateMotion dur="2s" repeatCount="indefinite" begin="2s" path="M200,200 L310,310"/>
                                            <animate attributeName="opacity" values="1;0.5;1" dur="2s" repeatCount="indefinite" begin="2s"/>
                                        </circle>

                                        <!-- Stats badge -->
                                        <g transform="translate(160, 370)">
                                            <rect x="0" y="0" width="80" height="26" rx="13" fill="#22c55e" opacity="0.2" stroke="#22c55e" stroke-width="1"/>
                                            <text x="40" y="17" text-anchor="middle" fill="#4ade80" font-size="10" font-weight="bold">+150% ROI</text>
                                        </g>
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
                                        {{ $locale === 'ru' ? 'Анализ Операторов' : 'Call Operatorlar Tahlili' }}
                                    </h3>
                                    <p class="text-slate-300 text-lg mb-8 leading-relaxed">
                                        {{ $locale === 'ru' ? 'AI анализирует каждый разговор: эффективность оператора, удовлетворенность клиента и возможности продаж. Коучинг в реальном времени.' : 'AI har bir suhbatni tahlil qiladi: operatorning samaradorligini, mijoz qoniqishini va sotuv imkoniyatlarini aniqlaydi. Real-time coaching.' }}
                                    </p>
                                    <ul class="space-y-4">
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-amber-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Транскрипция и анализ разговоров' : 'Suhbat transkripsiyasi va tahlili' }}
                                        </li>
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-amber-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Анализ настроения и эмоций' : 'Sentiment va emotion detection' }}
                                        </li>
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-amber-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Оценка эффективности оператора' : 'Operator performance scoring' }}
                                        </li>
                                    </ul>
                                </div>
                                <!-- SVG Illustration: Call Analysis Dashboard -->
                                <div class="relative">
                                    <svg viewBox="0 0 400 400" class="w-full max-w-md mx-auto" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="waveGradient2" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" style="stop-color:#f59e0b;stop-opacity:1" />
                                                <stop offset="100%" style="stop-color:#ea580c;stop-opacity:1" />
                                            </linearGradient>
                                            <linearGradient id="sentimentGood" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" style="stop-color:#22c55e" />
                                                <stop offset="100%" style="stop-color:#10b981" />
                                            </linearGradient>
                                            <filter id="callShadow">
                                                <feDropShadow dx="0" dy="8" stdDeviation="12" flood-color="#f59e0b" flood-opacity="0.3"/>
                                            </filter>
                                        </defs>

                                        <!-- Background glow -->
                                        <circle cx="200" cy="200" r="180" fill="#f59e0b" opacity="0.03"/>
                                        <circle cx="200" cy="200" r="120" fill="#ea580c" opacity="0.05"/>

                                        <!-- Phone icon floating top -->
                                        <g transform="translate(175, 5)">
                                            <circle cx="25" cy="30" r="25" fill="#f59e0b">
                                                <animate attributeName="r" values="25;28;25" dur="2s" repeatCount="indefinite"/>
                                            </circle>
                                            <text x="25" y="36" text-anchor="middle" fill="white" font-size="20">📞</text>
                                        </g>

                                        <!-- Main Dashboard -->
                                        <g transform="translate(50, 65)" filter="url(#callShadow)">
                                            <rect x="0" y="0" width="300" height="320" rx="24" fill="#0f172a" stroke="#334155" stroke-width="2"/>

                                            <!-- Header -->
                                            <rect x="0" y="0" width="300" height="55" rx="24" fill="#1e293b"/>
                                            <rect x="0" y="35" width="300" height="20" fill="#1e293b"/>

                                            <!-- Live indicator -->
                                            <g transform="translate(20, 18)">
                                                <circle cx="10" cy="10" r="8" fill="#22c55e">
                                                    <animate attributeName="opacity" values="1;0.4;1" dur="1.5s" repeatCount="indefinite"/>
                                                </circle>
                                                <circle cx="10" cy="10" r="12" fill="none" stroke="#22c55e" stroke-width="2" opacity="0.5">
                                                    <animate attributeName="r" values="8;16;8" dur="1.5s" repeatCount="indefinite"/>
                                                    <animate attributeName="opacity" values="0.5;0;0.5" dur="1.5s" repeatCount="indefinite"/>
                                                </circle>
                                            </g>
                                            <text x="45" y="32" fill="white" font-size="13" font-weight="bold">Live Call</text>
                                            <text x="125" y="32" fill="#94a3b8" font-size="12">03:45</text>

                                            <!-- Operator info -->
                                            <g transform="translate(200, 12)">
                                                <circle cx="20" cy="15" r="14" fill="#f59e0b" opacity="0.2"/>
                                                <text x="20" y="20" text-anchor="middle" fill="#f59e0b" font-size="14">👤</text>
                                                <text x="50" y="12" fill="#94a3b8" font-size="9">Operator</text>
                                                <text x="50" y="25" fill="white" font-size="10" font-weight="bold">Aziza M.</text>
                                            </g>

                                            <!-- Audio Waveform -->
                                            <g transform="translate(15, 70)">
                                                <rect x="0" y="0" width="270" height="65" rx="12" fill="#1e293b" stroke="#f59e0b" stroke-width="1" opacity="0.5"/>
                                                <text x="12" y="18" fill="#f59e0b" font-size="9" font-weight="bold">🎙️ AUDIO</text>
                                                <!-- Waveform bars -->
                                                <g transform="translate(15, 25)">
                                                    <rect x="0" y="15" width="10" height="15" rx="3" fill="url(#waveGradient2)">
                                                        <animate attributeName="height" values="15;30;15;22;15" dur="0.6s" repeatCount="indefinite"/>
                                                        <animate attributeName="y" values="15;7;15;11;15" dur="0.6s" repeatCount="indefinite"/>
                                                    </rect>
                                                    <rect x="18" y="10" width="10" height="25" rx="3" fill="url(#waveGradient2)">
                                                        <animate attributeName="height" values="25;12;25;18;25" dur="0.6s" repeatCount="indefinite" begin="0.1s"/>
                                                        <animate attributeName="y" values="7;14;7;11;7" dur="0.6s" repeatCount="indefinite" begin="0.1s"/>
                                                    </rect>
                                                    <rect x="36" y="5" width="10" height="35" rx="3" fill="url(#waveGradient2)">
                                                        <animate attributeName="height" values="35;18;35;28;35" dur="0.6s" repeatCount="indefinite" begin="0.2s"/>
                                                        <animate attributeName="y" values="2;11;2;6;2" dur="0.6s" repeatCount="indefinite" begin="0.2s"/>
                                                    </rect>
                                                    <rect x="54" y="12" width="10" height="20" rx="3" fill="url(#waveGradient2)">
                                                        <animate attributeName="height" values="20;32;20;26;20" dur="0.6s" repeatCount="indefinite" begin="0.15s"/>
                                                        <animate attributeName="y" values="10;4;10;7;10" dur="0.6s" repeatCount="indefinite" begin="0.15s"/>
                                                    </rect>
                                                    <rect x="72" y="8" width="10" height="28" rx="3" fill="url(#waveGradient2)">
                                                        <animate attributeName="height" values="28;15;28;22;28" dur="0.6s" repeatCount="indefinite" begin="0.25s"/>
                                                        <animate attributeName="y" values="6;13;6;9;6" dur="0.6s" repeatCount="indefinite" begin="0.25s"/>
                                                    </rect>
                                                    <rect x="90" y="10" width="10" height="24" rx="3" fill="url(#waveGradient2)">
                                                        <animate attributeName="height" values="24;36;24;18;24" dur="0.6s" repeatCount="indefinite" begin="0.3s"/>
                                                        <animate attributeName="y" values="8;2;8;11;8" dur="0.6s" repeatCount="indefinite" begin="0.3s"/>
                                                    </rect>
                                                    <rect x="108" y="15" width="10" height="15" rx="3" fill="url(#waveGradient2)">
                                                        <animate attributeName="height" values="15;28;15;20;15" dur="0.6s" repeatCount="indefinite" begin="0.35s"/>
                                                        <animate attributeName="y" values="13;6;13;10;13" dur="0.6s" repeatCount="indefinite" begin="0.35s"/>
                                                    </rect>
                                                    <rect x="126" y="7" width="10" height="30" rx="3" fill="url(#waveGradient2)">
                                                        <animate attributeName="height" values="30;16;30;24;30" dur="0.6s" repeatCount="indefinite" begin="0.05s"/>
                                                        <animate attributeName="y" values="5;12;5;8;5" dur="0.6s" repeatCount="indefinite" begin="0.05s"/>
                                                    </rect>
                                                    <rect x="144" y="12" width="10" height="20" rx="3" fill="url(#waveGradient2)">
                                                        <animate attributeName="height" values="20;30;20;26;20" dur="0.6s" repeatCount="indefinite" begin="0.4s"/>
                                                        <animate attributeName="y" values="10;5;10;7;10" dur="0.6s" repeatCount="indefinite" begin="0.4s"/>
                                                    </rect>
                                                    <rect x="162" y="8" width="10" height="28" rx="3" fill="url(#waveGradient2)">
                                                        <animate attributeName="height" values="28;18;28;22;28" dur="0.6s" repeatCount="indefinite" begin="0.45s"/>
                                                        <animate attributeName="y" values="6;11;6;9;6" dur="0.6s" repeatCount="indefinite" begin="0.45s"/>
                                                    </rect>
                                                    <rect x="180" y="10" width="10" height="24" rx="3" fill="url(#waveGradient2)">
                                                        <animate attributeName="height" values="24;14;24;32;24" dur="0.6s" repeatCount="indefinite" begin="0.2s"/>
                                                        <animate attributeName="y" values="8;13;8;4;8" dur="0.6s" repeatCount="indefinite" begin="0.2s"/>
                                                    </rect>
                                                    <rect x="198" y="13" width="10" height="18" rx="3" fill="url(#waveGradient2)">
                                                        <animate attributeName="height" values="18;26;18;22;18" dur="0.6s" repeatCount="indefinite" begin="0.3s"/>
                                                        <animate attributeName="y" values="11;7;11;9;11" dur="0.6s" repeatCount="indefinite" begin="0.3s"/>
                                                    </rect>
                                                    <rect x="216" y="9" width="10" height="26" rx="3" fill="url(#waveGradient2)">
                                                        <animate attributeName="height" values="26;16;26;20;26" dur="0.6s" repeatCount="indefinite" begin="0.1s"/>
                                                        <animate attributeName="y" values="7;12;7;10;7" dur="0.6s" repeatCount="indefinite" begin="0.1s"/>
                                                    </rect>
                                                    <rect x="234" y="14" width="10" height="16" rx="3" fill="url(#waveGradient2)">
                                                        <animate attributeName="height" values="16;24;16;20;16" dur="0.6s" repeatCount="indefinite" begin="0.25s"/>
                                                        <animate attributeName="y" values="12;8;12;10;12" dur="0.6s" repeatCount="indefinite" begin="0.25s"/>
                                                    </rect>
                                                </g>
                                            </g>

                                            <!-- AI Analysis Panel -->
                                            <g transform="translate(15, 150)">
                                                <rect x="0" y="0" width="270" height="155" rx="12" fill="#1e293b"/>

                                                <!-- Header -->
                                                <rect x="0" y="0" width="270" height="30" rx="12" fill="#f59e0b" opacity="0.15"/>
                                                <rect x="0" y="20" width="270" height="10" fill="#f59e0b" opacity="0.15"/>
                                                <text x="15" y="20" fill="#f59e0b" font-size="11" font-weight="bold">🤖 AI TAHLIL</text>
                                                <g transform="translate(200, 5)">
                                                    <rect x="0" y="0" width="55" height="20" rx="10" fill="#22c55e" opacity="0.2" stroke="#22c55e" stroke-width="1"/>
                                                    <text x="28" y="14" text-anchor="middle" fill="#22c55e" font-size="9" font-weight="bold">LIVE</text>
                                                </g>

                                                <!-- Metrics -->
                                                <g transform="translate(15, 45)">
                                                    <text x="0" y="0" fill="#94a3b8" font-size="10">Sentiment</text>
                                                    <rect x="75" y="-10" width="150" height="14" rx="5" fill="#0f172a"/>
                                                    <rect x="75" y="-10" width="0" height="14" rx="5" fill="url(#sentimentGood)">
                                                        <animate attributeName="width" values="0;117;110;117" dur="2s" fill="freeze"/>
                                                    </rect>
                                                    <text x="230" y="0" fill="#22c55e" font-size="10" font-weight="bold">78%</text>
                                                    <text x="240" y="0" fill="#22c55e" font-size="10">😊</text>
                                                </g>

                                                <g transform="translate(15, 75)">
                                                    <text x="0" y="0" fill="#94a3b8" font-size="10">{{ $locale === 'ru' ? 'Скрипт' : 'Skript' }}</text>
                                                    <rect x="75" y="-10" width="150" height="14" rx="5" fill="#0f172a"/>
                                                    <rect x="75" y="-10" width="0" height="14" rx="5" fill="#3b82f6">
                                                        <animate attributeName="width" values="0;128;120;128" dur="2s" fill="freeze"/>
                                                    </rect>
                                                    <text x="230" y="0" fill="#3b82f6" font-size="10" font-weight="bold">85%</text>
                                                    <text x="240" y="0" fill="#3b82f6" font-size="10">📋</text>
                                                </g>

                                                <g transform="translate(15, 105)">
                                                    <text x="0" y="0" fill="#94a3b8" font-size="10">{{ $locale === 'ru' ? 'Оценка' : 'Baholash' }}</text>
                                                    <rect x="75" y="-10" width="150" height="14" rx="5" fill="#0f172a"/>
                                                    <rect x="75" y="-10" width="0" height="14" rx="5" fill="#f59e0b">
                                                        <animate attributeName="width" values="0;138;132;138" dur="2s" fill="freeze"/>
                                                    </rect>
                                                    <text x="230" y="0" fill="#f59e0b" font-size="10" font-weight="bold">92%</text>
                                                    <text x="240" y="0" fill="#f59e0b" font-size="10">⭐</text>
                                                </g>

                                                <!-- Keywords detected -->
                                                <g transform="translate(15, 130)">
                                                    <rect x="0" y="0" width="60" height="18" rx="6" fill="#8b5cf6" opacity="0.2" stroke="#8b5cf6" stroke-width="1"/>
                                                    <text x="30" y="12" text-anchor="middle" fill="#a5b4fc" font-size="8">narx</text>
                                                    <rect x="68" y="0" width="60" height="18" rx="6" fill="#22c55e" opacity="0.2" stroke="#22c55e" stroke-width="1"/>
                                                    <text x="98" y="12" text-anchor="middle" fill="#4ade80" font-size="8">buyurtma</text>
                                                    <rect x="136" y="0" width="55" height="18" rx="6" fill="#f59e0b" opacity="0.2" stroke="#f59e0b" stroke-width="1"/>
                                                    <text x="163" y="12" text-anchor="middle" fill="#fbbf24" font-size="8">yetkazish</text>
                                                </g>
                                            </g>
                                        </g>

                                        <!-- Floating badges -->
                                        <g transform="translate(360, 130)">
                                            <circle cx="20" cy="20" r="22" fill="#1e293b" stroke="#22c55e" stroke-width="2">
                                                <animate attributeName="r" values="22;25;22" dur="2s" repeatCount="indefinite"/>
                                            </circle>
                                            <text x="20" y="25" text-anchor="middle" fill="#22c55e" font-size="14">✓</text>
                                        </g>
                                        <g transform="translate(360, 200)">
                                            <circle cx="20" cy="20" r="20" fill="#1e293b" stroke="#3b82f6" stroke-width="2">
                                                <animate attributeName="r" values="20;23;20" dur="2s" repeatCount="indefinite" begin="0.5s"/>
                                            </circle>
                                            <text x="20" y="25" text-anchor="middle" fill="#3b82f6" font-size="14">📊</text>
                                        </g>
                                        <g transform="translate(360, 270)">
                                            <circle cx="20" cy="20" r="18" fill="#1e293b" stroke="#f59e0b" stroke-width="2">
                                                <animate attributeName="r" values="18;21;18" dur="2s" repeatCount="indefinite" begin="1s"/>
                                            </circle>
                                            <text x="20" y="25" text-anchor="middle" fill="#f59e0b" font-size="14">🎯</text>
                                        </g>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 7: Moliya/Finance Module -->
                    <div class="w-full flex-shrink-0 px-4">
                        <div class="bg-gradient-to-br from-emerald-500/10 to-green-600/10 backdrop-blur-xl border border-white/10 rounded-3xl p-8 lg:p-12">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                                <div>
                                    <div class="inline-flex items-center px-3 py-1 bg-emerald-500/20 border border-emerald-500/30 rounded-full text-sm text-emerald-300 mb-6">
                                        <span class="w-2 h-2 bg-emerald-400 rounded-full mr-2 animate-pulse"></span>
                                        {{ $locale === 'ru' ? 'Финансовый учет' : 'Moliyaviy boshqaruv' }}
                                    </div>
                                    <h3 class="text-3xl lg:text-4xl font-bold text-white mb-6">
                                        {{ $locale === 'ru' ? 'Модуль Финансов' : 'Moliya Moduli' }}
                                    </h3>
                                    <p class="text-slate-300 text-lg mb-8 leading-relaxed">
                                        {{ $locale === 'ru' ? 'Полный контроль над финансами бизнеса. Доходы, расходы, счета-фактуры, платежи и отчеты для бухгалтерии - все в одном месте.' : 'Biznes moliyasi ustidan to\'liq nazorat. Kirim-chiqim, fakturalar, to\'lovlar va buxgalteriya uchun hisobotlar - barchasi bir joyda.' }}
                                    </p>
                                    <ul class="space-y-4">
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-emerald-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Учет доходов и расходов' : 'Kirim-chiqim hisobi' }}
                                        </li>
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-emerald-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Счета-фактуры и платежи' : 'Fakturalar va to\'lovlar' }}
                                        </li>
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-emerald-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Отчеты для бухгалтерии' : 'Buxgalteriya hisobotlari' }}
                                        </li>
                                    </ul>
                                </div>
                                <!-- SVG Illustration: Finance Dashboard -->
                                <div class="relative">
                                    <svg viewBox="0 0 400 400" class="w-full max-w-md mx-auto" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="financeGradient2" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" style="stop-color:#10b981;stop-opacity:1" />
                                                <stop offset="100%" style="stop-color:#059669;stop-opacity:1" />
                                            </linearGradient>
                                            <linearGradient id="moneyGradient2" x1="0%" y1="100%" x2="0%" y2="0%">
                                                <stop offset="0%" style="stop-color:#10b981;stop-opacity:0.2" />
                                                <stop offset="100%" style="stop-color:#34d399;stop-opacity:0.6" />
                                            </linearGradient>
                                            <linearGradient id="cashflowLine" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" style="stop-color:#10b981" />
                                                <stop offset="100%" style="stop-color:#34d399" />
                                            </linearGradient>
                                            <filter id="financeShadow">
                                                <feDropShadow dx="0" dy="8" stdDeviation="12" flood-color="#10b981" flood-opacity="0.3"/>
                                            </filter>
                                        </defs>

                                        <!-- Background glow -->
                                        <circle cx="200" cy="200" r="180" fill="#10b981" opacity="0.03"/>
                                        <circle cx="200" cy="200" r="120" fill="#059669" opacity="0.05"/>

                                        <!-- Money icon floating top -->
                                        <g transform="translate(175, 5)">
                                            <circle cx="25" cy="28" r="26" fill="url(#financeGradient2)">
                                                <animate attributeName="r" values="26;30;26" dur="2.5s" repeatCount="indefinite"/>
                                            </circle>
                                            <text x="25" y="35" text-anchor="middle" fill="white" font-size="22">💰</text>
                                        </g>

                                        <!-- Main Dashboard -->
                                        <g transform="translate(35, 65)" filter="url(#financeShadow)">
                                            <rect x="0" y="0" width="330" height="320" rx="24" fill="#0f172a" stroke="#334155" stroke-width="2"/>

                                            <!-- Header -->
                                            <rect x="0" y="0" width="330" height="55" rx="24" fill="url(#financeGradient2)"/>
                                            <rect x="0" y="35" width="330" height="20" fill="url(#financeGradient2)"/>
                                            <text x="20" y="35" fill="white" font-size="14" font-weight="bold">{{ $locale === 'ru' ? 'Финансы' : 'Moliya Dashboard' }}</text>

                                            <!-- Month selector -->
                                            <g transform="translate(230, 15)">
                                                <rect x="0" y="0" width="85" height="26" rx="13" fill="white" opacity="0.2"/>
                                                <text x="42" y="17" text-anchor="middle" fill="white" font-size="10" font-weight="bold">Yanvar 2026</text>
                                            </g>

                                            <!-- Balance Card -->
                                            <g transform="translate(15, 70)">
                                                <rect x="0" y="0" width="300" height="75" rx="16" fill="#1e293b" stroke="#10b981" stroke-width="1"/>
                                                <text x="18" y="22" fill="#94a3b8" font-size="11">{{ $locale === 'ru' ? 'Общий баланс' : 'Umumiy balans' }}</text>
                                                <text x="18" y="52" fill="#4ade80" font-size="28" font-weight="bold">$124,500</text>
                                                <g transform="translate(210, 20)">
                                                    <rect x="0" y="0" width="75" height="35" rx="10" fill="#22c55e" opacity="0.15" stroke="#22c55e" stroke-width="1"/>
                                                    <text x="38" y="15" text-anchor="middle" fill="#86efac" font-size="9">Bu oy</text>
                                                    <text x="38" y="28" text-anchor="middle" fill="#4ade80" font-size="12" font-weight="bold">↑ +12.5%</text>
                                                </g>
                                            </g>

                                            <!-- Income/Expense Cards Row -->
                                            <g transform="translate(15, 160)">
                                                <!-- Income Card -->
                                                <rect x="0" y="0" width="145" height="70" rx="14" fill="#1e293b" stroke="#22c55e" stroke-width="1"/>
                                                <g transform="translate(15, 15)">
                                                    <circle cx="12" cy="12" r="12" fill="#22c55e" opacity="0.2"/>
                                                    <path d="M8 12 L12 8 L16 12" stroke="#22c55e" stroke-width="2" fill="none" stroke-linecap="round"/>
                                                    <line x1="12" y1="8" x2="12" y2="16" stroke="#22c55e" stroke-width="2" stroke-linecap="round"/>
                                                </g>
                                                <text x="50" y="22" fill="#94a3b8" font-size="10">{{ $locale === 'ru' ? 'Доходы' : 'Kirim' }}</text>
                                                <text x="15" y="52" fill="#4ade80" font-size="20" font-weight="bold">$89,400</text>

                                                <!-- Expense Card -->
                                                <g transform="translate(155, 0)">
                                                    <rect x="0" y="0" width="145" height="70" rx="14" fill="#1e293b" stroke="#ef4444" stroke-width="1"/>
                                                    <g transform="translate(15, 15)">
                                                        <circle cx="12" cy="12" r="12" fill="#ef4444" opacity="0.2"/>
                                                        <path d="M8 12 L12 16 L16 12" stroke="#ef4444" stroke-width="2" fill="none" stroke-linecap="round"/>
                                                        <line x1="12" y1="8" x2="12" y2="16" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/>
                                                    </g>
                                                    <text x="50" y="22" fill="#94a3b8" font-size="10">{{ $locale === 'ru' ? 'Расходы' : 'Chiqim' }}</text>
                                                    <text x="15" y="52" fill="#f87171" font-size="20" font-weight="bold">$35,100</text>
                                                </g>
                                            </g>

                                            <!-- Cash Flow Chart -->
                                            <g transform="translate(15, 245)">
                                                <rect x="0" y="0" width="300" height="60" rx="14" fill="#1e293b"/>
                                                <text x="15" y="18" fill="#94a3b8" font-size="10" font-weight="500">{{ $locale === 'ru' ? 'Денежный поток' : 'Pul oqimi' }}</text>

                                                <!-- Chart grid lines -->
                                                <line x1="15" y1="30" x2="285" y2="30" stroke="#334155" stroke-width="1" stroke-dasharray="3 2" opacity="0.5"/>
                                                <line x1="15" y1="45" x2="285" y2="45" stroke="#334155" stroke-width="1" stroke-dasharray="3 2" opacity="0.5"/>

                                                <!-- Cash flow line -->
                                                <path d="M20 50 Q50 38, 80 42 T140 32 T200 28 T260 22 T285 18" fill="none" stroke="url(#cashflowLine)" stroke-width="3" stroke-linecap="round">
                                                    <animate attributeName="stroke-dasharray" values="0,400;280,0" dur="1.5s" fill="freeze"/>
                                                </path>
                                                <!-- Area under line -->
                                                <path d="M20 50 Q50 38, 80 42 T140 32 T200 28 T260 22 T285 18 L285 55 L20 55 Z" fill="url(#moneyGradient2)" opacity="0.5"/>

                                                <!-- Data points -->
                                                <circle cx="80" cy="42" r="4" fill="#10b981">
                                                    <animate attributeName="opacity" values="0;1" dur="0.4s" fill="freeze" begin="0.4s"/>
                                                </circle>
                                                <circle cx="140" cy="32" r="4" fill="#10b981">
                                                    <animate attributeName="opacity" values="0;1" dur="0.4s" fill="freeze" begin="0.7s"/>
                                                </circle>
                                                <circle cx="200" cy="28" r="4" fill="#10b981">
                                                    <animate attributeName="opacity" values="0;1" dur="0.4s" fill="freeze" begin="1s"/>
                                                </circle>
                                                <circle cx="260" cy="22" r="4" fill="#10b981">
                                                    <animate attributeName="opacity" values="0;1" dur="0.4s" fill="freeze" begin="1.3s"/>
                                                </circle>
                                            </g>
                                        </g>

                                        <!-- Floating Invoice Icon -->
                                        <g transform="translate(370, 120)">
                                            <rect x="0" y="0" width="28" height="38" rx="6" fill="#1e293b" stroke="#10b981" stroke-width="1.5">
                                                <animate attributeName="y" values="0;-5;0" dur="3s" repeatCount="indefinite"/>
                                            </rect>
                                            <rect x="5" y="7" width="18" height="2" rx="1" fill="#34d399"/>
                                            <rect x="5" y="12" width="14" height="2" rx="1" fill="#34d399" opacity="0.6"/>
                                            <rect x="5" y="17" width="16" height="2" rx="1" fill="#34d399" opacity="0.6"/>
                                            <rect x="5" y="22" width="10" height="2" rx="1" fill="#34d399" opacity="0.4"/>
                                            <text x="14" y="33" text-anchor="middle" fill="#4ade80" font-size="6">INV</text>
                                        </g>

                                        <!-- Floating elements -->
                                        <g transform="translate(370, 200)">
                                            <circle cx="15" cy="15" r="18" fill="#1e293b" stroke="#22c55e" stroke-width="2">
                                                <animate attributeName="r" values="18;21;18" dur="2.5s" repeatCount="indefinite"/>
                                            </circle>
                                            <text x="15" y="20" text-anchor="middle" fill="#22c55e" font-size="14">💵</text>
                                        </g>
                                        <g transform="translate(370, 270)">
                                            <circle cx="15" cy="15" r="16" fill="#1e293b" stroke="#f59e0b" stroke-width="2">
                                                <animate attributeName="r" values="16;19;16" dur="2.5s" repeatCount="indefinite" begin="0.5s"/>
                                            </circle>
                                            <text x="15" y="20" text-anchor="middle" fill="#f59e0b" font-size="12">📊</text>
                                        </g>
                                        <g transform="translate(370, 335)">
                                            <circle cx="15" cy="15" r="14" fill="#1e293b" stroke="#8b5cf6" stroke-width="2">
                                                <animate attributeName="r" values="14;17;14" dur="2.5s" repeatCount="indefinite" begin="1s"/>
                                            </circle>
                                            <text x="15" y="20" text-anchor="middle" fill="#8b5cf6" font-size="11">📑</text>
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
                                        {{ $locale === 'ru' ? 'Модуль KPI' : 'KPI Moduli' }}
                                    </h3>
                                    <p class="text-slate-300 text-lg mb-8 leading-relaxed">
                                        {{ $locale === 'ru' ? 'Отслеживайте ключевые показатели бизнеса в реальном времени. Ставьте цели, следите за прогрессом и повышайте эффективность команды.' : 'Biznesingizning asosiy ko\'rsatkichlarini real vaqtda kuzating. Maqsadlar qo\'ying, progressni tracking qiling va jamoa samaradorligini oshiring.' }}
                                    </p>
                                    <ul class="space-y-4">
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-cyan-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Real-time KPI дашборды' : 'Real-time KPI dashboardlar' }}
                                        </li>
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-cyan-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Постановка и отслеживание целей' : 'Goal setting va tracking' }}
                                        </li>
                                        <li class="flex items-center text-slate-300">
                                            <div class="w-8 h-8 bg-cyan-500/20 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            {{ $locale === 'ru' ? 'Лидерборды и геймификация' : 'Team leaderboards va gamification' }}
                                        </li>
                                    </ul>
                                </div>
                                <!-- SVG Illustration: KPI Dashboard -->
                                <div class="relative">
                                    <svg viewBox="0 0 400 400" class="w-full max-w-md mx-auto" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="kpiGradient2" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" style="stop-color:#06b6d4;stop-opacity:1" />
                                                <stop offset="100%" style="stop-color:#3b82f6;stop-opacity:1" />
                                            </linearGradient>
                                            <linearGradient id="gaugeGradient2" x1="0%" y1="100%" x2="0%" y2="0%">
                                                <stop offset="0%" style="stop-color:#ef4444" />
                                                <stop offset="50%" style="stop-color:#f59e0b" />
                                                <stop offset="100%" style="stop-color:#22c55e" />
                                            </linearGradient>
                                            <filter id="kpiShadow">
                                                <feDropShadow dx="0" dy="8" stdDeviation="12" flood-color="#06b6d4" flood-opacity="0.3"/>
                                            </filter>
                                            <filter id="gaugeGlow">
                                                <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                                                <feMerge>
                                                    <feMergeNode in="coloredBlur"/>
                                                    <feMergeNode in="SourceGraphic"/>
                                                </feMerge>
                                            </filter>
                                        </defs>

                                        <!-- Background glow -->
                                        <circle cx="200" cy="200" r="180" fill="#06b6d4" opacity="0.03"/>
                                        <circle cx="200" cy="200" r="120" fill="#3b82f6" opacity="0.05"/>

                                        <!-- KPI Icon floating top -->
                                        <g transform="translate(175, 5)">
                                            <circle cx="25" cy="28" r="26" fill="url(#kpiGradient2)">
                                                <animate attributeName="r" values="26;30;26" dur="2.5s" repeatCount="indefinite"/>
                                            </circle>
                                            <text x="25" y="35" text-anchor="middle" fill="white" font-size="20">📊</text>
                                        </g>

                                        <!-- Main Dashboard -->
                                        <g transform="translate(35, 65)" filter="url(#kpiShadow)">
                                            <rect x="0" y="0" width="330" height="320" rx="24" fill="#0f172a" stroke="#334155" stroke-width="2"/>

                                            <!-- Header -->
                                            <rect x="0" y="0" width="330" height="50" rx="24" fill="#1e293b"/>
                                            <rect x="0" y="35" width="330" height="15" fill="#1e293b"/>
                                            <text x="20" y="32" fill="white" font-size="14" font-weight="bold">KPI Dashboard</text>
                                            <g transform="translate(250, 13)">
                                                <circle cx="10" cy="10" r="8" fill="#22c55e">
                                                    <animate attributeName="opacity" values="1;0.4;1" dur="2s" repeatCount="indefinite"/>
                                                </circle>
                                                <text x="25" y="14" fill="#94a3b8" font-size="9">Live</text>
                                            </g>

                                            <!-- Main Gauge -->
                                            <g transform="translate(165, 135)" filter="url(#gaugeGlow)">
                                                <!-- Background circle -->
                                                <circle cx="0" cy="0" r="65" fill="#1e293b" stroke="#334155" stroke-width="2"/>
                                                <!-- Gauge track -->
                                                <circle cx="0" cy="0" r="55" fill="none" stroke="#1e293b" stroke-width="14"/>
                                                <!-- Gauge progress -->
                                                <circle cx="0" cy="0" r="55" fill="none" stroke="url(#gaugeGradient2)" stroke-width="14"
                                                        stroke-dasharray="0 346" stroke-linecap="round" transform="rotate(-90)">
                                                    <animate attributeName="stroke-dasharray" values="0 346;302 346" dur="2s" fill="freeze"/>
                                                </circle>
                                                <!-- Inner circle -->
                                                <circle cx="0" cy="0" r="42" fill="#0f172a"/>
                                                <!-- Score -->
                                                <text x="0" y="5" text-anchor="middle" fill="white" font-size="30" font-weight="bold">87%</text>
                                                <text x="0" y="22" text-anchor="middle" fill="#94a3b8" font-size="9">Overall Score</text>
                                            </g>

                                            <!-- Target Badge -->
                                            <g transform="translate(260, 70)">
                                                <rect x="0" y="0" width="55" height="48" rx="12" fill="#06b6d4" opacity="0.15" stroke="#06b6d4" stroke-width="1"/>
                                                <text x="28" y="18" text-anchor="middle" fill="#67e8f9" font-size="8" font-weight="bold">TARGET</text>
                                                <text x="28" y="38" text-anchor="middle" fill="#22d3ee" font-size="16" font-weight="bold">92%</text>
                                            </g>

                                            <!-- Quarter Goals -->
                                            <g transform="translate(15, 70)">
                                                <!-- Q1 -->
                                                <g transform="translate(0, 0)">
                                                    <text x="0" y="10" fill="#94a3b8" font-size="9">Q1 Goal</text>
                                                    <rect x="0" y="15" width="100" height="10" rx="5" fill="#1e293b"/>
                                                    <rect x="0" y="15" width="0" height="10" rx="5" fill="#22c55e">
                                                        <animate attributeName="width" values="0;90" dur="1.5s" fill="freeze"/>
                                                    </rect>
                                                    <text x="105" y="23" fill="#4ade80" font-size="9" font-weight="bold">90%</text>
                                                </g>
                                                <!-- Q2 -->
                                                <g transform="translate(0, 35)">
                                                    <text x="0" y="10" fill="#94a3b8" font-size="9">Q2 Goal</text>
                                                    <rect x="0" y="15" width="100" height="10" rx="5" fill="#1e293b"/>
                                                    <rect x="0" y="15" width="0" height="10" rx="5" fill="#f59e0b">
                                                        <animate attributeName="width" values="0;81" dur="1.5s" fill="freeze" begin="0.3s"/>
                                                    </rect>
                                                    <text x="105" y="23" fill="#fbbf24" font-size="9" font-weight="bold">81%</text>
                                                </g>
                                                <!-- Q3 -->
                                                <g transform="translate(0, 70)">
                                                    <text x="0" y="10" fill="#94a3b8" font-size="9">Q3 Goal</text>
                                                    <rect x="0" y="15" width="100" height="10" rx="5" fill="#1e293b"/>
                                                    <rect x="0" y="15" width="0" height="10" rx="5" fill="#06b6d4">
                                                        <animate attributeName="width" values="0;60" dur="1.5s" fill="freeze" begin="0.6s"/>
                                                    </rect>
                                                    <text x="105" y="23" fill="#22d3ee" font-size="9" font-weight="bold">60%</text>
                                                </g>
                                            </g>

                                            <!-- Stats Cards Row -->
                                            <g transform="translate(15, 220)">
                                                <!-- Sales Card -->
                                                <rect x="0" y="0" width="95" height="75" rx="14" fill="#1e293b" stroke="#22c55e" stroke-width="1"/>
                                                <text x="12" y="20" fill="#94a3b8" font-size="9">{{ $locale === 'ru' ? 'Продажи' : 'Sotuvlar' }}</text>
                                                <text x="12" y="45" fill="#4ade80" font-size="20" font-weight="bold">+23%</text>
                                                <!-- Mini chart -->
                                                <path d="M12 60 L25 55 L38 58 L51 48 L64 52 L77 42 L82 45" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round">
                                                    <animate attributeName="stroke-dasharray" values="0,100;100,0" dur="1s" fill="freeze"/>
                                                </path>

                                                <!-- Leads Card -->
                                                <g transform="translate(110, 0)">
                                                    <rect x="0" y="0" width="95" height="75" rx="14" fill="#1e293b" stroke="#06b6d4" stroke-width="1"/>
                                                    <text x="12" y="20" fill="#94a3b8" font-size="9">{{ $locale === 'ru' ? 'Лиды' : 'Leadlar' }}</text>
                                                    <text x="12" y="45" fill="#22d3ee" font-size="20" font-weight="bold">156</text>
                                                    <path d="M12 55 L25 58 L38 50 L51 55 L64 48 L77 52 L82 45" fill="none" stroke="#06b6d4" stroke-width="2" stroke-linecap="round">
                                                        <animate attributeName="stroke-dasharray" values="0,100;100,0" dur="1s" fill="freeze" begin="0.2s"/>
                                                    </path>
                                                </g>

                                                <!-- Conversion Card -->
                                                <g transform="translate(220, 0)">
                                                    <rect x="0" y="0" width="95" height="75" rx="14" fill="#1e293b" stroke="#f59e0b" stroke-width="1"/>
                                                    <text x="12" y="20" fill="#94a3b8" font-size="9">{{ $locale === 'ru' ? 'Конверсия' : 'Konversiya' }}</text>
                                                    <text x="12" y="45" fill="#fbbf24" font-size="20" font-weight="bold">12.4%</text>
                                                    <path d="M12 58 L25 52 L38 55 L51 48 L64 52 L77 45 L82 48" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round">
                                                        <animate attributeName="stroke-dasharray" values="0,100;100,0" dur="1s" fill="freeze" begin="0.4s"/>
                                                    </path>
                                                </g>
                                            </g>
                                        </g>

                                        <!-- Floating elements -->
                                        <g transform="translate(370, 130)">
                                            <circle cx="15" cy="15" r="18" fill="#1e293b" stroke="#22c55e" stroke-width="2">
                                                <animate attributeName="r" values="18;21;18" dur="2.5s" repeatCount="indefinite"/>
                                            </circle>
                                            <text x="15" y="20" text-anchor="middle" fill="#22c55e" font-size="14">🎯</text>
                                        </g>
                                        <g transform="translate(370, 200)">
                                            <circle cx="15" cy="15" r="16" fill="#1e293b" stroke="#06b6d4" stroke-width="2">
                                                <animate attributeName="r" values="16;19;16" dur="2.5s" repeatCount="indefinite" begin="0.5s"/>
                                            </circle>
                                            <text x="15" y="20" text-anchor="middle" fill="#06b6d4" font-size="12">📈</text>
                                        </g>
                                        <g transform="translate(370, 265)">
                                            <circle cx="15" cy="15" r="14" fill="#1e293b" stroke="#f59e0b" stroke-width="2">
                                                <animate attributeName="r" values="14;17;14" dur="2.5s" repeatCount="indefinite" begin="1s"/>
                                            </circle>
                                            <text x="15" y="20" text-anchor="middle" fill="#f59e0b" font-size="11">🏆</text>
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
                <template x-for="(slide, index) in slides" :key="index">
                    <button
                        @click="goToSlide(index)"
                        class="group relative"
                    >
                        <div
                            class="w-3 h-3 rounded-full transition-all duration-300"
                            :class="currentSlide === index ? 'bg-blue-500 scale-125' : 'bg-white/30 hover:bg-white/50'"
                        ></div>
                        <span
                            class="absolute -bottom-8 left-1/2 -translate-x-1/2 text-xs text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap"
                            x-text="slide.name"
                        ></span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</section>

<script>
function featuresCarousel() {
    return {
        currentSlide: 0,
        autoplayInterval: null,
        slides: [
            { name: '{{ $locale === "ru" ? "Instagram" : "Instagram" }}' },
            { name: '{{ $locale === "ru" ? "Telegram" : "Telegram" }}' },
            { name: '{{ $locale === "ru" ? "Facebook Ads" : "Facebook Ads" }}' },
            { name: '{{ $locale === "ru" ? "AI Чатбот" : "AI Chatbot" }}' },
            { name: '{{ $locale === "ru" ? "Маркетинг" : "Marketing" }}' },
            { name: '{{ $locale === "ru" ? "Анализ звонков" : "Call Tahlili" }}' },
            { name: '{{ $locale === "ru" ? "Финансы" : "Moliya" }}' },
            { name: 'KPI' }
        ],

        init() {
            this.startAutoplay();
        },

        nextSlide() {
            this.currentSlide = (this.currentSlide + 1) % this.slides.length;
        },

        prevSlide() {
            this.currentSlide = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
        },

        goToSlide(index) {
            this.currentSlide = index;
        },

        startAutoplay() {
            this.autoplayInterval = setInterval(() => {
                this.nextSlide();
            }, 6000);
        },

        stopAutoplay() {
            if (this.autoplayInterval) {
                clearInterval(this.autoplayInterval);
            }
        }
    }
}
</script>
