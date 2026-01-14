/**
 * BiznesPilot Multi-language System
 * Supports: O'zbek (lotin), Ўзбек (кирилл), Русский
 */

import { reactive, computed, watch } from 'vue';

// Available locales
export const locales = {
    'uz-latn': {
        code: 'uz-latn',
        name: 'O\'zbekcha',
        nativeName: 'O\'zbekcha',
        flag: '🇺🇿',
        dir: 'ltr',
    },
    'uz-cyrl': {
        code: 'uz-cyrl',
        name: 'Ўзбекча',
        nativeName: 'Ўзбекча',
        flag: '🇺🇿',
        dir: 'ltr',
    },
    'ru': {
        code: 'ru',
        name: 'Русский',
        nativeName: 'Русский',
        flag: '🇷🇺',
        dir: 'ltr',
    },
};

// Default locale
export const defaultLocale = 'uz-latn';

// All translations - loaded synchronously at boot
const allTranslations = {
    'uz-latn': {
        // Navigation
        'nav.dashboard': 'Bosh sahifa',
        'nav.marketing': 'Marketing',
        'nav.content_plan': 'Kontent Reja',
        'nav.inbox': 'Yagona Inbox',
        'nav.research': 'Tadqiqot',
        'nav.dream_buyer': 'Ideal Mijoz',
        'nav.custdev': 'CustDev So\'rovnoma',
        'nav.competitors': 'Raqobatchilar',
        'nav.offers_strategy': 'Takliflar va Strategiya',
        'nav.offers': 'Takliflar',
        'nav.strategy': 'Strategiya Rejasi',
        'nav.sales_leads': 'Sotuv / Leadlar',
        'nav.tasks': 'Vazifalar',
        'nav.daily_tasks': 'Kunlik vazifalar',
        'nav.lead_forms': 'Lead Formalar',
        'nav.ai_helpers': 'AI Yordamchilar',
        'nav.facebook_analysis': 'Facebook analiz',
        'nav.instagram_analysis': 'Instagram Tahlili',
        'nav.telegram_funnel': 'Telegram Funnel',
        'nav.youtube_analytics': 'YouTube Analitika',
        'nav.google_ads': 'Google Ads',
        'nav.analytics_section': 'Tahlillar',
        'nav.sales_analytics': 'Sotuv Tahlili',
        'nav.reports': 'Hisobotlar',
        'nav.kpi': 'KPI Reja',
        'nav.settings': 'Sozlamalar',
        // Dashboard
        'dashboard.welcome': 'Xush kelibsiz',
        'dashboard.total_leads': 'Jami Leadlar',
        'dashboard.customers': 'Mijozlar',
        'dashboard.revenue_30d': 'Daromad (30 kun)',
        'dashboard.conversion': 'Konversiya',
        'dashboard.cac': 'CAC (Customer Acquisition Cost)',
        'dashboard.cac_desc': 'Har bir mijozni jalb qilish narxi',
        'dashboard.cac_benchmark': 'CLV/3 dan kam bo\'lishi kerak',
        'dashboard.clv': 'CLV (Customer Lifetime Value)',
        'dashboard.clv_desc': 'Mijozning umrbod qiymati',
        'dashboard.ltv_cac_ratio': 'LTV/CAC Ratio',
        'dashboard.roas': 'ROAS (Return on Ad Spend)',
        'dashboard.roas_desc': 'Reklama xarajatlaridan daromad',
        'dashboard.roi': 'ROI (Return on Investment)',
        'dashboard.roi_desc': 'Investitsiyadan daromad',
        'dashboard.churn_rate': 'Churn Rate',
        'dashboard.churn_desc': 'Mijozlar yo\'qotilish darajasi',
        'dashboard.module_stats': 'Modul Statistikasi',
        'dashboard.ideal_customers': 'Ideal Mijozlar',
        'dashboard.marketing_channels': 'Marketing Kanallari',
        'dashboard.active_offers': 'Faol Takliflar',
        'dashboard.sales_trend': 'Sotuvlar tendensiyasi (oxirgi 7 kun)',
        'dashboard.no_sales': 'Hali sotuvlar yo\'q',
        'dashboard.date': 'Sana',
        'dashboard.sales': 'Sotuvlar',
        'dashboard.revenue': 'Daromad',
        'dashboard.benchmark': 'Benchmark',
        'dashboard.target': 'Target',
        'dashboard.good': 'Yaxshi',
        'dashboard.high': 'Yuqori',
        'dashboard.loading': 'Yuklanmoqda...',
        // Common
        'common.leads': 'Lidlar',
        'common.save': 'Saqlash',
        'common.cancel': 'Bekor qilish',
        'common.delete': 'O\'chirish',
        'common.edit': 'Tahrirlash',
        'common.add': 'Qo\'shish',
        'common.search': 'Qidirish',
        'common.filter': 'Filtrlash',
        'common.back': 'Orqaga',
        'common.next': 'Keyingi',
        'common.previous': 'Oldingi',
        'common.close': 'Yopish',
        'common.yes': 'Ha',
        'common.no': 'Yo\'q',
        'common.currency': 'so\'m',
        'common.loading': 'Yuklanmoqda...',
        'common.error': 'Xatolik',
        'common.success': 'Muvaffaqiyat',
        'common.confirm': 'Tasdiqlash',
        'common.actions': 'Amallar',
        'common.status': 'Holat',
        'common.name': 'Nomi',
        'common.description': 'Tavsif',
        'common.date': 'Sana',
        'common.time': 'Vaqt',
        'common.total': 'Jami',
        'common.view': 'Ko\'rish',
        'common.create': 'Yaratish',
        'common.update': 'Yangilash',
        'common.logout': 'Chiqish',
        'common.profile': 'Profil',
        'common.select_business': 'Biznes tanlang',
        'common.your_businesses': 'Bizneslaringiz',
        'common.create_business': 'Yangi biznes yaratish',
        'common.dark_mode': 'Qorong\'i rejim',
        'common.light_mode': 'Yorug\' rejim',
        // Layout
        'layout.select_business': 'Biznes tanlang',
        'layout.your_businesses': 'Bizneslaringiz',
        'layout.new_business': 'Yangi biznes yaratish',
        'layout.settings': 'Sozlamalar',
        'layout.logout': 'Chiqish',
        'layout.to_business_panel': 'Biznes Paneliga',
        // Sales
        'sales.title': 'Sotuv / Leadlar',
        'sales.new_lead': 'Yangi lead',
        'sales.leads': 'Leadlar',
        'sales.customers': 'Mijozlar',
        'sales.deals': 'Bitimlar',
        'sales.pipeline': 'Pipeline',
        'sales.total_revenue': 'Jami daromad',
        'sales.conversion_rate': 'Konversiya darajasi',
        // Marketing
        'marketing.title': 'Marketing',
        'marketing.campaigns': 'Kampaniyalar',
        'marketing.channels': 'Kanallar',
        'marketing.content': 'Kontent',
        'marketing.analytics': 'Analitika',
        'marketing.budget': 'Byudjet',
        // Tasks
        'tasks.title': 'Vazifalar',
        'tasks.my_tasks': 'Mening vazifalarim',
        'tasks.all_tasks': 'Barcha vazifalar',
        'tasks.completed': 'Bajarilgan',
        'tasks.pending': 'Kutilmoqda',
        'tasks.overdue': 'Muddati o\'tgan',
        'tasks.due_date': 'Muddat',
        'tasks.priority': 'Muhimlik',
        'tasks.assigned_to': 'Tayinlangan',
        // Forms
        'forms.required': 'Majburiy maydon',
        'forms.email': 'Email',
        'forms.phone': 'Telefon',
        'forms.address': 'Manzil',
        'forms.submit': 'Yuborish',
        'forms.reset': 'Tozalash',
    },
    'uz-cyrl': {
        // Navigation
        'nav.dashboard': 'Бош саҳифа',
        'nav.marketing': 'Маркетинг',
        'nav.content_plan': 'Контент Режа',
        'nav.inbox': 'Ягона Inbox',
        'nav.research': 'Тадқиқот',
        'nav.dream_buyer': 'Идеал Мижоз',
        'nav.custdev': 'CustDev Сўровнома',
        'nav.competitors': 'Рақобатчилар',
        'nav.offers_strategy': 'Таклифлар ва Стратегия',
        'nav.offers': 'Таклифлар',
        'nav.strategy': 'Стратегия Режаси',
        'nav.sales_leads': 'Сотув / Лидлар',
        'nav.tasks': 'Вазифалар',
        'nav.daily_tasks': 'Кунлик вазифалар',
        'nav.lead_forms': 'Lead Формалар',
        'nav.ai_helpers': 'AI Ёрдамчилар',
        'nav.facebook_analysis': 'Facebook таҳлил',
        'nav.instagram_analysis': 'Instagram Таҳлили',
        'nav.telegram_funnel': 'Telegram Фуннел',
        'nav.youtube_analytics': 'YouTube Аналитика',
        'nav.google_ads': 'Google Ads',
        'nav.analytics_section': 'Таҳлиллар',
        'nav.sales_analytics': 'Сотув Таҳлили',
        'nav.reports': 'Ҳисоботлар',
        'nav.kpi': 'KPI Режа',
        'nav.settings': 'Созламалар',
        // Dashboard
        'dashboard.welcome': 'Хуш келибсиз',
        'dashboard.total_leads': 'Жами Лидлар',
        'dashboard.customers': 'Мижозлар',
        'dashboard.revenue_30d': 'Даромад (30 кун)',
        'dashboard.conversion': 'Конверсия',
        'dashboard.cac': 'CAC (Customer Acquisition Cost)',
        'dashboard.cac_desc': 'Ҳар бир мижозни жалб қилиш нархи',
        'dashboard.cac_benchmark': 'CLV/3 дан кам бўлиши керак',
        'dashboard.clv': 'CLV (Customer Lifetime Value)',
        'dashboard.clv_desc': 'Мижознинг умрбод қиймати',
        'dashboard.ltv_cac_ratio': 'LTV/CAC Ratio',
        'dashboard.roas': 'ROAS (Return on Ad Spend)',
        'dashboard.roas_desc': 'Реклама харажатларидан даромад',
        'dashboard.roi': 'ROI (Return on Investment)',
        'dashboard.roi_desc': 'Инвестициядан даромад',
        'dashboard.churn_rate': 'Churn Rate',
        'dashboard.churn_desc': 'Мижозлар йўқотилиш даражаси',
        'dashboard.module_stats': 'Модул Статистикаси',
        'dashboard.ideal_customers': 'Идеал Мижозлар',
        'dashboard.marketing_channels': 'Маркетинг Каналлари',
        'dashboard.active_offers': 'Фаол Таклифлар',
        'dashboard.sales_trend': 'Сотувлар тенденсияси (охирги 7 кун)',
        'dashboard.no_sales': 'Ҳали сотувлар йўқ',
        'dashboard.date': 'Сана',
        'dashboard.sales': 'Сотувлар',
        'dashboard.revenue': 'Даромад',
        'dashboard.benchmark': 'Benchmark',
        'dashboard.target': 'Target',
        'dashboard.good': 'Яхши',
        'dashboard.high': 'Юқори',
        'dashboard.loading': 'Юкланмоқда...',
        // Common
        'common.leads': 'Лидлар',
        'common.save': 'Сақлаш',
        'common.cancel': 'Бекор қилиш',
        'common.delete': 'Ўчириш',
        'common.edit': 'Таҳрирлаш',
        'common.add': 'Қўшиш',
        'common.search': 'Қидириш',
        'common.filter': 'Филтрлаш',
        'common.back': 'Орқага',
        'common.next': 'Кейинги',
        'common.previous': 'Олдинги',
        'common.close': 'Ёпиш',
        'common.yes': 'Ҳа',
        'common.no': 'Йўқ',
        'common.currency': 'сўм',
        'common.loading': 'Юкланмоқда...',
        'common.error': 'Хатолик',
        'common.success': 'Муваффақият',
        'common.confirm': 'Тасдиқлаш',
        'common.actions': 'Амаллар',
        'common.status': 'Ҳолат',
        'common.name': 'Номи',
        'common.description': 'Тавсиф',
        'common.date': 'Сана',
        'common.time': 'Вақт',
        'common.total': 'Жами',
        'common.view': 'Кўриш',
        'common.create': 'Яратиш',
        'common.update': 'Янгилаш',
        'common.logout': 'Чиқиш',
        'common.profile': 'Профил',
        'common.select_business': 'Бизнес танланг',
        'common.your_businesses': 'Бизнесларингиз',
        'common.create_business': 'Янги бизнес яратиш',
        'common.dark_mode': 'Қоронғи режим',
        'common.light_mode': 'Ёруғ режим',
        // Layout
        'layout.select_business': 'Бизнес танланг',
        'layout.your_businesses': 'Бизнесларингиз',
        'layout.new_business': 'Янги бизнес яратиш',
        'layout.settings': 'Созламалар',
        'layout.logout': 'Чиқиш',
        'layout.to_business_panel': 'Бизнес Панелига',
        // Sales
        'sales.title': 'Сотув / Лидлар',
        'sales.new_lead': 'Янги лид',
        'sales.leads': 'Лидлар',
        'sales.customers': 'Мижозлар',
        'sales.deals': 'Битимлар',
        'sales.pipeline': 'Пайплайн',
        'sales.total_revenue': 'Жами даромад',
        'sales.conversion_rate': 'Конверсия даражаси',
        // Marketing
        'marketing.title': 'Маркетинг',
        'marketing.campaigns': 'Кампаниялар',
        'marketing.channels': 'Каналлар',
        'marketing.content': 'Контент',
        'marketing.analytics': 'Аналитика',
        'marketing.budget': 'Бюджет',
        // Tasks
        'tasks.title': 'Вазифалар',
        'tasks.my_tasks': 'Менинг вазифаларим',
        'tasks.all_tasks': 'Барча вазифалар',
        'tasks.completed': 'Бажарилган',
        'tasks.pending': 'Кутилмоқда',
        'tasks.overdue': 'Муддати ўтган',
        'tasks.due_date': 'Муддат',
        'tasks.priority': 'Муҳимлик',
        'tasks.assigned_to': 'Тайинланган',
        // Forms
        'forms.required': 'Мажбурий майдон',
        'forms.email': 'Емаил',
        'forms.phone': 'Телефон',
        'forms.address': 'Манзил',
        'forms.submit': 'Юбориш',
        'forms.reset': 'Тозалаш',
    },
    'ru': {
        // Navigation
        'nav.dashboard': 'Главная',
        'nav.marketing': 'Маркетинг',
        'nav.content_plan': 'Контент План',
        'nav.inbox': 'Единый Inbox',
        'nav.research': 'Исследования',
        'nav.dream_buyer': 'Идеальный Клиент',
        'nav.custdev': 'CustDev Опрос',
        'nav.competitors': 'Конкуренты',
        'nav.offers_strategy': 'Предложения и Стратегия',
        'nav.offers': 'Предложения',
        'nav.strategy': 'Стратегический План',
        'nav.sales_leads': 'Продажи / Лиды',
        'nav.tasks': 'Задачи',
        'nav.daily_tasks': 'Ежедневные задачи',
        'nav.lead_forms': 'Lead Формы',
        'nav.ai_helpers': 'AI Помощники',
        'nav.facebook_analysis': 'Facebook анализ',
        'nav.instagram_analysis': 'Instagram Анализ',
        'nav.telegram_funnel': 'Telegram Воронка',
        'nav.youtube_analytics': 'YouTube Аналитика',
        'nav.google_ads': 'Google Ads',
        'nav.analytics_section': 'Аналитика',
        'nav.sales_analytics': 'Анализ Продаж',
        'nav.reports': 'Отчёты',
        'nav.kpi': 'KPI План',
        'nav.settings': 'Настройки',
        // Dashboard
        'dashboard.welcome': 'Добро пожаловать',
        'dashboard.total_leads': 'Всего Лидов',
        'dashboard.customers': 'Клиенты',
        'dashboard.revenue_30d': 'Доход (30 дней)',
        'dashboard.conversion': 'Конверсия',
        'dashboard.cac': 'CAC (Customer Acquisition Cost)',
        'dashboard.cac_desc': 'Стоимость привлечения клиента',
        'dashboard.cac_benchmark': 'Должен быть меньше CLV/3',
        'dashboard.clv': 'CLV (Customer Lifetime Value)',
        'dashboard.clv_desc': 'Пожизненная ценность клиента',
        'dashboard.ltv_cac_ratio': 'LTV/CAC Ratio',
        'dashboard.roas': 'ROAS (Return on Ad Spend)',
        'dashboard.roas_desc': 'Доход от рекламных расходов',
        'dashboard.roi': 'ROI (Return on Investment)',
        'dashboard.roi_desc': 'Доход от инвестиций',
        'dashboard.churn_rate': 'Churn Rate',
        'dashboard.churn_desc': 'Уровень оттока клиентов',
        'dashboard.module_stats': 'Статистика Модулей',
        'dashboard.ideal_customers': 'Идеальные Клиенты',
        'dashboard.marketing_channels': 'Маркетинговые Каналы',
        'dashboard.active_offers': 'Активные Предложения',
        'dashboard.sales_trend': 'Тренд продаж (последние 7 дней)',
        'dashboard.no_sales': 'Пока нет продаж',
        'dashboard.date': 'Дата',
        'dashboard.sales': 'Продажи',
        'dashboard.revenue': 'Доход',
        'dashboard.benchmark': 'Benchmark',
        'dashboard.target': 'Target',
        'dashboard.good': 'Хорошо',
        'dashboard.high': 'Высокий',
        'dashboard.loading': 'Загрузка...',
        // Common
        'common.leads': 'Лиды',
        'common.save': 'Сохранить',
        'common.cancel': 'Отмена',
        'common.delete': 'Удалить',
        'common.edit': 'Редактировать',
        'common.add': 'Добавить',
        'common.search': 'Поиск',
        'common.filter': 'Фильтр',
        'common.back': 'Назад',
        'common.next': 'Далее',
        'common.previous': 'Предыдущий',
        'common.close': 'Закрыть',
        'common.yes': 'Да',
        'common.no': 'Нет',
        'common.currency': 'сум',
        'common.loading': 'Загрузка...',
        'common.error': 'Ошибка',
        'common.success': 'Успешно',
        'common.confirm': 'Подтвердить',
        'common.actions': 'Действия',
        'common.status': 'Статус',
        'common.name': 'Название',
        'common.description': 'Описание',
        'common.date': 'Дата',
        'common.time': 'Время',
        'common.total': 'Итого',
        'common.view': 'Просмотр',
        'common.create': 'Создать',
        'common.update': 'Обновить',
        'common.logout': 'Выйти',
        'common.profile': 'Профиль',
        'common.select_business': 'Выберите бизнес',
        'common.your_businesses': 'Ваши бизнесы',
        'common.create_business': 'Создать новый бизнес',
        'common.dark_mode': 'Тёмный режим',
        'common.light_mode': 'Светлый режим',
        // Layout
        'layout.select_business': 'Выберите бизнес',
        'layout.your_businesses': 'Ваши бизнесы',
        'layout.new_business': 'Создать новый бизнес',
        'layout.settings': 'Настройки',
        'layout.logout': 'Выйти',
        'layout.to_business_panel': 'В Бизнес Панель',
        // Sales
        'sales.title': 'Продажи / Лиды',
        'sales.new_lead': 'Новый лид',
        'sales.leads': 'Лиды',
        'sales.customers': 'Клиенты',
        'sales.deals': 'Сделки',
        'sales.pipeline': 'Воронка',
        'sales.total_revenue': 'Общий доход',
        'sales.conversion_rate': 'Коэффициент конверсии',
        // Marketing
        'marketing.title': 'Маркетинг',
        'marketing.campaigns': 'Кампании',
        'marketing.channels': 'Каналы',
        'marketing.content': 'Контент',
        'marketing.analytics': 'Аналитика',
        'marketing.budget': 'Бюджет',
        // Tasks
        'tasks.title': 'Задачи',
        'tasks.my_tasks': 'Мои задачи',
        'tasks.all_tasks': 'Все задачи',
        'tasks.completed': 'Завершённые',
        'tasks.pending': 'Ожидающие',
        'tasks.overdue': 'Просроченные',
        'tasks.due_date': 'Срок',
        'tasks.priority': 'Приоритет',
        'tasks.assigned_to': 'Назначено',
        // Forms
        'forms.required': 'Обязательное поле',
        'forms.email': 'Email',
        'forms.phone': 'Телефон',
        'forms.address': 'Адрес',
        'forms.submit': 'Отправить',
        'forms.reset': 'Сбросить',
    },
};

// Get stored locale from localStorage
function getStoredLocale() {
    if (typeof window === 'undefined') return defaultLocale;
    const stored = localStorage.getItem('biznespilot_locale');
    if (stored && locales[stored]) {
        return stored;
    }
    return defaultLocale;
}

// Save locale to storage
function saveLocale(locale) {
    if (typeof window === 'undefined') return;
    localStorage.setItem('biznespilot_locale', locale);
    // Cookie for server-side
    const expires = new Date();
    expires.setFullYear(expires.getFullYear() + 1);
    document.cookie = `locale=${locale};expires=${expires.toUTCString()};path=/;SameSite=Lax`;
}

// Initialize locale immediately
const initialLocale = typeof window !== 'undefined' ? getStoredLocale() : defaultLocale;

// Reactive state - GLOBAL singleton
const state = reactive({
    currentLocale: initialLocale,
    translations: { ...allTranslations[initialLocale] },
    isLoaded: true,
});

// Log initialization
if (typeof window !== 'undefined') {
    console.log(`[i18n] Initialized with locale: ${state.currentLocale}`);
    console.log(`[i18n] Translations loaded: ${Object.keys(state.translations).length} keys`);
}

// Get current locale
export function getCurrentLocale() {
    return state.currentLocale;
}

// Set locale and reload page
export function setLocale(locale) {
    if (!locales[locale]) {
        console.warn(`[i18n] Invalid locale: ${locale}`);
        return;
    }

    console.log(`[i18n] Switching to locale: ${locale}`);
    saveLocale(locale);

    // Reload page to apply new locale
    if (typeof window !== 'undefined') {
        window.location.reload();
    }
}

// Load translations (for compatibility - now sync)
export async function loadTranslations(locale = null) {
    const targetLocale = locale || state.currentLocale;

    // Update state with new translations
    state.currentLocale = targetLocale;

    // Clear and reassign translations
    const newTranslations = allTranslations[targetLocale] || allTranslations[defaultLocale];
    Object.keys(state.translations).forEach(key => delete state.translations[key]);
    Object.assign(state.translations, newTranslations);

    state.isLoaded = true;
    console.log(`[i18n] Loaded translations for: ${targetLocale}`);

    // Also try to fetch from API for extended translations
    try {
        const response = await fetch(`/api/translations/${targetLocale}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (response.ok) {
            const data = await response.json();
            // Merge API translations (they have app. prefix)
            Object.entries(data).forEach(([key, value]) => {
                // Store with and without app. prefix
                state.translations[key] = value;
                if (key.startsWith('app.')) {
                    state.translations[key.replace('app.', '')] = value;
                }
            });
            console.log(`[i18n] Merged ${Object.keys(data).length} API translations`);
        }
    } catch (e) {
        console.log('[i18n] API fetch skipped, using bundled translations');
    }

    return state.translations;
}

// Translation function
export function t(key, replacements = {}) {
    // Try direct key
    let value = state.translations[key];

    // Try with app. prefix
    if (value === undefined && !key.startsWith('app.')) {
        value = state.translations[`app.${key}`];
    }

    // Try without app. prefix
    if (value === undefined && key.startsWith('app.')) {
        value = state.translations[key.replace('app.', '')];
    }

    if (typeof value !== 'string') {
        return key;
    }

    // Replace placeholders
    let result = value;
    for (const [placeholder, replacement] of Object.entries(replacements)) {
        result = result.replace(new RegExp(`:${placeholder}`, 'g'), String(replacement));
    }

    return result;
}

// Vue plugin
export const i18nPlugin = {
    install(app) {
        // Global properties
        app.config.globalProperties.$t = (key, replacements = {}) => {
            return t(key, replacements);
        };

        app.config.globalProperties.$locale = {
            current: () => state.currentLocale,
            set: setLocale,
            list: locales,
        };

        // Provide for composition API
        app.provide('i18nState', state);
        app.provide('t', t);
        app.provide('locale', {
            current: computed(() => state.currentLocale),
            set: setLocale,
            list: locales,
        });
    },
};

// Composable for components
export function useI18n() {
    // Create reactive t function that re-evaluates when translations change
    const translate = (key, replacements = {}) => {
        // Access state.currentLocale to trigger reactivity
        const locale = state.currentLocale;
        const trans = state.translations;
        return t(key, replacements);
    };

    return {
        t: translate,
        locale: computed(() => state.currentLocale),
        setLocale,
        locales,
        isLoaded: computed(() => state.isLoaded),
        translations: computed(() => state.translations),
        // Direct access to state for debugging
        _state: state,
    };
}

// Export state for debugging
export { state as i18nState };
