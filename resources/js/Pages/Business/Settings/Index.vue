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


          <!-- Integrations Tab -->
          <div v-show="activeTab === 'integrations'" class="space-y-6">
            <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl overflow-hidden">
              <div class="px-6 py-5 border-b border-slate-700/50 bg-gradient-to-r from-cyan-500/10 via-blue-500/10 to-indigo-500/10">
                <div class="flex items-center">
                  <div class="w-14 h-14 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-2xl flex items-center justify-center mr-4 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-xl font-bold text-white">Messaging Platformalar</h2>
                    <p class="text-sm text-slate-400 mt-0.5">Ijtimoiy tarmoqlar va messaging platformalar bilan AI integratsiyalarini sozlang</p>
                  </div>
                </div>
              </div>

              <div class="px-6 py-6">
                <div class="grid grid-cols-1 gap-5">
                  <!-- Instagram AI -->
                  <a
                    :href="route('business.settings.instagram-ai')"
                    class="group relative overflow-hidden bg-gradient-to-br from-pink-500/10 via-purple-500/10 to-orange-500/10 border border-pink-500/30 rounded-2xl p-6 hover:border-pink-400 hover:bg-pink-500/20 transition-all duration-300 transform hover:-translate-y-1"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex items-center flex-1">
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 rounded-2xl flex items-center justify-center mr-5 group-hover:scale-110 transition-transform shadow-lg">
                          <svg class="w-9 h-9 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                          </svg>
                        </div>
                        <div>
                          <h3 class="text-xl font-bold text-white group-hover:text-pink-400 transition-colors">Instagram AI</h3>
                          <p class="text-sm text-slate-400 mt-1.5">AI-powered Instagram DM va Story reply automation</p>
                          <div class="flex items-center gap-2 mt-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-pink-500/20 text-pink-400 border border-pink-500/30">DM Bot</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-purple-500/20 text-purple-400 border border-purple-500/30">Story Reply</span>
                          </div>
                        </div>
                      </div>
                      <svg class="w-7 h-7 text-pink-400 group-hover:translate-x-2 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                      </svg>
                    </div>
                  </a>

                </div>
              </div>
            </div>

            <!-- Reklama Platformalar -->
            <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl overflow-hidden">
              <div class="px-6 py-5 border-b border-slate-700/50 bg-gradient-to-r from-blue-500/10 via-green-500/10 to-yellow-500/10">
                <div class="flex items-center">
                  <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-green-600 rounded-2xl flex items-center justify-center mr-4 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-xl font-bold text-white">Reklama Platformalar</h2>
                    <p class="text-sm text-slate-400 mt-0.5">Reklama kampaniyalarini boshqarish va analytics</p>
                  </div>
                </div>
              </div>

              <div class="px-6 py-6">
                <div class="grid grid-cols-1 gap-5">
                  <!-- Google Ads -->
                  <a
                    :href="route('business.settings.google-ads')"
                    class="group relative overflow-hidden bg-gradient-to-br from-blue-500/10 via-red-500/10 to-yellow-500/10 border border-blue-500/30 rounded-2xl p-6 hover:border-blue-400 hover:bg-blue-500/20 transition-all duration-300 transform hover:-translate-y-1"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex items-center flex-1">
                        <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mr-5 group-hover:scale-110 transition-transform shadow-lg">
                          <svg class="w-10 h-10" viewBox="0 0 24 24" fill="none">
                            <path d="M12.545 10.239v3.821h5.445c-.712 2.315-2.647 3.972-5.445 3.972a6.033 6.033 0 110-12.064c1.498 0 2.866.549 3.921 1.453l2.814-2.814A9.969 9.969 0 0012.545 2C7.021 2 2.543 6.477 2.543 12s4.478 10 10.002 10c8.396 0 10.249-7.85 9.426-11.748l-9.426-.013z" fill="#4285F4"/>
                            <path d="M12.545 10.239v3.821h5.445c-.712 2.315-2.647 3.972-5.445 3.972a6.033 6.033 0 01-5.195-2.987l-3.086 2.38A9.969 9.969 0 0012.545 22c5.524 0 10.002-4.477 10.002-10 0-.67-.069-1.325-.199-1.961l-9.803.2z" fill="#34A853"/>
                            <path d="M7.35 14.045a5.97 5.97 0 01-.307-1.888c0-.67.111-1.313.307-1.917L4.263 7.86A9.969 9.969 0 002.543 12c0 1.613.39 3.134 1.082 4.476l3.725-2.431z" fill="#FBBC05"/>
                            <path d="M12.545 5.969c1.665 0 3.157.573 4.334 1.696l2.814-2.814A9.969 9.969 0 0012.545 2a9.969 9.969 0 00-8.282 4.36l3.086 2.38a5.97 5.97 0 015.196-2.771z" fill="#EA4335"/>
                          </svg>
                        </div>
                        <div>
                          <h3 class="text-xl font-bold text-white group-hover:text-blue-400 transition-colors">Google Ads</h3>
                          <p class="text-sm text-slate-400 mt-1.5">Google reklama kampaniyalari va analytics</p>
                          <div class="flex items-center gap-2 mt-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-500/20 text-blue-400 border border-blue-500/30">Kampaniyalar</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-green-500/20 text-green-400 border border-green-500/30">Analytics</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-yellow-500/20 text-yellow-400 border border-yellow-500/30">Conversions</span>
                          </div>
                        </div>
                      </div>
                      <svg class="w-7 h-7 text-blue-400 group-hover:translate-x-2 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                      </svg>
                    </div>
                  </a>

                  <!-- Yandex Direct -->
                  <a
                    :href="route('business.settings.yandex-direct')"
                    class="group relative overflow-hidden bg-gradient-to-br from-red-500/10 via-yellow-500/10 to-orange-500/10 border border-red-500/30 rounded-2xl p-6 hover:border-red-400 hover:bg-red-500/20 transition-all duration-300 transform hover:-translate-y-1"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex items-center flex-1">
                        <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mr-5 group-hover:scale-110 transition-transform shadow-lg">
                          <svg class="w-10 h-10" viewBox="0 0 24 24" fill="none">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" fill="#FC3F1D"/>
                            <path d="M13.5 7h-2.1c-1.6 0-2.9 1.3-2.9 2.9 0 1.1.6 2.1 1.6 2.6l2.5 1.4v3.6h1.9v-4.9l-2.8-1.5c-.5-.3-.8-.8-.8-1.3 0-.8.7-1.5 1.5-1.5h1.1V7z" fill="white"/>
                          </svg>
                        </div>
                        <div>
                          <h3 class="text-xl font-bold text-white group-hover:text-red-400 transition-colors">Yandex Direct</h3>
                          <p class="text-sm text-slate-400 mt-1.5">Yandex reklama kampaniyalari va statistika</p>
                          <div class="flex items-center gap-2 mt-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-red-500/20 text-red-400 border border-red-500/30">Kampaniyalar</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-orange-500/20 text-orange-400 border border-orange-500/30">Statistika</span>
                          </div>
                        </div>
                      </div>
                      <svg class="w-7 h-7 text-red-400 group-hover:translate-x-2 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                      </svg>
                    </div>
                  </a>

                  <!-- YouTube Analytics -->
                  <a
                    :href="route('business.settings.youtube')"
                    class="group relative overflow-hidden bg-gradient-to-br from-red-600/10 via-red-500/10 to-red-400/10 border border-red-500/30 rounded-2xl p-6 hover:border-red-400 hover:bg-red-500/20 transition-all duration-300 transform hover:-translate-y-1"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex items-center flex-1">
                        <div class="w-16 h-16 bg-red-600 rounded-2xl flex items-center justify-center mr-5 group-hover:scale-110 transition-transform shadow-lg">
                          <svg class="w-10 h-10 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                          </svg>
                        </div>
                        <div>
                          <h3 class="text-xl font-bold text-white group-hover:text-red-400 transition-colors">YouTube Analytics</h3>
                          <p class="text-sm text-slate-400 mt-1.5">YouTube kanal statistikasi va video analytics</p>
                          <div class="flex items-center gap-2 mt-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-red-500/20 text-red-400 border border-red-500/30">Views</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-pink-500/20 text-pink-400 border border-pink-500/30">Subscribers</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-purple-500/20 text-purple-400 border border-purple-500/30">Revenue</span>
                          </div>
                        </div>
                      </div>
                      <svg class="w-7 h-7 text-red-400 group-hover:translate-x-2 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                      </svg>
                    </div>
                  </a>

                </div>
              </div>
            </div>

            <!-- Coming Soon -->
            <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl overflow-hidden">
              <div class="px-6 py-5 border-b border-slate-700/50 bg-gradient-to-r from-slate-500/10 via-gray-500/10 to-zinc-500/10">
                <div class="flex items-center">
                  <div class="w-14 h-14 bg-gradient-to-br from-gray-400 to-slate-500 rounded-2xl flex items-center justify-center mr-4 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-xl font-bold text-white">Tez Kunda</h2>
                    <p class="text-sm text-slate-400 mt-0.5">Qo'shimcha integratsiyalar ustida ishlamoqdamiz</p>
                  </div>
                </div>
              </div>

              <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                  <div class="relative overflow-hidden bg-slate-700/30 border border-slate-600/50 rounded-xl p-6 opacity-70">
                    <div class="flex items-center">
                      <div class="w-14 h-14 bg-slate-600/50 rounded-xl flex items-center justify-center mr-4">
                        <span class="text-3xl">✈️</span>
                      </div>
                      <div>
                        <h3 class="font-bold text-slate-300 text-lg">Telegram Bot</h3>
                        <p class="text-xs text-slate-400 mt-1">AI chat bot va kanallar uchun</p>
                        <span class="inline-block mt-2.5 text-xs px-3 py-1 bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 rounded-full font-semibold">Tez kunda</span>
                      </div>
                    </div>
                  </div>

                  <div class="relative overflow-hidden bg-slate-700/30 border border-slate-600/50 rounded-xl p-6 opacity-70">
                    <div class="flex items-center">
                      <div class="w-14 h-14 bg-slate-600/50 rounded-xl flex items-center justify-center mr-4">
                        <span class="text-3xl">👥</span>
                      </div>
                      <div>
                        <h3 class="font-bold text-slate-300 text-lg">Facebook Messenger</h3>
                        <p class="text-xs text-slate-400 mt-1">Messenger bot va DM automation</p>
                        <span class="inline-block mt-2.5 text-xs px-3 py-1 bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 rounded-full font-semibold">Tez kunda</span>
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

const LinkIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1' })
]);

const tabs = [
  { id: 'profile', name: 'Profil', icon: UserIcon },
  { id: 'preferences', name: 'Afzalliklar', icon: BellIcon },
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
