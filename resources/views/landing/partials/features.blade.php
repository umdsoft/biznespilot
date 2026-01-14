<!-- Features Section -->
<section id="features" class="py-24 lg:py-32 bg-white relative overflow-hidden">
    <!-- Background decoration -->
    <div class="absolute top-0 right-0 w-1/3 h-full opacity-50">
        <svg viewBox="0 0 400 800" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
            <circle cx="400" cy="200" r="300" fill="url(#featCircle1)" opacity="0.3"/>
            <circle cx="350" cy="600" r="200" fill="url(#featCircle2)" opacity="0.2"/>
            <defs>
                <linearGradient id="featCircle1" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#dbeafe"/>
                    <stop offset="100%" stop-color="#e0e7ff"/>
                </linearGradient>
                <linearGradient id="featCircle2" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#fae8ff"/>
                    <stop offset="100%" stop-color="#e0e7ff"/>
                </linearGradient>
            </defs>
        </svg>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section header -->
        <div class="text-center mb-20 animate-on-scroll">
            <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-100 to-indigo-100 rounded-full text-sm font-semibold text-blue-700 mb-6">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"/>
                </svg>
                {{ $translations['features']['badge'] }}
            </div>
            <h2 class="text-4xl sm:text-5xl font-black text-gray-900 mb-6">
                {{ $translations['features']['title'] }}
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                {{ $translations['features']['subtitle'] }}
            </p>
        </div>

        <!-- Features grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            @php
            $featureIcons = [
                'chart' => '
                    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                        <rect x="5" y="45" width="12" height="25" rx="3" fill="currentColor" opacity="0.3"/>
                        <rect x="22" y="30" width="12" height="40" rx="3" fill="currentColor" opacity="0.5"/>
                        <rect x="39" y="20" width="12" height="50" rx="3" fill="currentColor" opacity="0.7"/>
                        <rect x="56" y="10" width="12" height="60" rx="3" fill="currentColor"/>
                        <path d="M10 40 Q25 25, 45 30 T75 15" stroke="currentColor" stroke-width="3" fill="none" stroke-linecap="round" opacity="0.8"/>
                        <circle cx="75" cy="15" r="5" fill="currentColor"/>
                    </svg>
                ',
                'users' => '
                    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                        <circle cx="40" cy="25" r="12" fill="currentColor"/>
                        <path d="M20 60 C20 45 30 38 40 38 C50 38 60 45 60 60" fill="currentColor" opacity="0.8"/>
                        <circle cx="18" cy="30" r="8" fill="currentColor" opacity="0.4"/>
                        <path d="M5 55 C5 45 10 40 18 40 C22 40 25 42 27 45" fill="currentColor" opacity="0.3"/>
                        <circle cx="62" cy="30" r="8" fill="currentColor" opacity="0.4"/>
                        <path d="M75 55 C75 45 70 40 62 40 C58 40 55 42 53 45" fill="currentColor" opacity="0.3"/>
                        <circle cx="40" cy="25" r="5" fill="white" opacity="0.3"/>
                    </svg>
                ',
                'bot' => '
                    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                        <rect x="10" y="15" width="60" height="50" rx="8" fill="currentColor"/>
                        <rect x="18" y="23" width="44" height="28" rx="4" fill="white" opacity="0.2"/>
                        <text x="40" y="42" text-anchor="middle" fill="white" font-size="18" font-weight="bold">$</text>
                        <rect x="18" y="56" width="20" height="6" rx="2" fill="white" opacity="0.5"/>
                        <rect x="42" y="56" width="20" height="6" rx="2" fill="white" opacity="0.3"/>
                        <path d="M25 70 L25 75 M55 70 L55 75" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                        <circle cx="60" cy="20" r="8" fill="#22c55e"/>
                        <path d="M56 20 L59 23 L64 17" stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/>
                    </svg>
                ',
                'calendar' => '
                    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                        <circle cx="40" cy="40" r="30" fill="currentColor" opacity="0.2"/>
                        <circle cx="40" cy="40" r="22" fill="currentColor"/>
                        <path d="M30 40 L38 40 L42 32 L50 48 L54 40 L62 40" stroke="white" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="25" cy="25" r="8" fill="currentColor" opacity="0.6"/>
                        <circle cx="55" cy="25" r="8" fill="currentColor" opacity="0.6"/>
                        <circle cx="25" cy="55" r="8" fill="currentColor" opacity="0.6"/>
                        <circle cx="55" cy="55" r="8" fill="currentColor" opacity="0.6"/>
                        <path d="M25 33 L25 47 M33 25 L47 25 M55 33 L55 47 M33 55 L47 55" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity="0.5"/>
                    </svg>
                ',
                'dashboard' => '
                    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                        <rect x="5" y="5" width="32" height="32" rx="8" fill="currentColor"/>
                        <rect x="43" y="5" width="32" height="18" rx="6" fill="currentColor" opacity="0.5"/>
                        <rect x="43" y="28" width="32" height="9" rx="4" fill="currentColor" opacity="0.3"/>
                        <rect x="5" y="43" width="15" height="32" rx="6" fill="currentColor" opacity="0.4"/>
                        <rect x="25" y="43" width="50" height="32" rx="8" fill="currentColor" opacity="0.7"/>
                        <circle cx="21" cy="21" r="8" stroke="white" stroke-width="3" fill="none" opacity="0.5"/>
                        <path d="M30 55 L35 65 L50 50 L60 60 L70 48" stroke="white" stroke-width="3" fill="none" stroke-linecap="round" opacity="0.6"/>
                    </svg>
                ',
                'team' => '
                    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                        <circle cx="40" cy="35" r="25" fill="currentColor" opacity="0.15"/>
                        <circle cx="40" cy="28" r="10" fill="currentColor"/>
                        <path d="M25 55 C25 42 32 37 40 37 C48 37 55 42 55 55" fill="currentColor" opacity="0.8"/>
                        <circle cx="18" cy="35" r="7" fill="currentColor" opacity="0.6"/>
                        <circle cx="62" cy="35" r="7" fill="currentColor" opacity="0.6"/>
                        <path d="M8 52 C8 44 12 40 18 40 C20 40 22 41 24 42" fill="currentColor" opacity="0.4"/>
                        <path d="M72 52 C72 44 68 40 62 40 C60 40 58 41 56 42" fill="currentColor" opacity="0.4"/>
                        <path d="M32 65 L40 72 L48 65" stroke="currentColor" stroke-width="3" fill="none" stroke-linecap="round"/>
                    </svg>
                ',
            ];

            $bgColors = [
                'chart' => 'from-blue-500 to-indigo-600',
                'users' => 'from-emerald-500 to-teal-600',
                'bot' => 'from-violet-500 to-purple-600',
                'calendar' => 'from-orange-500 to-red-500',
                'dashboard' => 'from-cyan-500 to-blue-600',
                'team' => 'from-pink-500 to-rose-600',
            ];

            $lightBgColors = [
                'chart' => 'from-blue-50 to-indigo-50',
                'users' => 'from-emerald-50 to-teal-50',
                'bot' => 'from-violet-50 to-purple-50',
                'calendar' => 'from-orange-50 to-red-50',
                'dashboard' => 'from-cyan-50 to-blue-50',
                'team' => 'from-pink-50 to-rose-50',
            ];

            $textColors = [
                'chart' => 'text-blue-600',
                'users' => 'text-emerald-600',
                'bot' => 'text-violet-600',
                'calendar' => 'text-orange-600',
                'dashboard' => 'text-cyan-600',
                'team' => 'text-pink-600',
            ];
            @endphp

            @foreach($translations['features']['items'] as $index => $feature)
                <div class="animate-on-scroll group" style="animation-delay: {{ $index * 0.1 }}s;">
                    <div class="relative h-full p-8 bg-white rounded-3xl border border-gray-100 hover:border-gray-200 transition-all duration-500 hover:shadow-2xl hover:shadow-gray-200/50 hover:-translate-y-2">
                        <!-- Gradient background on hover -->
                        <div class="absolute inset-0 bg-gradient-to-br {{ $lightBgColors[$feature['icon']] ?? 'from-blue-50 to-indigo-50' }} rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                        <!-- Stat Badge -->
                        @if(isset($feature['stat']))
                        <div class="absolute -top-3 right-6">
                            <div class="bg-gradient-to-r {{ $bgColors[$feature['icon']] ?? 'from-blue-500 to-indigo-600' }} text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-lg">
                                {{ $feature['stat'] }}
                            </div>
                        </div>
                        @endif

                        <div class="relative">
                            <!-- Icon -->
                            <div class="w-20 h-20 mb-6 {{ $textColors[$feature['icon']] ?? 'text-blue-600' }}">
                                {!! $featureIcons[$feature['icon']] ?? $featureIcons['chart'] !!}
                            </div>

                            <!-- Content -->
                            <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-gray-900">
                                {{ $feature['title'] }}
                            </h3>
                            <p class="text-gray-600 leading-relaxed">
                                {{ $feature['description'] }}
                            </p>

                            <!-- Arrow link -->
                            <div class="mt-6 flex items-center text-sm font-semibold {{ $textColors[$feature['icon']] ?? 'text-blue-600' }} opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                <span>{{ $locale === 'ru' ? 'Подробнее' : 'Batafsil' }}</span>
                                <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
