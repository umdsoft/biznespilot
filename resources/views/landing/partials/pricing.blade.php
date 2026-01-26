<!-- Pricing Section -->
<section id="pricing" class="py-24 lg:py-32 bg-gradient-to-b from-gray-50 to-white relative overflow-hidden">
    <!-- Background decoration -->
    <div class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-[500px] h-[500px] bg-blue-200 rounded-full opacity-20 blur-3xl"></div>
    <div class="absolute bottom-0 left-0 translate-y-1/4 -translate-x-1/4 w-[500px] h-[500px] bg-indigo-200 rounded-full opacity-20 blur-3xl"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section header -->
        <div class="text-center mb-16 animate-on-scroll">
            <span class="inline-block px-4 py-2 bg-gradient-to-r from-green-100 to-emerald-100 text-green-700 rounded-full text-sm font-medium mb-6">
                <svg class="w-4 h-4 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ $translations['pricing']['badge'] }}
            </span>
            <h2 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-6">
                {{ $translations['pricing']['title'] }}
            </h2>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                {{ $translations['pricing']['subtitle'] }}
            </p>
        </div>

        <!-- Pricing cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 lg:gap-8">
            @foreach($translations['pricing']['plans'] as $index => $plan)
                @php
                    $isPopular = $plan['popular'] ?? false;
                    $isPremium = $plan['premium'] ?? false;
                    $icons = [
                        0 => '<svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
                        1 => '<svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>',
                        2 => '<svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
                        3 => '<svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>',
                    ];
                    $iconColors = [
                        0 => 'text-blue-600 bg-blue-50',
                        1 => 'text-emerald-600 bg-emerald-50',
                        2 => 'text-purple-600 bg-purple-50',
                        3 => 'text-amber-600 bg-amber-50',
                    ];
                    $borderColors = [
                        0 => 'hover:border-blue-300',
                        1 => 'hover:border-emerald-300',
                        2 => 'border-purple-200 hover:border-purple-400',
                        3 => 'hover:border-amber-300',
                    ];
                @endphp
                <div class="animate-on-scroll relative {{ $isPopular ? 'z-10' : '' }}" style="animation-delay: {{ $index * 0.1 }}s;">
                    {{-- Popular card wrapper with scale --}}
                    <div class="{{ $isPopular ? 'lg:-mt-4 lg:-mb-4' : '' }}">
                        {{-- Glow effect --}}
                        @if($isPopular)
                            <div class="absolute -inset-1 bg-gradient-to-r from-purple-500 to-violet-500 rounded-[28px] blur opacity-30"></div>
                        @elseif($isPremium)
                            <div class="absolute -inset-1 bg-gradient-to-r from-amber-400 to-orange-500 rounded-[28px] blur opacity-20"></div>
                        @endif

                        <div class="relative bg-white rounded-3xl p-8 lg:p-10 shadow-xl border-2 {{ $isPopular ? 'border-purple-300' : ($isPremium ? 'border-amber-200' : 'border-gray-100') }} {{ $borderColors[$index] ?? 'hover:border-blue-300' }} hover:shadow-2xl transition-all duration-500 h-full flex flex-col">

                            <!-- Badge -->
                            @if($isPopular)
                                <div class="absolute -top-4 left-1/2 -translate-x-1/2 px-5 py-2 bg-gradient-to-r from-purple-600 to-violet-600 text-white text-sm font-bold rounded-full shadow-lg flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    {{ $translations['pricing']['popular_badge'] }}
                                </div>
                            @elseif($isPremium)
                                <div class="absolute -top-4 left-1/2 -translate-x-1/2 px-5 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-sm font-bold rounded-full shadow-lg flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd"/>
                                    </svg>
                                    VIP
                                </div>
                            @endif

                            <!-- Icon & Plan name -->
                            <div class="flex items-start gap-4 mb-6 {{ $isPopular || $isPremium ? 'mt-2' : '' }}">
                                <div class="w-14 h-14 rounded-2xl flex items-center justify-center flex-shrink-0 {{ $iconColors[$index] ?? 'text-blue-600 bg-blue-50' }}">
                                    {!! $icons[$index] ?? $icons[0] !!}
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-900">{{ $plan['name'] }}</h3>
                                    <p class="text-gray-500 mt-1">{{ $plan['description'] }}</p>
                                </div>
                            </div>

                            <!-- Price -->
                            <div class="mb-8 pb-8 border-b border-gray-100">
                                <div class="flex items-baseline gap-2">
                                    <span class="text-5xl font-bold text-gray-900">{{ $plan['price'] }}</span>
                                    <span class="text-gray-500 text-lg">{{ $translations['pricing']['per_month'] }}</span>
                                </div>
                                @if($isPopular)
                                    <div class="mt-3 inline-flex items-center gap-1.5 text-purple-600 text-sm font-medium">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/>
                                        </svg>
                                        Eng foydali tanlov
                                    </div>
                                @endif
                            </div>

                            <!-- Features -->
                            <ul class="space-y-4 mb-10 flex-1">
                                @foreach($plan['features'] as $feature)
                                    <li class="flex items-start gap-3">
                                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 {{ $isPopular ? 'text-purple-500' : ($isPremium ? 'text-amber-500' : 'text-green-500') }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-gray-700 text-base">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <!-- CTA Button -->
                            <a href="{{ route('register') }}"
                               class="block w-full py-4 px-6 rounded-2xl font-bold text-center text-lg transition-all duration-300 transform hover:scale-[1.02] hover:-translate-y-0.5
                               {{ $isPopular
                                   ? 'bg-gradient-to-r from-purple-600 to-violet-600 text-white hover:from-purple-700 hover:to-violet-700 shadow-lg shadow-purple-500/30 hover:shadow-xl hover:shadow-purple-500/40'
                                   : ($isPremium
                                       ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white hover:from-amber-600 hover:to-orange-600 shadow-lg shadow-amber-500/30 hover:shadow-xl hover:shadow-amber-500/40'
                                       : 'bg-gray-100 text-gray-800 hover:bg-gray-200 hover:shadow-lg')
                               }}">
                                {{ $plan['cta'] }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Trust badges -->
        <div class="mt-16 flex flex-wrap items-center justify-center gap-8 text-gray-500 animate-on-scroll">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>SSL Secured</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                    <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                </svg>
                <span>Click & Payme</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                <span>24/7 Support</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                </svg>
                <span>Istalgan vaqt bekor qilish</span>
            </div>
        </div>

        <!-- Bottom note -->
        <div class="text-center mt-10 animate-on-scroll">
            <p class="text-gray-500">
                {{ $translations['pricing']['note'] }}
            </p>
            <a href="{{ route('pricing') }}" class="inline-flex items-center mt-6 text-blue-600 hover:text-blue-700 font-semibold text-lg transition-colors group">
                {{ $translations['pricing']['compare_link'] }}
                <svg class="w-5 h-5 ml-2 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</section>
