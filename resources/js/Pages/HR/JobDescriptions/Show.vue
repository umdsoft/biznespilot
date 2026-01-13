<script setup>
import { Link } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    BriefcaseIcon,
    BuildingOfficeIcon,
    MapPinIcon,
    BanknotesIcon,
    ClockIcon,
    UserIcon,
    ArrowLeftIcon,
    CheckCircleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    jobDescription: { type: Object, required: true },
    departments: { type: Object, default: () => ({}) },
    positionLevels: { type: Object, default: () => ({}) },
    employmentTypes: { type: Object, default: () => ({}) },
});

// Parse multiline text into list items
const parseList = (text) => {
    if (!text) return [];
    return text.split('\n').filter(line => line.trim() !== '');
};
</script>

<template>
    <HRLayout title="Lavozim Tafsilotlari">
        <Head :title="`${jobDescription.title} - Lavozim Tafsilotlari`" />

        <div class="space-y-6">
            <!-- Back Button -->
            <Link
                :href="route('hr.job-descriptions.index')"
                class="inline-flex items-center gap-2 text-purple-600 hover:text-purple-700 transition-colors"
            >
                <ArrowLeftIcon class="w-5 h-5" />
                <span>Lavozimlar ro'yxatiga qaytish</span>
            </Link>

            <!-- Header -->
            <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl p-8 text-white shadow-xl">
                <div class="flex items-start justify-between">
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center">
                            <BriefcaseIcon class="w-8 h-8" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold mb-2">{{ jobDescription.title }}</h1>
                            <div class="flex flex-wrap items-center gap-4 text-sm opacity-90">
                                <div class="flex items-center gap-2">
                                    <BuildingOfficeIcon class="w-5 h-5" />
                                    {{ jobDescription.department_label }}
                                </div>
                                <span v-if="jobDescription.position_level_label">•</span>
                                <div v-if="jobDescription.position_level_label" class="flex items-center gap-2">
                                    {{ jobDescription.position_level_label }}
                                </div>
                                <span>•</span>
                                <div class="flex items-center gap-2">
                                    <ClockIcon class="w-5 h-5" />
                                    {{ jobDescription.employment_type_label }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <span
                            :class="[
                                'inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium',
                                jobDescription.is_active
                                    ? 'bg-green-500 text-white'
                                    : 'bg-gray-400 text-white'
                            ]"
                        >
                            <component :is="jobDescription.is_active ? CheckCircleIcon : XCircleIcon" class="w-5 h-5" />
                            {{ jobDescription.is_active ? 'Faol' : 'Faolsiz' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Key Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div v-if="jobDescription.location" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <MapPinIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Joylashuv</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ jobDescription.location }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="jobDescription.salary_range_formatted" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <BanknotesIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Maosh oralig'i</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ jobDescription.salary_range_formatted }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="jobDescription.reports_to" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                            <UserIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Kim bilan ishlaydi</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ jobDescription.reports_to }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Job Summary -->
            <div v-if="jobDescription.job_summary" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Lavozim Haqida
                </h2>
                <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                    {{ jobDescription.job_summary }}
                </p>
            </div>

            <!-- Responsibilities -->
            <div v-if="jobDescription.responsibilities" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Mas'uliyatlar
                </h2>
                <ul class="space-y-2">
                    <li
                        v-for="(item, index) in parseList(jobDescription.responsibilities)"
                        :key="index"
                        class="flex items-start gap-3"
                    >
                        <span class="w-2 h-2 bg-purple-600 rounded-full mt-2 flex-shrink-0"></span>
                        <span class="text-gray-700 dark:text-gray-300">{{ item }}</span>
                    </li>
                </ul>
            </div>

            <!-- Requirements -->
            <div v-if="jobDescription.requirements" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Talablar
                </h2>
                <ul class="space-y-2">
                    <li
                        v-for="(item, index) in parseList(jobDescription.requirements)"
                        :key="index"
                        class="flex items-start gap-3"
                    >
                        <span class="w-2 h-2 bg-blue-600 rounded-full mt-2 flex-shrink-0"></span>
                        <span class="text-gray-700 dark:text-gray-300">{{ item }}</span>
                    </li>
                </ul>
            </div>

            <!-- Qualifications -->
            <div v-if="jobDescription.qualifications" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Malaka
                </h2>
                <ul class="space-y-2">
                    <li
                        v-for="(item, index) in parseList(jobDescription.qualifications)"
                        :key="index"
                        class="flex items-start gap-3"
                    >
                        <span class="w-2 h-2 bg-green-600 rounded-full mt-2 flex-shrink-0"></span>
                        <span class="text-gray-700 dark:text-gray-300">{{ item }}</span>
                    </li>
                </ul>
            </div>

            <!-- Skills -->
            <div v-if="jobDescription.skills" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Ko'nikmalar
                </h2>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="(skill, index) in jobDescription.skills.split(',').map(s => s.trim())"
                        :key="index"
                        class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400 rounded-full text-sm"
                    >
                        {{ skill }}
                    </span>
                </div>
            </div>

            <!-- Footer Info -->
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6">
                <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                    <div>
                        <span class="font-medium">Yaratilgan sana:</span>
                        {{ jobDescription.created_at }}
                    </div>
                    <div v-if="jobDescription.created_by_name">
                        <span class="font-medium">Yaratdi:</span>
                        {{ jobDescription.created_by_name }}
                    </div>
                </div>
            </div>
        </div>
    </HRLayout>
</template>
