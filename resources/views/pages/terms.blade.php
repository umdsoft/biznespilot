@extends('landing.layouts.landing')

@php
    $locale = app()->getLocale();
@endphp

@section('content')
    @include('landing.partials.header')

    <main class="pt-24 pb-16 min-h-screen gradient-bg">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-4">
                    @if($locale === 'ru')
                        Условия использования
                    @else
                        Foydalanish shartlari
                    @endif
                </h1>
                <p class="text-gray-600">
                    @if($locale === 'ru')
                        Последнее обновление: {{ now()->format('d.m.Y') }}
                    @else
                        Oxirgi yangilanish: {{ now()->format('d.m.Y') }}
                    @endif
                </p>
            </div>

            <!-- Content -->
            <div class="bg-white rounded-3xl shadow-xl p-8 md:p-12 space-y-8">
                <!-- Introduction -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">1</span>
                        @if($locale === 'ru')
                            Введение
                        @else
                            Kirish
                        @endif
                    </h2>
                    @if($locale === 'ru')
                        <p class="text-gray-700 leading-relaxed mb-4">
                            Настоящие Условия использования (далее "Условия") регулируют правовые отношения между
                            BiznesPilot — единственной Бизнес Операционной Системой Узбекистана (далее "Платформа", "Сервис" или "Мы")
                            и Вами (далее "Пользователь" или "Вы").
                        </p>
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                            <p class="text-amber-800 text-sm">
                                <strong>Важно:</strong> Начиная использовать Платформу, Вы подтверждаете, что полностью прочитали
                                и согласились с данными Условиями.
                            </p>
                        </div>
                    @else
                        <p class="text-gray-700 leading-relaxed mb-4">
                            Ushbu Foydalanish shartlari (keyingi o'rinlarda "Shartlar") BiznesPilot — O'zbekistonning yagona Biznes Operatsion Tizimi
                            (keyingi o'rinlarda "Platforma", "Xizmat" yoki "Biz") bilan Siz (keyingi o'rinlarda "Foydalanuvchi" yoki "Siz")
                            o'rtasidagi huquqiy munosabatlarni tartibga soladi.
                        </p>
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                            <p class="text-amber-800 text-sm">
                                <strong>Muhim:</strong> Platformadan foydalanishni boshlash orqali Siz ushbu Shartlarni to'liq o'qib chiqqaningizni va
                                ularga rozilik bildirishingizni tasdiqlaysiz.
                            </p>
                        </div>
                    @endif
                </section>

                <!-- What is BOS -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">2</span>
                        @if($locale === 'ru')
                            Бизнес Операционная Система
                        @else
                            Biznes Operatsion Tizimi
                        @endif
                    </h2>

                    @if($locale === 'ru')
                        <p class="text-gray-700 mb-4">
                            BiznesPilot — это платформа, объединяющая все отделы бизнеса в одну систему, обеспечивая предпринимателям полный контроль.
                            Платформа состоит из 4 основных модулей:
                        </p>
                    @else
                        <p class="text-gray-700 mb-4">
                            BiznesPilot — bu biznesning barcha bo'limlarini bitta tizimga birlashtiruvchi va tadbirkorlarga to'liq nazorat imkonini beruvchi platforma.
                            Platforma 4 ta asosiy moduldan iborat:
                        </p>
                    @endif

                    <div class="grid sm:grid-cols-2 gap-4">
                        <!-- Marketing Module -->
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-5 border border-blue-100">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                                    </svg>
                                </div>
                                <h4 class="font-semibold text-gray-900">
                                    @if($locale === 'ru')
                                        Marketing Boshqaruvi
                                    @else
                                        Marketing Boshqaruvi
                                    @endif
                                </h4>
                            </div>
                            <p class="text-sm text-gray-600">
                                @if($locale === 'ru')
                                    Управление клиентами, реклама, аналитика с помощью Искусственного интеллекта
                                @else
                                    Mijozlarni boshqarish, reklama, Sun'iy intellekt tahlillari
                                @endif
                            </p>
                        </div>

                        <!-- Sales Module -->
                        <div class="bg-gradient-to-br from-emerald-50 to-green-50 rounded-xl p-5 border border-emerald-100">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <h4 class="font-semibold text-gray-900">
                                    @if($locale === 'ru')
                                        Sotuv va CRM
                                    @else
                                        Sotuv va CRM
                                    @endif
                                </h4>
                            </div>
                            <p class="text-sm text-gray-600">
                                @if($locale === 'ru')
                                    Управление продажами, клиентами и сделками в реальном времени
                                @else
                                    Sotuvlarni, mijozlarni va bitimlarni real vaqtda boshqarish
                                @endif
                            </p>
                        </div>

                        <!-- HR Module -->
                        <div class="bg-gradient-to-br from-violet-50 to-purple-50 rounded-xl p-5 border border-violet-100">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <h4 class="font-semibold text-gray-900">
                                    @if($locale === 'ru')
                                        HR Boshqaruvi
                                    @else
                                        HR Boshqaruvi
                                    @endif
                                </h4>
                            </div>
                            <p class="text-sm text-gray-600">
                                @if($locale === 'ru')
                                    Управление сотрудниками, задачами и заработной платой
                                @else
                                    Xodimlar, vazifalar va ish haqini boshqarish
                                @endif
                            </p>
                        </div>

                        <!-- Finance Module -->
                        <div class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-xl p-5 border border-amber-100">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <h4 class="font-semibold text-gray-900">
                                    @if($locale === 'ru')
                                        Moliya Boshqaruvi
                                    @else
                                        Moliya Boshqaruvi
                                    @endif
                                </h4>
                            </div>
                            <p class="text-sm text-gray-600">
                                @if($locale === 'ru')
                                    Доходы-расходы, долги, платежи и финансовые отчеты
                                @else
                                    Daromad-xarajatlar, qarzlar, to'lovlar va moliyaviy hisobotlar
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- AI Note -->
                    <div class="mt-4 bg-gradient-to-r from-indigo-50 to-violet-50 rounded-xl p-5 border border-indigo-100">
                        <div class="flex items-start">
                            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-1">
                                    @if($locale === 'ru')
                                        Искусственный интеллект
                                    @else
                                        Sun'iy intellekt
                                    @endif
                                </h4>
                                <p class="text-sm text-gray-600">
                                    @if($locale === 'ru')
                                        Все модули работают с помощью Искусственного интеллекта — он анализирует данные, находит проблемы заранее и дает рекомендации для вашего полного контроля над бизнесом.
                                    @else
                                        Barcha modullar Sun'iy intellekt yordamida ishlaydi — u ma'lumotlarni tahlil qiladi, muammolarni oldindan topadi va biznesni to'liq nazorat qilishingiz uchun tavsiyalar beradi.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Account Registration -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">3</span>
                        @if($locale === 'ru')
                            Аккаунт и регистрация
                        @else
                            Akkaunt va ro'yxatdan o'tish
                        @endif
                    </h2>

                    <div class="space-y-4">
                        <div class="bg-gray-50 rounded-xl p-5">
                            <h3 class="font-semibold text-gray-900 mb-3">
                                @if($locale === 'ru')
                                    3.1. Для использования Платформы Вы:
                                @else
                                    3.1. Platformadan foydalanish uchun Siz:
                                @endif
                            </h3>
                            @php
                                $requirements = $locale === 'ru' ? [
                                    'Должны быть не моложе 18 лет',
                                    'Должны предоставить достоверные данные',
                                    'Должны обеспечить безопасность аккаунта',
                                    'Должны хранить пароль в тайне'
                                ] : [
                                    "Kamida 18 yoshda bo'lishingiz kerak",
                                    "To'g'ri va aniq ma'lumotlarni taqdim etishingiz shart",
                                    "Akkauntingiz xavfsizligini ta'minlashingiz lozim",
                                    "Parolingizni maxfiy saqlashingiz kerak"
                                ];
                            @endphp
                            <ul class="space-y-2 text-gray-700">
                                @foreach($requirements as $req)
                                <li class="flex items-start">
                                    <span class="w-2 h-2 bg-violet-500 rounded-full mr-3 mt-2"></span>
                                    {{ $req }}
                                </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="bg-gray-50 rounded-xl p-5">
                            <h3 class="font-semibold text-gray-900 mb-3">
                                @if($locale === 'ru')
                                    3.2. Вы несете ответственность за:
                                @else
                                    3.2. Siz quyidagilarga mas'ulsiz:
                                @endif
                            </h3>
                            @php
                                $responsibilities = $locale === 'ru' ? [
                                    'Все действия, совершенные через ваш аккаунт',
                                    'Предотвращение несанкционированного доступа',
                                    'Немедленное уведомление о любых нарушениях безопасности'
                                ] : [
                                    "Akkauntingiz orqali amalga oshirilgan barcha harakatlar uchun",
                                    "Akkauntga ruxsatsiz kirishni oldini olish uchun",
                                    "Har qanday xavfsizlik buzilishi haqida bizni zudlik bilan xabardor qilish uchun"
                                ];
                            @endphp
                            <ul class="space-y-2 text-gray-700">
                                @foreach($responsibilities as $resp)
                                <li class="flex items-start">
                                    <span class="w-2 h-2 bg-red-500 rounded-full mr-3 mt-2"></span>
                                    {{ $resp }}
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- User Obligations -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">4</span>
                        @if($locale === 'ru')
                            Обязательства пользователя
                        @else
                            Foydalanuvchi majburiyatlari
                        @endif
                    </h2>
                    <p class="text-gray-700 mb-4">
                        @if($locale === 'ru')
                            Используя Платформу, Вы соглашаетесь НЕ совершать следующие действия:
                        @else
                            Platformadan foydalanishda Siz quyidagilarni bajarmaslikka rozilik bildirasiz:
                        @endif
                    </p>

                    <div class="bg-red-50 rounded-xl p-5 border border-red-100">
                        @php
                            $obligations = $locale === 'ru' ? [
                                "Использование в незаконных или вредоносных целях",
                                "Нарушение прав других пользователей",
                                "Распространение вирусов или вредоносного кода",
                                "Вмешательство в нормальную работу Платформы",
                                "Несанкционированный доступ к данным",
                                "Отправка спама или нежелательных сообщений",
                                "Распространение ложной информации",
                                "Нарушение прав интеллектуальной собственности"
                            ] : [
                                "Noqonuniy yoki zararli maqsadlarda foydalanish",
                                "Boshqa foydalanuvchilarning huquqlarini buzish",
                                "Viruslar yoki zararli kodlarni tarqatish",
                                "Platformaning normal ishlashiga xalaqit berish",
                                "Ruxsatsiz ma'lumotlarga kirish",
                                "Spam yoki istalmagan xabarlar yuborish",
                                "Soxta ma'lumotlarni tarqatish",
                                "Intellektual mulk huquqlarini buzish"
                            ];
                        @endphp
                        <div class="grid sm:grid-cols-2 gap-3">
                            @foreach($obligations as $obligation)
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-red-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-red-800 text-sm">{{ $obligation }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <!-- Intellectual Property -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">5</span>
                        @if($locale === 'ru')
                            Интеллектуальная собственность
                        @else
                            Intellektual mulk
                        @endif
                    </h2>

                    <div class="space-y-4">
                        <p class="text-gray-700">
                            @if($locale === 'ru')
                                <strong>5.1.</strong> Платформа и все её компоненты (программное обеспечение, дизайн,
                                логотипы, модели Искусственного интеллекта) являются исключительной собственностью BiznesPilot и защищены авторским правом.
                            @else
                                <strong>5.1.</strong> Platforma va uning barcha tarkibiy qismlari (dasturiy ta'minot, dizayn,
                                logotiplar, Sun'iy intellekt modellari) BiznesPilot ning mutlaq mulki hisoblanadi va mualliflik huquqi bilan himoyalangan.
                            @endif
                        </p>
                        <p class="text-gray-700">
                            @if($locale === 'ru')
                                <strong>5.2.</strong> Вы сохраняете полные права на данные, загруженные на Платформу,
                                но предоставляете нам право использовать их для оказания услуг.
                            @else
                                <strong>5.2.</strong> Siz Platformaga yuklagan ma'lumotlar ustidan to'liq huquqni saqlab qolasiz,
                                lekin bizga ularni xizmat ko'rsatish maqsadida ishlatish huquqini berasiz.
                            @endif
                        </p>
                    </div>
                </section>

                <!-- Service Availability -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">6</span>
                        @if($locale === 'ru')
                            Доступность сервиса
                        @else
                            Xizmat mavjudligi
                        @endif
                    </h2>

                    <p class="text-gray-700 mb-4">
                        @if($locale === 'ru')
                            Мы стремимся обеспечить бесперебойную работу Платформы, однако сервис может быть временно приостановлен в следующих случаях:
                        @else
                            Biz Platformaning uzluksiz ishlashini ta'minlashga harakat qilamiz, lekin quyidagi hollarda xizmat vaqtincha to'xtatilishi mumkin:
                        @endif
                    </p>

                    @php
                        $availabilityReasons = $locale === 'ru' ? [
                            ['icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'text' => 'Техническое обслуживание'],
                            ['icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'text' => 'Обновление системы'],
                            ['icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'text' => 'Технические неполадки'],
                            ['icon' => 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z', 'text' => 'Форс-мажорные обстоятельства']
                        ] : [
                            ['icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'text' => "Texnik xizmat ko'rsatish"],
                            ['icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'text' => 'Tizimni yangilash'],
                            ['icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'text' => 'Texnik nosozliklar'],
                            ['icon' => 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z', 'text' => 'Fors-major holatlari']
                        ];
                    @endphp

                    <div class="grid sm:grid-cols-2 gap-3">
                        @foreach($availabilityReasons as $reason)
                        <div class="flex items-center p-3 bg-amber-50 rounded-lg border border-amber-100">
                            <svg class="w-5 h-5 text-amber-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $reason['icon'] }}"/>
                            </svg>
                            <span class="text-amber-800 text-sm">{{ $reason['text'] }}</span>
                        </div>
                        @endforeach
                    </div>

                    <p class="text-gray-600 text-sm mt-4">
                        @if($locale === 'ru')
                            О плановых технических работах мы уведомляем минимум за 24 часа.
                        @else
                            Rejalashtirilgan texnik ishlar haqida kamida 24 soat oldin xabardor qilamiz.
                        @endif
                    </p>
                </section>

                <!-- Limitation of Liability -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">7</span>
                        @if($locale === 'ru')
                            Ограничение ответственности
                        @else
                            Javobgarlikni cheklash
                        @endif
                    </h2>

                    <div class="bg-gray-50 rounded-xl p-5 space-y-3">
                        <p class="text-gray-700">
                            @if($locale === 'ru')
                                <strong>7.1.</strong> BiznesPilot не несет ответственности за:
                            @else
                                <strong>7.1.</strong> BiznesPilot quyidagilar uchun javobgar emas:
                            @endif
                        </p>
                        @php
                            $liabilities = $locale === 'ru' ? [
                                'Убытки, возникшие в результате действий пользователя',
                                'Проблемы со сторонними сервисами',
                                'Неполадки интернет-соединения или устройства',
                                "Потери, связанные с бизнес-решениями"
                            ] : [
                                "Foydalanuvchi harakatlari natijasida yuzaga kelgan zararlar",
                                "Uchinchi tomon xizmatlari bilan bog'liq muammolar",
                                "Internet ulanishi yoki qurilma nosozliklari",
                                "Biznes qarorlari natijasida yuzaga kelgan yo'qotishlar"
                            ];
                        @endphp
                        <ul class="space-y-2 text-gray-700 ml-4">
                            @foreach($liabilities as $liability)
                            <li>• {{ $liability }}</li>
                            @endforeach
                        </ul>
                        <p class="text-gray-700">
                            @if($locale === 'ru')
                                <strong>7.2.</strong> Наша общая ответственность ограничена суммой подписки за последние 12 месяцев.
                            @else
                                <strong>7.2.</strong> Bizning umumiy javobgarligimiz Siz tomonidan to'langan oxirgi 12 oylik obuna summasi bilan cheklanadi.
                            @endif
                        </p>
                    </div>
                </section>

                <!-- Termination -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">8</span>
                        @if($locale === 'ru')
                            Прекращение действия
                        @else
                            Shartlarni bekor qilish
                        @endif
                    </h2>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="bg-emerald-50 rounded-xl p-5 border border-emerald-100">
                            <h4 class="font-semibold text-emerald-800 mb-2">
                                @if($locale === 'ru')
                                    Ваше право
                                @else
                                    Sizning huquqingiz
                                @endif
                            </h4>
                            <p class="text-emerald-700 text-sm">
                                @if($locale === 'ru')
                                    Вы можете удалить аккаунт в любое время.
                                @else
                                    Siz istalgan vaqtda akkauntingizni o'chirishingiz mumkin.
                                @endif
                            </p>
                        </div>

                        <div class="bg-red-50 rounded-xl p-5 border border-red-100">
                            <h4 class="font-semibold text-red-800 mb-2">
                                @if($locale === 'ru')
                                    Наше право
                                @else
                                    Bizning huquqimiz
                                @endif
                            </h4>
                            <p class="text-red-700 text-sm mb-2">
                                @if($locale === 'ru')
                                    Мы можем приостановить ваш аккаунт в следующих случаях:
                                @else
                                    Quyidagi hollarda akkauntingizni to'xtatishimiz mumkin:
                                @endif
                            </p>
                            @php
                                $terminationReasons = $locale === 'ru' ? [
                                    'При нарушении Условий',
                                    'При обнаружении незаконной деятельности',
                                    'При неоплате'
                                ] : [
                                    'Shartlarni buzganingizda',
                                    'Noqonuniy faoliyat aniqlanganda',
                                    "To'lovlarni amalga oshirmaganda"
                                ];
                            @endphp
                            <ul class="text-red-700 text-sm space-y-1">
                                @foreach($terminationReasons as $reason)
                                <li>• {{ $reason }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Dispute Resolution -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">9</span>
                        @if($locale === 'ru')
                            Разрешение споров
                        @else
                            Nizolarni hal qilish
                        @endif
                    </h2>

                    <div class="space-y-3">
                        @if($locale === 'ru')
                            <p class="text-gray-700"><strong>9.1.</strong> Все споры решаются в первую очередь путем переговоров.</p>
                            <p class="text-gray-700"><strong>9.2.</strong> Если переговоры не дали результата, споры рассматриваются в судебном порядке в соответствии с законодательством Республики Узбекистан.</p>
                            <p class="text-gray-700"><strong>9.3.</strong> Место судебного разбирательства: город Ташкент.</p>
                        @else
                            <p class="text-gray-700"><strong>9.1.</strong> Barcha nizolar avvalo muzokaralar yo'li bilan hal qilinadi.</p>
                            <p class="text-gray-700"><strong>9.2.</strong> Muzokaralar natija bermaganda, nizolar O'zbekiston Respublikasi qonunchiligiga muvofiq sud tartibida ko'rib chiqiladi.</p>
                            <p class="text-gray-700"><strong>9.3.</strong> Sud joylashuvi: Toshkent shahri.</p>
                        @endif
                    </div>
                </section>

                <!-- Contact -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">10</span>
                        @if($locale === 'ru')
                            Связаться с нами
                        @else
                            Bog'lanish
                        @endif
                    </h2>

                    <div class="bg-gradient-to-br from-violet-50 to-purple-50 rounded-xl p-6 border border-violet-100">
                        <p class="text-gray-700 mb-4">
                            @if($locale === 'ru')
                                По вопросам или предложениям обращайтесь:
                            @else
                                Savollar yoki takliflar bo'lsa, biz bilan bog'laning:
                            @endif
                        </p>
                        <div class="space-y-2">
                            <p class="flex items-center text-gray-800">
                                <svg class="w-5 h-5 text-violet-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <strong>{{ $locale === 'ru' ? 'Компания:' : 'Kompaniya:' }}</strong>&nbsp; BiznesPilot
                            </p>
                            <p class="flex items-center text-gray-800">
                                <svg class="w-5 h-5 text-violet-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <strong>Email:</strong>&nbsp; support@biznespilot.uz
                            </p>
                            <p class="flex items-center text-gray-800">
                                <svg class="w-5 h-5 text-violet-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <strong>{{ $locale === 'ru' ? 'Телефон:' : 'Telefon:' }}</strong>&nbsp; +998 50 504 86 68
                            </p>
                            <p class="flex items-center text-gray-800">
                                <svg class="w-5 h-5 text-violet-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <strong>{{ $locale === 'ru' ? 'Часы работы:' : 'Ish vaqti:' }}</strong>&nbsp; {{ $locale === 'ru' ? 'Понедельник - Пятница, 09:00 - 18:00' : 'Dushanba - Juma, 09:00 - 18:00' }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Final Note -->
                <div class="bg-gray-100 rounded-xl p-5 text-center">
                    <p class="text-gray-600 text-sm">
                        @if($locale === 'ru')
                            Настоящие Условия регулируются законодательством Республики Узбекистан и
                            представляют собой полное соглашение между Вами и BiznesPilot.
                        @else
                            Ushbu Shartlar O'zbekiston Respublikasi qonunchiligiga bo'ysunadi va
                            Siz va BiznesPilot o'rtasidagi to'liq kelishuvni ifodalaydi.
                        @endif
                    </p>
                </div>
            </div>

            <!-- Back to Home -->
            <div class="text-center mt-8">
                <a href="{{ route('landing') }}" class="inline-flex items-center text-violet-600 hover:text-violet-700 font-medium">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    @if($locale === 'ru')
                        Вернуться на главную
                    @else
                        Bosh sahifaga qaytish
                    @endif
                </a>
            </div>
        </div>
    </main>

    @include('landing.partials.footer')
@endsection
