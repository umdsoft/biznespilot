/**
 * Markazlashtirilgan label (yorliq) composable
 * Barcha status, prioritet va tur yorliqlari shu yerda
 */

// ============================================
// PRIORITET YORLIQLARI
// ============================================

export const PRIORITIES = {
    low: {
        label: 'Past',
        color: 'gray',
        bgClass: 'bg-gray-100 dark:bg-gray-800',
        textClass: 'text-gray-600 dark:text-gray-400',
        badgeClass: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
        hex: '#94A3B8',
    },
    medium: {
        label: "O'rtacha",
        color: 'blue',
        bgClass: 'bg-blue-100 dark:bg-blue-900/30',
        textClass: 'text-blue-600 dark:text-blue-400',
        badgeClass: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
        hex: '#3B82F6',
    },
    high: {
        label: 'Yuqori',
        color: 'orange',
        bgClass: 'bg-orange-100 dark:bg-orange-900/30',
        textClass: 'text-orange-600 dark:text-orange-400',
        badgeClass: 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
        hex: '#F59E0B',
    },
    urgent: {
        label: 'Shoshilinch',
        color: 'red',
        bgClass: 'bg-red-100 dark:bg-red-900/30',
        textClass: 'text-red-600 dark:text-red-400',
        badgeClass: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
        hex: '#EF4444',
    },
};

/**
 * Prioritet yorlig'ini olish
 * @param {string} priority - Prioritet kaliti
 * @returns {string} Yorliq
 */
export const getPriorityLabel = (priority) => {
    return PRIORITIES[priority]?.label || priority || "Noma'lum";
};

/**
 * Prioritet badge klassini olish
 * @param {string} priority - Prioritet kaliti
 * @returns {string} CSS klasslari
 */
export const getPriorityBadgeClass = (priority) => {
    return PRIORITIES[priority]?.badgeClass || PRIORITIES.low.badgeClass;
};

/**
 * Prioritet rangini olish
 * @param {string} priority - Prioritet kaliti
 * @returns {string} Rang nomi
 */
export const getPriorityColor = (priority) => {
    return PRIORITIES[priority]?.color || 'gray';
};

// ============================================
// VAZIFA STATUSLARI
// ============================================

export const TASK_STATUSES = {
    pending: {
        label: 'Kutilmoqda',
        color: 'yellow',
        bgClass: 'bg-yellow-100 dark:bg-yellow-900/30',
        textClass: 'text-yellow-600 dark:text-yellow-400',
        badgeClass: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400',
    },
    in_progress: {
        label: 'Jarayonda',
        color: 'blue',
        bgClass: 'bg-blue-100 dark:bg-blue-900/30',
        textClass: 'text-blue-600 dark:text-blue-400',
        badgeClass: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
    },
    completed: {
        label: 'Bajarildi',
        color: 'green',
        bgClass: 'bg-green-100 dark:bg-green-900/30',
        textClass: 'text-green-600 dark:text-green-400',
        badgeClass: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
    },
    cancelled: {
        label: 'Bekor qilindi',
        color: 'gray',
        bgClass: 'bg-gray-100 dark:bg-gray-800',
        textClass: 'text-gray-600 dark:text-gray-400',
        badgeClass: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    },
};

/**
 * Vazifa status yorlig'ini olish
 * @param {string} status - Status kaliti
 * @returns {string} Yorliq
 */
export const getTaskStatusLabel = (status) => {
    return TASK_STATUSES[status]?.label || status || "Noma'lum";
};

/**
 * Vazifa status badge klassini olish
 * @param {string} status - Status kaliti
 * @returns {string} CSS klasslari
 */
export const getTaskStatusBadgeClass = (status) => {
    return TASK_STATUSES[status]?.badgeClass || TASK_STATUSES.pending.badgeClass;
};

// ============================================
// VAZIFA TURLARI
// ============================================

export const TASK_TYPES = {
    call: {
        label: "Qo'ng'iroq",
        icon: 'PhoneIcon',
    },
    meeting: {
        label: 'Uchrashuv',
        icon: 'UserGroupIcon',
    },
    email: {
        label: 'Email',
        icon: 'EnvelopeIcon',
    },
    task: {
        label: 'Vazifa',
        icon: 'ClipboardDocumentListIcon',
    },
    follow_up: {
        label: 'Qayta aloqa',
        icon: 'ArrowPathIcon',
    },
    other: {
        label: 'Boshqa',
        icon: 'EllipsisHorizontalIcon',
    },
};

/**
 * Vazifa turi yorlig'ini olish
 * @param {string} type - Tur kaliti
 * @returns {string} Yorliq
 */
export const getTaskTypeLabel = (type) => {
    return TASK_TYPES[type]?.label || type || "Noma'lum";
};

// ============================================
// QO'NG'IROQ STATUSLARI
// ============================================

export const CALL_STATUSES = {
    initiated: {
        label: 'Boshlandi',
        color: 'blue',
        textClass: 'text-blue-500',
    },
    ringing: {
        label: 'Jiringlamoqda',
        color: 'blue',
        textClass: 'text-blue-500',
    },
    answered: {
        label: 'Javob berildi',
        color: 'green',
        textClass: 'text-green-500',
    },
    completed: {
        label: 'Tugallandi',
        color: 'green',
        textClass: 'text-green-500',
    },
    failed: {
        label: 'Muvaffaqiyatsiz',
        color: 'gray',
        textClass: 'text-gray-500',
    },
    missed: {
        label: "O'tkazib yuborildi",
        color: 'red',
        textClass: 'text-red-500',
    },
    busy: {
        label: 'Band',
        color: 'yellow',
        textClass: 'text-yellow-500',
    },
    no_answer: {
        label: "Javob yo'q",
        color: 'red',
        textClass: 'text-red-500',
    },
    cancelled: {
        label: 'Bekor qilindi',
        color: 'gray',
        textClass: 'text-gray-500',
    },
};

/**
 * Qo'ng'iroq status yorlig'ini olish
 * @param {string} status - Status kaliti
 * @returns {string} Yorliq
 */
export const getCallStatusLabel = (status) => {
    return CALL_STATUSES[status]?.label || "Noma'lum";
};

/**
 * Qo'ng'iroq status rangini olish
 * @param {string} status - Status kaliti
 * @returns {string} Tailwind text-color klassi
 */
export const getCallStatusColor = (status) => {
    return CALL_STATUSES[status]?.textClass || 'text-blue-500';
};

// ============================================
// QO'NG'IROQ YO'NALISHI
// ============================================

export const CALL_DIRECTIONS = {
    inbound: {
        label: 'Kiruvchi',
        icon: 'ArrowDownIcon',
    },
    outbound: {
        label: 'Chiquvchi',
        icon: 'ArrowUpIcon',
    },
};

/**
 * Qo'ng'iroq yo'nalishi yorlig'ini olish
 * @param {string} direction - Yo'nalish kaliti
 * @returns {string} Yorliq
 */
export const getCallDirectionLabel = (direction) => {
    return CALL_DIRECTIONS[direction]?.label || direction || "Noma'lum";
};

// ============================================
// LID YO'QOTILISH SABABLARI
// ============================================

export const LOST_REASONS = {
    price: {
        label: 'Narx qimmat',
    },
    competitor: {
        label: 'Raqobatchini tanladi',
    },
    no_budget: {
        label: "Byudjet yo'q",
    },
    no_need: {
        label: "Ehtiyoj yo'q",
    },
    no_response: {
        label: 'Javob bermadi',
    },
    wrong_contact: {
        label: "Noto'g'ri kontakt",
    },
    low_quality: {
        label: 'Sifatsiz lid',
    },
    timing: {
        label: 'Vaqt mos kelmadi',
    },
    other: {
        label: 'Boshqa sabab',
    },
};

/**
 * Lid yo'qotilish sababi yorlig'ini olish
 * @param {string} reason - Sabab kaliti
 * @returns {string} Yorliq
 */
export const getLostReasonLabel = (reason) => {
    return LOST_REASONS[reason]?.label || reason || "Noma'lum";
};

// ============================================
// TO'LOV STATUSLARI
// ============================================

export const PAYMENT_STATUSES = {
    pending: {
        label: 'Kutilmoqda',
        color: 'yellow',
        badgeClass: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400',
    },
    processing: {
        label: 'Jarayonda',
        color: 'blue',
        badgeClass: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
    },
    completed: {
        label: "To'langan",
        color: 'green',
        badgeClass: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
    },
    cancelled: {
        label: 'Bekor qilingan',
        color: 'gray',
        badgeClass: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    },
    failed: {
        label: 'Xatolik',
        color: 'red',
        badgeClass: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
    },
    refunded: {
        label: 'Qaytarilgan',
        color: 'purple',
        badgeClass: 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
    },
};

/**
 * To'lov status yorlig'ini olish
 * @param {string} status - Status kaliti
 * @returns {string} Yorliq
 */
export const getPaymentStatusLabel = (status) => {
    return PAYMENT_STATUSES[status]?.label || status || "Noma'lum";
};

/**
 * To'lov status badge klassini olish
 * @param {string} status - Status kaliti
 * @returns {string} CSS klasslari
 */
export const getPaymentStatusBadgeClass = (status) => {
    return PAYMENT_STATUSES[status]?.badgeClass || PAYMENT_STATUSES.pending.badgeClass;
};

// ============================================
// KONTENT STATUSLARI
// ============================================

export const CONTENT_STATUSES = {
    idea: {
        label: "G'oya",
        color: 'gray',
        badgeClass: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    },
    draft: {
        label: 'Qoralama',
        color: 'yellow',
        badgeClass: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400',
    },
    pending_review: {
        label: 'Tekshiruvda',
        color: 'orange',
        badgeClass: 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
    },
    approved: {
        label: 'Tasdiqlangan',
        color: 'blue',
        badgeClass: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
    },
    scheduled: {
        label: 'Rejalashtirilgan',
        color: 'indigo',
        badgeClass: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400',
    },
    published: {
        label: 'Joylashtirilgan',
        color: 'green',
        badgeClass: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
    },
    failed: {
        label: 'Xato',
        color: 'red',
        badgeClass: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
    },
    archived: {
        label: 'Arxivlangan',
        color: 'gray',
        badgeClass: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    },
};

/**
 * Kontent status yorlig'ini olish
 * @param {string} status - Status kaliti
 * @returns {string} Yorliq
 */
export const getContentStatusLabel = (status) => {
    return CONTENT_STATUSES[status]?.label || status || "Noma'lum";
};

/**
 * Kontent status badge klassini olish
 * @param {string} status - Status kaliti
 * @returns {string} CSS klasslari
 */
export const getContentStatusBadgeClass = (status) => {
    return CONTENT_STATUSES[status]?.badgeClass || CONTENT_STATUSES.idea.badgeClass;
};

// ============================================
// IJTIMOIY TARMOQLAR
// ============================================

export const SOCIAL_CHANNELS = {
    instagram: {
        label: 'Instagram',
        color: '#E4405F',
        icon: 'instagram',
    },
    telegram: {
        label: 'Telegram',
        color: '#0088cc',
        icon: 'telegram',
    },
    facebook: {
        label: 'Facebook',
        color: '#1877F2',
        icon: 'facebook',
    },
    tiktok: {
        label: 'TikTok',
        color: '#000000',
        icon: 'tiktok',
    },
    youtube: {
        label: 'YouTube',
        color: '#FF0000',
        icon: 'youtube',
    },
    linkedin: {
        label: 'LinkedIn',
        color: '#0A66C2',
        icon: 'linkedin',
    },
    twitter: {
        label: 'Twitter/X',
        color: '#1DA1F2',
        icon: 'twitter',
    },
    website: {
        label: 'Websayt',
        color: '#4B5563',
        icon: 'globe',
    },
    email: {
        label: 'Email',
        color: '#EA4335',
        icon: 'envelope',
    },
    sms: {
        label: 'SMS',
        color: '#10B981',
        icon: 'chat',
    },
};

/**
 * Ijtimoiy tarmoq yorlig'ini olish
 * @param {string} channel - Kanal kaliti
 * @returns {string} Yorliq
 */
export const getSocialChannelLabel = (channel) => {
    return SOCIAL_CHANNELS[channel]?.label || channel || "Noma'lum";
};

// ============================================
// KONTENT TURLARI
// ============================================

export const CONTENT_TYPES = {
    post: { label: 'Post' },
    story: { label: 'Story' },
    reel: { label: 'Reel' },
    video: { label: 'Video' },
    article: { label: 'Maqola' },
    carousel: { label: 'Carousel' },
    live: { label: 'Live' },
    poll: { label: "So'rovnoma" },
    ad: { label: 'Reklama' },
    email: { label: 'Email' },
    sms: { label: 'SMS' },
    other: { label: 'Boshqa' },
};

/**
 * Kontent turi yorlig'ini olish
 * @param {string} type - Tur kaliti
 * @returns {string} Yorliq
 */
export const getContentTypeLabel = (type) => {
    return CONTENT_TYPES[type]?.label || type || "Noma'lum";
};

// ============================================
// FEEDBACK TURLARI
// ============================================

export const FEEDBACK_TYPES = {
    bug: {
        label: 'Xatolik',
        color: 'red',
        icon: 'BugAntIcon',
    },
    suggestion: {
        label: 'Taklif',
        color: 'blue',
        icon: 'LightBulbIcon',
    },
    question: {
        label: 'Savol',
        color: 'purple',
        icon: 'QuestionMarkCircleIcon',
    },
    other: {
        label: 'Boshqa',
        color: 'gray',
        icon: 'ChatBubbleLeftIcon',
    },
};

/**
 * Feedback turi yorlig'ini olish
 * @param {string} type - Tur kaliti
 * @returns {string} Yorliq
 */
export const getFeedbackTypeLabel = (type) => {
    return FEEDBACK_TYPES[type]?.label || type || "Noma'lum";
};

// ============================================
// UMUMIY USECOMPOSABLE
// ============================================

/**
 * useLabels composable - barcha labellarni ishlatish uchun
 */
export function useLabels() {
    return {
        // Konstantalar
        PRIORITIES,
        TASK_STATUSES,
        TASK_TYPES,
        CALL_STATUSES,
        CALL_DIRECTIONS,
        LOST_REASONS,
        PAYMENT_STATUSES,
        CONTENT_STATUSES,
        SOCIAL_CHANNELS,
        CONTENT_TYPES,
        FEEDBACK_TYPES,

        // Prioritet funksiyalari
        getPriorityLabel,
        getPriorityBadgeClass,
        getPriorityColor,

        // Vazifa funksiyalari
        getTaskStatusLabel,
        getTaskStatusBadgeClass,
        getTaskTypeLabel,

        // Qo'ng'iroq funksiyalari
        getCallStatusLabel,
        getCallStatusColor,
        getCallDirectionLabel,

        // Lid funksiyalari
        getLostReasonLabel,

        // To'lov funksiyalari
        getPaymentStatusLabel,
        getPaymentStatusBadgeClass,

        // Kontent funksiyalari
        getContentStatusLabel,
        getContentStatusBadgeClass,
        getContentTypeLabel,

        // Ijtimoiy tarmoq funksiyalari
        getSocialChannelLabel,

        // Feedback funksiyalari
        getFeedbackTypeLabel,
    };
}

export default useLabels;
