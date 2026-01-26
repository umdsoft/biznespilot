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
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-4">
                    @if($locale === 'ru')
                        Политика конфиденциальности
                    @else
                        Maxfiylik siyosati
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
                        <span class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">1</span>
                        @if($locale === 'ru')
                            Введение
                        @else
                            Kirish
                        @endif
                    </h2>
                    @if($locale === 'ru')
                        <p class="text-gray-700 leading-relaxed mb-4">
                            BiznesPilot — единственная Бизнес Операционная Система Узбекистана ("мы", "Компания") — уважает вашу конфиденциальность и стремится защитить ваши персональные данные. Данная Политика конфиденциальности объясняет, какие данные мы собираем, как их используем и защищаем.
                        </p>
                        <p class="text-gray-700 leading-relaxed">
                            Данная политика разработана в соответствии с Законом Республики Узбекистан "О персональных данных" и международными стандартами защиты данных.
                        </p>
                    @else
                        <p class="text-gray-700 leading-relaxed mb-4">
                            BiznesPilot — O'zbekistonning yagona Biznes Operatsion Tizimi ("Biz", "Kompaniya") — Sizning maxfiyligingizni hurmat qiladi va shaxsiy ma'lumotlaringizni himoya qilishga sodiq. Ushbu Maxfiylik siyosati qanday ma'lumotlarni to'plashimiz, ulardan qanday foydalanishimiz va qanday himoya qilishimizni tushuntiradi.
                        </p>
                        <p class="text-gray-700 leading-relaxed">
                            Ushbu siyosat O'zbekiston Respublikasining "Shaxsiy ma'lumotlar to'g'risida"gi Qonuni va xalqaro ma'lumotlarni himoya qilish standartlariga muvofiq ishlab chiqilgan.
                        </p>
                    @endif
                </section>

                <!-- Data We Collect -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">2</span>
                        @if($locale === 'ru')
                            Собираемые данные
                        @else
                            To'planadigan ma'lumotlar
                        @endif
                    </h2>

                    <div class="space-y-6">
                        <div class="bg-gray-50 rounded-xl p-6">
                            <h3 class="font-semibold text-gray-900 mb-3">
                                @if($locale === 'ru')
                                    2.1. Данные, предоставленные вами:
                                @else
                                    2.1. Siz taqdim etgan ma'lumotlar:
                                @endif
                            </h3>
                            <ul class="space-y-2 text-gray-700">
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    @if($locale === 'ru')
                                        <span><strong>Идентификационные данные:</strong> имя, фамилия, логин, email, номер телефона</span>
                                    @else
                                        <span><strong>Identifikatsiya ma'lumotlari:</strong> ism, familiya, login, email, telefon raqami</span>
                                    @endif
                                </li>
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    @if($locale === 'ru')
                                        <span><strong>Бизнес-данные:</strong> название компании, отрасль, адрес, количество сотрудников</span>
                                    @else
                                        <span><strong>Biznes ma'lumotlari:</strong> kompaniya nomi, sohasi, manzili, xodimlar soni</span>
                                    @endif
                                </li>
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    @if($locale === 'ru')
                                        <span><strong>Финансовые данные:</strong> платежные данные, история транзакций</span>
                                    @else
                                        <span><strong>Moliyaviy ma'lumotlar:</strong> to'lov ma'lumotlari, tranzaksiya tarixi</span>
                                    @endif
                                </li>
                            </ul>
                        </div>

                        <div class="bg-gray-50 rounded-xl p-6">
                            <h3 class="font-semibold text-gray-900 mb-3">
                                @if($locale === 'ru')
                                    2.2. Автоматически собираемые данные:
                                @else
                                    2.2. Avtomatik to'planadigan ma'lumotlar:
                                @endif
                            </h3>
                            <ul class="space-y-2 text-gray-700">
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-emerald-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    @if($locale === 'ru')
                                        <span><strong>Данные устройства:</strong> IP-адрес, тип браузера, операционная система</span>
                                    @else
                                        <span><strong>Qurilma ma'lumotlari:</strong> IP manzil, brauzer turi, operatsion tizim</span>
                                    @endif
                                </li>
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-emerald-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    @if($locale === 'ru')
                                        <span><strong>Данные использования:</strong> время посещения, просмотренные страницы</span>
                                    @else
                                        <span><strong>Foydalanish ma'lumotlari:</strong> tashrif vaqti, ko'rilgan sahifalar</span>
                                    @endif
                                </li>
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-emerald-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    @if($locale === 'ru')
                                        <span><strong>Cookie и tracking:</strong> данные сессии, предпочтения</span>
                                    @else
                                        <span><strong>Cookie va tracking:</strong> sessiya ma'lumotlari, preferenslar</span>
                                    @endif
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- How We Use Data -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">3</span>
                        @if($locale === 'ru')
                            Использование данных
                        @else
                            Ma'lumotlardan foydalanish
                        @endif
                    </h2>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="bg-blue-50 rounded-xl p-5 border border-blue-100">
                            <h4 class="font-semibold text-blue-800 mb-2">
                                @if($locale === 'ru')
                                    Предоставление услуг
                                @else
                                    Xizmat ko'rsatish
                                @endif
                            </h4>
                            <ul class="text-blue-700 text-sm space-y-1">
                                @if($locale === 'ru')
                                    <li>• Создание и управление аккаунтом</li>
                                    <li>• Обеспечение функций платформы</li>
                                    <li>• Анализ с помощью Искусственного интеллекта</li>
                                @else
                                    <li>• Akkauntingizni yaratish va boshqarish</li>
                                    <li>• Platforma funksiyalarini ta'minlash</li>
                                    <li>• Sun'iy intellekt tahlillarini amalga oshirish</li>
                                @endif
                            </ul>
                        </div>

                        <div class="bg-emerald-50 rounded-xl p-5 border border-emerald-100">
                            <h4 class="font-semibold text-emerald-800 mb-2">
                                @if($locale === 'ru')
                                    Улучшение сервиса
                                @else
                                    Xizmatni yaxshilash
                                @endif
                            </h4>
                            <ul class="text-emerald-700 text-sm space-y-1">
                                @if($locale === 'ru')
                                    <li>• Совершенствование платформы</li>
                                    <li>• Разработка новых функций</li>
                                    <li>• Оптимизация пользовательского опыта</li>
                                @else
                                    <li>• Platformani takomillashtirish</li>
                                    <li>• Yangi funksiyalar ishlab chiqish</li>
                                    <li>• Foydalanuvchi tajribasini optimallashtirish</li>
                                @endif
                            </ul>
                        </div>

                        <div class="bg-violet-50 rounded-xl p-5 border border-violet-100">
                            <h4 class="font-semibold text-violet-800 mb-2">
                                @if($locale === 'ru')
                                    Коммуникация
                                @else
                                    Kommunikatsiya
                                @endif
                            </h4>
                            <ul class="text-violet-700 text-sm space-y-1">
                                @if($locale === 'ru')
                                    <li>• Важные уведомления</li>
                                    <li>• Маркетинговые сообщения (с согласия)</li>
                                    <li>• Предупреждения безопасности</li>
                                @else
                                    <li>• Muhim bildirishnomalar yuborish</li>
                                    <li>• Marketing xabarlari (roziligingiz bilan)</li>
                                    <li>• Xavfsizlik ogohlantirishlari</li>
                                @endif
                            </ul>
                        </div>

                        <div class="bg-amber-50 rounded-xl p-5 border border-amber-100">
                            <h4 class="font-semibold text-amber-800 mb-2">
                                @if($locale === 'ru')
                                    Юридические цели
                                @else
                                    Huquqiy maqsadlar
                                @endif
                            </h4>
                            <ul class="text-amber-700 text-sm space-y-1">
                                @if($locale === 'ru')
                                    <li>• Выполнение законодательных требований</li>
                                    <li>• Предотвращение мошенничества</li>
                                    <li>• Разрешение споров</li>
                                @else
                                    <li>• Qonuniy talablarni bajarish</li>
                                    <li>• Firibgarlikni oldini olish</li>
                                    <li>• Nizolarni hal qilish</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Data Security -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">4</span>
                        @if($locale === 'ru')
                            Безопасность данных
                        @else
                            Ma'lumotlar xavfsizligi
                        @endif
                    </h2>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="flex items-start p-4 bg-gray-50 rounded-xl">
                            <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">
                                    @if($locale === 'ru')
                                        SSL/TLS Шифрование
                                    @else
                                        SSL/TLS Shifrlash
                                    @endif
                                </h4>
                                <p class="text-sm text-gray-600">
                                    @if($locale === 'ru')
                                        Защита 256-bit шифрованием
                                    @else
                                        256-bit shifrlash bilan himoyalangan
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start p-4 bg-gray-50 rounded-xl">
                            <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">
                                    @if($locale === 'ru')
                                        Аудит безопасности
                                    @else
                                        Xavfsizlik auditi
                                    @endif
                                </h4>
                                <p class="text-sm text-gray-600">
                                    @if($locale === 'ru')
                                        Регулярные проверки и тесты
                                    @else
                                        Muntazam tekshiruvlar va testlar
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start p-4 bg-gray-50 rounded-xl">
                            <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">
                                    @if($locale === 'ru')
                                        Контроль доступа
                                    @else
                                        Kirish nazorati
                                    @endif
                                </h4>
                                <p class="text-sm text-gray-600">
                                    @if($locale === 'ru')
                                        2FA и ролевой доступ
                                    @else
                                        2FA va rol asosida kirish
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start p-4 bg-gray-50 rounded-xl">
                            <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">
                                    @if($locale === 'ru')
                                        Резервное копирование
                                    @else
                                        Zaxira nusxa
                                    @endif
                                </h4>
                                <p class="text-sm text-gray-600">
                                    @if($locale === 'ru')
                                        Ежедневный автоматический бэкап
                                    @else
                                        Kunlik avtomatik backup
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Local Data Storage Notice -->
                    <div class="mt-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-100">
                        <div class="flex items-start">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-1">
                                    @if($locale === 'ru')
                                        Данные хранятся в Узбекистане
                                    @else
                                        Ma'lumotlar O'zbekistonda saqlanadi
                                    @endif
                                </h4>
                                <p class="text-sm text-gray-600">
                                    @if($locale === 'ru')
                                        Все ваши данные хранятся на серверах в Узбекистане. Это обеспечивает соответствие местному законодательству и высокую скорость работы системы.
                                    @else
                                        Barcha ma'lumotlaringiz O'zbekistondagi serverlarda saqlanadi. Bu mahalliy qonunchilikkka muvofiqlikni va tizimning yuqori tezligini ta'minlaydi.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- User Rights -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">5</span>
                        @if($locale === 'ru')
                            Ваши права
                        @else
                            Sizning huquqlaringiz
                        @endif
                    </h2>

                    <div class="space-y-3">
                        @php
                            $rights = $locale === 'ru' ? [
                                ['Право доступа', 'Узнать, какие данные о вас собраны'],
                                ['Право на исправление', 'Обновить неверные или устаревшие данные'],
                                ['Право на удаление', 'Запросить удаление ваших данных'],
                                ['Право на ограничение', 'Ограничить обработку данных'],
                                ['Право на переносимость', 'Перенести данные в другой сервис'],
                                ['Право на возражение', 'Отказаться от маркетинговых сообщений']
                            ] : [
                                ['Kirish huquqi', "Siz haqingizda qanday ma'lumotlar to'plangani bilan tanishish"],
                                ['Tuzatish huquqi', "Noto'g'ri yoki eskirgan ma'lumotlarni yangilash"],
                                ["O'chirish huquqi", "Ma'lumotlaringizni o'chirishni so'rash"],
                                ['Cheklash huquqi', "Ma'lumotlarni qayta ishlashni cheklash"],
                                ["Ko'chirish huquqi", "Ma'lumotlaringizni boshqa xizmatga ko'chirish"],
                                ["E'tiroz bildirish", "Marketing xabarlaridan voz kechish"]
                            ];
                        @endphp

                        @foreach($rights as $index => $right)
                        <div class="flex items-start p-4 bg-gray-50 rounded-xl">
                            <span class="w-8 h-8 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-sm font-bold mr-4 flex-shrink-0">{{ $index + 1 }}</span>
                            <div>
                                <h4 class="font-semibold text-gray-900">{{ $right[0] }}</h4>
                                <p class="text-sm text-gray-600">{{ $right[1] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </section>

                <!-- AI Data Processing -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">6</span>
                        @if($locale === 'ru')
                            Искусственный интеллект и ваши данные
                        @else
                            Sun'iy intellekt va ma'lumotlaringiz
                        @endif
                    </h2>

                    <div class="bg-gradient-to-r from-violet-50 to-purple-50 rounded-xl p-6 border border-violet-100">
                        @if($locale === 'ru')
                            <p class="text-gray-700 leading-relaxed mb-4">
                                BiznesPilot использует Искусственный интеллект для анализа бизнес-данных и предоставления рекомендаций. Важно знать:
                            </p>
                            <ul class="space-y-2 text-gray-700">
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-violet-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Искусственный интеллект анализирует только данные вашего бизнеса и не передает их третьим лицам</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-violet-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Все рекомендации основаны только на ваших данных для вашего полного контроля над бизнесом</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-violet-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Вы можете отключить функции Искусственного интеллекта в настройках аккаунта</span>
                                </li>
                            </ul>
                        @else
                            <p class="text-gray-700 leading-relaxed mb-4">
                                BiznesPilot biznes ma'lumotlarini tahlil qilish va tavsiyalar berish uchun Sun'iy intellektdan foydalanadi. Bilishingiz muhim:
                            </p>
                            <ul class="space-y-2 text-gray-700">
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-violet-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Sun'iy intellekt faqat sizning biznesingiz ma'lumotlarini tahlil qiladi va uchinchi shaxslarga uzatmaydi</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-violet-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Barcha tavsiyalar faqat sizning ma'lumotlaringiz asosida — biznesni to'liq nazorat qilishingiz uchun</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-violet-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Sun'iy intellekt funksiyalarini akkaunt sozlamalarida o'chirishingiz mumkin</span>
                                </li>
                            </ul>
                        @endif
                    </div>
                </section>

                <!-- Contact -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">7</span>
                        @if($locale === 'ru')
                            Связаться с нами
                        @else
                            Bog'lanish
                        @endif
                    </h2>

                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-100">
                        <p class="text-gray-700 mb-4">
                            @if($locale === 'ru')
                                По вопросам конфиденциальности или для подачи запросов:
                            @else
                                Maxfiylik bo'yicha savollar yoki so'rovlar uchun:
                            @endif
                        </p>
                        <div class="space-y-2">
                            <p class="flex items-center text-gray-800">
                                <svg class="w-5 h-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <strong>Email:</strong>&nbsp; privacy@biznespilot.uz
                            </p>
                            <p class="flex items-center text-gray-800">
                                <svg class="w-5 h-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <strong>{{ $locale === 'ru' ? 'Телефон:' : 'Telefon:' }}</strong>&nbsp; +998 50 504 86 68
                            </p>
                            <p class="flex items-center text-gray-800">
                                <svg class="w-5 h-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <strong>{{ $locale === 'ru' ? 'Адрес:' : 'Manzil:' }}</strong>&nbsp; {{ $locale === 'ru' ? 'г. Ташкент, Узбекистан' : "Toshkent shahri, O'zbekiston" }}
                            </p>
                        </div>
                        <p class="mt-4 text-sm text-blue-700">
                            @if($locale === 'ru')
                                Запросы рассматриваются в течение 30 рабочих дней.
                            @else
                                So'rovlar 30 ish kuni ichida ko'rib chiqiladi.
                            @endif
                        </p>
                    </div>
                </section>
            </div>

            <!-- Back to Home -->
            <div class="text-center mt-8">
                <a href="{{ route('landing') }}" class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium">
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
