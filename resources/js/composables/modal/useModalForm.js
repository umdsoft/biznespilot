/**
 * Modal forma boshqaruvi — asosiy composable
 * Forma holati, validatsiya, yuborish, xato boshqaruvi
 */

import { ref, computed } from 'vue';
import { refreshCsrfToken, isCsrfError } from '@/utils/csrf';

export function useModalForm(options = {}) {
    const {
        getDefaultForm = () => ({}),
        getEditForm = null,
        validate = () => true,
        transformPayload = (form) => form,
    } = options;

    // ═══ HOLAT ═══
    const form = ref(getDefaultForm());
    const isLoading = ref(false);
    const error = ref('');
    const errors = ref({});

    // ═══ FORMA BOSHQARUVI ═══
    const resetForm = () => {
        form.value = getDefaultForm();
        error.value = '';
        errors.value = {};
    };

    const populateForm = (item) => {
        if (item && getEditForm) {
            form.value = getEditForm(item);
        } else {
            resetForm();
        }
        error.value = '';
        errors.value = {};
    };

    const setField = (field, value) => {
        form.value[field] = value;
        if (errors.value[field]) {
            delete errors.value[field];
        }
    };

    const isValid = computed(() => validate(form.value));

    // ═══ XATO BOSHQARUVI ═══
    const clearErrors = () => {
        error.value = '';
        errors.value = {};
    };

    const handleValidationErrors = (err) => {
        if (err.response?.status === 422 && err.response?.data?.errors) {
            errors.value = err.response.data.errors;
            const firstError = Object.values(err.response.data.errors)[0];
            error.value = Array.isArray(firstError) ? firstError[0] : firstError;
        }
    };

    const handleError = async (err) => {
        console.error('Form error:', err);

        if (isCsrfError(err)) {
            error.value = "Sessiya muddati tugadi. Qayta urinib ko'ring.";
            await refreshCsrfToken();
            return error.value;
        }

        if (err.response?.status === 422) {
            handleValidationErrors(err);
            return error.value;
        }

        error.value = err.response?.data?.error
            || err.response?.data?.message
            || 'Tarmoq xatosi';

        return error.value;
    };

    const getFieldError = (field) => {
        const fieldError = errors.value[field];
        if (!fieldError) return null;
        return Array.isArray(fieldError) ? fieldError[0] : fieldError;
    };

    const hasFieldError = (field) => !!errors.value[field];

    // ═══ YUBORISH ═══
    const submit = async (submitFn, callbacks = {}) => {
        const { onSuccess, onError, onFinally } = callbacks;

        if (!isValid.value || isLoading.value) return null;

        isLoading.value = true;
        clearErrors();

        try {
            await refreshCsrfToken();
            const payload = transformPayload({ ...form.value });
            const response = await submitFn(payload);

            if (response?.data?.success !== false) {
                if (onSuccess) onSuccess(response?.data);
                return response?.data;
            }

            error.value = response?.data?.error
                || response?.data?.message
                || 'Xatolik yuz berdi';
            if (onError) onError(error.value);
            return null;
        } catch (err) {
            await handleError(err);
            if (onError) onError(error.value);
            return null;
        } finally {
            isLoading.value = false;
            if (onFinally) onFinally();
        }
    };

    const submitPost = (url, callbacks) =>
        submit((payload) => window.axios.post(url, payload), callbacks);

    const submitPut = (url, callbacks) =>
        submit((payload) => window.axios.put(url, payload), callbacks);

    const submitDelete = (url, callbacks) =>
        submit(() => window.axios.delete(url), callbacks);

    return {
        // Holat
        form, isLoading, error, errors, isValid,
        // Forma
        resetForm, populateForm, setField,
        // Xato
        clearErrors, handleError, handleValidationErrors,
        getFieldError, hasFieldError,
        // Yuborish
        submit, submitPost, submitPut, submitDelete,
    };
}
