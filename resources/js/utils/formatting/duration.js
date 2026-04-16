/**
 * Davomiylik formatlari
 */

/**
 * Sekundlarni HH:MM:SS formatiga aylantirish
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
 */
export const formatSecondsToReadable = (seconds) => {
    if (!seconds && seconds !== 0) return '0 sek';
    const sec = Math.floor(Number(seconds));
    if (isNaN(sec) || sec < 0) return '0 sek';

    if (sec < 60) return `${sec} sek`;
    return formatMinutesToReadable(Math.floor(sec / 60));
};
