<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import {
    BriefcaseIcon,
    PlusIcon,
    UserGroupIcon,
    MapPinIcon,
    CalendarIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    jobPostings: { type: Array, default: () => [] },
    jobDescriptions: { type: Array, default: () => [] },
    departments: { type: Object, default: () => ({}) },
    employmentTypes: { type: Object, default: () => ({}) },
    statuses: { type: Object, default: () => ({}) },
});

const jobPostings = ref(props.jobPostings);
const showAddModal = ref(false);

const addForm = ref({
    title: '',
    department: '',
    description: '',
    requirements: '',
    salary_min: '',
    salary_max: '',
    location: '',
    employment_type: 'full_time',
    openings: 1,
    posted_date: new Date().toISOString().split('T')[0],
    closing_date: '',
});

const getStatusColor = (status) => {
    const colors = {
        open: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        closed: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
        filled: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const reloadPage = () => {
    router.reload({ only: ['jobPostings'] });
};

const addJobPosting = async () => {
    try {
        const response = await fetch(route('hr.recruiting.job-postings.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify(addForm.value)
        });

        const data = await response.json();

        if (data.success) {
            showAddModal.value = false;
            addForm.value = {
                title: '',
                department: '',
                description: '',
                requirements: '',
                salary_min: '',
                salary_max: '',
                location: '',
                employment_type: 'full_time',
                openings: 1,
                posted_date: new Date().toISOString().split('T')[0],
                closing_date: '',
            };
            reloadPage();
        } else {
            alert(data.error || t('hr.error_occurred'));
        }
    } catch (error) {
        console.error('Error adding job posting:', error);
        alert(t('hr.error_occurred'));
    }
};
</script>

<template>
    <HRLayout :title="t('hr.recruiting')">
        <Head :title="t('hr.recruiting')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ t('hr.recruiting') }}</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ t('hr.recruiting_subtitle') }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('hr.recruiting.applications')"
                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        <UserGroupIcon class="w-5 h-5" />
                        {{ t('hr.applications') }}
                    </Link>
                    <button
                        @click="showAddModal = true"
                        class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                    >
                        <PlusIcon class="w-5 h-5" />
                        {{ t('hr.add_job_posting') }}
                    </button>
                </div>
            </div>

            <!-- Job Postings List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div v-if="jobPostings.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="job in jobPostings"
                        :key="job.id"
                        class="p-6 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                                        <BriefcaseIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                            {{ job.title }}
                                        </h3>
                                        <div class="flex items-center gap-2 mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            <span>{{ job.department_label }}</span>
                                            <span>•</span>
                                            <span>{{ job.employment_type_label }}</span>
                                            <span v-if="job.location">•</span>
                                            <span v-if="job.location" class="flex items-center gap-1">
                                                <MapPinIcon class="w-4 h-4" />
                                                {{ job.location }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 mt-3 text-sm text-gray-600 dark:text-gray-400">
                                    <span class="flex items-center gap-1">
                                        <UserGroupIcon class="w-4 h-4" />
                                        {{ job.openings }} {{ t('hr.vacancy') }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <CalendarIcon class="w-4 h-4" />
                                        {{ job.posted_date }}
                                    </span>
                                    <span class="font-semibold text-purple-600 dark:text-purple-400">
                                        {{ job.applications_count }} {{ t('hr.application') }}
                                    </span>
                                </div>
                                <div class="mt-3">
                                    <span
                                        :class="[
                                            'inline-flex px-3 py-1 rounded-full text-xs font-medium',
                                            getStatusColor(job.status)
                                        ]"
                                    >
                                        {{ job.status_label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="p-12 text-center">
                    <BriefcaseIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                    <p class="text-gray-500 dark:text-gray-400">
                        {{ t('hr.no_vacancies') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Add Modal -->
        <div
            v-if="showAddModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="showAddModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ t('hr.add_job_posting') }}
                    </h3>
                </div>
                <form @submit.prevent="addJobPosting" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ t('hr.job_title') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="addForm.title"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                :placeholder="`${t('hr.example')}: Senior Developer`"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ t('hr.department') }} <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="addForm.department"
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            >
                                <option value="">{{ t('hr.select') }}</option>
                                <option v-for="(label, code) in departments" :key="code" :value="code">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ t('hr.employment_type') }} <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="addForm.employment_type"
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            >
                                <option v-for="(label, code) in employmentTypes" :key="code" :value="code">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ t('hr.location') }}
                            </label>
                            <input
                                v-model="addForm.location"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                placeholder="Toshkent, Uzbekistan"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ t('hr.openings') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="addForm.openings"
                                type="number"
                                min="1"
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ t('hr.closing_date') }}
                            </label>
                            <input
                                v-model="addForm.closing_date"
                                type="date"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ t('hr.min_salary') }} (UZS)
                            </label>
                            <input
                                v-model="addForm.salary_min"
                                type="number"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ t('hr.job_description') }}
                        </label>
                        <textarea
                            v-model="addForm.description"
                            rows="4"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            placeholder="Lavozim haqida qisqacha ma'lumot..."
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ t('hr.requirements') }}
                        </label>
                        <textarea
                            v-model="addForm.requirements"
                            rows="4"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            placeholder="Nomzodga qo'yiladigan talablar..."
                        ></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="showAddModal = false"
                            class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                        >
                            {{ t('hr.cancel') }}
                        </button>
                        <button
                            type="submit"
                            class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                        >
                            {{ t('hr.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </HRLayout>
</template>
