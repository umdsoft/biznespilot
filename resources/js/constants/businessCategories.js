/**
 * Business Categories — yagona manba (DRY).
 *
 * Loyihada biznes kategoriya ishlatiladigan HAR QANDAY joyda shu fayldan
 * import qilinishi kerak. Yangi kategoriya qo'shilsa — SHU yerga qo'shiladi,
 * avtomatik hamma joyda ko'rinadi.
 *
 * Ishlatadigan joylar:
 *   - resources/js/Pages/Welcome/CreateBusiness.vue (yangi biznes yaratish)
 *   - resources/js/components/onboarding/forms/BusinessBasicForm.vue (onboarding)
 *   - resources/js/Pages/Partner/Referrals.vue (partner invite modal)
 *   - resources/js/Pages/Admin/Partners/* (agar kerak bo'lsa filter'da)
 */

export const BUSINESS_CATEGORIES = [
    { value: 'retail', label: 'Chakana savdo (Do\'konlar, supermarketlar)', short: 'Chakana savdo' },
    { value: 'wholesale', label: 'Ulgurji savdo', short: 'Ulgurji savdo' },
    { value: 'ecommerce', label: 'Onlayn savdo (E-commerce)', short: 'E-commerce' },
    { value: 'food_service', label: 'Oziq-ovqat xizmati (Restoranlar, kafelar)', short: 'Restoran / Kafe' },
    { value: 'manufacturing', label: 'Ishlab chiqarish', short: 'Ishlab chiqarish' },
    { value: 'construction', label: 'Qurilish va ta\'mirlash', short: 'Qurilish' },
    { value: 'it_services', label: 'IT xizmatlari va dasturlash', short: 'IT xizmatlari' },
    { value: 'education', label: 'Ta\'lim va o\'quv markazlari', short: 'Ta\'lim' },
    { value: 'healthcare', label: 'Sog\'liqni saqlash va tibbiyot', short: 'Tibbiyot' },
    { value: 'beauty_wellness', label: 'Go\'zallik va salomatlik (Salonlar, SPA)', short: 'Go\'zallik / Salon' },
    { value: 'real_estate', label: 'Ko\'chmas mulk', short: 'Ko\'chmas mulk' },
    { value: 'transportation', label: 'Transport va logistika', short: 'Transport' },
    { value: 'agriculture', label: 'Qishloq xo\'jaligi', short: 'Qishloq xo\'jaligi' },
    { value: 'tourism', label: 'Turizm va mehmonxonalar', short: 'Turizm' },
    { value: 'finance', label: 'Moliya va sug\'urta', short: 'Moliya' },
    { value: 'consulting', label: 'Konsalting va biznes xizmatlari', short: 'Konsalting' },
    { value: 'marketing_agency', label: 'Marketing va reklama agentligi', short: 'Marketing' },
    { value: 'media', label: 'Media va ko\'ngilochar sanoat', short: 'Media' },
    { value: 'fitness', label: 'Sport va fitness', short: 'Fitness' },
    { value: 'automotive', label: 'Avtomobil xizmatlari', short: 'Avtomobil' },
    { value: 'textile', label: 'To\'qimachilik va kiyim-kechak', short: 'To\'qimachilik' },
    { value: 'furniture', label: 'Mebel ishlab chiqarish va savdosi', short: 'Mebel' },
    { value: 'electronics', label: 'Elektronika va texnika', short: 'Elektronika' },
    { value: 'cleaning', label: 'Tozalash xizmatlari', short: 'Tozalash' },
    { value: 'event_services', label: 'Tadbirlar va to\'yxonalar', short: 'Tadbirlar' },
    { value: 'legal', label: 'Yuridik xizmatlar', short: 'Yuridik' },
    { value: 'other', label: 'Boshqa', short: 'Boshqa' },
];

/**
 * O'zbekiston viloyatlari — biznes yaratishda va invite modalda ishlatiladi.
 */
export const UZBEKISTAN_REGIONS = [
    { value: 'toshkent_shahar', label: 'Toshkent shahri' },
    { value: 'toshkent_viloyat', label: 'Toshkent viloyati' },
    { value: 'andijon', label: 'Andijon' },
    { value: 'buxoro', label: 'Buxoro' },
    { value: 'fargona', label: 'Farg\'ona' },
    { value: 'jizzax', label: 'Jizzax' },
    { value: 'namangan', label: 'Namangan' },
    { value: 'navoiy', label: 'Navoiy' },
    { value: 'qashqadaryo', label: 'Qashqadaryo' },
    { value: 'samarqand', label: 'Samarqand' },
    { value: 'sirdaryo', label: 'Sirdaryo' },
    { value: 'surxondaryo', label: 'Surxondaryo' },
    { value: 'xorazm', label: 'Xorazm' },
    { value: 'qoraqalpogiston', label: 'Qoraqalpog\'iston Respublikasi' },
];

/**
 * Helper: kategoriya value'dan label olish.
 *
 * @param {string} value — masalan 'retail'
 * @param {boolean} short — qisqa label (true) yoki to'liq (false, default)
 * @returns {string} label yoki value (agar topilmasa)
 */
export function getCategoryLabel(value, short = false) {
    const cat = BUSINESS_CATEGORIES.find((c) => c.value === value);
    if (!cat) return value || '';
    return short ? cat.short : cat.label;
}

/**
 * Helper: viloyat value'dan label olish.
 */
export function getRegionLabel(value) {
    const r = UZBEKISTAN_REGIONS.find((x) => x.value === value);
    return r?.label || value || '';
}
