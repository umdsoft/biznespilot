<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import {
    UserPlusIcon,
    ArrowPathIcon,
    FunnelIcon,
    PlusIcon,
    CheckCircleIcon,
    ClockIcon,
    ExclamationCircleIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    currentBusiness: Object,
});

const loading = ref(true);
const onboardings = ref([]);
const statistics = ref(null);
const selectedStatus = ref('');
const showCreateModal = ref(false);
const employees = ref([]);
const newOnboarding = ref({
    user_id: '',
    start_date: '',
});

const statusOptions = [
    { value: '', label: 'Barcha statuslar' },
    { value: 'not_started', label: 'Boshlanmagan' },
    { value: 'in_progress', label: 'Jarayonda' },
    { value: 'completed', label: 'Yakunlangan' },
    { value: 'cancelled', label: 'Bekor qilingan' },
];

const fetchData = async () => {
    if (!props.currentBusiness?.id) return;

    loading.value = true;
    try {
        const [onboardingsRes, statsRes] = await Promise.all([
            axios.get(`/api/v1/businesses/${props.currentBusiness.id}/hr/onboarding`, {
                params: { status: selectedStatus.value || undefined }
            }),
            axios.get(`/api/v1/businesses/${props.currentBusiness.id}/hr/onboarding/statistics`)
        ]);

        onboardings.value = onboardingsRes.data.data?.data || [];
        statistics.value = statsRes.data.data;
    } catch (error) {
        console.error('Error fetching onboarding data:', error);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = async () => {
    showCreateModal.value = true;
    // Fetch employees for dropdown
    try {
        const res = await axios.get(`/api/v1/businesses/${props.currentBusiness.id}/users`);
        employees.value = res.data.data || [];
    } catch (error) {
        console.error('Error fetching employees:', error);
    }
};

const createOnboarding = async () => {
    if (!props.currentBusiness?.id || !newOnboarding.value.user_id) return;

    try {
        await axios.post(`/api/v1/businesses/${props.currentBusiness.id}/hr/onboarding`, newOnboarding.value);
        showCreateModal.value = false;
        newOnboarding.value = { user_id: '', start_date: '' };
        fetchData();
    } catch (error) {
        console.error('Error creating onboarding:', error);
    }
};

const getStatusColor = (status) => {
    const colors = {
        not_started: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getPhaseColor = (phase) => {
    const colors = {
        day_30: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        day_60: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
        day_90: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    };
    return colors[phase] || 'bg-gray-100 text-gray-800';
};

const getProgressColor = (progress) => {
    if (progress >= 80) return 'bg-green-500';
    if (progress >= 50) return 'bg-yellow-500';
    return 'bg-red-500';
};

onMounted(fetchData);
</script>

<template>
    <HRLayout title="Onboarding">
        <Head title="HR Onboarding" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        30-60-90 Onboarding
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Yangi hodimlarni moslashish jarayonini boshqarish
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="fetchData"
                        :disabled="loading"
                        class="flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        <ArrowPathIcon :class="['w-5 h-5', loading ? 'animate-spin' : '']" />
                        Yangilash
                    </button>
                    <button
                        @click="openCreateModal"
                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        <PlusIcon class="w-5 h-5" />
                        Yangi onboarding
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div v-if="statistics" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                            <UserPlusIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Faol rejalar</p>
                            <p class="text-2xl font-bold text-blue-600">
                                {{ statistics.active_plans || 0 }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                            <CheckCircleIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Yakunlangan</p>
                            <p class="text-2xl font-bold text-green-600">
                                {{ statistics.completed_plans || 0 }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                            <ExclamationCircleIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Kechikkan vazifalar</p>
                            <p class="text-2xl font-bold text-red-600">
                                {{ statistics.overdue_tasks_count || 0 }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                            <ChartBarIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">O'rtacha progress</p>
                            <p class="text-2xl font-bold text-purple-600">
                                {{ statistics.avg_progress?.toFixed(0) || 0 }}%
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Milestone Scores -->
            <div v-if="statistics?.milestone_completion_rates" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Milestone o'rtacha ballari
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        v-for="(rate, phase) in statistics.milestone_completion_rates"
                        :key="phase"
                        class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span :class="['px-3 py-1 text-xs font-medium rounded-full', getPhaseColor(phase)]">
                                {{ phase === 'day_30' ? '30 kun' : phase === 'day_60' ? '60 kun' : '90 kun' }}
                            </span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ rate?.toFixed(0) || 0 }}%
                            </span>
                        </div>
                        <div class="bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                            <div
                                :class="['h-2 rounded-full transition-all duration-500', getProgressColor(rate)]"
                                :style="{ width: `${rate || 0}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <FunnelIcon class="w-5 h-5 text-gray-400" />
                    <select
                        v-model="selectedStatus"
                        @change="fetchData"
                        class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500"
                    >
                        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Onboarding List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Hodim</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bosqich</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Progress</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kunlar</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Vazifalar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="plan in onboardings"
                                :key="plan.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                            <span class="text-sm font-medium text-blue-600 dark:text-blue-400">
                                                {{ plan.user?.name?.charAt(0) || '?' }}
                                            </span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ plan.user?.name || 'Noma\'lum' }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ plan.user?.email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-3 py-1 text-xs font-medium rounded-full', getPhaseColor(plan.current_phase)]">
                                        {{ plan.current_phase_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-24 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                            <div
                                                :class="['h-2 rounded-full', getProgressColor(plan.progress)]"
                                                :style="{ width: `${plan.progress}%` }"
                                            ></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ plan.progress }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-3 py-1 text-xs font-medium rounded-full', getStatusColor(plan.status)]">
                                        {{ plan.status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ plan.days_elapsed }} kun / 90 kun
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <span class="text-green-600">{{ plan.completed_tasks_count }}</span>
                                    /
                                    <span>{{ plan.total_tasks_count }}</span>
                                    <span v-if="plan.overdue_tasks_count > 0" class="text-red-600 ml-2">
                                        ({{ plan.overdue_tasks_count }} kechikkan)
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="loading" class="p-8 text-center">
                    <ArrowPathIcon class="w-8 h-8 mx-auto animate-spin text-blue-600" />
                    <p class="mt-2 text-gray-500">Yuklanmoqda...</p>
                </div>

                <div v-else-if="onboardings.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <UserPlusIcon class="w-12 h-12 mx-auto opacity-50 mb-3" />
                    <p>Onboarding rejalari topilmadi</p>
                    <button
                        @click="openCreateModal"
                        class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        Birinchi rejani yarating
                    </button>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Yangi Onboarding Rejasi
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Hodim
                        </label>
                        <select
                            v-model="newOnboarding.user_id"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Tanlang...</option>
                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                {{ emp.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Boshlash sanasi
                        </label>
                        <input
                            type="date"
                            v-model="newOnboarding.start_date"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button
                        @click="showCreateModal = false"
                        class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        Bekor qilish
                    </button>
                    <button
                        @click="createOnboarding"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        Yaratish
                    </button>
                </div>
            </div>
        </div>
    </HRLayout>
</template>
