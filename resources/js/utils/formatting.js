/**
 * Markazlashtirilgan formatlash utillari (fasad)
 *
 * Fayllar formatting/ papkasida bo'limlar bo'yicha ajratilgan:
 *   - currency.js — valyuta va raqamlar
 *   - date.js — sana va vaqt
 *   - duration.js — davomiylik
 *   - helpers.js — yordamchi (initials, avatar, phone, truncate)
 *
 * Backward compatibility: barcha eski importlar shu fayldan ishlaydi.
 */

// Valyuta
export {
    formatCurrency,
    formatFullCurrency,
    formatMoney,
    formatNumber,
    formatPercent,
} from './formatting/currency.js';

// Sana
export {
    formatDate,
    formatDateTime,
    formatTime,
    formatRelativeTime,
    getMonthName,
    getDayName,
    formatDateFull,
} from './formatting/date.js';

// Davomiylik
export {
    formatDuration,
    formatMinutesToReadable,
    formatSecondsToReadable,
} from './formatting/duration.js';

// Yordamchi
export {
    getInitials,
    getAvatarColor,
    truncateText,
    formatPhone,
} from './formatting/helpers.js';

// Default export — eski kod bilan moslik uchun
import * as currency from './formatting/currency.js';
import * as date from './formatting/date.js';
import * as duration from './formatting/duration.js';
import * as helpers from './formatting/helpers.js';

export default {
    ...currency,
    ...date,
    ...duration,
    ...helpers,
};
