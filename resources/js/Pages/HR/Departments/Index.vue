<script setup>
import HRLayout from '@/layouts/HRLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import { BuildingOfficeIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();
</script>

<template>
    <HRLayout :title="t('hr.departments')">
        <Head :title="t('hr.departments')" />

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ t('hr.departments') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('hr.department_structure') }}</p>
            </div>

            <!-- Departments Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Sales Department -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <BuildingOfficeIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Sotuv Bo'limi</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Sales Department</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Lead boshqaruvi, sotuv jarayonlari, mijozlar bilan ishlash
                    </p>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">{{ t('hr.employees') }}:</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">-</span>
                    </div>
                </div>

                <!-- HR Department -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                            <BuildingOfficeIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">HR Bo'limi</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Human Resources</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Xodimlar boshqaruvi, davomat, ta'til, samaradorlik nazorati
                    </p>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">{{ t('hr.employees') }}:</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">-</span>
                    </div>
                </div>

                <!-- Marketing Department -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-pink-100 dark:bg-pink-900/30 rounded-lg flex items-center justify-center">
                            <BuildingOfficeIcon class="w-6 h-6 text-pink-600 dark:text-pink-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Marketing Bo'limi</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Marketing Department</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Kampaniyalar, ijtimoiy tarmoqlar, reklama, brendlash
                    </p>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">{{ t('hr.employees') }}:</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">-</span>
                    </div>
                </div>

                <!-- Finance Department -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <BuildingOfficeIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Moliya Bo'limi</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Finance Department</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Byudjet, hisobotlar, moliyaviy tahlil, to'lovlar
                    </p>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">{{ t('hr.employees') }}:</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">-</span>
                    </div>
                </div>
            </div>

            <!-- Note -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
                <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-2">{{ t('hr.departments') }} haqida</h4>
                <p class="text-sm text-blue-800 dark:text-blue-300">
                    Bo'limlar - bu kompaniya ichidagi tashkiliy tuzilma bo'lib, xodimlarni vazifalariga ko'ra guruhlaydi.
                    Har bir xodim bitta bo'limga tegishli bo'ladi va o'z bo'limiga mos keladigan funksiyalarga kirish huquqiga ega bo'ladi.
                </p>
            </div>
        </div>
    </HRLayout>
</template>
