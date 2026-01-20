<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import {
    BriefcaseIcon,
    PlusIcon,
    PencilIcon,
    TrashIcon,
    EyeIcon,
    CheckCircleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    jobDescriptions: { type: Array, default: () => [] },
    departments: { type: Object, default: () => ({}) },
    positionLevels: { type: Object, default: () => ({}) },
    employmentTypes: { type: Object, default: () => ({}) },
});

const jobDescriptions = ref(props.jobDescriptions);
const showAddModal = ref(false);
const showEditModal = ref(false);
const selectedJob = ref(null);
const filterDepartment = ref('');
const filterStatus = ref('all');

const addForm = ref({
    title: '',
    department: '',
    position_level: '',
    reports_to: '',
    job_summary: '',
    responsibilities: '',
    requirements: '',
    qualifications: '',
    skills: '',
    salary_range_min: '',
    salary_range_max: '',
    employment_type: 'full_time',
    location: '',
    is_active: true,
});

const editForm = ref({});

// Filtered job descriptions
const filteredJobs = computed(() => {
    return jobDescriptions.value.filter(job => {
        const matchesDepartment = !filterDepartment.value || job.department === filterDepartment.value;
        const matchesStatus = filterStatus.value === 'all' ||
            (filterStatus.value === 'active' && job.is_active) ||
            (filterStatus.value === 'inactive' && !job.is_active);
        return matchesDepartment && matchesStatus;
    });
});

// Reload page
const reloadPage = () => {
    router.reload({ only: ['jobDescriptions'] });
};

// Add new job description
const addJobDescription = async () => {
    try {
        const response = await fetch(route('hr.job-descriptions.store'), {
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
                position_level: '',
                reports_to: '',
                job_summary: '',
                responsibilities: '',
                requirements: '',
                qualifications: '',
                skills: '',
                salary_range_min: '',
                salary_range_max: '',
                employment_type: 'full_time',
                location: '',
                is_active: true,
            };
            reloadPage();
        } else {
            alert(data.error || 'Xatolik yuz berdi');
        }
    } catch (error) {
        console.error('Error adding job description:', error);
        alert('Xatolik yuz berdi');
    }
};

// Edit job description
const openEditModal = (job) => {
    selectedJob.value = job;
    editForm.value = {
        title: job.title,
        department: job.department,
        position_level: job.position_level || '',
        reports_to: job.reports_to || '',
        job_summary: job.job_summary || '',
        responsibilities: job.responsibilities || '',
        requirements: job.requirements || '',
        qualifications: job.qualifications || '',
        skills: job.skills || '',
        salary_range_min: job.salary_range_min || '',
        salary_range_max: job.salary_range_max || '',
        employment_type: job.employment_type,
        location: job.location || '',
        is_active: job.is_active,
    };
    showEditModal.value = true;
};

const updateJobDescription = async () => {
    try {
        const response = await fetch(route('hr.job-descriptions.update', selectedJob.value.id), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify(editForm.value)
        });

        const data = await response.json();

        if (data.success) {
            showEditModal.value = false;
            selectedJob.value = null;
            reloadPage();
        } else {
            alert(data.error || 'Xatolik yuz berdi');
        }
    } catch (error) {
        console.error('Error updating job description:', error);
        alert('Xatolik yuz berdi');
    }
};

// Delete job description
const deleteJobDescription = async (jobId) => {
    if (!confirm('Rostdan ham bu lavozim majburiyatini o\'chirmoqchimisiz?')) {
        return;
    }

    try {
        const response = await fetch(route('hr.job-descriptions.destroy', jobId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });

        const data = await response.json();

        if (data.success) {
            reloadPage();
        } else {
            alert(data.error || 'Xatolik yuz berdi');
        }
    } catch (error) {
        console.error('Error deleting job description:', error);
        alert('Xatolik yuz berdi');
    }
};

// Toggle job status
const toggleJobStatus = async (jobId) => {
    try {
        const response = await fetch(route('hr.job-descriptions.toggle-status', jobId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });

        const data = await response.json();

        if (data.success) {
            reloadPage();
        } else {
            alert(data.error || 'Xatolik yuz berdi');
        }
    } catch (error) {
        console.error('Error toggling job status:', error);
        alert('Xatolik yuz berdi');
    }
};
</script>

<template>
    <HRLayout :title="t('hr.job_descriptions')">
        <Head :title="t('hr.job_descriptions')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ t('hr.job_descriptions') }}</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ t('hr.job_descriptions_subtitle') }}
                    </p>
                </div>
                <button
                    @click="showAddModal = true"
                    class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                >
                    <PlusIcon class="w-5 h-5" />
                    {{ t('hr.add_job_description') }}
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Bo'lim
                        </label>
                        <select
                            v-model="filterDepartment"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        >
                            <option value="">Barcha bo'limlar</option>
                            <option v-for="(label, code) in departments" :key="code" :value="code">
                                {{ label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Holati
                        </label>
                        <select
                            v-model="filterStatus"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        >
                            <option value="all">Barchasi</option>
                            <option value="active">Faol</option>
                            <option value="inactive">Faolsiz</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Job Descriptions List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div v-if="filteredJobs.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="job in filteredJobs"
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
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ job.department_label }}
                                            </span>
                                            <span v-if="job.position_level_label" class="text-sm text-gray-400">•</span>
                                            <span v-if="job.position_level_label" class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ job.position_level_label }}
                                            </span>
                                            <span class="text-sm text-gray-400">•</span>
                                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ job.employment_type_label }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 mt-3 text-sm text-gray-600 dark:text-gray-400">
                                    <span v-if="job.location">📍 {{ job.location }}</span>
                                    <span v-if="job.salary_range_formatted">💰 {{ job.salary_range_formatted }}</span>
                                    <span>📅 {{ job.created_at }}</span>
                                </div>
                                <div class="mt-2">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium',
                                            job.is_active
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                                : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
                                        ]"
                                    >
                                        <component :is="job.is_active ? CheckCircleIcon : XCircleIcon" class="w-4 h-4" />
                                        {{ job.is_active ? 'Faol' : 'Faolsiz' }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Link
                                    :href="route('hr.job-descriptions.show', job.id)"
                                    class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                                    title="Ko'rish"
                                >
                                    <EyeIcon class="w-5 h-5" />
                                </Link>
                                <button
                                    @click="openEditModal(job)"
                                    class="p-2 text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/30 rounded-lg transition-colors"
                                    title="Tahrirlash"
                                >
                                    <PencilIcon class="w-5 h-5" />
                                </button>
                                <button
                                    @click="toggleJobStatus(job.id)"
                                    :class="[
                                        'p-2 rounded-lg transition-colors',
                                        job.is_active
                                            ? 'text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700'
                                            : 'text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30'
                                    ]"
                                    :title="job.is_active ? 'Faolsizlantirish' : 'Faollashtirish'"
                                >
                                    <component :is="job.is_active ? XCircleIcon : CheckCircleIcon" class="w-5 h-5" />
                                </button>
                                <button
                                    @click="deleteJobDescription(job.id)"
                                    class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                    title="O'chirish"
                                >
                                    <TrashIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="p-12 text-center">
                    <BriefcaseIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                    <p class="text-gray-500 dark:text-gray-400">
                        Lavozim majburiyatlari mavjud emas
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
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                        Yangi Lavozim Qo'shish
                    </h3>
                </div>
                <form @submit.prevent="addJobDescription" class="p-6 space-y-6">
                    <!-- Basic Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Lavozim nomi <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="addForm.title"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                placeholder="Masalan: Senior Developer"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Bo'lim <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="addForm.department"
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            >
                                <option value="">Bo'lim tanlang</option>
                                <option v-for="(label, code) in departments" :key="code" :value="code">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Daraja
                            </label>
                            <select
                                v-model="addForm.position_level"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            >
                                <option value="">Daraja tanlang</option>
                                <option v-for="(label, code) in positionLevels" :key="code" :value="code">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Ish turi <span class="text-red-500">*</span>
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
                                Kim bilan ishlaydi
                            </label>
                            <input
                                v-model="addForm.reports_to"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                placeholder="Masalan: IT Manager"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Joylashuv
                            </label>
                            <input
                                v-model="addForm.location"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                placeholder="Masalan: Toshkent, Uzbekistan"
                            />
                        </div>
                    </div>

                    <!-- Salary Range -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Minimum maosh (UZS)
                            </label>
                            <input
                                v-model="addForm.salary_range_min"
                                type="number"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                placeholder="Masalan: 5000000"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Maximum maosh (UZS)
                            </label>
                            <input
                                v-model="addForm.salary_range_max"
                                type="number"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                placeholder="Masalan: 10000000"
                            />
                        </div>
                    </div>

                    <!-- Detailed Info -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Qisqacha ta'rif
                        </label>
                        <textarea
                            v-model="addForm.job_summary"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            placeholder="Lavozimning qisqacha ta'rifi..."
                        ></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Mas'uliyatlar
                        </label>
                        <textarea
                            v-model="addForm.responsibilities"
                            rows="4"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            placeholder="Har bir mas'uliyatni yangi qatordan boshlang..."
                        ></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Talablar
                        </label>
                        <textarea
                            v-model="addForm.requirements"
                            rows="4"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            placeholder="Har bir talabni yangi qatordan boshlang..."
                        ></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Malaka
                        </label>
                        <textarea
                            v-model="addForm.qualifications"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            placeholder="Ta'lim, sertifikatlar va boshqa malakalar..."
                        ></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Ko'nikmalar
                        </label>
                        <textarea
                            v-model="addForm.skills"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            placeholder="Kerakli ko'nikmalarni vergul bilan ajrating..."
                        ></textarea>
                    </div>

                    <div class="flex items-center">
                        <input
                            v-model="addForm.is_active"
                            type="checkbox"
                            class="w-4 h-4 text-purple-600 border-gray-300 rounded"
                        />
                        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            Faol lavozim (ishga qabul qilish oching)
                        </label>
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="showAddModal = false"
                            class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                        >
                            Bekor qilish
                        </button>
                        <button
                            type="submit"
                            class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                        >
                            Saqlash
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Modal (similar structure to Add Modal) -->
        <div
            v-if="showEditModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="showEditModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                        Lavozimni Tahrirlash
                    </h3>
                </div>
                <form @submit.prevent="updateJobDescription" class="p-6 space-y-6">
                    <!-- Same form fields as Add Modal but with editForm -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Lavozim nomi <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="editForm.title"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Bo'lim <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="editForm.department"
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            >
                                <option v-for="(label, code) in departments" :key="code" :value="code">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Daraja
                            </label>
                            <select
                                v-model="editForm.position_level"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            >
                                <option value="">Daraja tanlang</option>
                                <option v-for="(label, code) in positionLevels" :key="code" :value="code">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Ish turi <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="editForm.employment_type"
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
                                Kim bilan ishlaydi
                            </label>
                            <input
                                v-model="editForm.reports_to"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Joylashuv
                            </label>
                            <input
                                v-model="editForm.location"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Minimum maosh (UZS)
                            </label>
                            <input
                                v-model="editForm.salary_range_min"
                                type="number"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Maximum maosh (UZS)
                            </label>
                            <input
                                v-model="editForm.salary_range_max"
                                type="number"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Qisqacha ta'rif
                        </label>
                        <textarea
                            v-model="editForm.job_summary"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        ></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Mas'uliyatlar
                        </label>
                        <textarea
                            v-model="editForm.responsibilities"
                            rows="4"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        ></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Talablar
                        </label>
                        <textarea
                            v-model="editForm.requirements"
                            rows="4"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        ></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Malaka
                        </label>
                        <textarea
                            v-model="editForm.qualifications"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        ></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Ko'nikmalar
                        </label>
                        <textarea
                            v-model="editForm.skills"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        ></textarea>
                    </div>

                    <div class="flex items-center">
                        <input
                            v-model="editForm.is_active"
                            type="checkbox"
                            class="w-4 h-4 text-purple-600 border-gray-300 rounded"
                        />
                        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            Faol lavozim (ishga qabul qilish ochiq)
                        </label>
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="showEditModal = false"
                            class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                        >
                            Bekor qilish
                        </button>
                        <button
                            type="submit"
                            class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                        >
                            O'zgartirish
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </HRLayout>
</template>
