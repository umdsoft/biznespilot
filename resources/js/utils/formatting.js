/**
 * Markazlashtirilgan formatlash utillari
 * Barcha valyuta, sana, vaqt va davomiylik formatlari shu yerda
 */

// ============================================
// VALYUTA FORMATLARI
// ============================================

/**
 * Valyutani qisqa formatda ko'rsatish (1.5M, 500K)
 * @param {number} amount - Summa
 * @returns {string} Formatlangan summa
 */
export const formatCurrency = (amount) => {
    if (!amount && amount !== 0) return '0';
    const num = Number(amount);
    if (isNaN(num)) return '0';
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(0) + 'K';
    return new Intl.NumberFormat('uz-UZ').format(num);
};

/**
 * Valyutani to'liq formatda ko'rsatish (so'm bilan)
 * @param {number} amount - Summa
 * @param {string} currency - Valyuta (default: so'm)
 * @returns {string} Formatlangan summa
 */
export const formatFullCurrency = (amount, currency = "so'm") => {
    if (!amount && amount !== 0) return `0 ${currency}`;
    const num = Number(amount);
    if (isNaN(num)) return `0 ${currency}`;
    return new Intl.NumberFormat('uz-UZ').format(num) + ` ${currency}`;
};

/**
 * Valyutani katta raqamlar uchun formatlash (B/M/K)
 * @param {number} amount - Summa
 * @param {string} currency - Valyuta (default: so'm)
 * @returns {string} Formatlangan summa
 */
export const formatMoney = (amount, currency = "so'm") => {
    if (!amount && amount !== 0) return `0 ${currency}`;
    const num = Number(amount);
    if (isNaN(num)) return `0 ${currency}`;

    if (num >= 1000000000) {
        return (num / 1000000000).toFixed(1) + `B ${currency}`;
    }
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + `M ${currency}`;
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(0) + `K ${currency}`;
    }
    return new Intl.NumberFormat('uz-UZ').format(num) + ` ${currency}`;
};

/**
 * Oddiy raqamni formatlash (vergul bilan)
 * @param {number} num - Raqam
 * @returns {string} Formatlangan raqam
 */
export const formatNumber = (num) => {
    if (!num && num !== 0) return '0';
    const n = Number(num);
    if (isNaN(n)) return '0';
    return new Intl.NumberFormat('uz-UZ').format(n);
};

/**
 * Foizni formatlash
 * @param {number} value - Foiz qiymati
 * @param {number} decimals - Kasr sonlar (default: 1)
 * @returns {string} Formatlangan foiz
 */
export const formatPercent = (value, decimals = 1) => {
    if (!value && value !== 0) return '0%';
    const num = Number(value);
    if (isNaN(num)) return '0%';
    return num.toFixed(decimals) + '%';
};

// ============================================
// SANA FORMATLARI
// ============================================

/**
 * Sanani DD.MM.YYYY formatida ko'rsatish
 * @param {string|Date} dateString - Sana
 * @returns {string} Formatlangan sana
 */
export const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';

    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}.${month}.${year}`;
};

/**
 * Sana va vaqtni DD.MM.YYYY | HH:MM formatida ko'rsatish
 * @param {string|Date} dateString - Sana
 * @returns {string} Formatlangan sana va vaqt
 */
export const formatDateTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';

    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${day}.${month}.${year} | ${hours}:${minutes}`;
};

/**
 * Faqat vaqtni HH:MM formatida ko'rsatish
 * @param {string|Date} dateString - Sana/vaqt
 * @returns {string} Formatlangan vaqt
 */
export const formatTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';

    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${hours}:${minutes}`;
};

/**
 * Nisbiy vaqtni ko'rsatish (3 daq oldin, 2 soat oldin)
 * @param {string|Date} dateString - Sana
 * @returns {string} Nisbiy vaqt
 */
export const formatRelativeTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';

    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Hozirgina';
    if (diffMins < 60) return `${diffMins} daq oldin`;
    if (diffHours < 24) return `${diffHours} soat oldin`;
    if (diffDays < 7) return `${diffDays} kun oldin`;
    if (diffDays < 30) return `${Math.floor(diffDays / 7)} hafta oldin`;

    // Agar 30 kundan oshsa, sanani ko'rsatish
    return formatDate(dateString);
};

/**
 * Oy nomini o'zbek tilida qaytarish
 * @param {number} monthIndex - Oy indeksi (0-11)
 * @returns {string} Oy nomi
 */
export const getMonthName = (monthIndex) => {
    const months = [
        'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun',
        'Iyul', 'Avgust', 'Sentyabr', 'Oktyabr', 'Noyabr', 'Dekabr'
    ];
    return months[monthIndex] || '';
};

/**
 * Hafta kunini o'zbek tilida qaytarish
 * @param {number} dayIndex - Kun indeksi (0-6, 0 = Yakshanba)
 * @returns {string} Kun nomi
 */
export const getDayName = (dayIndex) => {
    const days = [
        'Yakshanba', 'Dushanba', 'Seshanba', 'Chorshanba',
        'Payshanba', 'Juma', 'Shanba'
    ];
    return days[dayIndex] || '';
};

/**
 * Sanani o'zbek tilida to'liq formatda ko'rsatish
 * @param {string|Date} dateString - Sana
 * @returns {string} To'liq formatdagi sana
 */
export const formatDateFull = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';

    const day = date.getDate();
    const month = getMonthName(date.getMonth());
    const year = date.getFullYear();
    return `${day} ${month}, ${year}`;
};

// ============================================
// DAVOMIYLIK FORMATLARI
// ============================================

/**
 * Sekundlarni HH:MM:SS formatiga aylantirish
 * @param {number} seconds - Sekundlar
 * @returns {string} Formatlangan davomiylik
 */
export const formatDuration = (seconds) => {
    if (!seconds && seconds !== 0) return '00:00';
    const sec = Math.floor(Number(seconds));
    if (isNaN(sec) || sec < 0) return '00:00';

    const hours = Math.floor(sec / 3600);
    const mins = Math.floor((sec % 3600) / 60);
    const secs = sec % 60;

    if (hours > 0) {
        return `${hours}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
};

/**
 * Daqiqalarni o'qiladigan formatga aylantirish
 * @param {number} minutes - Daqiqalar
 * @returns {string} O'qiladigan davomiylik
 */
export const formatMinutesToReadable = (minutes) => {
    if (!minutes && minutes !== 0) return '0 daq';
    const mins = Math.floor(Number(minutes));
    if (isNaN(mins) || mins < 0) return '0 daq';

    if (mins < 60) return `${mins} daq`;

    const hours = Math.floor(mins / 60);
    const remainingMins = mins % 60;

    if (remainingMins === 0) return `${hours} soat`;
    return `${hours} soat ${remainingMins} daq`;
};

/**
 * Sekundlarni o'qiladigan formatga aylantirish
 * @param {number} seconds - Sekundlar
 * @returns {string} O'qiladigan davomiylik
 */
export const formatSecondsToReadable = (seconds) => {
    if (!seconds && seconds !== 0) return '0 sek';
    const sec = Math.floor(Number(seconds));
    if (isNaN(sec) || sec < 0) return '0 sek';

    if (sec < 60) return `${sec} sek`;
    return formatMinutesToReadable(Math.floor(sec / 60));
};

// ============================================
// YORDAMCHI FUNKSIYALAR
// ============================================

/**
 * Ismdan bosh harflarni olish (avatar uchun)
 * @param {string} name - Ism
 * @returns {string} Bosh harflar
 */
export const getInitials = (name) => {
    if (!name) return '?';
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

/**
 * Ismga qarab avatar rangini tanlash
 * @param {string} name - Ism
 * @returns {string} Tailwind gradient klassi
 */
export const getAvatarColor = (name) => {
    const colors = [
        'from-blue-500 to-blue-600',
        'from-purple-500 to-purple-600',
        'from-green-500 to-green-600',
        'from-orange-500 to-orange-600',
        'from-pink-500 to-pink-600',
        'from-indigo-500 to-indigo-600',
        'from-teal-500 to-teal-600',
        'from-red-500 to-red-600',
    ];
    const index = name ? name.charCodeAt(0) % colors.length : 0;
    return colors[index];
};

/**
 * Matn uzunligini qisqartirish
 * @param {string} text - Matn
 * @param {number} maxLength - Maksimal uzunlik
 * @returns {string} Qisqartirilgan matn
 */
export const truncateText = (text, maxLength = 50) => {
    if (!text) return '';
    if (text.length <= maxLength) return text;
    return text.slice(0, maxLength) + '...';
};

/**
 * Telefon raqamini formatlash
 * @param {string} phone - Telefon raqami
 * @returns {string} Formatlangan raqam
 */
export const formatPhone = (phone) => {
    if (!phone) return '';
    // Faqat raqamlarni olish
    const cleaned = phone.replace(/\D/g, '');

    // O'zbekiston formatida ko'rsatish
    if (cleaned.length === 12 && cleaned.startsWith('998')) {
        return `+${cleaned.slice(0, 3)} ${cleaned.slice(3, 5)} ${cleaned.slice(5, 8)} ${cleaned.slice(8, 10)} ${cleaned.slice(10)}`;
    }
    if (cleaned.length === 9) {
        return `+998 ${cleaned.slice(0, 2)} ${cleaned.slice(2, 5)} ${cleaned.slice(5, 7)} ${cleaned.slice(7)}`;
    }

    return phone;
};

// Default export
export default {
    // Valyuta
    formatCurrency,
    formatFullCurrency,
    formatMoney,
    formatNumber,
    formatPercent,
    // Sana
    formatDate,
    formatDateTime,
    formatTime,
    formatRelativeTime,
    getMonthName,
    getDayName,
    formatDateFull,
    // Davomiylik
    formatDuration,
    formatMinutesToReadable,
    formatSecondsToReadable,
    // Yordamchi
    getInitials,
    getAvatarColor,
    truncateText,
    formatPhone,
};
