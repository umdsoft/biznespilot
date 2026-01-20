<template>
  <SalesHeadLayout :title="t('nav.kpi')">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
            Sotuv KPI Dashboard
          </h2>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Jamoa samaradorligi va KPI ko'rsatkichlari
          </p>
        </div>
        <div class="flex gap-3">
          <button
            v-for="p in periods"
            :key="p.key"
            @click="handlePeriodChange(p.key)"
            :class="[
              'px-4 py-2 rounded-lg font-medium transition-all',
              periodType === p.key
                ? 'bg-emerald-600 text-white shadow-md'
                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
            ]"
          >
            {{ p.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
      <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
        </div>
        <p class="text-emerald-100 text-sm font-medium mb-1">O'rtacha KPI</p>
        <p class="text-3xl font-bold">{{ stats.avg_score }}%</p>
      </div>

      <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
            </svg>
          </div>
        </div>
        <p class="text-blue-100 text-sm font-medium mb-1">Top Natija</p>
        <p class="text-3xl font-bold">{{ stats.top_performer_score }}%</p>
      </div>

      <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <p class="text-green-100 text-sm font-medium mb-1">Maqsadga yetgan</p>
        <p class="text-3xl font-bold">{{ stats.above_target }}</p>
      </div>

      <div class="bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <p class="text-yellow-100 text-sm font-medium mb-1">Yaxshilash kerak</p>
        <p class="text-3xl font-bold">{{ stats.below_target }}</p>
      </div>

      <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
        </div>
        <p class="text-purple-100 text-sm font-medium mb-1">Jamoa</p>
        <p class="text-3xl font-bold">{{ stats.team_size }}</p>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
      <!-- Team KPI Table -->
      <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Jamoa KPI
          </h3>
          <a href="/sales-head/sales-kpi/targets" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">
            Maqsadlarni sozlash &rarr;
          </a>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">#</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Xodim</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">KPI Ball</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Holat</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amallar</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="(member, index) in teamMembers" :key="member.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                  {{ index + 1 }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-medium text-sm"
                         :class="getAvatarClass(index)">
                      {{ member.avatar }}
                    </div>
                    <span class="ml-3 font-medium text-gray-900 dark:text-white">{{ member.name }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <div class="flex flex-col items-center">
                    <span class="text-lg font-bold" :class="getScoreColor(member.overall_score)">
                      {{ member.overall_score }}%
                    </span>
                    <div class="w-20 bg-gray-200 dark:bg-gray-600 rounded-full h-1.5 mt-1">
                      <div class="h-1.5 rounded-full" :class="getProgressColor(member.overall_score)"
                           :style="{ width: Math.min(member.overall_score, 100) + '%' }"></div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <span :class="getTierBadgeClass(member.performance_tier)">
                    {{ getTierLabel(member.performance_tier) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <a :href="`/sales-head/sales-kpi/operator/${member.id}`"
                     class="text-emerald-600 hover:text-emerald-700 text-sm font-medium">
                    Batafsil
                  </a>
                </td>
              </tr>
              <tr v-if="teamMembers.length === 0">
                <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                  Jamoa a'zolari topilmadi
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Right Sidebar -->
      <div class="space-y-6">
        <!-- Leaderboard Mini -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
              </svg>
              Reyting
            </h3>
            <a href="/sales-head/sales-kpi/leaderboard" class="text-sm text-emerald-600 hover:text-emerald-700">
              Barchasi &rarr;
            </a>
          </div>
          <div class="p-4">
            <div v-for="(entry, index) in leaderboard" :key="entry.user_id"
                 class="flex items-center py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
              <div class="w-8 h-8 flex items-center justify-center font-bold text-lg"
                   :class="getMedalClass(index + 1)">
                {{ getMedalIcon(index + 1) }}
              </div>
              <div class="ml-3 flex-1">
                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ entry.user?.name }}</p>
                <p class="text-xs text-gray-500">{{ entry.total_score }} ball</p>
              </div>
              <div v-if="entry.rank_change" class="text-xs"
                   :class="entry.rank_change > 0 ? 'text-green-500' : 'text-red-500'">
                {{ entry.rank_change > 0 ? '+' : '' }}{{ entry.rank_change }}
              </div>
            </div>
            <div v-if="leaderboard.length === 0" class="py-4 text-center text-gray-500 text-sm">
              Ma'lumot yo'q
            </div>
          </div>
        </div>

        <!-- Recent Achievements -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
              </svg>
              So'nggi yutuqlar
            </h3>
            <a href="/sales-head/sales-kpi/achievements" class="text-sm text-emerald-600 hover:text-emerald-700">
              Barchasi &rarr;
            </a>
          </div>
          <div class="p-4">
            <div v-for="achievement in recentAchievements" :key="achievement.id"
                 class="flex items-center py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
              <div class="w-10 h-10 rounded-lg flex items-center justify-center text-lg"
                   :style="{ backgroundColor: achievement.achievement?.tier_color + '20' }">
                {{ achievement.achievement?.icon || '🏆' }}
              </div>
              <div class="ml-3 flex-1">
                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ achievement.achievement?.name }}</p>
                <p class="text-xs text-gray-500">{{ achievement.user?.name }}</p>
              </div>
              <span class="text-xs text-emerald-600 font-medium">+{{ achievement.points_awarded }}</span>
            </div>
            <div v-if="recentAchievements.length === 0" class="py-4 text-center text-gray-500 text-sm">
              Hozircha yutuqlar yo'q
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <a href="/sales-head/sales-kpi/settings" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow group">
        <div class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
          <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Sozlamalar</h4>
        <p class="text-sm text-gray-500">KPI, Bonus, Jarima</p>
      </a>

      <a href="/sales-head/sales-kpi/bonuses" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow group">
        <div class="w-12 h-12 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
          <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Bonuslar</h4>
        <p class="text-sm text-gray-500">Tasdiqlash va hisobot</p>
      </a>

      <a href="/sales-head/sales-kpi/penalties" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow group">
        <div class="w-12 h-12 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
          <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Jarimalar</h4>
        <p class="text-sm text-gray-500">Ko'rib chiqish</p>
      </a>

      <a href="/sales-head/sales-kpi/leaderboard" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow group">
        <div class="w-12 h-12 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
          <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
          </svg>
        </div>
        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Leaderboard</h4>
        <p class="text-sm text-gray-500">Reyting taxtasi</p>
      </a>
    </div>
  </SalesHeadLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
  teamMembers: Array,
  leaderboard: Array,
  stats: Object,
  recentAchievements: Array,
  periodType: String,
  panelType: String,
});

const periods = [
  { key: 'daily', label: 'Kunlik' },
  { key: 'weekly', label: 'Haftalik' },
  { key: 'monthly', label: 'Oylik' },
];

const performanceTiers = {
  exceptional: { label: "A'lo", color: 'blue' },
  excellent: { label: 'Juda yaxshi', color: 'green' },
  good: { label: 'Yaxshi', color: 'teal' },
  meets: { label: 'Qoniqarli', color: 'yellow' },
  developing: { label: 'Rivojlanmoqda', color: 'orange' },
  needs_improvement: { label: 'Yaxshilash kerak', color: 'red' },
};

const handlePeriodChange = (period) => {
  router.get('/sales-head/sales-kpi', { period }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const getAvatarClass = (index) => {
  const colors = [
    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400',
  ];
  return colors[index % colors.length];
};

const getScoreColor = (score) => {
  if (score >= 90) return 'text-blue-600 dark:text-blue-400';
  if (score >= 75) return 'text-green-600 dark:text-green-400';
  if (score >= 60) return 'text-teal-600 dark:text-teal-400';
  if (score >= 45) return 'text-yellow-600 dark:text-yellow-400';
  return 'text-red-600 dark:text-red-400';
};

const getProgressColor = (score) => {
  if (score >= 90) return 'bg-blue-500';
  if (score >= 75) return 'bg-green-500';
  if (score >= 60) return 'bg-teal-500';
  if (score >= 45) return 'bg-yellow-500';
  return 'bg-red-500';
};

const getTierLabel = (tier) => {
  return performanceTiers[tier]?.label || tier;
};

const getTierBadgeClass = (tier) => {
  const color = performanceTiers[tier]?.color || 'gray';
  return `px-2 py-1 rounded-full text-xs font-medium bg-${color}-100 dark:bg-${color}-900/30 text-${color}-700 dark:text-${color}-400`;
};

const getMedalClass = (rank) => {
  if (rank === 1) return 'text-yellow-500';
  if (rank === 2) return 'text-gray-400';
  if (rank === 3) return 'text-orange-600';
  return 'text-gray-400';
};

const getMedalIcon = (rank) => {
  if (rank === 1) return '🥇';
  if (rank === 2) return '🥈';
  if (rank === 3) return '🥉';
  return rank;
};
</script>
