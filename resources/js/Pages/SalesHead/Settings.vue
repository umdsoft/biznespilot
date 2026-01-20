<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { useI18n } from '@/i18n';
import {
    Cog6ToothIcon,
    BellIcon,
    UserGroupIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({
            notifications_enabled: true,
            daily_report_email: true,
            weekly_summary: true,
            team_alerts: true,
        }),
    },
});

const form = useForm({
    notifications_enabled: props.settings.notifications_enabled,
    daily_report_email: props.settings.daily_report_email,
    weekly_summary: props.settings.weekly_summary,
    team_alerts: props.settings.team_alerts,
});

const saveSettings = () => {
    form.post('/sales-head/settings', {
        preserveScroll: true,
    });
};

const settingSections = [
    {
        title: t('settings.notifications'),
        icon: BellIcon,
        settings: [
            { key: 'notifications_enabled', label: t('settings.all_notifications'), description: t('settings.all_notifications_desc') },
            { key: 'daily_report_email', label: t('settings.daily_report'), description: t('settings.daily_report_desc') },
            { key: 'weekly_summary', label: t('settings.weekly_summary'), description: t('settings.weekly_summary_desc') },
        ],
    },
    {
        title: t('saleshead.team'),
        icon: UserGroupIcon,
        settings: [
            { key: 'team_alerts', label: t('settings.team_alerts'), description: t('settings.team_alerts_desc') },
        ],
    },
];
</script>

<template>
    <SalesHeadLayout :title="t('settings.title')">
        <Head :title="t('settings.title')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center">
                    <Cog6ToothIcon class="w-6 h-6 text-gray-600 dark:text-gray-300" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('settings.title') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('saleshead.sales_department_settings') }}</p>
                </div>
            </div>

            <!-- Settings Sections -->
            <div class="space-y-6">
                <div
                    v-for="section in settingSections"
                    :key="section.title"
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700"
                >
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
                        <component :is="section.icon" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                        <h2 class="font-semibold text-gray-900 dark:text-white">{{ section.title }}</h2>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div
                            v-for="setting in section.settings"
                            :key="setting.key"
                            class="px-6 py-4 flex items-center justify-between"
                        >
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ setting.label }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ setting.description }}</p>
                            </div>
                            <button
                                type="button"
                                @click="form[setting.key] = !form[setting.key]"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="form[setting.key] ? 'bg-emerald-600' : 'bg-gray-200 dark:bg-gray-600'"
                            >
                                <span
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    :class="form[setting.key] ? 'translate-x-5' : 'translate-x-0'"
                                ></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end">
                <button
                    @click="saveSettings"
                    :disabled="form.processing"
                    class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white font-medium rounded-lg transition-colors"
                >
                    {{ form.processing ? t('common.saving') : t('common.save') }}
                </button>
            </div>
        </div>
    </SalesHeadLayout>
</template>
