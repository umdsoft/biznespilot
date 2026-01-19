<!-- Hero Section -->
<section class="relative pt-24 lg:pt-32 pb-20 lg:pb-32 overflow-hidden">
    <!-- Animated Background -->
    <div class="absolute inset-0">
        <!-- Gradient mesh -->
        <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-indigo-50 to-violet-50"></div>

        <!-- Animated blobs -->
        <svg class="absolute top-0 left-0 w-full h-full" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <filter id="goo">
                    <feGaussianBlur in="SourceGraphic" stdDeviation="10" result="blur" />
                    <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 18 -8" result="goo" />
                </filter>
                <linearGradient id="heroGrad1" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.3" />
                    <stop offset="100%" stop-color="#8b5cf6" stop-opacity="0.3" />
                </linearGradient>
                <linearGradient id="heroGrad2" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#06b6d4" stop-opacity="0.2" />
                    <stop offset="100%" stop-color="#3b82f6" stop-opacity="0.2" />
                </linearGradient>
            </defs>
            <ellipse cx="20%" cy="30%" rx="300" ry="200" fill="url(#heroGrad1)" class="animate-blob" />
            <ellipse cx="80%" cy="70%" rx="250" ry="180" fill="url(#heroGrad2)" class="animate-blob animation-delay-2000" />
        </svg>

        <!-- Grid pattern -->
        <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23000&quot; fill-opacity=&quot;1&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-8 items-center">
            <!-- Left content -->
            <div class="text-center lg:text-left z-10">
                <!-- Badge -->
                <div class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-yellow-400/20 to-orange-400/20 border border-yellow-300 text-yellow-800 rounded-full text-sm font-bold mb-8 backdrop-blur-sm shadow-lg">
                    <span class="mr-2"><?php echo e($translations['hero']['badge']); ?></span>
                </div>

                <!-- Title -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-black tracking-tight mb-6 leading-[1.1]">
                    <?php echo e($translations['hero']['title']); ?>

                    <span class="relative">
                        <span class="relative z-10 bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600 bg-clip-text text-transparent"><?php echo e($translations['hero']['title_highlight']); ?></span>
                        <svg class="absolute -bottom-2 left-0 w-full" viewBox="0 0 300 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M2 10C50 4 100 2 150 6C200 10 250 4 298 8" stroke="url(#underlineGrad)" stroke-width="4" stroke-linecap="round"/>
                            <defs>
                                <linearGradient id="underlineGrad" x1="0" y1="0" x2="300" y2="0">
                                    <stop stop-color="#3b82f6"/>
                                    <stop offset="0.5" stop-color="#6366f1"/>
                                    <stop offset="1" stop-color="#8b5cf6"/>
                                </linearGradient>
                            </defs>
                        </svg>
                    </span>
                </h1>

                <!-- Subtitle -->
                <p class="text-lg sm:text-xl text-gray-600 mb-8 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                    <?php echo e($translations['hero']['subtitle']); ?>

                </p>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start mb-6">
                    <a href="<?php echo e(route('register')); ?>"
                       class="group relative inline-flex items-center justify-center px-8 py-5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl font-bold text-lg overflow-hidden transition-all duration-300 hover:shadow-2xl hover:shadow-blue-500/30 hover:-translate-y-1 hover:scale-105">
                        <span class="absolute inset-0 bg-gradient-to-r from-indigo-600 to-violet-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                        <span class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-violet-600 rounded-2xl blur opacity-30 group-hover:opacity-50 transition-opacity"></span>
                        <span class="relative flex items-center">
                            <?php echo e($translations['hero']['cta_primary']); ?>

                        </span>
                    </a>
                    <a href="#demo"
                       class="group inline-flex items-center justify-center px-8 py-5 bg-white/80 backdrop-blur-sm text-gray-700 rounded-2xl font-bold text-lg border-2 border-gray-200 hover:border-blue-300 hover:bg-blue-50/50 transition-all duration-300">
                        <svg class="w-6 h-6 mr-2 text-red-500 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
                        </svg>
                        <?php echo e($translations['hero']['cta_secondary']); ?>

                    </a>
                </div>

                <!-- No credit card text -->
                <p class="text-sm text-gray-500 mb-8 flex items-center justify-center lg:justify-start gap-2">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <?php echo e($locale === 'ru' ? 'Карта не нужна • Отменить можно в любой момент' : "Kredit karta kerak emas • Istalgan vaqt bekor qilish mumkin"); ?>

                </p>

                <!-- Social Proof Bar -->
                <div class="bg-white/70 backdrop-blur-sm rounded-2xl p-4 shadow-lg border border-gray-100">
                    <p class="text-xs text-gray-500 mb-3 text-center lg:text-left"><?php echo e($translations['hero']['trusted_by']); ?></p>
                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4 sm:gap-6">
                        <div class="flex items-center gap-2 px-3 py-2 bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center text-white text-xs font-bold">T</div>
                            <span class="text-xs font-medium text-gray-600">TechStart</span>
                        </div>
                        <div class="flex items-center gap-2 px-3 py-2 bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center text-white text-xs font-bold">F</div>
                            <span class="text-xs font-medium text-gray-600">FoodExpress</span>
                        </div>
                        <div class="flex items-center gap-2 px-3 py-2 bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center text-white text-xs font-bold">E</div>
                            <span class="text-xs font-medium text-gray-600">EduPlatform</span>
                        </div>
                        <div class="text-xs font-semibold text-blue-600">+497</div>
                    </div>
                </div>

                <!-- Stats Row -->
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-6 mt-6">
                    <div class="flex items-center gap-2 text-sm">
                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="font-bold text-gray-900"><?php echo e($translations['hero']['stats']['businesses']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo e($locale === 'ru' ? 'используют' : 'foydalanmoqda'); ?></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <div class="font-bold text-gray-900"><?php echo e($translations['hero']['stats']['leads']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo e($locale === 'ru' ? 'обработано' : 'qayta ishlandi'); ?></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-bold text-green-600"><?php echo e($translations['hero']['stats']['growth']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo e($locale === 'ru' ? 'эффективность' : 'samaradorlik'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right content - Full Business Management Dashboard -->
            <div class="relative hidden lg:block">
                <div class="relative w-full max-w-2xl mx-auto">
                    <!-- Main Dashboard SVG - Full Business System -->
                    <svg viewBox="0 0 600 520" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto drop-shadow-2xl">
                        <!-- Background Card -->
                        <rect x="20" y="20" width="560" height="480" rx="24" fill="white" filter="url(#dashShadow)"/>

                        <!-- Header Bar -->
                        <rect x="40" y="40" width="520" height="50" rx="12" fill="#f8fafc"/>
                        <circle cx="65" cy="65" r="14" fill="url(#logoGrad)"/>
                        <path d="M60 65 L65 60 L70 65 L65 70 Z" fill="white"/>
                        <text x="88" y="62" fill="#1e293b" font-size="12" font-weight="bold">BiznesPilot</text>
                        <text x="88" y="76" fill="#94a3b8" font-size="9"><?php echo e($locale === 'ru' ? 'Система управления' : 'Boshqaruv tizimi'); ?></text>

                        <!-- Navigation tabs -->
                        <g transform="translate(220, 52)">
                            <rect width="70" height="28" rx="6" fill="#3b82f6"/>
                            <text x="12" y="18" fill="white" font-size="10" font-weight="600">Marketing</text>

                            <rect x="80" width="55" height="28" rx="6" fill="#f1f5f9"/>
                            <text x="92" y="18" fill="#64748b" font-size="10"><?php echo e($locale === 'ru' ? 'Продажи' : 'Sotuv'); ?></text>

                            <rect x="145" width="60" height="28" rx="6" fill="#f1f5f9"/>
                            <text x="157" y="18" fill="#64748b" font-size="10"><?php echo e($locale === 'ru' ? 'Финансы' : 'Moliya'); ?></text>

                            <rect x="215" width="55" height="28" rx="6" fill="#f1f5f9"/>
                            <text x="227" y="18" fill="#64748b" font-size="10"><?php echo e($locale === 'ru' ? 'Команда' : 'Jamoa'); ?></text>
                        </g>

                        <!-- User avatar -->
                        <circle cx="530" cy="65" r="16" fill="url(#avatarGrad)"/>

                        <!-- Three Module Cards -->
                        <!-- Marketing Module -->
                        <rect x="40" y="110" width="170" height="130" rx="16" fill="url(#marketingGrad)"/>
                        <g transform="translate(55, 125)">
                            <circle cx="12" cy="12" r="12" fill="white" opacity="0.2"/>
                            <path d="M8 12 L12 8 L16 12 L12 16 Z" fill="white"/>
                            <text x="32" y="8" fill="white" font-size="9" opacity="0.9">MARKETING</text>
                            <text x="32" y="22" fill="white" font-size="16" font-weight="bold">2,847</text>
                            <text x="0" y="50" fill="white" font-size="10" opacity="0.9"><?php echo e($locale === 'ru' ? 'Лидов' : 'Leadlar'); ?></text>
                            <rect y="60" width="140" height="4" rx="2" fill="white" opacity="0.2"/>
                            <rect y="60" width="98" height="4" rx="2" fill="white" opacity="0.6"/>
                            <text x="0" y="80" fill="white" font-size="9" opacity="0.8"><?php echo e($locale === 'ru' ? 'Кампании: 12 активных' : 'Kampaniyalar: 12 faol'); ?></text>
                            <text x="0" y="95" fill="white" font-size="9" opacity="0.8"><?php echo e($locale === 'ru' ? 'Конверсия: 24.8%' : 'Konversiya: 24.8%'); ?></text>
                        </g>

                        <!-- Sales Module -->
                        <rect x="220" y="110" width="170" height="130" rx="16" fill="url(#salesGrad)"/>
                        <g transform="translate(235, 125)">
                            <circle cx="12" cy="12" r="12" fill="white" opacity="0.2"/>
                            <text x="8" y="17" fill="white" font-size="12">$</text>
                            <text x="32" y="8" fill="white" font-size="9" opacity="0.9"><?php echo e($locale === 'ru' ? 'ПРОДАЖИ' : 'SOTUV'); ?></text>
                            <text x="32" y="22" fill="white" font-size="16" font-weight="bold">$128.5K</text>
                            <text x="0" y="50" fill="white" font-size="10" opacity="0.9"><?php echo e($locale === 'ru' ? 'Доход за месяц' : 'Bu oy daromad'); ?></text>
                            <rect y="60" width="140" height="4" rx="2" fill="white" opacity="0.2"/>
                            <rect y="60" width="112" height="4" rx="2" fill="white" opacity="0.6"/>
                            <text x="0" y="80" fill="white" font-size="9" opacity="0.8"><?php echo e($locale === 'ru' ? 'Сделки: 47 открытых' : 'Deallar: 47 ochiq'); ?></text>
                            <text x="0" y="95" fill="white" font-size="9" opacity="0.8"><?php echo e($locale === 'ru' ? 'Средний чек: $2,734' : "O'rtacha: $2,734"); ?></text>
                        </g>

                        <!-- Finance Module -->
                        <rect x="400" y="110" width="160" height="130" rx="16" fill="url(#financeGrad)"/>
                        <g transform="translate(415, 125)">
                            <circle cx="12" cy="12" r="12" fill="white" opacity="0.2"/>
                            <path d="M7 12 L12 7 L17 12 L12 17 Z M10 12 L12 10 L14 12 L12 14 Z" fill="white"/>
                            <text x="32" y="8" fill="white" font-size="9" opacity="0.9"><?php echo e($locale === 'ru' ? 'ФИНАНСЫ' : 'MOLIYA'); ?></text>
                            <text x="32" y="22" fill="white" font-size="16" font-weight="bold">89.2%</text>
                            <text x="0" y="50" fill="white" font-size="10" opacity="0.9"><?php echo e($locale === 'ru' ? 'Маржа прибыли' : 'Foyda margini'); ?></text>
                            <rect y="60" width="130" height="4" rx="2" fill="white" opacity="0.2"/>
                            <rect y="60" width="116" height="4" rx="2" fill="white" opacity="0.6"/>
                            <text x="0" y="80" fill="white" font-size="9" opacity="0.8"><?php echo e($locale === 'ru' ? 'Доход: $156.2K' : 'Kirim: $156.2K'); ?></text>
                            <text x="0" y="95" fill="white" font-size="9" opacity="0.8"><?php echo e($locale === 'ru' ? 'Расход: $27.7K' : 'Chiqim: $27.7K'); ?></text>
                        </g>

                        <!-- Unified Dashboard Area -->
                        <rect x="40" y="255" width="340" height="225" rx="16" fill="#f8fafc"/>
                        <text x="60" y="285" fill="#1e293b" font-size="13" font-weight="600"><?php echo e($locale === 'ru' ? 'Единый Dashboard' : 'Yagona Dashboard'); ?></text>

                        <!-- Mini module indicators -->
                        <g transform="translate(200, 272)">
                            <rect width="50" height="18" rx="4" fill="#dbeafe"/>
                            <circle cx="10" cy="9" r="4" fill="#3b82f6"/>
                            <rect x="18" y="6" width="25" height="6" rx="2" fill="#3b82f6" opacity="0.5"/>
                        </g>
                        <g transform="translate(255, 272)">
                            <rect width="50" height="18" rx="4" fill="#dcfce7"/>
                            <circle cx="10" cy="9" r="4" fill="#10b981"/>
                            <rect x="18" y="6" width="25" height="6" rx="2" fill="#10b981" opacity="0.5"/>
                        </g>
                        <g transform="translate(310, 272)">
                            <rect width="50" height="18" rx="4" fill="#f3e8ff"/>
                            <circle cx="10" cy="9" r="4" fill="#8b5cf6"/>
                            <rect x="18" y="6" width="25" height="6" rx="2" fill="#8b5cf6" opacity="0.5"/>
                        </g>

                        <!-- Combined chart -->
                        <g transform="translate(60, 300)">
                            <!-- Chart grid -->
                            <line x1="0" y1="0" x2="0" y2="140" stroke="#e2e8f0" stroke-width="1"/>
                            <line x1="0" y1="140" x2="300" y2="140" stroke="#e2e8f0" stroke-width="1"/>
                            <line x1="0" y1="70" x2="300" y2="70" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="4"/>
                            <line x1="0" y1="35" x2="300" y2="35" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="4"/>
                            <line x1="0" y1="105" x2="300" y2="105" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="4"/>

                            <!-- Marketing line (blue) -->
                            <path d="M0 100 Q40 90, 75 85 T150 70 T225 55 T300 40" stroke="#3b82f6" stroke-width="3" fill="none" stroke-linecap="round"/>

                            <!-- Sales line (green) -->
                            <path d="M0 110 Q40 95, 75 100 T150 80 T225 65 T300 50" stroke="#10b981" stroke-width="3" fill="none" stroke-linecap="round"/>

                            <!-- Finance line (purple) -->
                            <path d="M0 90 Q40 85, 75 75 T150 85 T225 60 T300 45" stroke="#8b5cf6" stroke-width="3" fill="none" stroke-linecap="round"/>

                            <!-- Data points -->
                            <circle cx="300" cy="40" r="5" fill="#3b82f6"/>
                            <circle cx="300" cy="50" r="5" fill="#10b981"/>
                            <circle cx="300" cy="45" r="5" fill="#8b5cf6"/>
                        </g>

                        <!-- AI Panel -->
                        <rect x="400" y="255" width="160" height="225" rx="16" fill="white" stroke="#e2e8f0" stroke-width="1"/>
                        <g transform="translate(415, 270)">
                            <rect width="130" height="28" rx="8" fill="url(#aiGrad)"/>
                            <circle cx="14" cy="14" r="10" fill="white" opacity="0.2"/>
                            <text x="10" y="18" fill="white" font-size="10" font-weight="bold">AI</text>
                            <text x="32" y="18" fill="white" font-size="10" font-weight="600"><?php echo e($locale === 'ru' ? 'Автоматизация' : 'Avtomatlashtirish'); ?></text>
                        </g>

                        <!-- AI Tasks -->
                        <g transform="translate(415, 310)">
                            <rect width="130" height="45" rx="8" fill="#f0fdf4" stroke="#bbf7d0" stroke-width="1"/>
                            <circle cx="16" cy="22" r="8" fill="#22c55e"/>
                            <path d="M12 22 L15 25 L20 19" stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/>
                            <text x="32" y="18" fill="#166534" font-size="9" font-weight="600">Lead scoring</text>
                            <text x="32" y="32" fill="#16a34a" font-size="8">847 lead <?php echo e($locale === 'ru' ? 'оценено' : 'baholandi'); ?></text>
                        </g>

                        <g transform="translate(415, 365)">
                            <rect width="130" height="45" rx="8" fill="#eff6ff" stroke="#bfdbfe" stroke-width="1"/>
                            <circle cx="16" cy="22" r="8" fill="#3b82f6"/>
                            <path d="M12 22 L20 22 M16 18 L16 26" stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/>
                            <text x="32" y="18" fill="#1e40af" font-size="9" font-weight="600"><?php echo e($locale === 'ru' ? 'Создание отчёта' : 'Hisobot yaratish'); ?></text>
                            <text x="32" y="32" fill="#2563eb" font-size="8"><?php echo e($locale === 'ru' ? 'Финансы + Продажи' : 'Moliya + Sotuv'); ?></text>
                        </g>

                        <g transform="translate(415, 420)">
                            <rect width="130" height="45" rx="8" fill="#fef3c7" stroke="#fde68a" stroke-width="1"/>
                            <circle cx="16" cy="22" r="8" fill="#f59e0b"/>
                            <path d="M13 22 L19 22 M16 19 L16 25" stroke="white" stroke-width="2" fill="none" stroke-linecap="round" transform="rotate(45 16 22)"/>
                            <text x="32" y="18" fill="#92400e" font-size="9" font-weight="600">Workflow</text>
                            <text x="32" y="32" fill="#b45309" font-size="8">15 <?php echo e($locale === 'ru' ? 'авто-процессов' : 'avtomatik jarayon'); ?></text>
                        </g>

                        <!-- Gradients & Filters -->
                        <defs>
                            <filter id="dashShadow" x="-20" y="-20" width="640" height="560">
                                <feDropShadow dx="0" dy="10" stdDeviation="20" flood-color="#6366f1" flood-opacity="0.15"/>
                            </filter>
                            <linearGradient id="logoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#3b82f6"/>
                                <stop offset="100%" stop-color="#6366f1"/>
                            </linearGradient>
                            <linearGradient id="avatarGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#f472b6"/>
                                <stop offset="100%" stop-color="#a855f7"/>
                            </linearGradient>
                            <linearGradient id="marketingGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#3b82f6"/>
                                <stop offset="100%" stop-color="#1d4ed8"/>
                            </linearGradient>
                            <linearGradient id="salesGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#10b981"/>
                                <stop offset="100%" stop-color="#059669"/>
                            </linearGradient>
                            <linearGradient id="financeGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#8b5cf6"/>
                                <stop offset="100%" stop-color="#7c3aed"/>
                            </linearGradient>
                            <linearGradient id="aiGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#6366f1"/>
                                <stop offset="100%" stop-color="#8b5cf6"/>
                            </linearGradient>
                        </defs>
                    </svg>

                    <!-- Floating notification - Time Saving -->
                    <div class="absolute -top-4 -right-4 bg-white rounded-2xl shadow-xl p-4 animate-bounce-slow border border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center text-white font-bold">
                                60%
                            </div>
                            <div>
                                <div class="text-sm font-bold text-gray-900"><?php echo e($locale === 'ru' ? 'Экономия времени' : 'Vaqt tejash'); ?></div>
                                <div class="text-xs text-gray-500"><?php echo e($locale === 'ru' ? 'Каждый день' : 'Har kuni'); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Floating AI notification -->
                    <div class="absolute -bottom-6 -left-6 bg-white rounded-2xl shadow-xl p-4 animate-float border border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold text-lg">AI</span>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-gray-900"><?php echo e($locale === 'ru' ? 'Работает 24/7' : '24/7 ishlaydi'); ?></div>
                                <div class="text-xs text-gray-500"><?php echo e($locale === 'ru' ? 'Автоматизация' : 'Avtomatik'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom wave -->
    <div class="absolute bottom-0 left-0 right-0">
        <svg viewBox="0 0 1440 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
            <path d="M0 50C240 80 480 100 720 90C960 80 1200 40 1440 60V100H0V50Z" fill="white"/>
        </svg>
    </div>
</section>
<?php /**PATH D:\marketing startap\biznespilot\resources\views/landing/partials/hero.blade.php ENDPATH**/ ?>