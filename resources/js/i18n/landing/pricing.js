export default {
    'uz-latn': {
        // ==================== HERO SECTION ====================
        hero: {
            social_proof: '500+ kompaniya bizga ishonadi',
            title_before: 'Biznesingiz uchun ',
            title_highlight: "to'g'ri tarif",
            title_after: 'ni tanlang',
            subtitle: "Yashirin to'lovlar yo'q. Istalgan vaqt bekor qilish mumkin.",
            subtitle_highlight: '14 kun bepul sinab ko\'ring!',
            billing_monthly: "Oylik to'lov",
            billing_yearly: "Yillik to'lov",
            badge_no_card: 'Karta talab qilinmaydi',
            badge_guarantee: '30 kunlik kafolat',
            badge_support: '24/7 yordam',
        },

        // ==================== PRICE LABELS ====================
        price: {
            per_month: "so'm/oy",
            yearly_label: 'Yillik:',
            yearly_suffix: "so'm",
            yearly_savings: "tejaysiz!",
            per_day: "Kuniga atigi",
            per_employee: "Har bir xodim uchun faqat",
        },

        // ==================== PLAN CARDS ====================
        plans: {
            start: {
                name: 'Start',
                description: "Sinab ko'rish uchun",
                badge: 'Faqat 2 xodim va 1 filial',
                button: 'Boshlash',
            },
            standard: {
                name: 'Standard',
                description: "O'sish uchun",
                decoy_hint: "+200,000 so'mga 2x ko'p imkoniyat",
                button: 'Tanlash',
            },
            business: {
                name: 'Business',
                description: "Eng ko'p tanlanadi",
                popular_badge: 'ENG XARIDORGIR',
                button: '14 Kun BEPUL Boshlash',
                card_note: 'Karta talab qilinmaydi. Xavfsiz.',
            },
            premium: {
                name: 'Premium',
                description: 'Katta jamoalar uchun',
                vip_badge: 'VIP PREMIUM',
                button: 'Premium olish',
                consult_link: 'yoki konsultatsiya olish',
                custom_hint: 'Maxsus yechim kerakmi?',
            },
        },

        // ==================== FEATURE LISTS (Plan cards) ====================
        features: {
            start: [
                '2 ta xodim',
                '1 ta filial',
                'Instagram + Telegram bot',
                '500 ta lid/oy',
                '60 daqiqa Call Center AI',
                'Asosiy CRM',
            ],
            standard: [
                '5 ta xodim',
                '1 ta filial',
                'Flow Builder (Vizual)',
                '2,000 ta lid/oy',
                '150 daqiqa Call Center AI',
                'HR vazifalar',
            ],
            business: [
                { text: '10 ta xodim', hot: false },
                { text: '2 ta filial', hot: false },
                { text: 'HR Bot + Marketing ROI', hot: true },
                { text: '10,000 ta lid/oy', hot: true },
                { text: '400 daqiqa Call Center AI', hot: false },
                { text: 'Flow Builder (Vizual)', hot: false },
                { text: '3 ta Instagram akkaunt', hot: true },
                { text: '8 soat ichida javob', hot: false },
            ],
            premium: [
                '15 ta xodim',
                '5 ta filial',
                'AI Bot + Anti-Fraud SMS',
                'Cheksiz lid',
                '1,000 daqiqa Call Center AI',
                'Shaxsiy menejer',
                '10 ta Instagram akkaunt',
                '2 soat ichida javob',
            ],
        },

        // ==================== COMPARISON STRIP ====================
        comparison_strip: {
            start: 'Start: 2 xodim',
            standard: 'Standard: 5 xodim',
            business: 'Business: 10 xodim + HR Bot',
            premium: 'Premium: 15 xodim + Anti-Fraud',
        },

        // ==================== GUARANTEE SECTION ====================
        guarantee: {
            title: '30 Kunlik Pulni Qaytarish Kafolati',
            text_before: 'Agar BiznesPilot sizga mos kelmasa — ',
            text_bold: '30 kun ichida to\'liq pulni qaytaramiz',
            text_after: '. Hech qanday savol yo\'q, hech qanday murakkablik yo\'q. Shunchaki Telegram orqali yozing — xolos.',
            badge_no_conditions: 'Shart-sharoitsiz',
            badge_24h: '24 soat ichida qaytarish',
            badge_telegram: 'Telegram orqali ariza',
        },

        // ==================== COMPARISON TABLE SECTION ====================
        comparison: {
            badge: 'Batafsil taqqoslash',
            title: 'Barcha imkoniyatlarni solishtiring',
            subtitle: 'Har bir tarifda nimalar bor — batafsil jadval',
            cta_button: 'Business tarifini 14 kun BEPUL sinash',
        },

        // ==================== SOCIAL PROOF / TESTIMONIALS ====================
        testimonials: {
            badge: 'Muvaffaqiyatli mijozlar',
            title: 'Ular nima uchun Business tarifini tanladi?',
            user_badge: 'Business tarifi foydalanuvchisi',
            items: [
                {
                    text: 'Business tarifida HR Bot bilan yangi xodimlarni 50% tezroq topamiz. Vaqtimiz tejaldi, sifat oshdi!',
                    name: 'Jasur Rahimov',
                    role: 'HR Manager',
                },
                {
                    text: 'Instagram so\'rovlarini 3x tezroq qayta ishlay boshladik. 10 ta xodim uchun Business tarifi eng ideal narx!',
                    name: 'Aziz Soliyev',
                    role: 'CEO',
                },
                {
                    text: 'Call Center AI juda foydali! 400 daqiqa har bir qo\'ng\'iroqni tahlil qilish uchun yetarli.',
                    name: 'Nodira Karimova',
                    role: 'Marketing Director',
                },
            ],
        },

        // ==================== FAQ SECTION ====================
        faq: {
            badge: 'Savollar',
            title: "Ko'p so'raladigan savollar",
            items: [
                {
                    question: '14 kunlik bepul sinov davri qanday ishlaydi?',
                    answer: 'Ro\'yxatdan o\'tishingiz bilanoq 14 kun bepul sinov boshlanadi. Karta talab qilinmaydi. Sinov tugagach, davom etishni xohlasangiz to\'lovni amalga oshirasiz. Xohlamasangiz — hech narsa to\'lamaysiz.',
                },
                {
                    question: '30 kunlik pulni qaytarish kafolati haqiqatmi?',
                    answer: 'Ha, 100% haqiqat. To\'lovdan keyin 30 kun ichida tizim sizga mos kelmasa — Telegram orqali yozing va biz 24 soat ichida to\'liq pulni qaytaramiz. Hech qanday savol yo\'q.',
                },
                {
                    question: "Tarif o'zgartirsa bo'ladimi?",
                    answer: 'Istalgan vaqt yuqoriroq tarifga o\'tishingiz mumkin. To\'langan summa proporsional hisoblanadi. Pastroq tarifga o\'tish esa keyingi to\'lov davridan boshlab amalga oshadi.',
                },
                {
                    question: "To'lov turlari qanday?",
                    answer: 'Click, Payme, bank o\'tkazmasi va naqd to\'lov qabul qilamiz. Korporativ mijozlar uchun shartnoma va schet-faktura tayyorlaymiz.',
                },
                {
                    question: "Botni o'zim sozlay olamanmi?",
                    answer: 'Ha, albatta! BiznesPilot\'da oddiy drag-and-drop Flow Builder mavjud. Hech qanday dasturlash bilimi talab qilinmaydi. Video qo\'llanmalar va Telegram support ham bor.',
                },
                {
                    question: "Ma'lumotlarim xavfsizmi?",
                    answer: 'Ha, biz SSL shifrlash, kunlik backup va serverlarimiz Yevropa data-centerlarida joylashgan. GDPR talablariga muvofiq ishlaymiz.',
                },
                {
                    question: "Call Center AI daqiqalari tugasa nima bo'ladi?",
                    answer: 'Daqiqalar tugagandan so\'ng, qo\'shimcha daqiqalar sotib olishingiz yoki keyingi oyga kutishingiz mumkin — limitlar avtomatik yangilanadi.',
                },
                {
                    question: 'Qaysi tarifni tanlashim kerak?',
                    answer: 'Ko\'pchilik mijozlarimiz Business tarifini tanlaydi — 10 ta xodim, HR Bot, Marketing ROI va 10,000 ta lid imkoniyati ko\'p bizneslar uchun ideal. Kichik jamoa bo\'lsa Start, katta jamoa uchun Premium tavsiya etamiz.',
                },
            ],
        },

        // ==================== FINAL CTA SECTION ====================
        cta: {
            live_badge: 'Hozir 500+ kompaniya foydalanmoqda',
            title: 'Hali ham ikkilanayapsizmi?',
            title_highlight: '14 kun bepul sinab ko\'ring',
            subtitle: "Karta talab qilinmaydi. Xavf yo'q. 30 kunlik pulni qaytarish kafolati.",
            button_primary: 'Business tarifini boshlash',
            button_secondary: 'Konsultatsiya olish',
            trust_guarantee: '30 kunlik kafolat',
            trust_payment: 'Click & Payme',
            trust_support: '24/7 yordam',
        },

        // ==================== COMPARISON TABLE COMPONENT ====================
        table: {
            feature_col: 'Imkoniyat',
            price_suffix: "so'm",
            sections: {
                basic_limits: 'Asosiy limitlar',
                bot_channels: 'Bot va kanallar',
                ai_features: 'AI imkoniyatlari',
                additional: "Qo'shimcha funksiyalar",
                included_all: 'Barcha tariflarda mavjud',
                support: 'Texnik yordam',
            },
            // Basic limits
            basic: {
                users: 'Foydalanuvchilar soni',
                branches: 'Filiallar soni',
                monthly_leads: 'Oylik lidlar',
                storage: 'Saqlash hajmi',
            },
            // Bot & channels
            bots: {
                instagram_accounts: 'Instagram akkauntlar',
                chatbot_channels: 'Chatbot kanallari',
                telegram_bots: 'Telegram botlar',
            },
            // AI features
            ai: {
                call_analysis: "Qo'ng'iroqlar AI tahlili",
                extra_minute_price: "Qo'shimcha daqiqa narxi",
                ai_requests: "AI so'rovlar",
            },
            // Additional features
            additional: {
                hr_tasks: 'HR vazifalar',
                hr_bot: 'Ishga olish boti (HR Bot)',
                anti_fraud: 'SMS ogohlantirish (Anti-fraud)',
            },
            // Included in all
            included: {
                instagram_facebook: 'Instagram/Facebook integratsiya',
                flow_builder: 'Vizual voronka (Flow Builder)',
                marketing_roi: 'Marketing ROI hisoboti',
                crm: 'CRM va Lidlar boshqaruvi',
                kanban: 'Kanban doska',
            },
            // Support
            support: {
                telegram: 'Telegram support',
                response_time: 'Javob vaqti',
                video_guides: "Video qo'llanmalar",
                onboarding: 'Onboarding yordam',
                personal_manager: 'Shaxsiy menejer',
            },
            // Values (text-based cell values)
            values: {
                pcs_2: '2 ta',
                pcs_3: '3 ta',
                pcs_5: '5 ta',
                pcs_10: '10 ta',
                pcs_15: '15 ta',
                pcs_20: '20 ta',
                pcs_1: '1 ta',
                leads_500: '500 ta',
                leads_2000: '2,000 ta',
                leads_10000: '10,000 ta',
                unlimited: 'Cheksiz',
                storage_500mb: '500 MB',
                storage_1gb: '1 GB',
                storage_5gb: '5 GB',
                storage_50gb: '50 GB',
                min_60: '60 daq',
                min_150: '150 daq',
                min_400: '400 daq',
                min_1000: '1,000 daq',
                price_500: "500 so'm",
                price_450: "450 so'm",
                price_400: "400 so'm",
                price_300: "300 so'm",
                requests_500: '500 ta',
                requests_2000: '2,000 ta',
                requests_10000: '10,000 ta',
                requests_50000: '50,000 ta',
                hours_24: '24 soat',
                hours_12: '12 soat',
                hours_8: '8 soat',
                hours_2: '2 soat',
                personal: 'Shaxsiy',
            },
        },
    },

    // ======================== RUSSIAN ========================
    ru: {
        // ==================== HERO SECTION ====================
        hero: {
            social_proof: '500+ компаний доверяют нам',
            title_before: 'Выберите ',
            title_highlight: 'подходящий тариф',
            title_after: ' для вашего бизнеса',
            subtitle: 'Без скрытых платежей. Отмена в любое время.',
            subtitle_highlight: '14 дней бесплатно!',
            billing_monthly: 'Ежемесячная оплата',
            billing_yearly: 'Годовая оплата',
            badge_no_card: 'Карта не требуется',
            badge_guarantee: '30-дневная гарантия',
            badge_support: 'Поддержка 24/7',
        },

        // ==================== PRICE LABELS ====================
        price: {
            per_month: 'сум/мес',
            yearly_label: 'Годовая:',
            yearly_suffix: 'сум',
            yearly_savings: 'экономия!',
            per_day: 'Всего в день',
            per_employee: 'За каждого сотрудника всего',
        },

        // ==================== PLAN CARDS ====================
        plans: {
            start: {
                name: 'Start',
                description: 'Для пробы',
                badge: 'Только 2 сотрудника и 1 филиал',
                button: 'Начать',
            },
            standard: {
                name: 'Standard',
                description: 'Для роста',
                decoy_hint: '+200 000 сум — в 2 раза больше возможностей',
                button: 'Выбрать',
            },
            business: {
                name: 'Business',
                description: 'Самый популярный',
                popular_badge: 'САМЫЙ ПОПУЛЯРНЫЙ',
                button: '14 дней БЕСПЛАТНО',
                card_note: 'Карта не требуется. Безопасно.',
            },
            premium: {
                name: 'Premium',
                description: 'Для больших команд',
                vip_badge: 'VIP PREMIUM',
                button: 'Получить Premium',
                consult_link: 'или получить консультацию',
                custom_hint: 'Нужно индивидуальное решение?',
            },
        },

        // ==================== FEATURE LISTS (Plan cards) ====================
        features: {
            start: [
                '2 сотрудника',
                '1 филиал',
                'Instagram + Telegram бот',
                '500 лидов/мес',
                '60 минут Call Center AI',
                'Базовая CRM',
            ],
            standard: [
                '5 сотрудников',
                '1 филиал',
                'Flow Builder (Визуальный)',
                '2 000 лидов/мес',
                '150 минут Call Center AI',
                'HR задачи',
            ],
            business: [
                { text: '10 сотрудников', hot: false },
                { text: '2 филиала', hot: false },
                { text: 'HR Бот + Marketing ROI', hot: true },
                { text: '10 000 лидов/мес', hot: true },
                { text: '400 минут Call Center AI', hot: false },
                { text: 'Flow Builder (Визуальный)', hot: false },
                { text: '3 аккаунта Instagram', hot: true },
                { text: 'Ответ в течение 8 часов', hot: false },
            ],
            premium: [
                '15 сотрудников',
                '5 филиалов',
                'AI Бот + Anti-Fraud SMS',
                'Безлимитные лиды',
                '1 000 минут Call Center AI',
                'Персональный менеджер',
                '10 аккаунтов Instagram',
                'Ответ в течение 2 часов',
            ],
        },

        // ==================== COMPARISON STRIP ====================
        comparison_strip: {
            start: 'Start: 2 сотрудника',
            standard: 'Standard: 5 сотрудников',
            business: 'Business: 10 сотрудников + HR Бот',
            premium: 'Premium: 15 сотрудников + Anti-Fraud',
        },

        // ==================== GUARANTEE SECTION ====================
        guarantee: {
            title: '30-дневная гарантия возврата денег',
            text_before: 'Если BiznesPilot вам не подойдёт — ',
            text_bold: 'мы вернём полную сумму в течение 30 дней',
            text_after: '. Никаких вопросов, никаких сложностей. Просто напишите нам в Telegram — и всё.',
            badge_no_conditions: 'Без условий',
            badge_24h: 'Возврат в течение 24 часов',
            badge_telegram: 'Заявка через Telegram',
        },

        // ==================== COMPARISON TABLE SECTION ====================
        comparison: {
            badge: 'Подробное сравнение',
            title: 'Сравните все возможности',
            subtitle: 'Что входит в каждый тариф — подробная таблица',
            cta_button: 'Попробовать Business 14 дней БЕСПЛАТНО',
        },

        // ==================== SOCIAL PROOF / TESTIMONIALS ====================
        testimonials: {
            badge: 'Успешные клиенты',
            title: 'Почему они выбрали тариф Business?',
            user_badge: 'Пользователь тарифа Business',
            items: [
                {
                    text: 'С HR Ботом в тарифе Business мы находим новых сотрудников на 50% быстрее. Экономим время, качество выросло!',
                    name: 'Жасур Рахимов',
                    role: 'HR-менеджер',
                },
                {
                    text: 'Начали обрабатывать запросы из Instagram в 3 раза быстрее. Для 10 сотрудников тариф Business — идеальная цена!',
                    name: 'Азиз Солиев',
                    role: 'Генеральный директор',
                },
                {
                    text: 'Call Center AI — невероятно полезный инструмент! 400 минут хватает для анализа каждого звонка.',
                    name: 'Нодира Каримова',
                    role: 'Директор по маркетингу',
                },
            ],
        },

        // ==================== FAQ SECTION ====================
        faq: {
            badge: 'Вопросы',
            title: 'Часто задаваемые вопросы',
            items: [
                {
                    question: 'Как работает 14-дневный бесплатный пробный период?',
                    answer: 'Сразу после регистрации начинается 14-дневный бесплатный период. Карта не требуется. По окончании пробного периода, если хотите продолжить — оплачиваете подписку. Не хотите — ничего не платите.',
                },
                {
                    question: '30-дневная гарантия возврата — это правда?',
                    answer: 'Да, на 100%. Если в течение 30 дней после оплаты система вам не подойдёт — напишите в Telegram, и мы вернём полную сумму в течение 24 часов. Никаких вопросов.',
                },
                {
                    question: 'Можно ли сменить тариф?',
                    answer: 'Вы можете перейти на более высокий тариф в любое время. Оплаченная сумма пересчитывается пропорционально. Переход на более низкий тариф вступает в силу со следующего платёжного периода.',
                },
                {
                    question: 'Какие способы оплаты доступны?',
                    answer: 'Мы принимаем Click, Payme, банковский перевод и наличную оплату. Для корпоративных клиентов оформляем договор и счёт-фактуру.',
                },
                {
                    question: 'Могу ли я сам настроить бота?',
                    answer: 'Конечно! В BiznesPilot есть простой drag-and-drop Flow Builder. Никаких навыков программирования не требуется. Также доступны видеоинструкции и поддержка в Telegram.',
                },
                {
                    question: 'Мои данные в безопасности?',
                    answer: 'Да, мы используем SSL-шифрование, ежедневное резервное копирование, а наши серверы расположены в европейских дата-центрах. Работаем в соответствии с требованиями GDPR.',
                },
                {
                    question: 'Что произойдёт, когда закончатся минуты Call Center AI?',
                    answer: 'После исчерпания минут вы можете докупить дополнительные или дождаться следующего месяца — лимиты обновляются автоматически.',
                },
                {
                    question: 'Какой тариф мне выбрать?',
                    answer: 'Большинство наших клиентов выбирают тариф Business — 10 сотрудников, HR Бот, Marketing ROI и 10 000 лидов идеально подходят для большинства бизнесов. Для маленькой команды подойдёт Start, для большой — рекомендуем Premium.',
                },
            ],
        },

        // ==================== FINAL CTA SECTION ====================
        cta: {
            live_badge: 'Сейчас 500+ компаний используют платформу',
            title: 'Всё ещё сомневаетесь?',
            title_highlight: 'Попробуйте 14 дней бесплатно',
            subtitle: 'Карта не требуется. Без рисков. 30-дневная гарантия возврата денег.',
            button_primary: 'Начать тариф Business',
            button_secondary: 'Получить консультацию',
            trust_guarantee: '30-дневная гарантия',
            trust_payment: 'Click & Payme',
            trust_support: 'Поддержка 24/7',
        },

        // ==================== COMPARISON TABLE COMPONENT ====================
        table: {
            feature_col: 'Функция',
            price_suffix: 'сум',
            sections: {
                basic_limits: 'Основные лимиты',
                bot_channels: 'Боты и каналы',
                ai_features: 'AI возможности',
                additional: 'Дополнительные функции',
                included_all: 'Доступно во всех тарифах',
                support: 'Техническая поддержка',
            },
            // Basic limits
            basic: {
                users: 'Количество пользователей',
                branches: 'Количество филиалов',
                monthly_leads: 'Лиды в месяц',
                storage: 'Объём хранилища',
            },
            // Bot & channels
            bots: {
                instagram_accounts: 'Аккаунты Instagram',
                chatbot_channels: 'Каналы чат-бота',
                telegram_bots: 'Telegram боты',
            },
            // AI features
            ai: {
                call_analysis: 'AI-анализ звонков',
                extra_minute_price: 'Стоимость доп. минуты',
                ai_requests: 'AI-запросы',
            },
            // Additional features
            additional: {
                hr_tasks: 'HR задачи',
                hr_bot: 'Бот для найма (HR Бот)',
                anti_fraud: 'SMS-уведомления (Anti-fraud)',
            },
            // Included in all
            included: {
                instagram_facebook: 'Интеграция Instagram/Facebook',
                flow_builder: 'Визуальная воронка (Flow Builder)',
                marketing_roi: 'Отчёт Marketing ROI',
                crm: 'CRM и управление лидами',
                kanban: 'Канбан-доска',
            },
            // Support
            support: {
                telegram: 'Поддержка в Telegram',
                response_time: 'Время ответа',
                video_guides: 'Видеоинструкции',
                onboarding: 'Помощь при подключении',
                personal_manager: 'Персональный менеджер',
            },
            // Values (text-based cell values)
            values: {
                pcs_2: '2',
                pcs_3: '3',
                pcs_5: '5',
                pcs_10: '10',
                pcs_15: '15',
                pcs_20: '20',
                pcs_1: '1',
                leads_500: '500',
                leads_2000: '2 000',
                leads_10000: '10 000',
                unlimited: 'Безлимит',
                storage_500mb: '500 МБ',
                storage_1gb: '1 ГБ',
                storage_5gb: '5 ГБ',
                storage_50gb: '50 ГБ',
                min_60: '60 мин',
                min_150: '150 мин',
                min_400: '400 мин',
                min_1000: '1 000 мин',
                price_500: '500 сум',
                price_450: '450 сум',
                price_400: '400 сум',
                price_300: '300 сум',
                requests_500: '500',
                requests_2000: '2 000',
                requests_10000: '10 000',
                requests_50000: '50 000',
                hours_24: '24 часа',
                hours_12: '12 часов',
                hours_8: '8 часов',
                hours_2: '2 часа',
                personal: 'Персональный',
            },
        },
    },
}
