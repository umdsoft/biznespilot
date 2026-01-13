<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    ClockIcon,
    CalendarIcon,
    CheckCircleIcon,
    XCircleIcon,
    MapPinIcon,
    ChartBarIcon,
    Cog6ToothIcon,
} from '@heroicons/vue/24/outline';
import { format } from 'date-fns';

const props = defineProps({
    records: { type: Array, default: () => [] },
    summary: { type: Object, default: null },
    todayAttendance: { type: Object, default: null },
    teamMembers: { type: Array, default: () => [] },
    selectedUserId: { type: String, default: null },
    selectedDate: { type: String, default: null },
    view: { type: String, default: 'daily' },
});

const checkingIn = ref(false);
const checkingOut = ref(false);
const showAddModal = ref(false);
const selectedRecord = ref(null);

// Check-in status
const isCheckedIn = computed(() => {
    return props.todayAttendance?.is_checked_in;
});

const isCheckedOut = computed(() => {
    return props.todayAttendance?.is_checked_out;
});

// Check-in handler
const checkIn = async () => {
    if (checkingIn.value) return;

    checkingIn.value = true;

    try {
        await router.post(route('hr.attendance.check-in'), {
            location: 'Office', // You can add geolocation here
        }, {
            preserveScroll: true,
            onSuccess: () => {
                // Success handled by Inertia
            },
            onError: () => {
                alert('Check-in xatolik yuz berdi');
            },
            onFinish: () => {
                checkingIn.value = false;
            }
        });
    } catch (error) {
        checkingIn.value = false;
    }
};

// Check-out handler
const checkOut = async () => {
    if (checkingOut.value) return;

    checkingOut.value = true;

    try {
        await router.post(route('hr.attendance.check-out'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Success handled by Inertia
            },
            onError: () => {
                alert('Check-out xatolik yuz berdi');
            },
            onFinish: () => {
                checkingOut.value = false;
            }
        });
    } catch (error) {
        checkingOut.value = false;
    }
};

// Change view
const changeView = (newView) => {
    router.get(route('hr.attendance.index'), {
        view: newView,
        user_id: props.selectedUserId,
        date: props.selectedDate,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Get status color
const getStatusColor = (status) => {
    const colors = {
        present: 'green',
        absent: 'red',
        late: 'yellow',
        half_day: 'orange',
        wfh: 'blue',
        leave: 'purple',
    };
    return colors[status] || 'gray';
};

const getStatusBgClass = (status) => {
    const classes = {
        present: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        absent: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
        late: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
        half_day: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
        wfh: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
        leave: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
    };
    return classes[status] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
};

// Format time
const formatTime = (time) => {
    return time || '--:--';
};
</script>

<template>
    <HRLayout title="Davomat">
        <Head title="Davomat" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Davomat Tizimi</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Xodimlarning davomat yozuvlarini boshqarish</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="changeView('daily')"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                            view === 'daily'
                                ? 'bg-purple-600 text-white'
                                : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                        ]"
                    >
                        Kunlik
                    </button>
                    <button
                        @click="changeView('monthly')"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                            view === 'monthly'
                                ? 'bg-purple-600 text-white'
                                : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                        ]"
                    >
                        Oylik
                    </button>
                </div>
            </div>

            <!-- Check-in/out Card (Only for own attendance) -->
            <div v-if="selectedUserId === $page.props.auth.user.id || !selectedUserId" class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl p-6 text-white shadow-xl">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left side - Status -->
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                <ClockIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <p class="text-sm opacity-90">Bugun</p>
                                <p class="text-xl font-bold">{{ new Date().toLocaleDateString('uz-UZ', { day: 'numeric', month: 'long' }) }}</p>
                            </div>
                        </div>

                        <div v-if="todayAttendance" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm opacity-90">Check-in:</span>
                                <span class="text-lg font-bold">{{ formatTime(todayAttendance.check_in) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm opacity-90">Check-out:</span>
                                <span class="text-lg font-bold">{{ formatTime(todayAttendance.check_out) }}</span>
                            </div>
                            <div v-if="todayAttendance.work_hours" class="flex items-center justify-between">
                                <span class="text-sm opacity-90">Ish soatlari:</span>
                                <span class="text-lg font-bold">{{ todayAttendance.work_hours }} soat</span>
                            </div>
                        </div>
                        <div v-else class="text-center py-4">
                            <p class="text-sm opacity-90">Bugun hali check-in qilmagansiz</p>
                        </div>
                    </div>

                    <!-- Right side - Actions -->
                    <div class="flex flex-col justify-center gap-3">
                        <button
                            v-if="!isCheckedIn"
                            @click="checkIn"
                            :disabled="checkingIn"
                            class="flex items-center justify-center gap-2 px-6 py-4 bg-white text-purple-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <CheckCircleIcon class="w-6 h-6" />
                            <span v-if="checkingIn">Check-in qilyapman...</span>
                            <span v-else>Check-in</span>
                        </button>

                        <button
                            v-else-if="!isCheckedOut"
                            @click="checkOut"
                            :disabled="checkingOut"
                            class="flex items-center justify-center gap-2 px-6 py-4 bg-white text-purple-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <XCircleIcon class="w-6 h-6" />
                            <span v-if="checkingOut">Check-out qilyapman...</span>
                            <span v-else>Check-out</span>
                        </button>

                        <div v-else class="flex items-center justify-center gap-2 px-6 py-4 bg-white/20 rounded-xl">
                            <CheckCircleIcon class="w-6 h-6" />
                            <span class="font-semibold">Bugun ishni tamomladingiz</span>
                        </div>

                        <div v-if="todayAttendance" class="text-center">
                            <span :class="['inline-flex px-4 py-1 rounded-full text-sm font-medium bg-white/20']">
                                {{ todayAttendance.status_label }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Summary -->
            <div v-if="summary" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <CheckCircleIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ summary.present_days }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Ishda</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                            <ClockIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ summary.late_days }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Kechikkan</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                            <XCircleIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ summary.absent_days }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Yo'q</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <ChartBarIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ summary.attendance_percentage }}%</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Davomat</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Records Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Davomat Yozuvlari</h2>
                </div>

                <div v-if="records.length > 0" class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sana</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Check-in</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Check-out</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ish soati</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Holat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Izoh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="record in records" :key="record.id" class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ record.date_formatted }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ formatTime(record.check_in) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ formatTime(record.check_out) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ record.work_hours || 0 }} soat
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusBgClass(record.status)]">
                                        {{ record.status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ record.notes || '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="p-12 text-center text-gray-500 dark:text-gray-400">
                    <ClockIcon class="w-12 h-12 mx-auto mb-4 opacity-50" />
                    <p>Davomat yozuvlari topilmadi</p>
                </div>
            </div>
        </div>
    </HRLayout>
</template>
