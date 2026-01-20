<script setup>
import HRLayout from '@/layouts/HRLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import { ClipboardDocumentListIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();

defineProps({
    tasks: { type: Array, default: () => [] },
});
</script>

<template>
    <HRLayout :title="t('hr.tasks')">
        <Head :title="t('hr.tasks')" />

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ t('hr.tasks') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('hr.tasks_subtitle') }}</p>
            </div>

            <!-- Coming Soon -->
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-12 text-white text-center">
                <ClipboardDocumentListIcon class="w-16 h-16 mx-auto mb-4 opacity-80" />
                <h2 class="text-3xl font-bold mb-2">{{ t('hr.coming_soon') }}</h2>
                <p class="text-lg opacity-90">{{ t('hr.tasks_coming_soon') }}</p>
                <p class="text-sm opacity-75 mt-2">{{ t('hr.tasks') }}, {{ t('hr.assignment') }}, {{ t('hr.progress_tracking') }}</p>
            </div>
        </div>
    </HRLayout>
</template>
