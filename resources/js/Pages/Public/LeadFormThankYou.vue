<script setup>
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    leadForm: Object,
});

const themeColor = props.leadForm?.theme_color || '#6366f1';
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center p-4">
        <Head :title="t('public_form.thank_you')" />

        <div class="max-w-md w-full">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                <div class="p-8 text-center">
                    <!-- Success Icon -->
                    <div
                        class="w-20 h-20 mx-auto rounded-full flex items-center justify-center mb-6"
                        :style="{ backgroundColor: themeColor + '20' }"
                    >
                        <svg
                            class="w-10 h-10"
                            :style="{ color: themeColor }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>

                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">{{ t('public_form.thank_you') }}</h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ leadForm?.success_message || t('public_form.success_message') }}
                    </p>
                </div>
            </div>

            <!-- Powered by -->
            <div class="mt-6 text-center">
                <a
                    href="https://biznespilot.uz"
                    target="_blank"
                    class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                >
                    Powered by BiznesPilot
                </a>
            </div>
        </div>
    </div>
</template>
