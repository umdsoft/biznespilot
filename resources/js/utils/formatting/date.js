/**
 * Sana va vaqt formatlari
 */

/**
 * Sanani DD.MM.YYYY formatida ko'rsatish
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

    return formatDate(dateString);
};

/**
 * Oy nomini o'zbek tilida qaytarish
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
