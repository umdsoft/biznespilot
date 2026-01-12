<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import StatCard from './StatCard.vue';
import DashboardCard from './DashboardCard.vue';
import LeadsList from './LeadsList.vue';
import TasksList from './TasksList.vue';
import PipelineChart from './PipelineChart.vue';
import TeamPerformance from './TeamPerformance.vue';
import {
    UserPlusIcon,
    CheckCircleIcon,
    CurrencyDollarIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    teamMembers: { type: Array, default: () => [] },
    leadStats: { type: Object, default: () => ({}) },
    revenueStats: { type: Object, default: () => ({}) },
    pipeline: { type: Object, default: () => ({}) },
    teamPerformance: { type: Array, default: () => [] },
    recentLeads: { type: Array, default: () => [] },
    overdueTasks: { type: Array, default: () => [] },
    panelType: {
        type: String,
        default: 'saleshead',
        validator: (v) => ['saleshead', 'business'].includes(v),
    },
});

const isRefreshing = ref(false);

const pipelineStages = {
    new: { label: 'Yangi', color: 'bg-blue-500' },
    contacted: { label: "Bog'lanildi", color: 'bg-yellow-500' },
    qualified: { label: 'Kvalifikatsiya', color: 'bg-purple-500' },
    proposal: { label: 'Taklif', color: 'bg-orange-500' },
    negotiation: { label: 'Muzokara', color: 'bg-pink-500' },
};

const currentDate = computed(() => {
    return new Date().toLocaleDateString('uz-UZ', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

const formatCurrency = (value) => {
    if (!value) return "0 so'm";
    return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

const getBasePath = () => {
    return props.panelType === 'saleshead' ? '/sales-head' : '/business';
};

const refreshData = () => {
    isRefreshing.value = true;
    router.reload({
        only: ['teamMembers', 'leadStats', 'revenueStats', 'pipeline', 'teamPerformance', 'recentLeads', 'overdueTasks'],
        onFinish: () => {
            isRefreshing.value = false;
        }
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sotuv Bo'limi Dashboard</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Bugungi holat va asosiy ko'rsatkichlar</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ currentDate }}</span>
                <button
                    @click="refreshData"
                    :disabled="isRefreshing"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors flex items-center gap-2 disabled:opacity-50"
                >
                    <svg :class="{ 'animate-spin': isRefreshing }" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Yangilash
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <StatCard
                title="Bugungi yangi leadlar"
                :value="leadStats.new_today || 0"
                :subtitle="`Faol leadlar: ${leadStats.total_active || 0}`"
                :icon="UserPlusIcon"
                icon-bg-color="emerald"
            />
            <StatCard
                title="Oylik yutilgan"
                :value="leadStats.won_this_month || 0"
                :subtitle="`Konversiya: ${leadStats.conversion_rate || 0}%`"
                :icon="CheckCircleIcon"
                icon-bg-color="green"
                value-color="emerald"
            />
            <StatCard
                title="Bugungi daromad"
                :value="formatCurrency(revenueStats.today)"
                :subtitle="`Oylik: ${formatCurrency(revenueStats.this_month)}`"
                :icon="CurrencyDollarIcon"
                icon-bg-color="blue"
                value-color="blue"
            />
            <StatCard
                title="Operatorlar soni"
                :value="teamMembers.length"
                :icon="UsersIcon"
                icon-bg-color="purple"
                value-color="purple"
                :href="`${getBasePath()}/team`"
                link-text="Batafsil ko'rish"
            />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Pipeline Summary -->
            <div class="lg:col-span-2">
                <DashboardCard
                    title="Sotuv Voronkasi"
                    :link-href="`${getBasePath()}/pipeline`"
                    link-text="Batafsil"
                >
                    <PipelineChart :pipeline="pipeline" :stages="pipelineStages" />
                </DashboardCard>
            </div>

            <!-- Team Performance -->
            <DashboardCard
                title="Top Operatorlar"
                :link-href="`${getBasePath()}/performance`"
                link-text="Barchasi"
            >
                <TeamPerformance :members="teamPerformance" :limit="5" />
            </DashboardCard>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Leads -->
            <DashboardCard
                title="So'nggi Leadlar"
                :link-href="`${getBasePath()}/leads`"
                link-text="Barchasi"
            >
                <LeadsList
                    :leads="recentLeads"
                    :base-path="`${getBasePath()}/leads`"
                    show-avatar
                    show-date
                />
            </DashboardCard>

            <!-- Overdue Tasks -->
            <DashboardCard
                title="Kechikkan Vazifalar"
                :badge="overdueTasks.length > 0 ? overdueTasks.length : null"
                badge-color="red"
                :link-href="`${getBasePath()}/tasks`"
                link-text="Barchasi"
            >
                <TasksList
                    :tasks="overdueTasks"
                    is-overdue
                    show-assignee
                    empty-text="Kechikkan vazifa yo'q!"
                    empty-icon="success"
                />
            </DashboardCard>
        </div>
    </div>
</template>
