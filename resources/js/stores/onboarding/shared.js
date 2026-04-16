/**
 * Onboarding store uchun umumiy async helper
 * try/catch/loading/error pattern'ni qayta-qayta yozmaslik uchun
 */

export function createAsyncWrapper(loading, error) {
    return async function runAsync(fn) {
        loading.value = true;
        error.value = null;
        try {
            return await fn();
        } catch (err) {
            error.value = err.response?.data?.message || 'Xatolik yuz berdi';
            throw err;
        } finally {
            loading.value = false;
        }
    };
}

/**
 * Faqat xato ushlaydigan wrapper (loading o'zgartirilmaydi)
 */
export function createFetchWrapper(error) {
    return async function runFetch(fn) {
        try {
            return await fn();
        } catch (err) {
            error.value = err.response?.data?.message || 'Xatolik yuz berdi';
            throw err;
        }
    };
}
