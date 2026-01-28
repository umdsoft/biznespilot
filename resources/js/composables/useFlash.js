/**
 * useFlash - Flash xabarlarni boshqarish uchun composable
 *
 * Bu composable Inertia page props orqali flash xabarlarni
 * oson ishlatish va boshqarish imkonini beradi.
 *
 * Ishlatilishi:
 *
 * ```vue
 * <script setup>
 * import { useFlash } from '@/composables/useFlash';
 *
 * const {
 *   success,
 *   error,
 *   warning,
 *   info,
 *   hasFlash,
 *   upgradeRequired,
 *   upgradeData,
 * } = useFlash();
 * </script>
 *
 * <template>
 *   <div v-if="hasFlash('success')" class="alert alert-success">
 *     {{ success }}
 *   </div>
 *
 *   <UpgradeModal v-if="upgradeRequired" :data="upgradeData" />
 * </template>
 * ```
 */

import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useFlash() {
    const page = usePage();

    // Flash xabarlar
    const flash = computed(() => page.props.flash || {});

    // Success xabar
    const success = computed(() => flash.value.success);

    // Error xabar
    const error = computed(() => flash.value.error);

    // Warning xabar
    const warning = computed(() => flash.value.warning);

    // Info xabar
    const info = computed(() => flash.value.info);

    // Upgrade required flag
    const upgradeRequired = computed(() => flash.value.upgrade_required ?? false);

    // Upgrade data (limit yoki feature ma'lumotlari)
    const upgradeData = computed(() => flash.value.upgrade_data || null);

    /**
     * Flash xabar mavjudligini tekshirish
     * @param {string} type - Flash turi ('success', 'error', 'warning', 'info')
     * @returns {boolean}
     */
    const hasFlash = (type) => {
        return !!flash.value[type];
    };

    /**
     * Barcha flash xabarlarni olish
     * @returns {object}
     */
    const getAllFlash = () => {
        return {
            success: success.value,
            error: error.value,
            warning: warning.value,
            info: info.value,
        };
    };

    /**
     * Birinchi mavjud flash xabarni olish
     * @returns {object|null} - { type, message } yoki null
     */
    const getFirstFlash = () => {
        if (success.value) return { type: 'success', message: success.value };
        if (error.value) return { type: 'error', message: error.value };
        if (warning.value) return { type: 'warning', message: warning.value };
        if (info.value) return { type: 'info', message: info.value };
        return null;
    };

    /**
     * Flash xabar turi bo'yicha rang olish
     * @param {string} type - Flash turi
     * @returns {object} - Tailwind class'lar
     */
    const getFlashStyles = (type) => {
        const styles = {
            success: {
                bg: 'bg-green-50 dark:bg-green-900/20',
                border: 'border-green-200 dark:border-green-800',
                text: 'text-green-800 dark:text-green-200',
                icon: 'text-green-500',
            },
            error: {
                bg: 'bg-red-50 dark:bg-red-900/20',
                border: 'border-red-200 dark:border-red-800',
                text: 'text-red-800 dark:text-red-200',
                icon: 'text-red-500',
            },
            warning: {
                bg: 'bg-amber-50 dark:bg-amber-900/20',
                border: 'border-amber-200 dark:border-amber-800',
                text: 'text-amber-800 dark:text-amber-200',
                icon: 'text-amber-500',
            },
            info: {
                bg: 'bg-blue-50 dark:bg-blue-900/20',
                border: 'border-blue-200 dark:border-blue-800',
                text: 'text-blue-800 dark:text-blue-200',
                icon: 'text-blue-500',
            },
        };

        return styles[type] || styles.info;
    };

    return {
        // Raw flash data
        flash,

        // Individual flash messages
        success,
        error,
        warning,
        info,

        // Upgrade related
        upgradeRequired,
        upgradeData,

        // Helper methods
        hasFlash,
        getAllFlash,
        getFirstFlash,
        getFlashStyles,
    };
}

export default useFlash;
