<template>
  <SalesHeadLayout title="Dashboard">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sotuv Bo'limi Dashboard</h1>
          <p class="text-gray-500 dark:text-gray-400 mt-1">Bugungi holat va asosiy ko'rsatkichlar</p>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-500 dark:text-gray-400">
            {{ currentDate }}
          </span>
          <button
            @click="refreshData"
            :disabled="isRefreshing"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors flex items-center gap-2"
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
        <!-- New Leads Today -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Bugungi yangi leadlar</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ leadStats.new_today }}</p>
            </div>
            <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
              </svg>
            </div>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">Faol leadlar: {{ leadStats.total_active }}</p>
        </div>

        <!-- Won This Month -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Oylik yutilgan</p>
              <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">{{ leadStats.won_this_month }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">Konversiya: {{ leadStats.conversion_rate }}%</p>
        </div>

        <!-- Today Revenue -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Bugungi daromad</p>
              <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2">{{ formatCurrency(revenueStats.today) }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">Oylik: {{ formatCurrency(revenueStats.this_month) }}</p>
        </div>

        <!-- Team Members -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Operatorlar soni</p>
              <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-2">{{ teamMembers.length }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
          </div>
          <Link href="/sales-head/team" class="text-xs text-emerald-600 dark:text-emerald-400 mt-3 inline-flex items-center hover:underline">
            Batafsil ko'rish
            <svg class="w-3 h-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </Link>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Pipeline Summary -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Sotuv Voronkasi</h2>
            <Link href="/sales-head/pipeline" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">
              Batafsil
            </Link>
          </div>

          <div class="space-y-4">
            <div v-for="(stage, key) in pipelineStages" :key="key" class="flex items-center">
              <div class="w-32 text-sm text-gray-600 dark:text-gray-400">{{ stage.label }}</div>
              <div class="flex-1 mx-4">
                <div class="h-8 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
                  <div
                    :class="stage.color"
                    class="h-full rounded-lg transition-all duration-500"
                    :style="{ width: getStageWidth(key) + '%' }"
                  ></div>
                </div>
              </div>
              <div class="w-20 text-right">
                <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ pipeline[key]?.count || 0 }}</span>
                <span class="text-xs text-gray-500 ml-1">lead</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Team Performance -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Top Operatorlar</h2>
            <Link href="/sales-head/performance" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">
              Barchasi
            </Link>
          </div>

          <div class="space-y-4">
            <div
              v-for="(member, index) in teamPerformance.slice(0, 5)"
              :key="member.id"
              class="flex items-center gap-3"
            >
              <div
                class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
                :class="index === 0 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'"
              >
                {{ index + 1 }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ member.name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ member.leads_won }} bitim</p>
              </div>
              <div class="text-right">
                <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(member.revenue) }}</p>
              </div>
            </div>

            <div v-if="teamPerformance.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
              <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <p>Hali operator yo'q</p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Leads -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">So'nggi Leadlar</h2>
            <Link href="/sales-head/leads" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">
              Barchasi
            </Link>
          </div>

          <div class="space-y-3">
            <div
              v-for="lead in recentLeads"
              :key="lead.id"
              class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            >
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center">
                  <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                    {{ getInitials(lead.name) }}
                  </span>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{ lead.name }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ lead.phone || lead.email }}</p>
                </div>
              </div>
              <div class="text-right">
                <span :class="getStatusClass(lead.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                  {{ getStatusLabel(lead.status) }}
                </span>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ formatDate(lead.created_at) }}</p>
              </div>
            </div>

            <div v-if="recentLeads.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
              <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
              </svg>
              <p>Hali lead yo'q</p>
            </div>
          </div>
        </div>

        <!-- Overdue Tasks -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
              Kechikkan Vazifalar
              <span v-if="overdueTasks.length > 0" class="ml-2 px-2 py-0.5 text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-full">
                {{ overdueTasks.length }}
              </span>
            </h2>
            <Link href="/sales-head/tasks" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">
              Barchasi
            </Link>
          </div>

          <div class="space-y-3">
            <div
              v-for="task in overdueTasks"
              :key="task.id"
              class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800"
            >
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                  <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{ task.title }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ task.assignee?.name || 'Tayinlanmagan' }}</p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ getOverdueDays(task.due_date) }} kun kechikdi</p>
              </div>
            </div>

            <div v-if="overdueTasks.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
              <svg class="w-12 h-12 mx-auto mb-3 opacity-50 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <p class="text-green-600 dark:text-green-400">Kechikkan vazifa yo'q!</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </SalesHeadLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';

const props = defineProps({
  teamMembers: { type: Array, default: () => [] },
  leadStats: { type: Object, default: () => ({}) },
  revenueStats: { type: Object, default: () => ({}) },
  pipeline: { type: Object, default: () => ({}) },
  teamPerformance: { type: Array, default: () => [] },
  recentLeads: { type: Array, default: () => [] },
  overdueTasks: { type: Array, default: () => [] },
});

const isRefreshing = ref(false);

// Pipeline stages config
const pipelineStages = {
  new: { label: 'Yangi', color: 'bg-blue-500' },
  contacted: { label: 'Bog\'lanildi', color: 'bg-yellow-500' },
  qualified: { label: 'Kvalifikatsiya', color: 'bg-purple-500' },
  proposal: { label: 'Taklif', color: 'bg-orange-500' },
  negotiation: { label: 'Muzokara', color: 'bg-pink-500' },
};

// Current date
const currentDate = computed(() => {
  return new Date().toLocaleDateString('uz-UZ', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
});

// Format currency
const formatCurrency = (value) => {
  if (!value) return "0 so'm";
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

// Format date
const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('uz-UZ', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Get initials
const getInitials = (name) => {
  if (!name) return '?';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

// Get stage width for pipeline
const getStageWidth = (stage) => {
  const total = Object.values(props.pipeline).reduce((sum, s) => sum + (s?.count || 0), 0);
  if (total === 0) return 0;
  return Math.round(((props.pipeline[stage]?.count || 0) / total) * 100);
};

// Get status class
const getStatusClass = (status) => {
  const classes = {
    new: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    contacted: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    qualified: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    proposal: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    negotiation: 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400',
    won: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    lost: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
  };
  return classes[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400';
};

// Get status label
const getStatusLabel = (status) => {
  const labels = {
    new: 'Yangi',
    contacted: "Bog'lanildi",
    qualified: 'Kvalifikatsiya',
    proposal: 'Taklif',
    negotiation: 'Muzokara',
    won: 'Yutildi',
    lost: "Yo'qotildi",
  };
  return labels[status] || status;
};

// Get overdue days
const getOverdueDays = (dueDate) => {
  if (!dueDate) return 0;
  const due = new Date(dueDate);
  const now = new Date();
  const diff = Math.floor((now - due) / (1000 * 60 * 60 * 24));
  return diff > 0 ? diff : 0;
};

// Refresh data
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
