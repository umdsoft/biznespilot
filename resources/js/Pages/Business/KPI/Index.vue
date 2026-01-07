<template>
  <BusinessLayout title="KPI">
    <!-- Header - Only show full header when NOT in empty state -->
    <div v-if="!isEmptyState" class="mb-8">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
            KPI Ko'rsatkichlari
          </h2>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Biznesingizning asosiy ko'rsatkichlari va ularning bajarilishi
          </p>
          <!-- Action Buttons -->
          <div class="mt-3 flex flex-wrap gap-2">
            <button
              @click="showAddPlanModal = true"
              class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-800 transition-colors"
            >
              <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Keyingi oy rejasini qo'shish
            </button>
            <a
              href="/business/kpi/data-entry"
              class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors"
            >
              <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              Kunlik ma'lumotlarni kiritish
            </a>
          </div>
        </div>
        <div class="flex gap-3">
          <button
            @click="selectedPeriod = 'daily'"
            :class="[
              'px-4 py-2 rounded-lg font-medium transition-all',
              selectedPeriod === 'daily'
                ? 'bg-blue-600 text-white shadow-md'
                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
            ]"
          >
            Kunlik
          </button>
          <button
            @click="selectedPeriod = 'weekly'"
            :class="[
              'px-4 py-2 rounded-lg font-medium transition-all',
              selectedPeriod === 'weekly'
                ? 'bg-blue-600 text-white shadow-md'
                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
            ]"
          >
            Haftalik
          </button>
          <button
            @click="selectedPeriod = 'monthly'"
            :class="[
              'px-4 py-2 rounded-lg font-medium transition-all',
              selectedPeriod === 'monthly'
                ? 'bg-blue-600 text-white shadow-md'
                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
            ]"
          >
            Oylik
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State - First Time User -->
    <div v-if="isEmptyState" class="mb-8">
      <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-2xl p-8 border border-blue-200 dark:border-gray-600">
        <div class="text-center max-w-2xl mx-auto">
          <!-- Icon -->
          <div class="mx-auto w-20 h-20 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mb-6">
            <svg class="w-10 h-10 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>

          <!-- Title -->
          <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-3">
            Biznesingiz uchun KPI rejasi yarating
          </h3>

          <!-- Description -->
          <p class="text-gray-600 dark:text-gray-400 mb-6 text-lg">
            KPI ko'rsatkichlarini kuzatish uchun avval biznesingiz uchun reja yarating.
            Faqat 2 ta ma'lumotni kiriting - qolganini tizim avtomatik hisoblab beradi.
          </p>

          <!-- Features List -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 text-left">
            <div class="flex items-start gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl">
              <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <div>
                <p class="font-medium text-gray-900 dark:text-gray-100">Avtomatik hisoblash</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Barcha KPI'lar algoritm asosida</p>
              </div>
            </div>

            <div class="flex items-start gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl">
              <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
              </div>
              <div>
                <p class="font-medium text-gray-900 dark:text-gray-100">Industry benchmark</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Soha standartlariga asoslangan</p>
              </div>
            </div>

            <div class="flex items-start gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl">
              <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
              </div>
              <div>
                <p class="font-medium text-gray-900 dark:text-gray-100">Real vaqt monitoring</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Kunlik, haftalik, oylik hisobotlar</p>
              </div>
            </div>
          </div>

          <!-- CTA Button -->
          <button
            @click="showAddPlanModal = true"
            class="inline-flex items-center px-8 py-4 rounded-xl text-lg font-semibold bg-gradient-to-r from-blue-600 to-indigo-600 text-white hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
          >
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Birinchi KPI rejasini yaratish
          </button>

          <!-- Hint -->
          <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
            Atigi 2 ta ma'lumot kiritasiz: Yangi sotuvlar soni va O'rtacha chek summasi
          </p>
        </div>
      </div>
    </div>

    <!-- KPI Summary Cards -->
    <div v-if="!isEmptyState" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Total KPIs -->
      <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
        </div>
        <p class="text-blue-100 text-sm font-medium mb-1">Jami KPI</p>
        <p class="text-3xl font-bold">{{ kpiMetrics.length }}</p>
      </div>

      <!-- Achieved -->
      <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <p class="text-green-100 text-sm font-medium mb-1">Bajarilgan</p>
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
        <p class="text-yellow-100 text-sm font-medium mb-1">Jarayonda</p>
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
        <p class="text-red-100 text-sm font-medium mb-1">Bajarilmagan</p>
        <p class="text-3xl font-bold">{{ notAchievedCount }}</p>
      </div>
    </div>

    <!-- KPI Table - Weekly View (Days breakdown) -->
    <Card v-if="!isEmptyState && selectedPeriod === 'weekly'" title="Haftalik KPI Ko'rsatkichlari (Kunlar bo'yicha)">
      <div class="space-y-4">
        <!-- Week date range header -->
        <div class="border-b border-gray-200 dark:border-gray-700 pb-4 space-y-4">
          <div class="flex items-center justify-between">
            <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              {{ currentWeekRange }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              Hafta kunlari bo'yicha bajarilish
            </div>
          </div>

          <!-- Week selector buttons -->
          <div class="flex flex-wrap gap-2">
            <button
              v-for="week in weeksInCurrentMonth"
              :key="week.number"
              class="px-4 py-2 rounded-lg text-sm font-medium transition-all border"
              :class="getButtonClass(week.achievement, week.number === currentWeekInMonth, week.isPastOrCurrent)"
            >
              {{ week.label }}
            </button>
          </div>
        </div>

        <!-- Week days table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase sticky left-0 bg-gray-50 dark:bg-gray-700">Ko'rsatkich</th>
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Dush</th>
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sesh</th>
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Chor</th>
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Pay</th>
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Juma</th>
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Shan</th>
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Yak</th>
                <th class="px-4 py-3 text-center text-xs font-medium uppercase border-l-2 border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">Jami</th>
                <th class="px-4 py-3 text-center text-xs font-medium uppercase bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">Reja</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase border-l-2 border-gray-300 dark:border-gray-600">Bajarilishi</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="metric in kpiMetrics" :key="metric.name" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <td class="px-4 py-3 whitespace-nowrap sticky left-0 bg-white dark:bg-gray-800">
                  <div class="flex items-center">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-2" :class="metric.iconBg">
                      <svg class="w-4 h-4" :class="metric.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="metric.iconSvg"></svg>
                    </div>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ metric.name }}</span>
                  </div>
                </td>
                <td v-for="dayIndex in 7" :key="dayIndex" class="px-3 py-3 text-center text-sm text-gray-900 dark:text-gray-100">
                  {{ formatValue(getDailyValue(metric.name, dayIndex - 1), metric.unit) }}
                </td>
                <td class="px-4 py-3 text-center border-l-2 border-blue-500 bg-blue-50/50 dark:bg-blue-900/20">
                  <div class="text-sm font-bold text-blue-700 dark:text-blue-300">
                    {{ formatValue(getWeeklyTotal(metric.name) || metric.current, metric.unit) }}
                  </div>
                </td>
                <td class="px-4 py-3 text-center bg-emerald-50/50 dark:bg-emerald-900/20">
                  <div class="text-sm font-bold text-emerald-700 dark:text-emerald-300">
                    {{ formatValue(metric.plan, metric.unit) }}
                  </div>
                </td>
                <!-- Achievement -->
                <td class="px-4 py-3 whitespace-nowrap border-l-2 border-gray-300 dark:border-gray-600">
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
                <td class="px-4 py-3 whitespace-nowrap text-center">
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
    </Card>

    <!-- KPI Table - Monthly View (Weeks breakdown) -->
    <Card v-else-if="!isEmptyState && selectedPeriod === 'monthly'" title="Oylik KPI Ko'rsatkichlari">
      <div class="space-y-4">
        <!-- Month header -->
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-4">
          <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ currentMonth }}
          </div>
          <div class="text-sm text-gray-600 dark:text-gray-400">
            Haftalik bajarilish ko'rsatkichlari
          </div>
        </div>

        <!-- Weekly breakdown table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase sticky left-0 bg-gray-50 dark:bg-gray-700">Ko'rsatkich</th>
                <th v-for="week in weeksInCurrentMonth" :key="week.number"
                    class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                  {{ week.label }}
                </th>
                <th class="px-4 py-3 text-center text-xs font-medium uppercase border-l-2 border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">Jami</th>
                <th class="px-4 py-3 text-center text-xs font-medium uppercase bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">Reja</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase border-l-2 border-gray-300 dark:border-gray-600">Bajarilishi</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="metric in kpiMetrics" :key="metric.name" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <td class="px-4 py-3 whitespace-nowrap sticky left-0 bg-white dark:bg-gray-800">
                  <div class="flex items-center">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-2" :class="metric.iconBg">
                      <svg class="w-4 h-4" :class="metric.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="metric.iconSvg"></svg>
                    </div>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ metric.name }}</span>
                  </div>
                </td>
                <td v-for="week in weeksInCurrentMonth" :key="week.number"
                    class="px-3 py-3 text-center text-sm text-gray-900 dark:text-gray-100">
                  {{ formatValue(getWeekValue(metric.name, week.number), metric.unit) }}
                </td>
                <td class="px-4 py-3 text-center border-l-2 border-blue-500 bg-blue-50/50 dark:bg-blue-900/20">
                  <div class="text-sm font-bold text-blue-700 dark:text-blue-300">
                    {{ formatValue(getMonthlyTotal(metric.name) || metric.current, metric.unit) }}
                  </div>
                </td>
                <td class="px-4 py-3 text-center bg-emerald-50/50 dark:bg-emerald-900/20">
                  <div class="text-sm font-bold text-emerald-700 dark:text-emerald-300">
                    {{ formatValue(metric.plan, metric.unit) }}
                  </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap border-l-2 border-gray-300 dark:border-gray-600">
                  <div class="flex flex-col items-center">
                    <div class="text-sm font-bold mb-2" :class="getAchievementColor(metric.achievement)">
                      {{ metric.achievement }}%
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2.5">
                      <div class="h-2.5 rounded-full transition-all duration-500"
                           :class="getProgressBarColor(metric.achievement)"
                           :style="{ width: Math.min(metric.achievement, 100) + '%' }">
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-center">
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
    </Card>

    <!-- KPI Table - Previous Month View (Below monthly weeks table) -->
    <Card v-if="!isEmptyState && selectedPeriod === 'monthly' && hasPreviousMonthData" class="mt-8">
      <template #header>
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">O'tgan Oy Ko'rsatkichlari</h3>
          <div class="relative inline-block">
            <select
              v-model="selectedMonth"
              class="inline-flex items-center px-3 py-1 pr-8 rounded-full text-sm font-medium bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 border-0 focus:ring-2 focus:ring-blue-500 cursor-pointer appearance-none"
            >
              <option v-for="month in availableMonths" :key="month.value" :value="month.value">
                {{ month.label }}
              </option>
            </select>
            <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-blue-700 dark:text-blue-300 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </div>
        </div>
      </template>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ko'rsatkich</th>
              <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kategoriya</th>
              <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reja</th>
              <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hozirgi</th>
              <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bajarilishi</th>
              <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="metric in previousMonthMetrics" :key="metric.name" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
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
                  metric.category === 'marketing' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' :
                  metric.category === 'mijozlar' ? 'bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300' :
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
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  Oylik
                </div>
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
                  <span class="w-2 h-2 rounded-full mr-2" :class="[
                    metric.achievement >= 100 ? 'bg-green-500' :
                    metric.achievement >= 75 ? 'bg-blue-500' :
                    metric.achievement >= 50 ? 'bg-yellow-500' :
                    'bg-red-500'
                  ]"></span>
                  {{ getStatusLabel(metric.achievement) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </Card>

    <!-- Daily Tracking Section (shown when period is daily) -->
    <Card v-if="!isEmptyState && selectedPeriod === 'daily'" title="Kunlik Monitoring (Oy bo'yicha)">
      <div class="space-y-4">
        <!-- Month/Year header and day selector -->
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-4">
          <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ currentMonth }}
          </div>
          <div class="text-sm text-gray-600 dark:text-gray-400">
            <span v-if="selectedDay === currentDay">Joriy oy davomida har kungi bajarilish ko'rsatkichlari</span>
            <span v-else class="font-medium text-blue-600 dark:text-blue-400">{{ selectedDay }}-kun ko'rsatkichlari tanlangan</span>
          </div>
        </div>

        <!-- Day buttons -->
        <div class="flex flex-wrap gap-2 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
          <button
            v-for="day in daysInCurrentMonth"
            :key="day"
            @click="selectDay(day)"
            class="px-3 py-2 rounded-lg text-sm font-medium transition-all border"
            :class="getButtonClass(getDayAchievement(day), day === selectedDay, day <= currentDay)"
            :disabled="day > currentDay"
          >
            {{ day }}
          </button>
        </div>

        <!-- KPI Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ko'rsatkich</th>
                <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kategoriya</th>
                <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reja</th>
                <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hozirgi</th>
                <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bajarilishi</th>
                <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
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
                    metric.category === 'marketing' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' :
                    metric.category === 'mijozlar' ? 'bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300' :
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
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ selectedPeriod === 'daily' ? 'Kunlik' : selectedPeriod === 'weekly' ? 'Haftalik' : 'Oylik' }}
                  </div>
                </td>

                <!-- Current -->
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <div class="text-sm font-bold text-gray-900 dark:text-gray-100">
                    {{ formatValue(metric.current, metric.unit) }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ selectedPeriod === 'daily' ? 'Kunlik' : selectedPeriod === 'weekly' ? 'Haftalik' : 'Oylik' }}
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
    </Card>

    <!-- Category-wise Analysis -->
    <div v-if="!isEmptyState && selectedPeriod !== 'daily'" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-6 mt-8">
      <!-- Sales KPIs -->
      <Card title="Sotuv Ko'rsatkichlari">
        <div class="space-y-4">
          <div v-for="metric in salesKPIs" :key="metric.name" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <div class="flex-1">
              <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ metric.name }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">{{ formatValue(metric.current, metric.unit) }} / {{ formatValue(metric.plan, metric.unit) }}</div>
            </div>
            <div class="text-right">
              <div class="text-sm font-bold" :class="getAchievementColor(metric.achievement)">
                {{ metric.achievement }}%
              </div>
            </div>
          </div>
          <div v-if="salesKPIs.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
            Ma'lumotlar topilmadi
          </div>
        </div>
      </Card>

      <!-- Financial KPIs -->
      <Card title="Moliyaviy Ko'rsatkichlar">
        <div class="space-y-4">
          <div v-for="metric in financialKPIs" :key="metric.name" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <div class="flex-1">
              <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ metric.name }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">{{ formatValue(metric.current, metric.unit) }} / {{ formatValue(metric.plan, metric.unit) }}</div>
            </div>
            <div class="text-right">
              <div class="text-sm font-bold" :class="getAchievementColor(metric.achievement)">
                {{ metric.achievement }}%
              </div>
            </div>
          </div>
          <div v-if="financialKPIs.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
            Ma'lumotlar topilmadi
          </div>
        </div>
      </Card>

      <!-- Marketing KPIs -->
      <Card title="Marketing Ko'rsatkichlar">
        <div class="space-y-4">
          <div v-for="metric in marketingKPIs" :key="metric.name" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <div class="flex-1">
              <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ metric.name }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">{{ formatValue(metric.current, metric.unit) }} / {{ formatValue(metric.plan, metric.unit) }}</div>
            </div>
            <div class="text-right">
              <div class="text-sm font-bold" :class="getAchievementColor(metric.achievement)">
                {{ metric.achievement }}%
              </div>
            </div>
          </div>
          <div v-if="marketingKPIs.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
            Ma'lumotlar topilmadi
          </div>
        </div>
      </Card>

      <!-- Customer KPIs -->
      <Card title="Mijozlar Ko'rsatkichlari">
        <div class="space-y-4">
          <div v-for="metric in customerKPIs" :key="metric.name" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <div class="flex-1">
              <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ metric.name }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">{{ formatValue(metric.current, metric.unit) }} / {{ formatValue(metric.plan, metric.unit) }}</div>
            </div>
            <div class="text-right">
              <div class="text-sm font-bold" :class="getAchievementColor(metric.achievement)">
                {{ metric.achievement }}%
              </div>
            </div>
          </div>
          <div v-if="customerKPIs.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
            Ma'lumotlar topilmadi
          </div>
        </div>
      </Card>
    </div>

    <!-- Add Next Month Plan Modal -->
    <div v-if="showAddPlanModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
          <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ nextMonth }} Rejasi</h3>
          <button
            @click="showAddPlanModal = false"
            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 space-y-6">
          <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <p class="text-sm text-blue-800 dark:text-blue-300">
              <strong>Eslatma:</strong> Faqat <strong>Yangi Sotuvlar</strong> va <strong>O'rtacha Chek</strong> kiriting. Qolgan barcha KPI'lar algoritm orqali avtomatik hisoblanadi.
            </p>
          </div>

          <!-- User Input Section -->
          <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-900 border-2 border-blue-300 dark:border-blue-700 rounded-xl p-6">
            <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
              <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              Asosiy Ma'lumotlar
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Yangi Sotuvlar (dona)
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model.number="planInputs.newSales"
                  @input="calculatePlan"
                  type="number"
                  min="0"
                  step="1"
                  class="w-full px-4 py-3 rounded-lg border-2 border-blue-300 dark:border-blue-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-opacity-50 font-semibold text-lg"
                  placeholder="Masalan: 50"
                >
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  O'rtacha Chek (so'm)
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="avgCheckDisplay"
                  @input="handleAvgCheckInput"
                  type="text"
                  class="w-full px-4 py-3 rounded-lg border-2 border-blue-300 dark:border-blue-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-opacity-50 font-semibold text-lg"
                  placeholder="Masalan: 500 000"
                >
              </div>
            </div>
          </div>

          <!-- Calculated KPIs Section -->
          <div v-if="calculatedPlan" class="space-y-4">
            <div class="flex items-center justify-between mb-4">
              <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Avtomatik Hisoblangan Ko'rsatkichlar
              </h4>
              <span class="text-xs px-2 py-1 rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300">
                {{ calculatedPlan.calculation_method === 'historical_data' ? 'Tarixiy ma\'lumotlar asosida' : 'Biznes turi benchmark' }}
              </span>
            </div>

            <!-- Sales KPIs -->
            <div class="border border-teal-200 dark:border-teal-800 rounded-lg p-4 bg-teal-50 dark:bg-teal-900/20">
              <h5 class="font-semibold text-teal-900 dark:text-teal-100 mb-3 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                Sotuv Ko'rsatkichlari
              </h5>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="bg-white dark:bg-gray-800 rounded p-3">
                  <div class="text-xs text-gray-500 dark:text-gray-400">Yangi Sotuvlar</div>
                  <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ calculatedPlan.new_sales }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded p-3">
                  <div class="text-xs text-gray-500 dark:text-gray-400">Qayta Sotuvlar</div>
                  <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ calculatedPlan.repeat_sales }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded p-3">
                  <div class="text-xs text-gray-500 dark:text-gray-400">Jami Mijozlar</div>
                  <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ calculatedPlan.total_customers }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded p-3">
                  <div class="text-xs text-gray-500 dark:text-gray-400">O'rtacha Chek</div>
                  <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(calculatedPlan.avg_check) }}</div>
                </div>
              </div>
            </div>

            <!-- Financial KPIs -->
            <div class="border border-green-200 dark:border-green-800 rounded-lg p-4 bg-green-50 dark:bg-green-900/20">
              <h5 class="font-semibold text-green-900 dark:text-green-100 mb-3 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Moliyaviy Ko'rsatkichlar
              </h5>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="bg-white dark:bg-gray-800 rounded p-3">
                  <div class="text-xs text-gray-500 dark:text-gray-400">Daromad</div>
                  <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ formatCurrency(calculatedPlan.total_revenue) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded p-3">
                  <div class="text-xs text-gray-500 dark:text-gray-400">Reklama Xarajati</div>
                  <div class="text-lg font-bold text-red-600 dark:text-red-400">{{ formatCurrency(calculatedPlan.ad_costs) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded p-3">
                  <div class="text-xs text-gray-500 dark:text-gray-400">ROI</div>
                  <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ calculatedPlan.roi }}%</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded p-3">
                  <div class="text-xs text-gray-500 dark:text-gray-400">ROAS</div>
                  <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ calculatedPlan.roas }}x</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded p-3">
                  <div class="text-xs text-gray-500 dark:text-gray-400">CAC</div>
                  <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(calculatedPlan.cac) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded p-3">
                  <div class="text-xs text-gray-500 dark:text-gray-400">CLV</div>
                  <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(calculatedPlan.clv) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded p-3">
                  <div class="text-xs text-gray-500 dark:text-gray-400">LTV/CAC</div>
                  <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ calculatedPlan.ltv_cac_ratio }}x</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded p-3">
                  <div class="text-xs text-gray-500 dark:text-gray-400">Gross Margin</div>
                  <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ calculatedPlan.gross_margin_percent }}%</div>
                </div>
              </div>
            </div>

            <!-- Marketing KPIs -->
            <div class="border border-blue-200 dark:border-blue-800 rounded-lg p-4 bg-blue-50 dark:bg-blue-900/20">
              <h5 class="font-semibold text-blue-900 dark:text-blue-100 mb-3 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
                Marketing Ko'rsatkichlari
              </h5>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <!-- Lidlar - Editable -->
                <div class="bg-white dark:bg-gray-800 rounded p-3">
                  <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center justify-between">
                    <span>Lidlar</span>
                    <span class="text-blue-500 text-xs">✎ tahrir</span>
                  </div>
                  <input
                    v-model.number="editableLeads"
                    @input="recalculateFromLeads"
                    type="number"
                    min="1"
                    class="w-full text-lg font-bold text-gray-900 dark:text-gray-100 bg-transparent border-b border-dashed border-blue-400 dark:border-blue-600 focus:border-blue-600 dark:focus:border-blue-400 focus:outline-none"
                  >
                </div>
                <!-- Lid Narxi - Editable -->
                <div class="bg-white dark:bg-gray-800 rounded p-3">
                  <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center justify-between">
                    <span>Lid Narxi</span>
                    <span class="text-blue-500 text-xs">✎ tahrir</span>
                  </div>
                  <div class="flex items-center">
                    <input
                      v-model="editableLeadCostDisplay"
                      @input="handleLeadCostInput"
                      type="text"
                      class="w-full text-lg font-bold text-gray-900 dark:text-gray-100 bg-transparent border-b border-dashed border-blue-400 dark:border-blue-600 focus:border-blue-600 dark:focus:border-blue-400 focus:outline-none"
                    >
                    <span class="text-sm text-gray-500 ml-1">so'm</span>
                  </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded p-3">
                  <div class="text-xs text-gray-500 dark:text-gray-400">Konversiya</div>
                  <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ editableConversionRate }}%</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded p-3">
                  <div class="text-xs text-gray-500 dark:text-gray-400">CTR</div>
                  <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ calculatedPlan.ctr }}%</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty state when no calculation -->
          <div v-else class="text-center py-12 text-gray-400">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <p class="text-gray-500 dark:text-gray-400">
              Yangi sotuvlar va o'rtacha chekni kiriting, qolgan KPI'lar avtomatik hisoblanadi
            </p>
          </div>
        </div>

        <div class="sticky bottom-0 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-end gap-3">
          <button
            @click="showAddPlanModal = false"
            class="px-4 py-2 rounded-lg font-medium bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
          >
            Bekor qilish
          </button>
          <button
            @click="savePlans"
            class="px-4 py-2 rounded-lg font-medium bg-green-600 text-white hover:bg-green-700 transition-colors shadow-md"
          >
            Rejani qo'shish
          </button>
        </div>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import Card from '@/Components/Card.vue';

const props = defineProps({
  kpis: {
    type: Object,
    required: true,
  },
  roasBenchmark: {
    type: Object,
    required: true,
  },
  ltvCacBenchmark: {
    type: Object,
    required: true,
  },
  dateRange: {
    type: Object,
    required: true,
  },
  businessRegistrationDate: {
    type: String,
    required: true,
  },
  activePlan: {
    type: Object,
    default: null,
  },
  kpiPlans: {
    type: Array,
    default: () => [],
  },
  targetMonth: {
    type: Object,
    default: null,
  },
  dailyEntries: {
    type: Object,
    default: () => ({}),
  },
});

const selectedPeriod = ref('daily');
const showAddPlanModal = ref(false);

// Selected day for daily view - default to current day
const selectedDay = ref(new Date().getDate());

// Plan inputs (only 2 fields user needs to enter)
const planInputs = ref({
  newSales: null,
  avgCheck: null,
});

// Display value for avg check (formatted with spaces)
const avgCheckDisplay = ref('');

// Calculated plan from backend
const calculatedPlan = ref(null);

// Editable marketing values
const editableLeads = ref(null);
const editableLeadCost = ref(null);
const editableLeadCostDisplay = ref('');
const editableConversionRate = ref(20);

// Check if this is a new business with no KPI data
const isEmptyState = computed(() => {
  // If there's an active plan, it's not empty state
  if (props.activePlan) {
    return false;
  }

  // If there are any saved KPI plans, it's not empty state
  if (props.kpiPlans && props.kpiPlans.length > 0) {
    return false;
  }

  // Check if all KPI values are 0 or empty
  const kpis = props.kpis;
  return !kpis || (
    kpis.cac === 0 &&
    kpis.clv === 0 &&
    kpis.roas === 0 &&
    kpis.roi === 0 &&
    kpis.churn_rate === 0 &&
    kpis.ltv_cac_ratio === 0
  );
});

// Selected month - default to current month
const now = new Date();
const selectedMonth = ref(`${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`);

// Icon SVG paths
const icons = {
  money: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
  trending: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />',
  chart: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />',
  user: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />',
  megaphone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />',
  click: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />',
  users: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />',
  conversion: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />',
  warning: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
  balance: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />',
  shopping: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />',
  refresh: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />',
  tag: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />',
  wallet: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />',
  star: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />',
  userMinus: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6" />'
};

// Available months - from business registration date to current month
const availableMonths = computed(() => {
  const months = [
    'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun',
    'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr'
  ];

  // Use business registration date from props
  const businessStartDate = new Date(props.businessRegistrationDate);
  const currentDate = new Date();

  const result = [];
  let year = businessStartDate.getFullYear();
  let month = businessStartDate.getMonth();

  while (year < currentDate.getFullYear() || (year === currentDate.getFullYear() && month <= currentDate.getMonth())) {
    result.push({
      value: `${year}-${String(month + 1).padStart(2, '0')}`,
      label: `${months[month]} ${year}`
    });

    month++;
    if (month > 11) {
      month = 0;
      year++;
    }
  }

  // Reverse so newest months appear first
  return result.reverse();
});

// Current month display based on activePlan or selected month
const currentMonth = computed(() => {
  const months = [
    'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun',
    'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr'
  ];

  // Use activePlan data if available
  if (props.activePlan) {
    return `${months[props.activePlan.month - 1]} ${props.activePlan.year}`;
  }

  // Fall back to targetMonth from backend
  if (props.targetMonth) {
    return `${props.targetMonth.month_name} ${props.targetMonth.year}`;
  }

  if (selectedMonth.value) {
    const [year, month] = selectedMonth.value.split('-');
    return `${months[parseInt(month) - 1]} ${year}`;
  }

  const now = new Date();
  return `${months[now.getMonth()]} ${now.getFullYear()}`;
});

// Previous month display based on selected month
const previousMonth = computed(() => {
  const months = [
    'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun',
    'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr'
  ];

  if (selectedMonth.value) {
    const [year, month] = selectedMonth.value.split('-');
    let prevMonth = parseInt(month) - 2; // -1 for 0-based index, -1 for previous month
    let prevYear = parseInt(year);

    if (prevMonth < 0) {
      prevMonth = 11;
      prevYear--;
    }

    return `${months[prevMonth]} ${prevYear}`;
  }

  const now = new Date();
  const prevMonth = now.getMonth() - 1;
  const prevYear = prevMonth < 0 ? now.getFullYear() - 1 : now.getFullYear();
  const monthIndex = prevMonth < 0 ? 11 : prevMonth;
  return `${months[monthIndex]} ${prevYear}`;
});

// Next month display for adding new plans
const nextMonth = computed(() => {
  const months = [
    'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun',
    'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr'
  ];

  const now = new Date();
  let nextMonthIndex = now.getMonth() + 1;
  let nextYear = now.getFullYear();

  if (nextMonthIndex > 11) {
    nextMonthIndex = 0;
    nextYear++;
  }

  return `${months[nextMonthIndex]} ${nextYear}`;
});

// Days in selected month
const daysInCurrentMonth = computed(() => {
  if (selectedMonth.value) {
    const [year, month] = selectedMonth.value.split('-');
    const daysCount = new Date(parseInt(year), parseInt(month), 0).getDate();
    return Array.from({ length: daysCount }, (_, i) => i + 1);
  }

  const now = new Date();
  const year = now.getFullYear();
  const month = now.getMonth();
  const daysCount = new Date(year, month + 1, 0).getDate();
  return Array.from({ length: daysCount }, (_, i) => i + 1);
});

// Current day of the month (only for current month)
const currentDay = computed(() => {
  const now = new Date();

  if (selectedMonth.value) {
    const [year, month] = selectedMonth.value.split('-');
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth() + 1;

    // Only return current day if selected month is the current month
    if (parseInt(year) === currentYear && parseInt(month) === currentMonth) {
      return now.getDate();
    }

    // For past months, all days are past (return last day of month)
    return new Date(parseInt(year), parseInt(month), 0).getDate();
  }

  return now.getDate();
});

// Current week date range (for weekly view)
const currentWeekRange = computed(() => {
  const now = new Date();
  const day = now.getDay();
  const diff = now.getDate() - day + (day === 0 ? -6 : 1); // Adjust when day is Sunday
  const monday = new Date(now.setDate(diff));
  const sunday = new Date(monday);
  sunday.setDate(monday.getDate() + 6);

  const formatDate = (date) => {
    const d = String(date.getDate()).padStart(2, '0');
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const y = date.getFullYear();
    return `${d}.${m}.${y}`;
  };

  return `${formatDate(monday)} - ${formatDate(sunday)}`;
});

// Get current week's dates (array of 7 dates for Mon-Sun)
const currentWeekDates = computed(() => {
  const now = new Date();
  const day = now.getDay();
  const diff = now.getDate() - day + (day === 0 ? -6 : 1);
  const monday = new Date(now.getFullYear(), now.getMonth(), diff);

  const dates = [];
  for (let i = 0; i < 7; i++) {
    const date = new Date(monday);
    date.setDate(monday.getDate() + i);
    dates.push(date.toISOString().split('T')[0]); // Format: YYYY-MM-DD
  }
  return dates;
});

// Mapping of metric key to dailyEntries fields
const metricToDailyField = {
  'Yangi Sotuvlar': 'sales_new',
  'Qayta Sotuvlar': 'sales_repeat',
  'Daromad': 'revenue_total',
  'ROI': (entry) => {
    const revenue = parseFloat(entry.revenue_total) || 0;
    const spend = (parseFloat(entry.spend_digital) || 0) + (parseFloat(entry.spend_offline) || 0);
    return spend > 0 ? ((revenue - spend) / spend) * 100 : 0;
  },
  'Gross Margin': (entry) => {
    const revenue = parseFloat(entry.revenue_total) || 0;
    const spend = (parseFloat(entry.spend_digital) || 0) + (parseFloat(entry.spend_offline) || 0);
    // Assuming 60% margin rate for simplicity (can be adjusted based on business settings)
    return revenue > 0 ? 60 : 0;
  },
  'Leadlar': (entry) => (entry.leads_digital || 0) + (entry.leads_offline || 0) + (entry.leads_referral || 0) + (entry.leads_organic || 0),
  'ROAS': (entry) => {
    const revenue = parseFloat(entry.revenue_total) || 0;
    const spend = (parseFloat(entry.spend_digital) || 0) + (parseFloat(entry.spend_offline) || 0);
    return spend > 0 ? revenue / spend : 0;
  },
  'Reklama Xarajati': (entry) => (parseFloat(entry.spend_digital) || 0) + (parseFloat(entry.spend_offline) || 0),
  'Lid Narxi': (entry) => {
    const totalLeads = (entry.leads_digital || 0) + (entry.leads_offline || 0) + (entry.leads_referral || 0) + (entry.leads_organic || 0);
    const totalSpend = (parseFloat(entry.spend_digital) || 0) + (parseFloat(entry.spend_offline) || 0);
    return totalLeads > 0 ? totalSpend / totalLeads : 0;
  },
  'Mijozlar': (entry) => (entry.sales_new || 0) + (entry.sales_repeat || 0),
  'Konversiya': (entry) => {
    const totalLeads = (entry.leads_digital || 0) + (entry.leads_offline || 0) + (entry.leads_referral || 0) + (entry.leads_organic || 0);
    const totalSales = (entry.sales_new || 0) + (entry.sales_repeat || 0);
    return totalLeads > 0 ? (totalSales / totalLeads) * 100 : 0;
  },
  'CAC': (entry) => {
    const totalSales = (entry.sales_new || 0) + (entry.sales_repeat || 0);
    const totalSpend = (parseFloat(entry.spend_digital) || 0) + (parseFloat(entry.spend_offline) || 0);
    return totalSales > 0 ? totalSpend / totalSales : 0;
  },
  'LTV/CAC Ratio': (entry) => {
    const avgCheck = parseFloat(entry.avg_check) || 0;
    const cac = parseFloat(entry.cac) || 0;
    // Simple LTV calculation: avg_check * assumed repeat purchases (3)
    const ltv = avgCheck * 3;
    return cac > 0 ? ltv / cac : 0;
  },
};

// Get daily value for a specific metric and day index (0-6 for Mon-Sun)
const getDailyValue = (metricName, dayIndex) => {
  const dateKey = currentWeekDates.value[dayIndex];
  const entry = props.dailyEntries[dateKey];

  if (!entry) return 0;

  const fieldOrFn = metricToDailyField[metricName];

  if (!fieldOrFn) return 0;

  if (typeof fieldOrFn === 'function') {
    return fieldOrFn(entry);
  }

  return parseFloat(entry[fieldOrFn]) || 0;
};

// Get weekly total for a metric (sum of daily values for current week)
const getWeeklyTotal = (metricName) => {
  let total = 0;
  for (let i = 0; i < 7; i++) {
    total += getDailyValue(metricName, i);
  }
  return total;
};

// Get weekly value for monthly view (aggregated data for a specific week of the month)
const getWeekValue = (metricName, weekNumber) => {
  // Get the first day of current month
  let year, month;
  if (selectedMonth.value) {
    const [y, m] = selectedMonth.value.split('-');
    year = parseInt(y);
    month = parseInt(m) - 1;
  } else {
    const now = new Date();
    year = now.getFullYear();
    month = now.getMonth();
  }

  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);

  // Calculate start day for this week (weekNumber is 1-indexed)
  const weekStartDay = (weekNumber - 1) * 7 + 1;
  const weekEndDay = Math.min(weekStartDay + 6, lastDay.getDate());

  // Sum daily values for this week
  let total = 0;
  for (let day = weekStartDay; day <= weekEndDay; day++) {
    const date = new Date(year, month, day);
    const dateKey = date.toISOString().split('T')[0];
    const entry = props.dailyEntries[dateKey];

    if (entry) {
      const fieldOrFn = metricToDailyField[metricName];
      if (fieldOrFn) {
        if (typeof fieldOrFn === 'function') {
          total += fieldOrFn(entry);
        } else {
          total += parseFloat(entry[fieldOrFn]) || 0;
        }
      }
    }
  }

  return total;
};

// Get monthly total for a metric (sum of all daily entries in current month)
const getMonthlyTotal = (metricName) => {
  let year, month;
  if (selectedMonth.value) {
    const [y, m] = selectedMonth.value.split('-');
    year = parseInt(y);
    month = parseInt(m) - 1;
  } else {
    const now = new Date();
    year = now.getFullYear();
    month = now.getMonth();
  }

  const lastDay = new Date(year, month + 1, 0).getDate();
  let total = 0;

  for (let day = 1; day <= lastDay; day++) {
    const date = new Date(year, month, day);
    const dateKey = date.toISOString().split('T')[0];
    const entry = props.dailyEntries[dateKey];

    if (entry) {
      const fieldOrFn = metricToDailyField[metricName];
      if (fieldOrFn) {
        if (typeof fieldOrFn === 'function') {
          total += fieldOrFn(entry);
        } else {
          total += parseFloat(entry[fieldOrFn]) || 0;
        }
      }
    }
  }

  return total;
};

// Current week number in month based on selected month
const currentWeekInMonth = computed(() => {
  const now = new Date();
  let year, month, currentDayOfMonth;

  if (selectedMonth.value) {
    const [y, m] = selectedMonth.value.split('-');
    year = parseInt(y);
    month = parseInt(m) - 1;

    const currentYear = now.getFullYear();
    const currentMonthNum = now.getMonth();

    // If selected month is current month, use current day
    if (year === currentYear && month === currentMonthNum) {
      currentDayOfMonth = now.getDate();
    } else {
      // For past months, return last week
      const lastDay = new Date(year, month + 1, 0).getDate();
      currentDayOfMonth = lastDay;
    }
  } else {
    year = now.getFullYear();
    month = now.getMonth();
    currentDayOfMonth = now.getDate();
  }

  const firstDay = new Date(year, month, 1);
  let weekNumber = 1;
  let currentDate = new Date(firstDay);

  while (currentDate.getDate() <= currentDayOfMonth) {
    const weekEnd = new Date(currentDate);
    weekEnd.setDate(currentDate.getDate() + 6);

    if (currentDayOfMonth >= currentDate.getDate() && currentDayOfMonth <= weekEnd.getDate()) {
      return weekNumber;
    }

    weekNumber++;
    currentDate.setDate(currentDate.getDate() + 7);
  }

  return weekNumber;
});

// Weeks in selected month (for monthly view)
const weeksInCurrentMonth = computed(() => {
  let year, month;

  if (selectedMonth.value) {
    const [y, m] = selectedMonth.value.split('-');
    year = parseInt(y);
    month = parseInt(m) - 1;
  } else {
    const now = new Date();
    year = now.getFullYear();
    month = now.getMonth();
  }

  // Get first and last day of the month
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);

  // Calculate number of weeks
  const weeks = [];
  let weekNumber = 1;
  let currentDate = new Date(firstDay);

  while (currentDate <= lastDay) {
    const weekStart = new Date(currentDate);
    const weekEnd = new Date(currentDate);
    weekEnd.setDate(weekEnd.getDate() + 6);

    // Don't go beyond the last day of month
    if (weekEnd > lastDay) {
      weekEnd.setTime(lastDay.getTime());
    }

    // Generate random achievement for each week (for demo purposes)
    const achievement = Math.floor(Math.random() * 100);

    weeks.push({
      number: weekNumber,
      label: `${weekNumber}-hafta`,
      achievement: achievement,
      isPastOrCurrent: weekNumber <= currentWeekInMonth.value
    });

    weekNumber++;
    currentDate.setDate(currentDate.getDate() + 7);
  }

  return weeks;
});

// Select a specific day
const selectDay = (day) => {
  // Only allow selecting past or current days
  if (day <= currentDay.value) {
    selectedDay.value = day;
  }
};

// Get date key for selected day (YYYY-MM-DD format)
const getSelectedDayDateKey = () => {
  let year, month;
  if (selectedMonth.value) {
    const [y, m] = selectedMonth.value.split('-');
    year = parseInt(y);
    month = parseInt(m) - 1; // JS months are 0-indexed
  } else {
    const now = new Date();
    year = now.getFullYear();
    month = now.getMonth();
  }
  const day = String(selectedDay.value).padStart(2, '0');
  const monthStr = String(month + 1).padStart(2, '0');
  return `${year}-${monthStr}-${day}`;
};

// Function to get achievement for a specific day based on actual data
const getDayAchievement = (day) => {
  // Get date key for this day
  let year, month;
  if (selectedMonth.value) {
    const [y, m] = selectedMonth.value.split('-');
    year = parseInt(y);
    month = parseInt(m) - 1;
  } else {
    const now = new Date();
    year = now.getFullYear();
    month = now.getMonth();
  }
  const dayStr = String(day).padStart(2, '0');
  const monthStr = String(month + 1).padStart(2, '0');
  const dateKey = `${year}-${monthStr}-${dayStr}`;

  // Check if this day is before the plan's start_date
  // Return -1 for "not tracked" days (before plan was created)
  if (props.activePlan?.start_date) {
    const planStartDate = new Date(props.activePlan.start_date);
    const currentDate = new Date(year, month, day);
    if (currentDate < planStartDate) {
      return -1; // Not tracked - day is before plan start
    }
  }

  // Check if we have data for this day
  const entry = props.dailyEntries[dateKey];
  if (!entry) {
    // No data - return low achievement
    return 0;
  }

  // Calculate average achievement based on available metrics
  let totalAchievement = 0;
  let metricsCount = 0;

  // Check key metrics
  if (props.activePlan?.daily_breakdown) {
    const breakdown = props.activePlan.daily_breakdown;

    // New sales
    if (breakdown.new_sales > 0 && entry.sales_new !== undefined) {
      totalAchievement += (entry.sales_new / breakdown.new_sales) * 100;
      metricsCount++;
    }

    // Revenue
    if (breakdown.total_revenue > 0 && entry.revenue_total !== undefined) {
      totalAchievement += (parseFloat(entry.revenue_total) / breakdown.total_revenue) * 100;
      metricsCount++;
    }

    // Leads
    if (breakdown.total_leads > 0) {
      const totalLeads = (parseInt(entry.leads_digital) || 0) +
                        (parseInt(entry.leads_offline) || 0) +
                        (parseInt(entry.leads_referral) || 0) +
                        (parseInt(entry.leads_organic) || 0);
      totalAchievement += (totalLeads / breakdown.total_leads) * 100;
      metricsCount++;
    }
  }

  if (metricsCount === 0) {
    // If we have entry but no plan, show moderate achievement
    return 50;
  }

  return Math.min(Math.round(totalAchievement / metricsCount), 100);
};

// Function to get button class based on achievement
const getButtonClass = (achievement, isActive = false, isPastOrCurrent = true) => {
  // Tanlangan kun - ko'k rang, shadow bilan ajratib ko'rsatish
  if (isActive) {
    return 'bg-blue-600 text-white shadow-lg border-blue-600 ring-2 ring-blue-300 dark:ring-blue-500';
  }

  // Kelajakdagi kunlar - disabled ko'rinish
  if (!isPastOrCurrent) {
    return 'bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600 border border-gray-200 dark:border-gray-700 cursor-not-allowed opacity-50';
  }

  // Reja start_date dan oldingi kunlar - kulrang "N/A" ko'rinish
  // achievement = -1 degani bu kun kuzatilmagan (reja yaratilmagan edi)
  if (achievement === -1) {
    return 'bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 border-gray-300 dark:border-gray-600 cursor-default';
  }

  // O'tgan kunlar - bajarilish foiziga qarab rang
  if (achievement >= 85) {
    return 'bg-green-500 text-white border-green-500 hover:bg-green-600 cursor-pointer';
  } else if (achievement >= 65) {
    return 'bg-yellow-500 text-white border-yellow-500 hover:bg-yellow-600 cursor-pointer';
  } else {
    return 'bg-red-500 text-white border-red-500 hover:bg-red-600 cursor-pointer';
  }
};

// Helper to get plan value based on period from activePlan
const getPlanValue = (field, period) => {
  if (!props.activePlan) return 0;

  if (period === 'daily' && props.activePlan.daily_breakdown) {
    return props.activePlan.daily_breakdown[field] || 0;
  }
  if (period === 'weekly' && props.activePlan.weekly_breakdown) {
    return props.activePlan.weekly_breakdown[field] || 0;
  }
  // Monthly - use main plan values
  return props.activePlan[field] || 0;
};

// Bugungi sana YYYY-MM-DD formatda
const getTodayDateKey = () => {
  const now = new Date();
  return now.toISOString().split('T')[0];
};

// Field mapping: kpiMetrics field -> dailyEntries field
const fieldToDailyEntryField = {
  'new_sales': 'sales_new',
  'repeat_sales': 'sales_repeat',
  'total_revenue': 'revenue_total',
  'total_leads': (entry) => {
    return (parseInt(entry.leads_digital) || 0) +
           (parseInt(entry.leads_offline) || 0) +
           (parseInt(entry.leads_referral) || 0) +
           (parseInt(entry.leads_organic) || 0);
  },
  'ad_costs': (entry) => {
    return (parseFloat(entry.spend_digital) || 0) + (parseFloat(entry.spend_offline) || 0);
  },
  'total_customers': (entry) => {
    return (parseInt(entry.sales_new) || 0) + (parseInt(entry.sales_repeat) || 0);
  },
  'lead_cost': 'cpl',
  'cac': 'cac',
  'avg_check': 'avg_check',
  'conversion_rate': 'conversion_rate',
  'roi': (entry) => {
    const revenue = parseFloat(entry.revenue_total) || 0;
    const spend = (parseFloat(entry.spend_digital) || 0) + (parseFloat(entry.spend_offline) || 0);
    return spend > 0 ? ((revenue - spend) / spend) * 100 : 0;
  },
  'roas': (entry) => {
    const revenue = parseFloat(entry.revenue_total) || 0;
    const spend = (parseFloat(entry.spend_digital) || 0) + (parseFloat(entry.spend_offline) || 0);
    return spend > 0 ? revenue / spend : 0;
  },
  'gross_margin': (entry) => {
    const revenue = parseFloat(entry.revenue_total) || 0;
    const spend = (parseFloat(entry.spend_digital) || 0) + (parseFloat(entry.spend_offline) || 0);
    return revenue > 0 ? ((revenue - spend) / revenue) * 100 : 0;
  }
};

// dailyEntries dan qiymat olish
const getValueFromEntry = (entry, field) => {
  if (!entry) return 0;

  const mapping = fieldToDailyEntryField[field];
  if (!mapping) return 0;

  if (typeof mapping === 'function') {
    return mapping(entry);
  }

  return parseFloat(entry[mapping]) || 0;
};

// Haqiqiy qiymatni period ga qarab dailyEntries dan hisoblash
const getCurrentValue = (field, period) => {
  if (period === 'daily') {
    // Kunlik: tanlangan kun ma'lumotni olish
    const selectedDateKey = getSelectedDayDateKey();
    const entry = props.dailyEntries[selectedDateKey];
    return getValueFromEntry(entry, field);
  } else if (period === 'weekly') {
    // Haftalik: joriy hafta ma'lumotlarini yig'ish
    let total = 0;
    let count = 0;

    // Foiz/ratio bo'lmagan maydonlar uchun summa, bo'lganlar uchun o'rtacha
    const isRatioField = ['conversion_rate', 'roi', 'roas', 'gross_margin', 'ctr',
                          'churn_rate', 'ltv_cac_ratio', 'cac', 'lead_cost', 'avg_check', 'cpl'].includes(field);

    for (let i = 0; i < 7; i++) {
      const dateKey = currentWeekDates.value[i];
      const entry = props.dailyEntries[dateKey];
      if (entry) {
        const val = getValueFromEntry(entry, field);
        total += val;
        if (val > 0) count++;
      }
    }

    // Ratio maydonlar uchun o'rtacha, boshqalar uchun summa
    if (isRatioField && count > 0) {
      return Math.round(total / count * 100) / 100;
    }
    return Math.round(total);
  } else {
    // Oylik: joriy oy ma'lumotlarini yig'ish
    let year, month;
    if (selectedMonth.value) {
      const [y, m] = selectedMonth.value.split('-');
      year = parseInt(y);
      month = parseInt(m) - 1;
    } else {
      const now = new Date();
      year = now.getFullYear();
      month = now.getMonth();
    }

    const lastDay = new Date(year, month + 1, 0).getDate();
    let total = 0;
    let count = 0;

    const isRatioField = ['conversion_rate', 'roi', 'roas', 'gross_margin', 'ctr',
                          'churn_rate', 'ltv_cac_ratio', 'cac', 'lead_cost', 'avg_check', 'cpl'].includes(field);

    for (let day = 1; day <= lastDay; day++) {
      const date = new Date(year, month, day);
      const dateKey = date.toISOString().split('T')[0];
      const entry = props.dailyEntries[dateKey];
      if (entry) {
        const val = getValueFromEntry(entry, field);
        total += val;
        if (val > 0) count++;
      }
    }

    if (isRatioField && count > 0) {
      return Math.round(total / count * 100) / 100;
    }
    return Math.round(total);
  }
};

// KPI metrics data structure
const kpiMetrics = computed(() => {
  const period = selectedPeriod.value;

  return [
    // Sales KPIs
    {
      name: 'Yangi Sotuvlar',
      description: 'Yangi mijozlarga sotuvlar',
      category: 'sotuv',
      plan: getPlanValue('new_sales', period),
      current: getCurrentValue('new_sales', period),
      unit: 'number',
      iconBg: 'bg-teal-100 dark:bg-teal-900',
      iconColor: 'text-teal-600 dark:text-teal-400',
      iconSvg: icons.shopping
    },
    {
      name: 'Qayta Sotuvlar',
      description: 'Mavjud mijozlarga qayta sotuvlar',
      category: 'sotuv',
      plan: getPlanValue('repeat_sales', period),
      current: getCurrentValue('repeat_sales', period),
      unit: 'number',
      iconBg: 'bg-sky-100 dark:bg-sky-900',
      iconColor: 'text-sky-600 dark:text-sky-400',
      iconSvg: icons.refresh
    },

    // Financial KPIs
    {
      name: 'Daromad',
      description: 'Umumiy sotuvdan tushgan daromad',
      category: 'moliyaviy',
      plan: getPlanValue('total_revenue', period),
      current: getCurrentValue('total_revenue', period),
      unit: 'currency',
      iconBg: 'bg-green-100 dark:bg-green-900',
      iconColor: 'text-green-600 dark:text-green-400',
      iconSvg: icons.money
    },
    {
      name: 'ROI',
      description: 'Investitsiyadan qaytim',
      category: 'moliyaviy',
      plan: getPlanValue('roi', period),
      current: getCurrentValue('roi', period),
      unit: 'percent',
      iconBg: 'bg-blue-100 dark:bg-blue-900',
      iconColor: 'text-blue-600 dark:text-blue-400',
      iconSvg: icons.trending
    },
    // Marketing KPIs
    {
      name: 'Leadlar',
      description: 'Jalb qilingan yangi leadlar',
      category: 'marketing',
      plan: getPlanValue('total_leads', period),
      current: getCurrentValue('total_leads', period),
      unit: 'number',
      iconBg: 'bg-purple-100 dark:bg-purple-900',
      iconColor: 'text-purple-600 dark:text-purple-400',
      iconSvg: icons.user
    },
    {
      name: 'ROAS',
      description: 'Reklama xarajatlaridan daromad',
      category: 'marketing',
      plan: getPlanValue('roas', period),
      current: getCurrentValue('roas', period),
      unit: 'multiplier',
      iconBg: 'bg-indigo-100 dark:bg-indigo-900',
      iconColor: 'text-indigo-600 dark:text-indigo-400',
      iconSvg: icons.megaphone
    },
    // Marketing Cost KPIs
    {
      name: 'Reklama Xarajati',
      description: 'Umumiy reklama xarajatlari',
      category: 'marketing',
      plan: getPlanValue('ad_costs', period),
      current: getCurrentValue('ad_costs', period),
      unit: 'currency',
      isInverse: true, // Pastroq yaxshiroq (budjet ichida qolish)
      iconBg: 'bg-red-100 dark:bg-red-900',
      iconColor: 'text-red-600 dark:text-red-400',
      iconSvg: icons.money
    },
    {
      name: 'Lid Narxi',
      description: 'Bitta lid jalb qilish narxi',
      category: 'marketing',
      plan: getPlanValue('lead_cost', period),
      current: getCurrentValue('lead_cost', period),
      unit: 'currency',
      isInverse: true, // Pastroq yaxshiroq
      iconBg: 'bg-amber-100 dark:bg-amber-900',
      iconColor: 'text-amber-600 dark:text-amber-400',
      iconSvg: icons.tag
    },

    // Customer KPIs
    {
      name: 'Mijozlar',
      description: 'Jami faol mijozlar',
      category: 'mijozlar',
      plan: getPlanValue('total_customers', period),
      current: getCurrentValue('total_customers', period),
      unit: 'number',
      iconBg: 'bg-pink-100 dark:bg-pink-900',
      iconColor: 'text-pink-600 dark:text-pink-400',
      iconSvg: icons.users
    },
    {
      name: 'Konversiya',
      description: 'Leaddan mijozga aylantirish',
      category: 'mijozlar',
      plan: getPlanValue('conversion_rate', period),
      current: getCurrentValue('conversion_rate', period),
      unit: 'percent',
      iconBg: 'bg-orange-100 dark:bg-orange-900',
      iconColor: 'text-orange-600 dark:text-orange-400',
      iconSvg: icons.conversion
    },
    {
      name: 'CAC',
      description: 'Mijoz jalb qilish xarajati',
      category: 'mijozlar',
      plan: getPlanValue('cac', period),
      current: getCurrentValue('cac', period),
      unit: 'currency',
      isInverse: true, // Pastroq yaxshiroq
      iconBg: 'bg-rose-100 dark:bg-rose-900',
      iconColor: 'text-rose-600 dark:text-rose-400',
      iconSvg: icons.wallet
    }
  ].map(metric => ({
    ...metric,
    achievement: calculateAchievement(metric.current, metric.plan, metric.isInverse || false)
  }));
});

// Previous month metrics (with simulated data - in production this would come from backend)
const previousMonthMetrics = computed(() => {
  // For now, we'll use the same structure as kpiMetrics but with adjusted values
  // to simulate previous month's data (80-120% of current values)
  return kpiMetrics.value.map(metric => {
    const randomFactor = 0.8 + Math.random() * 0.4; // Random factor between 0.8 and 1.2
    const prevCurrent = Math.floor(metric.current * randomFactor);
    return {
      ...metric,
      current: prevCurrent,
      achievement: calculateAchievement(prevCurrent, metric.plan, false)
    };
  });
});

// Check if previous month has data
const hasPreviousMonthData = computed(() => {
  // Don't show previous month data if in empty state
  if (isEmptyState.value) return false;

  // Check if we have any meaningful KPI data
  const kpis = props.kpis;
  if (!kpis) return false;

  // Check if any KPI has non-zero value
  return kpis.cac > 0 || kpis.clv > 0 || kpis.roas > 0 || kpis.roi > 0;
});

// Editable metrics for the plan editor
const editableMetrics = ref([]);

// Initialize editable metrics when modal opens
const initializeEditableMetrics = () => {
  editableMetrics.value = kpiMetrics.value.map(metric => ({
    ...metric,
    dailyPlan: metric.plan / 30, // Approximate daily from monthly
    weeklyPlan: metric.plan / 4,  // Approximate weekly from monthly
    monthlyPlan: metric.plan
  }));
};

// Category-wise KPIs
const salesKPIs = computed(() => kpiMetrics.value.filter(m => m.category === 'sotuv'));
const financialKPIs = computed(() => kpiMetrics.value.filter(m => m.category === 'moliyaviy'));
const marketingKPIs = computed(() => kpiMetrics.value.filter(m => m.category === 'marketing'));
const customerKPIs = computed(() => kpiMetrics.value.filter(m => m.category === 'mijozlar'));

// Summary counts
const achievedCount = computed(() => kpiMetrics.value.filter(m => m.achievement >= 100).length);
const inProgressCount = computed(() => kpiMetrics.value.filter(m => m.achievement >= 50 && m.achievement < 100).length);
const notAchievedCount = computed(() => kpiMetrics.value.filter(m => m.achievement < 50).length);

function calculateAchievement(current, plan, isInverse = false) {
  if (plan === 0) return current > 0 ? 100 : 0;

  if (isInverse) {
    // For metrics where lower is better (like CAC, ad_costs, lead_cost)
    // If current < plan, achievement > 100% (good!)
    // If current > plan, achievement < 100% (bad!)
    if (current === 0) return 100; // No spend = perfect
    const achievement = (plan / current) * 100;
    return Math.min(Math.round(achievement), 200); // Cap at 200%
  } else {
    // For metrics where higher is better
    return Math.round((current / plan) * 100);
  }
}

function formatValue(value, unit) {
  if (value === null || value === undefined) return '0';

  // Convert to number if it's a string
  const numValue = typeof value === 'string' ? parseFloat(value) : Number(value);

  // If it's not a valid number, return '0'
  if (isNaN(numValue)) return '0';

  switch (unit) {
    case 'currency':
      return new Intl.NumberFormat('uz-UZ', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
      }).format(numValue) + ' so\'m';

    case 'percent':
      return numValue.toFixed(1) + '%';

    case 'multiplier':
      return numValue.toFixed(1) + 'x';

    case 'number':
    default:
      return new Intl.NumberFormat('uz-UZ').format(numValue);
  }
}

function getAchievementColor(achievement) {
  if (achievement >= 100) return 'text-green-600 dark:text-green-400';
  if (achievement >= 75) return 'text-blue-600 dark:text-blue-400';
  if (achievement >= 50) return 'text-yellow-600 dark:text-yellow-400';
  return 'text-red-600 dark:text-red-400';
}

function getProgressBarColor(achievement) {
  if (achievement >= 100) return 'bg-green-500';
  if (achievement >= 75) return 'bg-blue-500';
  if (achievement >= 50) return 'bg-yellow-500';
  return 'bg-red-500';
}

function getStatusLabel(achievement) {
  if (achievement >= 100) return 'Bajarilgan';
  if (achievement >= 75) return 'Yaxshi';
  if (achievement >= 50) return 'O\'rtacha';
  return 'Past';
}

// Calculate KPI plan based on user inputs
async function calculatePlan() {
  // Only calculate if both values are provided and valid
  const hasNewSales = planInputs.value.newSales && planInputs.value.newSales > 0;
  const hasAvgCheck = planInputs.value.avgCheck && planInputs.value.avgCheck > 0;

  if (!hasNewSales || !hasAvgCheck) {
    calculatedPlan.value = null;
    return;
  }

  try {
    // Call backend API using axios (handles CSRF automatically)
    const response = await axios.post(route('business.kpi.calculate-plan'), {
      new_sales: planInputs.value.newSales,
      avg_check: planInputs.value.avgCheck,
    });

    calculatedPlan.value = response.data.plan;

    // Initialize editable values from calculated plan
    if (response.data.plan) {
      editableLeads.value = response.data.plan.total_leads;
      editableLeadCost.value = response.data.plan.lead_cost;
      editableLeadCostDisplay.value = formatNumberWithSpaces(response.data.plan.lead_cost);
      editableConversionRate.value = response.data.plan.conversion_rate;
    }
  } catch (error) {

    // Handle specific error codes
    if (error.response?.status === 419) {
      alert('Sessiya muddati tugagan. Sahifani yangilang va qayta urinib ko\'ring.');
      window.location.reload();
      return;
    }

    if (error.response?.status === 422) {
      const errors = error.response.data?.errors;
      if (errors) {
        const errorMessages = Object.values(errors).flat().join('\n');
        alert('Validatsiya xatoligi:\n' + errorMessages);
        return;
      }
    }

    const errorMessage = error.response?.data?.error || error.message || 'Noma\'lum xatolik';
    alert('Hisoblashda xatolik yuz berdi: ' + errorMessage);
  }
}

// Format currency
function formatCurrency(value) {
  if (!value && value !== 0) return '0 so\'m';
  return new Intl.NumberFormat('uz-UZ', {
    style: 'decimal',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value) + ' so\'m';
}

// Format number with spaces (500 000)
function formatNumberWithSpaces(value) {
  if (!value && value !== 0) return '';
  return new Intl.NumberFormat('uz-UZ', {
    style: 'decimal',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value);
}

// Parse number from formatted string
function parseFormattedNumber(value) {
  if (!value) return null;
  // Remove all spaces and non-numeric characters except digits
  const cleanValue = value.toString().replace(/\s/g, '').replace(/[^0-9]/g, '');
  return cleanValue ? parseInt(cleanValue, 10) : null;
}

// Handle avg check input change
function handleAvgCheckInput(event) {
  let value = event.target.value;

  // Parse the number (remove all spaces and non-digits)
  const numericValue = parseFormattedNumber(value);

  // Update the actual value
  planInputs.value.avgCheck = numericValue;

  // Format and update display
  if (numericValue) {
    avgCheckDisplay.value = formatNumberWithSpaces(numericValue);
  } else {
    avgCheckDisplay.value = '';
  }

  calculatePlan();
}

// Handle lead cost input with formatting
function handleLeadCostInput(event) {
  let value = event.target.value;

  // Parse the number
  const numericValue = parseFormattedNumber(value);

  // Update the actual value
  editableLeadCost.value = numericValue;

  // Format and update display
  if (numericValue) {
    editableLeadCostDisplay.value = formatNumberWithSpaces(numericValue);
  } else {
    editableLeadCostDisplay.value = '';
  }

  // Recalculate KPIs based on new lead cost
  recalculateKPIs();
}

// Recalculate when leads are changed
function recalculateFromLeads() {
  if (!editableLeads.value || !planInputs.value.newSales) return;

  // Calculate new conversion rate based on leads
  // conversionRate = (newSales / leads) * 100
  const newConversionRate = (planInputs.value.newSales / editableLeads.value) * 100;
  editableConversionRate.value = Math.round(newConversionRate * 10) / 10; // Round to 1 decimal

  // Recalculate KPIs
  recalculateKPIs();
}

// Recalculate all KPIs based on editable values
function recalculateKPIs() {
  if (!calculatedPlan.value || !editableLeads.value || !editableLeadCost.value) return;

  const leads = editableLeads.value;
  const leadCost = editableLeadCost.value;
  const newSales = planInputs.value.newSales;
  const avgCheck = planInputs.value.avgCheck;

  // Recalculate values
  const revenue = newSales * avgCheck;
  const adCosts = leads * leadCost;
  const roi = adCosts > 0 ? ((revenue - adCosts) / adCosts) * 100 : 0;
  const roas = adCosts > 0 ? revenue / adCosts : 0;
  const cac = newSales > 0 ? adCosts / newSales : 0;
  const clv = avgCheck * 3;
  const ltvCacRatio = cac > 0 ? clv / cac : 0;

  // Update calculated plan with new values
  calculatedPlan.value = {
    ...calculatedPlan.value,
    total_leads: leads,
    lead_cost: Math.round(leadCost),
    conversion_rate: editableConversionRate.value,
    ad_costs: Math.round(adCosts),
    roi: Math.round(roi * 10) / 10,
    roas: Math.round(roas * 10) / 10,
    cac: Math.round(cac),
    clv: Math.round(clv),
    ltv_cac_ratio: Math.round(ltvCacRatio * 100) / 100,
  };
}

// Save plans functionality
function savePlans() {
  if (!calculatedPlan.value || !planInputs.value.newSales || !planInputs.value.avgCheck) {
    alert('Iltimos, avval yangi sotuvlar va o\'rtacha chekni kiriting');
    return;
  }

  // Send all required data to backend
  router.post(route('business.kpi.save-plan'), {
    new_sales: planInputs.value.newSales,
    avg_check: planInputs.value.avgCheck,
    leads: editableLeads.value,
    lead_cost: editableLeadCost.value,
  }, {
    onSuccess: () => {
      showAddPlanModal.value = false;
      // Reset inputs
      planInputs.value = {
        newSales: null,
        avgCheck: null,
      };
      avgCheckDisplay.value = '';
      calculatedPlan.value = null;
      editableLeads.value = null;
      editableLeadCost.value = null;
      editableLeadCostDisplay.value = '';
      editableConversionRate.value = 20;
    },
    onError: (errors) => {
      console.error('KPI saqlashda xatolik:', errors);
      alert('KPI saqlashda xatolik yuz berdi. Iltimos, qaytadan urinib ko\'ring.');
    },
  });
}

// Watch for modal open/close
watch(showAddPlanModal, (newValue) => {
  if (newValue) {
    // Reset when opening
    planInputs.value = {
      newSales: null,
      avgCheck: null,
    };
    avgCheckDisplay.value = '';
    calculatedPlan.value = null;
    // Reset editable values
    editableLeads.value = null;
    editableLeadCost.value = null;
    editableLeadCostDisplay.value = '';
    editableConversionRate.value = 20;
  }
});
</script>
