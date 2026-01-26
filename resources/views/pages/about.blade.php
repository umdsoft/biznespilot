@extends('landing.layouts.landing')

@section('content')
    @include('landing.partials.header')

    <main class="pt-24 min-h-screen">
        <!-- Hero Section -->
        <section class="relative gradient-bg py-20 overflow-hidden">
            <!-- Background decorations -->
            <div class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-96 h-96 bg-blue-300 rounded-full opacity-20 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 translate-y-1/4 -translate-x-1/4 w-96 h-96 bg-indigo-300 rounded-full opacity-20 blur-3xl"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center max-w-3xl mx-auto">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl mb-8 shadow-xl">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h1 class="text-5xl font-bold text-gray-900 mb-6">
                        {{ $locale === 'ru' ? 'О' : '' }} <span class="gradient-text">BiznesPilot</span>{{ $locale === 'ru' ? '' : ' haqida' }}
                    </h1>
                    <p class="text-xl text-gray-600 leading-relaxed">
                        {{ $locale === 'ru'
                            ? "Единственная Бизнес Операционная Система Узбекистана. Объединяем Маркетинг, Продажи, HR и Финансы с помощью Искусственного интеллекта — чтобы вы получили полный контроль над бизнесом."
                            : "O'zbekistonning yagona Biznes Operatsion Tizimi. Marketing, Sotuv, HR va Moliyani Sun'iy intellekt yordamida birlashtiramiz — siz biznesingizni to'liq nazorat qilasiz."
                        }}
                    </p>
                </div>
            </div>
        </section>

        <!-- What is BOS Section -->
        <section class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">
                        {{ $locale === 'ru' ? 'Что такое Бизнес Операционная Система?' : 'Biznes Operatsion Tizimi nima?' }}
                    </h2>
                    <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                        {{ $locale === 'ru'
                            ? "Как Windows управляет всеми программами на компьютере, так BiznesPilot управляет всеми процессами вашего бизнеса. Все отделы — в одном месте."
                            : "Xuddi Windows kompyuterdagi barcha dasturlarni boshqarganidek, BiznesPilot ham biznesingizning barcha jarayonlarini boshqaradi. Barcha bo'limlar — bir joyda."
                        }}
                    </p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Marketing -->
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-100">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $locale === 'ru' ? 'Маркетинг' : 'Marketing' }}</h3>
                        <p class="text-gray-600 text-sm">
                            {{ $locale === 'ru' ? 'Все каналы и клиенты в одном месте' : "Barcha kanallar va mijozlar bir joyda" }}
                        </p>
                    </div>

                    <!-- Sales -->
                    <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl p-6 border border-emerald-100">
                        <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $locale === 'ru' ? 'Продажи' : 'Sotuv' }}</h3>
                        <p class="text-gray-600 text-sm">
                            {{ $locale === 'ru' ? 'Каждый клиент и сделка под контролем' : "Har bir mijoz va kelishuv nazoratda" }}
                        </p>
                    </div>

                    <!-- HR -->
                    <div class="bg-gradient-to-br from-violet-50 to-purple-50 rounded-2xl p-6 border border-violet-100">
                        <div class="w-12 h-12 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">HR</h3>
                        <p class="text-gray-600 text-sm">
                            {{ $locale === 'ru' ? 'Сотрудники и задачи прозрачны' : "Xodimlar va vazifalar shaffof" }}
                        </p>
                    </div>

                    <!-- Finance -->
                    <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl p-6 border border-amber-100">
                        <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $locale === 'ru' ? 'Финансы' : 'Moliya' }}</h3>
                        <p class="text-gray-600 text-sm">
                            {{ $locale === 'ru' ? 'Где деньги — всегда видно' : "Pul qayerda — doim ko'rinadi" }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Mission & Vision -->
        <section class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-2 gap-12">
                    <!-- Mission -->
                    <div class="relative">
                        <div class="absolute -top-4 -left-4 w-20 h-20 bg-blue-100 rounded-2xl -z-10"></div>
                        <div class="bg-white rounded-3xl p-8 border border-gray-200 shadow-lg">
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mb-6">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-4">
                                {{ $locale === 'ru' ? 'Наша Миссия' : 'Bizning Missiyamiz' }}
                            </h2>
                            <p class="text-gray-600 leading-relaxed">
                                {{ $locale === 'ru'
                                    ? "Дать каждому бизнесу в Узбекистане полный контроль над своими процессами. Объединить Маркетинг, Продажи, HR и Финансы в одной системе с помощью Искусственного интеллекта — чтобы предприниматели могли сосредоточиться на главном: развитии бизнеса."
                                    : "O'zbekistondagi har bir biznesga o'z jarayonlarini to'liq nazorat qilish imkonini berish. Marketing, Sotuv, HR va Moliyani Sun'iy intellekt yordamida bitta tizimda birlashtirish — tadbirkorlar asosiy ishga e'tibor qaratsin: biznesni o'stirishga."
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Vision -->
                    <div class="relative">
                        <div class="absolute -top-4 -right-4 w-20 h-20 bg-emerald-100 rounded-2xl -z-10"></div>
                        <div class="bg-white rounded-3xl p-8 border border-gray-200 shadow-lg">
                            <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center mb-6">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-4">
                                {{ $locale === 'ru' ? 'Наше Видение' : 'Bizning Visionimiz' }}
                            </h2>
                            <p class="text-gray-600 leading-relaxed">
                                {{ $locale === 'ru'
                                    ? "К 2030 году стать ведущей Бизнес Операционной Системой в Узбекистане и Центральной Азии. Помочь тысячам предпринимателей получить полный контроль над бизнесом и достичь успеха."
                                    : "2030-yilga kelib O'zbekiston va Markaziy Osiyodagi yetakchi Biznes Operatsion Tizimiga aylanish. Minglab tadbirkorlarga bizneslarini to'liq nazorat qilish va muvaffaqiyatga erishishda yordam berish."
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="py-16 bg-gradient-to-r from-blue-600 to-indigo-600">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                    <div class="text-center">
                        <div class="text-4xl lg:text-5xl font-bold text-white mb-2">4</div>
                        <div class="text-blue-100">{{ $locale === 'ru' ? 'Основных модуля' : 'Asosiy modul' }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl lg:text-5xl font-bold text-white mb-2">100%</div>
                        <div class="text-blue-100">{{ $locale === 'ru' ? 'Контроль' : 'Nazorat' }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl lg:text-5xl font-bold text-white mb-2">10 {{ $locale === 'ru' ? 'мин' : 'daq' }}</div>
                        <div class="text-blue-100">{{ $locale === 'ru' ? 'Настройка' : "O'rnatish" }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl lg:text-5xl font-bold text-white mb-2">24/7</div>
                        <div class="text-blue-100">{{ $locale === 'ru' ? 'Искусственный интеллект' : "Sun'iy intellekt" }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why BiznesPilot -->
        <section class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">
                        {{ $locale === 'ru' ? 'Почему именно BiznesPilot?' : 'Nega aynan BiznesPilot?' }}
                    </h2>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                        {{ $locale === 'ru'
                            ? "Единственная система, которая объединяет все отделы бизнеса и даёт полный контроль"
                            : "Biznesingizning barcha bo'limlarini birlashtiruvchi va to'liq nazorat beruvchi yagona tizim"
                        }}
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Card 1 -->
                    <div class="bg-gray-50 rounded-2xl p-8 hover:shadow-lg transition-shadow">
                        <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">
                            {{ $locale === 'ru' ? 'Полный контроль' : "To'liq nazorat" }}
                        </h3>
                        <p class="text-gray-600">
                            {{ $locale === 'ru'
                                ? "4 модуля в одной системе. Маркетинг, Продажи, HR, Финансы — всё видно на одном экране. Никаких разрозненных данных."
                                : "4 ta modul bitta tizimda. Marketing, Sotuv, HR, Moliya — hammasi bitta ekranda ko'rinadi. Tarqoq ma'lumotlar yo'q."
                            }}
                        </p>
                    </div>

                    <!-- Card 2 -->
                    <div class="bg-gray-50 rounded-2xl p-8 hover:shadow-lg transition-shadow">
                        <div class="w-14 h-14 bg-emerald-100 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">
                            {{ $locale === 'ru' ? 'Искусственный интеллект' : "Sun'iy intellekt" }}
                        </h3>
                        <p class="text-gray-600">
                            {{ $locale === 'ru'
                                ? "Система находит проблемы заранее, даёт рекомендации, выполняет рутинные задачи автоматически. Работает 24/7."
                                : "Tizim muammolarni oldindan topadi, tavsiyalar beradi, takroriy ishlarni avtomatik bajaradi. 24/7 ishlaydi."
                            }}
                        </p>
                    </div>

                    <!-- Card 3 -->
                    <div class="bg-gray-50 rounded-2xl p-8 hover:shadow-lg transition-shadow">
                        <div class="w-14 h-14 bg-violet-100 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">
                            {{ $locale === 'ru' ? 'Сделано для Узбекистана' : "O'zbekiston uchun yaratilgan" }}
                        </h3>
                        <p class="text-gray-600">
                            {{ $locale === 'ru'
                                ? "Локальные интеграции (Click, Payme, 1C), узбекский и русский языки, сум валюта, местная поддержка."
                                : "Mahalliy integratsiyalar (Click, Payme, 1C), o'zbek va rus tillari, so'm valyutasi, mahalliy qo'llab-quvvatlash."
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Values Section -->
        <section class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">
                        {{ $locale === 'ru' ? 'Наши ценности' : 'Bizning qadriyatlarimiz' }}
                    </h2>
                    <p class="text-xl text-gray-600">
                        {{ $locale === 'ru' ? 'Принципы, которыми мы руководствуемся' : "Biz amal qiladigan tamoyillar" }}
                    </p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Value 1 -->
                    <div class="text-center p-6 rounded-2xl bg-white border border-gray-200 shadow-sm">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">
                            {{ $locale === 'ru' ? 'Надёжность' : 'Ishonchlilik' }}
                        </h3>
                        <p class="text-gray-600 text-sm">
                            {{ $locale === 'ru' ? 'Открытые и честные отношения с клиентами' : "Mijozlar bilan ochiq va halol munosabatlar" }}
                        </p>
                    </div>

                    <!-- Value 2 -->
                    <div class="text-center p-6 rounded-2xl bg-white border border-gray-200 shadow-sm">
                        <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">
                            {{ $locale === 'ru' ? 'Инновации' : 'Innovatsiya' }}
                        </h3>
                        <p class="text-gray-600 text-sm">
                            {{ $locale === 'ru' ? 'Постоянно внедряем новые технологии' : "Doimiy ravishda yangi texnologiyalarni joriy etamiz" }}
                        </p>
                    </div>

                    <!-- Value 3 -->
                    <div class="text-center p-6 rounded-2xl bg-white border border-gray-200 shadow-sm">
                        <div class="w-16 h-16 bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">
                            {{ $locale === 'ru' ? 'Клиентоориентированность' : "Mijozga e'tibor" }}
                        </h3>
                        <p class="text-gray-600 text-sm">
                            {{ $locale === 'ru' ? 'Каждое решение в интересах клиента' : "Har bir qaror mijoz manfaatini ko'zlab" }}
                        </p>
                    </div>

                    <!-- Value 4 -->
                    <div class="text-center p-6 rounded-2xl bg-white border border-gray-200 shadow-sm">
                        <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">
                            {{ $locale === 'ru' ? 'Результативность' : "Natijaga yo'nalganlik" }}
                        </h3>
                        <p class="text-gray-600 text-sm">
                            {{ $locale === 'ru' ? 'Фокус на реальных результатах клиентов' : "Mijozlarning real natijalariga e'tibor" }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact CTA -->
        <section class="py-20 bg-gradient-to-br from-gray-900 to-gray-800">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold text-white mb-4">
                    {{ $locale === 'ru' ? 'Свяжитесь с нами' : "Biz bilan bog'laning" }}
                </h2>
                <p class="text-xl text-gray-300 mb-8">
                    {{ $locale === 'ru'
                        ? "Хотите получить полный контроль над бизнесом? Свяжитесь с нами!"
                        : "Biznesingizni to'liq nazorat qilmoqchimisiz? Biz bilan bog'laning!"
                    }}
                </p>

                <div class="grid sm:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/10">
                        <svg class="w-8 h-8 text-blue-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-white font-semibold mb-1">Email</p>
                        <p class="text-gray-400">info@biznespilot.uz</p>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/10">
                        <svg class="w-8 h-8 text-emerald-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <p class="text-white font-semibold mb-1">{{ $locale === 'ru' ? 'Телефон' : 'Telefon' }}</p>
                        <p class="text-gray-400">+998 50 504 86 68</p>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/10">
                        <svg class="w-8 h-8 text-violet-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="text-white font-semibold mb-1">{{ $locale === 'ru' ? 'Адрес' : 'Manzil' }}</p>
                        <p class="text-gray-400">{{ $locale === 'ru' ? "Ташкент, Узбекистан" : "Toshkent, O'zbekiston" }}</p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="https://t.me/biznespilot" target="_blank" class="inline-flex items-center px-8 py-4 bg-[#0088cc] hover:bg-[#0077b5] text-white font-semibold rounded-xl transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                        </svg>
                        Telegram
                    </a>
                    <a href="{{ route('landing') }}" class="inline-flex items-center px-8 py-4 bg-white text-gray-900 font-semibold rounded-xl hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        {{ $locale === 'ru' ? 'Главная' : 'Bosh sahifa' }}
                    </a>
                </div>
            </div>
        </section>
    </main>

    @include('landing.partials.footer')
@endsection
