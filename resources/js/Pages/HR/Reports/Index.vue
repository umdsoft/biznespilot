<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import {
    DocumentChartBarIcon,
    UsersIcon,
    ClockIcon,
    CalendarIcon,
    HeartIcon,
    FireIcon,
    ArrowPathIcon,
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    currentBusiness: Object,
});

const loading = ref(true);
const stats = ref(null);
const attendanceStats = ref(null);
const leaveStats = ref(null);
const engagementStats = ref(null);
const flightRiskStats = ref(null);

const fetchData = async () => {
    if (!props.currentBusiness?.id) return;

    loading.value = true;
    try {
        const [dashboardRes, engagementRes, flightRiskRes] = await Promise.all([
            axios.get(`/api/v1/businesses/${props.currentBusiness.id}/hr/dashboard`).catch(() => null),
            axios.get(`/api/v1/businesses/${props.currentBusiness.id}/hr/engagement/statistics`).catch(() => null),
            axios.get(`/api/v1/businesses/${props.currentBusiness.id}/hr/flight-risk/statistics`).catch(() => null),
        ]);

        if (dashboardRes?.data) {
            stats.value = dashboardRes.data.data?.stats || dashboardRes.data.stats;
            attendanceStats.value = dashboardRes.data.data?.attendance || dashboardRes.data.attendance;
            leaveStats.value = dashboardRes.data.data?.leave || dashboardRes.data.leave;
        }

        if (engagementRes?.data) {
            engagementStats.value = engagementRes.data.data;
        }

        if (flightRiskRes?.data) {
            flightRiskStats.value = flightRiskRes.data.data;
        }
    } catch (error) {
        console.error('Error fetching report data:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);
</script>

<template>
    <HRLayout title="Hisobotlar">
        <Head title="HR Hisobotlar" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        HR Hisobotlar
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Kadrlar bo'limi uchun umumiy hisobotlar va statistika
                    </p>
                </div>
                <button
                    @click="fetchData"
                    :disabled="loading"
                    class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50"
                >
                    <ArrowPathIcon :class="['w-5 h-5', loading ? 'animate-spin' : '']" />
                    Yangilash
                </button>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-12">
                <ArrowPathIcon class="w-8 h-8 animate-spin text-purple-600" />
                <span class="ml-3 text-gray-500">Yuklanmoqda...</span>
            </div>

            <!-- Content -->
            <div v-else class="space-y-6">
                <!-- Quick Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                                <UsersIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Jami xodimlar</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ stats?.total_employees || 0 }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                                <ClockIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Bugun kelganlar</p>
                                <p class="text-2xl font-bold text-green-600">
                                    {{ attendanceStats?.today_present || 0 }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                                <CalendarIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Ta'tilda</p>
                                <p class="text-2xl font-bold text-blue-600">
                                    {{ leaveStats?.on_leave || 0 }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                                <CalendarIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Kutilayotgan so'rovlar</p>
                                <p class="text-2xl font-bold text-yellow-600">
                                    {{ leaveStats?.pending_requests || 0 }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analytics Summary -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Engagement Summary -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                <HeartIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Xodimlar Qiziqishi
                            </h3>
                        </div>

                        <div v-if="engagementStats" class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-400">O'rtacha ball</span>
                                <span class="text-2xl font-bold text-purple-600">
                                    {{ engagementStats.overview?.avg_score?.toFixed(1) || 'N/A' }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                                    <div class="text-green-600 dark:text-green-400 font-medium">Qiziqgan</div>
                                    <div class="text-lg font-bold text-green-700 dark:text-green-300">
                                        {{ (engagementStats.distribution?.highly_engaged?.count || 0) + (engagementStats.distribution?.engaged?.count || 0) }}
                                    </div>
                                </div>
                                <div class="bg-red-50 dark:bg-red-900/20 p-3 rounded-lg">
                                    <div class="text-red-600 dark:text-red-400 font-medium">Qiziqmagan</div>
                                    <div class="text-lg font-bold text-red-700 dark:text-red-300">
                                        {{ engagementStats.distribution?.disengaged?.count || 0 }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-gray-500 dark:text-gray-400 text-center py-4">
                            Ma'lumot mavjud emas
                        </div>

                        <a href="/hr/engagement" class="mt-4 inline-flex items-center text-sm text-purple-600 hover:text-purple-800 dark:text-purple-400">
                            Batafsil ko'rish &rarr;
                        </a>
                    </div>

                    <!-- Flight Risk Summary -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                                <FireIcon class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Ketish Xavfi Tahlili
                            </h3>
                        </div>

                        <div v-if="flightRiskStats" class="space-y-4">
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="bg-red-50 dark:bg-red-900/20 p-3 rounded-lg">
                                    <div class="text-red-600 dark:text-red-400 font-medium">Kritik</div>
                                    <div class="text-lg font-bold text-red-700 dark:text-red-300">
                                        {{ flightRiskStats.by_level?.critical || 0 }}
                                    </div>
                                </div>
                                <div class="bg-orange-50 dark:bg-orange-900/20 p-3 rounded-lg">
                                    <div class="text-orange-600 dark:text-orange-400 font-medium">Yuqori</div>
                                    <div class="text-lg font-bold text-orange-700 dark:text-orange-300">
                                        {{ flightRiskStats.by_level?.high || 0 }}
                                    </div>
                                </div>
                                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded-lg">
                                    <div class="text-yellow-600 dark:text-yellow-400 font-medium">O'rtacha</div>
                                    <div class="text-lg font-bold text-yellow-700 dark:text-yellow-300">
                                        {{ flightRiskStats.by_level?.medium || 0 }}
                                    </div>
                                </div>
                                <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                                    <div class="text-green-600 dark:text-green-400 font-medium">Past</div>
                                    <div class="text-lg font-bold text-green-700 dark:text-green-300">
                                        {{ flightRiskStats.by_level?.low || 0 }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-gray-500 dark:text-gray-400 text-center py-4">
                            Ma'lumot mavjud emas
                        </div>

                        <a href="/hr/flight-risk" class="mt-4 inline-flex items-center text-sm text-orange-600 hover:text-orange-800 dark:text-orange-400">
                            Batafsil ko'rish &rarr;
                        </a>
                    </div>
                </div>

                <!-- Department Distribution -->
                <div v-if="stats?.departments" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <DocumentChartBarIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Bo'limlar bo'yicha taqsimot
                        </h3>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ stats.departments?.sales || 0 }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Sotuv</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ stats.departments?.marketing || 0 }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Marketing</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ stats.departments?.finance || 0 }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Moliya</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ stats.departments?.hr || 0 }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">HR</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Tezkor havolalar
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <a href="/hr/engagement" class="flex items-center gap-3 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                            <HeartIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                            <span class="font-medium text-purple-700 dark:text-purple-300">Ishga Qiziqish</span>
                        </a>
                        <a href="/hr/flight-risk" class="flex items-center gap-3 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors">
                            <FireIcon class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                            <span class="font-medium text-orange-700 dark:text-orange-300">Ketish Xavfi</span>
                        </a>
                        <a href="/hr/surveys" class="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                            <DocumentChartBarIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            <span class="font-medium text-blue-700 dark:text-blue-300">So'rovnomalar</span>
                        </a>
                        <a href="/hr/attendance" class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                            <ClockIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                            <span class="font-medium text-green-700 dark:text-green-300">Davomat</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </HRLayout>
</template>
