<script setup>
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    survey: Object,
});
</script>

<template>
    <Head :title="`Rahmat - ${survey.title}`" />

    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center px-4">
        <div class="max-w-lg w-full">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 text-center">
                <!-- Success Icon -->
                <div class="mb-6">
                    <div
                        class="w-20 h-20 rounded-full flex items-center justify-center mx-auto animate-bounce"
                        :style="{ backgroundColor: (survey.theme_color || '#10B981') + '20' }"
                    >
                        <svg
                            class="w-10 h-10"
                            :style="{ color: survey.theme_color || '#10B981' }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>

                <!-- Thank You Message -->
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-3">
                    Katta rahmat!
                </h1>

                <p v-if="survey.thank_you_message" class="text-gray-600 dark:text-gray-400 mb-6">
                    {{ survey.thank_you_message }}
                </p>
                <p v-else class="text-gray-600 dark:text-gray-400 mb-6">
                    Javoblaringiz uchun katta rahmat! Sizning fikringiz biz uchun juda qimmatli.
                </p>

                <!-- Decorative Element -->
                <div class="flex justify-center gap-2 mb-6">
                    <div
                        v-for="i in 3"
                        :key="i"
                        class="w-2 h-2 rounded-full animate-pulse"
                        :style="{ backgroundColor: survey.theme_color || '#10B981', animationDelay: `${i * 0.2}s` }"
                    ></div>
                </div>

                <!-- Survey Info -->
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 mb-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <strong class="text-gray-700 dark:text-gray-300">{{ survey.title }}</strong>
                    </p>
                </div>

                <!-- Close Button -->
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Bu sahifani yopishingiz mumkin
                </p>
            </div>

            <!-- Footer -->
            <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-6">
                BiznesPilot AI bilan yaratilgan
            </p>
        </div>
    </div>
</template>

<style scoped>
@keyframes bounce {
    0%, 100% {
        transform: translateY(-5%);
    }
    50% {
        transform: translateY(0);
    }
}

.animate-bounce {
    animation: bounce 2s infinite;
}
</style>
