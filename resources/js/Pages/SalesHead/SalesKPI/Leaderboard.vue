<template>
  <SalesHeadLayout title="Leaderboard">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
            Reyting Taxtasi
          </h2>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Jamoa a'zolarining samaradorlik reytingi
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

    <!-- Records -->
    <div v-if="records && Object.keys(records).length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
      <div v-for="(record, type) in records" :key="type"
           class="bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-yellow-100 text-sm font-medium">{{ getRecordLabel(type) }}</p>
            <p class="text-2xl font-bold mt-1">{{ record.value }}</p>
            <p class="text-yellow-100 text-xs mt-1">{{ record.holder_name }}</p>
          </div>
          <div class="text-4xl">🏆</div>
        </div>
      </div>
    </div>

    <!-- Main Leaderboard -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
          <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
          </svg>
          {{ getPeriodLabel(periodType) }} Reyting
        </h3>
      </div>

      <!-- Top 3 Podium -->
      <div v-if="leaderboard.length >= 3" class="p-8 bg-gradient-to-b from-gray-50 to-white dark:from-gray-800 dark:to-gray-900">
        <div class="flex items-end justify-center gap-4">
          <!-- 2nd Place -->
          <div class="text-center">
            <div class="w-20 h-20 mx-auto mb-3 rounded-full bg-gradient-to-br from-gray-300 to-gray-400 flex items-center justify-center text-white text-2xl font-bold shadow-lg">
              {{ getInitials(leaderboard[1]?.user?.name) }}
            </div>
            <div class="text-3xl mb-2">🥈</div>
            <p class="font-semibold text-gray-900 dark:text-white">{{ leaderboard[1]?.user?.name }}</p>
            <p class="text-2xl font-bold text-gray-600 dark:text-gray-300">{{ leaderboard[1]?.total_score }}</p>
            <div class="h-24 w-24 mx-auto bg-gradient-to-t from-gray-300 to-gray-200 dark:from-gray-600 dark:to-gray-500 rounded-t-lg mt-4 flex items-center justify-center">
              <span class="text-4xl font-bold text-white/80">2</span>
            </div>
          </div>

          <!-- 1st Place -->
          <div class="text-center">
            <div class="w-24 h-24 mx-auto mb-3 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center text-white text-3xl font-bold shadow-lg ring-4 ring-yellow-300">
              {{ getInitials(leaderboard[0]?.user?.name) }}
            </div>
            <div class="text-4xl mb-2">🥇</div>
            <p class="font-bold text-lg text-gray-900 dark:text-white">{{ leaderboard[0]?.user?.name }}</p>
            <p class="text-3xl font-bold text-yellow-600">{{ leaderboard[0]?.total_score }}</p>
            <div class="h-32 w-28 mx-auto bg-gradient-to-t from-yellow-500 to-yellow-400 rounded-t-lg mt-4 flex items-center justify-center">
              <span class="text-5xl font-bold text-white/80">1</span>
            </div>
          </div>

          <!-- 3rd Place -->
          <div class="text-center">
            <div class="w-20 h-20 mx-auto mb-3 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-2xl font-bold shadow-lg">
              {{ getInitials(leaderboard[2]?.user?.name) }}
            </div>
            <div class="text-3xl mb-2">🥉</div>
            <p class="font-semibold text-gray-900 dark:text-white">{{ leaderboard[2]?.user?.name }}</p>
            <p class="text-2xl font-bold text-orange-600">{{ leaderboard[2]?.total_score }}</p>
            <div class="h-16 w-24 mx-auto bg-gradient-to-t from-orange-500 to-orange-400 rounded-t-lg mt-4 flex items-center justify-center">
              <span class="text-4xl font-bold text-white/80">3</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Full List -->
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">O'rin</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Xodim</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ball</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">O'zgarish</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lidlar</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Daromad</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="entry in leaderboard" :key="entry.id"
                :class="[
                  'hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors',
                  entry.rank <= 3 ? 'bg-yellow-50/50 dark:bg-yellow-900/10' : ''
                ]">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <span v-if="entry.rank === 1" class="text-2xl">🥇</span>
                  <span v-else-if="entry.rank === 2" class="text-2xl">🥈</span>
                  <span v-else-if="entry.rank === 3" class="text-2xl">🥉</span>
                  <span v-else class="text-lg font-medium text-gray-500 ml-2">{{ entry.rank }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold"
                       :class="getAvatarBg(entry.rank)">
                    {{ getInitials(entry.user?.name) }}
                  </div>
                  <div class="ml-3">
                    <p class="font-medium text-gray-900 dark:text-white">{{ entry.user?.name }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span class="text-lg font-bold text-gray-900 dark:text-white">{{ entry.total_score }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span v-if="entry.rank_change > 0" class="text-green-500 font-medium">
                  ↑{{ entry.rank_change }}
                </span>
                <span v-else-if="entry.rank_change < 0" class="text-red-500 font-medium">
                  ↓{{ Math.abs(entry.rank_change) }}
                </span>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center text-gray-600 dark:text-gray-300">
                {{ entry.leads_converted || 0 }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right font-medium text-gray-900 dark:text-white">
                {{ formatCurrency(entry.revenue) }}
              </td>
            </tr>
            <tr v-if="leaderboard.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                <div class="flex flex-col items-center">
                  <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                  </svg>
                  <p class="text-lg font-medium">Hozircha reyting ma'lumotlari yo'q</p>
                  <p class="text-sm mt-1">Ma'lumotlar yig'ilgach avtomatik yangilanadi</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </SalesHeadLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';

const props = defineProps({
  leaderboard: Array,
  records: Object,
  history: Object,
  periodType: String,
  medals: Object,
  panelType: String,
});

const periods = [
  { key: 'daily', label: 'Kunlik' },
  { key: 'weekly', label: 'Haftalik' },
  { key: 'monthly', label: 'Oylik' },
];

const handlePeriodChange = (period) => {
  router.get('/sales-head/sales-kpi/leaderboard', { period }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const getPeriodLabel = (type) => {
  const labels = { daily: 'Kunlik', weekly: 'Haftalik', monthly: 'Oylik' };
  return labels[type] || type;
};

const getRecordLabel = (type) => {
  const labels = {
    highest_score: 'Eng yuqori ball',
    most_sales: 'Eng ko\'p sotuv',
    highest_revenue: 'Eng yuqori daromad',
  };
  return labels[type] || type;
};

const getInitials = (name) => {
  if (!name) return '?';
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

const getAvatarBg = (rank) => {
  if (rank === 1) return 'bg-gradient-to-br from-yellow-400 to-orange-500';
  if (rank === 2) return 'bg-gradient-to-br from-gray-300 to-gray-400';
  if (rank === 3) return 'bg-gradient-to-br from-orange-400 to-orange-600';
  return 'bg-gradient-to-br from-emerald-400 to-emerald-600';
};

const formatCurrency = (value) => {
  if (!value) return "0 so'm";
  if (value >= 1000000) {
    return (value / 1000000).toFixed(1) + " mln";
  }
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};
</script>
