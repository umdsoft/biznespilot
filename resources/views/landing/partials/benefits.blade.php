<!-- Benefits Section -->
<section id="benefits" class="py-24 lg:py-32 bg-white relative overflow-hidden">
    <!-- Background pattern -->
    <div class="absolute inset-0 opacity-[0.02]">
        <svg width="100%" height="100%">
            <defs>
                <pattern id="benefitGrid" width="40" height="40" patternUnits="userSpaceOnUse">
                    <circle cx="20" cy="20" r="1.5" fill="#6366f1"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#benefitGrid)"/>
        </svg>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <!-- Left content - Illustration -->
            <div class="relative animate-on-scroll order-2 lg:order-1">
                <svg viewBox="0 0 500 500" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full max-w-lg mx-auto">
                    <!-- Background circles -->
                    <circle cx="250" cy="250" r="200" fill="url(#benefitBg1)" opacity="0.1"/>
                    <circle cx="250" cy="250" r="150" fill="url(#benefitBg2)" opacity="0.15"/>

                    <!-- Main card - Unified Dashboard -->
                    <g filter="url(#benefitShadow)">
                        <rect x="100" y="100" width="300" height="300" rx="24" fill="white"/>

                        <!-- Card header with tabs -->
                        <rect x="120" y="120" width="260" height="40" rx="10" fill="#f8fafc"/>
                        <rect x="130" y="128" width="70" height="24" rx="6" fill="#3b82f6"/>
                        <text x="165" y="144" text-anchor="middle" fill="white" font-size="9" font-weight="bold">Hammasi</text>
                        <rect x="208" y="128" width="55" height="24" rx="6" fill="#f1f5f9"/>
                        <text x="235" y="144" text-anchor="middle" fill="#64748b" font-size="9">Marketing</text>
                        <rect x="270" y="128" width="45" height="24" rx="6" fill="#f1f5f9"/>
                        <text x="292" y="144" text-anchor="middle" fill="#64748b" font-size="9">Sotuv</text>
                        <rect x="322" y="128" width="48" height="24" rx="6" fill="#f1f5f9"/>
                        <text x="346" y="144" text-anchor="middle" fill="#64748b" font-size="9">Moliya</text>

                        <!-- Three module stats -->
                        <g transform="translate(120, 175)">
                            <rect width="80" height="70" rx="12" fill="#dbeafe"/>
                            <circle cx="40" cy="22" r="12" fill="#3b82f6"/>
                            <text x="40" y="26" text-anchor="middle" fill="white" font-size="10">M</text>
                            <text x="40" y="48" text-anchor="middle" fill="#1e40af" font-size="14" font-weight="bold">2.8K</text>
                            <text x="40" y="62" text-anchor="middle" fill="#3b82f6" font-size="8">Leadlar</text>
                        </g>
                        <g transform="translate(210, 175)">
                            <rect width="80" height="70" rx="12" fill="#dcfce7"/>
                            <circle cx="40" cy="22" r="12" fill="#10b981"/>
                            <text x="40" y="26" text-anchor="middle" fill="white" font-size="10">S</text>
                            <text x="40" y="48" text-anchor="middle" fill="#166534" font-size="14" font-weight="bold">$128K</text>
                            <text x="40" y="62" text-anchor="middle" fill="#16a34a" font-size="8">Daromad</text>
                        </g>
                        <g transform="translate(300, 175)">
                            <rect width="80" height="70" rx="12" fill="#f3e8ff"/>
                            <circle cx="40" cy="22" r="12" fill="#8b5cf6"/>
                            <text x="40" y="26" text-anchor="middle" fill="white" font-size="10">F</text>
                            <text x="40" y="48" text-anchor="middle" fill="#6b21a8" font-size="14" font-weight="bold">89%</text>
                            <text x="40" y="62" text-anchor="middle" fill="#7c3aed" font-size="8">Margin</text>
                        </g>

                        <!-- Combined chart with 3 lines -->
                        <rect x="120" y="260" width="260" height="120" rx="12" fill="#f8fafc"/>
                        <text x="135" y="280" fill="#1e293b" font-size="10" font-weight="600">Yagona ko'rinish</text>

                        <!-- Three chart lines -->
                        <path d="M140 340 Q180 330, 210 320 T280 300 T350 290" stroke="#3b82f6" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                        <path d="M140 350 Q180 335, 210 340 T280 310 T350 295" stroke="#10b981" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                        <path d="M140 335 Q180 325, 210 330 T280 305 T350 285" stroke="#8b5cf6" stroke-width="2.5" fill="none" stroke-linecap="round"/>

                        <!-- Legend -->
                        <g transform="translate(280, 272)">
                            <circle cx="0" cy="0" r="4" fill="#3b82f6"/>
                            <circle cx="35" cy="0" r="4" fill="#10b981"/>
                            <circle cx="70" cy="0" r="4" fill="#8b5cf6"/>
                        </g>
                    </g>

                    <!-- Floating elements -->
                    <!-- All-in-one badge -->
                    <g transform="translate(50, 80)" class="animate-float">
                        <rect width="100" height="85" rx="16" fill="white" filter="url(#floatShadow)"/>
                        <circle cx="50" cy="30" r="18" fill="#eff6ff"/>
                        <text x="50" y="36" text-anchor="middle" fill="#3b82f6" font-size="16">🔗</text>
                        <text x="50" y="55" text-anchor="middle" fill="#1e293b" font-size="12" font-weight="bold">3 modul</text>
                        <text x="50" y="72" text-anchor="middle" fill="#64748b" font-size="9">Bitta joyda</text>
                    </g>

                    <!-- Automation badge -->
                    <g transform="translate(350, 70)">
                        <rect width="110" height="90" rx="16" fill="white" filter="url(#floatShadow)"/>
                        <circle cx="55" cy="32" r="18" fill="#dcfce7"/>
                        <text x="55" y="38" text-anchor="middle" fill="#16a34a" font-size="16">⚡</text>
                        <text x="55" y="58" text-anchor="middle" fill="#1e293b" font-size="12" font-weight="bold">Auto</text>
                        <text x="55" y="75" text-anchor="middle" fill="#64748b" font-size="9">Avtomatlashtirish</text>
                    </g>

                    <!-- AI badge -->
                    <g transform="translate(60, 350)">
                        <rect width="95" height="85" rx="16" fill="white" filter="url(#floatShadow)"/>
                        <circle cx="47" cy="30" r="18" fill="#f3e8ff"/>
                        <text x="47" y="36" text-anchor="middle" fill="#7c3aed" font-size="14" font-weight="bold">AI</text>
                        <text x="47" y="55" text-anchor="middle" fill="#1e293b" font-size="12" font-weight="bold">24/7</text>
                        <text x="47" y="72" text-anchor="middle" fill="#64748b" font-size="9">Yordamchi</text>
                    </g>

                    <!-- 360 view badge -->
                    <g transform="translate(355, 330)">
                        <rect width="100" height="85" rx="16" fill="white" filter="url(#floatShadow)"/>
                        <circle cx="50" cy="30" r="18" fill="#fef3c7"/>
                        <text x="50" y="36" text-anchor="middle" fill="#d97706" font-size="14">👁️</text>
                        <text x="50" y="55" text-anchor="middle" fill="#1e293b" font-size="12" font-weight="bold">360°</text>
                        <text x="50" y="72" text-anchor="middle" fill="#64748b" font-size="9">Ko'rinish</text>
                    </g>

                    <defs>
                        <linearGradient id="benefitBg1" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#3b82f6"/>
                            <stop offset="100%" stop-color="#8b5cf6"/>
                        </linearGradient>
                        <linearGradient id="benefitBg2" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#6366f1"/>
                            <stop offset="100%" stop-color="#a855f7"/>
                        </linearGradient>
                        <linearGradient id="avatarGrad2" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#3b82f6"/>
                            <stop offset="100%" stop-color="#6366f1"/>
                        </linearGradient>
                        <linearGradient id="chartLine" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#3b82f6"/>
                            <stop offset="100%" stop-color="#6366f1"/>
                        </linearGradient>
                        <linearGradient id="chartFill" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#6366f1"/>
                            <stop offset="100%" stop-color="#6366f1" stop-opacity="0"/>
                        </linearGradient>
                        <filter id="benefitShadow" x="-50" y="-50" width="450" height="400">
                            <feDropShadow dx="0" dy="10" stdDeviation="25" flood-color="#6366f1" flood-opacity="0.1"/>
                        </filter>
                        <filter id="floatShadow" x="-20" y="-20" width="180" height="160">
                            <feDropShadow dx="0" dy="4" stdDeviation="10" flood-color="#000" flood-opacity="0.08"/>
                        </filter>
                    </defs>
                </svg>
            </div>

            <!-- Right content - Benefits list -->
            <div class="order-1 lg:order-2">
                <!-- Section header -->
                <div class="mb-12 animate-on-scroll">
                    <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-100 to-emerald-100 rounded-full text-sm font-semibold text-green-700 mb-6">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $translations['benefits']['badge'] }}
                    </div>
                    <h2 class="text-4xl sm:text-5xl font-black text-gray-900 mb-6">
                        {{ $translations['benefits']['title'] }}
                    </h2>
                    <p class="text-xl text-gray-600">
                        {{ $translations['benefits']['subtitle'] }}
                    </p>
                </div>

                <!-- Benefits list -->
                <div class="space-y-6">
                    @php
                    $benefitData = [
                        'clock' => ['icon' => '🔗', 'gradient' => 'from-blue-500 to-indigo-500', 'hoverText' => 'group-hover:text-blue-600', 'arrowBg' => 'group-hover:bg-blue-100', 'arrowText' => 'text-blue-600'],
                        'trending' => ['icon' => '⚡', 'gradient' => 'from-green-500 to-emerald-500', 'hoverText' => 'group-hover:text-green-600', 'arrowBg' => 'group-hover:bg-green-100', 'arrowText' => 'text-green-600'],
                        'analytics' => ['icon' => '👁️', 'gradient' => 'from-purple-500 to-violet-500', 'hoverText' => 'group-hover:text-purple-600', 'arrowBg' => 'group-hover:bg-purple-100', 'arrowText' => 'text-purple-600'],
                        'target' => ['icon' => '🤖', 'gradient' => 'from-orange-500 to-red-500', 'hoverText' => 'group-hover:text-orange-600', 'arrowBg' => 'group-hover:bg-orange-100', 'arrowText' => 'text-orange-600'],
                    ];
                    @endphp

                    @foreach($translations['benefits']['items'] as $index => $benefit)
                        @php $data = $benefitData[$benefit['icon']] ?? $benefitData['clock']; @endphp
                        <div class="animate-on-scroll group" style="animation-delay: {{ $index * 0.1 }}s;">
                            <div class="flex items-start gap-5 p-6 rounded-2xl hover:bg-gray-50 transition-all duration-300 cursor-pointer border border-transparent hover:border-gray-100">
                                <!-- Icon -->
                                <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br {{ $data['gradient'] }} rounded-2xl flex items-center justify-center text-2xl shadow-lg group-hover:scale-110 transition-transform duration-300">
                                    {{ $data['icon'] }}
                                </div>

                                <!-- Content -->
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-gray-900 mb-2 {{ $data['hoverText'] }} transition-colors">
                                        {{ $benefit['title'] }}
                                    </h3>
                                    <p class="text-gray-600 leading-relaxed mb-3">
                                        {{ $benefit['description'] }}
                                    </p>

                                    <!-- Before/After comparison -->
                                    @if(isset($benefit['before']) && isset($benefit['after']))
                                    <div class="flex flex-wrap gap-2 text-xs">
                                        <span class="px-2 py-1 bg-red-50 text-red-600 rounded-full line-through">{{ $benefit['before'] }}</span>
                                        <span class="px-2 py-1 bg-green-50 text-green-600 rounded-full font-semibold">{{ $benefit['after'] }}</span>
                                    </div>
                                    @endif
                                </div>

                                <!-- Arrow -->
                                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 {{ $data['arrowBg'] }}">
                                    <svg class="w-4 h-4 {{ $data['arrowText'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

