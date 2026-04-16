/**
 * Valyuta va raqam formatlari
 */

/**
 * Valyutani qisqa formatda ko'rsatish (1.5M, 500K)
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
 */
export const formatFullCurrency = (amount, currency = "so'm") => {
    if (!amount && amount !== 0) return `0 ${currency}`;
    const num = Number(amount);
    if (isNaN(num)) return `0 ${currency}`;
    return new Intl.NumberFormat('uz-UZ').format(num) + ` ${currency}`;
};

/**
 * Valyutani katta raqamlar uchun formatlash (B/M/K)
 */
export const formatMoney = (amount, currency = "so'm") => {
    if (!amount && amount !== 0) return `0 ${currency}`;
    const num = Number(amount);
    if (isNaN(num)) return `0 ${currency}`;

    if (num >= 1000000000) return (num / 1000000000).toFixed(1) + `B ${currency}`;
    if (num >= 1000000) return (num / 1000000).toFixed(1) + `M ${currency}`;
    if (num >= 1000) return (num / 1000).toFixed(0) + `K ${currency}`;

    return new Intl.NumberFormat('uz-UZ').format(num) + ` ${currency}`;
};

/**
 * Oddiy raqamni formatlash (vergul bilan)
 */
export const formatNumber = (num) => {
    if (!num && num !== 0) return '0';
    const n = Number(num);
    if (isNaN(n)) return '0';
    return new Intl.NumberFormat('uz-UZ').format(n);
};

/**
 * Foizni formatlash
 */
export const formatPercent = (value, decimals = 1) => {
    if (!value && value !== 0) return '0%';
    const num = Number(value);
    if (isNaN(num)) return '0%';
    return num.toFixed(decimals) + '%';
};
