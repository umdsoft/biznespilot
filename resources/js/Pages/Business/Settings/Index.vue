<template>
  <BusinessLayout title="Sozlamalar">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sozlamalar</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Hisob, xavfsizlik va tizim sozlamalari</p>
        </div>
        <div class="hidden sm:flex items-center gap-3 text-sm">
          <div class="w-8 h-8 bg-gray-900 dark:bg-gray-600 rounded-full flex items-center justify-center">
            <span class="text-white font-medium text-xs">{{ user.name.charAt(0).toUpperCase() }}</span>
          </div>
          <div>
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ user.name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ user.email }}</p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar -->
        <div class="lg:col-span-1">
          <nav class="space-y-0.5 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-1.5 sticky top-6">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                activeTab === tab.id
                  ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'
                  : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-gray-200',
                'group flex items-center w-full px-3 py-2 text-sm font-medium rounded-md transition-colors'
              ]"
            >
              <component
                :is="tab.icon"
                :class="[
                  activeTab === tab.id ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 dark:text-gray-500',
                  'mr-2.5 h-4 w-4 flex-shrink-0'
                ]"
              />
              <span>{{ tab.name }}</span>
            </button>
          </nav>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-3">
          <!-- Profile Tab -->
          <div v-show="activeTab === 'profile'" class="space-y-5">
            <!-- Profile Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
              <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Profil ma'lumotlari</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Shaxsiy ma'lumotlaringizni yangilang</p>
              </div>

              <form @submit.prevent="updateProfile" class="px-5 py-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Ism</label>
                    <input
                      v-model="profileForm.name"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 dark:text-gray-100 text-sm"
                      :class="{ 'border-red-400 focus:ring-red-500 focus:border-red-500': profileForm.errors.name }"
                      required
                    />
                    <p v-if="profileForm.errors.name" class="mt-1.5 text-sm text-red-600">{{ profileForm.errors.name }}</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                      <span class="flex items-center justify-between">
                        Login
                        <span class="text-xs text-gray-400 font-normal">O'zgartirib bo'lmaydi</span>
                      </span>
                    </label>
                    <input
                      v-model="user.login"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-400 cursor-not-allowed text-sm"
                      disabled
                    />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                    <input
                      v-model="profileForm.email"
                      type="email"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 dark:text-gray-100 text-sm"
                      :class="{ 'border-red-400 focus:ring-red-500 focus:border-red-500': profileForm.errors.email }"
                      required
                    />
                    <p v-if="profileForm.errors.email" class="mt-1.5 text-sm text-red-600">{{ profileForm.errors.email }}</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                      Telefon <span class="text-xs text-gray-400 font-normal">(Ixtiyoriy)</span>
                    </label>
                    <input
                      v-model="profileForm.phone"
                      type="tel"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 dark:text-gray-100 text-sm"
                      :class="{ 'border-red-400 focus:ring-red-500 focus:border-red-500': profileForm.errors.phone }"
                      placeholder="+998 XX XXX XX XX"
                    />
                    <p v-if="profileForm.errors.phone" class="mt-1.5 text-sm text-red-600">{{ profileForm.errors.phone }}</p>
                  </div>
                </div>

                <div class="mt-5 flex justify-end">
                  <button
                    type="submit"
                    :disabled="profileForm.processing"
                    class="px-5 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    {{ profileForm.processing ? 'Saqlanmoqda...' : 'Saqlash' }}
                  </button>
                </div>
              </form>
            </div>

            <!-- Change Password -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
              <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Parolni o'zgartirish</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kuchli parol bilan hisobingizni himoyalang</p>
              </div>

              <form @submit.prevent="updatePassword" class="px-5 py-5">
                <div class="space-y-4 max-w-md">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Joriy parol</label>
                    <input
                      v-model="passwordForm.current_password"
                      type="password"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 dark:text-gray-100 text-sm"
                      :class="{ 'border-red-400': passwordForm.errors.current_password }"
                      required
                    />
                    <p v-if="passwordForm.errors.current_password" class="mt-1.5 text-sm text-red-600">{{ passwordForm.errors.current_password }}</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Yangi parol</label>
                    <input
                      v-model="passwordForm.password"
                      type="password"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 dark:text-gray-100 text-sm"
                      :class="{ 'border-red-400': passwordForm.errors.password }"
                      required
                    />
                    <p v-if="passwordForm.errors.password" class="mt-1.5 text-sm text-red-600">{{ passwordForm.errors.password }}</p>
                    <p class="mt-1.5 text-xs text-gray-400">Kamida 8 ta belgi, harflar va raqamlar kombinatsiyasi</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Parolni tasdiqlash</label>
                    <input
                      v-model="passwordForm.password_confirmation"
                      type="password"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 dark:text-gray-100 text-sm"
                      required
                    />
                  </div>
                </div>

                <div class="mt-5 flex justify-end">
                  <button
                    type="submit"
                    :disabled="passwordForm.processing"
                    class="px-5 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    {{ passwordForm.processing ? 'Saqlanmoqda...' : 'Parolni o\'zgartirish' }}
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Preferences Tab -->
          <div v-show="activeTab === 'preferences'" class="space-y-5">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
              <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Bildirishnomalar va afzalliklar</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Bildirishnoma va interfeys sozlamalari</p>
              </div>

              <form @submit.prevent="updatePreferences" class="px-5 py-5">
                <div class="space-y-6">
                  <!-- Notifications -->
                  <div class="space-y-3">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Bildirishnomalar</h3>

                    <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700/50">
                      <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Email bildirishnomalar</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Muhim yangiliklarni email orqali qabul qiling</p>
                      </div>
                      <label class="relative inline-flex items-center cursor-pointer">
                        <input v-model="preferencesForm.email_notifications" type="checkbox" class="sr-only peer" />
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                      </label>
                    </div>

                    <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700/50">
                      <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Brauzer bildirishnomalar</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Real vaqt bildirishnomalarini yoqish</p>
                      </div>
                      <label class="relative inline-flex items-center cursor-pointer">
                        <input v-model="preferencesForm.browser_notifications" type="checkbox" class="sr-only peer" />
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                      </label>
                    </div>

                    <div class="flex items-center justify-between py-3">
                      <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Marketing xabarlari</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Yangi funksiyalar va takliflar haqida xabar olish</p>
                      </div>
                      <label class="relative inline-flex items-center cursor-pointer">
                        <input v-model="preferencesForm.marketing_emails" type="checkbox" class="sr-only peer" />
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                      </label>
                    </div>
                  </div>

                  <div class="border-t border-gray-200 dark:border-gray-700 pt-5">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">Interfeys</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Til</label>
                        <select v-model="preferencesForm.language" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 dark:text-gray-100 text-sm">
                          <option value="uz">O'zbekcha</option>
                          <option value="ru">Русский</option>
                          <option value="en">English</option>
                        </select>
                      </div>

                      <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Mavzu</label>
                        <select v-model="preferencesForm.theme" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 dark:text-gray-100 text-sm">
                          <option value="light">Yorug'</option>
                          <option value="dark">Qorong'i</option>
                          <option value="auto">Avtomatik</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="mt-5 flex justify-end border-t border-gray-200 dark:border-gray-700 pt-5">
                  <button
                    type="submit"
                    :disabled="preferencesForm.processing"
                    class="px-5 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    {{ preferencesForm.processing ? 'Saqlanmoqda...' : 'Saqlash' }}
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Team Tab -->
          <div v-show="activeTab === 'team'" class="space-y-5">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
              <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                  <h2 class="text-base font-semibold text-gray-900 dark:text-white">Jamoa a'zolari</h2>
                  <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Biznes jamoangizni boshqaring</p>
                </div>
                <button
                  @click="showInviteModal = true"
                  class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                  </svg>
                  Xodim qo'shish
                </button>
              </div>

              <!-- Team Members Table -->
              <div class="p-5">
                <!-- Loading -->
                <div v-if="isLoadingTeam" class="flex justify-center py-10">
                  <svg class="animate-spin h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                </div>

                <!-- Empty State -->
                <div v-else-if="teamMembers.length === 0" class="text-center py-10">
                  <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Hali jamoa a'zolari yo'q</p>
                  <button
                    @click="showInviteModal = true"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Xodim qo'shish
                  </button>
                </div>

                <!-- Members List -->
                <div v-else class="space-y-2">
                  <div
                    v-for="member in teamMembers"
                    :key="member.id"
                    :class="[
                      'flex items-center justify-between p-3 rounded-lg transition-colors',
                      member.is_owner
                        ? 'bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/40'
                        : 'bg-gray-50 dark:bg-gray-700/30 hover:bg-gray-100 dark:hover:bg-gray-700/50'
                    ]"
                  >
                    <div class="flex items-center gap-3">
                      <!-- Avatar -->
                      <div :class="[
                        'w-9 h-9 rounded-full flex items-center justify-center text-white font-medium text-sm',
                        member.is_owner ? 'bg-amber-500' : 'bg-gray-500 dark:bg-gray-600'
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
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
              <div class="flex gap-3">
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                  <p class="font-medium text-gray-700 dark:text-gray-300 mb-1">Jamoa a'zolari haqida</p>
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
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-md z-10 overflow-hidden border border-gray-200 dark:border-gray-700">
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
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                      <div class="w-9 h-9 bg-gray-500 dark:bg-gray-600 rounded-full flex items-center justify-center text-white font-medium text-sm">
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
                            'flex items-center justify-center gap-2 p-2.5 rounded-lg border transition-all text-sm font-medium',
                            editForm.department === value
                              ? 'border-blue-500 ' + (departmentColors[value] || 'bg-blue-50 text-blue-700')
                              : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 text-gray-700 dark:text-gray-300'
                          ]"
                        >
                          {{ label }}
                        </button>
                      </div>
                    </div>

                    <!-- Login (per-business unique) -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Login
                        <span class="text-xs text-gray-400 font-normal">(ixtiyoriy — biznesingizda unikal)</span>
                      </label>
                      <input
                        :value="editForm.login"
                        @input="onEditLoginInput"
                        type="text"
                        placeholder="masalan: manager, operator1"
                        autocomplete="off"
                        class="w-full px-3 py-2 bg-white dark:bg-gray-700 border rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 font-mono focus:ring-2 focus:ring-blue-500"
                        :class="editLoginError ? 'border-red-400' : 'border-gray-200 dark:border-gray-600'"
                      />
                      <p v-if="editLoginError" class="mt-1 text-xs text-red-500">{{ editLoginError }}</p>
                      <p v-else class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Bo'sh qoldirilsa xodim faqat telefon orqali kiradi.
                      </p>
                    </div>

                    <!-- Reset Password -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                      <button
                        v-if="!showResetPassword"
                        type="button"
                        @click="showResetPassword = true"
                        class="text-sm text-amber-600 hover:text-amber-700 font-medium flex items-center gap-1.5"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        Parolni o'zgartirish
                      </button>

                      <div v-else class="space-y-3">
                        <div class="flex items-center justify-between">
                          <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Yangi parol</label>
                          <button type="button" @click="showResetPassword = false; editForm.new_password = ''; editForm.new_password_confirmation = ''" class="text-xs text-gray-400 hover:text-gray-600">
                            Bekor qilish
                          </button>
                        </div>
                        <input
                          v-model="editForm.new_password"
                          type="password"
                          placeholder="Yangi parol (kamida 6 ta belgi)"
                          autocomplete="new-password"
                          class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                        />
                        <input
                          v-model="editForm.new_password_confirmation"
                          type="password"
                          placeholder="Parolni qayta kiriting"
                          autocomplete="new-password"
                          class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                        />
                        <p v-if="editForm.new_password && editForm.new_password.length < 6" class="text-xs text-red-500">Kamida 6 ta belgi</p>
                        <p v-if="editForm.new_password && editForm.new_password_confirmation && editForm.new_password !== editForm.new_password_confirmation" class="text-xs text-red-500">Parollar mos kelmayapti</p>
                      </div>
                    </div>

                    <!-- Error -->
                    <p v-if="editError" class="text-sm text-red-500">{{ editError }}</p>
                  </div>

                  <!-- Footer -->
                  <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    <button
                      @click="showEditModal = false"
                      class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 text-sm font-medium rounded-lg transition-colors"
                    >
                      Bekor qilish
                    </button>
                    <button
                      @click="updateMember"
                      :disabled="isEditLoading"
                      class="px-5 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors disabled:opacity-50"
                    >
                      {{ isEditLoading ? 'Saqlanmoqda...' : 'Saqlash' }}
                    </button>
                  </div>
                </div>
              </div>
            </Transition>
          </Teleport>

          <!-- Integrations Tab -->
          <div v-show="activeTab === 'integrations'">
            <IntegrationsContent />
          </div>

          <!-- OLD Messaging (replaced by IntegrationsContent) -->
          <div v-if="false" class="space-y-4">

            <!-- Messaging -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
              <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700/50 flex items-center gap-2.5">
                <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                  <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Messaging</h2>
                  <p class="text-[11px] text-gray-500 dark:text-gray-400">Ijtimoiy tarmoqlar va SMS xizmatlari</p>
                </div>
              </div>

              <div class="p-3">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2">
                  <!-- Instagram AI — Tez kunda -->
                  <div class="relative bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-600/30 rounded-lg p-3 opacity-60 cursor-not-allowed">
                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-300">Tez kunda</span>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 rounded-lg flex items-center justify-center mb-1.5">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Instagram AI</h3>
                      <p class="text-[10px] text-gray-400 dark:text-gray-500">DM & Story</p>
                    </div>
                  </div>

                  <!-- Eskiz SMS -->
                  <a :href="route('business.settings.sms') + '?provider=eskiz'" class="group bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-600/40 rounded-lg p-3 hover:border-teal-400 dark:hover:border-teal-500/50 hover:bg-teal-50/50 dark:hover:bg-teal-500/10 transition-all">
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-lg flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                        <span class="text-white font-black text-lg">E</span>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-900 dark:text-white group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors">Eskiz</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400">SMS gateway</p>
                    </div>
                  </a>

                  <!-- PlayMobile SMS -->
                  <a :href="route('business.settings.sms') + '?provider=playmobile'" class="group bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-600/40 rounded-lg p-3 hover:border-orange-400 dark:hover:border-orange-500/50 hover:bg-orange-50/50 dark:hover:bg-orange-500/10 transition-all">
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-amber-600 rounded-lg flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                        <span class="text-white font-black text-xs">PM</span>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-900 dark:text-white group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">PlayMobile</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400">SMS gateway</p>
                    </div>
                  </a>

                  <!-- Telegram Bot — Aktiv -->
                  <a :href="route('business.telegram-funnels.index')" class="group relative bg-white dark:bg-gray-800/50 border border-sky-200 dark:border-sky-500/30 rounded-lg p-3 hover:border-sky-400 dark:hover:border-sky-500/50 hover:bg-sky-50/50 dark:hover:bg-sky-500/10 transition-all">
                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] font-semibold bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400">Aktiv</span>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-sky-500 rounded-lg flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-900 dark:text-white group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">Telegram</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400">Bot & Kanal</p>
                    </div>
                  </a>

                  <!-- Facebook — Tez kunda -->
                  <div class="relative bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-600/30 rounded-lg p-3 opacity-60 cursor-not-allowed">
                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-300">Tez kunda</span>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-[#1877F2] rounded-lg flex items-center justify-center mb-1.5">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Facebook</h3>
                      <p class="text-[10px] text-gray-400 dark:text-gray-500">Messenger</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Reklama -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
              <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700/50 flex items-center gap-2.5">
                <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
                  <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Reklama</h2>
                  <p class="text-[11px] text-gray-500 dark:text-gray-400">Reklama kampaniyalari va analytics</p>
                </div>
              </div>

              <div class="p-3">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                  <!-- Google Ads — Tez kunda -->
                  <div class="relative bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-600/30 rounded-lg p-3 opacity-60 cursor-not-allowed">
                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-300">Tez kunda</span>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-white dark:bg-white rounded-lg flex items-center justify-center mb-1.5 border border-gray-100">
                        <svg class="w-6 h-6" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/><path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/><path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/><path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/></svg>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Google Ads</h3>
                      <p class="text-[10px] text-gray-400 dark:text-gray-500">Kampaniyalar</p>
                    </div>
                  </div>

                  <!-- Yandex Direct — Tez kunda -->
                  <div class="relative bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-600/30 rounded-lg p-3 opacity-60 cursor-not-allowed">
                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-300">Tez kunda</span>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-[#FC3F1D] rounded-lg flex items-center justify-center mb-1.5">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M14.5 5H12.2C9.6 5 8 6.8 8 9.1C8 10.9 8.9 12.2 10.5 13L8 19H10.5L12.8 13.4V19H15V5H14.5ZM12.8 11.5L11.5 10.8C10.6 10.4 10.2 9.8 10.2 9C10.2 7.9 10.9 7.2 12.1 7.2H12.8V11.5Z" fill="white"/></svg>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Yandex Direct</h3>
                      <p class="text-[10px] text-gray-400 dark:text-gray-500">Reklama</p>
                    </div>
                  </div>

                  <!-- Facebook Ads — Tez kunda -->
                  <div class="relative bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-600/30 rounded-lg p-3 opacity-60 cursor-not-allowed">
                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-300">Tez kunda</span>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-[#1877F2] rounded-lg flex items-center justify-center mb-1.5">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Facebook Ads</h3>
                      <p class="text-[10px] text-gray-400 dark:text-gray-500">Meta Ads</p>
                    </div>
                  </div>

                  <!-- Telegram Ads — Tez kunda (TikTok Ads o'rniga) -->
                  <div class="relative bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-600/30 rounded-lg p-3 opacity-60 cursor-not-allowed">
                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-300">Tez kunda</span>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-sky-500 rounded-lg flex items-center justify-center mb-1.5">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Telegram Ads</h3>
                      <p class="text-[10px] text-gray-400 dark:text-gray-500">Reklama</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Analytics & Media -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
              <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700/50 flex items-center gap-2.5">
                <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center">
                  <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Analytics & Media</h2>
                  <p class="text-[11px] text-gray-500 dark:text-gray-400">Statistika va kontent platformalari</p>
                </div>
              </div>

              <div class="p-3">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                  <!-- YouTube — Tez kunda -->
                  <div class="relative bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-600/30 rounded-lg p-3 opacity-60 cursor-not-allowed">
                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-300">Tez kunda</span>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-red-600 rounded-lg flex items-center justify-center mb-1.5">
                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">YouTube</h3>
                      <p class="text-[10px] text-gray-400 dark:text-gray-500">Kanal statistika</p>
                    </div>
                  </div>

                  <!-- GA4 — Tez kunda -->
                  <div class="relative bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-600/30 rounded-lg p-3 opacity-60 cursor-not-allowed">
                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-300">Tez kunda</span>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-yellow-500 rounded-lg flex items-center justify-center mb-1.5">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">GA4</h3>
                      <p class="text-[10px] text-gray-400 dark:text-gray-500">Sayt analytics</p>
                    </div>
                  </div>

                  <!-- Metrika — Tez kunda -->
                  <div class="relative bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-600/30 rounded-lg p-3 opacity-60 cursor-not-allowed">
                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-300">Tez kunda</span>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-[#FC3F1D] rounded-lg flex items-center justify-center mb-1.5">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M14.5 5H12.2C9.6 5 8 6.8 8 9.1C8 10.9 8.9 12.2 10.5 13L8 19H10.5L12.8 13.4V19H15V5H14.5ZM12.8 11.5L11.5 10.8C10.6 10.4 10.2 9.8 10.2 9C10.2 7.9 10.9 7.2 12.1 7.2H12.8V11.5Z" fill="white"/></svg>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Metrika</h3>
                      <p class="text-[10px] text-gray-400 dark:text-gray-500">Yandex</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Telefon & CRM -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
              <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700/50 flex items-center gap-2.5">
                <div class="w-8 h-8 bg-violet-500 rounded-lg flex items-center justify-center">
                  <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Telefon & CRM</h2>
                  <p class="text-[11px] text-gray-500 dark:text-gray-400">VoIP telefoniya va CRM integratsiyalar</p>
                </div>
              </div>

              <div class="p-3">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2">
                  <!-- SipUNI — Tez kunda -->
                  <div class="relative bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-600/30 rounded-lg p-3 opacity-60 cursor-not-allowed">
                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-300">Tez kunda</span>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-violet-600 rounded-lg flex items-center justify-center mb-1.5">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">SipUni</h3>
                      <p class="text-[10px] text-gray-400 dark:text-gray-500">IP-telefoniya</p>
                    </div>
                  </div>

                  <!-- PBX — Aktiv -->
                  <a :href="route('integrations.telephony.settings') + '?provider=pbx'" class="group bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-600/40 rounded-lg p-3 hover:border-blue-400 dark:hover:border-blue-500/50 hover:bg-blue-50/50 dark:hover:bg-blue-500/10 transition-all">
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">PBX</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400">Telefoniya</p>
                    </div>
                  </a>

                  <!-- MoiZvonki — Tez kunda -->
                  <div class="relative bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-600/30 rounded-lg p-3 opacity-60 cursor-not-allowed">
                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-300">Tez kunda</span>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg flex items-center justify-center mb-1.5">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">MoiZvonki</h3>
                      <p class="text-[10px] text-gray-400 dark:text-gray-500">Android</p>
                    </div>
                  </div>

                  <!-- UTEL — Aktiv -->
                  <a :href="route('integrations.telephony.settings') + '?provider=utel'" class="group bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-600/40 rounded-lg p-3 hover:border-green-400 dark:hover:border-green-500/50 hover:bg-green-50/50 dark:hover:bg-green-500/10 transition-all">
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-teal-600 rounded-lg flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                        <span class="text-white font-bold text-xs">UZ</span>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">UTEL</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400">O'zbekiston VoIP</p>
                    </div>
                  </a>

                  <!-- Bitrix24 — Tez kunda -->
                  <div class="relative bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-600/30 rounded-lg p-3 opacity-60 cursor-not-allowed">
                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-300">Tez kunda</span>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-lg flex items-center justify-center mb-1.5">
                        <span class="text-white font-bold text-xs">B24</span>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Bitrix24</h3>
                      <p class="text-[10px] text-gray-400 dark:text-gray-500">CRM sync</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Hisobot & Buxgalteriya -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
              <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700/50 flex items-center gap-2.5">
                <div class="w-8 h-8 bg-amber-500 rounded-lg flex items-center justify-center">
                  <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Hisobot & Buxgalteriya</h2>
                  <p class="text-[11px] text-gray-500 dark:text-gray-400">Hisob-kitob va moliyaviy integratsiyalar</p>
                </div>
              </div>

              <div class="p-3">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                  <!-- 1C — Tez kunda -->
                  <div class="relative bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-600/30 rounded-lg p-3 opacity-60 cursor-not-allowed">
                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-300">Tez kunda</span>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-amber-600 rounded-lg flex items-center justify-center mb-1.5">
                        <span class="text-white font-bold text-sm">1C</span>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">1C</h3>
                      <p class="text-[10px] text-gray-400 dark:text-gray-500">Buxgalteriya</p>
                    </div>
                  </div>

                  <!-- MySoliq — Tez kunda -->
                  <div class="relative bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-600/30 rounded-lg p-3 opacity-60 cursor-not-allowed">
                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] font-semibold bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-300">Tez kunda</span>
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center mb-1.5">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">MySoliq</h3>
                      <p class="text-[10px] text-gray-400 dark:text-gray-500">Soliq hisoboti</p>
                    </div>
                  </div>

                  <!-- Click -->
                  <a :href="route('business.settings.payments') + '?tab=click'" class="group bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-600/40 rounded-lg p-3 hover:border-blue-400 dark:hover:border-blue-500/50 hover:bg-blue-50/50 dark:hover:bg-blue-500/10 transition-all">
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Click</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400">To'lov</p>
                    </div>
                  </a>

                  <!-- Payme -->
                  <a :href="route('business.settings.payments') + '?tab=payme'" class="group bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-600/40 rounded-lg p-3 hover:border-cyan-400 dark:hover:border-cyan-500/50 hover:bg-cyan-50/50 dark:hover:bg-cyan-500/10 transition-all">
                    <div class="flex flex-col items-center text-center">
                      <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-teal-600 rounded-lg flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                      </div>
                      <h3 class="text-[11px] font-semibold text-gray-900 dark:text-white group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">Payme</h3>
                      <p class="text-[10px] text-gray-500 dark:text-gray-400">To'lov</p>
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
import IntegrationsContent from '@/components/IntegrationsContent.vue';
import { useI18n } from '@/i18n';
import { useConfirm } from '@/composables/useConfirm';

const { t } = useI18n();
const { confirm } = useConfirm();

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
  const oldLang = props.settings.language;
  preferencesForm.put(route('business.settings.preferences.update'), {
    preserveScroll: true,
    onSuccess: () => {
      applyTheme(preferencesForm.theme);
      // Til o'zgargan bo'lsa — sahifani qayta yuklash (cookie'dan yangi locale o'qiladi)
      if (preferencesForm.language && preferencesForm.language !== oldLang) {
        setTimeout(() => window.location.reload(), 300);
      }
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

const deleteKey = async (keyType) => {
  if (await confirm({ title: 'API kalitini o\'chirish', message: 'Haqiqatan ham bu API kalitini o\'chirmoqchimisiz?', type: 'danger', confirmText: 'O\'chirish' })) {
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
const showResetPassword = ref(false);
const editForm = ref({
  department: '',
  login: '',
  new_password: '',
  new_password_confirmation: '',
});
const editError = ref('');
const isEditLoading = ref(false);

// Login validation (edit modal) — faqat lotin harf, raqam, _
const editLoginError = computed(() => {
  const v = (editForm.value.login || '').trim();
  if (!v) return ''; // optional
  if (v.length < 3) return "Login kamida 3 ta belgi bo'lishi kerak";
  if (!/^[a-zA-Z0-9_]+$/.test(v)) return "Faqat lotin harf, raqam va _";
  return '';
});

const onEditLoginInput = (e) => {
  editForm.value.login = (e.target.value || '').toLowerCase().replace(/[^a-z0-9_]/g, '').slice(0, 50);
};

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
  if (!await confirm({ title: 'Taklifni bekor qilish', message: 'Taklifni bekor qilmoqchimisiz?', type: 'warning', confirmText: 'Bekor qilish' })) return;
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
  if (!await confirm({ title: 'A\'zoni o\'chirish', message: `${member.name || member.email} ni jamoadan o'chirmoqchimisiz?`, type: 'danger', confirmText: 'O\'chirish' })) return;
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
  editForm.value = {
    department: member.department,
    login: member.login || '',
    new_password: '',
    new_password_confirmation: '',
  };
  showResetPassword.value = false;
  editError.value = '';
  showEditModal.value = true;
};

const updateMember = async () => {
  if (!editingMember.value || isEditLoading.value) return;

  // Login error?
  if (editLoginError.value) {
    editError.value = editLoginError.value;
    return;
  }

  // Parol o'zgartirilayotgan bo'lsa — validatsiya
  const wantsPasswordChange = showResetPassword.value && editForm.value.new_password.length > 0;
  if (wantsPasswordChange) {
    if (editForm.value.new_password.length < 6) {
      editError.value = "Yangi parol kamida 6 ta belgi bo'lishi kerak";
      return;
    }
    if (editForm.value.new_password !== editForm.value.new_password_confirmation) {
      editError.value = "Parollar mos kelmayapti";
      return;
    }
  }

  isEditLoading.value = true;
  editError.value = '';

  try {
    // 1. Department + Login update
    const updatePayload = {
      department: editForm.value.department,
      login: editForm.value.login || null,
    };

    const updateResp = await fetch(route('business.settings.team.update', editingMember.value.id), {
      method: 'PUT',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(updatePayload),
    });

    const updateData = await updateResp.json();

    if (!updateResp.ok || !updateData.success) {
      const fieldErr = updateData.errors?.login?.[0];
      editError.value = fieldErr || updateData.error || updateData.message || 'Xatolik yuz berdi';
      isEditLoading.value = false;
      return;
    }

    // 2. Parol o'zgartirilishi kerak bo'lsa — alohida endpoint
    if (wantsPasswordChange) {
      const pwdResp = await fetch(route('business.settings.team.reset-password', editingMember.value.id), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          password: editForm.value.new_password,
          password_confirmation: editForm.value.new_password_confirmation,
        }),
      });

      const pwdData = await pwdResp.json();
      if (!pwdResp.ok || !pwdData.success) {
        editError.value = pwdData.error || pwdData.message || 'Parolni saqlashda xatolik';
        isEditLoading.value = false;
        return;
      }
    }

    // Local state ni yangilash
    const index = teamMembers.value.findIndex(m => m.id === editingMember.value.id);
    if (index !== -1) {
      teamMembers.value[index] = {
        ...teamMembers.value[index],
        department: updateData.member.department,
        department_label: updateData.member.department_label,
        login: updateData.member.login,
      };
    }
    showEditModal.value = false;
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
