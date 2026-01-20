<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import {
    ClockIcon,
    MapPinIcon,
    Cog6ToothIcon,
    CheckCircleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    settings: { type: Object, required: true },
});

const form = useForm({
    work_start_time: props.settings.work_start_time || '09:00',
    work_end_time: props.settings.work_end_time || '18:00',
    work_hours_per_day: props.settings.work_hours_per_day || 8,
    late_threshold_minutes: props.settings.late_threshold_minutes || 15,
    require_location: props.settings.require_location || false,
    allow_remote_checkin: props.settings.allow_remote_checkin || true,
});

const saving = ref(false);

const saveSettings = () => {
    if (saving.value) return;

    saving.value = true;

    form.put(route('hr.attendance.settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success
        },
        onError: (errors) => {
            console.error('Sozlamalarni saqlashda xatolik:', errors);
        },
        onFinish: () => {
            saving.value = false;
        },
    });
};

const workHoursText = computed(() => {
    return `${form.work_start_time} - ${form.work_end_time}`;
});

const resetToDefaults = () => {
    if (confirm('Sozlamalarni standart qiymatlariga qaytarmoqchimisiz?')) {
        form.work_start_time = '09:00';
        form.work_end_time = '18:00';
        form.work_hours_per_day = 8;
        form.late_threshold_minutes = 15;
        form.require_location = false;
        form.allow_remote_checkin = true;
    }
};
</script>

<template>
    <HRLayout :title="t('hr.attendance_settings')">
        <Head :title="t('hr.attendance_settings')" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ t('hr.attendance_settings_title') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('hr.attendance_settings_desc') }}</p>
                </div>
                <button
                    @click="resetToDefaults"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                >
                    {{ t('hr.default_values') }}
                </button>
            </div>

            <!-- Settings Form -->
            <form @submit.prevent="saveSettings" class="space-y-6">
                <!-- Work Hours Section -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                            <ClockIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Ish vaqti</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Standart ish soatlari</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Work Start Time -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Ish boshlanish vaqti
                            </label>
                            <input
                                v-model="form.work_start_time"
                                type="time"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.work_start_time }"
                            />
                            <p v-if="form.errors.work_start_time" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.work_start_time }}
                            </p>
                        </div>

                        <!-- Work End Time -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Ish tugash vaqti
                            </label>
                            <input
                                v-model="form.work_end_time"
                                type="time"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.work_end_time }"
                            />
                            <p v-if="form.errors.work_end_time" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.work_end_time }}
                            </p>
                        </div>

                        <!-- Work Hours Per Day -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Kunlik ish soati
                            </label>
                            <input
                                v-model.number="form.work_hours_per_day"
                                type="number"
                                min="1"
                                max="24"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.work_hours_per_day }"
                            />
                            <p v-if="form.errors.work_hours_per_day" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.work_hours_per_day }}
                            </p>
                        </div>
                    </div>

                    <!-- Work Hours Summary -->
                    <div class="mt-4 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-medium">Ish vaqti:</span> {{ workHoursText }} ({{ form.work_hours_per_day }} soat)
                        </p>
                    </div>
                </div>

                <!-- Late Threshold Section -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                            <CheckCircleIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Kechikish chegarasi</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Qachon kechikkan deb hisoblanadi</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Kechikish chegarasi (daqiqalarda)
                        </label>
                        <input
                            v-model.number="form.late_threshold_minutes"
                            type="number"
                            min="0"
                            max="120"
                            class="w-full max-w-xs px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent"
                            :class="{ 'border-red-500': form.errors.late_threshold_minutes }"
                        />
                        <p v-if="form.errors.late_threshold_minutes" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.late_threshold_minutes }}
                        </p>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Xodim {{ form.late_threshold_minutes }} daqiqadan ko'proq kechiksa, "Kechikkan" deb belgilanadi
                        </p>
                    </div>
                </div>

                <!-- Location & Remote Section -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <MapPinIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Joylashuv va masofaviy ish</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">GPS va masofaviy check-in sozlamalari</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- Require Location -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">Joylashuv majburiy</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Check-in qilishda GPS joylashuv talab qilinsin</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    v-model="form.require_location"
                                    type="checkbox"
                                    class="sr-only peer"
                                />
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                            </label>
                        </div>

                        <!-- Allow Remote Check-in -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">Masofaviy check-in ruxsat</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Xodimlar istalgan joydan check-in qila olsinlar</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    v-model="form.allow_remote_checkin"
                                    type="checkbox"
                                    class="sr-only peer"
                                />
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-3">
                    <button
                        type="submit"
                        :disabled="saving || form.processing"
                        class="px-6 py-3 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                        <Cog6ToothIcon v-if="saving" class="w-5 h-5 animate-spin" />
                        <CheckCircleIcon v-else class="w-5 h-5" />
                        <span v-if="saving">Saqlanmoqda...</span>
                        <span v-else>Sozlamalarni saqlash</span>
                    </button>
                </div>
            </form>
        </div>
    </HRLayout>
</template>
