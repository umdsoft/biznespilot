<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    UsersIcon,
    UserGroupIcon,
    BuildingOfficeIcon,
    BellIcon,
    CalendarIcon,
    BanknotesIcon,
} from '@heroicons/vue/24/outline';
import StatCard from './StatCard.vue';
import DashboardCard from './DashboardCard.vue';
import EngagementWidget from '@/components/HR/EngagementWidget.vue';
import FlightRiskWidget from '@/components/HR/FlightRiskWidget.vue';
import OnboardingWidget from '@/components/HR/OnboardingWidget.vue';
import HRAlertsList from '@/components/HR/HRAlertsList.vue';
import { useI18n } from '@/i18n';
import axios from 'axios';

const { t } = useI18n();
const page = usePage();
const userName = computed(() => page.props.auth?.user?.name || t('dashboard.hr.hr_panel'));

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    recentActivities: { type: Array, default: () => [] },
    pendingTasks: { type: Array, default: () => [] },
    leaveBalances: { type: Array, default: () => [] },
    upcomingLeaves: { type: Array, default: () => [] },
    currentBusiness: { type: Object, default: null },
});

// HR Analytics Data
const engagementData = ref(null);
const flightRiskData = ref(null);
const onboardingData = ref(null);
const alertsData = ref([]);
const alertsUnreadCount = ref(0);
const loadingAnalytics = ref(false);

// Fetch HR Analytics Data
const fetchHRAnalytics = async () => {
    if (!props.currentBusiness?.id) return;

    loadingAnalytics.value = true;
    try {
        const [engagementRes, flightRiskRes, onboardingRes, alertsRes] = await Promise.all([
            axios.get(`/api/v1/businesses/${props.currentBusiness.id}/hr/engagement/statistics`).catch(() => ({ data: { data: null } })),
            axios.get(`/api/v1/businesses/${props.currentBusiness.id}/hr/flight-risk/statistics`).catch(() => ({ data: { data: null } })),
            axios.get(`/api/v1/businesses/${props.currentBusiness.id}/hr/onboarding/statistics`).catch(() => ({ data: { data: null } })),
            axios.get(`/api/v1/businesses/${props.currentBusiness.id}/hr/alerts?limit=5`).catch(() => ({ data: { data: { data: [] } } })),
        ]);

        engagementData.value = engagementRes.data.data;
        flightRiskData.value = flightRiskRes.data.data;
        onboardingData.value = onboardingRes.data.data;
        alertsData.value = alertsRes.data.data?.data || [];
        alertsUnreadCount.value = alertsData.value.filter(a => a.status === 'new').length;
    } catch (error) {
        console.error('Error fetching HR analytics:', error);
    } finally {
        loadingAnalytics.value = false;
    }
};

// Alert handlers
const handleAlertAcknowledge = async (alertId) => {
    if (!props.currentBusiness?.id) return;
    try {
        await axios.post(`/api/v1/businesses/${props.currentBusiness.id}/hr/alerts/${alertId}/acknowledge`);
        fetchHRAnalytics();
    } catch (error) {
        console.error('Error acknowledging alert:', error);
    }
};

const handleAlertResolve = async (alertId) => {
    if (!props.currentBusiness?.id) return;
    try {
        await axios.post(`/api/v1/businesses/${props.currentBusiness.id}/hr/alerts/${alertId}/resolve`);
        fetchHRAnalytics();
    } catch (error) {
        console.error('Error resolving alert:', error);
    }
};

const handleMarkAllSeen = async () => {
    if (!props.currentBusiness?.id) return;
    try {
        await axios.post(`/api/v1/businesses/${props.currentBusiness.id}/hr/alerts/mark-all-seen`);
        fetchHRAnalytics();
    } catch (error) {
        console.error('Error marking all as seen:', error);
    }
};

onMounted(() => {
    fetchHRAnalytics();
});

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
                    {{ t('dashboard.hr.welcome', { name: userName }) }}
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

        <!-- HR Analytics Section -->
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    HR Analytics
                </h2>
                <span v-if="loadingAnalytics" class="text-sm text-gray-500">Yuklanmoqda...</span>
            </div>

            <!-- Analytics Widgets Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-6">
                <!-- Engagement Widget -->
                <EngagementWidget
                    v-if="engagementData"
                    :avg-score="engagementData.overview?.avg_score || 0"
                    :highly-engaged="engagementData.distribution?.highly_engaged?.count || 0"
                    :engaged="engagementData.distribution?.engaged?.count || 0"
                    :neutral="engagementData.distribution?.neutral?.count || 0"
                    :disengaged="engagementData.distribution?.disengaged?.count || 0"
                    :business-id="currentBusiness?.id"
                />

                <!-- Flight Risk Widget -->
                <FlightRiskWidget
                    v-if="flightRiskData"
                    :total-at-risk="(flightRiskData.distribution?.critical?.count || 0) + (flightRiskData.distribution?.high?.count || 0)"
                    :critical-count="flightRiskData.distribution?.critical?.count || 0"
                    :high-count="flightRiskData.distribution?.high?.count || 0"
                    :medium-count="flightRiskData.distribution?.medium?.count || 0"
                    :top-risk-employees="flightRiskData.top_risk_employees || []"
                    :business-id="currentBusiness?.id"
                />

                <!-- Onboarding Widget -->
                <OnboardingWidget
                    v-if="onboardingData"
                    :active-plans="onboardingData.active_plans || 0"
                    :completed-plans="onboardingData.completed_plans || 0"
                    :overdue-tasks-count="onboardingData.overdue_tasks_count || 0"
                    :recent-onboardings="onboardingData.recent_onboardings || []"
                    :milestone-scores="onboardingData.milestone_completion_rates || { day_30: 0, day_60: 0, day_90: 0 }"
                    :business-id="currentBusiness?.id"
                />

                <!-- Alerts Widget -->
                <HRAlertsList
                    :alerts="alertsData"
                    :unread-count="alertsUnreadCount"
                    :business-id="currentBusiness?.id"
                    @acknowledge="handleAlertAcknowledge"
                    @resolve="handleAlertResolve"
                    @mark-all-seen="handleMarkAllSeen"
                />
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
    </div>
</template>
