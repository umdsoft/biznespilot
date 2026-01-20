<template>
  <Head :title="t('strategy.title')" />

  <BusinessLayout :title="t('strategy.title')">
    <!-- Header Section -->
    <div class="mb-8">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
            {{ t('strategy.business_strategy') }}
          </h2>
          <p class="mt-2 text-gray-600 dark:text-gray-400 flex items-center">
            <svg class="w-4 h-4 mr-2 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <span class="font-medium">{{ year }}-yil uchun strategik rejalar</span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <!-- Year selector -->
          <select
            v-model="selectedYear"
            @change="changeYear"
            class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
          >
            <option v-for="y in availableYears" :key="y" :value="y">{{ y }}-yil</option>
          </select>

          <Link
            v-if="!has_strategy"
            href="/business/strategy/wizard"
            class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-lg text-sm font-medium text-white hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 hover:shadow-xl transform hover:-translate-y-0.5"
          >
            <PlusIcon class="w-5 h-5 mr-2" />
            Strategiya yaratish
          </Link>
        </div>
      </div>
    </div>

    <!-- No Strategy State - Beautiful Empty State -->
    <div v-if="!has_strategy" class="flex flex-col items-center justify-center py-16">
      <div class="relative">
        <!-- Background decorative elements -->
        <div class="absolute -top-4 -left-4 w-72 h-72 bg-indigo-100 dark:bg-indigo-900/30 rounded-full blur-3xl opacity-60"></div>
        <div class="absolute -bottom-4 -right-4 w-72 h-72 bg-purple-100 dark:bg-purple-900/30 rounded-full blur-3xl opacity-60"></div>

        <!-- Main content card -->
        <div class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-700 p-12 max-w-2xl mx-auto text-center">
          <!-- Animated icon -->
          <div class="relative mb-8">
            <div class="w-24 h-24 mx-auto bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center transform rotate-3 shadow-xl">
              <RocketLaunchIcon class="w-12 h-12 text-white" />
            </div>
            <div class="absolute -top-2 -right-2 w-8 h-8 bg-yellow-400 rounded-full flex items-center justify-center shadow-lg animate-bounce">
              <SparklesIcon class="w-5 h-5 text-yellow-900" />
            </div>
          </div>

          <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
            {{ year }}-yil strategiyasini yarating
          </h2>
          <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto leading-relaxed">
            AI yordamida diagnostika natijalariga asoslangan optimal strategiya tavsiya etiladi. Yillik, choraklik, oylik va haftalik rejalarni avtomatik yarating.
          </p>

          <!-- Features list -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
              <div class="w-10 h-10 bg-green-100 dark:bg-green-900/50 rounded-lg flex items-center justify-center mx-auto mb-3">
                <ChartBarIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
              </div>
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300">KPI maqsadlari</p>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
              <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center mx-auto mb-3">
                <BanknotesIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
              </div>
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Byudjet rejasi</p>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
              <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/50 rounded-lg flex items-center justify-center mx-auto mb-3">
                <CalendarDaysIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
              </div>
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Kontent kalendar</p>
            </div>
          </div>

          <Link
            href="/business/strategy/wizard"
            class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-lg text-base font-semibold text-white hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 hover:shadow-2xl transform hover:-translate-y-1"
          >
            <SparklesIcon class="w-5 h-5 mr-2" />
            AI bilan strategiya yaratish
          </Link>
        </div>
      </div>
    </div>

    <!-- Strategy Dashboard -->
    <div v-else class="space-y-8">
      <!-- Quick Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Revenue Target Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
          <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
          <div class="relative p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                <CurrencyDollarIcon class="w-6 h-6 text-white" />
              </div>
              <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-medium text-white">Yillik</span>
            </div>
            <p class="text-green-100 text-sm font-medium mb-1">Daromad maqsadi</p>
            <p class="text-white text-3xl font-bold">{{ formatMoney(annual_strategy?.revenue_target) }}</p>
          </div>
        </div>

        <!-- KPI Progress Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
          <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
          <div class="relative p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                <ChartBarIcon class="w-6 h-6 text-white" />
              </div>
              <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-medium text-white">KPI</span>
            </div>
            <p class="text-blue-100 text-sm font-medium mb-1">Umumiy progress</p>
            <p class="text-white text-3xl font-bold">{{ kpi_summary?.avg_progress?.toFixed(1) || 0 }}%</p>
          </div>
        </div>

        <!-- Budget Spent Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-violet-600 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
          <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
          <div class="relative p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                <BanknotesIcon class="w-6 h-6 text-white" />
              </div>
              <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-medium text-white">Byudjet</span>
            </div>
            <p class="text-purple-100 text-sm font-medium mb-1">Sarflangan</p>
            <p class="text-white text-3xl font-bold">{{ budget_summary?.spent_percent?.toFixed(1) || 0 }}%</p>
          </div>
        </div>

        <!-- Alerts Card -->
        <div class="relative overflow-hidden rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1"
             :class="alertsCount > 0 ? 'bg-gradient-to-br from-amber-500 to-orange-600' : 'bg-gradient-to-br from-gray-500 to-gray-600'">
          <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
          <div class="relative p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                <ExclamationTriangleIcon class="w-6 h-6 text-white" />
              </div>
              <span v-if="alertsCount > 0" class="px-2 py-1 bg-white/20 rounded-lg text-xs font-medium text-white animate-pulse">Diqqat!</span>
            </div>
            <p class="text-white/80 text-sm font-medium mb-1">Ogohlantirishlar</p>
            <p class="text-white text-3xl font-bold">{{ alertsCount }}</p>
          </div>
        </div>
      </div>

      <!-- Strategy Hierarchy Section -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Strategiya ierarxiyasi</h3>
          <div class="flex items-center space-x-2">
            <span class="w-3 h-3 bg-green-500 rounded-full"></span>
            <span class="text-sm text-gray-600 dark:text-gray-400">Faol</span>
            <span class="w-3 h-3 bg-yellow-500 rounded-full ml-4"></span>
            <span class="text-sm text-gray-600 dark:text-gray-400">Qoralama</span>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-6">
          <!-- Annual Strategy -->
          <div v-if="annual_strategy" @click="goToAnnual"
               class="group cursor-pointer bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-2xl p-5 border-2 border-transparent hover:border-indigo-500 transition-all duration-300 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
              <span class="px-3 py-1 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 rounded-lg text-xs font-semibold uppercase tracking-wide">Yillik</span>
              <span class="px-2 py-1 rounded-full text-xs font-medium"
                    :class="annual_strategy.status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300'">
                {{ annual_strategy.status === 'active' ? 'Faol' : 'Qoralama' }}
              </span>
            </div>
            <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
              {{ annual_strategy.title || `${year}-yil strategiyasi` }}
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ annual_strategy.strategic_goals?.length || 0 }} ta strategik maqsad</p>

            <!-- Progress bar -->
            <div class="mb-3">
              <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                <span>Progress</span>
                <span>{{ annual_strategy.completion_percent || 0 }}%</span>
              </div>
              <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-500"
                     :style="{ width: `${annual_strategy.completion_percent || 0}%` }"></div>
              </div>
            </div>

            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600 dark:text-gray-400">Daromad:</span>
              <span class="font-semibold text-gray-900 dark:text-gray-100">{{ formatMoney(annual_strategy.revenue_target) }}</span>
            </div>
          </div>

          <!-- Quarterly Plan -->
          <div v-if="quarterly_plan" @click="goToQuarterly"
               class="group cursor-pointer bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-2xl p-5 border-2 border-transparent hover:border-blue-500 transition-all duration-300 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
              <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-lg text-xs font-semibold uppercase tracking-wide">Q{{ current_quarter }}</span>
              <span class="px-2 py-1 rounded-full text-xs font-medium"
                    :class="quarterly_plan.status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300'">
                {{ quarterly_plan.status === 'active' ? 'Faol' : 'Qoralama' }}
              </span>
            </div>
            <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
              {{ quarterly_plan.title || quarterly_plan.theme || `${current_quarter}-chorak` }}
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ quarterly_plan.quarterly_objectives?.length || 0 }} ta maqsad</p>

            <div class="mb-3">
              <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                <span>Progress</span>
                <span>{{ quarterly_plan.completion_percent || 0 }}%</span>
              </div>
              <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full transition-all duration-500"
                     :style="{ width: `${quarterly_plan.completion_percent || 0}%` }"></div>
              </div>
            </div>

            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600 dark:text-gray-400">Byudjet:</span>
              <span class="font-semibold text-gray-900 dark:text-gray-100">{{ formatMoney(quarterly_plan.budget) }}</span>
            </div>
          </div>
          <div v-else class="bg-gray-50 dark:bg-gray-700/30 rounded-2xl p-5 border-2 border-dashed border-gray-300 dark:border-gray-600 flex flex-col items-center justify-center text-center min-h-[200px]">
            <div class="w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded-xl flex items-center justify-center mb-3">
              <PlusIcon class="w-6 h-6 text-gray-400 dark:text-gray-500" />
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Choraklik reja mavjud emas</p>
          </div>

          <!-- Monthly Plan -->
          <div v-if="monthly_plan" @click="goToMonthly"
               class="group cursor-pointer bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl p-5 border-2 border-transparent hover:border-green-500 transition-all duration-300 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
              <span class="px-3 py-1 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded-lg text-xs font-semibold uppercase tracking-wide">{{ getMonthName(current_month) }}</span>
              <span class="px-2 py-1 rounded-full text-xs font-medium"
                    :class="monthly_plan.status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300'">
                {{ monthly_plan.status === 'active' ? 'Faol' : 'Qoralama' }}
              </span>
            </div>
            <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-2 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">
              {{ monthly_plan.title || `${getMonthName(current_month)} ${year}` }}
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ monthly_plan.monthly_objectives?.length || 0 }} ta maqsad</p>

            <div class="mb-3">
              <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                <span>Progress</span>
                <span>{{ monthly_plan.completion_percent || 0 }}%</span>
              </div>
              <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-green-500 to-emerald-500 rounded-full transition-all duration-500"
                     :style="{ width: `${monthly_plan.completion_percent || 0}%` }"></div>
              </div>
            </div>

            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600 dark:text-gray-400">Kontent:</span>
              <span class="font-semibold text-gray-900 dark:text-gray-100">{{ monthly_plan.posts_target || 0 }} post</span>
            </div>
          </div>
          <div v-else class="bg-gray-50 dark:bg-gray-700/30 rounded-2xl p-5 border-2 border-dashed border-gray-300 dark:border-gray-600 flex flex-col items-center justify-center text-center min-h-[200px]">
            <div class="w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded-xl flex items-center justify-center mb-3">
              <PlusIcon class="w-6 h-6 text-gray-400 dark:text-gray-500" />
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Oylik reja mavjud emas</p>
          </div>

          <!-- Weekly Plan -->
          <div v-if="weekly_plan" @click="goToWeekly"
               class="group cursor-pointer bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-2xl p-5 border-2 border-transparent hover:border-orange-500 transition-all duration-300 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
              <span class="px-3 py-1 bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300 rounded-lg text-xs font-semibold uppercase tracking-wide">Hafta {{ weekly_plan.week_of_month || weekly_plan.week_number }}</span>
              <span class="px-2 py-1 rounded-full text-xs font-medium"
                    :class="weekly_plan.status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300'">
                {{ weekly_plan.status === 'active' ? 'Faol' : 'Qoralama' }}
              </span>
            </div>
            <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-2 group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">
              {{ weekly_plan.title || `Hafta ${weekly_plan.week_of_month || weekly_plan.week_number}` }}
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ formatDateRange(weekly_plan.start_date, weekly_plan.end_date) }}</p>

            <div class="mb-3">
              <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                <span>Progress</span>
                <span>{{ weekly_plan.completion_percent || 0 }}%</span>
              </div>
              <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-orange-500 to-amber-500 rounded-full transition-all duration-500"
                     :style="{ width: `${weekly_plan.completion_percent || 0}%` }"></div>
              </div>
            </div>

            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600 dark:text-gray-400">Vazifalar:</span>
              <span class="font-semibold text-gray-900 dark:text-gray-100">{{ weekly_plan.completed_tasks || 0 }}/{{ weekly_plan.total_tasks || 0 }}</span>
            </div>
          </div>
          <div v-else class="bg-gray-50 dark:bg-gray-700/30 rounded-2xl p-5 border-2 border-dashed border-gray-300 dark:border-gray-600 flex flex-col items-center justify-center text-center min-h-[200px]">
            <div class="w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded-xl flex items-center justify-center mb-3">
              <PlusIcon class="w-6 h-6 text-gray-400 dark:text-gray-500" />
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Haftalik reja mavjud emas</p>
          </div>
        </div>
      </div>

      <!-- KPI & Budget Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- KPIs Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">KPI ko'rsatkichlari</h3>
            <div class="flex items-center space-x-3 text-xs">
              <span class="flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                <span class="text-gray-600 dark:text-gray-400">{{ kpi_summary?.achieved || 0 }} erishildi</span>
              </span>
              <span class="flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-1"></span>
                <span class="text-gray-600 dark:text-gray-400">{{ kpi_summary?.on_track || 0 }} rejada</span>
              </span>
              <span class="flex items-center">
                <span class="w-2 h-2 bg-amber-500 rounded-full mr-1"></span>
                <span class="text-gray-600 dark:text-gray-400">{{ kpi_summary?.at_risk || 0 }} xavfda</span>
              </span>
            </div>
          </div>

          <div class="space-y-4">
            <div
              v-for="(data, category) in kpi_summary?.by_category"
              :key="category"
              class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"
            >
              <div class="flex items-center justify-between mb-2">
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ categoryLabel(category) }}</span>
                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ data.avg_progress?.toFixed(1) || 0 }}%</span>
              </div>
              <div class="h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-500"
                  :class="getProgressColor(data.avg_progress)"
                  :style="{ width: `${Math.min(data.avg_progress || 0, 100)}%` }"
                ></div>
              </div>
            </div>

            <div v-if="!kpi_summary?.by_category || Object.keys(kpi_summary?.by_category).length === 0"
                 class="text-center py-8 text-gray-500 dark:text-gray-400">
              <ChartBarIcon class="w-12 h-12 mx-auto mb-3 opacity-50" />
              <p>KPI ma'lumotlari mavjud emas</p>
            </div>
          </div>
        </div>

        <!-- Budget Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Byudjet taqsimoti</h3>
            <span class="text-sm text-gray-600 dark:text-gray-400">
              {{ formatMoney(budget_summary?.total_spent) }} / {{ formatMoney(budget_summary?.total_planned) }}
            </span>
          </div>

          <div class="space-y-4">
            <div
              v-for="(data, category) in budget_summary?.by_category"
              :key="category"
              class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"
            >
              <div class="flex items-center justify-between mb-2">
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ categoryLabel(category) }}</span>
                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ formatMoney(data.spent) }}</span>
              </div>
              <div class="h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-500"
                  :class="getSpentColor(data.spent, data.planned)"
                  :style="{ width: `${Math.min((data.spent / (data.planned || 1)) * 100, 100)}%` }"
                ></div>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ formatMoney(data.remaining) }} qoldi</p>
            </div>

            <div v-if="!budget_summary?.by_category || Object.keys(budget_summary?.by_category).length === 0"
                 class="text-center py-8 text-gray-500 dark:text-gray-400">
              <BanknotesIcon class="w-12 h-12 mx-auto mb-3 opacity-50" />
              <p>Byudjet ma'lumotlari mavjud emas</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Link
          href="/business/content-calendar"
          class="group flex items-center p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 hover:shadow-xl hover:border-pink-300 dark:hover:border-pink-700 transition-all duration-300"
        >
          <div class="w-14 h-14 bg-gradient-to-br from-pink-500 to-rose-500 rounded-xl flex items-center justify-center mr-4 group-hover:scale-110 transition-transform">
            <CalendarDaysIcon class="w-7 h-7 text-white" />
          </div>
          <div>
            <h3 class="font-bold text-gray-900 dark:text-gray-100 group-hover:text-pink-600 dark:group-hover:text-pink-400 transition-colors">Kontent Kalendar</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kontentlarni rejalashtiring</p>
          </div>
        </Link>

        <Link
          href="/business/diagnostic"
          class="group flex items-center p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 hover:shadow-xl hover:border-blue-300 dark:hover:border-blue-700 transition-all duration-300"
        >
          <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-xl flex items-center justify-center mr-4 group-hover:scale-110 transition-transform">
            <BeakerIcon class="w-7 h-7 text-white" />
          </div>
          <div>
            <h3 class="font-bold text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">AI Diagnostika</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Biznes tahlili</p>
          </div>
        </Link>

        <Link
          href="/business/analytics"
          class="group flex items-center p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 hover:shadow-xl hover:border-green-300 dark:hover:border-green-700 transition-all duration-300"
        >
          <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mr-4 group-hover:scale-110 transition-transform">
            <ChartPieIcon class="w-7 h-7 text-white" />
          </div>
          <div>
            <h3 class="font-bold text-gray-900 dark:text-gray-100 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">Analitika</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Natijalarni kuzating</p>
          </div>
        </Link>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { useI18n } from '@/i18n';
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

const { t } = useI18n();

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
  if (value >= 1000000000) return `${(value / 1000000000).toFixed(1)} mlrd`;
  if (value >= 1000000) return `${(value / 1000000).toFixed(1)} mln`;
  if (value >= 1000) return `${(value / 1000).toFixed(0)}K`;
  return value.toLocaleString();
}

function getMonthName(month) {
  const months = ['', 'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun', 'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr'];
  return months[month] || '';
}

function formatDateRange(start, end) {
  if (!start || !end) return '';
  const startDate = new Date(start);
  const endDate = new Date(end);
  return `${startDate.getDate()}.${String(startDate.getMonth() + 1).padStart(2, '0')} - ${endDate.getDate()}.${String(endDate.getMonth() + 1).padStart(2, '0')}`;
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
    other: 'Boshqa',
  };
  return labels[category] || category;
}

function getProgressColor(progress) {
  if (progress >= 100) return 'bg-gradient-to-r from-green-500 to-emerald-500';
  if (progress >= 80) return 'bg-gradient-to-r from-blue-500 to-indigo-500';
  if (progress >= 50) return 'bg-gradient-to-r from-yellow-500 to-amber-500';
  return 'bg-gradient-to-r from-red-500 to-orange-500';
}

function getSpentColor(spent, planned) {
  if (!planned) return 'bg-gray-300';
  const percent = (spent / planned) * 100;
  if (percent >= 100) return 'bg-gradient-to-r from-red-500 to-rose-500';
  if (percent >= 80) return 'bg-gradient-to-r from-yellow-500 to-amber-500';
  return 'bg-gradient-to-r from-green-500 to-emerald-500';
}
</script>
