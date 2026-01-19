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

            // Feature modal content - Professional marketing descriptions
            $featureDetails = [
                'chart' => [
                    'uz' => [
                        'title' => 'Marketing Avtomatlashtirish',
                        'subtitle' => 'Mijozlar o\'zi keladi — siz faqat sotasiz',
                        'description' => "Instagram, Facebook, Telegram va boshqa tarmoqlardan kelgan barcha so'rovlarni bitta joyda ko'ring. Qaysi reklama ishlayotganini, qaysi biri pulni behuda sarflayotganini tizim o'zi aniqlaydi va sizga aytadi.",
                        'benefits' => [
                            'Barcha reklamalar bitta ekranda ko\'rinadi',
                            'Qaysi reklama yaxshi ishlayotganini ko\'rsatadi',
                            'Qiziqish bildirgan mijozlarni avtomatik ajratadi',
                            'SMS va email avtomatik yuboriladi',
                            'Har bir so\'mga qancha mijoz kelganini ko\'rsatadi',
                            'Ikki xil reklamani solishtirib ko\'rish imkoni',
                        ],
                        'stats' => [
                            ['value' => '3x', 'label' => 'Ko\'proq mijoz'],
                            ['value' => '40%', 'label' => 'Kam xarajat'],
                            ['value' => '24/7', 'label' => 'Tinmay ishlaydi'],
                        ],
                    ],
                    'ru' => [
                        'title' => 'Автоматизация маркетинга',
                        'subtitle' => 'Клиенты приходят сами — вы только продаёте',
                        'description' => 'Все заявки из Instagram, Facebook, Telegram и других сетей видны в одном месте. Система сама определяет какая реклама работает, а какая тратит деньги впустую, и сообщает вам.',
                        'benefits' => [
                            'Вся реклама видна на одном экране',
                            'Показывает какая реклама работает лучше',
                            'Автоматически выделяет заинтересованных клиентов',
                            'SMS и email отправляются автоматически',
                            'Показывает сколько клиентов пришло на каждый сум',
                            'Можно сравнить два варианта рекламы',
                        ],
                        'stats' => [
                            ['value' => '3x', 'label' => 'Больше клиентов'],
                            ['value' => '40%', 'label' => 'Меньше затрат'],
                            ['value' => '24/7', 'label' => 'Работает постоянно'],
                        ],
                    ],
                ],
                'users' => [
                    'uz' => [
                        'title' => 'Sotuv va Mijozlar Bazasi',
                        'subtitle' => 'Har bir mijoz nazoratda — hech kim unutilmaydi',
                        'description' => "Har bir mijoz bilan qachon gaplashganingiz, nima kelishganingiz, keyingi qadamingiz — hammasi yozilgan. Sotuvchilaringiz kim bilan ishlayotganini, qaysi bosqichda turganini ko'rasiz. Tizim o'zi eslatadi: \"Bugun Ahmadga qo'ng'iroq qilish kerak\".",
                        'benefits' => [
                            'Barcha mijozlar va ularning holati ko\'rinadi',
                            'Qachon qo\'ng\'iroq qilish kerakligini eslatadi',
                            'Mijoz bilan barcha yozishmalar saqlanadi',
                            'Har bir sotuvchi qancha sotganini ko\'rsatadi',
                            'Keyingi oyda qancha sotuv bo\'lishini bashorat qiladi',
                            'WhatsApp va Telegram xabarlari ham shu yerda',
                        ],
                        'stats' => [
                            ['value' => '40%', 'label' => 'Ko\'proq sotuv'],
                            ['value' => '0', 'label' => 'Unutilgan mijoz'],
                            ['value' => '2x', 'label' => 'Tezroq sotish'],
                        ],
                    ],
                    'ru' => [
                        'title' => 'Продажи и База клиентов',
                        'subtitle' => 'Каждый клиент под контролем — никто не забыт',
                        'description' => 'Когда вы говорили с клиентом, о чём договорились, какой следующий шаг — всё записано. Видите с кем работают ваши продавцы и на каком этапе. Система сама напоминает: "Сегодня нужно позвонить Ахмаду".',
                        'benefits' => [
                            'Все клиенты и их статус видны',
                            'Напоминает когда нужно позвонить',
                            'Вся переписка с клиентом сохраняется',
                            'Показывает сколько продал каждый продавец',
                            'Прогнозирует продажи на следующий месяц',
                            'Сообщения WhatsApp и Telegram тоже здесь',
                        ],
                        'stats' => [
                            ['value' => '40%', 'label' => 'Больше продаж'],
                            ['value' => '0', 'label' => 'Забытых клиентов'],
                            ['value' => '2x', 'label' => 'Быстрее продажи'],
                        ],
                    ],
                ],
                'bot' => [
                    'uz' => [
                        'title' => 'Moliya Boshqaruvi',
                        'subtitle' => 'Pul qaerda — aniq ko\'rinadi',
                        'description' => "Biznesingizga qancha pul kirdi, qancha chiqdi, kimdan pul olish kerak, kimga qarzdorsiz — hammasi bitta joyda. Buxgalteringiz uchun tayyor hisobotlar. Oy oxirida bosh og'rig'i yo'q.",
                        'benefits' => [
                            'Kirim va chiqim avtomatik yoziladi',
                            'Kimdan pul olish kerak — ro\'yxati tayyor',
                            'Kimga qarzingiz bor — ko\'rinadi',
                            'Hisob-faktura bir tugma bilan yaratiladi',
                            'Click va Payme to\'lovlari avtomatik kiritiladi',
                            'Keyingi oyda qancha pul bo\'lishini ko\'rsatadi',
                        ],
                        'stats' => [
                            ['value' => '0', 'label' => 'Xatolik'],
                            ['value' => '80%', 'label' => 'Kam vaqt'],
                            ['value' => '100%', 'label' => 'Aniq hisobot'],
                        ],
                    ],
                    'ru' => [
                        'title' => 'Управление финансами',
                        'subtitle' => 'Где деньги — чётко видно',
                        'description' => 'Сколько денег пришло, сколько ушло, кто должен вам, кому должны вы — всё в одном месте. Готовые отчёты для бухгалтера. В конце месяца никакой головной боли.',
                        'benefits' => [
                            'Доходы и расходы записываются автоматически',
                            'Список кто должен вам — готов',
                            'Видно кому вы должны',
                            'Счёт создаётся одной кнопкой',
                            'Платежи Click и Payme заносятся автоматически',
                            'Показывает сколько денег будет в следующем месяце',
                        ],
                        'stats' => [
                            ['value' => '0', 'label' => 'Ошибок'],
                            ['value' => '80%', 'label' => 'Меньше времени'],
                            ['value' => '100%', 'label' => 'Точный отчёт'],
                        ],
                    ],
                ],
                'calendar' => [
                    'uz' => [
                        'title' => 'AI Yordamchi',
                        'subtitle' => 'Oddiy ishlarni tizim qiladi — siz muhim narsalar bilan shug\'ullaning',
                        'description' => "Sun'iy intellekt sizning yordamchingiz. U nima o'zgarganini, qayerda muammo borligini topadi va sizga aytadi. Oddiy, takroriy ishlarni o'zi qiladi. Siz esa muhim qarorlarga e'tibor bering.",
                        'benefits' => [
                            'Muhim narsalarni o\'zi eslatadi',
                            'Muammolarni erta aniqlaydi va xabar beradi',
                            'Sotuv qachon oshishi yoki tushishini aytadi',
                            'Hisobotlarni o\'zi tayyorlaydi',
                            'Mijozlarga avtomatik javob beradi',
                            'Kechayu kunduz, dam olishsiz ishlaydi',
                        ],
                        'stats' => [
                            ['value' => '60%', 'label' => 'Vaqt tejaladi'],
                            ['value' => '24/7', 'label' => 'Doim ishlaydi'],
                            ['value' => '3x', 'label' => 'Tez natija'],
                        ],
                    ],
                    'ru' => [
                        'title' => 'AI Помощник',
                        'subtitle' => 'Рутину делает система — вы занимаетесь важным',
                        'description' => 'Искусственный интеллект — ваш помощник. Он находит что изменилось, где проблема и сообщает вам. Простые повторяющиеся задачи делает сам. А вы фокусируйтесь на важных решениях.',
                        'benefits' => [
                            'Сам напоминает о важном',
                            'Рано находит проблемы и сообщает',
                            'Говорит когда продажи вырастут или упадут',
                            'Сам готовит отчёты',
                            'Автоматически отвечает клиентам',
                            'Работает днём и ночью, без отдыха',
                        ],
                        'stats' => [
                            ['value' => '60%', 'label' => 'Экономия времени'],
                            ['value' => '24/7', 'label' => 'Всегда работает'],
                            ['value' => '3x', 'label' => 'Быстрый результат'],
                        ],
                    ],
                ],
                'dashboard' => [
                    'uz' => [
                        'title' => 'Asosiy Ekran',
                        'subtitle' => 'Biznes holati bitta qarashda — tez qaror qabul qiling',
                        'description' => "Sotuv qancha, pul qancha, jamoa nima qilayotgani — hammasi bitta ekranda. Ma'lumotlar doimiy yangilanib turadi. Uyda bo'ling, yo'lda bo'ling — telefondan ham ko'ra olasiz.",
                        'benefits' => [
                            'Barcha muhim raqamlar bir joyda',
                            'Ma\'lumotlar har doim yangi',
                            'Telefonda ham ishlaydi',
                            'O\'zingizga kerakli ko\'rsatkichlarni tanlaysiz',
                            'Muammo bo\'lsa darhol xabar beradi',
                            'Hisobotni yuklab olish yoki ulashish mumkin',
                        ],
                        'stats' => [
                            ['value' => 'Jonli', 'label' => 'Ma\'lumot'],
                            ['value' => '360°', 'label' => 'To\'liq ko\'rinish'],
                            ['value' => '1', 'label' => 'Ekranda hammasi'],
                        ],
                    ],
                    'ru' => [
                        'title' => 'Главный экран',
                        'subtitle' => 'Состояние бизнеса одним взглядом — принимайте решения быстро',
                        'description' => 'Сколько продаж, сколько денег, что делает команда — всё на одном экране. Данные постоянно обновляются. Дома или в дороге — смотрите с телефона.',
                        'benefits' => [
                            'Все важные цифры в одном месте',
                            'Данные всегда свежие',
                            'Работает на телефоне',
                            'Выбираете нужные вам показатели',
                            'Сразу сообщает если проблема',
                            'Отчёт можно скачать или поделиться',
                        ],
                        'stats' => [
                            ['value' => 'Живые', 'label' => 'Данные'],
                            ['value' => '360°', 'label' => 'Полный обзор'],
                            ['value' => '1', 'label' => 'Экран для всего'],
                        ],
                    ],
                ],
                'team' => [
                    'uz' => [
                        'title' => 'Jamoa va HR Boshqaruvi',
                        'subtitle' => 'Kim nima qilayotgani aniq — jamoa tartibda',
                        'description' => "Xodimlaringiz qanday ishlayotganini kuzating: vazifalar, ish vaqti, natijalar. Yangi xodim qabul qilish, ta'tillar, ish haqi — HR bo'limining barcha ishlari ham shu yerda. Har bir xodim o'z vazifasini, muddatini ko'radi.",
                        'benefits' => [
                            'Har bir xodimga vazifa berish va kuzatish',
                            'Kim qancha ishladi — vaqt hisobi',
                            'Yangi xodim qabul qilish jarayoni',
                            'Ta\'til va kasallik varaqalari hisobi',
                            'Ish haqi hisoblash avtomatlashtirilgan',
                            'Xodimlar reytingi va mukofotlash tizimi',
                        ],
                        'stats' => [
                            ['value' => '2x', 'label' => 'Samaradorlik'],
                            ['value' => '100%', 'label' => 'Shaffoflik'],
                            ['value' => '0', 'label' => 'Yo\'qolgan vazifa'],
                        ],
                    ],
                    'ru' => [
                        'title' => 'Управление командой и HR',
                        'subtitle' => 'Ясно кто что делает — команда в порядке',
                        'description' => 'Следите как работают сотрудники: задачи, рабочее время, результаты. Приём новых сотрудников, отпуска, зарплата — все HR задачи тоже здесь. Каждый сотрудник видит свои задачи и сроки.',
                        'benefits' => [
                            'Назначать задачи и следить за каждым сотрудником',
                            'Сколько кто работал — учёт времени',
                            'Процесс приёма новых сотрудников',
                            'Учёт отпусков и больничных',
                            'Расчёт зарплаты автоматизирован',
                            'Рейтинг сотрудников и система поощрений',
                        ],
                        'stats' => [
                            ['value' => '2x', 'label' => 'Эффективность'],
                            ['value' => '100%', 'label' => 'Прозрачность'],
                            ['value' => '0', 'label' => 'Потерянных задач'],
                        ],
                    ],
                ],
            ];
            @endphp

            @foreach($translations['features']['items'] as $index => $feature)
                <div class="animate-on-scroll group" style="animation-delay: {{ $index * 0.1 }}s;">
                    <div class="relative h-full p-8 bg-white rounded-3xl border border-gray-100 hover:border-gray-200 transition-all duration-500 hover:shadow-2xl hover:shadow-gray-200/50 hover:-translate-y-2 cursor-pointer"
                         onclick="openFeatureModal('{{ $feature['icon'] }}')">
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

    <!-- Feature Modals -->
    @foreach(['chart', 'users', 'bot', 'calendar', 'dashboard', 'team'] as $featureKey)
    @php
        $modalData = $featureDetails[$featureKey][$locale === 'ru' ? 'ru' : 'uz'];
        $gradientClass = $bgColors[$featureKey];
        $textColorClass = $textColors[$featureKey];
    @endphp
    <div id="modal-{{ $featureKey }}" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeFeatureModal('{{ $featureKey }}')"></div>

        <!-- Modal Content -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-3xl transform overflow-hidden rounded-3xl bg-white shadow-2xl transition-all">
                <!-- Close button -->
                <button onclick="closeFeatureModal('{{ $featureKey }}')" class="absolute top-4 right-4 z-10 p-2 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors">
                    <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <!-- Header with gradient -->
                <div class="bg-gradient-to-r {{ $gradientClass }} p-8 text-white">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center">
                            <div class="w-10 h-10 text-white">
                                {!! $featureIcons[$featureKey] !!}
                            </div>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold">{{ $modalData['title'] }}</h3>
                            <p class="text-white/80">{{ $modalData['subtitle'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-8">
                    <p class="text-gray-600 text-lg mb-8 leading-relaxed">
                        {{ $modalData['description'] }}
                    </p>

                    <!-- Benefits -->
                    <div class="mb-8">
                        <h4 class="text-lg font-bold text-gray-900 mb-4">
                            {{ $locale === 'ru' ? 'Что вы получаете:' : 'Nimalarni olasiz:' }}
                        </h4>
                        <div class="grid sm:grid-cols-2 gap-3">
                            @foreach($modalData['benefits'] as $benefit)
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 {{ $textColorClass }} flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">{{ $benefit }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-4 mb-8">
                        @foreach($modalData['stats'] as $stat)
                        <div class="text-center p-4 bg-gray-50 rounded-2xl">
                            <div class="text-2xl font-bold {{ $textColorClass }}">{{ $stat['value'] }}</div>
                            <div class="text-sm text-gray-600">{{ $stat['label'] }}</div>
                        </div>
                        @endforeach
                    </div>

                    <!-- CTA -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('register') }}" class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r {{ $gradientClass }} text-white font-semibold rounded-xl hover:opacity-90 transition-opacity">
                            {{ $locale === 'ru' ? 'Попробовать бесплатно' : 'Bepul sinab ko\'rish' }}
                            <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                        <button onclick="closeFeatureModal('{{ $featureKey }}')" class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                            {{ $locale === 'ru' ? 'Закрыть' : 'Yopish' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</section>

<!-- Modal JavaScript -->
<script>
function openFeatureModal(featureKey) {
    const modal = document.getElementById('modal-' + featureKey);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        // Animate in
        setTimeout(() => {
            modal.querySelector('.relative.w-full').classList.add('animate-modal-in');
        }, 10);
    }
}

function closeFeatureModal(featureKey) {
    const modal = document.getElementById('modal-' + featureKey);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('[id^="modal-"]').forEach(modal => {
            if (!modal.classList.contains('hidden')) {
                const featureKey = modal.id.replace('modal-', '');
                closeFeatureModal(featureKey);
            }
        });
    }
});
</script>

<style>
@keyframes modalIn {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.animate-modal-in {
    animation: modalIn 0.3s ease-out forwards;
}
</style>
