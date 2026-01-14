<!-- Header / Navigation -->
<header id="navbar" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 lg:h-20">
            <!-- Logo -->
            <a href="{{ route('landing') }}" class="flex items-center space-x-2">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="text-xl font-bold gradient-text">BiznesPilot</span>
            </a>

            <!-- Desktop Navigation -->
            <nav class="hidden lg:flex items-center space-x-8">
                <a href="#features" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">
                    {{ $translations['nav']['features'] }}
                </a>
                <a href="#how-it-works" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">
                    {{ $translations['nav']['how_it_works'] }}
                </a>
                <a href="#benefits" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">
                    {{ $translations['nav']['benefits'] }}
                </a>
                <a href="#faq" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">
                    {{ $translations['nav']['faq'] }}
                </a>
            </nav>

            <!-- Right side actions -->
            <div class="hidden lg:flex items-center space-x-4">
                <!-- Language Switcher -->
                <div class="relative group">
                    <button class="flex items-center space-x-1 text-gray-600 hover:text-gray-900 font-medium px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <span>{{ $locales[$locale]['flag'] }}</span>
                        <span>{{ $locales[$locale]['name'] }}</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                        @foreach($locales as $code => $localeInfo)
                            <a href="{{ route('landing.language', $code) }}"
                               class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-50 first:rounded-t-lg last:rounded-b-lg {{ $locale === $code ? 'bg-blue-50 text-blue-600' : 'text-gray-700' }}">
                                <span>{{ $localeInfo['flag'] }}</span>
                                <span>{{ $localeInfo['name'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Login Button -->
                <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">
                    {{ $translations['nav']['login'] }}
                </a>

                <!-- CTA Button -->
                <a href="{{ route('register') }}"
                   class="relative group bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-5 py-2.5 rounded-xl font-bold hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg shadow-blue-500/25 hover:shadow-xl hover:shadow-blue-500/30 hover:-translate-y-0.5">
                    <span class="absolute -inset-0.5 bg-gradient-to-r from-blue-600 to-violet-600 rounded-xl blur opacity-30 group-hover:opacity-50 transition-opacity"></span>
                    <span class="relative">{{ $translations['nav']['get_started'] }}</span>
                </a>
            </div>

            <!-- Mobile menu button -->
            <button onclick="toggleMobileMenu()" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-6 h-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="mobile-menu lg:hidden">
            <div class="py-4 space-y-2 border-t border-gray-100">
                <a href="#features" class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    {{ $translations['nav']['features'] }}
                </a>
                <a href="#how-it-works" class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    {{ $translations['nav']['how_it_works'] }}
                </a>
                <a href="#benefits" class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    {{ $translations['nav']['benefits'] }}
                </a>
                <a href="#faq" class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    {{ $translations['nav']['faq'] }}
                </a>

                <!-- Language switcher mobile -->
                <div class="px-4 py-2 flex items-center space-x-2">
                    @foreach($locales as $code => $localeInfo)
                        <a href="{{ route('landing.language', $code) }}"
                           class="flex items-center space-x-1 px-3 py-1.5 rounded-lg {{ $locale === $code ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-600' }}">
                            <span>{{ $localeInfo['flag'] }}</span>
                            <span class="text-sm">{{ $localeInfo['name'] }}</span>
                        </a>
                    @endforeach
                </div>

                <div class="pt-4 space-y-2 border-t border-gray-100">
                    <a href="{{ route('login') }}" class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                        {{ $translations['nav']['login'] }}
                    </a>
                    <a href="{{ route('register') }}"
                       class="block mx-4 text-center bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-2.5 rounded-lg font-semibold">
                        {{ $translations['nav']['get_started'] }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
