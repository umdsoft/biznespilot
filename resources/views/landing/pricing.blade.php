@extends('landing.layouts.landing')

@section('content')
    {{-- Header / Navigation --}}
    @include('landing.partials.header')

    {{-- Hero Section with Social Proof --}}
    <section class="pt-32 pb-12 bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/50 relative overflow-hidden">
        {{-- Background decorations --}}
        <div class="absolute top-20 left-10 w-72 h-72 bg-blue-200/30 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 bg-indigo-200/30 rounded-full blur-3xl"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            {{-- Trust indicator --}}
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/80 backdrop-blur-sm border border-gray-200 rounded-full text-sm mb-6 animate-on-scroll shadow-sm">
                <div class="flex -space-x-2">
                    <div class="w-6 h-6 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 border-2 border-white flex items-center justify-center text-white text-xs font-bold">A</div>
                    <div class="w-6 h-6 rounded-full bg-gradient-to-br from-green-400 to-green-600 border-2 border-white flex items-center justify-center text-white text-xs font-bold">B</div>
                    <div class="w-6 h-6 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 border-2 border-white flex items-center justify-center text-white text-xs font-bold">C</div>
                </div>
                <span class="text-gray-600"><strong class="text-gray-900">100+</strong> {{ $locale === 'ru' ? 'компаний уже используют' : 'kompaniya allaqachon foydalanmoqda' }}</span>
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 mb-6 animate-on-scroll">
                {!! $locale === 'ru'
                    ? 'Инвестируйте в <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">рост бизнеса</span>'
                    : 'Biznesingiz o\'sishiga <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">investitsiya qiling</span>'
                !!}
            </h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto mb-8 animate-on-scroll">
                {{ $locale === 'ru'
                    ? 'Выберите подходящий тариф. Без скрытых платежей. Отмена в любой момент.'
                    : 'O\'zingizga mos tarifni tanlang. Yashirin to\'lovlar yo\'q. Istalgan vaqt bekor qilish mumkin.'
                }}
            </p>

            {{-- Money-back guarantee --}}
            <div class="mt-8 inline-flex items-center gap-3 px-6 py-3 bg-green-50 border border-green-200 rounded-2xl animate-on-scroll">
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div class="text-left">
                    <p class="font-semibold text-green-800">{{ $locale === 'ru' ? '14 дней бесплатно' : '14 kun bepul sinov' }}</p>
                    <p class="text-sm text-green-600">{{ $locale === 'ru' ? 'Карта не требуется • Полный доступ' : 'Karta kerak emas • To\'liq kirish' }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing Cards - Premium Design --}}
    <section class="py-16 -mt-8 relative z-20" x-data="{ isYearly: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Billing Toggle - Premium Design --}}
            <div class="flex justify-center mb-12">
                <div class="relative inline-flex items-center p-1.5 bg-white rounded-2xl shadow-xl border border-gray-100">
                    {{-- Background slider --}}
                    <div class="absolute inset-y-1.5 transition-all duration-300 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg"
                         :class="isYearly ? 'left-[50%] right-1.5' : 'left-1.5 right-[50%]'"></div>
                    <button
                        @click="isYearly = false"
                        class="relative z-10 px-8 py-3.5 text-sm font-bold rounded-xl transition-all duration-300"
                        :class="!isYearly ? 'text-white' : 'text-gray-500 hover:text-gray-700'"
                    >
                        {{ $locale === 'ru' ? 'Ежемесячно' : 'Oylik' }}
                    </button>
                    <button
                        @click="isYearly = true"
                        class="relative z-10 px-8 py-3.5 text-sm font-bold rounded-xl transition-all duration-300 flex items-center gap-2"
                        :class="isYearly ? 'text-white' : 'text-gray-500 hover:text-gray-700'"
                    >
                        {{ $locale === 'ru' ? 'Ежегодно' : 'Yillik' }}
                        <span class="px-2.5 py-1 text-xs font-bold rounded-full transition-all duration-300"
                              :class="isYearly ? 'bg-white/20 text-white' : 'bg-green-100 text-green-700'">
                            2 OY BEPUL
                        </span>
                    </button>
                </div>
            </div>

            {{-- Savings notification --}}
            <div x-show="isYearly" x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform -translate-y-4"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 class="flex justify-center mb-10">
                <div class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-full text-green-700 font-semibold shadow-sm">
                    <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    {{ $locale === 'ru' ? '2 месяца бесплатно при годовой оплате!' : 'Yillik to\'lovda 2 oy bepul!' }}
                </div>
            </div>

            @php
                // Define 4 plans (without Free)
                $pricingPlans = [
                    [
                        'id' => 'start',
                        'name' => 'Start',
                        'description' => $locale === 'ru' ? 'Для начала' : 'Boshlash uchun',
                        'price' => 299000,
                        'features' => [
                            $locale === 'ru' ? '2 сотрудника' : '2 ta xodim',
                            $locale === 'ru' ? '1 филиал' : '1 ta filial',
                            'Instagram + Telegram',
                            $locale === 'ru' ? '500 лидов/мес' : '500 ta lid/oy',
                            $locale === 'ru' ? '60 мин Call Center AI' : '60 daq Call Center AI',
                        ],
                        'color' => 'blue',
                        'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                    ],
                    [
                        'id' => 'standard',
                        'name' => 'Standard',
                        'description' => $locale === 'ru' ? 'Для роста' : 'Rivojlanish uchun',
                        'price' => 599000,
                        'features' => [
                            $locale === 'ru' ? '5 сотрудников' : '5 ta xodim',
                            $locale === 'ru' ? '1 филиал' : '1 ta filial',
                            $locale === 'ru' ? 'Flow Builder (Визуал)' : 'Flow Builder (Vizual)',
                            $locale === 'ru' ? '2,000 лидов/мес' : '2,000 ta lid/oy',
                            $locale === 'ru' ? '150 мин Call Center AI' : '150 daq Call Center AI',
                        ],
                        'color' => 'emerald',
                        'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
                    ],
                    [
                        'id' => 'business',
                        'name' => 'Business',
                        'description' => $locale === 'ru' ? 'Для систематизации' : 'Tizimlashish uchun',
                        'price' => 799000,
                        'popular' => true,
                        'features' => [
                            $locale === 'ru' ? '10 сотрудников' : '10 ta xodim',
                            $locale === 'ru' ? '2 филиала' : '2 ta filial',
                            'HR Bot + Marketing ROI',
                            $locale === 'ru' ? '10,000 лидов/мес' : '10,000 ta lid/oy',
                            $locale === 'ru' ? '400 мин Call Center AI' : '400 daq Call Center AI',
                        ],
                        'color' => 'purple',
                        'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                    ],
                    [
                        'id' => 'premium',
                        'name' => 'Premium',
                        'description' => $locale === 'ru' ? 'Для масштабирования' : 'Masshtablash uchun',
                        'price' => 1499000,
                        'premium' => true,
                        'features' => [
                            $locale === 'ru' ? '15 сотрудников' : '15 ta xodim',
                            $locale === 'ru' ? '5 филиалов' : '5 ta filial',
                            'AI Bot + Anti-Fraud',
                            $locale === 'ru' ? 'Безлимит лидов' : 'Cheksiz lid',
                            $locale === 'ru' ? '1,000 мин Call Center AI' : '1,000 daq Call Center AI',
                        ],
                        'color' => 'amber',
                        'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
                    ],
                ];
            @endphp

            {{-- Pricing Cards Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 lg:gap-8 items-stretch">
                @foreach($pricingPlans as $index => $plan)
                    @php
                        $isPopular = $plan['popular'] ?? false;
                        $isPremium = $plan['premium'] ?? false;
                        $yearlyPrice = round($plan['price'] * 10 / 12);
                        $yearlySaving = $plan['price'] * 2;

                        $colorSchemes = [
                            'blue' => [
                                'gradient' => 'from-blue-500 to-blue-600',
                                'light' => 'bg-blue-50',
                                'border' => 'border-blue-100 hover:border-blue-300',
                                'icon_bg' => 'bg-gradient-to-br from-blue-100 to-blue-50',
                                'icon_color' => 'text-blue-600',
                                'check' => 'text-blue-500',
                                'btn' => 'bg-gray-900 hover:bg-gray-800 text-white',
                            ],
                            'emerald' => [
                                'gradient' => 'from-emerald-500 to-teal-500',
                                'light' => 'bg-emerald-50',
                                'border' => 'border-emerald-100 hover:border-emerald-300',
                                'icon_bg' => 'bg-gradient-to-br from-emerald-100 to-emerald-50',
                                'icon_color' => 'text-emerald-600',
                                'check' => 'text-emerald-500',
                                'btn' => 'bg-gray-900 hover:bg-gray-800 text-white',
                            ],
                            'purple' => [
                                'gradient' => 'from-purple-500 via-violet-500 to-purple-600',
                                'light' => 'bg-purple-50',
                                'border' => 'border-purple-200',
                                'icon_bg' => 'bg-gradient-to-br from-purple-100 to-purple-50',
                                'icon_color' => 'text-purple-600',
                                'check' => 'text-purple-500',
                                'btn' => 'bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-700 hover:to-violet-700 text-white shadow-lg shadow-purple-500/25',
                            ],
                            'amber' => [
                                'gradient' => 'from-amber-400 via-orange-500 to-amber-500',
                                'light' => 'bg-amber-50',
                                'border' => 'border-amber-200',
                                'icon_bg' => 'bg-gradient-to-br from-amber-100 to-amber-50',
                                'icon_color' => 'text-amber-600',
                                'check' => 'text-amber-500',
                                'btn' => 'bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white shadow-lg shadow-amber-500/25',
                            ],
                        ];
                        $scheme = $colorSchemes[$plan['color']];
                    @endphp

                    <div class="relative group {{ $isPopular ? 'xl:-mt-6 xl:mb-6' : '' }}"
                         style="animation: fadeInUp 0.6s ease-out {{ $index * 0.1 }}s both;">

                        {{-- Glow effect for popular/premium --}}
                        @if($isPopular)
                            <div class="absolute -inset-[2px] bg-gradient-to-b {{ $scheme['gradient'] }} rounded-[26px] opacity-100"></div>
                        @elseif($isPremium)
                            <div class="absolute -inset-[2px] bg-gradient-to-b {{ $scheme['gradient'] }} rounded-[26px] opacity-100"></div>
                        @endif

                        <div class="relative h-full bg-white rounded-3xl {{ !$isPopular && !$isPremium ? 'border-2 ' . $scheme['border'] : '' }} transition-all duration-500 flex flex-col overflow-hidden
                                    {{ $isPopular ? 'shadow-2xl shadow-purple-500/20' : ($isPremium ? 'shadow-2xl shadow-amber-500/20' : 'shadow-lg hover:shadow-2xl') }}
                                    {{ !$isPopular && !$isPremium ? 'hover:-translate-y-2' : '' }}">

                            {{-- Colored header bar --}}
                            <div class="h-1.5 bg-gradient-to-r {{ $scheme['gradient'] }}"></div>

                            {{-- Badge --}}
                            @if($isPopular)
                                <div class="absolute -top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 z-10">
                                    <span class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-gradient-to-r from-purple-600 to-violet-600 text-white text-sm font-bold rounded-full shadow-xl shadow-purple-500/30">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        ENG FOYDALI
                                    </span>
                                </div>
                            @elseif($isPremium)
                                <div class="absolute -top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 z-10">
                                    <span class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-sm font-bold rounded-full shadow-xl shadow-amber-500/30">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd"/>
                                        </svg>
                                        VIP
                                    </span>
                                </div>
                            @endif

                            <div class="p-6 lg:p-8 flex flex-col flex-1 {{ $isPopular || $isPremium ? 'pt-10' : '' }}">
                                {{-- Plan Header --}}
                                <div class="flex items-start gap-4 mb-6">
                                    <div class="w-14 h-14 rounded-2xl {{ $scheme['icon_bg'] }} flex items-center justify-center flex-shrink-0 shadow-sm">
                                        <svg class="w-7 h-7 {{ $scheme['icon_color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="{{ $plan['icon'] }}" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900">{{ $plan['name'] }}</h3>
                                        <p class="text-sm text-gray-500">{{ $plan['description'] }}</p>
                                    </div>
                                </div>

                                {{-- Price --}}
                                <div class="mb-6">
                                    {{-- Monthly Price --}}
                                    <div x-show="!isYearly">
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-4xl lg:text-5xl font-extrabold text-gray-900 tracking-tight">{{ number_format($plan['price'], 0, '', ',') }}</span>
                                            <div class="flex flex-col">
                                                <span class="text-lg font-bold text-gray-700">so'm</span>
                                                <span class="text-xs text-gray-500 -mt-1">/oy</span>
                                            </div>
                                        </div>
                                        @if($isPopular)
                                            <p class="mt-2 inline-flex items-center gap-1.5 text-purple-600 text-sm font-semibold bg-purple-50 px-3 py-1.5 rounded-full">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/>
                                                </svg>
                                                Eng foydali tanlov
                                            </p>
                                        @endif
                                    </div>
                                    {{-- Yearly Price --}}
                                    <div x-show="isYearly" x-cloak>
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-4xl lg:text-5xl font-extrabold text-gray-900 tracking-tight">{{ number_format($yearlyPrice, 0, '', ',') }}</span>
                                            <div class="flex flex-col">
                                                <span class="text-lg font-bold text-gray-700">so'm</span>
                                                <span class="text-xs text-gray-500 -mt-1">/oy</span>
                                            </div>
                                        </div>
                                        <div class="mt-2 flex items-center gap-2 flex-wrap">
                                            <span class="text-gray-400 line-through text-sm">{{ number_format($plan['price'], 0, '', ',') }} so'm</span>
                                            <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-bold rounded-full">
                                                {{ number_format($yearlySaving, 0, '', ',') }} so'm tejash!
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Divider --}}
                                <div class="border-t border-gray-100 mb-6"></div>

                                {{-- Features --}}
                                <ul class="space-y-3.5 mb-8 flex-1">
                                    @foreach($plan['features'] as $feature)
                                        <li class="flex items-start gap-3">
                                            <div class="w-5 h-5 rounded-full bg-gradient-to-br {{ $scheme['gradient'] }} flex items-center justify-center flex-shrink-0 mt-0.5">
                                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <span class="text-gray-700 text-sm lg:text-base">{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                </ul>

                                {{-- CTA Button --}}
                                <a href="{{ route('register') }}?plan={{ $plan['id'] }}"
                                   class="block w-full py-4 px-6 rounded-xl font-bold text-center text-base transition-all duration-300 transform hover:scale-[1.02] hover:-translate-y-0.5 {{ $scheme['btn'] }}">
                                    {{ $isPopular ? ($locale === 'ru' ? 'Начать бесплатно' : 'Bepul Boshlash') : ($isPremium ? ($locale === 'ru' ? 'Получить Premium' : 'Premium olish') : ($locale === 'ru' ? 'Начать' : ($index === 1 ? 'Tanlash' : 'Boshlash'))) }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Trust badges - Premium Design --}}
            <div class="mt-14 flex flex-wrap items-center justify-center gap-6 lg:gap-10">
                <div class="flex items-center gap-2.5 text-gray-500 group">
                    <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center group-hover:bg-green-100 transition-colors">
                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium">SSL Secured</span>
                </div>
                <div class="flex items-center gap-2.5 text-gray-500 group">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                            <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium">Click & Payme</span>
                </div>
                <div class="flex items-center gap-2.5 text-gray-500 group">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center group-hover:bg-purple-100 transition-colors">
                        <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium">24/7 Support</span>
                </div>
                <div class="flex items-center gap-2.5 text-gray-500 group">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center group-hover:bg-amber-100 transition-colors">
                        <svg class="w-5 h-5 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium">{{ $locale === 'ru' ? 'Отмена в любое время' : 'Istalgan vaqt bekor qilish' }}</span>
                </div>
            </div>
        </div>

        <style>
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        </style>
    </section>

    {{-- ROI Calculator Section - Light Theme --}}
    <section class="py-16 bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10 animate-on-scroll">
                <span class="inline-block px-4 py-1.5 bg-green-100 text-green-700 rounded-full text-sm font-medium mb-4">
                    💰 {{ $locale === 'ru' ? 'Калькулятор экономии' : 'Tejash kalkulyatori' }}
                </span>
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                    {{ $locale === 'ru' ? 'Сколько вы сэкономите?' : 'Qancha tejaysiz?' }}
                </h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    {{ $locale === 'ru'
                        ? 'BiznesPilot заменяет 6 отдельных сервисов. Посчитайте вашу экономию.'
                        : 'BiznesPilot 6 ta alohida xizmatni almashtiradi. Tejamingizni hisoblang.'
                    }}
                </p>
            </div>

            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden animate-on-scroll">
                <div class="grid md:grid-cols-2">
                    {{-- Left side - Services list --}}
                    <div class="p-8 lg:p-10 border-b md:border-b-0 md:border-r border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                            </svg>
                            {{ $locale === 'ru' ? 'Отдельно покупать' : 'Alohida sotib olsangiz' }}
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                                <span class="text-gray-600 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                    CRM tizimi
                                </span>
                                <span class="font-semibold text-gray-900">~350,000</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                                <span class="text-gray-600 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-pink-500 rounded-full"></span>
                                    Instagram bot
                                </span>
                                <span class="font-semibold text-gray-900">~250,000</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                                <span class="text-gray-600 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-sky-500 rounded-full"></span>
                                    Telegram bot
                                </span>
                                <span class="font-semibold text-gray-900">~200,000</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                                <span class="text-gray-600 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                                    Kontent planner
                                </span>
                                <span class="font-semibold text-gray-900">~150,000</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                                <span class="text-gray-600 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                    Analytics tool
                                </span>
                                <span class="font-semibold text-gray-900">~200,000</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                                <span class="text-gray-600 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-orange-500 rounded-full"></span>
                                    Call Center AI
                                </span>
                                <span class="font-semibold text-gray-900">~300,000</span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-red-50 rounded-xl border border-red-200 mt-4">
                                <span class="text-red-700 font-semibold">{{ $locale === 'ru' ? 'Итого отдельно' : 'Jami alohida' }}</span>
                                <span class="font-bold text-red-600 text-xl line-through">1,450,000 {{ $locale === 'ru' ? 'сум' : 'so\'m' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Right side - BiznesPilot offer --}}
                    <div class="p-8 lg:p-10 bg-gradient-to-br from-green-50 to-emerald-50 flex flex-col justify-center">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            BiznesPilot Business
                        </h3>

                        <div class="text-center mb-6">
                            <div class="inline-block px-6 py-8 bg-white rounded-2xl shadow-lg border border-green-200">
                                <p class="text-sm text-gray-500 mb-1">{{ $locale === 'ru' ? 'Все включено' : 'Hammasi kiritilgan' }}</p>
                                <p class="text-5xl font-bold text-gray-900 mb-1">799,000</p>
                                <p class="text-gray-500">{{ $locale === 'ru' ? 'сум/мес' : 'so\'m/oy' }}</p>
                            </div>
                        </div>

                        <div class="bg-green-100 rounded-xl p-4 text-center mb-6">
                            <p class="text-green-800 font-bold text-2xl">
                                {{ $locale === 'ru' ? 'Экономия 651,000 сум!' : '651,000 so\'m tejaysiz!' }}
                            </p>
                            <p class="text-green-600 text-sm mt-1">
                                {{ $locale === 'ru' ? '45% экономия каждый месяц' : 'Har oy 45% tejash' }}
                            </p>
                        </div>

                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                {{ $locale === 'ru' ? 'Все в одном месте' : 'Hammasi bir joyda' }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                {{ $locale === 'ru' ? 'Без интеграций' : 'Integratsiyalarsiz' }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                {{ $locale === 'ru' ? 'Единая аналитика' : 'Yagona analitika' }}
                            </li>
                        </ul>

                        <a href="{{ route('register') }}?plan=business"
                           class="mt-6 block w-full py-3.5 px-6 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-semibold rounded-xl text-center hover:from-green-600 hover:to-emerald-700 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            {{ $locale === 'ru' ? 'Начать экономить' : 'Tejashni boshlash' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Social Proof / Testimonial --}}
    <section class="py-12 bg-gradient-to-r from-blue-600 to-indigo-600 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <div class="flex justify-center mb-4">
                @for($i = 0; $i < 5; $i++)
                    <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                @endfor
            </div>
            <blockquote class="text-xl sm:text-2xl text-white font-medium mb-6 animate-on-scroll">
                "{{ $locale === 'ru'
                    ? 'BiznesPilot помог нам увеличить конверсию на 340% за 3 месяца. Теперь ни один лид не теряется!'
                    : 'BiznesPilot bizga 3 oyda konversiyani 340% ga oshirishga yordam berdi. Endi birorta lid yo\'qolmaydi!'
                }}"
            </blockquote>
            <div class="flex items-center justify-center gap-4 animate-on-scroll">
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center text-white font-bold">
                    AS
                </div>
                <div class="text-left">
                    <p class="text-white font-semibold">Aziz Salomov</p>
                    <p class="text-blue-100 text-sm">CEO</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Comparison Table --}}
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 animate-on-scroll">
                <span class="inline-block px-4 py-1.5 bg-blue-100 text-blue-700 rounded-full text-sm font-medium mb-4">
                    {{ $locale === 'ru' ? 'Подробное сравнение' : 'Batafsil solishtirish' }}
                </span>
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">{{ $translations['pricing_page']['compare_title'] ?? 'Barcha imkoniyatlarni solishtiring' }}</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">{{ $translations['pricing_page']['compare_subtitle'] ?? 'Har bir tarifda nimalar bor - batafsil jadval' }}</p>
            </div>

            <div class="bg-white rounded-3xl border border-gray-200 shadow-xl overflow-hidden animate-on-scroll">
                @include('landing.partials.pricing-comparison-table')
            </div>
        </div>
    </section>

    {{-- FAQ Section --}}
    <section class="py-20 bg-white" id="faq">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 animate-on-scroll">
                <span class="inline-block px-4 py-1.5 bg-blue-100 text-blue-700 rounded-full text-sm font-medium mb-4">
                    {{ $translations['pricing_page']['faq_badge'] ?? 'Savollar va javoblar' }}
                </span>
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">{{ $translations['pricing_page']['faq_title'] ?? "Ko'p so'raladigan savollar" }}</h2>
            </div>

            <div class="space-y-4" x-data="{ openFaq: null }">
                @foreach($translations['pricing_page']['faqs'] ?? [] as $index => $faq)
                    <div class="bg-gray-50 rounded-2xl border border-gray-200 overflow-hidden hover:border-blue-300 hover:shadow-lg transition-all duration-300 animate-on-scroll">
                        <button
                            @click="openFaq = openFaq === {{ $index }} ? null : {{ $index }}"
                            class="w-full flex items-center justify-between p-6 text-left"
                        >
                            <span class="font-semibold text-gray-900 pr-4">{{ $faq['question'] }}</span>
                            <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300"
                                 :class="openFaq === {{ $index }} ? 'bg-blue-600 text-white rotate-180' : 'bg-gray-200 text-gray-600'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>
                        <div
                            x-show="openFaq === {{ $index }}"
                            x-collapse
                            class="px-6 pb-6"
                        >
                            <p class="text-gray-600 leading-relaxed">{{ $faq['answer'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Still have questions --}}
            <div class="mt-12 text-center animate-on-scroll">
                <p class="text-gray-600 mb-4">{{ $locale === 'ru' ? 'Остались вопросы?' : 'Hali savollaringiz bormi?' }}</p>
                <a href="https://t.me/biznespilot_support" target="_blank"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0a12 12 0 00-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                    </svg>
                    {{ $locale === 'ru' ? 'Написать в Telegram' : 'Telegramga yozish' }}
                </a>
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="py-20 bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-600 relative overflow-hidden">
        <div class="absolute inset-0">
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-1/4 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-6 animate-on-scroll">
                {{ $locale === 'ru' ? 'Готовы начать?' : 'Boshlashga tayyormisiz?' }}
            </h2>
            <p class="text-xl text-blue-100 mb-8 animate-on-scroll">
                {{ $locale === 'ru'
                    ? '14 дней бесплатно. Без карты. Без риска.'
                    : '14 kun bepul. Kartasiz. Xavfsiz.'
                }}
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 animate-on-scroll">
                <a href="{{ route('register') }}"
                   class="inline-flex items-center px-8 py-4 bg-white text-blue-600 text-lg font-bold rounded-xl hover:bg-blue-50 transition-all shadow-2xl hover:shadow-3xl transform hover:-translate-y-1">
                    {{ $locale === 'ru' ? 'Начать бесплатно' : 'Bepul boshlash' }}
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
                <a href="https://t.me/biznespilot" target="_blank"
                   class="inline-flex items-center px-8 py-4 bg-white/10 backdrop-blur-sm text-white text-lg font-semibold rounded-xl hover:bg-white/20 transition-all border border-white/20">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0a12 12 0 00-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                    </svg>
                    {{ $locale === 'ru' ? 'Демо в Telegram' : 'Telegram demo' }}
                </a>
            </div>

            <p class="mt-8 text-blue-200 text-sm animate-on-scroll">
                {{ $locale === 'ru'
                    ? '✓ Настройка за 10 минут  ✓ Поддержка 24/7  ✓ Без скрытых платежей'
                    : '✓ 10 daqiqada sozlash  ✓ 24/7 yordam  ✓ Yashirin to\'lovlarsiz'
                }}
            </p>
        </div>
    </section>

    {{-- Footer --}}
    @include('landing.partials.footer')
@endsection
