<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/50 to-indigo-50">
    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-xl border-b border-gray-200/50 sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/25">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <div>
              <h1 class="text-lg font-bold text-gray-900">BiznesPilot AI</h1>
              <p class="text-xs text-gray-500">Onboarding</p>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <div class="text-right hidden sm:block">
              <p class="text-sm font-medium text-gray-900">{{ businessName }}</p>
              <p class="text-xs text-gray-500">{{ userName }}</p>
            </div>
            <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-full flex items-center justify-center">
              <span class="text-sm font-bold text-blue-600">{{ userInitial }}</span>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Welcome Section with AI CTA -->
      <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 rounded-3xl p-8 sm:p-10 mb-10 relative overflow-hidden">
        <!-- Background decoration -->
        <div class="absolute inset-0 opacity-10">
          <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full -translate-y-1/2 translate-x-1/2"></div>
          <div class="absolute bottom-0 left-0 w-48 h-48 bg-white rounded-full translate-y-1/2 -translate-x-1/2"></div>
        </div>

        <div class="relative flex flex-col lg:flex-row items-center justify-between gap-8">
          <div class="text-center lg:text-left flex-1">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full mb-4">
              <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
              <span class="text-sm font-medium text-white">{{ overallPercent }}% tayyor</span>
            </div>
            <h1 class="text-3xl sm:text-4xl font-bold text-white mb-3">
              Biznesingizni AI bilan tahlil qiling
            </h1>
            <p class="text-lg text-indigo-100 max-w-xl">
              Ma'lumotlaringiz asosida AI sizning biznesingizni 360° tahlil qiladi va o'sish strategiyasini tavsiya qiladi
            </p>
          </div>

          <div class="flex flex-col items-center gap-4">
            <button
              @click="startAIDiagnostic"
              class="group relative px-8 py-4 bg-white text-indigo-600 font-bold text-lg rounded-2xl shadow-2xl shadow-black/20 hover:shadow-black/30 hover:scale-105 transition-all duration-300 flex items-center gap-3"
            >
              <svg class="w-6 h-6 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              <span>AI Diagnostikani Boshlash</span>
              <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </button>
            <p class="text-indigo-200 text-sm">Bepul • 2-3 daqiqa • Aniq tavsiyalar</p>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex items-center justify-center py-20">
        <div class="text-center">
          <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-4"></div>
          <p class="text-gray-600">Ma'lumotlar yuklanmoqda...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="hasError" class="bg-red-50 border border-red-200 rounded-2xl p-8 text-center">
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-red-800 mb-2">Xatolik yuz berdi</h3>
        <p class="text-red-600 mb-4">{{ errorMessage }}</p>
        <button @click="loadData" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
          Qayta urinib ko'rish
        </button>
      </div>

      <!-- Main Content Grid -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Left Sidebar - Progress -->
        <div class="lg:col-span-4 space-y-6">
          <!-- Overall Progress Card -->
          <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-semibold text-gray-900">Umumiy Progress</h3>
              <span class="text-2xl font-bold text-blue-600">{{ overallPercent }}%</span>
            </div>

            <!-- Circular Progress -->
            <div class="flex justify-center mb-6">
              <div class="relative w-40 h-40">
                <svg class="w-full h-full transform -rotate-90">
                  <circle cx="80" cy="80" r="70" stroke-width="10" fill="none" class="stroke-gray-100" />
                  <circle cx="80" cy="80" r="70" stroke-width="10" fill="none" class="stroke-blue-500 transition-all duration-1000" :stroke-dasharray="`${overallPercent * 4.4} 440`" stroke-linecap="round" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                  <span class="text-4xl font-bold text-gray-900">{{ overallPercent }}</span>
                  <span class="text-sm text-gray-500">foiz</span>
                </div>
              </div>
            </div>

            <!-- Category Progress -->
            <div class="space-y-4">
              <div v-for="cat in categoryProgress" :key="cat.key">
                <div class="flex items-center justify-between mb-1">
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-700">{{ cat.name }}</span>
                    <span v-if="cat.isOptional" class="text-xs text-gray-400">(ixtiyoriy)</span>
                  </div>
                  <span class="text-sm font-bold" :class="cat.percent >= 100 ? 'text-green-600' : 'text-gray-600'">{{ cat.percent }}%</span>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full rounded-full transition-all duration-500" :class="cat.percent >= 100 ? 'bg-green-500' : cat.isOptional ? 'bg-emerald-400' : 'bg-blue-500'" :style="{ width: cat.percent + '%' }"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tips Card -->
          <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-6 text-white">
            <div class="flex items-start gap-3 mb-4">
              <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div>
                <h4 class="font-semibold mb-1">Maslahat</h4>
                <p class="text-sm text-blue-100">
                  Barcha qadamlar ixtiyoriy! Xohlagan vaqtda Dashboard ga o'tishingiz mumkin.
                </p>
              </div>
            </div>
          </div>

          <!-- Dashboard Button -->
          <button @click="goToDashboard" class="w-full py-3 bg-white border-2 border-indigo-200 text-indigo-600 font-semibold rounded-xl hover:bg-indigo-50 hover:border-indigo-300 transition-colors flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Dashboard ga o'tish
          </button>
        </div>

        <!-- Right Content - Steps -->
        <div class="lg:col-span-8 space-y-6">
          <!-- Category: Profile -->
          <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-500 text-white rounded-xl flex items-center justify-center shadow-sm">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                </div>
                <div class="flex-1">
                  <h3 class="text-lg font-semibold text-gray-900">Biznes Profili</h3>
                  <p class="text-sm text-gray-500">Asosiy ma'lumotlarni kiriting</p>
                </div>
                <div class="px-3 py-1 rounded-full text-sm font-bold" :class="getCategoryPercent('profile') >= 100 ? 'bg-green-100 text-green-700' : getCategoryPercent('profile') > 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600'">
                  {{ getCategoryPercent('profile') }}%
                </div>
              </div>
            </div>
            <div class="p-4 space-y-3">
              <div
                v-for="step in profileSteps"
                :key="step.code"
                @click="openStep(step)"
                class="flex items-center gap-4 p-4 rounded-xl border cursor-pointer transition-all group"
                :class="step.is_completed ? 'border-green-200 bg-green-50/50 hover:border-green-300' : 'border-gray-200 hover:border-blue-300 hover:bg-blue-50/50'"
              >
                <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-transform group-hover:scale-105" :class="step.is_completed ? 'bg-green-500 text-white' : 'bg-gradient-to-br from-blue-100 to-indigo-100 text-blue-600'">
                  <svg v-if="step.is_completed" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  <template v-else>
                    <!-- Building icon for business_basic -->
                    <svg v-if="step.code === 'business_basic'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <!-- Clipboard for business_details -->
                    <svg v-else-if="step.code === 'business_details'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <!-- Chart bar for business_maturity -->
                    <svg v-else-if="step.code === 'business_maturity'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <!-- Default question mark -->
                    <span v-else class="text-lg font-bold">?</span>
                  </template>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <h4 class="font-semibold text-gray-900 truncate">{{ step.step?.name?.uz || step.step?.name_uz || step.name || 'Qadam' }}</h4>
                    <span v-if="!step.is_required" class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600">Ixtiyoriy</span>
                  </div>
                  <p class="text-sm text-gray-500 truncate">{{ step.step?.description?.uz || step.step?.description_uz || step.description || '' }}</p>
                </div>
                <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </div>
          </div>

          <!-- Category: KPI va Metrikalar -->
          <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-teal-50">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-500 text-white rounded-xl flex items-center justify-center shadow-sm">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                  </svg>
                </div>
                <div class="flex-1">
                  <h3 class="text-lg font-semibold text-gray-900">KPI va Metrikalar</h3>
                  <p class="text-sm text-gray-500">Sotuv va marketing ko'rsatkichlari</p>
                </div>
                <div class="px-3 py-1 rounded-full text-sm font-bold" :class="getCategoryPercent('kpi') >= 100 ? 'bg-green-100 text-green-700' : getCategoryPercent('kpi') > 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600'">
                  {{ getCategoryPercent('kpi') }}%
                </div>
              </div>
            </div>
            <div class="p-4 space-y-3">
              <!-- Agar metricsSteps mavjud bo'lsa -->
              <template v-if="metricsSteps.length > 0">
                <div
                  v-for="step in metricsSteps"
                  :key="step.code"
                  @click="openStep(step)"
                  class="flex items-center gap-4 p-4 rounded-xl border cursor-pointer transition-all group"
                  :class="step.is_completed ? 'border-green-200 bg-green-50/50 hover:border-green-300' : 'border-gray-200 hover:border-emerald-300 hover:bg-emerald-50/50'"
                >
                  <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-transform group-hover:scale-105" :class="step.is_completed ? 'bg-green-500 text-white' : 'bg-gradient-to-br from-emerald-100 to-teal-100 text-emerald-600'">
                    <svg v-if="step.is_completed" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <template v-else>
                      <!-- Sotuv KPI - Currency Dollar -->
                      <svg v-if="step.code === 'kpi_sales'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <!-- Marketing KPI - Chart Pie -->
                      <svg v-else-if="step.code === 'kpi_marketing'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                      </svg>
                      <!-- Default -->
                      <span v-else class="text-lg font-bold">?</span>
                    </template>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                      <h4 class="font-semibold text-gray-900 truncate">{{ step.step?.name?.uz || step.step?.name_uz || step.name || 'Qadam' }}</h4>
                      <span v-if="!step.is_completed" class="px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">Ixtiyoriy</span>
                    </div>
                    <p class="text-sm text-gray-500 truncate">{{ step.step?.description?.uz || step.step?.description_uz || step.description || '' }}</p>
                  </div>
                  <svg class="w-5 h-5 text-gray-400 group-hover:text-emerald-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </div>
              </template>

              <!-- Agar metricsSteps bo'sh bo'lsa -->
              <template v-else>
                <!-- Sotuv ko'rsatkichlari -->
                <div
                  @click="openMetricsStep('kpi_sales')"
                  class="flex items-center gap-4 p-4 rounded-xl border border-gray-200 cursor-pointer transition-all group hover:border-emerald-300 hover:bg-emerald-50/50"
                >
                  <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-transform group-hover:scale-105 bg-gradient-to-br from-emerald-100 to-teal-100 text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <div class="flex-1">
                    <div class="flex items-center gap-2">
                      <h4 class="font-semibold text-gray-900">Sotuv ko'rsatkichlari</h4>
                      <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">Ixtiyoriy</span>
                    </div>
                    <p class="text-sm text-gray-500">Lidlar, konversiya, sotuv hajmi</p>
                  </div>
                  <div class="text-sm text-gray-400">~5 daq</div>
                  <svg class="w-5 h-5 text-gray-400 group-hover:text-emerald-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </div>

                <!-- Marketing ko'rsatkichlari -->
                <div
                  @click="openMetricsStep('kpi_marketing')"
                  class="flex items-center gap-4 p-4 rounded-xl border border-gray-200 cursor-pointer transition-all group hover:border-teal-300 hover:bg-teal-50/50"
                >
                  <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-transform group-hover:scale-105 bg-gradient-to-br from-teal-100 to-cyan-100 text-teal-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                    </svg>
                  </div>
                  <div class="flex-1">
                    <div class="flex items-center gap-2">
                      <h4 class="font-semibold text-gray-900">Marketing ko'rsatkichlari</h4>
                      <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-teal-100 text-teal-700">Ixtiyoriy</span>
                    </div>
                    <p class="text-sm text-gray-500">Kanallar, byudjet, ROI</p>
                  </div>
                  <div class="text-sm text-gray-400">~5 daq</div>
                  <svg class="w-5 h-5 text-gray-400 group-hover:text-teal-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </div>
              </template>
            </div>
          </div>

          <!-- Category: Framework -->
          <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-violet-50">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-500 text-white rounded-xl flex items-center justify-center shadow-sm">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                  </svg>
                </div>
                <div class="flex-1">
                  <h3 class="text-lg font-semibold text-gray-900">Marketing Framework</h3>
                  <p class="text-sm text-gray-500">Strategiya asoslarini yarating</p>
                </div>
                <div class="px-3 py-1 rounded-full text-sm font-bold" :class="getCategoryPercent('framework') >= 100 ? 'bg-green-100 text-green-700' : getCategoryPercent('framework') > 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600'">
                  {{ getCategoryPercent('framework') }}%
                </div>
              </div>
            </div>
            <div class="p-4 space-y-3">
              <div
                v-for="step in frameworkSteps"
                :key="step.code"
                @click="openStep(step)"
                class="flex items-center gap-4 p-4 rounded-xl border cursor-pointer transition-all group"
                :class="step.is_completed ? 'border-green-200 bg-green-50/50 hover:border-green-300' : 'border-gray-200 hover:border-purple-300 hover:bg-purple-50/50'"
              >
                <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-transform group-hover:scale-105" :class="step.is_completed ? 'bg-green-500 text-white' : 'bg-gradient-to-br from-purple-100 to-violet-100 text-purple-600'">
                  <svg v-if="step.is_completed" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  <template v-else>
                    <!-- Muammo aniqlash - Exclamation -->
                    <svg v-if="step.code === 'framework_problem'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <!-- Dream Buyer - User Circle -->
                    <svg v-else-if="step.code === 'framework_dream_buyer'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <!-- Tadqiqot - Search -->
                    <svg v-else-if="step.code === 'framework_research'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <!-- Raqobatchilar - Users -->
                    <svg v-else-if="step.code === 'framework_competitors'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <!-- Gipotezalar - Lightbulb -->
                    <svg v-else-if="step.code === 'framework_hypotheses'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    <!-- Default -->
                    <span v-else class="text-lg font-bold">?</span>
                  </template>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <h4 class="font-semibold text-gray-900 truncate">{{ step.step?.name?.uz || step.step?.name_uz || step.name || 'Qadam' }}</h4>
                    <span v-if="!step.is_completed" class="px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 text-purple-700">Ixtiyoriy</span>
                  </div>
                  <p class="text-sm text-gray-500 truncate">{{ step.step?.description?.uz || step.step?.description_uz || step.description || '' }}</p>
                </div>
                <svg class="w-5 h-5 text-gray-400 group-hover:text-purple-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </div>
          </div>

        </div>
      </div>
    </main>

    <!-- Step Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden">
              <!-- Modal Header -->
              <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 z-10">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 text-white rounded-xl flex items-center justify-center" :class="modalIconBg">
                      <!-- Business Basic - Building -->
                      <svg v-if="activeStepCode === 'business_basic'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                      </svg>
                      <!-- Business Details - Clipboard -->
                      <svg v-else-if="activeStepCode === 'business_details'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                      </svg>
                      <!-- Business Maturity - Chart Bar -->
                      <svg v-else-if="activeStepCode === 'business_maturity'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                      </svg>
                      <!-- Dream Buyer - User Circle -->
                      <svg v-else-if="activeStepCode === 'framework_dream_buyer'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <!-- Problem - Exclamation -->
                      <svg v-else-if="activeStepCode === 'framework_problem'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                      </svg>
                      <!-- Competitors - Users -->
                      <svg v-else-if="activeStepCode === 'framework_competitors'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                      </svg>
                      <!-- Hypotheses - Lightbulb -->
                      <svg v-else-if="activeStepCode === 'framework_hypotheses'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                      </svg>
                      <!-- Sales KPI - Currency -->
                      <svg v-else-if="activeStepCode === 'kpi_sales'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <!-- Marketing KPI - Chart Pie -->
                      <svg v-else-if="activeStepCode === 'kpi_marketing'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                      </svg>
                      <!-- Default - Edit -->
                      <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </div>
                    <div>
                      <h3 class="text-lg font-semibold text-gray-900">{{ modalTitle }}</h3>
                      <p class="text-sm text-gray-500">{{ modalSubtitle }}</p>
                    </div>
                  </div>
                  <button @click="closeModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Modal Content -->
              <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
                <!-- Loading -->
                <div v-if="modalLoading" class="flex items-center justify-center py-12">
                  <div class="w-10 h-10 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
                </div>

                <!-- Error Display -->
                <div v-else-if="modalError" class="bg-red-50 border border-red-200 rounded-xl p-6">
                  <div class="flex items-start gap-3">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                      <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                      </svg>
                    </div>
                    <div class="flex-1">
                      <h4 class="text-lg font-semibold text-red-800 mb-2">Xatolik yuz berdi</h4>
                      <p class="text-red-700 mb-2">{{ modalError.message }}</p>
                      <p class="text-sm text-red-600 mb-2">Info: {{ modalError.info }}</p>
                      <details class="mt-3">
                        <summary class="text-sm text-red-600 cursor-pointer">Stack trace</summary>
                        <pre class="mt-2 text-xs bg-red-100 p-3 rounded overflow-x-auto">{{ modalError.stack }}</pre>
                      </details>
                    </div>
                  </div>
                  <button @click="closeModal" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Yopish</button>
                </div>

                <!-- Dynamic Form -->
                <template v-else>
                  <component
                    :is="currentFormComponent"
                    v-if="currentFormComponent"
                    v-bind="currentFormProps"
                    @submit="handleFormSubmit"
                    @cancel="closeModal"
                    @skip="closeModal"
                  />

                  <!-- Placeholder -->
                  <div v-else class="text-center py-12">
                    <div class="w-20 h-20 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                      <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                      </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Komponent topilmadi</h4>
                    <p class="text-gray-500 mb-2">Step code: {{ activeStepCode }}</p>
                    <p class="text-gray-500 mb-6">Bu qadamning komponenti topilmadi</p>
                    <button @click="closeModal" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition-colors">
                      Yopish
                    </button>
                  </div>
                </template>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Toast Notifications -->
    <Toast />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, shallowRef, onErrorCaptured, markRaw } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useOnboardingStore } from '@/stores/onboarding';
import Toast from '@/components/Toast.vue';

// Form components
import BusinessBasicForm from '@/components/onboarding/forms/BusinessBasicForm.vue';
import BusinessDetailsForm from '@/components/onboarding/forms/BusinessDetailsForm.vue';
import BusinessMaturityForm from '@/components/onboarding/forms/BusinessMaturityForm.vue';
import DreamBuyerForm from '@/components/onboarding/forms/DreamBuyerForm.vue';
import ProblemForm from '@/components/onboarding/forms/ProblemForm.vue';
import CompetitorsForm from '@/components/onboarding/forms/CompetitorsForm.vue';
import HypothesesForm from '@/components/onboarding/forms/HypothesesForm.vue';
import ResearchForm from '@/components/onboarding/forms/ResearchForm.vue';
import SalesMetricsForm from '@/components/onboarding/forms/SalesMetricsForm.vue';
import MarketingMetricsForm from '@/components/onboarding/forms/MarketingMetricsForm.vue';

// Page props
const page = usePage();
const store = useOnboardingStore();

// Debug: Modal error state
const modalError = ref(null);

// Error capture for child components
onErrorCaptured((err, instance, info) => {
  console.error('🔴 MODAL ERROR CAPTURED:', err);
  console.error('Component:', instance);
  console.error('Info:', info);
  modalError.value = {
    message: err.message,
    stack: err.stack,
    info: info
  };
  return false; // Prevent error from propagating
});

// State
const isLoading = ref(true);
const hasError = ref(false);
const errorMessage = ref('');
const showModal = ref(false);
const modalLoading = ref(false);
const activeStepCode = ref(null);
const stepData = ref(null);

// Computed from page props
const businessName = computed(() => page.props.currentBusiness?.name || 'Biznes');
const userName = computed(() => page.props.auth?.user?.name || 'Foydalanuvchi');
const userInitial = computed(() => userName.value.charAt(0).toUpperCase());

// Progress computed
const progress = computed(() => store.progress);
const overallPercent = computed(() => progress.value?.overall_percent || 0);

const categoryProgress = computed(() => [
  { key: 'profile', name: 'Biznes Profili', percent: progress.value?.categories?.profile?.required_percent || 0 },
  { key: 'kpi', name: 'KPI va Metrikalar', percent: progress.value?.categories?.kpi?.percent || 0, isOptional: true },
  { key: 'framework', name: 'Framework', percent: progress.value?.categories?.framework?.required_percent || 0 },
]);

const profileSteps = computed(() => (progress.value?.steps || []).filter(s => s.category === 'profile'));
const integrationSteps = computed(() => (progress.value?.steps || []).filter(s => s.category === 'integration'));
const metricsSteps = computed(() => (progress.value?.steps || []).filter(s => s.category === 'kpi'));
const frameworkSteps = computed(() => (progress.value?.steps || []).filter(s => s.category === 'framework'));

// Form component mapping - markRaw to prevent reactivity issues
const formComponents = {
  business_basic: markRaw(BusinessBasicForm),
  business_details: markRaw(BusinessDetailsForm),
  business_maturity: markRaw(BusinessMaturityForm),
  framework_dream_buyer: markRaw(DreamBuyerForm),
  framework_problem: markRaw(ProblemForm),
  framework_competitors: markRaw(CompetitorsForm),
  framework_hypotheses: markRaw(HypothesesForm),
  framework_research: markRaw(ResearchForm),
  kpi_sales: markRaw(SalesMetricsForm),
  kpi_marketing: markRaw(MarketingMetricsForm),
};

// Modal computed
const modalTitle = computed(() => {
  if (!activeStepCode.value) return '';
  const titles = {
    business_basic: 'Biznes ma\'lumotlari',
    business_details: 'Biznes tafsilotlari',
    business_maturity: 'Biznes holati',
    framework_dream_buyer: 'Ideal mijoz profili',
    framework_problem: 'Muammolar',
    framework_competitors: 'Raqobatchilar',
    framework_hypotheses: 'Gipotezalar',
    framework_research: 'Tadqiqot',
    kpi_sales: 'Sotuv ko\'rsatkichlari',
    kpi_marketing: 'Marketing ko\'rsatkichlari',
  };
  return stepData.value?.step?.name?.uz || stepData.value?.step?.name_uz || titles[activeStepCode.value] || 'Ma\'lumotlar';
});

const modalSubtitle = computed(() => {
  return stepData.value?.step?.description?.uz || stepData.value?.step?.description_uz || 'Ma\'lumotlarni kiriting';
});

// Modal icon background based on step category
const modalIconBg = computed(() => {
  if (!activeStepCode.value) return 'bg-gradient-to-br from-blue-500 to-indigo-600';

  if (activeStepCode.value.startsWith('business_')) {
    return 'bg-gradient-to-br from-blue-500 to-indigo-600';
  } else if (activeStepCode.value.startsWith('kpi_')) {
    return 'bg-gradient-to-br from-emerald-500 to-teal-600';
  } else if (activeStepCode.value.startsWith('framework_')) {
    return 'bg-gradient-to-br from-purple-500 to-violet-600';
  }
  return 'bg-gradient-to-br from-blue-500 to-indigo-600';
});

const currentFormComponent = shallowRef(null);

const currentFormProps = computed(() => {
  if (!activeStepCode.value || !stepData.value?.data) return {};

  const propsMap = {
    business_basic: { business: stepData.value.data.business },
    business_details: { business: stepData.value.data.business },
    business_maturity: { maturity: stepData.value.data.maturity },
    framework_dream_buyer: { dreamBuyer: stepData.value.data.dream_buyer },
  };

  return propsMap[activeStepCode.value] || {};
});

// Methods
function getCategoryPercent(category) {
  const cat = progress.value?.categories?.[category];
  if (!cat) return 0;
  // KPI ixtiyoriy - oddiy percent ishlatamiz
  // Boshqa kategoriyalar uchun required_percent (overall hisoblashga kiradi)
  if (category === 'kpi') {
    return cat.percent || 0;
  }
  return cat.required_percent || 0;
}

async function loadData() {
  isLoading.value = true;
  hasError.value = false;
  errorMessage.value = '';

  try {
    await Promise.all([
      store.fetchProgress(),
      store.fetchIndustries(),
    ]);
  } catch (err) {
    hasError.value = true;
    errorMessage.value = err.response?.data?.message || 'Ma\'lumotlarni yuklashda xatolik';
  } finally {
    isLoading.value = false;
  }
}

async function openStep(step) {
  console.log('📂 openStep called:', step.code);
  modalError.value = null; // Reset error
  activeStepCode.value = step.code;
  showModal.value = true;
  modalLoading.value = true;
  currentFormComponent.value = null;

  try {
    const response = await store.fetchStepDetail(step.code);
    console.log('📂 Step data received:', response.data);
    stepData.value = response.data;

    const component = formComponents[step.code];
    console.log('📂 Component found:', component ? 'YES' : 'NO', step.code);
    currentFormComponent.value = component || null;
  } catch (err) {
    console.error('❌ Failed to load step:', err);
    modalError.value = {
      message: err.message || 'Step yuklanmadi',
      stack: err.stack,
      info: 'openStep error'
    };
  } finally {
    modalLoading.value = false;
  }
}

function openMetricsStep(stepCode) {
  console.log('📊 openMetricsStep called:', stepCode);
  modalError.value = null; // Reset error
  activeStepCode.value = stepCode;
  showModal.value = true;
  modalLoading.value = false;
  stepData.value = { step: {}, data: {} };

  const component = formComponents[stepCode];
  console.log('📊 Component found:', component ? 'YES' : 'NO', stepCode);
  console.log('📊 Component object:', component);
  currentFormComponent.value = component || null;
  console.log('📊 currentFormComponent set to:', currentFormComponent.value);
}

function closeModal() {
  showModal.value = false;
  activeStepCode.value = null;
  stepData.value = null;
  currentFormComponent.value = null;
  modalError.value = null;
}

async function handleFormSubmit() {
  const currentStepCode = activeStepCode.value;
  closeModal();
  await store.fetchProgress();

  // Find and open next incomplete step
  const nextStep = findNextIncompleteStep(currentStepCode);
  if (nextStep) {
    // Small delay for smooth transition
    setTimeout(() => {
      if (nextStep.code.startsWith('kpi_')) {
        openMetricsStep(nextStep.code);
      } else {
        openStep(nextStep);
      }
    }, 300);
  }
}

// Find next incomplete step after the current one
function findNextIncompleteStep(currentStepCode) {
  // Define step order
  const stepOrder = [
    'business_basic',
    'business_details',
    'business_maturity',
    'kpi_sales',
    'kpi_marketing',
    'framework_problem',
    'framework_dream_buyer',
    'framework_competitors',
    'framework_hypotheses',
  ];

  const currentIndex = stepOrder.indexOf(currentStepCode);
  if (currentIndex === -1) return null;

  // Get all steps from progress
  const allSteps = progress.value?.steps || [];

  // Look for next incomplete step
  for (let i = currentIndex + 1; i < stepOrder.length; i++) {
    const stepCode = stepOrder[i];
    const step = allSteps.find(s => s.code === stepCode);

    // If step found and not completed, return it
    if (step && !step.is_completed) {
      return step;
    }

    // For KPI steps that might not be in the list
    if (stepCode.startsWith('kpi_') && !step) {
      return { code: stepCode, category: 'kpi' };
    }
  }

  return null;
}

function goToDashboard() {
  window.location.href = '/dashboard';
}

function startAIDiagnostic() {
  window.location.href = '/business/diagnostic';
}

// Lifecycle
onMounted(() => {
  loadData();
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.95) translateY(10px);
}
</style>
