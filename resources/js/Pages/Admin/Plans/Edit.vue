<template>
  <AdminLayout :title="plan ? 'Tarifni tahrirlash' : 'Yangi tarif'">
    <div class="max-w-[1600px] mx-auto">
      <!-- Premium Gradient Header -->
      <div class="relative overflow-hidden bg-gradient-to-br from-violet-600 via-purple-600 to-indigo-700 rounded-2xl mb-6">
        <div class="absolute inset-0 bg-grid-white/10 [mask-image:linear-gradient(0deg,transparent,white)]"></div>
        <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -mb-16 -ml-16 w-64 h-64 bg-purple-500/20 rounded-full blur-3xl"></div>

        <div class="relative px-4 py-4 sm:px-6 sm:py-5">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3 sm:gap-4">
              <Link
                href="/dashboard/plans"
                class="p-2 sm:p-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-all duration-200 backdrop-blur-sm"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </Link>
              <div>
                <h1 class="text-lg sm:text-2xl font-bold text-white flex items-center gap-2 sm:gap-3">
                  <div :class="[
                    'w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center',
                    getPlanGradient(form.name)
                  ]">
                    <component :is="getPlanIcon(form.name)" class="w-4 h-4 sm:w-5 sm:h-5 text-white" />
                  </div>
                  {{ plan ? plan.name + ' tahrirlash' : 'Yangi tarif' }}
                </h1>
                <p class="text-purple-200 text-sm mt-0.5 hidden sm:block">Tarif rejasi narxlari va limitlarini sozlang</p>
              </div>
            </div>

            <div class="flex items-center justify-end gap-2 sm:gap-3">
              <!-- Status Badge -->
              <div :class="[
                'px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-medium backdrop-blur-sm',
                form.is_active
                  ? 'bg-emerald-500/20 text-emerald-100 border border-emerald-400/30'
                  : 'bg-red-500/20 text-red-100 border border-red-400/30'
              ]">
                {{ form.is_active ? 'Faol' : 'Nofaol' }}
              </div>

              <!-- Save Button -->
              <button
                @click="saveForm"
                :disabled="loading"
                class="inline-flex items-center gap-2 px-4 py-2 sm:px-6 sm:py-2.5 bg-white text-purple-700 text-sm sm:text-base font-semibold rounded-xl hover:bg-purple-50 transition-all duration-200 shadow-lg shadow-purple-900/20 disabled:opacity-50"
              >
                <svg v-if="loading" class="w-4 h-4 sm:w-5 sm:h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="hidden sm:inline">Saqlash</span>
                <span class="sm:hidden">Saqlash</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-6">
        <!-- Left Column: Basic Info & Pricing -->
        <div class="lg:col-span-1 space-y-6">
          <!-- Basic Info Card -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
            <div class="px-5 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-700 border-b border-gray-100 dark:border-gray-600">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-blue-500 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-base font-semibold text-gray-900 dark:text-white">Asosiy ma'lumotlar</h2>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Tarif identifikatsiyasi</p>
                </div>
              </div>
            </div>
            <div class="p-5 space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Tarif nomi <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  placeholder="Masalan: Business"
                  class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Slug (URL)</label>
                <div class="relative">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm">/plans/</span>
                  <input
                    v-model="form.slug"
                    type="text"
                    placeholder="avtomatik"
                    class="w-full pl-16 pr-4 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                  />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tavsif</label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  placeholder="Qisqa tavsif yozing..."
                  class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none"
                ></textarea>
              </div>
              <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                <div class="flex items-center gap-3">
                  <div :class="[
                    'w-8 h-8 rounded-lg flex items-center justify-center',
                    form.is_active ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-gray-200 dark:bg-gray-600'
                  ]">
                    <svg :class="[
                      'w-4 h-4',
                      form.is_active ? 'text-emerald-600' : 'text-gray-400'
                    ]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Faol holat</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Foydalanuvchilarga ko'rinadi</p>
                  </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input v-model="form.is_active" type="checkbox" class="sr-only peer" />
                  <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all after:shadow-sm peer-checked:bg-emerald-500"></div>
                </label>
              </div>
            </div>
          </div>

          <!-- Pricing Card -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
            <div class="px-5 py-4 bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-gray-700 dark:to-gray-700 border-b border-gray-100 dark:border-gray-600">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-emerald-500 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-base font-semibold text-gray-900 dark:text-white">Narxlash</h2>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Oylik va yillik narxlar</p>
                </div>
              </div>
            </div>
            <div class="p-5 space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Oylik narx</label>
                <div class="relative">
                  <input
                    v-model.number="form.price_monthly"
                    type="number"
                    min="0"
                    step="1000"
                    class="w-full pl-4 pr-16 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                  />
                  <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">so'm</span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">
                  {{ formatPrice(form.price_monthly) }} / oy
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Yillik narx</label>
                <div class="relative">
                  <input
                    v-model.number="form.price_yearly"
                    type="number"
                    min="0"
                    step="1000"
                    class="w-full pl-4 pr-16 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                  />
                  <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">so'm</span>
                </div>
                <!-- Savings Badge -->
                <div class="flex items-center gap-2 mt-2">
                  <div :class="[
                    'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium',
                    calculateSavings > 0
                      ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                      : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                  ]">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    {{ calculateSavings }}% tejamkorlik
                  </div>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Valyuta</label>
                <select
                  v-model="form.currency"
                  class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                >
                  <option value="UZS">UZS - O'zbek so'mi</option>
                  <option value="USD">USD - AQSH dollari</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tartib raqami</label>
                <input
                  v-model.number="form.sort_order"
                  type="number"
                  min="0"
                  class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Middle Column: Limits -->
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm h-full">
            <div class="px-5 py-4 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-gray-700 dark:to-gray-700 border-b border-gray-100 dark:border-gray-600">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-amber-500 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-base font-semibold text-gray-900 dark:text-white">Limitlar</h2>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Raqamli cheklovlar</p>
                </div>
              </div>
            </div>
            <div class="p-3 space-y-2 max-h-[550px] overflow-y-auto custom-scrollbar">
              <div
                v-for="(config, key) in limitConfig"
                :key="key"
                class="group p-3 rounded-xl bg-gray-50 dark:bg-gray-700/30 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors"
              >
                <div class="flex items-center gap-2 mb-2">
                  <div :class="[
                    'w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0',
                    getLimitIconBg(key)
                  ]">
                    <component :is="getLimitIcon(key)" :class="['w-3.5 h-3.5', getLimitIconColor(key)]" />
                  </div>
                  <span class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ config.label }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <input
                    v-model.number="form.limits[key]"
                    type="number"
                    min="0"
                    :placeholder="'∞'"
                    class="flex-1 px-3 py-2 text-sm text-center border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                  />
                  <span class="text-xs text-gray-500 dark:text-gray-400 font-medium w-12">{{ config.suffix }}</span>
                </div>
              </div>
            </div>
            <!-- Quick Actions -->
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
              <div class="flex flex-col gap-2">
                <button
                  @click="setAllLimitsUnlimited"
                  class="w-full px-3 py-2.5 text-xs font-semibold text-white bg-gradient-to-r from-amber-500 to-orange-500 rounded-lg hover:from-amber-600 hover:to-orange-600 transition-all shadow-sm"
                >
                  Barchasini cheksiz qilish
                </button>
                <button
                  @click="resetLimits"
                  class="w-full px-3 py-2.5 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-600 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors"
                >
                  Tozalash
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Features -->
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm h-full">
            <div class="px-5 py-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-gray-700 dark:to-gray-700 border-b border-gray-100 dark:border-gray-600">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-purple-500 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-base font-semibold text-gray-900 dark:text-white">Xususiyatlar</h2>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Funksiyalarni yoqish/o'chirish</p>
                </div>
              </div>
            </div>
            <div class="p-3 space-y-2 max-h-[550px] overflow-y-auto custom-scrollbar">
              <div
                v-for="(config, key) in featureConfig"
                :key="key"
                :class="[
                  'group p-3 rounded-xl transition-all duration-200 cursor-pointer',
                  form.features[key]
                    ? 'bg-purple-100 dark:bg-purple-900/30 ring-2 ring-purple-400 dark:ring-purple-600'
                    : 'bg-gray-50 dark:bg-gray-700/30 hover:bg-gray-100 dark:hover:bg-gray-700/50'
                ]"
                @click="form.features[key] = !form.features[key]"
              >
                <div class="flex items-center justify-between mb-2">
                  <div class="flex items-center gap-2">
                    <div :class="[
                      'w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors',
                      form.features[key]
                        ? 'bg-purple-500'
                        : 'bg-gray-300 dark:bg-gray-600'
                    ]">
                      <svg :class="[
                        'w-3.5 h-3.5 transition-colors',
                        form.features[key] ? 'text-white' : 'text-gray-500 dark:text-gray-400'
                      ]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                    </div>
                    <span :class="[
                      'text-xs font-semibold transition-colors',
                      form.features[key]
                        ? 'text-purple-700 dark:text-purple-300'
                        : 'text-gray-700 dark:text-gray-300'
                    ]">{{ config.label }}</span>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer flex-shrink-0" @click.stop>
                    <input v-model="form.features[key]" type="checkbox" class="sr-only peer" />
                    <div class="w-10 h-5 bg-gray-300 peer-focus:ring-2 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-5 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all after:shadow-sm peer-checked:bg-purple-500"></div>
                  </label>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 pl-9">{{ config.description }}</p>
              </div>
            </div>
            <!-- Quick Actions -->
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
              <div class="flex flex-col gap-2">
                <button
                  @click="enableAllFeatures"
                  class="w-full px-3 py-2.5 text-xs font-semibold text-white bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg hover:from-purple-600 hover:to-pink-600 transition-all shadow-sm"
                >
                  Barchasini yoqish
                </button>
                <button
                  @click="disableAllFeatures"
                  class="w-full px-3 py-2.5 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-600 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors"
                >
                  Barchasini o'chirish
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Preview Column -->
        <div class="lg:col-span-2 xl:col-span-1">
          <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm lg:sticky lg:top-24">
            <div class="px-5 py-4 bg-gradient-to-r from-cyan-50 to-blue-50 dark:from-gray-700 dark:to-gray-700 border-b border-gray-100 dark:border-gray-600">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-cyan-500 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-base font-semibold text-gray-900 dark:text-white">Ko'rinish</h2>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Foydalanuvchi ko'rishi</p>
                </div>
              </div>
            </div>

            <!-- Preview Card -->
            <div class="p-5">
              <div :class="[
                'relative overflow-hidden rounded-2xl border-2 transition-all duration-300',
                getPreviewBorderColor(form.name)
              ]">
                <!-- Gradient Background -->
                <div :class="[
                  'absolute inset-0 opacity-10',
                  getPreviewGradient(form.name)
                ]"></div>

                <!-- Popular Badge (if Business plan) -->
                <div v-if="form.name?.toLowerCase() === 'business'" class="absolute top-0 right-0">
                  <div class="bg-gradient-to-r from-amber-500 to-orange-500 text-white text-xs font-bold px-3 py-1 rounded-bl-lg shadow-lg">
                    ENG FOYDALI
                  </div>
                </div>

                <div class="relative p-5">
                  <!-- Plan Header -->
                  <div class="text-center mb-4">
                    <div :class="[
                      'w-14 h-14 rounded-xl mx-auto mb-3 flex items-center justify-center',
                      getPlanGradient(form.name)
                    ]">
                      <component :is="getPlanIcon(form.name)" class="w-7 h-7 text-white" />
                    </div>
                    <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ form.name || 'Tarif nomi' }}</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ form.description || 'Tavsif' }}</p>
                  </div>

                  <!-- Price -->
                  <div class="text-center mb-5 pb-5 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-end justify-center gap-1">
                      <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ formatShortPrice(form.price_monthly) }}</span>
                      <span class="text-gray-500 dark:text-gray-400 mb-1">/ oy</span>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                      yillik: {{ formatShortPrice(form.price_yearly) }}
                    </p>
                  </div>

                  <!-- Key Limits -->
                  <div class="space-y-2.5 mb-4">
                    <div v-for="(config, key) in getKeyLimits()" :key="key" class="flex items-center gap-2.5">
                      <div class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                      </div>
                      <span class="text-sm text-gray-600 dark:text-gray-300">
                        <span class="font-medium">{{ form.limits[key] || '∞' }}</span> {{ config.label.toLowerCase() }}
                      </span>
                    </div>
                  </div>

                  <!-- Active Features Count -->
                  <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Faol xususiyatlar</span>
                    <span :class="[
                      'px-2.5 py-0.5 rounded-full text-sm font-semibold',
                      activeFeatureCount > 5
                        ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
                        : 'bg-gray-200 text-gray-600 dark:bg-gray-600 dark:text-gray-300'
                    ]">
                      {{ activeFeatureCount }} / {{ Object.keys(featureConfig).length }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Status Indicator -->
              <div class="mt-4 flex items-center justify-center gap-2 py-3 px-4 rounded-xl" :class="form.is_active ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-red-50 dark:bg-red-900/20'">
                <div :class="['w-2 h-2 rounded-full animate-pulse', form.is_active ? 'bg-emerald-500' : 'bg-red-500']"></div>
                <span :class="['text-sm font-medium', form.is_active ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400']">
                  {{ form.is_active ? 'Foydalanuvchilarga ko\'rinadi' : 'Yashirin (foydalanuvchilar ko\'rmaydi)' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, h } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import axios from 'axios';

const props = defineProps({
  plan: Object,
  limitConfig: Object,
  featureConfig: Object,
});

const loading = ref(false);

// Initialize form with defaults or existing plan data
const initLimits = () => {
  const limits = {};
  Object.keys(props.limitConfig).forEach(key => {
    limits[key] = props.plan?.limits?.[key] ?? null;
  });
  return limits;
};

const initFeatures = () => {
  const features = {};
  Object.keys(props.featureConfig).forEach(key => {
    features[key] = props.plan?.features?.[key] ?? false;
  });
  return features;
};

const form = reactive({
  name: props.plan?.name ?? '',
  slug: props.plan?.slug ?? '',
  description: props.plan?.description ?? '',
  price_monthly: props.plan?.price_monthly ?? 0,
  price_yearly: props.plan?.price_yearly ?? 0,
  currency: props.plan?.currency ?? 'UZS',
  is_active: props.plan?.is_active ?? true,
  sort_order: props.plan?.sort_order ?? 0,
  limits: initLimits(),
  features: initFeatures(),
});

// Computed
const calculateSavings = computed(() => {
  if (!form.price_monthly || form.price_monthly === 0) return 0;
  const yearlyIfMonthly = form.price_monthly * 12;
  const savings = ((yearlyIfMonthly - form.price_yearly) / yearlyIfMonthly) * 100;
  return Math.round(savings);
});

const activeFeatureCount = computed(() => {
  return Object.values(form.features).filter(v => v).length;
});

// Format helpers
const formatPrice = (price) => {
  if (!price || price === 0) return 'Bepul';
  return new Intl.NumberFormat('uz-UZ').format(price) + ' so\'m';
};

const formatShortPrice = (price) => {
  if (!price || price === 0) return 'Bepul';
  if (price >= 1000000) {
    return (price / 1000000).toFixed(1).replace('.0', '') + 'M';
  }
  if (price >= 1000) {
    return (price / 1000).toFixed(0) + 'K';
  }
  return price.toString();
};

// Plan styling helpers
const getPlanGradient = (name) => {
  const lower = name?.toLowerCase() || '';
  if (lower === 'start') return 'bg-gradient-to-br from-emerald-400 to-teal-500';
  if (lower === 'standard') return 'bg-gradient-to-br from-blue-400 to-indigo-500';
  if (lower === 'business') return 'bg-gradient-to-br from-amber-400 to-orange-500';
  if (lower === 'premium') return 'bg-gradient-to-br from-purple-400 to-pink-500';
  if (lower === 'enterprise') return 'bg-gradient-to-br from-slate-500 to-gray-700';
  return 'bg-gradient-to-br from-gray-400 to-gray-500';
};

const getPreviewBorderColor = (name) => {
  const lower = name?.toLowerCase() || '';
  if (lower === 'start') return 'border-emerald-200 dark:border-emerald-700';
  if (lower === 'standard') return 'border-blue-200 dark:border-blue-700';
  if (lower === 'business') return 'border-amber-300 dark:border-amber-600';
  if (lower === 'premium') return 'border-purple-200 dark:border-purple-700';
  if (lower === 'enterprise') return 'border-slate-300 dark:border-slate-600';
  return 'border-gray-200 dark:border-gray-700';
};

const getPreviewGradient = (name) => {
  const lower = name?.toLowerCase() || '';
  if (lower === 'start') return 'bg-gradient-to-br from-emerald-400 to-teal-500';
  if (lower === 'standard') return 'bg-gradient-to-br from-blue-400 to-indigo-500';
  if (lower === 'business') return 'bg-gradient-to-br from-amber-400 to-orange-500';
  if (lower === 'premium') return 'bg-gradient-to-br from-purple-400 to-pink-500';
  if (lower === 'enterprise') return 'bg-gradient-to-br from-slate-500 to-gray-700';
  return 'bg-gradient-to-br from-gray-400 to-gray-500';
};

// Icons
const StarIcon = {
  render() {
    return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z' })
    ]);
  }
};

const RocketIcon = {
  render() {
    return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M13 10V3L4 14h7v7l9-11h-7z' })
    ]);
  }
};

const BriefcaseIcon = {
  render() {
    return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' })
    ]);
  }
};

const CrownIcon = {
  render() {
    return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M5 3l3.5 7L12 6l3.5 4L19 3v14a2 2 0 01-2 2H7a2 2 0 01-2-2V3z' })
    ]);
  }
};

const BuildingIcon = {
  render() {
    return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' })
    ]);
  }
};

const DefaultIcon = {
  render() {
    return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z' })
    ]);
  }
};

const getPlanIcon = (name) => {
  const lower = name?.toLowerCase() || '';
  if (lower === 'start') return StarIcon;
  if (lower === 'standard') return RocketIcon;
  if (lower === 'business') return BriefcaseIcon;
  if (lower === 'premium') return CrownIcon;
  if (lower === 'enterprise') return BuildingIcon;
  return DefaultIcon;
};

// Limit icons
const getLimitIcon = (key) => {
  const icons = {
    users: { render() { return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' })]); }},
    branches: { render() { return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' })]); }},
    instagram_accounts: { render() { return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('rect', { x: '2', y: '2', width: '20', height: '20', rx: '5', 'stroke-width': '2' }), h('circle', { cx: '12', cy: '12', r: '4', 'stroke-width': '2' }), h('circle', { cx: '18', cy: '6', r: '1', fill: 'currentColor' })]); }},
    monthly_leads: { render() { return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z' })]); }},
    ai_call_minutes: { render() { return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z' })]); }},
    chatbot_channels: { render() { return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' })]); }},
    telegram_bots: { render() { return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8' })]); }},
    ai_requests: { render() { return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M13 10V3L4 14h7v7l9-11h-7z' })]); }},
    storage_mb: { render() { return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4' })]); }},
    extra_call_price: { render() { return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' })]); }},
  };
  return icons[key] || { render() { return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M13 10V3L4 14h7v7l9-11h-7z' })]); }};
};

const getLimitIconBg = (key) => {
  const colors = {
    users: 'bg-blue-100 dark:bg-blue-900/30',
    branches: 'bg-indigo-100 dark:bg-indigo-900/30',
    instagram_accounts: 'bg-pink-100 dark:bg-pink-900/30',
    monthly_leads: 'bg-emerald-100 dark:bg-emerald-900/30',
    ai_call_minutes: 'bg-violet-100 dark:bg-violet-900/30',
    chatbot_channels: 'bg-cyan-100 dark:bg-cyan-900/30',
    telegram_bots: 'bg-sky-100 dark:bg-sky-900/30',
    ai_requests: 'bg-purple-100 dark:bg-purple-900/30',
    storage_mb: 'bg-slate-100 dark:bg-slate-900/30',
    extra_call_price: 'bg-green-100 dark:bg-green-900/30',
  };
  return colors[key] || 'bg-gray-100 dark:bg-gray-700';
};

const getLimitIconColor = (key) => {
  const colors = {
    users: 'text-blue-600 dark:text-blue-400',
    branches: 'text-indigo-600 dark:text-indigo-400',
    instagram_accounts: 'text-pink-600 dark:text-pink-400',
    monthly_leads: 'text-emerald-600 dark:text-emerald-400',
    ai_call_minutes: 'text-violet-600 dark:text-violet-400',
    chatbot_channels: 'text-cyan-600 dark:text-cyan-400',
    telegram_bots: 'text-sky-600 dark:text-sky-400',
    ai_requests: 'text-purple-600 dark:text-purple-400',
    storage_mb: 'text-slate-600 dark:text-slate-400',
    extra_call_price: 'text-green-600 dark:text-green-400',
  };
  return colors[key] || 'text-gray-600 dark:text-gray-400';
};

// Get key limits for preview (only show top 5)
const getKeyLimits = () => {
  const keyLimitKeys = ['users', 'branches', 'monthly_leads', 'ai_call_minutes', 'ai_requests'];
  const result = {};
  keyLimitKeys.forEach(key => {
    if (props.limitConfig[key]) {
      result[key] = props.limitConfig[key];
    }
  });
  return result;
};

// Quick actions
const setAllLimitsUnlimited = () => {
  Object.keys(form.limits).forEach(key => {
    form.limits[key] = null;
  });
};

const resetLimits = () => {
  Object.keys(form.limits).forEach(key => {
    form.limits[key] = null;
  });
};

const enableAllFeatures = () => {
  Object.keys(form.features).forEach(key => {
    form.features[key] = true;
  });
};

const disableAllFeatures = () => {
  Object.keys(form.features).forEach(key => {
    form.features[key] = false;
  });
};

// Save form
const saveForm = async () => {
  if (!form.name) {
    alert('Tarif nomini kiriting');
    return;
  }

  loading.value = true;

  try {
    // Clean up limits - convert empty/null to null for unlimited
    const cleanLimits = {};
    Object.entries(form.limits).forEach(([key, value]) => {
      cleanLimits[key] = value === '' || value === null || value === undefined ? null : Number(value);
    });

    const payload = {
      ...form,
      limits: cleanLimits,
    };

    if (props.plan) {
      await axios.put(`/dashboard/plans/${props.plan.id}`, payload);
    } else {
      await axios.post('/dashboard/plans', payload);
    }

    router.visit('/dashboard/plans');
  } catch (error) {
    console.error(error);
    alert(error.response?.data?.message || 'Xatolik yuz berdi');
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.5);
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: rgba(156, 163, 175, 0.7);
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgba(75, 85, 99, 0.5);
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: rgba(75, 85, 99, 0.7);
}
</style>
