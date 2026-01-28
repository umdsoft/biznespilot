/**
 * useSubscription - Tarif ma'lumotlarini olish uchun composable
 *
 * Bu composable Inertia page props orqali tarif limitlari va
 * feature'larni oson ishlatish imkonini beradi.
 *
 * Ishlatilishi:
 *
 * ```vue
 * <script setup>
 * import { useSubscription } from '@/composables/useSubscription';
 *
 * const {
 *   hasSubscription,
 *   plan,
 *   features,
 *   limits,
 *   hasFeature,
 *   canAdd,
 *   getUsage,
 *   getRemaining,
 *   isLimitExceeded,
 *   isLimitWarning,
 * } = useSubscription();
 * </script>
 *
 * <template>
 *   <div v-if="hasFeature('hr_tasks')">
 *     HR vazifalar mavjud
 *   </div>
 *
 *   <button
 *     :disabled="!canAdd('users')"
 *     @click="addUser"
 *   >
 *     Xodim qo'shish ({{ getRemaining('users') }} ta qoldi)
 *   </button>
 * </template>
 * ```
 */

import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useSubscription() {
    const page = usePage();

    // Raw subscription data from Inertia props
    const subscriptionData = computed(() => page.props.subscription || {});

    // Has active subscription
    const hasSubscription = computed(() => subscriptionData.value.has_subscription ?? false);

    // Current plan info
    const plan = computed(() => subscriptionData.value.plan || null);

    // Plan name
    const planName = computed(() => plan.value?.name || 'Bepul');

    // Plan slug
    const planSlug = computed(() => plan.value?.slug || 'free');

    // Subscription info
    const subscription = computed(() => subscriptionData.value.subscription || null);

    // Is on trial
    const isTrial = computed(() => subscription.value?.is_trial ?? false);

    // Days remaining
    const daysRemaining = computed(() => subscription.value?.days_remaining ?? 0);

    // Features object (boolean values) - v-if="features.hr_tasks" formatida
    const features = computed(() => subscriptionData.value.features || {});

    // Features detail (with labels and descriptions)
    const featuresDetail = computed(() => subscriptionData.value.features_detail || {});

    // Limits object (with usage stats)
    const limits = computed(() => subscriptionData.value.limits || {});

    /**
     * Feature yoqilganmi tekshirish
     * @param {string} featureKey - Feature kaliti (masalan: 'hr_tasks')
     * @returns {boolean}
     */
    const hasFeature = (featureKey) => {
        return features.value[featureKey] ?? false;
    };

    /**
     * Qo'shish mumkinmi tekshirish (limit bo'yicha)
     * @param {string} limitKey - Limit kaliti (masalan: 'users')
     * @param {number} count - Qo'shilmoqchi bo'lgan miqdor (default: 1)
     * @returns {boolean}
     */
    const canAdd = (limitKey, count = 1) => {
        const limitData = limits.value[limitKey];
        if (!limitData) return true; // No limit data = allow

        if (limitData.is_unlimited) return true;

        const remaining = limitData.remaining ?? 0;
        return remaining >= count;
    };

    /**
     * Joriy foydalanishni olish
     * @param {string} limitKey - Limit kaliti
     * @returns {number}
     */
    const getUsage = (limitKey) => {
        return limits.value[limitKey]?.current ?? 0;
    };

    /**
     * Limitni olish
     * @param {string} limitKey - Limit kaliti
     * @returns {number|string} - Raqam yoki 'Cheksiz'
     */
    const getLimit = (limitKey) => {
        const limitData = limits.value[limitKey];
        if (!limitData) return 0;

        return limitData.is_unlimited ? 'Cheksiz' : (limitData.limit ?? 0);
    };

    /**
     * Qolgan kvotani olish
     * @param {string} limitKey - Limit kaliti
     * @returns {number|null} - null = cheksiz
     */
    const getRemaining = (limitKey) => {
        const limitData = limits.value[limitKey];
        if (!limitData) return null;

        if (limitData.is_unlimited) return null;

        return limitData.remaining ?? 0;
    };

    /**
     * Foiz ko'rinishida foydalanishni olish
     * @param {string} limitKey - Limit kaliti
     * @returns {number} - 0-100 oralig'ida
     */
    const getPercentage = (limitKey) => {
        return limits.value[limitKey]?.percentage ?? 0;
    };

    /**
     * Limit oshib ketganmi tekshirish
     * @param {string} limitKey - Limit kaliti
     * @returns {boolean}
     */
    const isLimitExceeded = (limitKey) => {
        return limits.value[limitKey]?.is_exceeded ?? false;
    };

    /**
     * Limit ogohlantirish holatidami tekshirish (80%+)
     * @param {string} limitKey - Limit kaliti
     * @returns {boolean}
     */
    const isLimitWarning = (limitKey) => {
        return limits.value[limitKey]?.is_warning ?? false;
    };

    /**
     * Limit cheksizmi tekshirish
     * @param {string} limitKey - Limit kaliti
     * @returns {boolean}
     */
    const isUnlimited = (limitKey) => {
        return limits.value[limitKey]?.is_unlimited ?? false;
    };

    /**
     * Feature label olish
     * @param {string} featureKey - Feature kaliti
     * @returns {string}
     */
    const getFeatureLabel = (featureKey) => {
        return featuresDetail.value[featureKey]?.label ?? featureKey;
    };

    /**
     * Limit label olish
     * @param {string} limitKey - Limit kaliti
     * @returns {string}
     */
    const getLimitLabel = (limitKey) => {
        return limits.value[limitKey]?.label ?? limitKey;
    };

    /**
     * Usage display string (masalan: "5/10 ta")
     * @param {string} limitKey - Limit kaliti
     * @returns {string}
     */
    const getUsageDisplay = (limitKey) => {
        const limitData = limits.value[limitKey];
        if (!limitData) return '0';

        const suffix = limitData.suffix ?? '';
        const current = limitData.current ?? 0;

        if (limitData.is_unlimited) {
            return `${current} ${suffix}`.trim();
        }

        return `${current}/${limitData.limit} ${suffix}`.trim();
    };

    return {
        // Raw data
        subscriptionData,
        hasSubscription,
        plan,
        planName,
        planSlug,
        subscription,
        isTrial,
        daysRemaining,
        features,
        featuresDetail,
        limits,

        // Feature methods
        hasFeature,
        getFeatureLabel,

        // Limit methods
        canAdd,
        getUsage,
        getLimit,
        getRemaining,
        getPercentage,
        isLimitExceeded,
        isLimitWarning,
        isUnlimited,
        getLimitLabel,
        getUsageDisplay,
    };
}

export default useSubscription;
