<template>
  <SalesHeadLayout title="KPI Sozlamalari">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">KPI Sozlamalari</h2>
          <p class="mt-2 text-gray-600 dark:text-gray-400">KPI, Bonus va Jarima qoidalarini sozlash</p>
        </div>
        <button @click="openCreateModal('kpi')"
                class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Yangi KPI qo'shish
        </button>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
      <nav class="flex gap-8">
        <button @click="activeTab = 'kpi'"
                :class="['pb-4 px-1 font-medium text-sm border-b-2 transition-colors',
                         activeTab === 'kpi' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
          KPI Sozlamalari
        </button>
        <button @click="activeTab = 'bonus'"
                :class="['pb-4 px-1 font-medium text-sm border-b-2 transition-colors',
                         activeTab === 'bonus' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
          Bonus Sozlamalari
        </button>
        <button @click="activeTab = 'penalty'"
                :class="['pb-4 px-1 font-medium text-sm border-b-2 transition-colors',
                         activeTab === 'penalty' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
          Jarima Qoidalari
        </button>
      </nav>
    </div>

    <!-- KPI Settings Tab -->
    <div v-if="activeTab === 'kpi'" class="space-y-6">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Nomi</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Turi</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Maqsad</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Vazn</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Holat</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="kpi in kpiSettings" :key="kpi.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-6 py-4">
                  <div class="font-medium text-gray-900 dark:text-white">{{ kpi.name }}</div>
                  <div class="text-sm text-gray-500">{{ kpi.description }}</div>
                </td>
                <td class="px-6 py-4">
                  <span :class="getKpiTypeBadge(kpi.kpi_type)">{{ getKpiTypeLabel(kpi.kpi_type) }}</span>
                </td>
                <td class="px-6 py-4 text-center font-medium">
                  {{ kpi.target_min }} {{ kpi.measurement_unit }}
                </td>
                <td class="px-6 py-4 text-center">
                  <span class="text-emerald-600 font-medium">{{ kpi.weight }}%</span>
                </td>
                <td class="px-6 py-4 text-center">
                  <span :class="kpi.is_active ? 'text-green-600' : 'text-gray-400'">
                    {{ kpi.is_active ? 'Faol' : 'Nofaol' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-center">
                  <div class="flex items-center justify-center gap-2">
                    <button @click="editKpi(kpi)" class="p-1 text-blue-600 hover:bg-blue-50 rounded">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                      </svg>
                    </button>
                    <button @click="confirmDelete('kpi', kpi)" class="p-1 text-red-600 hover:bg-red-50 rounded">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="kpiSettings.length === 0">
                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                  KPI sozlamalari topilmadi. Yangi KPI qo'shing yoki shablon tanlang.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Bonus Settings Tab -->
    <div v-if="activeTab === 'bonus'" class="space-y-6">
      <div class="flex justify-end mb-4">
        <button @click="openCreateModal('bonus')"
                class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Yangi bonus qoidasi
        </button>
      </div>
      <div class="grid gap-4">
        <div v-for="bonus in bonusSettings" :key="bonus.id"
             class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div>
              <h3 class="font-bold text-gray-900 dark:text-white">{{ bonus.name }}</h3>
              <p class="text-sm text-gray-500 mt-1">{{ bonus.description }}</p>
              <div class="mt-3 flex flex-wrap gap-2">
                <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-sm">
                  Min KPI: {{ bonus.min_kpi_score }}%
                </span>
                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-sm">
                  Summa: {{ formatCurrency(bonus.base_amount) }}
                </span>
                <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-sm">
                  Multiplier: {{ bonus.multiplier }}x
                </span>
              </div>
            </div>
            <div class="flex gap-2">
              <button @click="editBonus(bonus)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
              </button>
              <button @click="confirmDelete('bonus', bonus)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </div>
          </div>
        </div>
        <div v-if="bonusSettings.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center text-gray-500 border border-gray-200 dark:border-gray-700">
          Bonus qoidalari topilmadi.
        </div>
      </div>
    </div>

    <!-- Penalty Rules Tab -->
    <div v-if="activeTab === 'penalty'" class="space-y-6">
      <div class="flex justify-end mb-4">
        <button @click="openCreateModal('penalty')"
                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Yangi jarima qoidasi
        </button>
      </div>
      <div class="grid gap-4">
        <div v-for="rule in penaltyRules" :key="rule.id"
             class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div>
              <div class="flex items-center gap-2">
                <h3 class="font-bold text-gray-900 dark:text-white">{{ rule.name }}</h3>
                <span v-if="rule.trigger_type === 'auto'" class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded text-xs">Avtomatik</span>
                <span v-if="!rule.is_active" class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-xs">Nofaol</span>
              </div>
              <p class="text-sm text-gray-500 mt-1">{{ rule.description }}</p>
              <div class="mt-3 flex flex-wrap gap-2">
                <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-sm">
                  {{ getPenaltyAmountLabel(rule) }}
                </span>
                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-sm">
                  {{ getCategoryLabel(rule.category) }}
                </span>
                <span v-if="rule.warning_before_penalty" class="px-2 py-1 bg-orange-100 text-orange-700 rounded text-sm">
                  {{ rule.warnings_before_penalty }} marta ogohlantirish
                </span>
                <span v-if="rule.allow_appeal" class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-sm">
                  Shikoyat mumkin
                </span>
              </div>
            </div>
            <div class="flex gap-2">
              <button @click="editPenalty(rule)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
              </button>
              <button @click="confirmDelete('penalty', rule)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </div>
          </div>
        </div>
        <div v-if="penaltyRules.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center text-gray-500 border border-gray-200 dark:border-gray-700">
          Jarima qoidalari topilmadi.
        </div>
      </div>
    </div>

    <!-- KPI Create/Edit Modal -->
    <div v-if="kpiModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
          {{ editingKpi ? 'KPI tahrirlash' : 'Yangi KPI qo\'shish' }}
        </h3>
        <form @submit.prevent="saveKpi" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nomi</label>
            <input v-model="kpiForm.name" type="text" required
                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tavsif</label>
            <textarea v-model="kpiForm.description" rows="2"
                      class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">KPI turi</label>
              <select v-model="kpiForm.kpi_type" required
                      class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
                <option value="leads_converted">Sotuvga o'tgan lidlar</option>
                <option value="revenue">Umumiy sotuv</option>
                <option value="deals_count">Deallar soni</option>
                <option value="calls_made">Qo'ng'iroqlar</option>
                <option value="tasks_completed">Vazifalar</option>
                <option value="conversion_rate">Konversiya %</option>
                <option value="response_time">Javob vaqti</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Davr</label>
              <select v-model="kpiForm.period_type" required
                      class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
                <option value="daily">Kunlik</option>
                <option value="weekly">Haftalik</option>
                <option value="monthly">Oylik</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Maqsad</label>
              <input v-model.number="kpiForm.target_min" type="number" required
                     class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Birlik</label>
              <input v-model="kpiForm.measurement_unit" type="text" placeholder="ta, so'm, %"
                     class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vazn (%)</label>
              <input v-model.number="kpiForm.weight" type="number" min="1" max="100" required
                     class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
            </div>
          </div>
          <div class="flex items-center gap-2">
            <input v-model="kpiForm.is_active" type="checkbox" id="is_active" class="rounded">
            <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">Faol</label>
          </div>
          <div class="flex justify-end gap-3 pt-4">
            <button type="button" @click="kpiModal = false"
                    class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
              Bekor qilish
            </button>
            <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
              Saqlash
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Bonus Create/Edit Modal -->
    <div v-if="bonusModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
          {{ editingBonus ? 'Bonus tahrirlash' : 'Yangi bonus qoidasi' }}
        </h3>
        <form @submit.prevent="saveBonus" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nomi</label>
            <input v-model="bonusForm.name" type="text" required
                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tavsif</label>
            <textarea v-model="bonusForm.description" rows="2"
                      class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bonus turi</label>
              <select v-model="bonusForm.bonus_type" required
                      class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
                <option value="fixed">Belgilangan summa</option>
                <option value="revenue_percentage">Daromad foizi</option>
                <option value="kpi_based">KPI asosida</option>
                <option value="tiered">Bosqichli</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hisoblash davri</label>
              <select v-model="bonusForm.calculation_period" required
                      class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
                <option value="monthly">Oylik</option>
                <option value="quarterly">Choraklik</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Asosiy summa</label>
              <input v-model.number="bonusForm.base_amount" type="number" min="0"
                     class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Minimal KPI (%)</label>
              <input v-model.number="bonusForm.min_kpi_score" type="number" min="0" max="100"
                     class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Minimal ish kunlari</label>
              <input v-model.number="bonusForm.min_working_days" type="number" min="0"
                     class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
            </div>
            <div class="flex items-end gap-4">
              <label class="flex items-center gap-2">
                <input v-model="bonusForm.requires_approval" type="checkbox" class="rounded">
                <span class="text-sm text-gray-700 dark:text-gray-300">Tasdiqlash kerak</span>
              </label>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <input v-model="bonusForm.is_active" type="checkbox" id="bonus_is_active" class="rounded">
            <label for="bonus_is_active" class="text-sm text-gray-700 dark:text-gray-300">Faol</label>
          </div>
          <div class="flex justify-end gap-3 pt-4">
            <button type="button" @click="bonusModal = false"
                    class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
              Bekor qilish
            </button>
            <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
              Saqlash
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Penalty Create/Edit Modal -->
    <div v-if="penaltyModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
          {{ editingPenalty ? 'Jarima tahrirlash' : 'Yangi jarima qoidasi' }}
        </h3>
        <form @submit.prevent="savePenalty" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nomi</label>
            <input v-model="penaltyForm.name" type="text" required
                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tavsif</label>
            <textarea v-model="penaltyForm.description" rows="2"
                      class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategoriya</label>
              <select v-model="penaltyForm.category" required
                      class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
                <option value="crm_discipline">CRM intizomi</option>
                <option value="performance">Samaradorlik</option>
                <option value="attendance">Davomat</option>
                <option value="customer_service">Mijozlarga xizmat</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jarima turi</label>
              <select v-model="penaltyForm.penalty_type" required
                      class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
                <option value="fixed">Belgilangan summa</option>
                <option value="percentage_of_bonus">Bonus foizi</option>
                <option value="warning_only">Faqat ogohlantirish</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div v-if="penaltyForm.penalty_type === 'fixed'">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Summa</label>
              <input v-model.number="penaltyForm.penalty_amount" type="number" min="0"
                     class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
            </div>
            <div v-if="penaltyForm.penalty_type === 'percentage_of_bonus'">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Foiz (%)</label>
              <input v-model.number="penaltyForm.penalty_percentage" type="number" min="0" max="100"
                     class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Trigger hodisa</label>
              <select v-model="penaltyForm.trigger_event"
                      class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
                <option value="">Qo'lda</option>
                <option value="lead_not_contacted_24h">Lid 24 soat ichida kontakt qilinmadi</option>
                <option value="lead_not_contacted_48h">Lid 48 soat ichida kontakt qilinmadi</option>
                <option value="crm_not_filled">CRM to'ldirilmagan</option>
                <option value="task_overdue">Vazifa muddati o'tdi</option>
                <option value="task_overdue_3_days">Vazifa 3 kundan ortiq kechiktirildi</option>
                <option value="low_kpi_3_days">KPI 3 kun ketma-ket past</option>
                <option value="missed_call">O'tkazib yuborilgan qo'ng'iroq</option>
                <option value="no_activity_24h">24 soat faoliyat yo'q</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="flex items-center gap-2">
              <input v-model="penaltyForm.warning_before_penalty" type="checkbox" id="warning_before_penalty" class="rounded">
              <label for="warning_before_penalty" class="text-sm text-gray-700 dark:text-gray-300">Avval ogohlantirish</label>
            </div>
            <div v-if="penaltyForm.warning_before_penalty">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ogohlantirish soni</label>
              <input v-model.number="penaltyForm.warnings_before_penalty" type="number" min="1" max="5"
                     class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
            </div>
          </div>
          <div class="flex items-center gap-4">
            <label class="flex items-center gap-2">
              <input v-model="penaltyForm.is_active" type="checkbox" class="rounded">
              <span class="text-sm text-gray-700 dark:text-gray-300">Faol</span>
            </label>
            <label class="flex items-center gap-2">
              <input v-model="penaltyForm.allow_appeal" type="checkbox" class="rounded">
              <span class="text-sm text-gray-700 dark:text-gray-300">Shikoyat qilish mumkin</span>
            </label>
          </div>
          <div class="flex justify-end gap-3 pt-4">
            <button type="button" @click="penaltyModal = false"
                    class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
              Bekor qilish
            </button>
            <button type="submit" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">
              Saqlash
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="deleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-sm w-full p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">O'chirishni tasdiqlang</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
          "{{ deleteItem?.name }}" ni o'chirishni xohlaysizmi?
        </p>
        <div class="flex justify-end gap-3">
          <button @click="deleteModal = false" class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
            Bekor qilish
          </button>
          <button @click="executeDelete" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">
            O'chirish
          </button>
        </div>
      </div>
    </div>
  </SalesHeadLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';

const props = defineProps({
  kpiSettings: Array,
  bonusSettings: Array,
  penaltyRules: Array,
  panelType: String,
});

const activeTab = ref('kpi');
const kpiModal = ref(false);
const bonusModal = ref(false);
const penaltyModal = ref(false);
const deleteModal = ref(false);
const editingKpi = ref(null);
const editingBonus = ref(null);
const editingPenalty = ref(null);
const deleteItem = ref(null);
const deleteType = ref('');

const kpiForm = ref({
  name: '',
  description: '',
  kpi_type: 'leads_converted',
  period_type: 'monthly',
  target_min: 0,
  measurement_unit: 'count',
  weight: 10,
  is_active: true,
});

const bonusForm = ref({
  name: '',
  description: '',
  bonus_type: 'fixed',
  base_amount: 0,
  calculation_period: 'monthly',
  min_kpi_score: 80,
  min_working_days: 20,
  requires_approval: true,
  is_active: true,
});

const penaltyForm = ref({
  name: '',
  description: '',
  category: 'performance',
  penalty_type: 'fixed',
  penalty_amount: 0,
  penalty_percentage: 0,
  trigger_event: '',
  warning_before_penalty: false,
  warnings_before_penalty: 1,
  is_active: true,
  allow_appeal: true,
});

const formatCurrency = (value) => {
  if (!value) return "0 so'm";
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

const getKpiTypeBadge = (type) => {
  const badges = {
    leads_converted: 'px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700',
    revenue: 'px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-700',
    deals_count: 'px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-700',
    calls_made: 'px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-700',
    tasks_completed: 'px-2 py-1 rounded text-xs font-medium bg-indigo-100 text-indigo-700',
    conversion_rate: 'px-2 py-1 rounded text-xs font-medium bg-teal-100 text-teal-700',
    response_time: 'px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-700',
  };
  return badges[type] || 'px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700';
};

const getKpiTypeLabel = (type) => {
  const labels = {
    leads_converted: 'Konversiya',
    revenue: 'Sotuv',
    deals_count: 'Deallar',
    calls_made: "Qo'ng'iroqlar",
    tasks_completed: 'Vazifalar',
    conversion_rate: 'Konversiya %',
    response_time: 'Javob vaqti',
  };
  return labels[type] || type;
};

const getPenaltySeverityLabel = (severity) => {
  const labels = { low: 'Past', medium: "O'rta", high: 'Yuqori', critical: 'Kritik' };
  return labels[severity] || severity;
};

const getCategoryLabel = (category) => {
  const labels = {
    crm_discipline: 'CRM intizomi',
    performance: 'Samaradorlik',
    attendance: 'Davomat',
    customer_service: 'Mijozlarga xizmat',
  };
  return labels[category] || category;
};

const getPenaltyAmountLabel = (rule) => {
  if (rule.penalty_type === 'fixed') {
    return formatCurrency(rule.penalty_amount);
  } else if (rule.penalty_type === 'percentage_of_bonus') {
    return rule.penalty_percentage + '% (bonus)';
  } else {
    return 'Ogohlantirish';
  }
};

const openCreateModal = (type) => {
  if (type === 'kpi') {
    editingKpi.value = null;
    kpiForm.value = {
      name: '',
      description: '',
      kpi_type: 'leads_converted',
      period_type: 'monthly',
      target_min: 0,
      measurement_unit: 'count',
      weight: 10,
      is_active: true,
    };
    kpiModal.value = true;
  } else if (type === 'bonus') {
    editingBonus.value = null;
    bonusForm.value = {
      name: '',
      description: '',
      bonus_type: 'fixed',
      base_amount: 0,
      calculation_period: 'monthly',
      min_kpi_score: 80,
      min_working_days: 20,
      requires_approval: true,
      is_active: true,
    };
    bonusModal.value = true;
  } else if (type === 'penalty') {
    editingPenalty.value = null;
    penaltyForm.value = {
      name: '',
      description: '',
      category: 'performance',
      penalty_type: 'fixed',
      penalty_amount: 0,
      penalty_percentage: 0,
      trigger_event: '',
      warning_before_penalty: false,
      warnings_before_penalty: 1,
      is_active: true,
      allow_appeal: true,
    };
    penaltyModal.value = true;
  }
};

const editKpi = (kpi) => {
  editingKpi.value = kpi;
  kpiForm.value = { ...kpi };
  kpiModal.value = true;
};

const saveKpi = () => {
  if (editingKpi.value) {
    router.put(`/sales-head/sales-kpi/settings/${editingKpi.value.id}`, kpiForm.value, {
      onSuccess: () => { kpiModal.value = false; }
    });
  } else {
    router.post('/sales-head/sales-kpi/settings/kpi', kpiForm.value, {
      onSuccess: () => { kpiModal.value = false; }
    });
  }
};

const confirmDelete = (type, item) => {
  deleteType.value = type;
  deleteItem.value = item;
  deleteModal.value = true;
};

const executeDelete = () => {
  const routes = {
    kpi: `/sales-head/sales-kpi/settings/${deleteItem.value.id}`,
    bonus: `/sales-head/sales-kpi/bonus-settings/${deleteItem.value.id}`,
    penalty: `/sales-head/sales-kpi/penalty-rules/${deleteItem.value.id}`,
  };
  router.delete(routes[deleteType.value], {
    onSuccess: () => { deleteModal.value = false; }
  });
};

const editBonus = (bonus) => {
  editingBonus.value = bonus;
  bonusForm.value = { ...bonus };
  bonusModal.value = true;
};

const saveBonus = () => {
  if (editingBonus.value) {
    router.put(`/sales-head/sales-kpi/bonus-settings/${editingBonus.value.id}`, bonusForm.value, {
      onSuccess: () => { bonusModal.value = false; }
    });
  } else {
    router.post('/sales-head/sales-kpi/bonus-settings', bonusForm.value, {
      onSuccess: () => { bonusModal.value = false; }
    });
  }
};

const editPenalty = (rule) => {
  editingPenalty.value = rule;
  penaltyForm.value = { ...rule };
  penaltyModal.value = true;
};

const savePenalty = () => {
  if (editingPenalty.value) {
    router.put(`/sales-head/sales-kpi/penalty-rules/${editingPenalty.value.id}`, penaltyForm.value, {
      onSuccess: () => { penaltyModal.value = false; }
    });
  } else {
    router.post('/sales-head/sales-kpi/penalty-rules', penaltyForm.value, {
      onSuccess: () => { penaltyModal.value = false; }
    });
  }
};
</script>
