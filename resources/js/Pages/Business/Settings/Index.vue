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
                      <div class="h-px flex-1 bg-gradient-to-r from-transparent via-purple-300 dark:via-purple-700 to-transparent"></div>
                      <h3 class="px-4 text-sm font-bold text-gray-900 dark:text-gray-100 uppercase tracking-wider">Bildirishnomalar</h3>
                      <div class="h-px flex-1 bg-gradient-to-r from-transparent via-purple-300 dark:via-purple-700 to-transparent"></div>
                    </div>

                    <div class="flex items-center justify-between p-5 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl border-2 border-blue-100 dark:border-blue-800 hover:border-blue-300 dark:hover:border-blue-600 transition-all duration-200 group">
                      <div class="flex-1">
                        <div class="flex items-center">
                          <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform shadow-lg">
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
                        <div class="w-16 h-8 bg-gray-300 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 dark:after:border-gray-500 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-cyan-600 shadow-inner"></div>
                      </label>
                    </div>

                    <div class="flex items-center justify-between p-5 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl border-2 border-indigo-100 dark:border-indigo-800 hover:border-indigo-300 dark:hover:border-indigo-600 transition-all duration-200 group">
                      <div class="flex-1">
                        <div class="flex items-center">
                          <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform shadow-lg">
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
                        <div class="w-16 h-8 bg-gray-300 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 dark:after:border-gray-500 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-indigo-500 peer-checked:to-purple-600 shadow-inner"></div>
                      </label>
                    </div>

                    <div class="flex items-center justify-between p-5 bg-gradient-to-r from-pink-50 to-rose-50 dark:from-pink-900/20 dark:to-rose-900/20 rounded-xl border-2 border-pink-100 dark:border-pink-800 hover:border-pink-300 dark:hover:border-pink-600 transition-all duration-200 group">
                      <div class="flex-1">
                        <div class="flex items-center">
                          <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform shadow-lg">
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
                        <div class="w-16 h-8 bg-gray-300 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-pink-300 dark:peer-focus:ring-pink-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 dark:after:border-gray-500 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-pink-500 peer-checked:to-rose-600 shadow-inner"></div>
                      </label>
                    </div>
                  </div>

                  <div class="border-t-2 border-gray-100 dark:border-gray-700 pt-8">
                    <div class="flex items-center mb-5">
                      <div class="h-px flex-1 bg-gradient-to-r from-transparent via-purple-300 dark:via-purple-700 to-transparent"></div>
                      <h3 class="px-4 text-sm font-bold text-gray-900 dark:text-gray-100 uppercase tracking-wider">Interfeys</h3>
                      <div class="h-px flex-1 bg-gradient-to-r from-transparent via-purple-300 dark:via-purple-700 to-transparent"></div>
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


          <!-- Team Tab -->
          <div v-show="activeTab === 'team'" class="space-y-6">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
              <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-blue-50 via-indigo-50 to-purple-50 dark:from-blue-900/20 dark:via-indigo-900/20 dark:to-purple-900/20">
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mr-4 shadow-lg">
                      <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                      </svg>
                    </div>
                    <div>
                      <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Jamoa a'zolari</h2>
                      <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">Biznes jamoangizni boshqaring</p>
                    </div>
                  </div>
                  <button
                    @click="showInviteModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 text-white font-medium rounded-xl hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg"
                  >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Xodim qo'shish
                  </button>
                </div>
              </div>

              <!-- Team Members Table -->
              <div class="p-6">
                <!-- Loading -->
                <div v-if="isLoadingTeam" class="flex justify-center py-12">
                  <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                </div>

                <!-- Empty State -->
                <div v-else-if="teamMembers.length === 0" class="text-center py-12">
                  <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                  </div>
                  <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Jamoa a'zolari yo'q</h3>
                  <p class="text-gray-500 dark:text-gray-400 mb-4">Birinchi xodimingizni qo'shing</p>
                  <button
                    @click="showInviteModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Xodim qo'shish
                  </button>
                </div>

                <!-- Members List -->
                <div v-else class="space-y-3">
                  <div
                    v-for="member in teamMembers"
                    :key="member.id"
                    :class="[
                      'flex items-center justify-between p-4 rounded-xl transition-colors',
                      member.is_owner
                        ? 'bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 border border-amber-200 dark:border-amber-800'
                        : 'bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]"
                  >
                    <div class="flex items-center gap-4">
                      <!-- Avatar -->
                      <div :class="[
                        'w-12 h-12 rounded-full flex items-center justify-center text-white font-semibold text-lg',
                        member.is_owner
                          ? 'bg-gradient-to-br from-amber-500 to-yellow-600'
                          : 'bg-gradient-to-br from-blue-500 to-purple-600'
                      ]">
                        {{ member.name?.charAt(0)?.toUpperCase() || '?' }}
                      </div>
                      <!-- Info -->
                      <div>
                        <div class="flex items-center gap-2">
                          <h4 class="font-semibold text-gray-900 dark:text-white">
                            {{ member.name }}
                          </h4>
                          <span v-if="member.is_owner" class="px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded-full flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            Biznes egasi
                          </span>
                          <span v-else class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-full">
                            Faol
                          </span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ member.phone?.startsWith('+') ? member.phone : '+' + member.phone }}</p>
                        <div v-if="member.department" class="flex items-center gap-2 mt-1">
                          <span :class="['px-2 py-0.5 text-xs font-medium rounded-full', departmentColors[member.department] || 'bg-gray-100 text-gray-600']">
                            {{ member.department_label || member.department }}
                          </span>
                        </div>
                      </div>
                    </div>

                    <!-- Actions (only for non-owners) -->
                    <div v-if="!member.is_owner" class="flex items-center gap-2">
                      <button
                        @click="openEditMemberModal(member)"
                        class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                        title="Tahrirlash"
                      >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                      <button
                        @click="removeMember(member)"
                        class="p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                        title="O'chirish"
                      >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Info Card -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-800">
              <div class="flex gap-3">
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-blue-700 dark:text-blue-300">
                  <p class="font-medium mb-1">Jamoa a'zolari haqida</p>
                  <p>Xodimlar telefon raqam va parol orqali tizimga kiradi. Har bir a'zoga bo'lim va rol tayinlash mumkin.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Invite Modal -->
          <InviteTeamMemberModal
            :show="showInviteModal"
            :departments="teamDepartments"
            @close="showInviteModal = false"
            @added="onMemberInvited"
          />

          <!-- Edit Member Modal -->
          <Teleport to="body">
            <Transition
              enter-active-class="transition ease-out duration-200"
              enter-from-class="opacity-0"
              enter-to-class="opacity-100"
              leave-active-class="transition ease-in duration-150"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" @click="showEditModal = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md z-10 overflow-hidden">
                  <!-- Header -->
                  <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                      Xodimni tahrirlash
                    </h3>
                    <button @click="showEditModal = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>

                  <!-- Body -->
                  <div class="p-6 space-y-5">
                    <!-- Member Info -->
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                      <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                        {{ editingMember?.name?.charAt(0)?.toUpperCase() || 'X' }}
                      </div>
                      <div>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ editingMember?.name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">+{{ editingMember?.phone }}</p>
                      </div>
                    </div>

                    <!-- Department -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Bo'lim
                      </label>
                      <div class="grid grid-cols-2 gap-2">
                        <button
                          v-for="(label, value) in teamDepartments"
                          :key="value"
                          @click="editForm.department = value"
                          :class="[
                            'flex items-center justify-center gap-2 p-3 rounded-xl border-2 transition-all text-sm font-medium',
                            editForm.department === value
                              ? 'border-blue-500 ' + (departmentColors[value] || 'bg-blue-50 text-blue-700')
                              : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 text-gray-700 dark:text-gray-300'
                          ]"
                        >
                          {{ label }}
                        </button>
                      </div>
                    </div>

                    <!-- Error -->
                    <p v-if="editError" class="text-sm text-red-500">{{ editError }}</p>
                  </div>

                  <!-- Footer -->
                  <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    <button
                      @click="showEditModal = false"
                      class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium rounded-xl transition-colors"
                    >
                      Bekor qilish
                    </button>
                    <button
                      @click="updateMember"
                      :disabled="isEditLoading"
                      class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors disabled:opacity-50"
                    >
                      {{ isEditLoading ? 'Saqlanmoqda...' : 'Saqlash' }}
                    </button>
                  </div>
                </div>
              </div>
            </Transition>
          </Teleport>

          <!-- Integrations Tab -->
          <div v-show="activeTab === 'integrations'" class="space-y-5">

            <!-- Messaging Platformalar -->
            <div class="bg-white dark:bg-gray-800/80 backdrop-blur-sm border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm dark:shadow-none">
              <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-cyan-50 via-blue-50 to-purple-50 dark:from-cyan-500/10 dark:via-blue-500/10 dark:to-purple-500/10">
                <div class="flex items-center">
                  <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Messaging</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Ijtimoiy tarmoqlar va SMS xizmatlari</p>
                  </div>
                </div>
              </div>

              <div class="p-4 bg-gray-50/50 dark:bg-transparent">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                  <!-- Instagram AI -->
                  <a
                    :href="route('business.settings.instagram-ai')"
                    class="group relative overflow-hidden bg-white dark:bg-gray-800/50 border border-pink-200 dark:border-pink-500/30 rounded-xl p-3 hover:border-pink-400 hover:bg-pink-50 dark:hover:bg-pink-500/20 transition-all duration-300 hover:shadow-lg hover:shadow-pink-500/10"
                  >
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 rounded-xl flex items-center justify-center mb-2 group-hover:scale-110 transition-transform shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                      </div>
                      <h3 class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-pink-600 dark:group-hover:text-pink-400 transition-colors">Instagram AI</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">DM & Story</p>
                    </div>
                  </a>

                  <!-- Eskiz SMS -->
                  <a
                    :href="route('business.settings.sms') + '?provider=eskiz'"
                    class="group relative overflow-hidden bg-white dark:bg-gray-800/50 border border-teal-200 dark:border-teal-500/30 rounded-xl p-3 hover:border-teal-400 hover:bg-teal-50 dark:hover:bg-teal-500/20 transition-all duration-300 hover:shadow-lg hover:shadow-teal-500/10"
                  >
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl flex items-center justify-center mb-2 group-hover:scale-110 transition-transform shadow-lg">
                        <span class="text-white font-black text-xl">E</span>
                      </div>
                      <h3 class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors">Eskiz</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">SMS gateway</p>
                    </div>
                  </a>

                  <!-- PlayMobile SMS -->
                  <a
                    :href="route('business.settings.sms') + '?provider=playmobile'"
                    class="group relative overflow-hidden bg-white dark:bg-gray-800/50 border border-orange-200 dark:border-orange-500/30 rounded-xl p-3 hover:border-orange-400 hover:bg-orange-50 dark:hover:bg-orange-500/20 transition-all duration-300 hover:shadow-lg hover:shadow-orange-500/10"
                  >
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl flex items-center justify-center mb-2 group-hover:scale-110 transition-transform shadow-lg">
                        <span class="text-white font-black text-sm">PM</span>
                      </div>
                      <h3 class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">PlayMobile</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">SMS gateway</p>
                    </div>
                  </a>

                  <!-- Telegram Bot -->
                  <a
                    href="#"
                    class="group relative overflow-hidden bg-gray-50 dark:bg-gray-800/30 border border-gray-200 dark:border-gray-600/30 rounded-xl p-3 opacity-70 dark:opacity-60 cursor-not-allowed"
                  >
                    <div class="absolute top-1.5 right-1.5">
                      <span class="px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Tez kunda</span>
                    </div>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-sky-600 rounded-xl flex items-center justify-center mb-2 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                        </svg>
                      </div>
                      <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300">Telegram</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Bot & Kanal</p>
                    </div>
                  </a>

                  <!-- Facebook -->
                  <a
                    href="#"
                    class="group relative overflow-hidden bg-gray-50 dark:bg-gray-800/30 border border-gray-200 dark:border-gray-600/30 rounded-xl p-3 opacity-70 dark:opacity-60 cursor-not-allowed"
                  >
                    <div class="absolute top-1.5 right-1.5">
                      <span class="px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Tez kunda</span>
                    </div>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-[#1877F2] rounded-xl flex items-center justify-center mb-2 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                      </div>
                      <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300">Facebook</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Messenger</p>
                    </div>
                  </a>

                  <!-- WhatsApp -->
                  <a
                    href="#"
                    class="group relative overflow-hidden bg-gray-50 dark:bg-gray-800/30 border border-gray-200 dark:border-gray-600/30 rounded-xl p-3 opacity-70 dark:opacity-60 cursor-not-allowed"
                  >
                    <div class="absolute top-1.5 right-1.5">
                      <span class="px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Tez kunda</span>
                    </div>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-[#25D366] rounded-xl flex items-center justify-center mb-2 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                      </div>
                      <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300">WhatsApp</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Business API</p>
                    </div>
                  </a>
                </div>
              </div>
            </div>

            <!-- Reklama Platformalar -->
            <div class="bg-white dark:bg-gray-800/80 backdrop-blur-sm border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm dark:shadow-none">
              <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 via-green-50 to-yellow-50 dark:from-blue-500/10 dark:via-green-500/10 dark:to-yellow-500/10">
                <div class="flex items-center">
                  <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-green-600 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Reklama</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Reklama kampaniyalari va analytics</p>
                  </div>
                </div>
              </div>

              <div class="p-4 bg-gray-50/50 dark:bg-transparent">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                  <!-- Google Ads - Coming Soon -->
                  <div
                    class="group relative overflow-hidden bg-gray-50 dark:bg-gray-800/30 border border-gray-200 dark:border-gray-600/30 rounded-xl p-3 opacity-70 dark:opacity-60 cursor-not-allowed"
                  >
                    <div class="absolute top-1.5 right-1.5">
                      <span class="px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Tez kunda</span>
                    </div>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-white dark:bg-white rounded-xl flex items-center justify-center mb-2 shadow-lg">
                        <svg class="w-7 h-7" viewBox="0 0 48 48">
                          <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/>
                          <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/>
                          <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/>
                          <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/>
                        </svg>
                      </div>
                      <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300">Google Ads</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Kampaniyalar</p>
                    </div>
                  </div>

                  <!-- Yandex Direct - Coming Soon -->
                  <div
                    class="group relative overflow-hidden bg-gray-50 dark:bg-gray-800/30 border border-gray-200 dark:border-gray-600/30 rounded-xl p-3 opacity-70 dark:opacity-60 cursor-not-allowed"
                  >
                    <div class="absolute top-1.5 right-1.5">
                      <span class="px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Tez kunda</span>
                    </div>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-[#FC3F1D] rounded-xl flex items-center justify-center mb-2 shadow-lg">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                          <path d="M14.5 5H12.2C9.6 5 8 6.8 8 9.1C8 10.9 8.9 12.2 10.5 13L8 19H10.5L12.8 13.4V19H15V5H14.5ZM12.8 11.5L11.5 10.8C10.6 10.4 10.2 9.8 10.2 9C10.2 7.9 10.9 7.2 12.1 7.2H12.8V11.5Z" fill="white"/>
                        </svg>
                      </div>
                      <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300">Yandex Direct</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Reklama</p>
                    </div>
                  </div>

                  <!-- Facebook Ads -->
                  <a
                    href="#"
                    class="group relative overflow-hidden bg-gray-50 dark:bg-gray-800/30 border border-gray-200 dark:border-gray-600/30 rounded-xl p-3 opacity-70 dark:opacity-60 cursor-not-allowed"
                  >
                    <div class="absolute top-1.5 right-1.5">
                      <span class="px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Tez kunda</span>
                    </div>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-[#1877F2] rounded-xl flex items-center justify-center mb-2 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                      </div>
                      <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300">Facebook Ads</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Meta Ads</p>
                    </div>
                  </a>

                  <!-- TikTok Ads -->
                  <a
                    href="#"
                    class="group relative overflow-hidden bg-gray-50 dark:bg-gray-800/30 border border-gray-200 dark:border-gray-600/30 rounded-xl p-3 opacity-70 dark:opacity-60 cursor-not-allowed"
                  >
                    <div class="absolute top-1.5 right-1.5">
                      <span class="px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Tez kunda</span>
                    </div>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-black rounded-xl flex items-center justify-center mb-2 shadow-lg">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none">
                          <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z" fill="#ff0050"/>
                          <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z" fill="#00f2ea" style="mix-blend-mode:multiply"/>
                        </svg>
                      </div>
                      <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300">TikTok Ads</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Video reklama</p>
                    </div>
                  </a>
                </div>
              </div>
            </div>

            <!-- Analytics & Media -->
            <div class="bg-white dark:bg-gray-800/80 backdrop-blur-sm border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm dark:shadow-none">
              <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-red-50 via-purple-50 to-blue-50 dark:from-red-500/10 dark:via-purple-500/10 dark:to-blue-500/10">
                <div class="flex items-center">
                  <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-purple-600 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Analytics & Media</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Statistika va kontent platformalari</p>
                  </div>
                </div>
              </div>

              <div class="p-4 bg-gray-50/50 dark:bg-transparent">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                  <!-- YouTube Analytics - Coming Soon -->
                  <div
                    class="group relative overflow-hidden bg-gray-50 dark:bg-gray-800/30 border border-gray-200 dark:border-gray-600/30 rounded-xl p-3 opacity-70 dark:opacity-60 cursor-not-allowed"
                  >
                    <div class="absolute top-1.5 right-1.5">
                      <span class="px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Tez kunda</span>
                    </div>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-red-600 rounded-xl flex items-center justify-center mb-2 shadow-lg">
                        <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor">
                          <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                      </div>
                      <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300">YouTube</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Kanal statistika</p>
                    </div>
                  </div>

                  <!-- Google Analytics -->
                  <a
                    :href="route('business.settings.analytics')"
                    class="group relative overflow-hidden bg-white dark:bg-gray-800/50 border border-orange-200 dark:border-orange-500/30 rounded-xl p-3 hover:border-orange-400 hover:bg-orange-50 dark:hover:bg-orange-500/20 transition-all duration-300 hover:shadow-lg hover:shadow-orange-500/10"
                  >
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-yellow-500 rounded-xl flex items-center justify-center mb-2 group-hover:scale-110 transition-transform shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                      </div>
                      <h3 class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">GA4</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Sayt analytics</p>
                    </div>
                  </a>

                  <!-- Yandex Metrika -->
                  <a
                    :href="route('business.settings.analytics')"
                    class="group relative overflow-hidden bg-white dark:bg-gray-800/50 border border-red-200 dark:border-red-500/30 rounded-xl p-3 hover:border-red-400 hover:bg-red-50 dark:hover:bg-red-500/20 transition-all duration-300 hover:shadow-lg hover:shadow-red-500/10"
                  >
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-[#FC3F1D] rounded-xl flex items-center justify-center mb-2 group-hover:scale-110 transition-transform shadow-lg">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                          <path d="M14.5 5H12.2C9.6 5 8 6.8 8 9.1C8 10.9 8.9 12.2 10.5 13L8 19H10.5L12.8 13.4V19H15V5H14.5ZM12.8 11.5L11.5 10.8C10.6 10.4 10.2 9.8 10.2 9C10.2 7.9 10.9 7.2 12.1 7.2H12.8V11.5Z" fill="white"/>
                        </svg>
                      </div>
                      <h3 class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">Metrika</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Yandex</p>
                    </div>
                  </a>
                </div>
              </div>
            </div>

            <!-- Telefon & CRM -->
            <div class="bg-white dark:bg-gray-800/80 backdrop-blur-sm border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm dark:shadow-none">
              <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-indigo-50 via-violet-50 to-purple-50 dark:from-indigo-500/10 dark:via-violet-500/10 dark:to-purple-500/10">
                <div class="flex items-center">
                  <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Telefon & CRM</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">VoIP telefoniya va CRM integratsiyalar</p>
                  </div>
                </div>
              </div>

              <div class="p-4 bg-gray-50/50 dark:bg-transparent">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                  <!-- SipUNI -->
                  <a
                    :href="route('integrations.telephony.settings') + '?provider=sipuni'"
                    class="group relative overflow-hidden bg-white dark:bg-gray-800/50 border border-purple-200 dark:border-purple-500/30 rounded-xl p-3 hover:border-purple-400 hover:bg-purple-50 dark:hover:bg-purple-500/20 transition-all duration-300 hover:shadow-lg hover:shadow-purple-500/10"
                  >
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl flex items-center justify-center mb-2 group-hover:scale-110 transition-transform shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                      </div>
                      <h3 class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">SipUni</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">IP-telefoniya</p>
                    </div>
                  </a>

                  <!-- PBX -->
                  <a
                    :href="route('integrations.telephony.settings') + '?provider=pbx'"
                    class="group relative overflow-hidden bg-white dark:bg-gray-800/50 border border-blue-200 dark:border-blue-500/30 rounded-xl p-3 hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-500/20 transition-all duration-300 hover:shadow-lg hover:shadow-blue-500/10"
                  >
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mb-2 group-hover:scale-110 transition-transform shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                      </div>
                      <h3 class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">PBX</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Telefoniya</p>
                    </div>
                  </a>

                  <!-- MoiZvonki -->
                  <a
                    :href="route('integrations.telephony.settings') + '?provider=moizvonki'"
                    class="group relative overflow-hidden bg-white dark:bg-gray-800/50 border border-orange-200 dark:border-orange-500/30 rounded-xl p-3 hover:border-orange-400 hover:bg-orange-50 dark:hover:bg-orange-500/20 transition-all duration-300 hover:shadow-lg hover:shadow-orange-500/10"
                  >
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center mb-2 group-hover:scale-110 transition-transform shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                      </div>
                      <h3 class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">MoiZvonki</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Android qo'ng'iroq</p>
                    </div>
                  </a>

                  <!-- UTEL -->
                  <a
                    :href="route('integrations.telephony.settings') + '?provider=utel'"
                    class="group relative overflow-hidden bg-white dark:bg-gray-800/50 border border-green-200 dark:border-green-500/30 rounded-xl p-3 hover:border-green-400 hover:bg-green-50 dark:hover:bg-green-500/20 transition-all duration-300 hover:shadow-lg hover:shadow-green-500/10"
                  >
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-teal-600 rounded-xl flex items-center justify-center mb-2 group-hover:scale-110 transition-transform shadow-lg">
                        <span class="text-white font-bold text-sm">UZ</span>
                      </div>
                      <h3 class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">UTEL</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">O'zbekiston VoIP</p>
                    </div>
                  </a>

                  <!-- Bitrix24 -->
                  <a
                    href="#"
                    class="group relative overflow-hidden bg-gray-50 dark:bg-gray-800/30 border border-gray-200 dark:border-gray-600/30 rounded-xl p-3 opacity-70 dark:opacity-60 cursor-not-allowed"
                  >
                    <div class="absolute top-1.5 right-1.5">
                      <span class="px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Tez kunda</span>
                    </div>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center mb-2 shadow-lg">
                        <span class="text-white font-bold text-sm">B24</span>
                      </div>
                      <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300">Bitrix24</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">CRM sync</p>
                    </div>
                  </a>
                </div>
              </div>
            </div>

            <!-- Hisobot & Buxgalteriya -->
            <div class="bg-white dark:bg-gray-800/80 backdrop-blur-sm border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm">
              <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-yellow-50 via-amber-50 to-orange-50 dark:from-yellow-500/10 dark:via-amber-500/10 dark:to-orange-500/10">
                <div class="flex items-center">
                  <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-amber-600 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Hisobot & Buxgalteriya</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Hisob-kitob va moliyaviy integratsiyalar</p>
                  </div>
                </div>
              </div>

              <div class="p-4 bg-gray-50/50 dark:bg-transparent">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                  <!-- 1C -->
                  <a
                    href="#"
                    class="group relative overflow-hidden bg-gray-50 dark:bg-gray-800/30 border border-yellow-200 dark:border-yellow-500/30 rounded-xl p-3 opacity-70 dark:opacity-60 cursor-not-allowed"
                  >
                    <div class="absolute top-1.5 right-1.5">
                      <span class="px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Tez kunda</span>
                    </div>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-amber-600 rounded-xl flex items-center justify-center mb-2 shadow-lg">
                        <span class="text-white font-bold text-base">1C</span>
                      </div>
                      <h3 class="text-xs font-bold text-gray-900 dark:text-white">1C</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Buxgalteriya</p>
                    </div>
                  </a>

                  <!-- MySoliq -->
                  <a
                    href="#"
                    class="group relative overflow-hidden bg-gray-50 dark:bg-gray-800/30 border border-green-200 dark:border-green-500/30 rounded-xl p-3 opacity-70 dark:opacity-60 cursor-not-allowed"
                  >
                    <div class="absolute top-1.5 right-1.5">
                      <span class="px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300">Tez kunda</span>
                    </div>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mb-2 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                      </div>
                      <h3 class="text-xs font-bold text-gray-900 dark:text-white">MySoliq</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Soliq hisoboti</p>
                    </div>
                  </a>

                  <!-- Click -->
                  <a
                    :href="route('business.settings.payments') + '?tab=click'"
                    class="group relative overflow-hidden bg-white dark:bg-gray-800/50 border border-blue-200 dark:border-blue-500/30 rounded-xl p-3 hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-500/20 transition-all duration-300 hover:shadow-lg hover:shadow-blue-500/10"
                  >
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mb-2 shadow-lg group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                      </div>
                      <h3 class="text-xs font-bold text-gray-900 dark:text-white">Click</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">To'lov</p>
                    </div>
                  </a>

                  <!-- Payme -->
                  <a
                    :href="route('business.settings.payments') + '?tab=payme'"
                    class="group relative overflow-hidden bg-white dark:bg-gray-800/50 border border-cyan-200 dark:border-cyan-500/30 rounded-xl p-3 hover:border-cyan-400 hover:bg-cyan-50 dark:hover:bg-cyan-500/20 transition-all duration-300 hover:shadow-lg hover:shadow-cyan-500/10"
                  >
                    <div class="flex flex-col items-center text-center">
                      <div class="w-12 h-12 bg-gradient-to-br from-cyan-500 to-teal-600 rounded-xl flex items-center justify-center mb-2 shadow-lg group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                      </div>
                      <h3 class="text-xs font-bold text-gray-900 dark:text-white">Payme</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">To'lov</p>
                    </div>
                  </a>
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
import InviteTeamMemberModal from '@/components/InviteTeamMemberModal.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

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

const LinkIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1' })
]);

const UsersIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' })
]);

const tabs = [
  { id: 'profile', name: 'Profil', icon: UserIcon },
  { id: 'preferences', name: 'Afzalliklar', icon: BellIcon },
  { id: 'team', name: 'Jamoa', icon: UsersIcon },
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

// Team Management
const teamMembers = ref([]);
const teamDepartments = ref({});
const teamRoles = ref({});
const isLoadingTeam = ref(false);
const showInviteModal = ref(false);
const editingMember = ref(null);
const showEditModal = ref(false);
const editForm = ref({ department: '' });
const editError = ref('');
const isEditLoading = ref(false);

const fetchTeamMembers = async () => {
  isLoadingTeam.value = true;
  try {
    const response = await fetch(route('business.settings.team.index'), {
      headers: {
        'Accept': 'application/json',
      },
    });
    const data = await response.json();
    if (data.members) {
      teamMembers.value = data.members;
      teamDepartments.value = data.departments || {};
      teamRoles.value = data.roles || {};
    }
  } catch (error) {
    console.error('Failed to fetch team members:', error);
  } finally {
    isLoadingTeam.value = false;
  }
};

const onMemberInvited = (member) => {
  teamMembers.value.unshift(member);
};

const resendInvite = async (member) => {
  try {
    const response = await fetch(route('business.settings.team.resend', member.id), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
      },
    });
    const data = await response.json();
    if (data.success) {
      alert('Taklif qayta yuborildi');
    }
  } catch (error) {
    console.error('Failed to resend invite:', error);
  }
};

const cancelInvite = async (member) => {
  if (!confirm('Taklifni bekor qilmoqchimisiz?')) return;
  try {
    const response = await fetch(route('business.settings.team.cancel', member.id), {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
      },
    });
    const data = await response.json();
    if (data.success) {
      teamMembers.value = teamMembers.value.filter(m => m.id !== member.id);
    }
  } catch (error) {
    console.error('Failed to cancel invite:', error);
  }
};

const removeMember = async (member) => {
  if (!confirm(`${member.name || member.email} ni jamoadan o'chirmoqchimisiz?`)) return;
  try {
    const response = await fetch(route('business.settings.team.remove', member.id), {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
      },
    });
    const data = await response.json();
    if (data.success) {
      teamMembers.value = teamMembers.value.filter(m => m.id !== member.id);
    }
  } catch (error) {
    console.error('Failed to remove member:', error);
  }
};

const openEditMemberModal = (member) => {
  editingMember.value = member;
  editForm.value = { department: member.department };
  editError.value = '';
  showEditModal.value = true;
};

const updateMember = async () => {
  if (!editingMember.value || isEditLoading.value) return;

  isEditLoading.value = true;
  editError.value = '';

  try {
    const response = await fetch(route('business.settings.team.update', editingMember.value.id), {
      method: 'PUT',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        department: editForm.value.department,
      }),
    });

    const data = await response.json();

    if (response.ok && data.success) {
      // Update local member data
      const index = teamMembers.value.findIndex(m => m.id === editingMember.value.id);
      if (index !== -1) {
        teamMembers.value[index] = {
          ...teamMembers.value[index],
          department: data.member.department,
          department_label: data.member.department_label,
        };
      }
      showEditModal.value = false;
    } else {
      editError.value = data.error || data.message || 'Xatolik yuz berdi';
    }
  } catch (error) {
    console.error('Failed to update member:', error);
    editError.value = 'Tarmoq xatosi';
  } finally {
    isEditLoading.value = false;
  }
};

// Watch for tab changes to load team data
watch(activeTab, (newTab) => {
  if (newTab === 'team' && teamMembers.value.length === 0) {
    fetchTeamMembers();
  }
});

// Department colors for badges
const departmentColors = {
  sales_head: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
  marketing: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
  sales_operator: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  hr: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
  finance: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
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
