import { computed } from 'vue'

function getCookie(name) {
    const value = `; ${document.cookie}`
    const parts = value.split(`; ${name}=`)
    if (parts.length === 2) return parts.pop().split(';').shift()
    return null
}

const translations = {
    'uz-latn': {
        nav: {
            features: 'Imkoniyatlar',
            modules: 'Modullar',
            pricing: 'Narxlar',
            login: 'Kirish',
            get_started: 'Bepul Boshlash',
            free_badge: '14 kun tekin',
            free_mobile: 'Bepul Boshlash — 14 kun tekin',
            urgency_text: "Diqqat! Faqat bu hafta ro'yxatdan o'tganlar uchun",
            urgency_text_short: 'Faqat bu hafta:',
            urgency_highlight: '1 oylik Premium bepul.',
        },
        footer: {
            description: "O'zbekistonning yagona Biznes Operatsion Tizimi. Marketing, Sotuv, HR va Moliyani Sun'iy intellekt yordamida birlashtiring.",
            product_title: 'Mahsulot',
            features: 'Imkoniyatlar',
            pricing: 'Narxlar',
            integrations: 'Integratsiyalar',
            company_title: 'Kompaniya',
            about: 'Biz haqimizda',
            contact: 'Aloqa',
            careers: 'Vakansiyalar',
            legal_title: 'Huquqiy',
            privacy: 'Maxfiylik siyosati',
            terms: 'Foydalanish shartlari',
            copyright: 'Barcha huquqlar himoyalangan.',
            lang_label: "O'zbekcha",
            made_with: 'Made with',
            in_country: 'in',
            country: 'Uzbekistan',
        },
    },
    ru: {
        nav: {
            features: 'Возможности',
            modules: 'Модули',
            pricing: 'Цены',
            login: 'Войти',
            get_started: 'Начать бесплатно',
            free_badge: '14 дней бесплатно',
            free_mobile: 'Начать бесплатно — 14 дней',
            urgency_text: 'Внимание! Только для зарегистрировавшихся на этой неделе',
            urgency_text_short: 'Только на этой неделе:',
            urgency_highlight: '1 месяц Premium бесплатно.',
        },
        footer: {
            description: 'Единственная Бизнес Операционная Система Узбекистана. Объедините Маркетинг, Продажи, HR и Финансы с помощью Искусственного интеллекта.',
            product_title: 'Продукт',
            features: 'Возможности',
            pricing: 'Цены',
            integrations: 'Интеграции',
            company_title: 'Компания',
            about: 'О нас',
            contact: 'Контакты',
            careers: 'Вакансии',
            legal_title: 'Юридическая информация',
            privacy: 'Политика конфиденциальности',
            terms: 'Условия использования',
            copyright: 'Все права защищены.',
            lang_label: 'Русский',
            made_with: 'Сделано с',
            in_country: 'в',
            country: 'Узбекистане',
        },
    },
}

export function useLandingTranslations() {
    const locale = computed(() => {
        return getCookie('landing_locale') || 'uz-latn'
    })

    const t = computed(() => {
        return translations[locale.value] || translations['uz-latn']
    })

    return {
        locale,
        nav: computed(() => t.value.nav),
        footer: computed(() => t.value.footer),
    }
}
