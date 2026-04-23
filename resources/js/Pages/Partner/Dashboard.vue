<template>
  <AppLayout title="Partner Dashboard">
    <div class="max-w-[1600px] mx-auto">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Assalomu alaykum, {{ partner.full_name }}!
          </h2>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md font-mono text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
              {{ partner.code }}
            </span>
            <span :class="statusBadge(partner.status)" class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-md">
              {{ statusLabel(partner.status) }}
            </span>
            <span :class="tierBadge(partner.tier)" class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-md">
              {{ partner.tier_icon }} {{ partner.tier_name || partner.tier }}
            </span>
          </p>
        </div>
        <div class="text-right">
          <p class="text-xs text-gray-500 dark:text-gray-400">Stavkangiz</p>
          <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
            1-to'lov: <span class="text-blue-600 dark:text-blue-400">{{ (partner.first_payment_rate * 100).toFixed(0) }}%</span>
            &middot; Keyingi to'lovlar: <span class="text-emerald-600 dark:text-emerald-400">{{ (partner.lifetime_rate * 100).toFixed(0) }}%</span>
          </p>
        </div>
      </div>

      <!-- Tabs -->

      <!-- KPI Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-2 mb-1.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
              <CurrencyDollarIcon class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Jami daromad</p>
          </div>
          <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatMoney(kpi.lifetime_earned) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-2 mb-1.5">
            <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
              <ClockIcon class="w-4 h-4 text-amber-600 dark:text-amber-400" />
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Kutilayotgan</p>
          </div>
          <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatMoney(kpi.pending_balance) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-2 mb-1.5">
            <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
              <BanknotesIcon class="w-4 h-4 text-green-600 dark:text-green-400" />
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Mavjud</p>
          </div>
          <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ formatMoney(kpi.available_balance) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-2 mb-1.5">
            <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
              <UserGroupIcon class="w-4 h-4 text-blue-600 dark:text-blue-400" />
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Faol referrallar</p>
          </div>
          <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatNumber(kpi.active_referrals) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-2 mb-1.5">
            <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
              <ChartBarIcon class="w-4 h-4 text-purple-600 dark:text-purple-400" />
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Bu oy</p>
          </div>
          <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatMoney(kpi.this_month) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-2 mb-1.5">
            <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
              <UsersIcon class="w-4 h-4 text-gray-600 dark:text-gray-300" />
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Jami ref</p>
          </div>
          <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatNumber(kpi.referrals_count) }}</p>
        </div>
      </div>

      <!-- Chart -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 mb-6 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Daromad dinamikasi</h3>
          <span class="text-xs text-gray-400">Oxirgi 12 oy</span>
        </div>
        <div class="p-4">
          <div v-if="hasChartData" class="h-64">
            <apexchart type="area" height="256" :options="chartOptions" :series="chartSeries" />
          </div>
          <div v-else class="h-64 flex flex-col items-center justify-center">
            <ChartBarIcon class="w-10 h-10 text-gray-300 dark:text-gray-600 mb-2" />
            <p class="text-sm text-gray-400">Hali ma'lumot yo'q</p>
          </div>
        </div>
      </div>

      <!-- Tier progress -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 mb-6 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Tier dasturi</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Ko'proq referral — yuqori stavka</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-5">
          <div
            v-for="tier in tiers"
            :key="tier.tier"
            :class="[
              'rounded-xl p-4 border-2 transition-all',
              tier.is_current
                ? tierActiveBorder(tier.tier)
                : 'border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30'
            ]"
          >
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-2">
                <span class="text-2xl">{{ tier.icon }}</span>
                <span :class="tierBadge(tier.tier)" class="px-2 py-0.5 text-xs font-semibold rounded-md">
                  {{ tier.name }}
                </span>
              </div>
              <span v-if="tier.is_current" class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase">Joriy</span>
            </div>
            <div class="space-y-1 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">1-to'lov:</span>
                <span class="font-semibold text-blue-600 dark:text-blue-400">{{ (tier.first_payment_rate * 100).toFixed(0) }}%</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">Keyingi to'lovlar:</span>
                <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ (tier.lifetime_rate * 100).toFixed(0) }}%</span>
              </div>
              <div class="flex justify-between pt-1 border-t border-gray-200 dark:border-gray-700 mt-2">
                <span class="text-xs text-gray-500 dark:text-gray-400">Faol ref:</span>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-200">{{ tier.min_active_referrals }}+</span>
              </div>
            </div>
            <ul v-if="tier.perks && tier.perks.length" class="mt-3 space-y-1">
              <li v-for="(perk, i) in tier.perks" :key="i" class="flex items-start gap-1.5 text-xs text-gray-600 dark:text-gray-300">
                <CheckIcon class="w-3.5 h-3.5 text-green-500 shrink-0 mt-0.5" />
                <span>{{ perk }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Recent referrals -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Oxirgi referrallar</h3>
          <Link :href="route('partner.referrals')" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Barchasi &rarr;</Link>
        </div>
        <div class="overflow-x-auto">
          <table v-if="recent_referrals.length" class="w-full">
            <thead>
              <tr class="bg-gray-50 dark:bg-gray-900/40">
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Biznes</th>
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Status</th>
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden md:table-cell">Kanal</th>
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden sm:table-cell">Attribute sanasi</th>
                <th class="text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Daromad</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
              <tr v-for="r in recent_referrals" :key="r.id" class="hover:bg-gray-50 dark:hover:bg-gray-900/30">
                <td class="px-5 py-3 text-sm text-gray-900 dark:text-gray-100 font-medium">{{ r.business_name }}</td>
                <td class="px-5 py-3">
                  <span :class="referralStatusBadge(r.status)" class="px-2 py-0.5 text-xs font-medium rounded-md">{{ r.status }}</span>
                </td>
                <td class="px-5 py-3 text-sm text-gray-600 dark:text-gray-300 hidden md:table-cell">{{ r.via || '—' }}</td>
                <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400 hidden sm:table-cell">{{ r.attributed_at || '—' }}</td>
                <td class="px-5 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100 text-right">{{ formatMoney(r.lifetime_earned) }}</td>
              </tr>
            </tbody>
          </table>
          <div v-else class="text-center py-10">
            <UserGroupIcon class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
            <p class="text-sm text-gray-500 dark:text-gray-400">Hali referral yo'q. Havolangizni do'stlaringizga yuboring!</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, defineAsyncComponent } from 'vue';
import AppLayout from '@/Layouts/PartnerLayout.vue';
import {
  CurrencyDollarIcon,
  UsersIcon,
  UserGroupIcon,
  ClockIcon,
  BanknotesIcon,
  ChartBarIcon,
} from '@heroicons/vue/24/outline';

const apexchart = defineAsyncComponent(() => import('vue3-apexcharts').then(m => m.default || m));

const props = defineProps({
  partner: { type: Object, required: true },
  kpi: { type: Object, required: true },
  chart: { type: Array, default: () => [] },
  recent_referrals: { type: Array, default: () => [] },
  tiers: { type: Array, default: () => [] },
  min_payout: { type: Number, default: 0 },
});

const formatMoney = (v) => new Intl.NumberFormat('uz-UZ').format(Math.round(v || 0)) + " so'm";
const formatNumber = (v) => new Intl.NumberFormat('uz-UZ').format(v || 0);

const chartSeries = computed(() => [{
  name: 'Komissiya',
  data: (props.chart || []).map(item => Math.round(Number(item.total) || 0)),
}]);

const chartOptions = computed(() => ({
  chart: { toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
  colors: ['#6366f1'],
  stroke: { curve: 'smooth', width: 2 },
  fill: {
    type: 'gradient',
    gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 100] },
  },
  dataLabels: { enabled: false },
  xaxis: {
    categories: (props.chart || []).map(item => item.month),
    labels: { style: { colors: '#9ca3af', fontSize: '11px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      style: { colors: '#9ca3af', fontSize: '11px' },
      formatter: (v) => new Intl.NumberFormat('uz-UZ').format(Math.round(v)),
    },
  },
  grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
  tooltip: { theme: 'light', y: { formatter: (v) => new Intl.NumberFormat('uz-UZ').format(v) + " so'm" } },
}));

const hasChartData = computed(() => (props.chart || []).some(c => Number(c.total) > 0));

const statusLabel = (s) => ({
  pending: 'Ko\'rib chiqilmoqda',
  active: 'Faol',
  suspended: 'To\'xtatilgan',
  terminated: 'Bekor qilingan',
}[s] || s);

const statusBadge = (s) => ({
  pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
  active: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
  suspended: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  terminated: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
}[s] || 'bg-gray-100 text-gray-700');

const tierBadge = (t) => ({
  bronze: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
  silver: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
  gold: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
  platinum: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
}[t] || 'bg-gray-100 text-gray-700');

const tierActiveBorder = (t) => ({
  bronze: 'border-amber-400 bg-amber-50 dark:bg-amber-900/10',
  silver: 'border-slate-400 bg-slate-50 dark:bg-slate-800/40',
  gold: 'border-yellow-400 bg-yellow-50 dark:bg-yellow-900/10',
  platinum: 'border-indigo-400 bg-indigo-50 dark:bg-indigo-900/10',
}[t] || 'border-blue-400 bg-blue-50');

const referralStatusBadge = (s) => ({
  pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
  attributed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
  active: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
  inactive: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  churned: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
}[s] || 'bg-gray-100 text-gray-700');
</script>
