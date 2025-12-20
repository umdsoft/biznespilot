<template>
  <BusinessLayout title="Dashboard">
    <!-- Welcome Section -->
    <div class="mb-8">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-3xl font-bold bg-gradient-to-r from-gray-900 via-blue-900 to-indigo-900 bg-clip-text text-transparent">
            Xush kelibsiz, {{ $page.props.auth?.user?.name }}! 👋
          </h2>
          <p class="mt-2 text-gray-600 flex items-center">
            <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span class="font-medium">{{ currentBusiness?.name }}</span>
          </p>
        </div>
        <div class="flex gap-3">
          <Link
            href="/business/dream-buyer/create"
            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all duration-200"
          >
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Dream Buyer
          </Link>
          <Link
            href="/business/marketing/campaigns/create"
            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg shadow-lg text-sm font-medium text-white hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 hover:shadow-xl"
          >
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Yangi Kampaniya
          </Link>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Leads Card -->
      <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
        <div class="relative p-6">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
          </div>
          <p class="text-purple-100 text-sm font-medium mb-1">Jami Leadlar</p>
          <p class="text-white text-3xl font-bold">{{ formatNumber(stats.total_leads) }}</p>
        </div>
      </div>

      <!-- Customers Card -->
      <div class="relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
        <div class="relative p-6">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
          </div>
          <p class="text-green-100 text-sm font-medium mb-1">Mijozlar</p>
          <p class="text-white text-3xl font-bold">{{ formatNumber(stats.total_customers) }}</p>
        </div>
      </div>

      <!-- Revenue Card -->
      <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
        <div class="relative p-6">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <p class="text-blue-100 text-sm font-medium mb-1">Daromad (30 kun)</p>
          <p class="text-white text-3xl font-bold">{{ formatCurrency(stats.total_revenue) }}</p>
        </div>
      </div>

      <!-- Conversion Card -->
      <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
        <div class="relative p-6">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
          </div>
          <p class="text-orange-100 text-sm font-medium mb-1">Konversiya</p>
          <p class="text-white text-3xl font-bold">{{ stats.conversion_rate }}%</p>
        </div>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
      <!-- CAC -->
      <Card title="CAC (Customer Acquisition Cost)">
        <div class="flex flex-col">
          <div class="text-3xl font-bold text-gray-900 mb-2">{{ formatCurrency(kpis.cac) }}</div>
          <p class="text-sm text-gray-500">Har bir mijozni jalb qilish narxi</p>
          <div class="mt-3 text-xs text-gray-600">
            <span class="font-medium">Benchmark:</span> CLV/3 dan kam bo'lishi kerak
          </div>
        </div>
      </Card>

      <!-- CLV -->
      <Card title="CLV (Customer Lifetime Value)">
        <div class="flex flex-col">
          <div class="text-3xl font-bold text-gray-900 mb-2">{{ formatCurrency(kpis.clv) }}</div>
          <p class="text-sm text-gray-500">Mijozning umrbod qiymati</p>
          <div class="mt-3 flex items-center">
            <span class="text-xs font-medium mr-2">LTV/CAC Ratio:</span>
            <span
              class="px-2 py-1 rounded text-xs font-medium"
              :class="{
                'bg-blue-100 text-blue-700': ltvCacBenchmark.color === 'blue',
                'bg-green-100 text-green-700': ltvCacBenchmark.color === 'green',
                'bg-yellow-100 text-yellow-700': ltvCacBenchmark.color === 'yellow',
                'bg-red-100 text-red-700': ltvCacBenchmark.color === 'red',
              }"
            >
              {{ kpis.ltv_cac_ratio }}x - {{ ltvCacBenchmark.label }}
            </span>
          </div>
        </div>
      </Card>

      <!-- ROAS -->
      <Card title="ROAS (Return on Ad Spend)">
        <div class="flex flex-col">
          <div class="text-3xl font-bold text-gray-900 mb-2">{{ kpis.roas }}x</div>
          <p class="text-sm text-gray-500">Reklama xarajatlaridan daromad</p>
          <div class="mt-3 flex items-center">
            <span
              class="px-2 py-1 rounded text-xs font-medium"
              :class="{
                'bg-blue-100 text-blue-700': roasBenchmark.color === 'blue',
                'bg-green-100 text-green-700': roasBenchmark.color === 'green',
                'bg-yellow-100 text-yellow-700': roasBenchmark.color === 'yellow',
                'bg-orange-100 text-orange-700': roasBenchmark.color === 'orange',
                'bg-red-100 text-red-700': roasBenchmark.color === 'red',
              }"
            >
              {{ roasBenchmark.label }}
            </span>
          </div>
        </div>
      </Card>

      <!-- ROI -->
      <Card title="ROI (Return on Investment)">
        <div class="flex flex-col">
          <div class="text-3xl font-bold text-gray-900 mb-2">{{ kpis.roi }}%</div>
          <p class="text-sm text-gray-500">Investitsiyadan daromad</p>
          <div class="mt-3 text-xs">
            <span class="font-medium">Target:</span>
            <span :class="kpis.roi >= 100 ? 'text-green-600' : 'text-red-600'">
              {{ kpis.roi >= 100 ? '✓' : '✗' }} > 100%
            </span>
          </div>
        </div>
      </Card>

      <!-- Churn Rate -->
      <Card title="Churn Rate">
        <div class="flex flex-col">
          <div class="text-3xl font-bold text-gray-900 mb-2">{{ kpis.churn_rate }}%</div>
          <p class="text-sm text-gray-500">Mijozlar yo'qotilish darajasi</p>
          <div class="mt-3 text-xs">
            <span class="font-medium">Benchmark:</span>
            <span :class="kpis.churn_rate < 5 ? 'text-green-600' : 'text-red-600'">
              {{ kpis.churn_rate < 5 ? '✓ Yaxshi' : '✗ Yuqori' }} (< 5%)
            </span>
          </div>
        </div>
      </Card>

      <!-- Module Stats -->
      <Card title="Modul Statistikasi">
        <div class="space-y-3">
          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-600">Dream Buyers:</span>
            <span class="font-semibold text-gray-900">{{ moduleStats.dream_buyers }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-600">Marketing Kanallari:</span>
            <span class="font-semibold text-gray-900">{{ moduleStats.marketing_channels }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-600">Faol Takliflar:</span>
            <span class="font-semibold text-gray-900">{{ moduleStats.active_offers }}</span>
          </div>
        </div>
      </Card>
    </div>

    <!-- Sales Trend Chart -->
    <Card title="Sotuvlar tendensiyasi (oxirgi 7 kun)">
      <div v-if="salesTrend.length > 0" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sana</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sotuvlar</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Daromad</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="item in salesTrend" :key="item.date">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(item.date) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.count }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatCurrency(item.revenue) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="text-center py-12 text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        <p>Hali sotuvlar yo'q</p>
      </div>
    </Card>
  </BusinessLayout>
</template>

<script setup>
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import Card from '@/components/Card.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  stats: Object,
  kpis: Object,
  roasBenchmark: Object,
  ltvCacBenchmark: Object,
  salesTrend: Array,
  moduleStats: Object,
  currentBusiness: Object,
});

const formatNumber = (value) => {
  if (!value) return '0';
  return new Intl.NumberFormat('uz-UZ').format(value);
};

const formatCurrency = (value) => {
  if (!value) return '0 so\'m';
  return new Intl.NumberFormat('uz-UZ').format(value) + ' so\'m';
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('uz-UZ', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};
</script>
