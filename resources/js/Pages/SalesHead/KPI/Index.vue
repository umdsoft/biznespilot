<template>
  <SalesHeadLayout :title="t('nav.kpi')">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
            {{ t('kpi.sales_kpi_indicators') }}
          </h2>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            {{ t('kpi.sales_efficiency_indicators') }}
          </p>
          <!-- Action Buttons -->
          <div class="mt-3 flex flex-wrap gap-2">
            <button
              @click="showAddPlanModal = true"
              class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-200 dark:hover:bg-emerald-800 transition-colors"
            >
              <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              {{ t('kpi.add_sales_plan') }}
            </button>
            <a
              href="/sales-head/kpi/data-entry"
              class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors"
            >
              <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              {{ t('kpi.enter_daily_data') }}
            </a>
          </div>
        </div>
        <div class="flex gap-3">
          <button
            v-for="p in periods"
            :key="p.key"
            @click="handlePeriodChange(p.key)"
            :class="[
              'px-4 py-2 rounded-lg font-medium transition-all',
              period === p.key
                ? 'bg-emerald-600 text-white shadow-md'
                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
            ]"
          >
            {{ p.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Total KPIs -->
      <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
        </div>
        <p class="text-emerald-100 text-sm font-medium mb-1">{{ t('kpi.total_kpi') }}</p>
        <p class="text-3xl font-bold">{{ kpiMetrics.length }}</p>
      </div>

      <!-- Achieved -->
      <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <p class="text-green-100 text-sm font-medium mb-1">{{ t('kpi.achieved') }}</p>
        <p class="text-3xl font-bold">{{ achievedCount }}</p>
      </div>

      <!-- In Progress -->
      <div class="bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <p class="text-yellow-100 text-sm font-medium mb-1">{{ t('kpi.in_progress') }}</p>
        <p class="text-3xl font-bold">{{ inProgressCount }}</p>
      </div>

      <!-- Not Achieved -->
      <div class="bg-gradient-to-br from-red-500 to-pink-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <p class="text-red-100 text-sm font-medium mb-1">{{ t('kpi.not_achieved') }}</p>
        <p class="text-3xl font-bold">{{ notAchievedCount }}</p>
      </div>
    </div>

    <!-- KPI Table - Daily View -->
    <div v-if="period === 'daily'" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden mb-8">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ t('common.daily') }} {{ t('saleshead.monitoring') }}</h3>
          <span class="text-sm text-gray-500 dark:text-gray-400">{{ dateRange.current_month }}</span>
        </div>
      </div>

      <!-- Day buttons -->
      <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
        <div class="flex flex-wrap gap-2">
          <button
            v-for="day in dateRange.days_in_month"
            :key="day"
            @click="handleDayChange(day)"
            class="px-3 py-2 rounded-lg text-sm font-medium transition-all border"
            :class="getDayButtonClass(day)"
            :disabled="day > dateRange.current_day"
          >
            {{ day }}
          </button>
        </div>
      </div>

      <!-- KPI Table -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.indicator') }}</th>
              <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.category') }}</th>
              <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.plan') }}</th>
              <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.current') }}</th>
              <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.achievement') }}</th>
              <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('common.status') }}</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="metric in kpiMetrics" :key="metric.name" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
              <!-- KPI Name -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-10 h-10 rounded-lg flex items-center justify-center mr-3" :class="metric.iconBg">
                    <svg class="w-5 h-5" :class="metric.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="metric.iconSvg"></svg>
                  </div>
                  <div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ metric.name }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ metric.description }}</div>
                  </div>
                </div>
              </td>

              <!-- Category -->
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span :class="[
                  'px-3 py-1 rounded-full text-xs font-medium',
                  metric.category === 'sotuv' ? 'bg-teal-100 dark:bg-teal-900 text-teal-700 dark:text-teal-300' :
                  metric.category === 'moliyaviy' ? 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300' :
                  'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
                ]">
                  {{ metric.category }}
                </span>
              </td>

              <!-- Plan -->
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <div class="text-sm font-bold text-gray-900 dark:text-gray-100">
                  {{ formatValue(metric.plan, metric.unit) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ metric.targetLabel || t('common.daily') }}</div>
              </td>

              <!-- Current -->
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <div class="text-sm font-bold text-gray-900 dark:text-gray-100">
                  {{ formatValue(metric.current, metric.unit) }}
                </div>
              </td>

              <!-- Achievement -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex flex-col items-center">
                  <div class="text-sm font-bold mb-2" :class="getAchievementColor(metric.achievement)">
                    {{ metric.achievement }}%
                  </div>
                  <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2.5">
                    <div
                      class="h-2.5 rounded-full transition-all duration-500"
                      :class="getProgressBarColor(metric.achievement)"
                      :style="{ width: Math.min(metric.achievement, 100) + '%' }"
                    ></div>
                  </div>
                </div>
              </td>

              <!-- Status -->
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span :class="[
                  'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium',
                  metric.achievement >= 100 ? 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300' :
                  metric.achievement >= 75 ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' :
                  metric.achievement >= 50 ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300' :
                  'bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300'
                ]">
                  {{ getStatusLabel(metric.achievement) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Weekly/Monthly View -->
    <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden mb-8">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ period === 'weekly' ? t('common.weekly') : t('common.monthly') }} {{ t('kpi.kpi_indicators') }}
          </h3>
          <span class="text-sm text-gray-500 dark:text-gray-400">{{ dateRange.current_month }}</span>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.indicator') }}</th>
              <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.category') }}</th>
              <th class="px-6 py-4 text-center text-xs font-medium uppercase bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">{{ t('kpi.plan') }}</th>
              <th class="px-6 py-4 text-center text-xs font-medium uppercase border-l-2 border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">{{ t('kpi.current') }}</th>
              <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('kpi.achievement') }}</th>
              <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('common.status') }}</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="metric in kpiMetrics" :key="metric.name" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-10 h-10 rounded-lg flex items-center justify-center mr-3" :class="metric.iconBg">
                    <svg class="w-5 h-5" :class="metric.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="metric.iconSvg"></svg>
                  </div>
                  <div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ metric.name }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ metric.description }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span :class="[
                  'px-3 py-1 rounded-full text-xs font-medium',
                  metric.category === 'sotuv' ? 'bg-teal-100 dark:bg-teal-900 text-teal-700 dark:text-teal-300' :
                  metric.category === 'moliyaviy' ? 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300' :
                  'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
                ]">
                  {{ metric.category }}
                </span>
              </td>
              <td class="px-6 py-4 text-center bg-emerald-50/50 dark:bg-emerald-900/20">
                <div class="text-sm font-bold text-emerald-700 dark:text-emerald-300">
                  {{ formatValue(metric.plan, metric.unit) }}
                </div>
              </td>
              <td class="px-6 py-4 text-center border-l-2 border-blue-500 bg-blue-50/50 dark:bg-blue-900/20">
                <div class="text-sm font-bold text-blue-700 dark:text-blue-300">
                  {{ formatValue(metric.current, metric.unit) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex flex-col items-center">
                  <div class="text-sm font-bold mb-2" :class="getAchievementColor(metric.achievement)">
                    {{ metric.achievement }}%
                  </div>
                  <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2.5">
                    <div
                      class="h-2.5 rounded-full transition-all duration-500"
                      :class="getProgressBarColor(metric.achievement)"
                      :style="{ width: Math.min(metric.achievement, 100) + '%' }"
                    ></div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span :class="[
                  'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium',
                  metric.achievement >= 100 ? 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300' :
                  metric.achievement >= 75 ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' :
                  metric.achievement >= 50 ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300' :
                  'bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300'
                ]">
                  {{ getStatusLabel(metric.achievement) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Team Performance -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden mb-8">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
          <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          {{ t('saleshead.team_performance') }}
        </h3>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('saleshead.employee') }}</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('sales.leads') }}</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('saleshead.deal_won') }}</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('saleshead.conversion') }}%</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('saleshead.revenue') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="member in teamKpi" :key="member.name" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-700 dark:text-emerald-400 font-medium text-sm">
                    {{ member.avatar }}
                  </div>
                  <span class="ml-3 font-medium text-gray-900 dark:text-white">{{ member.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center text-gray-700 dark:text-gray-300">{{ member.leads }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span :class="member.won > 0 ? 'text-emerald-600 dark:text-emerald-400 font-medium' : 'text-gray-500'">{{ member.won }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span :class="[
                  'font-medium',
                  member.conversion >= 30 ? 'text-green-600 dark:text-green-400' :
                  member.conversion >= 15 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'
                ]">{{ member.conversion }}%</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-gray-900 dark:text-white font-medium">
                {{ formatCurrency(member.revenue) }}
              </td>
            </tr>
            <tr v-if="teamKpi.length === 0">
              <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                {{ t('saleshead.no_team_members') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add Plan Modal -->
    <div v-if="showAddPlanModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full">
        <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
          <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ t('kpi.add_sales_plan') }}</h3>
          <button @click="showAddPlanModal = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="savePlan" class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('common.year') }}</label>
              <select v-model="planForm.year" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                <option :value="currentYear - 1">{{ currentYear - 1 }}</option>
                <option :value="currentYear">{{ currentYear }}</option>
                <option :value="currentYear + 1">{{ currentYear + 1 }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('common.month') }}</label>
              <select v-model="planForm.month" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                <option v-for="m in 12" :key="m" :value="m">{{ monthNames[m - 1] }}</option>
              </select>
            </div>
          </div>

          <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4">
            <p class="text-sm text-emerald-800 dark:text-emerald-300">
              <strong>{{ t('kpi.note') }}:</strong> {{ t('kpi.plan_note') }}
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              {{ t('kpi.sales_count') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model.number="planForm.new_sales"
              type="number"
              min="0"
              required
              class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              :placeholder="t('kpi.example') + ': 50'"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              {{ t('kpi.avg_check') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model.number="planForm.avg_check"
              type="number"
              min="0"
              required
              class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              :placeholder="t('kpi.example') + ': 1000000'"
            >
          </div>

          <div v-if="planForm.new_sales && planForm.avg_check" class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
              <strong>{{ t('kpi.calculated_revenue') }}:</strong>
              <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400 ml-2">
                {{ formatCurrency(planForm.new_sales * planForm.avg_check) }}
              </span>
            </p>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button
              type="button"
              @click="showAddPlanModal = false"
              class="px-4 py-2 rounded-lg font-medium bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              type="submit"
              class="px-4 py-2 rounded-lg font-medium bg-emerald-600 text-white hover:bg-emerald-700"
            >
              {{ t('common.save') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </SalesHeadLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
  activePlan: Object,
  kpiPlans: Array,
  dailyEntries: Object,
  kpiMetrics: Array,
  teamKpi: Array,
  period: String,
  dateRange: Object,
  panelType: String,
});

const periods = [
  { key: 'daily', label: t('common.daily') },
  { key: 'weekly', label: t('common.weekly') },
  { key: 'monthly', label: t('common.monthly') },
];

const monthNames = [
  t('common.months.january'), t('common.months.february'), t('common.months.march'),
  t('common.months.april'), t('common.months.may'), t('common.months.june'),
  t('common.months.july'), t('common.months.august'), t('common.months.september'),
  t('common.months.october'), t('common.months.november'), t('common.months.december')
];

const currentYear = new Date().getFullYear();
const currentMonth = new Date().getMonth() + 1;

const showAddPlanModal = ref(false);
const selectedDay = ref(props.dateRange?.selected_day || props.dateRange?.current_day || 1);

const planForm = ref({
  year: currentYear,
  month: currentMonth + 1 > 12 ? 1 : currentMonth + 1, // Next month
  new_sales: '',
  avg_check: '',
});

// Computed
const achievedCount = computed(() => {
  return props.kpiMetrics?.filter(m => m.achievement >= 100).length || 0;
});

const inProgressCount = computed(() => {
  return props.kpiMetrics?.filter(m => m.achievement >= 50 && m.achievement < 100).length || 0;
});

const notAchievedCount = computed(() => {
  return props.kpiMetrics?.filter(m => m.achievement < 50).length || 0;
});

// Methods
const handlePeriodChange = (periodKey) => {
  router.get('/sales-head/kpi', { period: periodKey }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const handleDayChange = (day) => {
  selectedDay.value = day;
  router.get('/sales-head/kpi', { period: 'daily', day: day }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const formatValue = (value, unit) => {
  if (value === null || value === undefined) return '-';

  if (unit === 'som') {
    return formatCurrency(value);
  }
  if (unit === 'foiz') {
    return value + '%';
  }
  return new Intl.NumberFormat('uz-UZ').format(value) + (unit === 'dona' ? '' : ' ' + unit);
};

const formatCurrency = (value) => {
  if (!value) return "0 so'm";
  if (value >= 1000000000) {
    return (value / 1000000000).toFixed(1) + " mlrd so'm";
  }
  if (value >= 1000000) {
    return (value / 1000000).toFixed(1) + " mln so'm";
  }
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

const getAchievementColor = (achievement) => {
  if (achievement >= 100) return 'text-green-600 dark:text-green-400';
  if (achievement >= 75) return 'text-blue-600 dark:text-blue-400';
  if (achievement >= 50) return 'text-yellow-600 dark:text-yellow-400';
  return 'text-red-600 dark:text-red-400';
};

const getProgressBarColor = (achievement) => {
  if (achievement >= 100) return 'bg-green-500';
  if (achievement >= 75) return 'bg-blue-500';
  if (achievement >= 50) return 'bg-yellow-500';
  return 'bg-red-500';
};

const getStatusLabel = (achievement) => {
  if (achievement >= 100) return t('kpi.completed');
  if (achievement >= 75) return t('kpi.good');
  if (achievement >= 50) return t('kpi.average');
  return t('kpi.attention');
};

const getDayButtonClass = (day) => {
  const isToday = day === props.dateRange?.current_day;
  const isSelected = day === selectedDay.value;
  const isPast = day < props.dateRange?.current_day;
  const isFuture = day > props.dateRange?.current_day;

  if (isFuture) {
    return 'bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600 border-gray-200 dark:border-gray-700 cursor-not-allowed';
  }
  if (isSelected) {
    return 'bg-emerald-600 text-white border-emerald-600';
  }
  if (isToday) {
    return 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700';
  }
  return 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700';
};

const savePlan = () => {
  router.post('/sales-head/kpi/plan', planForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      showAddPlanModal.value = false;
      planForm.value = {
        year: currentYear,
        month: currentMonth + 1 > 12 ? 1 : currentMonth + 1,
        new_sales: '',
        avg_check: '',
      };
    },
  });
};
</script>
