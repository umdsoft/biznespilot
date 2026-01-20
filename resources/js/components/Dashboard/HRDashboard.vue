<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import {
    UsersIcon,
    UserGroupIcon,
    BuildingOfficeIcon,
    BellIcon,
    ClockIcon,
    CheckCircleIcon,
    ChartBarIcon,
    XCircleIcon,
    CalendarIcon,
    BanknotesIcon,
} from '@heroicons/vue/24/outline';
import StatCard from './StatCard.vue';
import DashboardCard from './DashboardCard.vue';
import QuickActions from './QuickActions.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    recentActivities: { type: Array, default: () => [] },
    pendingTasks: { type: Array, default: () => [] },
    todayAttendance: { type: Object, default: null },
    leaveBalances: { type: Array, default: () => [] },
    upcomingLeaves: { type: Array, default: () => [] },
    currentBusiness: { type: Object, default: null },
});

const checkingIn = ref(false);
const checkingOut = ref(false);

// Check-in handler
const checkIn = async () => {
    if (checkingIn.value) return;

    checkingIn.value = true;

    try {
        await router.post(route('hr.attendance.check-in'), {
            location: 'Office',
        }, {
            preserveScroll: true,
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
            onFinish: () => {
                checkingOut.value = false;
            }
        });
    } catch (error) {
        checkingOut.value = false;
    }
};

const quickActions = computed(() => [
    { href: '/hr/team', icon: UsersIcon, label: t('dashboard.hr.employees'), color: 'blue' },
    { href: '/hr/attendance', icon: ClockIcon, label: t('dashboard.hr.attendance'), color: 'purple' },
    { href: '/hr/departments', icon: BuildingOfficeIcon, label: t('dashboard.hr.departments'), color: 'green' },
    { href: '/hr/invitations', icon: BellIcon, label: t('dashboard.hr.invitations'), color: 'orange' },
]);

const getActivityColor = (type) => {
    const colors = {
        new_member: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        accepted: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
        department_change: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        invitation_sent: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    };
    return colors[type] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
};

const getPriorityColor = (priority) => {
    const colors = {
        high: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        low: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
    };
    return colors[priority] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
};
</script>

<template>
    <div class="space-y-8">
        <!-- Welcome Banner -->
        <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-pink-600 to-purple-700 rounded-3xl p-8 shadow-2xl">
            <div class="absolute inset-0 bg-grid-white/[0.05] bg-[size:20px_20px]"></div>
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
            <div class="relative">
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                    {{ t('dashboard.hr.welcome', { name: currentBusiness?.name || t('dashboard.hr.hr_panel') }) }}
                </h1>
                <p class="text-purple-100 text-lg">
                    {{ t('dashboard.hr.today') }} {{ new Date().toLocaleDateString('uz-UZ', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
                </p>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Jami xodimlar -->
            <div class="group relative bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl p-6 border border-purple-100 dark:border-purple-800/30 hover:shadow-2xl hover:scale-105 transition-all duration-300 cursor-pointer">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-600 to-pink-600 rounded-2xl flex items-center justify-center shadow-lg shadow-purple-500/50 group-hover:scale-110 transition-transform duration-300">
                        <UsersIcon class="w-7 h-7 text-white" />
                    </div>
                    <div class="flex flex-col items-end">
                        <span class="text-4xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                            {{ stats?.total_employees || 0 }}
                        </span>
                        <span class="text-xs font-medium px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full mt-2">
                            {{ stats?.active_employees || 0 }} {{ t('dashboard.hr.active') }}
                        </span>
                    </div>
                </div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('dashboard.hr.total_employees') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ t('dashboard.hr.all_company_employees') }}</p>
            </div>

            <!-- Kutilayotgan takliflar -->
            <div class="group relative bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-2xl p-6 border border-blue-100 dark:border-blue-800/30 hover:shadow-2xl hover:scale-105 transition-all duration-300 cursor-pointer">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-600 to-cyan-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/50 group-hover:scale-110 transition-transform duration-300">
                        <BellIcon class="w-7 h-7 text-white" />
                    </div>
                    <span class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
                        {{ stats?.pending_invitations || 0 }}
                    </span>
                </div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('dashboard.hr.pending_invitations') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ t('dashboard.hr.unconfirmed_invitations') }}</p>
            </div>

            <!-- Sotuv bo'limi -->
            <div class="group relative bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-2xl p-6 border border-emerald-100 dark:border-emerald-800/30 hover:shadow-2xl hover:scale-105 transition-all duration-300 cursor-pointer">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-emerald-600 to-green-600 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-500/50 group-hover:scale-110 transition-transform duration-300">
                        <UserGroupIcon class="w-7 h-7 text-white" />
                    </div>
                    <div class="flex flex-col items-end">
                        <span class="text-4xl font-bold bg-gradient-to-r from-emerald-600 to-green-600 bg-clip-text text-transparent">
                            {{ stats?.departments?.sales || 0 }}
                        </span>
                        <span class="text-xs font-medium px-3 py-1 bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 rounded-full mt-2">
                            {{ t('dashboard.hr.employee') }}
                        </span>
                    </div>
                </div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('dashboard.hr.sales_department') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ t('dashboard.hr.sales_team_members') }}</p>
            </div>

            <!-- Boshqa bo'limlar -->
            <div class="group relative bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-2xl p-6 border border-orange-100 dark:border-orange-800/30 hover:shadow-2xl hover:scale-105 transition-all duration-300 cursor-pointer">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-orange-600 to-amber-600 rounded-2xl flex items-center justify-center shadow-lg shadow-orange-500/50 group-hover:scale-110 transition-transform duration-300">
                        <BuildingOfficeIcon class="w-7 h-7 text-white" />
                    </div>
                    <span class="text-4xl font-bold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">
                        {{ (stats?.departments?.marketing || 0) + (stats?.departments?.finance || 0) + (stats?.departments?.hr || 0) }}
                    </span>
                </div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('dashboard.hr.other_departments') }}</h3>
                <div class="flex gap-2 mt-2 flex-wrap">
                    <span class="text-xs px-2 py-1 bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 rounded-lg">
                        {{ t('dashboard.hr.marketing') }}: {{ stats?.departments?.marketing || 0 }}
                    </span>
                    <span class="text-xs px-2 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg">
                        {{ t('dashboard.hr.finance') }}: {{ stats?.departments?.finance || 0 }}
                    </span>
                    <span class="text-xs px-2 py-1 bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400 rounded-lg">
                        {{ t('dashboard.hr.hr') }}: {{ stats?.departments?.hr || 0 }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Today's Attendance Widget -->
        <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl p-6 text-white shadow-xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left side - Status -->
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <ClockIcon class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-sm opacity-90">{{ t('dashboard.hr.today') }}</p>
                            <p class="text-xl font-bold">{{ new Date().toLocaleDateString('uz-UZ', { day: 'numeric', month: 'long' }) }}</p>
                        </div>
                    </div>

                    <div v-if="todayAttendance" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm opacity-90">Check-in:</span>
                            <span class="text-lg font-bold">{{ todayAttendance.check_in || '--:--' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm opacity-90">Check-out:</span>
                            <span class="text-lg font-bold">{{ todayAttendance.check_out || '--:--' }}</span>
                        </div>
                        <div v-if="todayAttendance.work_hours" class="flex items-center justify-between">
                            <span class="text-sm opacity-90">{{ t('dashboard.hr.work_hours') }}:</span>
                            <span class="text-lg font-bold">{{ todayAttendance.work_hours }} {{ t('dashboard.hr.hours') }}</span>
                        </div>
                    </div>
                    <div v-else class="text-center py-4">
                        <p class="text-sm opacity-90">{{ t('dashboard.hr.no_check_in_today') }}</p>
                    </div>
                </div>

                <!-- Right side - Actions -->
                <div class="flex flex-col justify-center gap-3">
                    <button
                        v-if="!todayAttendance || !todayAttendance.is_checked_in"
                        @click="checkIn"
                        :disabled="checkingIn"
                        class="flex items-center justify-center gap-2 px-6 py-4 bg-white text-purple-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <CheckCircleIcon class="w-6 h-6" />
                        <span v-if="checkingIn">{{ t('dashboard.hr.checking_in') }}</span>
                        <span v-else>Check-in</span>
                    </button>

                    <button
                        v-else-if="!todayAttendance.is_checked_out"
                        @click="checkOut"
                        :disabled="checkingOut"
                        class="flex items-center justify-center gap-2 px-6 py-4 bg-white text-purple-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <XCircleIcon class="w-6 h-6" />
                        <span v-if="checkingOut">{{ t('dashboard.hr.checking_out') }}</span>
                        <span v-else>Check-out</span>
                    </button>

                    <div v-else class="flex items-center justify-center gap-2 px-6 py-4 bg-white/20 rounded-xl">
                        <CheckCircleIcon class="w-6 h-6" />
                        <span class="font-semibold">{{ t('dashboard.hr.work_completed_today') }}</span>
                    </div>

                    <div v-if="todayAttendance" class="text-center">
                        <span class="inline-flex px-4 py-1 rounded-full text-sm font-medium bg-white/20">
                            {{ todayAttendance.status_label }}
                        </span>
                    </div>

                    <Link
                        :href="route('hr.attendance.index')"
                        class="text-center text-sm opacity-90 hover:opacity-100 transition-opacity underline"
                    >
                        {{ t('dashboard.view_details') }}
                    </Link>
                </div>
            </div>
        </div>

        <!-- Leave Balance Widget -->
        <div v-if="leaveBalances.length > 0" class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl p-6 text-white shadow-xl">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <CalendarIcon class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-sm opacity-90">{{ t('dashboard.hr.my_leave') }}</p>
                    <p class="text-xl font-bold">{{ new Date().getFullYear() }}-{{ t('dashboard.hr.year_balance') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div
                    v-for="balance in leaveBalances"
                    :key="balance.id"
                    class="bg-white/10 backdrop-blur-sm rounded-xl p-4"
                >
                    <div class="flex items-start justify-between mb-2">
                        <BanknotesIcon class="w-5 h-5 opacity-80" />
                        <span class="text-2xl font-bold">{{ balance.available_days }}</span>
                    </div>
                    <p class="text-sm opacity-90">{{ balance.leave_type.name }}</p>
                    <p class="text-xs opacity-70 mt-1">
                        {{ balance.used_days }}/{{ balance.total_days }} {{ t('dashboard.hr.used') }}
                    </p>
                </div>
            </div>

            <div v-if="upcomingLeaves.length > 0" class="mt-6 pt-6 border-t border-white/20">
                <p class="text-sm font-semibold mb-3 opacity-90">{{ t('dashboard.hr.upcoming_leaves') }}:</p>
                <div class="space-y-2">
                    <div
                        v-for="leave in upcomingLeaves"
                        :key="leave.id"
                        class="bg-white/10 backdrop-blur-sm rounded-lg p-3 flex items-center justify-between"
                    >
                        <div>
                            <p class="text-sm font-medium">{{ leave.leave_type }}</p>
                            <p class="text-xs opacity-75">{{ leave.start_date }} - {{ leave.end_date }}</p>
                        </div>
                        <span class="text-lg font-bold">{{ leave.total_days }} {{ t('dashboard.hr.days') }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center">
                <Link
                    :href="route('hr.leave.index')"
                    class="text-sm opacity-90 hover:opacity-100 transition-opacity underline"
                >
                    {{ t('dashboard.view_details') }}
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Activities -->
            <DashboardCard
                :title="t('dashboard.hr.recent_activities')"
                :link-href="'/hr/team'"
                divided
                no-padding
            >
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="activity in recentActivities"
                        :key="activity.id"
                        class="p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ activity.user }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ activity.description }}
                                    <span v-if="activity.department" class="text-xs">
                                        • {{ activity.department }}
                                    </span>
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    {{ activity.date }}
                                </p>
                            </div>
                            <span
                                :class="[
                                    'ml-3 px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap',
                                    getActivityColor(activity.type)
                                ]"
                            >
                                {{
                                    activity.type === 'new_member' ? t('dashboard.hr.activity_new') :
                                    activity.type === 'accepted' ? t('dashboard.hr.activity_accepted') :
                                    activity.type === 'department_change' ? t('dashboard.hr.activity_changed') :
                                    t('dashboard.hr.activity_sent')
                                }}
                            </span>
                        </div>
                    </div>
                    <div
                        v-if="!recentActivities?.length"
                        class="p-8 text-center text-gray-500 dark:text-gray-400"
                    >
                        {{ t('dashboard.hr.no_activities') }}
                    </div>
                </div>
            </DashboardCard>

            <!-- Pending Tasks -->
            <DashboardCard
                :title="t('dashboard.hr.pending_tasks')"
                :link-href="'/hr/tasks'"
                divided
                no-padding
            >
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="task in pendingTasks"
                        :key="task.id"
                        class="p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ task.title }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    {{ t('dashboard.hr.deadline') }}: {{ task.due_date }}
                                </p>
                            </div>
                            <span
                                :class="[
                                    'ml-3 px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap',
                                    getPriorityColor(task.priority)
                                ]"
                            >
                                {{ task.priority === 'high' ? t('priority.high') : task.priority === 'medium' ? t('priority.medium') : t('priority.low') }}
                            </span>
                        </div>
                    </div>
                    <div
                        v-if="!pendingTasks?.length"
                        class="p-8 text-center text-gray-500 dark:text-gray-400"
                    >
                        {{ t('dashboard.hr.no_tasks') }}
                    </div>
                </div>
            </DashboardCard>
        </div>

        <!-- Quick Actions -->
        <QuickActions :actions="quickActions" />
    </div>
</template>
