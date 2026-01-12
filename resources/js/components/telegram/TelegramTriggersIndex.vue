<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <Link
          :href="getRoute('telegram-funnels.show', bot.id)"
          class="w-10 h-10 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl flex items-center justify-center transition-colors group"
        >
          <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </Link>
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Triggerlar</h1>
          <p class="text-gray-500 dark:text-gray-400 flex items-center gap-2">
            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
            @{{ bot.username }}
          </p>
        </div>
      </div>
      <button
        @click="openCreateModal"
        class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-sm font-semibold rounded-xl transition-all shadow-lg shadow-purple-500/30 hover:shadow-xl"
      >
        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Yangi Trigger
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ triggersList.length }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Jami triggerlar</p>
          </div>
        </div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ activeTriggers }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Faol triggerlar</p>
          </div>
        </div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ commandTriggers }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Buyruqlar</p>
          </div>
        </div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ keywordTriggers }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kalit so'zlar</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="triggersList.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="p-12 text-center">
        <div class="relative w-24 h-24 mx-auto mb-6">
          <div class="absolute inset-0 bg-purple-500/20 rounded-full animate-ping"></div>
          <div class="relative w-24 h-24 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-full flex items-center justify-center shadow-xl shadow-purple-500/30">
            <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
        </div>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Hozircha trigger yo'q</h3>
        <p class="text-gray-600 dark:text-gray-400 max-w-md mx-auto mb-6">
          Triggerlar foydalanuvchi xabarlariga avtomatik javob berish uchun ishlatiladi.
          Buyruqlar, kalit so'zlar va boshqa hodisalar uchun trigger yarating.
        </p>
        <button
          @click="openCreateModal"
          class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold rounded-xl transition-all shadow-lg shadow-purple-500/30"
        >
          <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Birinchi Triggerni Yaratish
        </button>
      </div>

      <!-- Tips -->
      <div class="grid grid-cols-1 md:grid-cols-3 border-t border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
          <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
            </svg>
          </div>
          <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Buyruqlar</h4>
          <p class="text-sm text-gray-500 dark:text-gray-400">/start, /help kabi buyruqlarga javob</p>
        </div>
        <div class="p-6 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
          <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
          </div>
          <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Kalit so'zlar</h4>
          <p class="text-sm text-gray-500 dark:text-gray-400">Ma'lum so'zlarni topib javob berish</p>
        </div>
        <div class="p-6">
          <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
            </svg>
          </div>
          <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Callback</h4>
          <p class="text-sm text-gray-500 dark:text-gray-400">Tugma bosilganda ishga tushish</p>
        </div>
      </div>
    </div>

    <!-- Triggers List -->
    <div v-else class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <!-- Test Input -->
      <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
        <div class="flex gap-3">
          <input
            v-model="testText"
            type="text"
            placeholder="Test uchun matn kiriting..."
            class="flex-1 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:text-white transition-all text-sm"
            @keyup.enter="testTrigger"
          />
          <button
            @click="testTrigger"
            :disabled="!testText || isTesting"
            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-xl transition-colors"
          >
            {{ isTesting ? 'Tekshirilmoqda...' : 'Test qilish' }}
          </button>
        </div>
        <div v-if="testResult" class="mt-3 p-3 rounded-lg" :class="testResult.matches?.length > 0 ? 'bg-green-50 dark:bg-green-900/20' : 'bg-amber-50 dark:bg-amber-900/20'">
          <p v-if="testResult.matches?.length > 0" class="text-sm text-green-700 dark:text-green-300">
            <strong>{{ testResult.matches.length }}</strong> ta trigger topildi! Ishga tushadigan: <strong>{{ testResult.will_trigger?.name }}</strong>
          </p>
          <p v-else class="text-sm text-amber-700 dark:text-amber-300">
            Mos trigger topilmadi
          </p>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 dark:border-gray-700">
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Trigger</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Turi</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Qiymati</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Funnel</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Holati</th>
              <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amallar</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="trigger in triggersList" :key="trigger.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', getTriggerColor(trigger.type)]">
                    <component :is="getTriggerIcon(trigger.type)" class="w-5 h-5" />
                  </div>
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ trigger.name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Prioritet: {{ trigger.priority }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="['inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium', getTriggerTypeBadge(trigger.type)]">
                  {{ getTriggerTypeLabel(trigger.type) }}
                </span>
              </td>
              <td class="px-6 py-4">
                <code class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-sm text-gray-800 dark:text-gray-200 font-mono">
                  {{ trigger.value }}
                </code>
                <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">{{ getMatchTypeLabel(trigger.match_type) }}</span>
              </td>
              <td class="px-6 py-4">
                <div v-if="trigger.funnel">
                  <p class="text-sm text-gray-900 dark:text-white">{{ trigger.funnel.name }}</p>
                  <p v-if="trigger.step" class="text-xs text-gray-500 dark:text-gray-400">{{ trigger.step.name }}</p>
                </div>
                <span v-else class="text-sm text-gray-400 dark:text-gray-500">-</span>
              </td>
              <td class="px-6 py-4">
                <button
                  @click="toggleTriggerActive(trigger)"
                  :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors', trigger.is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600']"
                >
                  <span :class="['inline-block h-4 w-4 transform rounded-full bg-white transition-transform', trigger.is_active ? 'translate-x-6' : 'translate-x-1']"></span>
                </button>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="openEditModal(trigger)"
                    class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                    title="Tahrirlash"
                  >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button
                    @click="deleteTrigger(trigger)"
                    class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                    title="O'chirish"
                  >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Create/Edit Modal -->
  <Teleport to="body">
    <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full shadow-2xl max-h-[90vh] overflow-y-auto">
          <!-- Modal Header -->
          <div class="sticky top-0 z-10 bg-white dark:bg-gray-800 p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ editingTrigger ? 'Triggerni Tahrirlash' : 'Yangi Trigger' }}
              </h3>
              <button
                @click="closeModal"
                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Modal Body -->
          <form @submit.prevent="saveTrigger" class="p-6 space-y-5">
            <!-- Name -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Nomi *</label>
              <input
                v-model="formData.name"
                type="text"
                required
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:text-white transition-all"
                placeholder="Masalan: /start buyrug'i"
              />
            </div>

            <!-- Type -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Turi *</label>
              <select
                v-model="formData.type"
                required
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:text-white transition-all"
              >
                <option value="command">Buyruq (Command)</option>
                <option value="keyword">Kalit so'z (Keyword)</option>
                <option value="text">Matn (Text)</option>
                <option value="callback">Callback</option>
                <option value="start_payload">/start payload</option>
                <option value="event">Hodisa (Event)</option>
              </select>
            </div>

            <!-- Value -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Qiymati *</label>
              <input
                v-model="formData.value"
                type="text"
                required
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:text-white transition-all font-mono"
                :placeholder="getValuePlaceholder()"
              />
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ getValueHint() }}</p>
            </div>

            <!-- Match Type -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Mos kelish turi</label>
              <select
                v-model="formData.match_type"
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:text-white transition-all"
              >
                <option value="exact">Aniq mos kelish</option>
                <option value="contains">O'z ichiga oladi</option>
                <option value="starts_with">...bilan boshlanadi</option>
                <option value="ends_with">...bilan tugaydi</option>
                <option value="regex">Regex</option>
                <option value="wildcard">Wildcard (*)</option>
              </select>
            </div>

            <!-- Funnel -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Funnel *</label>
              <select
                v-model="formData.funnel_id"
                required
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:text-white transition-all"
                @change="onFunnelChange"
              >
                <option value="">Funnel tanlang</option>
                <option v-for="funnel in funnelsList" :key="funnel.id" :value="funnel.id">
                  {{ funnel.name }}
                </option>
              </select>
            </div>

            <!-- Step -->
            <div v-if="selectedFunnelSteps.length > 0">
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Boshlang'ich qadam (ixtiyoriy)</label>
              <select
                v-model="formData.step_id"
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:text-white transition-all"
              >
                <option value="">Birinchi qadamdan boshlash</option>
                <option v-for="step in selectedFunnelSteps" :key="step.id" :value="step.id">
                  {{ step.name }}
                </option>
              </select>
            </div>

            <!-- Priority -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Prioritet</label>
              <input
                v-model.number="formData.priority"
                type="number"
                min="0"
                max="100"
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:text-white transition-all"
              />
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Yuqori prioritetli triggerlar birinchi tekshiriladi (0-100)</p>
            </div>

            <!-- Is Active -->
            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
              <div>
                <p class="font-medium text-gray-900 dark:text-white">Faollashtirish</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Trigger darhol ishlashni boshlaydi</p>
              </div>
              <button
                type="button"
                @click="formData.is_active = !formData.is_active"
                :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors', formData.is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600']"
              >
                <span :class="['inline-block h-4 w-4 transform rounded-full bg-white transition-transform', formData.is_active ? 'translate-x-6' : 'translate-x-1']"></span>
              </button>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-4">
              <button
                type="button"
                @click="closeModal"
                class="px-5 py-2.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium rounded-xl transition-colors"
              >
                Bekor qilish
              </button>
              <button
                type="submit"
                :disabled="isSaving"
                class="px-5 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 disabled:from-gray-400 disabled:to-gray-500 text-white font-semibold rounded-xl transition-all shadow-lg shadow-purple-500/30 disabled:shadow-none"
              >
                {{ isSaving ? 'Saqlanmoqda...' : (editingTrigger ? 'Saqlash' : 'Yaratish') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, reactive, computed, h } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  bot: Object,
  triggers: {
    type: Array,
    default: () => []
  },
  funnels: {
    type: Array,
    default: () => []
  },
  panelType: {
    type: String,
    required: true,
    validator: (value) => ['business', 'marketing'].includes(value),
  },
})

// Route helpers based on panel type
const getRoute = (name, params = null) => {
  const prefix = props.panelType === 'business' ? 'business.' : 'marketing.';
  if (Array.isArray(params)) {
    return route(prefix + name, params);
  }
  return params ? route(prefix + name, params) : route(prefix + name);
};

const triggersList = ref(props.triggers)
const funnelsList = ref(props.funnels)
const showModal = ref(false)
const editingTrigger = ref(null)
const isSaving = ref(false)
const testText = ref('')
const testResult = ref(null)
const isTesting = ref(false)

const formData = reactive({
  name: '',
  type: 'command',
  value: '',
  match_type: 'exact',
  funnel_id: '',
  step_id: '',
  priority: 0,
  is_active: true
})

const activeTriggers = computed(() => triggersList.value.filter(t => t.is_active).length)
const commandTriggers = computed(() => triggersList.value.filter(t => t.type === 'command').length)
const keywordTriggers = computed(() => triggersList.value.filter(t => t.type === 'keyword').length)

const selectedFunnelSteps = computed(() => {
  if (!formData.funnel_id) return []
  const funnel = funnelsList.value.find(f => f.id === formData.funnel_id)
  return funnel?.steps || []
})

const getTriggerColor = (type) => {
  const colors = {
    command: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
    keyword: 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
    text: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
    callback: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
    start_payload: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
    event: 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400'
  }
  return colors[type] || colors.text
}

const getTriggerIcon = (type) => {
  const icons = {
    command: {
      render() {
        return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
          h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z' })
        ])
      }
    },
    keyword: {
      render() {
        return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
          h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z' })
        ])
      }
    },
    callback: {
      render() {
        return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
          h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122' })
        ])
      }
    },
    default: {
      render() {
        return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
          h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M13 10V3L4 14h7v7l9-11h-7z' })
        ])
      }
    }
  }
  return icons[type] || icons.default
}

const getTriggerTypeBadge = (type) => {
  const badges = {
    command: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
    keyword: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
    text: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
    callback: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
    start_payload: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
    event: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
  }
  return badges[type] || badges.text
}

const getTriggerTypeLabel = (type) => {
  const labels = {
    command: 'Buyruq',
    keyword: 'Kalit so\'z',
    text: 'Matn',
    callback: 'Callback',
    start_payload: '/start payload',
    event: 'Hodisa'
  }
  return labels[type] || type
}

const getMatchTypeLabel = (matchType) => {
  const labels = {
    exact: 'aniq',
    contains: 'ichida',
    starts_with: 'boshlanadi',
    ends_with: 'tugaydi',
    regex: 'regex',
    wildcard: 'wildcard'
  }
  return labels[matchType] || matchType
}

const getValuePlaceholder = () => {
  const placeholders = {
    command: '/start',
    keyword: 'narx',
    text: 'salom',
    callback: 'btn_confirm',
    start_payload: 'ref123',
    event: 'new_chat_member'
  }
  return placeholders[formData.type] || 'Qiymat kiriting'
}

const getValueHint = () => {
  const hints = {
    command: 'Buyruq / bilan boshlanishi kerak',
    keyword: 'Xabarda qidiriladigan so\'z',
    text: 'Foydalanuvchi xabari matni',
    callback: 'Inline tugma callback_data qiymati',
    start_payload: '/start dan keyingi payload',
    event: 'Telegram hodisasi nomi'
  }
  return hints[formData.type] || ''
}

const onFunnelChange = () => {
  formData.step_id = ''
}

const openCreateModal = () => {
  editingTrigger.value = null
  formData.name = ''
  formData.type = 'command'
  formData.value = ''
  formData.match_type = 'exact'
  formData.funnel_id = ''
  formData.step_id = ''
  formData.priority = 0
  formData.is_active = true
  showModal.value = true
}

const openEditModal = (trigger) => {
  editingTrigger.value = trigger
  formData.name = trigger.name
  formData.type = trigger.type
  formData.value = trigger.value
  formData.match_type = trigger.match_type
  formData.funnel_id = trigger.funnel?.id || ''
  formData.step_id = trigger.step?.id || ''
  formData.priority = trigger.priority
  formData.is_active = trigger.is_active
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingTrigger.value = null
}

const saveTrigger = async () => {
  isSaving.value = true
  try {
    const url = editingTrigger.value
      ? getRoute('telegram-funnels.triggers.update', [props.bot.id, editingTrigger.value.id])
      : getRoute('telegram-funnels.triggers.store', props.bot.id)

    const response = await fetch(url, {
      method: editingTrigger.value ? 'PUT' : 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        name: formData.name,
        type: formData.type,
        value: formData.value,
        match_type: formData.match_type,
        funnel_id: formData.funnel_id,
        step_id: formData.step_id || null,
        priority: formData.priority,
        is_active: formData.is_active
      })
    })

    const data = await response.json()

    if (data.success) {
      closeModal()
      router.reload()
    } else {
      alert(data.message || 'Xatolik yuz berdi')
    }
  } catch (error) {
    console.error('Error saving trigger:', error)
    alert('Xatolik yuz berdi')
  } finally {
    isSaving.value = false
  }
}

const toggleTriggerActive = async (trigger) => {
  try {
    await fetch(getRoute('telegram-funnels.triggers.toggle-active', [props.bot.id, trigger.id]), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })
    trigger.is_active = !trigger.is_active
  } catch (error) {
    console.error('Error toggling trigger:', error)
  }
}

const deleteTrigger = async (trigger) => {
  if (!confirm(`"${trigger.name}" triggerini o'chirishni xohlaysizmi?`)) return

  try {
    await fetch(getRoute('telegram-funnels.triggers.destroy', [props.bot.id, trigger.id]), {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })
    router.reload()
  } catch (error) {
    console.error('Error deleting trigger:', error)
  }
}

const testTrigger = async () => {
  if (!testText.value) return

  isTesting.value = true
  testResult.value = null

  try {
    const response = await fetch(getRoute('telegram-funnels.triggers.test', props.bot.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({ text: testText.value })
    })

    testResult.value = await response.json()
  } catch (error) {
    console.error('Error testing trigger:', error)
  } finally {
    isTesting.value = false
  }
}
</script>
