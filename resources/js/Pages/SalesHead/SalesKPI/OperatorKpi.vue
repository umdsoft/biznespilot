<template>
  <SalesHeadLayout :title="user?.name + ' - KPI'">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center gap-4 mb-4">
        <button @click="goBack" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
          <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
        </button>
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-700 dark:text-emerald-400 font-bold text-xl">
            {{ getInitials(user?.name) }}
          </div>
          <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ user?.name }}</h2>
            <p class="text-gray-600 dark:text-gray-400">{{ user?.position || 'Sotuv operatori' }}</p>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <select v-model="selectedPeriod" @change="filterByPeriod"
                class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800">
          <option value="daily">Kunlik</option>
          <option value="weekly">Haftalik</option>
          <option value="monthly">Oylik</option>
        </select>
        <input v-model="selectedDate" type="date" @change="filterByPeriod"
               class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800">
      </div>
    </div>

    <!-- Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
      <div class="bg-gradient-to-r from-emerald-500 to-green-500 rounded-xl p-6 text-white">
        <p class="text-sm text-emerald-100 mb-1">Umumiy ball</p>
        <p class="text-3xl font-bold">{{ summary?.overall_score || 0 }}%</p>
        <p v-if="scoreChange" :class="scoreChange > 0 ? 'text-emerald-200' : 'text-red-200'" class="text-sm mt-1">
          {{ scoreChange > 0 ? '+' : '' }}{{ scoreChange }}% oldingi davrdan
        </p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 mb-1">Reyting</p>
        <div class="flex items-center gap-2">
          <p class="text-2xl font-bold text-gray-900 dark:text-white">#{{ summary?.rank_in_team || '-' }}</p>
          <span v-if="summary?.rank_change" :class="getRankChangeClass(summary.rank_change)">
            {{ summary.rank_change > 0 ? '↑' : '↓' }} {{ Math.abs(summary.rank_change) }}
          </span>
        </div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 mb-1">Yutuqlar</p>
        <p class="text-2xl font-bold text-yellow-600">{{ userStats?.achievements_count || 0 }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 mb-1">Ballar</p>
        <p class="text-2xl font-bold text-purple-600">{{ userStats?.total_points || 0 }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 mb-1">Streak</p>
        <p class="text-2xl font-bold text-orange-600">{{ userStats?.current_streak || 0 }} kun</p>
      </div>
    </div>

    <!-- KPI Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
      <!-- KPI Cards -->
      <div class="lg:col-span-2 space-y-4">
        <h3 class="font-bold text-gray-900 dark:text-white">KPI ko'rsatkichlari</h3>
        <div v-for="kpi in kpiDetails" :key="kpi.kpi_setting_id"
             class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h4 class="font-bold text-gray-900 dark:text-white">{{ kpi.name }}</h4>
              <p class="text-sm text-gray-500">{{ kpi.description }}</p>
            </div>
            <span :class="getScoreBadge(kpi.score)" class="text-lg font-bold">{{ kpi.score }}%</span>
          </div>
          <div class="flex items-center justify-between text-sm mb-2">
            <span class="text-gray-500">Haqiqiy: {{ kpi.actual }} {{ kpi.unit }}</span>
            <span class="text-gray-500">Maqsad: {{ kpi.target }} {{ kpi.unit }}</span>
          </div>
          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
            <div :class="getProgressColor(kpi.score)"
                 :style="{ width: Math.min(kpi.score, 100) + '%' }"
                 class="h-3 rounded-full transition-all duration-500"></div>
          </div>
          <div class="mt-3 flex items-center justify-between text-sm">
            <span class="text-gray-500">Vazn: {{ kpi.weight }}%</span>
            <span :class="kpi.score >= 100 ? 'text-green-600' : 'text-gray-600'" class="font-medium">
              {{ kpi.score >= 100 ? 'Maqsadga yetdi ✓' : 'Davom etilmoqda' }}
            </span>
          </div>
        </div>
        <div v-if="kpiDetails.length === 0"
             class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center text-gray-500 border border-gray-200 dark:border-gray-700">
          Bu davr uchun KPI ma'lumotlari yo'q
        </div>
      </div>

      <!-- Side Panel -->
      <div class="space-y-6">
        <!-- Recent Achievements -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h3 class="font-bold text-gray-900 dark:text-white mb-4">So'nggi yutuqlar</h3>
          <div class="space-y-3">
            <div v-for="achievement in recentAchievements" :key="achievement.id"
                 class="flex items-center gap-3">
              <div :class="['w-10 h-10 rounded-lg flex items-center justify-center text-lg', getTierBackground(achievement.achievement?.tier)]">
                {{ achievement.achievement?.icon || '🏆' }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-900 dark:text-white truncate">{{ achievement.achievement?.name }}</p>
                <p class="text-xs text-gray-500">{{ formatDate(achievement.earned_at) }}</p>
              </div>
            </div>
            <div v-if="recentAchievements.length === 0" class="text-center text-gray-500 text-sm py-4">
              Yutuqlar yo'q
            </div>
          </div>
        </div>

        <!-- Penalties This Month -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h3 class="font-bold text-gray-900 dark:text-white mb-4">Bu oylik jarimalar</h3>
          <div class="space-y-3">
            <div v-for="penalty in penalties" :key="penalty.id"
                 class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
              <div>
                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ penalty.penalty_rule?.name || penalty.reason }}</p>
                <p class="text-xs text-gray-500">{{ formatDate(penalty.triggered_at) }}</p>
              </div>
              <span class="text-red-600 font-bold text-sm">-{{ formatCurrency(penalty.penalty_amount) }}</span>
            </div>
            <div v-if="penalties.length === 0" class="text-center text-green-600 text-sm py-4">
              Jarimalar yo'q ✓
            </div>
          </div>
        </div>

        <!-- Bonus Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h3 class="font-bold text-gray-900 dark:text-white mb-4">Bonus holati</h3>
          <div v-if="currentBonus" class="space-y-3">
            <div class="flex justify-between">
              <span class="text-gray-500">KPI ball:</span>
              <span class="font-bold" :class="getScoreColor(currentBonus.kpi_score)">{{ currentBonus.kpi_score }}%</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Tier:</span>
              <span :class="getTierBadge(currentBonus.applied_tier)">{{ currentBonus.applied_tier }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Hisoblangan bonus:</span>
              <span class="font-bold text-green-600">{{ formatCurrency(currentBonus.base_amount) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Jarimalar:</span>
              <span class="font-bold text-red-600">-{{ formatCurrency(currentBonus.total_penalties || 0) }}</span>
            </div>
            <div class="pt-3 border-t border-gray-200 dark:border-gray-700 flex justify-between">
              <span class="font-medium text-gray-900 dark:text-white">Jami:</span>
              <span class="text-xl font-bold text-emerald-600">{{ formatCurrency(currentBonus.final_amount) }}</span>
            </div>
            <div class="pt-2">
              <span :class="getBonusStatusBadge(currentBonus.status)" class="w-full flex justify-center">
                {{ getBonusStatusLabel(currentBonus.status) }}
              </span>
            </div>
          </div>
          <div v-else class="text-center text-gray-500 py-4">
            Bu oy uchun bonus hali hisoblanmagan
          </div>
        </div>
      </div>
    </div>

    <!-- Historical Performance -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
      <h3 class="font-bold text-gray-900 dark:text-white mb-4">Tarixiy natijalar</h3>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Davr</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ball</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reyting</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tier</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="history in historicalData" :key="history.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ history.period_label }}</td>
              <td class="px-4 py-3 text-center">
                <span :class="getScoreBadge(history.overall_score)">{{ history.overall_score }}%</span>
              </td>
              <td class="px-4 py-3 text-center">
                <span class="font-medium">#{{ history.rank_in_team }}</span>
                <span v-if="history.rank_change" :class="getRankChangeClass(history.rank_change)" class="ml-1 text-xs">
                  {{ history.rank_change > 0 ? '↑' : '↓' }}{{ Math.abs(history.rank_change) }}
                </span>
              </td>
              <td class="px-4 py-3 text-center">
                <span :class="getPerformanceTierBadge(history.performance_tier)">
                  {{ history.tier_label }}
                </span>
              </td>
            </tr>
            <tr v-if="historicalData.length === 0">
              <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                Tarixiy ma'lumotlar yo'q
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </SalesHeadLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';

const props = defineProps({
  user: Object,
  summary: Object,
  kpiDetails: Array,
  userStats: Object,
  recentAchievements: Array,
  penalties: Array,
  currentBonus: Object,
  historicalData: Array,
  scoreChange: Number,
  period: String,
  date: String,
  panelType: String,
});

const selectedPeriod = ref(props.period || 'monthly');
const selectedDate = ref(props.date || new Date().toISOString().split('T')[0]);

const goBack = () => {
  router.get('/sales-head/sales-kpi/leaderboard');
};

const filterByPeriod = () => {
  router.get(`/sales-head/sales-kpi/operator/${props.user.id}`, {
    period: selectedPeriod.value,
    date: selectedDate.value,
  }, { preserveState: true });
};

const formatCurrency = (value) => {
  if (!value) return "0 so'm";
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short' });
};

const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';

const getScoreBadge = (score) => {
  if (score >= 100) return 'text-green-600';
  if (score >= 80) return 'text-blue-600';
  if (score >= 60) return 'text-yellow-600';
  return 'text-red-600';
};

const getScoreColor = (score) => {
  if (score >= 100) return 'text-green-600';
  if (score >= 80) return 'text-blue-600';
  return 'text-yellow-600';
};

const getProgressColor = (score) => {
  if (score >= 100) return 'bg-green-500';
  if (score >= 80) return 'bg-blue-500';
  if (score >= 60) return 'bg-yellow-500';
  return 'bg-red-500';
};

const getRankChangeClass = (change) => {
  if (change > 0) return 'text-green-600 text-sm';
  if (change < 0) return 'text-red-600 text-sm';
  return 'text-gray-500 text-sm';
};

const getTierBackground = (tier) => {
  const backgrounds = {
    bronze: 'bg-orange-100 dark:bg-orange-900/30',
    silver: 'bg-gray-200 dark:bg-gray-700',
    gold: 'bg-yellow-100 dark:bg-yellow-900/30',
    platinum: 'bg-blue-100 dark:bg-blue-900/30',
    diamond: 'bg-purple-100 dark:bg-purple-900/30',
  };
  return backgrounds[tier] || backgrounds.bronze;
};

const getTierBadge = (tier) => {
  const badges = {
    standard: 'px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700',
    excellent: 'px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-700',
    accelerator: 'px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700',
  };
  return badges[tier] || badges.standard;
};

const getPerformanceTierBadge = (tier) => {
  const badges = {
    exceptional: 'px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-700',
    excellent: 'px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700',
    good: 'px-2 py-1 rounded text-xs font-medium bg-teal-100 text-teal-700',
    meets: 'px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-700',
    developing: 'px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-700',
    needs_improvement: 'px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700',
  };
  return badges[tier] || 'px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700';
};

const getBonusStatusBadge = (status) => {
  const badges = {
    pending: 'px-3 py-2 rounded-lg text-sm font-medium bg-yellow-100 text-yellow-700',
    approved: 'px-3 py-2 rounded-lg text-sm font-medium bg-green-100 text-green-700',
    rejected: 'px-3 py-2 rounded-lg text-sm font-medium bg-red-100 text-red-700',
  };
  return badges[status] || badges.pending;
};

const getBonusStatusLabel = (status) => {
  const labels = { pending: 'Kutilmoqda', approved: 'Tasdiqlangan', rejected: 'Rad etilgan' };
  return labels[status] || status;
};
</script>
