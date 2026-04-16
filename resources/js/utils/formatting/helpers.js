/**
 * Yordamchi formatlash funksiyalari
 */

/**
 * Ismdan bosh harflarni olish (avatar uchun)
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
 */
export const truncateText = (text, maxLength = 50) => {
    if (!text) return '';
    if (text.length <= maxLength) return text;
    return text.slice(0, maxLength) + '...';
};

/**
 * Telefon raqamini formatlash (O'zbekiston)
 */
export const formatPhone = (phone) => {
    if (!phone) return '';
    const cleaned = phone.replace(/\D/g, '');

    if (cleaned.length === 12 && cleaned.startsWith('998')) {
        return `+${cleaned.slice(0, 3)} ${cleaned.slice(3, 5)} ${cleaned.slice(5, 8)} ${cleaned.slice(8, 10)} ${cleaned.slice(10)}`;
    }
    if (cleaned.length === 9) {
        return `+998 ${cleaned.slice(0, 2)} ${cleaned.slice(2, 5)} ${cleaned.slice(5, 7)} ${cleaned.slice(7)}`;
    }

    return phone;
};
