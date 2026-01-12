<template>
  <Head title="Strategiya" />

  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Strategiya</h1>
          <p class="text-gray-500 mt-1">{{ year }}-yil uchun biznes strategiyasi</p>
        </div>

        <div class="flex items-center space-x-3">
          <!-- Year selector -->
          <select
            v-model="selectedYear"
            @change="changeYear"
            class="rounded-lg border-gray-300 text-sm"
          >
            <option v-for="y in availableYears" :key="y" :value="y">{{ y }}-yil</option>
          </select>

          <Link
            v-if="!has_strategy"
            href="/business/strategy/wizard"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
          >
            <PlusIcon class="w-5 h-5 inline mr-1" />
            Strategiya yaratish
          </Link>
        </div>
      </div>

      <!-- No strategy state -->
      <div v-if="!has_strategy" class="bg-white rounded-lg border p-12 text-center">
        <RocketLaunchIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
        <h2 class="text-xl font-semibold text-gray-900 mb-2">Strategiya yaratilmagan</h2>
        <p class="text-gray-500 mb-6 max-w-md mx-auto">
          {{ year }}-yil uchun biznes strategiyangizni yarating. AI yordamida diagnostika natijalariga asoslangan optimal strategiya tavsiya etiladi.
        </p>
        <Link
          href="/business/strategy/wizard"
          class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
        >
          <SparklesIcon class="w-5 h-5 mr-2" />
          Strategiya yaratishni boshlash
        </Link>
      </div>

      <!-- Strategy dashboard -->
      <div v-else class="space-y-6">
        <!-- Quick stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="bg-white rounded-lg border p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-500">Yillik maqsad</span>
              <CurrencyDollarIcon class="w-5 h-5 text-green-500" />
            </div>
            <p class="text-2xl font-bold text-gray-900 mt-1">
              {{ formatMoney(annual_strategy?.revenue_target) }}
            </p>
          </div>

          <div class="bg-white rounded-lg border p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-500">KPI progress</span>
              <ChartBarIcon class="w-5 h-5 text-blue-500" />
            </div>
            <p class="text-2xl font-bold text-gray-900 mt-1">
              {{ kpi_summary?.avg_progress?.toFixed(1) || 0 }}%
            </p>
          </div>

          <div class="bg-white rounded-lg border p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-500">Byudjet sarflangan</span>
              <BanknotesIcon class="w-5 h-5 text-purple-500" />
            </div>
            <p class="text-2xl font-bold text-gray-900 mt-1">
              {{ budget_summary?.spent_percent?.toFixed(1) || 0 }}%
            </p>
          </div>

          <div class="bg-white rounded-lg border p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-500">Ogohlantirish</span>
              <ExclamationTriangleIcon class="w-5 h-5 text-amber-500" />
            </div>
            <p class="text-2xl font-bold" :class="alertsCount > 0 ? 'text-amber-600' : 'text-gray-900'">
              {{ alertsCount }}
            </p>
          </div>
        </div>

        <!-- Strategy hierarchy -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Annual -->
          <StrategyCard
            v-if="annual_strategy"
            :title="annual_strategy.title || `${year}-yil strategiyasi`"
            :subtitle="`${annual_strategy.strategic_goals?.length || 0} ta maqsad`"
            :status="annual_strategy.status"
            :progress="annual_strategy.completion_percent || 0"
            type="annual"
            :stats="[
              { label: 'Daromad', value: formatMoney(annual_strategy.revenue_target), color: 'text-green-600' },
              { label: 'Byudjet', value: formatMoney(annual_strategy.annual_budget) },
              { label: 'Choraklar', value: `${quarterly_plan ? 1 : 0}/4` },
            ]"
            @click="goToAnnual"
          >
            <template #actions>
              <Link
                :href="`/business/strategy/annual/${annual_strategy.id}`"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                Batafsil
              </Link>
            </template>
          </StrategyCard>

          <!-- Quarterly -->
          <StrategyCard
            v-if="quarterly_plan"
            :title="`Q${current_quarter} - ${quarterly_plan.title || quarterly_plan.theme || ''}`"
            :subtitle="`${quarterly_plan.quarterly_objectives?.length || 0} ta maqsad`"
            :status="quarterly_plan.status"
            :progress="quarterly_plan.completion_percent || 0"
            type="quarterly"
            :stats="[
              { label: 'Daromad', value: formatMoney(quarterly_plan.revenue_target) },
              { label: 'Byudjet', value: formatMoney(quarterly_plan.budget) },
              { label: 'Oylar', value: `${monthly_plan ? 1 : 0}/3` },
            ]"
            @click="goToQuarterly"
          >
            <template #actions>
              <Link
                :href="`/business/strategy/quarterly/${quarterly_plan.id}`"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                Batafsil
              </Link>
            </template>
          </StrategyCard>

          <!-- Monthly -->
          <StrategyCard
            v-if="monthly_plan"
            :title="monthly_plan.title || `${getMonthName(current_month)} ${year}`"
            :subtitle="`${monthly_plan.monthly_objectives?.length || 0} ta maqsad`"
            :status="monthly_plan.status"
            :progress="monthly_plan.completion_percent || 0"
            type="monthly"
            :stats="[
              { label: 'Kontent', value: `${monthly_plan.posts_target || 0} post` },
              { label: 'Byudjet', value: formatMoney(monthly_plan.budget) },
              { label: 'Haftalar', value: `${weekly_plan ? 1 : 0}/4` },
            ]"
            @click="goToMonthly"
          >
            <template #actions>
              <Link
                :href="`/business/strategy/monthly/${monthly_plan.id}`"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                Batafsil
              </Link>
            </template>
          </StrategyCard>

          <!-- Weekly -->
          <StrategyCard
            v-if="weekly_plan"
            :title="weekly_plan.title || `Hafta ${weekly_plan.week_of_month}`"
            :subtitle="`${weekly_plan.start_date} - ${weekly_plan.end_date}`"
            :status="weekly_plan.status"
            :progress="weekly_plan.completion_percent || 0"
            type="weekly"
            :stats="[
              { label: 'Vazifalar', value: `${weekly_plan.completed_tasks || 0}/${weekly_plan.total_tasks || 0}` },
              { label: 'Postlar', value: `${weekly_plan.posts_published || 0}/${weekly_plan.posts_planned || 0}` },
              { label: 'Lidlar', value: weekly_plan.lead_target || 0 },
            ]"
            @click="goToWeekly"
          >
            <template #actions>
              <Link
                :href="`/business/strategy/weekly/${weekly_plan.id}`"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                Batafsil
              </Link>
            </template>
          </StrategyCard>
        </div>

        <!-- KPIs section -->
        <div class="bg-white rounded-lg border p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">KPI ko'rsatkichlari</h2>
            <div class="flex items-center space-x-2 text-sm">
              <span class="text-green-600">{{ kpi_summary?.achieved || 0 }} erishildi</span>
              <span class="text-gray-300">|</span>
              <span class="text-blue-600">{{ kpi_summary?.on_track || 0 }} rejada</span>
              <span class="text-gray-300">|</span>
              <span class="text-amber-600">{{ kpi_summary?.at_risk || 0 }} xavfda</span>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="(data, category) in kpi_summary?.by_category"
              :key="category"
              class="p-4 bg-gray-50 rounded-lg"
            >
              <div class="flex items-center justify-between mb-2">
                <span class="font-medium text-gray-700 capitalize">{{ categoryLabel(category) }}</span>
                <span class="text-sm text-gray-500">{{ data.count }} KPI</span>
              </div>
              <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                <div
                  class="h-full bg-indigo-500 rounded-full"
                  :style="{ width: `${data.avg_progress || 0}%` }"
                ></div>
              </div>
              <p class="text-right text-sm text-gray-600 mt-1">{{ data.avg_progress?.toFixed(1) || 0 }}%</p>
            </div>
          </div>
        </div>

        <!-- Budget section -->
        <div class="bg-white rounded-lg border p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Byudjet taqsimoti</h2>
            <span class="text-sm text-gray-500">
              {{ formatMoney(budget_summary?.total_spent) }} / {{ formatMoney(budget_summary?.total_planned) }}
            </span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div
              v-for="(data, category) in budget_summary?.by_category"
              :key="category"
              class="p-4 bg-gray-50 rounded-lg"
            >
              <span class="text-sm text-gray-600 capitalize">{{ categoryLabel(category) }}</span>
              <p class="text-lg font-semibold text-gray-900 mt-1">{{ formatMoney(data.spent) }}</p>
              <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden mt-2">
                <div
                  class="h-full rounded-full"
                  :class="getSpentColor(data.spent, data.planned)"
                  :style="{ width: `${Math.min((data.spent / data.planned) * 100, 100)}%` }"
                ></div>
              </div>
              <p class="text-xs text-gray-500 mt-1">{{ formatMoney(data.remaining) }} qoldi</p>
            </div>
          </div>
        </div>

        <!-- Quick links -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Link
            href="/business/content-calendar"
            class="flex items-center p-4 bg-white rounded-lg border hover:shadow-md transition-shadow"
          >
            <CalendarDaysIcon class="w-10 h-10 text-pink-500 mr-4" />
            <div>
              <h3 class="font-medium text-gray-900">Kontent Kalendar</h3>
              <p class="text-sm text-gray-500">Kontentlarni rejalashtiring</p>
            </div>
          </Link>

          <Link
            href="/business/diagnostic"
            class="flex items-center p-4 bg-white rounded-lg border hover:shadow-md transition-shadow"
          >
            <BeakerIcon class="w-10 h-10 text-blue-500 mr-4" />
            <div>
              <h3 class="font-medium text-gray-900">Diagnostika</h3>
              <p class="text-sm text-gray-500">Biznes tahlili</p>
            </div>
          </Link>

          <Link
            href="/business/analytics"
            class="flex items-center p-4 bg-white rounded-lg border hover:shadow-md transition-shadow"
          >
            <ChartPieIcon class="w-10 h-10 text-green-500 mr-4" />
            <div>
              <h3 class="font-medium text-gray-900">Analitika</h3>
              <p class="text-sm text-gray-500">Natijalarni kuzating</p>
            </div>
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import StrategyCard from '@/Components/strategy/StrategyCard.vue';
import {
  PlusIcon,
  RocketLaunchIcon,
  SparklesIcon,
  CurrencyDollarIcon,
  ChartBarIcon,
  BanknotesIcon,
  ExclamationTriangleIcon,
  CalendarDaysIcon,
  BeakerIcon,
  ChartPieIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  annual_strategy: Object,
  quarterly_plan: Object,
  monthly_plan: Object,
  weekly_plan: Object,
  kpi_summary: Object,
  budget_summary: Object,
  year: Number,
  current_quarter: Number,
  current_month: Number,
  has_strategy: Boolean,
});

const selectedYear = ref(props.year);

const availableYears = computed(() => {
  const current = new Date().getFullYear();
  return [current - 1, current, current + 1];
});

const alertsCount = computed(() => {
  return (props.kpi_summary?.alerts || 0) + (props.budget_summary?.overspent_count || 0);
});

function changeYear() {
  router.get('/business/strategy', { year: selectedYear.value }, { preserveState: true });
}

function goToAnnual() {
  if (props.annual_strategy) {
    router.visit(`/business/strategy/annual/${props.annual_strategy.id}`);
  }
}

function goToQuarterly() {
  if (props.quarterly_plan) {
    router.visit(`/business/strategy/quarterly/${props.quarterly_plan.id}`);
  }
}

function goToMonthly() {
  if (props.monthly_plan) {
    router.visit(`/business/strategy/monthly/${props.monthly_plan.id}`);
  }
}

function goToWeekly() {
  if (props.weekly_plan) {
    router.visit(`/business/strategy/weekly/${props.weekly_plan.id}`);
  }
}

function formatMoney(value) {
  if (!value) return '-';
  if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
  if (value >= 1000) return `${(value / 1000).toFixed(0)}K`;
  return value.toLocaleString();
}

function getMonthName(month) {
  const months = ['', 'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun', 'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr'];
  return months[month] || '';
}

function categoryLabel(category) {
  const labels = {
    revenue: 'Daromad',
    marketing: 'Marketing',
    sales: 'Savdo',
    content: 'Kontent',
    customer: 'Mijozlar',
    operational: 'Operatsion',
    advertising: 'Reklama',
    tools: 'Asboblar',
  };
  return labels[category] || category;
}

function getSpentColor(spent, planned) {
  if (!planned) return 'bg-gray-300';
  const percent = (spent / planned) * 100;
  if (percent >= 100) return 'bg-red-500';
  if (percent >= 80) return 'bg-yellow-500';
  return 'bg-green-500';
}
</script>
