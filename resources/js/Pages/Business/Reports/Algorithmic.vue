<template>
    <BusinessLayout title="Algoritmik Hisobotlar">
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Algoritmik Hisobotlar</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Real vaqtda biznes ko'rsatkichlari va tahlillar
                    </p>
                </div>
                <button
                    @click="showGenerateModal = true"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Yangi hisobot
                </button>
            </div>

            <!-- Realtime Summary Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Real vaqt holati</h3>
                    <button @click="refreshRealtime" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                        <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Yangilash
                    </button>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <!-- Health Score -->
                    <div class="col-span-2 md:col-span-1 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white">
                        <div class="text-4xl font-bold">{{ realtimeSummary?.health_score || 0 }}</div>
                        <div class="text-blue-100 text-sm">Salomatlik balli</div>
                        <div class="mt-1 text-xs text-blue-200">{{ realtimeSummary?.health_label || 'Hisoblanmoqda...' }}</div>
                    </div>

                    <!-- Key Metrics -->
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ realtimeSummary?.key_metrics?.total_sales || 0 }}
                        </div>
                        <div class="text-gray-600 dark:text-gray-400 text-sm">Sotuvlar (7 kun)</div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ formatCurrency(realtimeSummary?.key_metrics?.total_revenue || 0) }}
                        </div>
                        <div class="text-gray-600 dark:text-gray-400 text-sm">Daromad</div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ realtimeSummary?.key_metrics?.total_leads || 0 }}
                        </div>
                        <div class="text-gray-600 dark:text-gray-400 text-sm">Lidlar</div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ realtimeSummary?.key_metrics?.conversion_rate || 0 }}%
                        </div>
                        <div class="text-gray-600 dark:text-gray-400 text-sm">Konversiya</div>
                    </div>
                </div>

                <!-- KPI Progress -->
                <div v-if="realtimeSummary?.kpi_progress?.has_plan" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">KPI bajarilishi</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div v-for="(item, key) in ['sales', 'revenue', 'leads']" :key="key" class="relative">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400">
                                    {{ key === 'sales' ? 'Sotuvlar' : key === 'revenue' ? 'Daromad' : 'Lidlar' }}
                                </span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ realtimeSummary?.kpi_progress?.[item]?.progress || 0 }}%
                                </span>
                            </div>
                            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div
                                    class="h-full transition-all duration-500"
                                    :class="getProgressColor(realtimeSummary?.kpi_progress?.[item]?.status)"
                                    :style="{ width: Math.min(100, realtimeSummary?.kpi_progress?.[item]?.progress || 0) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Reports -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">So'nggi hisobotlar</h3>
                </div>

                <div v-if="reports.length === 0" class="p-8 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="mt-4 text-gray-600 dark:text-gray-400">Hali hisobotlar yo'q</p>
                    <button
                        @click="showGenerateModal = true"
                        class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                    >
                        Birinchi hisobotni yarating
                    </button>
                </div>

                <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="report in reports"
                        :key="report.id"
                        class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"
                        @click="viewReport(report.id)"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div
                                    class="w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg"
                                    :class="getScoreClass(report.health_score)"
                                >
                                    {{ report.health_score || '-' }}
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ report.title }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ report.period }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="text-right">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="getStatusClass(report.status)"
                                    >
                                        {{ report.status_label }}
                                    </span>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ report.created_at }}</p>
                                </div>
                                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schedules Section -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Jadvallar</h3>
                    <button
                        @click="showScheduleModal = true"
                        class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                    >
                        + Yangi jadval
                    </button>
                </div>

                <div v-if="schedules.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
                    Avtomatik hisobot jadvallari yo'q
                </div>

                <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="schedule in schedules"
                        :key="schedule.id"
                        class="px-6 py-4 flex items-center justify-between"
                    >
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ schedule.name }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ schedule.frequency_label }} |
                                Keyingi: {{ schedule.next_scheduled_at || 'Belgilanmagan' }}
                            </p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button
                                @click="toggleSchedule(schedule.id)"
                                :class="schedule.is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            >
                                <span
                                    :class="schedule.is_active ? 'translate-x-5' : 'translate-x-0'"
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                ></span>
                            </button>
                            <button
                                @click="deleteSchedule(schedule.id)"
                                class="text-red-500 hover:text-red-700"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generate Report Modal -->
        <div v-if="showGenerateModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" @click="showGenerateModal = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Yangi hisobot yaratish</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Boshlanish sanasi</label>
                            <input
                                type="date"
                                v-model="generateForm.start_date"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tugash sanasi</label>
                            <input
                                type="date"
                                v-model="generateForm.end_date"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Shablon (ixtiyoriy)</label>
                            <select
                                v-model="generateForm.template_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            >
                                <option :value="null">Standart</option>
                                <option v-for="template in templates" :key="template.id" :value="template.id">
                                    {{ template.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            @click="showGenerateModal = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            Bekor qilish
                        </button>
                        <button
                            @click="generateReport"
                            :disabled="generating"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
                        >
                            <span v-if="generating">Yaratilmoqda...</span>
                            <span v-else>Yaratish</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import BusinessLayout from '@/layouts/BusinessLayout.vue';

const props = defineProps({
    reports: Array,
    schedules: Array,
    templates: Array,
    realtimeSummary: Object,
});

const showGenerateModal = ref(false);
const showScheduleModal = ref(false);
const generating = ref(false);

const generateForm = reactive({
    start_date: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
    template_id: null,
});

const formatCurrency = (value) => {
    if (!value) return '0 UZS';
    return new Intl.NumberFormat('uz-UZ').format(value) + ' UZS';
};

const getProgressColor = (status) => {
    return {
        'bg-green-500': status === 'excellent' || status === 'on_track',
        'bg-yellow-500': status === 'warning',
        'bg-red-500': status === 'critical',
        'bg-gray-400': !status,
    };
};

const getScoreClass = (score) => {
    if (!score) return 'bg-gray-100 dark:bg-gray-700 text-gray-500';
    if (score >= 80) return 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400';
    if (score >= 60) return 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400';
    if (score >= 40) return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400';
    return 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400';
};

const getStatusClass = (status) => {
    return {
        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': status === 'completed' || status === 'delivered',
        'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400': status === 'generating',
        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': status === 'pending',
        'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': status === 'failed',
    };
};

const refreshRealtime = () => {
    router.reload({ only: ['realtimeSummary'] });
};

const generateReport = async () => {
    generating.value = true;

    try {
        const response = await axios.post(route('business.reports.generate'), generateForm);

        if (response.data.success) {
            showGenerateModal.value = false;
            router.reload({ only: ['reports'] });
        }
    } catch (error) {
        console.error('Report generation failed:', error);
        alert(error.response?.data?.message || 'Hisobot yaratishda xatolik');
    } finally {
        generating.value = false;
    }
};

const viewReport = (id) => {
    router.visit(route('business.reports.show', id));
};

const toggleSchedule = async (id) => {
    try {
        await axios.post(route('business.reports.schedules.toggle', id));
        router.reload({ only: ['schedules'] });
    } catch (error) {
        console.error('Toggle failed:', error);
    }
};

const deleteSchedule = async (id) => {
    if (!confirm('Jadvalni o\'chirishni xohlaysizmi?')) return;

    try {
        await axios.delete(route('business.reports.schedules.delete', id));
        router.reload({ only: ['schedules'] });
    } catch (error) {
        console.error('Delete failed:', error);
    }
};
</script>
