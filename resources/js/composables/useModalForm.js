/**
 * Modal forma boshqaruvi uchun composable
 * Barcha modal formalar uchun umumiy logika
 */

import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { refreshCsrfToken, isCsrfError } from '@/utils/csrf';

/**
 * Modal forma composable
 * @param {Object} options - Sozlamalar
 * @param {Function} options.getDefaultForm - Boshlang'ich forma qiymatlarini qaytaruvchi funksiya
 * @param {Function} options.getEditForm - Tahrirlash uchun forma qiymatlarini qaytaruvchi funksiya (item)
 * @param {Function} options.validate - Validatsiya funksiyasi (form) => boolean
 * @param {Object} props - Komponent props (show, item va h.k.)
 * @param {Function} emit - Emit funksiyasi
 * @returns {Object} Forma boshqaruvi
 */
export function useModalForm(options = {}) {
    const {
        getDefaultForm = () => ({}),
        getEditForm = null,
        validate = () => true,
        transformPayload = (form) => form,
    } = options;

    // Holat
    const form = ref(getDefaultForm());
    const isLoading = ref(false);
    const error = ref('');
    const errors = ref({});

    /**
     * Formani boshlang'ich holatga qaytarish
     */
    const resetForm = () => {
        form.value = getDefaultForm();
        error.value = '';
        errors.value = {};
    };

    /**
     * Tahrirlash uchun formani to'ldirish
     * @param {Object} item - Tahrir qilinadigan element
     */
    const populateForm = (item) => {
        if (item && getEditForm) {
            form.value = getEditForm(item);
        } else {
            resetForm();
        }
        error.value = '';
        errors.value = {};
    };

    /**
     * Forma qiymatini yangilash
     * @param {string} field - Maydon nomi
     * @param {*} value - Yangi qiymat
     */
    const setField = (field, value) => {
        form.value[field] = value;
        // Maydon o'zgarganida uning xatosini tozalash
        if (errors.value[field]) {
            delete errors.value[field];
        }
    };

    /**
     * Forma to'g'riligini tekshirish
     */
    const isValid = computed(() => {
        return validate(form.value);
    });

    /**
     * Xatolarni tozalash
     */
    const clearErrors = () => {
        error.value = '';
        errors.value = {};
    };

    /**
     * Laravel validatsiya xatolarini qayta ishlash
     * @param {Object} err - Axios xato obyekti
     */
    const handleValidationErrors = (err) => {
        if (err.response?.status === 422 && err.response?.data?.errors) {
            errors.value = err.response.data.errors;
            // Birinchi xatoni umumiy xato sifatida ko'rsatish
            const firstError = Object.values(err.response.data.errors)[0];
            error.value = Array.isArray(firstError) ? firstError[0] : firstError;
        }
    };

    /**
     * Xatoni qayta ishlash
     * @param {Object} err - Xato obyekti
     * @returns {string} Xato xabari
     */
    const handleError = async (err) => {
        console.error('Form error:', err);

        // CSRF xatosi
        if (isCsrfError(err)) {
            error.value = "Sessiya muddati tugadi. Qayta urinib ko'ring.";
            await refreshCsrfToken();
            return error.value;
        }

        // Laravel validatsiya xatosi
        if (err.response?.status === 422) {
            handleValidationErrors(err);
            return error.value;
        }

        // Boshqa xatolar
        if (err.response?.data?.error) {
            error.value = err.response.data.error;
        } else if (err.response?.data?.message) {
            error.value = err.response.data.message;
        } else {
            error.value = 'Tarmoq xatosi';
        }

        return error.value;
    };

    /**
     * Formani yuborish
     * @param {Function} submitFn - Yuborish funksiyasi (payload) => Promise
     * @param {Object} callbacks - Callback funksiyalari
     * @returns {Object|null} Javob yoki null
     */
    const submit = async (submitFn, callbacks = {}) => {
        const { onSuccess, onError, onFinally } = callbacks;

        if (!isValid.value || isLoading.value) return null;

        isLoading.value = true;
        clearErrors();

        try {
            // CSRF tokenni yangilash
            await refreshCsrfToken();

            // Payloadni tayyorlash
            const payload = transformPayload({ ...form.value });

            // So'rovni yuborish
            const response = await submitFn(payload);

            // Muvaffaqiyatli javob
            if (response?.data?.success !== false) {
                if (onSuccess) {
                    onSuccess(response?.data);
                }
                return response?.data;
            } else {
                error.value = response?.data?.error || response?.data?.message || 'Xatolik yuz berdi';
                if (onError) {
                    onError(error.value);
                }
                return null;
            }
        } catch (err) {
            await handleError(err);
            if (onError) {
                onError(error.value);
            }
            return null;
        } finally {
            isLoading.value = false;
            if (onFinally) {
                onFinally();
            }
        }
    };

    /**
     * POST so'rovi
     * @param {string} url - URL
     * @param {Object} callbacks - Callback funksiyalari
     */
    const submitPost = async (url, callbacks = {}) => {
        return submit(
            (payload) => window.axios.post(url, payload),
            callbacks
        );
    };

    /**
     * PUT so'rovi
     * @param {string} url - URL
     * @param {Object} callbacks - Callback funksiyalari
     */
    const submitPut = async (url, callbacks = {}) => {
        return submit(
            (payload) => window.axios.put(url, payload),
            callbacks
        );
    };

    /**
     * DELETE so'rovi
     * @param {string} url - URL
     * @param {Object} callbacks - Callback funksiyalari
     */
    const submitDelete = async (url, callbacks = {}) => {
        return submit(
            () => window.axios.delete(url),
            callbacks
        );
    };

    /**
     * Maydon xatosini olish
     * @param {string} field - Maydon nomi
     * @returns {string|null} Xato xabari
     */
    const getFieldError = (field) => {
        const fieldError = errors.value[field];
        if (!fieldError) return null;
        return Array.isArray(fieldError) ? fieldError[0] : fieldError;
    };

    /**
     * Maydon xatosi borligini tekshirish
     * @param {string} field - Maydon nomi
     * @returns {boolean}
     */
    const hasFieldError = (field) => {
        return !!errors.value[field];
    };

    return {
        // Holat
        form,
        isLoading,
        error,
        errors,
        isValid,

        // Metodlar
        resetForm,
        populateForm,
        setField,
        clearErrors,
        handleError,
        handleValidationErrors,
        submit,
        submitPost,
        submitPut,
        submitDelete,
        getFieldError,
        hasFieldError,
    };
}

/**
 * Modal ko'rinishini boshqarish uchun composable
 * @param {Object} props - Komponent props (show)
 * @param {Function} emit - Emit funksiyasi
 * @param {Object} formInstance - useModalForm dan qaytgan obyekt
 * @param {Object} options - Qo'shimcha sozlamalar
 */
export function useModalVisibility(props, emit, formInstance = null, options = {}) {
    const {
        onOpen = null,
        onClose = null,
        editItem = null, // ref yoki computed
    } = options;

    /**
     * Modalni yopish
     */
    const close = () => {
        emit('close');
        if (onClose) {
            onClose();
        }
    };

    /**
     * Esc tugmasi bilan yopish
     */
    const handleKeydown = (e) => {
        if (e.key === 'Escape' && props.show) {
            close();
        }
    };

    // Modal ochilganda/yopilganda
    watch(() => props.show, (newVal) => {
        if (newVal) {
            // Modal ochildi
            if (formInstance) {
                if (editItem?.value) {
                    formInstance.populateForm(editItem.value);
                } else {
                    formInstance.resetForm();
                }
            }
            if (onOpen) {
                onOpen();
            }
            // Esc tugmasini tinglash
            document.addEventListener('keydown', handleKeydown);
        } else {
            // Modal yopildi
            document.removeEventListener('keydown', handleKeydown);
        }
    });

    // Komponent yo'q qilinganda
    onUnmounted(() => {
        document.removeEventListener('keydown', handleKeydown);
    });

    return {
        close,
    };
}

/**
 * Tez sana tanlash uchun yordamchi
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
     * Kunlar qo'shilgan sanani olish
     * @param {number} days - Qo'shiladigan kunlar
     * @returns {string} YYYY-MM-DD formatida sana
     */
    const getDatePlusDays = (days) => {
        const date = new Date();
        date.setDate(date.getDate() + days);
        return date.toISOString().split('T')[0];
    };

    /**
     * Forma maydoniga tez sana o'rnatish
     * @param {Object} form - Forma ref
     * @param {string} field - Maydon nomi (default: 'due_date')
     * @param {number} days - Qo'shiladigan kunlar
     */
    const setQuickDate = (form, days, field = 'due_date') => {
        form.value[field] = getDatePlusDays(days);
    };

    return {
        quickDates,
        getDatePlusDays,
        setQuickDate,
    };
}

/**
 * Massiv maydonlarini boshqarish (subtasks, items va h.k.)
 * @param {Object} form - Forma ref
 * @param {string} field - Massiv maydon nomi
 */
export function useArrayField(form, field) {
    const newItem = ref('');

    /**
     * Yangi element qo'shish
     * @param {*} item - Qo'shiladigan element (default: { title: newItem.value })
     */
    const addItem = (item = null) => {
        if (!form.value[field]) {
            form.value[field] = [];
        }

        const itemToAdd = item || (newItem.value.trim() ? { title: newItem.value.trim() } : null);
        if (itemToAdd) {
            form.value[field].push(itemToAdd);
            newItem.value = '';
        }
    };

    /**
     * Elementni o'chirish
     * @param {number} index - Element indeksi
     */
    const removeItem = (index) => {
        if (form.value[field] && index >= 0 && index < form.value[field].length) {
            form.value[field].splice(index, 1);
        }
    };

    /**
     * Elementni yangilash
     * @param {number} index - Element indeksi
     * @param {*} value - Yangi qiymat
     */
    const updateItem = (index, value) => {
        if (form.value[field] && index >= 0 && index < form.value[field].length) {
            form.value[field][index] = value;
        }
    };

    /**
     * Elementlar sonini olish
     */
    const itemCount = computed(() => {
        return form.value[field]?.length || 0;
    });

    return {
        newItem,
        addItem,
        removeItem,
        updateItem,
        itemCount,
    };
}

export default {
    useModalForm,
    useModalVisibility,
    useQuickDates,
    useArrayField,
};
