<script setup>
/**
 * UpgradeModal - Tarif limit yoki feature cheklanganda chiqadigan modal
 *
 * Bu komponent flash xabarlar orqali avtomatik ochiladi va
 * foydalanuvchini pricing sahifasiga yo'naltiradi.
 */
import { ref, computed, watch, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Modal from './Modal.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();
const page = usePage();

const isOpen = ref(false);
const upgradeData = ref(null);

// Flash xabarlarni kuzatish
const flashData = computed(() => ({
    error: page.props.flash?.error,
    warning: page.props.flash?.warning,
    upgrade_required: page.props.flash?.upgrade_required,
    upgrade_data: page.props.flash?.upgrade_data,
}));

// Flash o'zgarganda modalni ochish
watch(
    () => flashData.value.upgrade_required,
    (newVal) => {
        if (newVal) {
            upgradeData.value = flashData.value.upgrade_data || {};
            isOpen.value = true;
        }
    },
    { immediate: true }
);

// Modal yopilganda flash xabarni tozalash
const closeModal = () => {
    isOpen.value = false;
    upgradeData.value = null;
};

// Pricing sahifasiga o'tish
const goToPricing = () => {
    closeModal();
    router.visit('/pricing');
};

// Upgrade turi bo'yicha matn va icon
const upgradeInfo = computed(() => {
    if (!upgradeData.value) {
        return {
            icon: 'limit',
            title: "Tarif cheklovi",
            description: "Sizning tarifingizda bu imkoniyat cheklangan.",
            detail: null,
        };
    }

    const type = upgradeData.value.type;

    if (type === 'quota') {
        const limitLabel = upgradeData.value.limit_label || upgradeData.value.limit_key;
        const limit = upgradeData.value.limit;
        const current = upgradeData.value.current_usage;

        return {
            icon: 'limit',
            title: "Limit tugadi",
            description: `"${limitLabel}" limiti to'ldi.`,
            detail: limit !== undefined && current !== undefined
                ? `Joriy: ${current}/${limit}`
                : null,
        };
    }

    if (type === 'feature') {
        const featureLabel = upgradeData.value.feature_label || upgradeData.value.feature_key;

        return {
            icon: 'feature',
            title: "Imkoniyat mavjud emas",
            description: `"${featureLabel}" sizning tarifingizda mavjud emas.`,
            detail: null,
        };
    }

    if (type === 'no_subscription') {
        return {
            icon: 'subscription',
            title: "Obuna talab qilinadi",
            description: "Bu funksiyadan foydalanish uchun obuna bo'lishingiz kerak.",
            detail: null,
        };
    }

    return {
        icon: 'limit',
        title: "Tarif cheklovi",
        description: flashData.value.error || flashData.value.warning || "Sizning tarifingizda bu imkoniyat cheklangan.",
        detail: null,
    };
});
</script>

<template>
    <Modal
        v-model="isOpen"
        max-width="md"
        :closeable="true"
        @close="closeModal"
    >
        <div class="text-center py-4">
            <!-- Icon -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full mb-6"
                 :class="{
                     'bg-amber-100': upgradeInfo.icon === 'limit',
                     'bg-purple-100': upgradeInfo.icon === 'feature',
                     'bg-blue-100': upgradeInfo.icon === 'subscription',
                 }">
                <!-- Limit Icon -->
                <svg v-if="upgradeInfo.icon === 'limit'"
                     class="h-8 w-8 text-amber-600"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>

                <!-- Feature Icon -->
                <svg v-else-if="upgradeInfo.icon === 'feature'"
                     class="h-8 w-8 text-purple-600"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>

                <!-- Subscription Icon -->
                <svg v-else
                     class="h-8 w-8 text-blue-600"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
            </div>

            <!-- Title -->
            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                {{ upgradeInfo.title }}
            </h3>

            <!-- Description -->
            <p class="text-gray-600 dark:text-gray-400 mb-2">
                {{ upgradeInfo.description }}
            </p>

            <!-- Detail (if exists) -->
            <p v-if="upgradeInfo.detail"
               class="text-sm text-gray-500 dark:text-gray-500 mb-4">
                {{ upgradeInfo.detail }}
            </p>

            <!-- Upgrade CTA -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-4 mb-6">
                <p class="text-sm text-blue-800 dark:text-blue-300">
                    <span class="font-semibold">Yangi imkoniyatlar uchun tarifni yangilang!</span>
                    <br>
                    <span class="text-blue-600 dark:text-blue-400">
                        Ko'proq foydalanuvchilar, ko'proq imkoniyatlar.
                    </span>
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <button
                    @click="closeModal"
                    class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl transition-colors"
                >
                    Keyinroq
                </button>

                <button
                    @click="goToPricing"
                    class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 rounded-xl shadow-lg shadow-blue-500/25 transition-all transform hover:scale-[1.02]"
                >
                    <span class="flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                        Tariflarni ko'rish
                    </span>
                </button>
            </div>
        </div>
    </Modal>
</template>
