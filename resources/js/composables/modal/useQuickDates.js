/**
 * Tez sana tanlash yordamchisi
 * Bugun, Ertaga, 3 kun, Hafta va h.k. uchun tayyor tugmalar
 */

export function useQuickDates() {
    const quickDates = [
        { label: 'Bugun', days: 0 },
        { label: 'Ertaga', days: 1 },
        { label: '3 kun', days: 3 },
        { label: 'Hafta', days: 7 },
        { label: '2 hafta', days: 14 },
        { label: 'Oy', days: 30 },
    ];

    /**
     * Bugundan N kun keyingi sanani YYYY-MM-DD formatida qaytarish
     */
    const getDatePlusDays = (days) => {
        const date = new Date();
        date.setDate(date.getDate() + days);
        return date.toISOString().split('T')[0];
    };

    /**
     * Formaning berilgan maydoniga tez sana o'rnatish
     */
    const setQuickDate = (form, days, field = 'due_date') => {
        form.value[field] = getDatePlusDays(days);
    };

    return { quickDates, getDatePlusDays, setQuickDate };
}
