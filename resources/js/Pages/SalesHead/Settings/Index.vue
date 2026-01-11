<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import {
    Cog6ToothIcon,
    BellIcon,
    SunIcon,
    MoonIcon,
    GlobeAltIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({
            notifications: {
                new_lead: true,
                task_reminder: true,
                deal_closed: true,
                team_updates: true,
            },
            language: 'uz',
            theme: 'auto',
        }),
    },
});

const form = useForm({
    notifications: { ...props.settings.notifications },
    language: props.settings.language,
    theme: props.settings.theme,
});

const isDarkMode = ref(document.documentElement.classList.contains('dark'));

const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('darkMode', 'true');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('darkMode', 'false');
    }
};

const saveSettings = () => {
    form.put('/sales-head/settings', {
        preserveScroll: true,
    });
};
</script>

<template>
    <SalesHeadLayout title="Sozlamalar">
        <Head title="Sozlamalar" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sozlamalar</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Panel sozlamalarini boshqaring</p>
            </div>

            <!-- Theme Settings -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <SunIcon class="w-5 h-5" />
                    Ko'rinish
                </h3>

                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">Qorong'i rejim</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Tungi ko'rinishga o'tish</p>
                    </div>
                    <button
                        @click="toggleDarkMode"
                        :class="[
                            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                            isDarkMode ? 'bg-emerald-600' : 'bg-gray-200 dark:bg-gray-700'
                        ]"
                    >
                        <span
                            :class="[
                                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                isDarkMode ? 'translate-x-6' : 'translate-x-1'
                            ]"
                        />
                    </button>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <BellIcon class="w-5 h-5" />
                    Bildirishnomalar
                </h3>

                <div class="space-y-4">
                    <div class="flex items-center justify-between py-2">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Yangi lead</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Yangi lead kelganda xabar berish</p>
                        </div>
                        <button
                            @click="form.notifications.new_lead = !form.notifications.new_lead"
                            :class="[
                                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                                form.notifications.new_lead ? 'bg-emerald-600' : 'bg-gray-200 dark:bg-gray-700'
                            ]"
                        >
                            <span
                                :class="[
                                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                    form.notifications.new_lead ? 'translate-x-6' : 'translate-x-1'
                                ]"
                            />
                        </button>
                    </div>

                    <div class="flex items-center justify-between py-2">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Vazifa eslatmasi</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Vazifa muddati yaqinlashganda eslatish</p>
                        </div>
                        <button
                            @click="form.notifications.task_reminder = !form.notifications.task_reminder"
                            :class="[
                                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                                form.notifications.task_reminder ? 'bg-emerald-600' : 'bg-gray-200 dark:bg-gray-700'
                            ]"
                        >
                            <span
                                :class="[
                                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                    form.notifications.task_reminder ? 'translate-x-6' : 'translate-x-1'
                                ]"
                            />
                        </button>
                    </div>

                    <div class="flex items-center justify-between py-2">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Bitim yopildi</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Bitim yutilganda yoki yo'qotilganda xabar berish</p>
                        </div>
                        <button
                            @click="form.notifications.deal_closed = !form.notifications.deal_closed"
                            :class="[
                                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                                form.notifications.deal_closed ? 'bg-emerald-600' : 'bg-gray-200 dark:bg-gray-700'
                            ]"
                        >
                            <span
                                :class="[
                                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                    form.notifications.deal_closed ? 'translate-x-6' : 'translate-x-1'
                                ]"
                            />
                        </button>
                    </div>

                    <div class="flex items-center justify-between py-2">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Jamoa yangiliklari</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jamoa faoliyati haqida xabar berish</p>
                        </div>
                        <button
                            @click="form.notifications.team_updates = !form.notifications.team_updates"
                            :class="[
                                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                                form.notifications.team_updates ? 'bg-emerald-600' : 'bg-gray-200 dark:bg-gray-700'
                            ]"
                        >
                            <span
                                :class="[
                                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                    form.notifications.team_updates ? 'translate-x-6' : 'translate-x-1'
                                ]"
                            />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Language Settings -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <GlobeAltIcon class="w-5 h-5" />
                    Til
                </h3>

                <div class="max-w-xs">
                    <select
                        v-model="form.language"
                        class="w-full px-4 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                    >
                        <option value="uz">O'zbek tili</option>
                        <option value="ru">Русский</option>
                        <option value="en">English</option>
                    </select>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end">
                <button
                    @click="saveSettings"
                    :disabled="form.processing"
                    class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
                >
                    {{ form.processing ? 'Saqlanmoqda...' : 'Sozlamalarni saqlash' }}
                </button>
            </div>
        </div>
    </SalesHeadLayout>
</template>
