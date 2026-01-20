<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    survey: Object,
});

const statusMessage = computed(() => {
    if (!props.survey) {
        return {
            title: t('public_survey.status.not_found_title'),
            description: t('public_survey.status.not_found_desc'),
            icon: 'notfound',
        };
    }

    switch (props.survey.status) {
        case 'draft':
            return {
                title: t('public_survey.status.draft_title'),
                description: t('public_survey.status.draft_desc'),
                icon: 'draft',
            };
        case 'paused':
            return {
                title: t('public_survey.status.paused_title'),
                description: t('public_survey.status.paused_desc'),
                icon: 'paused',
            };
        case 'completed':
            return {
                title: t('public_survey.status.completed_title'),
                description: t('public_survey.status.completed_desc'),
                icon: 'completed',
            };
        default:
            if (props.survey.expires_at) {
                return {
                    title: t('public_survey.status.expired_title'),
                    description: t('public_survey.status.expired_desc'),
                    icon: 'expired',
                };
            }
            return {
                title: t('public_survey.status.inactive_title'),
                description: t('public_survey.status.inactive_desc'),
                icon: 'inactive',
            };
    }
});

const themeColor = computed(() => props.survey?.theme_color || '#6366f1');
</script>

<template>
    <Head :title="survey?.title || t('public_survey.survey')" />

    <div class="min-h-screen flex items-center justify-center p-4" :style="{ background: `linear-gradient(135deg, ${themeColor}15 0%, ${themeColor}05 100%)` }">
        <div class="max-w-md w-full">
            <!-- Card -->
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
                <!-- Header with icon -->
                <div class="p-8 text-center" :style="{ background: `linear-gradient(135deg, ${themeColor} 0%, ${themeColor}dd 100%)` }">
                    <!-- Icon based on status -->
                    <div class="w-20 h-20 mx-auto rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center mb-4">
                        <!-- Draft icon -->
                        <svg v-if="statusMessage.icon === 'draft'" class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <!-- Paused icon -->
                        <svg v-else-if="statusMessage.icon === 'paused'" class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <!-- Completed icon -->
                        <svg v-else-if="statusMessage.icon === 'completed'" class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <!-- Expired icon -->
                        <svg v-else-if="statusMessage.icon === 'expired'" class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <!-- Not found / Inactive icon -->
                        <svg v-else class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>

                    <h1 class="text-2xl font-bold text-white mb-2">{{ statusMessage.title }}</h1>
                </div>

                <!-- Body -->
                <div class="p-8 text-center">
                    <p class="text-gray-600 mb-6">{{ statusMessage.description }}</p>

                    <div v-if="survey?.title" class="mb-6 p-4 bg-gray-50 rounded-xl">
                        <p class="text-sm text-gray-500 mb-1">{{ t('public_survey.survey_name') }}:</p>
                        <p class="font-semibold text-gray-900">{{ survey.title }}</p>
                    </div>

                    <div v-if="survey?.expires_at" class="mb-6 p-4 bg-amber-50 rounded-xl border border-amber-100">
                        <p class="text-sm text-amber-600">
                            {{ t('public_survey.expires_at') }}: {{ new Date(survey.expires_at).toLocaleDateString('uz-UZ') }}
                        </p>
                    </div>

                    <!-- Back button -->
                    <button
                        @click="window.history.back()"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        {{ t('public_survey.go_back') }}
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500">
                    Powered by <span class="font-semibold" :style="{ color: themeColor }">BiznesPilot</span>
                </p>
            </div>
        </div>
    </div>
</template>
