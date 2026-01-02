<template>
  <BusinessLayout title="Sozlamalar">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
      <!-- Enhanced Header with Animation -->
      <div class="mb-10 relative">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-400 to-purple-500 blur-3xl opacity-10 dark:opacity-20 -z-10"></div>
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-4xl font-extrabold bg-gradient-to-r from-gray-900 to-gray-600 dark:from-gray-100 dark:to-gray-400 bg-clip-text text-transparent mb-3">
              Sozlamalar
            </h1>
            <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 text-lg flex items-center">
              <svg class="w-5 h-5 mr-2 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              Hisob, xavfsizlik va tizim sozlamalarini boshqaring
            </p>
          </div>
          <!-- User Avatar Badge -->
          <div class="hidden sm:flex items-center bg-white dark:bg-gray-800 px-5 py-3 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700">
            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mr-3">
              <span class="text-white font-bold text-lg">{{ user.name.charAt(0).toUpperCase() }}</span>
            </div>
            <div>
              <p class="font-semibold text-gray-900 dark:text-gray-100 dark:text-gray-100">{{ user.name }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ user.email }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Enhanced Sidebar Navigation -->
        <div class="lg:col-span-1">
          <nav class="space-y-2 bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-4 sticky top-6">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                activeTab === tab.id
                  ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105'
                  : 'text-gray-600 dark:text-gray-400 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:text-gray-100 dark:hover:text-gray-100 hover:scale-102',
                'group flex items-center w-full px-4 py-3.5 text-sm font-semibold rounded-xl transition-all duration-200 transform'
              ]"
            >
              <component
                :is="tab.icon"
                :class="[
                  activeTab === tab.id ? 'text-white' : 'text-gray-400 dark:text-gray-500 dark:text-gray-400 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-300',
                  'mr-3 h-5 w-5 flex-shrink-0'
                ]"
              />
              <span>{{ tab.name }}</span>
              <svg
                v-if="activeTab === tab.id"
                class="ml-auto w-5 h-5 text-white"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
            </button>
          </nav>
        </div>

        <!-- Main Content with Enhanced Cards -->
        <div class="lg:col-span-3">
          <!-- Profile Tab -->
          <div v-show="activeTab === 'profile'" class="space-y-6">
            <!-- Profile Information -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl dark:shadow-gray-900/50 border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-2xl dark:hover:shadow-gray-900/80 transition-shadow duration-300">
              <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-blue-50 via-indigo-50 to-purple-50 dark:from-blue-900/20 dark:via-indigo-900/20 dark:to-purple-900/20">
                <div class="flex items-center">
                  <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mr-4 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Profil Ma'lumotlari</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">Shaxsiy ma'lumotlaringizni yangilang</p>
                  </div>
                </div>
              </div>

              <form @submit.prevent="updateProfile" class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                      <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Ism
                      </span>
                    </label>
                    <input
                      v-model="profileForm.name"
                      type="text"
                      class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all bg-white dark:bg-gray-700 dark:text-gray-100"
                      :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': profileForm.errors.name }"
                      required
                    />
                    <p v-if="profileForm.errors.name" class="mt-2 text-sm text-red-600 flex items-center">
                      <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                      </svg>
                      {{ profileForm.errors.name }}
                    </p>
                  </div>

                  <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                      <span class="flex items-center justify-between">
                        <span class="flex items-center">
                          <svg class="w-4 h-4 mr-2 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                          </svg>
                          Login
                        </span>
                        <span class="px-2.5 py-1 bg-gray-100 text-gray-600 dark:text-gray-400 text-xs font-medium rounded-full">O'zgartirib bo'lmaydi</span>
                      </span>
                    </label>
                    <input
                      v-model="user.login"
                      type="text"
                      class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 cursor-not-allowed"
                      disabled
                    />
                  </div>

                  <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                      <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Email
                      </span>
                    </label>
                    <input
                      v-model="profileForm.email"
                      type="email"
                      class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all bg-white dark:bg-gray-700 dark:text-gray-100"
                      :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': profileForm.errors.email }"
                      required
                    />
                    <p v-if="profileForm.errors.email" class="mt-2 text-sm text-red-600 flex items-center">
                      <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                      </svg>
                      {{ profileForm.errors.email }}
                    </p>
                  </div>

                  <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                      <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        Telefon
                        <span class="ml-2 text-xs text-gray-500 dark:text-gray-400 font-normal">(Ixtiyoriy)</span>
                      </span>
                    </label>
                    <input
                      v-model="profileForm.phone"
                      type="tel"
                      class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all bg-white dark:bg-gray-700 dark:text-gray-100"
                      :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': profileForm.errors.phone }"
                      placeholder="+998 XX XXX XX XX"
                    />
                    <p v-if="profileForm.errors.phone" class="mt-2 text-sm text-red-600 flex items-center">
                      <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                      </svg>
                      {{ profileForm.errors.phone }}
                    </p>
                  </div>
                </div>

                <div class="mt-8 flex justify-end">
                  <button
                    type="submit"
                    :disabled="profileForm.processing"
                    class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-500 dark:to-indigo-500 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 dark:hover:from-blue-600 dark:hover:to-indigo-600 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                  >
                    <span v-if="profileForm.processing" class="flex items-center">
                      <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Saqlanmoqda...
                    </span>
                    <span v-else class="flex items-center">
                      <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                      O'zgarishlarni Saqlash
                    </span>
                  </button>
                </div>
              </form>
            </div>

            <!-- Change Password -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl dark:shadow-gray-900/50 border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-2xl dark:hover:shadow-gray-900/80 transition-shadow duration-300">
              <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-red-50 via-orange-50 to-amber-50 dark:from-red-900/20 dark:via-orange-900/20 dark:to-amber-900/20">
                <div class="flex items-center">
                  <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-orange-600 rounded-2xl flex items-center justify-center mr-4 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Parolni O'zgartirish</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">Hisobingizni xavfsiz saqlash uchun kuchli parol kiriting</p>
                  </div>
                </div>
              </div>

              <form @submit.prevent="updatePassword" class="px-6 py-6">
                <div class="space-y-5">
                  <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Joriy Parol</label>
                    <input
                      v-model="passwordForm.current_password"
                      type="password"
                      class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 dark:focus:ring-red-400 focus:border-red-500 dark:focus:border-red-400 transition-all bg-white dark:bg-gray-700 dark:text-gray-100"
                      :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': passwordForm.errors.current_password }"
                      required
                    />
                    <p v-if="passwordForm.errors.current_password" class="mt-2 text-sm text-red-600 flex items-center">
                      <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                      </svg>
                      {{ passwordForm.errors.current_password }}
                    </p>
                  </div>

                  <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Yangi Parol</label>
                    <input
                      v-model="passwordForm.password"
                      type="password"
                      class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 dark:focus:ring-red-400 focus:border-red-500 dark:focus:border-red-400 transition-all bg-white dark:bg-gray-700 dark:text-gray-100"
                      :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': passwordForm.errors.password }"
                      required
                    />
                    <p v-if="passwordForm.errors.password" class="mt-2 text-sm text-red-600 flex items-center">
                      <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                      </svg>
                      {{ passwordForm.errors.password }}
                    </p>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-center">
                      <svg class="w-3.5 h-3.5 mr-1.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Kamida 8 ta belgi, harflar va raqamlar kombinatsiyasi
                    </p>
                  </div>

                  <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Parolni Tasdiqlash</label>
                    <input
                      v-model="passwordForm.password_confirmation"
                      type="password"
                      class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 dark:focus:ring-red-400 focus:border-red-500 dark:focus:border-red-400 transition-all bg-white dark:bg-gray-700 dark:text-gray-100"
                      required
                    />
                  </div>
                </div>

                <div class="mt-8 flex justify-end">
                  <button
                    type="submit"
                    :disabled="passwordForm.processing"
                    class="px-8 py-3 bg-gradient-to-r from-red-600 to-orange-600 dark:from-red-500 dark:to-orange-500 text-white font-semibold rounded-xl hover:from-red-700 hover:to-orange-700 dark:hover:from-red-600 dark:hover:to-orange-600 focus:outline-none focus:ring-2 focus:ring-red-500 dark:focus:ring-red-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                  >
                    <span v-if="passwordForm.processing" class="flex items-center">
                      <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Saqlanmoqda...
                    </span>
                    <span v-else class="flex items-center">
                      <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                      </svg>
                      Parolni O'zgartirish
                    </span>
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Preferences Tab -->
          <div v-show="activeTab === 'preferences'" class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl dark:shadow-gray-900/50 border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-2xl dark:hover:shadow-gray-900/80 transition-shadow duration-300">
              <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-purple-50 via-pink-50 to-rose-50 dark:from-purple-900/20 dark:via-pink-900/20 dark:to-rose-900/20">
                <div class="flex items-center">
                  <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl flex items-center justify-center mr-4 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Bildirishnomalar va Afzalliklar</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">Bildirishnoma sozlamalarini va interfeys afzalliklarini sozlang</p>
                  </div>
                </div>
              </div>

              <form @submit.prevent="updatePreferences" class="px-6 py-6">
                <div class="space-y-8">
                  <!-- Notifications -->
                  <div class="space-y-4">
                    <div class="flex items-center mb-5">
                      <div class="h-px flex-1 bg-gradient-to-r from-transparent via-purple-200 to-transparent"></div>
                      <h3 class="px-4 text-sm font-bold text-gray-900 dark:text-gray-100 uppercase tracking-wider">Bildirishnomalar</h3>
                      <div class="h-px flex-1 bg-gradient-to-r from-transparent via-purple-200 to-transparent"></div>
                    </div>

                    <div class="flex items-center justify-between p-5 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl border-2 border-blue-100 dark:border-blue-800 hover:border-blue-300 dark:hover:border-blue-600 transition-all duration-200 group">
                      <div class="flex-1">
                        <div class="flex items-center">
                          <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                          </div>
                          <div>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Email Bildirishnomalar</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Muhim yangiliklarni email orqali qabul qiling</p>
                          </div>
                        </div>
                      </div>
                      <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input
                          v-model="preferencesForm.email_notifications"
                          type="checkbox"
                          class="sr-only peer"
                        />
                        <div class="w-16 h-8 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-cyan-600 shadow-inner"></div>
                      </label>
                    </div>

                    <div class="flex items-center justify-between p-5 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl border-2 border-indigo-100 hover:border-indigo-300 transition-all duration-200 group">
                      <div class="flex-1">
                        <div class="flex items-center">
                          <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                          </div>
                          <div>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Brauzer Bildirishnomalar</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Real vaqt bildirishnomalarini yoqish</p>
                          </div>
                        </div>
                      </div>
                      <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input
                          v-model="preferencesForm.browser_notifications"
                          type="checkbox"
                          class="sr-only peer"
                        />
                        <div class="w-16 h-8 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-indigo-500 peer-checked:to-purple-600 shadow-inner"></div>
                      </label>
                    </div>

                    <div class="flex items-center justify-between p-5 bg-gradient-to-r from-pink-50 to-rose-50 rounded-xl border-2 border-pink-100 hover:border-pink-300 transition-all duration-200 group">
                      <div class="flex-1">
                        <div class="flex items-center">
                          <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                            </svg>
                          </div>
                          <div>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Marketing Xabarlari</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Yangi funksiyalar va takliflar haqida xabar olish</p>
                          </div>
                        </div>
                      </div>
                      <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input
                          v-model="preferencesForm.marketing_emails"
                          type="checkbox"
                          class="sr-only peer"
                        />
                        <div class="w-16 h-8 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-pink-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-pink-500 peer-checked:to-rose-600 shadow-inner"></div>
                      </label>
                    </div>
                  </div>

                  <div class="border-t-2 border-gray-100 dark:border-gray-700 pt-8">
                    <div class="flex items-center mb-5">
                      <div class="h-px flex-1 bg-gradient-to-r from-transparent via-purple-200 to-transparent"></div>
                      <h3 class="px-4 text-sm font-bold text-gray-900 dark:text-gray-100 uppercase tracking-wider">Interfeys</h3>
                      <div class="h-px flex-1 bg-gradient-to-r from-transparent via-purple-200 to-transparent"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                      <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                          <span class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-purple-500 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                            Til
                          </span>
                        </label>
                        <select v-model="preferencesForm.language" class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-purple-500 dark:focus:border-purple-400 transition-all bg-white dark:bg-gray-700 dark:text-gray-100">
                          <option value="uz">🇺🇿 O'zbekcha</option>
                          <option value="ru">🇷🇺 Русский</option>
                          <option value="en">🇬🇧 English</option>
                        </select>
                      </div>

                      <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                          <span class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-purple-500 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            Interfeys
                          </span>
                        </label>
                        <select v-model="preferencesForm.theme" class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-purple-500 dark:focus:border-purple-400 transition-all bg-white dark:bg-gray-700 dark:text-gray-100">
                          <option value="light">☀️ Yorug'</option>
                          <option value="dark">🌙 Qorong'i</option>
                          <option value="auto">🔄 Avtomatik</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="mt-8 flex justify-end border-t-2 border-gray-100 dark:border-gray-700 pt-6">
                  <button
                    type="submit"
                    :disabled="preferencesForm.processing"
                    class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-500 dark:to-pink-500 text-white font-semibold rounded-xl hover:from-purple-700 hover:to-pink-700 dark:hover:from-purple-600 dark:hover:to-pink-600 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                  >
                    <span v-if="preferencesForm.processing" class="flex items-center">
                      <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Saqlanmoqda...
                    </span>
                    <span v-else class="flex items-center">
                      <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                      O'zgarishlarni Saqlash
                    </span>
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- AI Settings Tab -->
          <div v-show="activeTab === 'ai'" class="space-y-6">
            <!-- API Keys -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl dark:shadow-gray-900/50 border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-2xl dark:hover:shadow-gray-900/80 transition-shadow duration-300">
              <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-emerald-50 via-teal-50 to-cyan-50 dark:from-emerald-900/20 dark:via-teal-900/20 dark:to-cyan-900/20">
                <div class="flex items-center">
                  <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center mr-4 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">API Kalitlari</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">AI funksiyalaridan foydalanish uchun API kalitlarini sozlang</p>
                  </div>
                </div>
              </div>

              <form @submit.prevent="updateApiKeys" class="px-6 py-6">
                <div class="space-y-6">
                  <div class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 border-2 border-blue-200 dark:border-blue-700 rounded-xl p-5">
                    <div class="flex items-start">
                      <svg class="w-6 h-6 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <div class="flex-1">
                        <h4 class="text-sm font-bold text-blue-900 mb-2">Xavfsizlik haqida</h4>
                        <p class="text-sm text-blue-800 leading-relaxed">
                          API kalitlaringiz AES-256 shifrlash bilan himoyalangan va xavfsiz ma'lumotlar bazasida saqlanadi. Ular faqat AI so'rovlarni amalga oshirish uchun ishlatiladi va hech qachon uchinchi shaxslarga ko'rsatilmaydi.
                        </p>
                      </div>
                    </div>
                  </div>

                  <!-- OpenAI API Key -->
                  <div class="border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-emerald-300 dark:hover:border-emerald-600 transition-all duration-200 bg-gradient-to-br from-white to-emerald-50/30 dark:from-gray-800 dark:to-emerald-900/10">
                    <div class="flex items-start justify-between mb-5">
                      <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-600 rounded-xl flex items-center justify-center mr-4 shadow-md">
                          <span class="text-white font-bold text-lg">AI</span>
                        </div>
                        <div>
                          <label class="block text-base font-bold text-gray-900 dark:text-gray-100">
                            OpenAI API Kaliti
                          </label>
                          <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">GPT-4 va GPT-3.5 uchun</p>
                        </div>
                      </div>
                      <div class="flex items-center gap-3">
                        <span v-if="settings.has_openai_key" class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-200">
                          <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                          </svg>
                          Ulangan
                        </span>
                        <button
                          v-if="settings.has_openai_key"
                          type="button"
                          @click="deleteKey('openai')"
                          class="text-sm font-semibold text-red-600 hover:text-red-700 hover:underline transition-colors"
                        >
                          O'chirish
                        </button>
                      </div>
                    </div>
                    <input
                      v-model="apiKeysForm.openai_api_key"
                      type="password"
                      class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-mono text-sm"
                      :placeholder="settings.has_openai_key ? '••••••••••••••••••••••••••••••••' : 'sk-...'"
                    />
                    <p class="mt-3 text-xs text-gray-600 dark:text-gray-400 flex items-center">
                      <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      API kalitini
                      <a href="https://platform.openai.com/api-keys" target="_blank" class="text-emerald-600 hover:text-emerald-700 font-semibold underline mx-1">
                        OpenAI Platform
                      </a>
                      dan oling
                    </p>
                  </div>

                  <!-- Claude API Key -->
                  <div class="border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-orange-300 dark:hover:border-orange-600 transition-all duration-200 bg-gradient-to-br from-white to-orange-50/30 dark:from-gray-800 dark:to-orange-900/10">
                    <div class="flex items-start justify-between mb-5">
                      <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-red-600 rounded-xl flex items-center justify-center mr-4 shadow-md">
                          <span class="text-white font-bold text-lg">C</span>
                        </div>
                        <div>
                          <label class="block text-base font-bold text-gray-900 dark:text-gray-100">
                            Claude API Kaliti
                          </label>
                          <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Claude 3 Opus va Sonnet uchun</p>
                        </div>
                      </div>
                      <div class="flex items-center gap-3">
                        <span v-if="settings.has_claude_key" class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-200">
                          <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                          </svg>
                          Ulangan
                        </span>
                        <button
                          v-if="settings.has_claude_key"
                          type="button"
                          @click="deleteKey('claude')"
                          class="text-sm font-semibold text-red-600 hover:text-red-700 hover:underline transition-colors"
                        >
                          O'chirish
                        </button>
                      </div>
                    </div>
                    <input
                      v-model="apiKeysForm.claude_api_key"
                      type="password"
                      class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all font-mono text-sm"
                      :placeholder="settings.has_claude_key ? '••••••••••••••••••••••••••••••••' : 'sk-ant-...'"
                    />
                    <p class="mt-3 text-xs text-gray-600 dark:text-gray-400 flex items-center">
                      <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      API kalitini
                      <a href="https://console.anthropic.com/settings/keys" target="_blank" class="text-orange-600 hover:text-orange-700 font-semibold underline mx-1">
                        Anthropic Console
                      </a>
                      dan oling
                    </p>
                  </div>
                </div>

                <div class="mt-8 flex justify-end border-t-2 border-gray-100 dark:border-gray-700 pt-6">
                  <button
                    type="submit"
                    :disabled="apiKeysForm.processing"
                    class="px-8 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 dark:from-emerald-500 dark:to-teal-500 text-white font-semibold rounded-xl hover:from-emerald-700 hover:to-teal-700 dark:hover:from-emerald-600 dark:hover:to-teal-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                  >
                    <span v-if="apiKeysForm.processing" class="flex items-center">
                      <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Saqlanmoqda...
                    </span>
                    <span v-else class="flex items-center">
                      <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                      </svg>
                      API Kalitlarini Saqlash
                    </span>
                  </button>
                </div>
              </form>
            </div>

            <!-- AI Preferences -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl dark:shadow-gray-900/50 border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-2xl dark:hover:shadow-gray-900/80 transition-shadow duration-300">
              <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-violet-50 via-purple-50 to-fuchsia-50 dark:from-violet-900/20 dark:via-purple-900/20 dark:to-fuchsia-900/20">
                <div class="flex items-center">
                  <div class="w-14 h-14 bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl flex items-center justify-center mr-4 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">AI Sozlamalari</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">AI model va xatti-harakatlarini moslang</p>
                  </div>
                </div>
              </div>

              <form @submit.prevent="updatePreferences" class="px-6 py-6">
                <div class="space-y-8">
                  <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                      <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                        Afzal AI Model
                      </span>
                    </label>
                    <select v-model="preferencesForm.preferred_ai_model" class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-violet-500 dark:focus:ring-violet-400 focus:border-violet-500 dark:focus:border-violet-400 transition-all bg-white dark:bg-gray-700 dark:text-gray-100 text-base">
                      <option value="gpt-4">🤖 GPT-4 (OpenAI) - Eng kuchli</option>
                      <option value="gpt-3.5-turbo">⚡ GPT-3.5 Turbo (OpenAI) - Tez</option>
                      <option value="claude-3-opus">🎨 Claude 3 Opus (Anthropic) - Ijodiy</option>
                      <option value="claude-3-sonnet">🎵 Claude 3 Sonnet (Anthropic) - Muvozanatli</option>
                    </select>
                    <p class="mt-3 text-xs text-gray-600 dark:text-gray-400 flex items-center">
                      <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Bu model default sifatida ishlatiladi. Har bir so'rovda boshqa model tanlashingiz mumkin.
                    </p>
                  </div>

                  <div class="border-t-2 border-gray-100 dark:border-gray-700 pt-8">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
                      <span class="flex items-center justify-between">
                        <span class="flex items-center">
                          <svg class="w-5 h-5 mr-2 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                          </svg>
                          Ijodkorlik Darajasi
                        </span>
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-bold bg-gradient-to-r from-violet-100 to-purple-100 dark:from-violet-900/40 dark:to-purple-900/40 text-violet-900 dark:text-violet-100 border-2 border-violet-200 dark:border-violet-700">
                          {{ preferencesForm.ai_creativity_level }}/10
                        </span>
                      </span>
                    </label>

                    <div class="relative">
                      <input
                        v-model="preferencesForm.ai_creativity_level"
                        type="range"
                        min="1"
                        max="10"
                        class="w-full h-4 bg-gradient-to-r from-blue-200 via-purple-200 to-pink-200 rounded-lg appearance-none cursor-pointer slider"
                        style="accent-color: #8b5cf6;"
                      />
                      <div class="flex justify-between text-xs font-medium text-gray-600 dark:text-gray-400 mt-3">
                        <span class="flex items-center px-2 py-1 bg-blue-50 rounded-lg">
                          <svg class="w-4 h-4 mr-1.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                          </svg>
                          Konservativ
                        </span>
                        <span class="flex items-center px-2 py-1 bg-purple-50 rounded-lg">
                          <svg class="w-4 h-4 mr-1.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                          </svg>
                          Muvozanatli
                        </span>
                        <span class="flex items-center px-2 py-1 bg-pink-50 rounded-lg">
                          <svg class="w-4 h-4 mr-1.5 text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                          </svg>
                          Ijodiy
                        </span>
                      </div>
                    </div>

                    <div class="mt-5 p-5 bg-gradient-to-r from-violet-50 to-purple-50 dark:from-violet-900/20 dark:to-purple-900/20 border-2 border-violet-200 dark:border-violet-700 rounded-xl">
                      <div class="flex items-start">
                        <div class="w-10 h-10 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center mr-3 flex-shrink-0">
                          <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                        </div>
                        <div>
                          <p class="text-sm font-bold text-violet-900 mb-2">
                            {{ preferencesForm.ai_creativity_level <= 3 ? '🛡️ Konservativ Yondashuv' : preferencesForm.ai_creativity_level <= 7 ? '⚖️ Muvozanatli Yondashuv' : '🚀 Ijodiy Yondashuv' }}
                          </p>
                          <p class="text-sm text-violet-800 leading-relaxed">
                            {{ preferencesForm.ai_creativity_level <= 3 ? 'Aniqroq, faktlarga asoslangan va konservativ javoblar. Biznes ma\'lumotlari tahlili va moliyaviy hisobotlar uchun ideal. AI xatolar ehtimolini minimallash uchun faqat ishonchli ma\'lumotlarga tayanadi.' : preferencesForm.ai_creativity_level <= 7 ? 'Muvozanatli yondashuv - aniqlik va ijodkorlik o\'rtasida. Umumiy foydalanish uchun tavsiya etiladi. AI faktlar va ijodiy yechimlar o\'rtasida optimal balansni saqlaydi.' : 'Ko\'proq ijodiy, noodatiy va eksperimental g\'oyalar. Marketing strategiyalari, kontent yaratish va innovatsion loyihalar uchun foydali. AI yangi va noyob yechimlarni taklif qiladi.' }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="mt-8 flex justify-end border-t-2 border-gray-100 dark:border-gray-700 pt-6">
                  <button
                    type="submit"
                    :disabled="preferencesForm.processing"
                    class="px-8 py-3 bg-gradient-to-r from-violet-600 to-purple-600 dark:from-violet-500 dark:to-purple-500 text-white font-semibold rounded-xl hover:from-violet-700 hover:to-purple-700 dark:hover:from-violet-600 dark:hover:to-purple-600 focus:outline-none focus:ring-2 focus:ring-violet-500 dark:focus:ring-violet-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                  >
                    <span v-if="preferencesForm.processing" class="flex items-center">
                      <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Saqlanmoqda...
                    </span>
                    <span v-else class="flex items-center">
                      <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                      O'zgarishlarni Saqlash
                    </span>
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Integrations Tab -->
          <div v-show="activeTab === 'integrations'" class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl dark:shadow-gray-900/50 border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-2xl dark:hover:shadow-gray-900/80 transition-shadow duration-300">
              <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-cyan-50 via-blue-50 to-indigo-50 dark:from-cyan-900/20 dark:via-blue-900/20 dark:to-indigo-900/20">
                <div class="flex items-center">
                  <div class="w-14 h-14 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-2xl flex items-center justify-center mr-4 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Messaging Platformalar</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">Ijtimoiy tarmoqlar va messaging platformalar bilan AI integratsiyalarini sozlang</p>
                  </div>
                </div>
              </div>

              <div class="px-6 py-6">
                <div class="grid grid-cols-1 gap-5">
                  <!-- WhatsApp AI -->
                  <a
                    :href="route('business.settings.whatsapp-ai')"
                    class="group relative overflow-hidden bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200 rounded-2xl p-6 hover:border-green-400 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex items-center flex-1">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mr-5 group-hover:scale-110 transition-transform shadow-lg">
                          <svg class="w-9 h-9 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                          </svg>
                        </div>
                        <div>
                          <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 group-hover:text-green-700 transition-colors">WhatsApp AI</h3>
                          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1.5">AI-powered WhatsApp chat automation va sozlamalari</p>
                          <div class="flex items-center gap-2 mt-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-green-100 text-green-800 border border-green-200">Chat Bot</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">Auto Reply</span>
                          </div>
                        </div>
                      </div>
                      <svg class="w-7 h-7 text-green-600 group-hover:translate-x-2 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                      </svg>
                    </div>
                  </a>

                  <!-- Instagram AI -->
                  <a
                    :href="route('business.settings.instagram-ai')"
                    class="group relative overflow-hidden bg-gradient-to-br from-pink-50 via-purple-50 to-indigo-50 border-2 border-pink-200 rounded-2xl p-6 hover:border-pink-400 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex items-center flex-1">
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 rounded-2xl flex items-center justify-center mr-5 group-hover:scale-110 transition-transform shadow-lg">
                          <svg class="w-9 h-9 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                          </svg>
                        </div>
                        <div>
                          <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 group-hover:text-pink-700 transition-colors">Instagram AI</h3>
                          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1.5">AI-powered Instagram DM va Story reply automation</p>
                          <div class="flex items-center gap-2 mt-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-pink-100 text-pink-800 border border-pink-200">DM Bot</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-purple-100 text-purple-800 border border-purple-200">Story Reply</span>
                          </div>
                        </div>
                      </div>
                      <svg class="w-7 h-7 text-pink-600 group-hover:translate-x-2 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                      </svg>
                    </div>
                  </a>

                  <!-- WhatsApp Integration -->
                  <a
                    :href="route('business.settings.whatsapp')"
                    class="group relative overflow-hidden bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-blue-200 dark:border-blue-700 rounded-2xl p-6 hover:border-blue-400 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex items-center flex-1">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mr-5 group-hover:scale-110 transition-transform shadow-lg">
                          <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          </svg>
                        </div>
                        <div>
                          <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 group-hover:text-blue-700 transition-colors">WhatsApp Ulanishi</h3>
                          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1.5">WhatsApp Business API ni ulash va sozlash</p>
                          <div class="flex items-center gap-2 mt-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">Business API</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-800 border border-gray-200">Integration</span>
                          </div>
                        </div>
                      </div>
                      <svg class="w-7 h-7 text-blue-600 group-hover:translate-x-2 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                      </svg>
                    </div>
                  </a>
                </div>
              </div>
            </div>

            <!-- Coming Soon -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl dark:shadow-gray-900/50 border border-gray-100 dark:border-gray-700 overflow-hidden">
              <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-slate-50">
                <div class="flex items-center">
                  <div class="w-14 h-14 bg-gradient-to-br from-gray-400 to-slate-500 rounded-2xl flex items-center justify-center mr-4 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Tez Kunda</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">Qo'shimcha integratsiyalar ustida ishlamoqdamiz</p>
                  </div>
                </div>
              </div>

              <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                  <div class="relative overflow-hidden bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-900 dark:to-slate-900 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 opacity-70">
                    <div class="flex items-center">
                      <div class="w-14 h-14 bg-gray-200 rounded-xl flex items-center justify-center mr-4">
                        <span class="text-3xl">✈️</span>
                      </div>
                      <div>
                        <h3 class="font-bold text-gray-700 dark:text-gray-300 text-lg">Telegram Bot</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">AI chat bot va kanallar uchun</p>
                        <span class="inline-block mt-2.5 text-xs px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full font-semibold">Tez kunda</span>
                      </div>
                    </div>
                  </div>

                  <div class="relative overflow-hidden bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-900 dark:to-slate-900 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 opacity-70">
                    <div class="flex items-center">
                      <div class="w-14 h-14 bg-gray-200 rounded-xl flex items-center justify-center mr-4">
                        <span class="text-3xl">👥</span>
                      </div>
                      <div>
                        <h3 class="font-bold text-gray-700 dark:text-gray-300 text-lg">Facebook Messenger</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Messenger bot va DM automation</p>
                        <span class="inline-block mt-2.5 text-xs px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full font-semibold">Tez kunda</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, h, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';

const props = defineProps({
  user: Object,
  settings: Object,
});

const activeTab = ref('profile');

// Icon components
const UserIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' })
]);

const BellIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9' })
]);

const SparklesIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z' })
]);

const LinkIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1' })
]);

const tabs = [
  { id: 'profile', name: 'Profil', icon: UserIcon },
  { id: 'preferences', name: 'Afzalliklar', icon: BellIcon },
  { id: 'ai', name: 'AI', icon: SparklesIcon },
  { id: 'integrations', name: 'Integratsiyalar', icon: LinkIcon },
];

// Profile Form
const profileForm = useForm({
  name: props.user.name,
  email: props.user.email,
  phone: props.user.phone || '',
});

const updateProfile = () => {
  profileForm.put(route('business.settings.profile.update'), {
    preserveScroll: true,
  });
};

// Password Form
const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const updatePassword = () => {
  passwordForm.put(route('business.settings.password.update'), {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset();
    },
  });
};

// Preferences Form
const preferencesForm = useForm({
  email_notifications: props.settings.email_notifications,
  browser_notifications: props.settings.browser_notifications,
  marketing_emails: props.settings.marketing_emails,
  preferred_ai_model: props.settings.preferred_ai_model,
  ai_creativity_level: props.settings.ai_creativity_level,
  theme: props.settings.theme,
  language: props.settings.language,
});

const updatePreferences = () => {
  preferencesForm.put(route('business.settings.preferences.update'), {
    preserveScroll: true,
    onSuccess: () => {
      // Apply theme change immediately
      applyTheme(preferencesForm.theme);
    },
  });
};

// Apply theme function
const applyTheme = (theme) => {
  localStorage.setItem('theme', theme);

  if (theme === 'dark') {
    document.documentElement.classList.add('dark');
  } else if (theme === 'light') {
    document.documentElement.classList.remove('dark');
  } else if (theme === 'auto') {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (prefersDark) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  }
};

// Watch for theme changes
watch(() => preferencesForm.theme, (newTheme) => {
  applyTheme(newTheme);
});

// API Keys Form
const apiKeysForm = useForm({
  openai_api_key: '',
  claude_api_key: '',
});

const updateApiKeys = () => {
  apiKeysForm.put(route('business.settings.api-keys.update'), {
    preserveScroll: true,
    onSuccess: () => {
      apiKeysForm.reset();
    },
  });
};

const deleteKey = (keyType) => {
  if (confirm('Haqiqatan ham bu API kalitini o\'chirmoqchimisiz?')) {
    useForm({ key_type: keyType }).delete(route('business.settings.api-keys.delete'), {
      preserveScroll: true,
    });
  }
};
</script>

<style scoped>
.slider::-webkit-slider-thumb {
  appearance: none;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
  cursor: pointer;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  transition: all 0.2s ease;
}

.slider::-webkit-slider-thumb:hover {
  transform: scale(1.2);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.slider::-moz-range-thumb {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
  cursor: pointer;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  border: none;
  transition: all 0.2s ease;
}

.slider::-moz-range-thumb:hover {
  transform: scale(1.2);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}
</style>
