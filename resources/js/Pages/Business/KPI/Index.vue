<template>
  <BusinessLayout :title="t('kpi.title')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ t('kpi.title') }}</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ t('kpi.subtitle') }}</p>
        </div>
        <button v-if="hasConfig" @click="showQuickEntry = true" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
          {{ t('kpi.daily_entry') }}
        </button>
      </div>

      <!-- Empty State — Setup Wizard -->
      <div v-if="!hasConfig" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
        <div class="max-w-xl mx-auto px-6 py-12 text-center">
          <div class="w-14 h-14 mx-auto mb-5 bg-indigo-100 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center">
            <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
          </div>
          <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ t('kpi.setup_title') }}</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ t('kpi.setup_desc') }}</p>

          <!-- Step indicator -->
          <div class="flex items-center justify-center gap-8 mb-8 text-xs">
            <div class="flex items-center gap-2">
              <span :class="['w-6 h-6 rounded-full flex items-center justify-center font-bold', step === 1 ? 'bg-indigo-600 text-white' : step > 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-400']">{{ step > 1 ? '✓' : '1' }}</span>
              <span :class="step === 1 ? 'text-indigo-600 font-semibold' : 'text-gray-500'">{{ t('kpi.step_business_type') }}</span>
            </div>
            <div class="w-8 h-px bg-gray-200"></div>
            <div class="flex items-center gap-2">
              <span :class="['w-6 h-6 rounded-full flex items-center justify-center font-bold', step === 2 ? 'bg-indigo-600 text-white' : step > 2 ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-400']">{{ step > 2 ? '✓' : '2' }}</span>
              <span :class="step === 2 ? 'text-indigo-600 font-semibold' : 'text-gray-500'">{{ t('kpi.step_select_kpis') }}</span>
            </div>
            <div class="w-8 h-px bg-gray-200"></div>
            <div class="flex items-center gap-2">
              <span :class="['w-6 h-6 rounded-full flex items-center justify-center font-bold', step === 3 ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-400']">3</span>
              <span :class="step === 3 ? 'text-indigo-600 font-semibold' : 'text-gray-500'">{{ t('kpi.step_set_targets') }}</span>
            </div>
          </div>

          <!-- Step 1: Biznes turi -->
          <div v-if="step === 1" class="text-left space-y-3">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('kpi.select_business_type') }}</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
              <button v-for="bt in businessTypes" :key="bt.value" @click="selectedBusinessType = bt.value; loadKpis()" :class="[
                'p-3 rounded-xl border-2 text-left transition-all',
                selectedBusinessType === bt.value ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
              ]">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center mb-2" :class="selectedBusinessType === bt.value ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'">
                  <div class="w-5 h-5" v-html="bt.svg"></div>
                </div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ bt.label }}</p>
              </button>
            </div>
            <div class="flex justify-end pt-2">
              <button @click="step = 2" :disabled="!selectedBusinessType" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg disabled:opacity-40 transition-colors">Keyingi</button>
            </div>
          </div>

          <!-- Step 2: KPI tanlash -->
          <div v-if="step === 2" class="text-left space-y-3">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('kpi.recommended_kpis') }}</label>
            <div class="space-y-2">
              <label v-for="kpi in availableKpis" :key="kpi.code" :class="[
                'flex items-center gap-3 p-3 rounded-xl border transition-all cursor-pointer',
                selectedKpis.includes(kpi.code) ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/10' : 'border-gray-200 dark:border-gray-700'
              ]">
                <input type="checkbox" :value="kpi.code" v-model="selectedKpis" class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500" />
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{ kpi.name }}</p>
                  <p class="text-xs text-gray-500">{{ kpi.description }}</p>
                </div>
                <span class="text-xs text-gray-400 flex-shrink-0">{{ kpi.unit }}</span>
              </label>
            </div>
            <div class="flex justify-between pt-2">
              <button @click="step = 1" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">Orqaga</button>
              <button @click="step = 3" :disabled="selectedKpis.length === 0" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg disabled:opacity-40 transition-colors">Keyingi</button>
            </div>
          </div>

          <!-- Step 3: Maqsadlar -->
          <div v-if="step === 3" class="text-left space-y-4">
            <!-- Asosiy ko'rsatkichlar -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Asosiy maqsadlar</label>
              <div class="space-y-2">
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/30 rounded-xl">
                  <div class="flex-1"><p class="text-sm font-medium text-gray-900 dark:text-white">Oylik daromad</p></div>
                  <input :value="formatNum(targets.revenue)" @input="targets.revenue = parseNum($event.target.value); autoCalc()" type="text" inputmode="numeric" placeholder="30 000 000" class="w-40 px-3 py-2 text-sm text-right bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" />
                  <span class="text-xs text-gray-400 w-10">so'm</span>
                </div>
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/30 rounded-xl">
                  <div class="flex-1"><p class="text-sm font-medium text-gray-900 dark:text-white">Jami leadlar</p></div>
                  <input :value="formatNum(targets.leads_total)" @input="targets.leads_total = parseNum($event.target.value); autoCalc()" type="text" inputmode="numeric" placeholder="100" class="w-40 px-3 py-2 text-sm text-right bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" />
                  <span class="text-xs text-gray-400 w-10">ta</span>
                </div>
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/30 rounded-xl">
                  <div class="flex-1"><p class="text-sm font-medium text-gray-900 dark:text-white">Jami sotuvlar</p></div>
                  <input :value="formatNum(targets.sales_total)" @input="targets.sales_total = parseNum($event.target.value); autoCalc()" type="text" inputmode="numeric" placeholder="50" class="w-40 px-3 py-2 text-sm text-right bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" />
                  <span class="text-xs text-gray-400 w-10">ta</span>
                </div>
              </div>
            </div>

            <!-- Avtomatik hisoblanadigan ko'rsatkichlar -->
            <div>
              <div class="flex items-center gap-2 mb-2">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Avtomatik hisoblangan</label>
                <span class="text-[10px] text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 px-1.5 py-0.5 rounded font-medium">auto</span>
              </div>
              <div class="space-y-2">
                <div class="flex items-center gap-3 p-3 rounded-xl border border-dashed" :class="manualOverride.avg_check ? 'bg-amber-50/50 border-amber-300' : 'bg-indigo-50/30 dark:bg-indigo-900/5 border-indigo-200 dark:border-indigo-800'">
                  <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">O'rtacha chek</p>
                    <p class="text-[10px] text-gray-400">{{ manualOverride.avg_check ? "Qo'lda o'zgartirilgan" : 'Daromad ÷ Sotuvlar' }}</p>
                  </div>
                  <input :value="formatNum(targets.avg_check)" @input="targets.avg_check = parseNum($event.target.value); manualOverride.avg_check = true" type="text" inputmode="numeric" class="w-40 px-3 py-2 text-sm text-right border rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" :class="manualOverride.avg_check ? 'bg-white border-amber-300' : 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-200 dark:border-indigo-700'" />
                  <div class="flex items-center gap-1 w-14">
                    <span class="text-xs text-gray-400">so'm</span>
                    <button v-if="manualOverride.avg_check" @click="manualOverride.avg_check = false; autoCalc()" class="p-0.5 text-amber-500 hover:text-indigo-600" title="Avtomatik hisoblashga qaytarish">
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    </button>
                  </div>
                </div>
                <div class="flex items-center gap-3 p-3 rounded-xl border border-dashed" :class="manualOverride.conversion_rate ? 'bg-amber-50/50 border-amber-300' : 'bg-indigo-50/30 dark:bg-indigo-900/5 border-indigo-200 dark:border-indigo-800'">
                  <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Konversiya</p>
                    <p class="text-[10px] text-gray-400">{{ manualOverride.conversion_rate ? "Qo'lda o'zgartirilgan" : 'Sotuvlar ÷ Leadlar × 100' }}</p>
                  </div>
                  <input :value="targets.conversion_rate" @input="targets.conversion_rate = $event.target.value; manualOverride.conversion_rate = true" type="text" inputmode="numeric" class="w-40 px-3 py-2 text-sm text-right border rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" :class="manualOverride.conversion_rate ? 'bg-white border-amber-300' : 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-200 dark:border-indigo-700'" />
                  <div class="flex items-center gap-1 w-14">
                    <span class="text-xs text-gray-400">%</span>
                    <button v-if="manualOverride.conversion_rate" @click="manualOverride.conversion_rate = false; autoCalc()" class="p-0.5 text-amber-500 hover:text-indigo-600" title="Avtomatik hisoblashga qaytarish">
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Qo'shimcha (ixtiyoriy) -->
            <div v-if="selectedKpis.includes('ad_spend')">
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Qo'shimcha</label>
              <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/30 rounded-xl">
                <div class="flex-1"><p class="text-sm font-medium text-gray-900 dark:text-white">Reklama xarajati</p></div>
                <input :value="formatNum(targets.ad_spend)" @input="targets.ad_spend = parseNum($event.target.value)" type="text" inputmode="numeric" placeholder="5 000 000" class="w-40 px-3 py-2 text-sm text-right bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" />
                <span class="text-xs text-gray-400 w-10">so'm</span>
              </div>
            </div>

            <div class="flex justify-between pt-2">
              <button @click="step = 2" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">Orqaga</button>
              <button @click="createPlan" :disabled="isSaving || !targets.revenue" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg disabled:opacity-50 transition-colors">
                <span v-if="isSaving">Yaratilmoqda...</span>
                <span v-else>{{ t('kpi.create_plan') }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Dashboard — Kunlik jadval -->
      <template v-if="hasConfig">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ currentMonth }} {{ currentYear }}</h3>
              <span class="text-xs text-gray-400">{{ filledDays }}/{{ monthDays.length }} kun kiritilgan</span>
            </div>
            <div class="flex items-center gap-4 text-xs">
              <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-indigo-100 border border-indigo-300"></span> Reja</span>
              <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-emerald-500"></span> Fakt</span>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full border-collapse" style="min-width: 100%;">
              <thead>
                <tr>
                  <th class="text-left text-sm font-bold text-gray-700 dark:text-gray-300 px-3 py-3 sticky left-0 bg-white dark:bg-gray-800 z-20 min-w-[140px] border-r border-b border-gray-200 dark:border-gray-700">Ko'rsatkich</th>
                  <th v-for="day in monthDays" :key="day.date" :class="[
                    'text-center px-1 py-2 min-w-[58px] border-b border-gray-200 dark:border-gray-700',
                    day.isToday ? 'bg-indigo-50 dark:bg-indigo-900/20' : day.isWeekend ? 'bg-orange-50/50 dark:bg-orange-900/5' : ''
                  ]">
                    <div :class="['text-sm font-bold', day.isToday ? 'text-indigo-600' : day.isFuture ? 'text-gray-300' : 'text-gray-700 dark:text-gray-400']">{{ day.dayNum }}</div>
                    <div :class="['text-[10px]', day.isToday ? 'text-indigo-500 font-semibold' : day.isWeekend ? 'text-orange-500 font-medium' : 'text-gray-400']">{{ day.dayName }}</div>
                  </th>
                  <th class="text-center px-3 py-2 min-w-[80px] bg-gray-100 dark:bg-gray-700 border-b border-gray-200 sticky right-0 z-20">
                    <div class="text-sm font-bold text-gray-800 dark:text-gray-200">Jami</div>
                  </th>
                </tr>
              </thead>
              <tbody>
                <template v-for="row in tableRows" :key="row.key">
                  <!-- Reja qatori -->
                  <tr class="bg-indigo-50/40 dark:bg-indigo-900/5">
                    <td class="px-3 py-2.5 sticky left-0 z-20 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700" rowspan="2">
                      <p class="text-sm font-bold text-gray-900 dark:text-white">{{ row.label }}</p>
                      <p class="text-[10px] text-gray-400">{{ row.unit }}</p>
                    </td>
                    <td v-for="day in monthDays" :key="'p-'+day.date" :class="['text-center px-1 py-2 border-r border-gray-100/60 dark:border-gray-700/20', day.isWeekend ? 'bg-orange-50/30' : 'bg-indigo-50/20 dark:bg-indigo-900/5']">
                      <span v-if="!day.isWeekend" class="text-[11px] text-indigo-400 font-semibold">{{ row.dailyPlan }}</span>
                      <span v-else class="text-[10px] text-orange-300">dam</span>
                    </td>
                    <td class="text-center px-2 py-2 bg-indigo-50 dark:bg-indigo-900/10 sticky right-0 z-20 border-l border-gray-200">
                      <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ row.monthPlan }}</span>
                    </td>
                  </tr>
                  <!-- Fakt qatori -->
                  <tr class="border-b-2 border-gray-200 dark:border-gray-700">
                    <td v-for="day in monthDays" :key="'f-'+day.date" :class="['text-center px-1 py-2 border-r border-gray-100/60 dark:border-gray-700/20', day.isToday ? 'bg-indigo-50/40' : day.isWeekend ? 'bg-orange-50/20' : '']">
                      <span v-if="day.entry && day.entry[row.key]" :class="['text-sm font-bold', row.getColor(day.entry[row.key], row.dailyPlanRaw)]">{{ row.fmt(day.entry[row.key]) }}</span>
                      <span v-else-if="!day.isFuture" class="text-[10px] text-gray-200 dark:text-gray-700">·</span>
                    </td>
                    <td class="text-center px-2 py-2 bg-gray-100 dark:bg-gray-700/50 sticky right-0 z-20 border-l border-gray-200">
                      <span class="text-sm font-black text-gray-900 dark:text-white">{{ row.fmt(row.total) }}</span>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </template>

      <!-- Quick Entry Modal -->
      <Teleport to="body">
        <Transition enter-active-class="transition-opacity duration-150" leave-active-class="transition-opacity duration-100" enter-from-class="opacity-0" leave-to-class="opacity-0">
          <div v-if="showQuickEntry" @click="showQuickEntry = false" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div @click.stop class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-700 overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ t('kpi.daily_entry') }}</h3>
                <p class="text-xs text-gray-500 mt-0.5">Bugungi ko'rsatkichlarni kiriting</p>
              </div>
              <div class="px-6 py-4 space-y-3 max-h-[60vh] overflow-y-auto">
                <div v-for="kpi in dashboardKpis" :key="'qe-'+kpi.code" class="flex items-center gap-3">
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ kpi.name }}</p>
                  </div>
                  <input v-model="quickEntryData[kpi.code]" type="text" inputmode="numeric" :placeholder="String(kpi.actual || 0)" class="w-28 px-3 py-2 text-sm text-right bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" />
                </div>
              </div>
              <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex gap-3">
                <button @click="showQuickEntry = false" class="flex-1 px-4 py-2.5 text-sm font-semibold text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">Bekor qilish</button>
                <button @click="saveQuickEntry" :disabled="isSavingEntry" class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition-colors disabled:opacity-50">
                  {{ isSavingEntry ? 'Saqlanmoqda...' : 'Saqlash' }}
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { useI18n } from '@/i18n';
import axios from 'axios';

const { t } = useI18n();

const props = defineProps({
  kpis: { type: Object, default: null },
  businessCategory: { type: String, default: '' },
  activePlan: { type: Object, default: null },
  kpiPlans: { type: Array, default: () => [] },
  dailyEntries: { type: Object, default: null },
  targetMonth: { type: Object, default: null },
  roasBenchmark: { type: Object, default: null },
  ltvCacBenchmark: { type: Object, default: null },
});

const hasConfig = computed(() => !!props.activePlan);

// Biznes kategoriyasini KPI turga mapping
const categoryToKpiType = {
  'ecommerce': 'ecommerce', 'online_shop': 'ecommerce', 'marketplace': 'ecommerce',
  'service': 'service', 'consulting': 'service', 'agency': 'service',
  'education': 'education', 'courses': 'education', 'training': 'education',
  'restaurant': 'restaurant', 'cafe': 'restaurant', 'food': 'restaurant',
  'retail': 'retail', 'shop': 'retail', 'store': 'retail',
  'saas': 'saas', 'technology': 'saas', 'it': 'saas', 'software': 'saas',
  'beauty': 'beauty', 'salon': 'beauty', 'spa': 'beauty',
  'fitness': 'fitness', 'gym': 'fitness', 'sport': 'fitness',
};

const detectedType = categoryToKpiType[props.businessCategory?.toLowerCase()] || '';

// Setup wizard state — agar biznes turi aniqlangan bo'lsa, 2-qadamdan boshlaymiz
const step = ref(detectedType ? 2 : 1);
const selectedBusinessType = ref(detectedType || '');
const availableKpis = ref([]);
const selectedKpis = ref([]);
const targets = ref({ revenue: '', leads_total: '', sales_total: '', avg_check: '', conversion_rate: '', ad_spend: '' });
const isSaving = ref(false);
const manualOverride = ref({ avg_check: false, conversion_rate: false });

const autoCalc = () => {
  const revenue = Number(targets.value.revenue) || 0;
  const leads = Number(targets.value.leads_total) || 0;
  const sales = Number(targets.value.sales_total) || 0;

  // O'rtacha chek = Daromad / Sotuvlar
  if (!manualOverride.value.avg_check && sales > 0) {
    targets.value.avg_check = Math.round(revenue / sales);
  }

  // Konversiya = Sotuvlar / Leadlar * 100
  if (!manualOverride.value.conversion_rate && leads > 0) {
    targets.value.conversion_rate = Math.round((sales / leads) * 100);
  }
};

// Agar biznes turi aniqlangan — KPIlarni avtomatik yuklash
if (detectedType) { setTimeout(() => loadKpis(), 0); }

const businessTypes = [
  { value: 'ecommerce', label: 'E-commerce', svg: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>' },
  { value: 'service', label: 'Xizmat', svg: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.1-5.1m0 0L11.42 4.97m-5.1 5.1h13.16M4.93 19.07h14.14" /><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 01-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 11-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 016.336-4.486l-3.276 3.276a3.004 3.004 0 002.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852z" /></svg>' },
  { value: 'education', label: "Ta'lim", svg: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" /></svg>' },
  { value: 'restaurant', label: 'Restoran', svg: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v-1.5m0 1.5c-1.355 0-2.697.056-4.024.166C6.845 8.51 6 9.473 6 10.608v2.513m6-4.87c1.355 0 2.697.055 4.024.165C17.155 8.51 18 9.473 18 10.608v2.513m-3-4.87v-1.5m-6 1.5v-1.5m12 9.75l-1.5.75a3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-3 0L3 16.5m15-3.38a48.474 48.474 0 00-6-.37c-2.032 0-4.034.126-6 .37" /></svg>' },
  { value: 'retail', label: 'Chakana savdo', svg: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" /></svg>' },
  { value: 'saas', label: 'SaaS / IT', svg: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" /></svg>' },
  { value: 'beauty', label: "Go'zallik", svg: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" /></svg>' },
  { value: 'fitness', label: 'Fitness', svg: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>' },
  { value: 'default', label: 'Boshqa', svg: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" /></svg>' },
];

const defaultKpis = [
  { code: 'revenue', name: 'Oylik daromad', description: "Umumiy tushum", unit: "so'm", benchmark: '50000000' },
  { code: 'leads_total', name: 'Jami leadlar', description: 'Barcha manbalardan', unit: 'ta', benchmark: '100' },
  { code: 'sales_total', name: 'Jami sotuvlar', description: "Yangi + takroriy", unit: 'ta', benchmark: '50' },
  { code: 'avg_check', name: "O'rtacha chek", description: "Bir sotuv summasi", unit: "so'm", benchmark: '500000' },
  { code: 'conversion_rate', name: 'Konversiya', description: 'Lead → Sotuv', unit: '%', benchmark: '15' },
  { code: 'cac', name: 'CAC', description: 'Mijoz jalb qilish narxi', unit: "so'm", benchmark: '50000' },
  { code: 'roi', name: 'ROI', description: 'Investitsiya qaytimi', unit: '%', benchmark: '200' },
  { code: 'ad_spend', name: 'Reklama xarajati', description: "Marketing sarfi", unit: "so'm", benchmark: '5000000' },
];

const formatNum = (v) => {
  if (!v && v !== 0) return '';
  return new Intl.NumberFormat('uz-UZ').format(Number(v) || 0);
};
const parseNum = (s) => Number(String(s).replace(/\s/g, '').replace(/[^\d]/g, '')) || '';

const loadKpis = () => {
  availableKpis.value = defaultKpis;
  selectedKpis.value = defaultKpis.slice(0, 5).map(k => k.code);
};

const selectedKpiDetails = computed(() => availableKpis.value.filter(k => selectedKpis.value.includes(k.code)));

const createPlan = () => {
  isSaving.value = true;
  const t = targets.value;
  router.post('/business/kpi/save-plan', {
    new_sales: Number(t.sales_total) || 50,
    avg_check: Number(t.avg_check) || 500000,
    leads: Number(t.leads_total) || null,
    lead_cost: Number(t.ad_spend) && Number(t.leads_total) ? Math.round(Number(t.ad_spend) / Number(t.leads_total)) : null,
  }, {
    onSuccess: () => { isSaving.value = false; },
    onError: () => { isSaving.value = false; },
  });
};

// Dashboard
const currentMonth = props.targetMonth?.month_name || new Date().toLocaleString('uz-UZ', { month: 'long' });
const currentYear = props.targetMonth?.year || new Date().getFullYear();

const dashboardKpis = computed(() => {
  if (!props.activePlan) return [];

  const plan = props.activePlan;
  const kpis = props.kpis || {};

  // Plan'dan to'g'ridan-to'g'ri ustunlardan olish
  const revenue = Number(plan.new_sales || 0) * Number(plan.avg_check || 0);
  const kpiMap = [
    { code: 'revenue', name: 'Daromad', target: plan.revenue_target || revenue, actual: kpis.total_revenue },
    { code: 'sales', name: 'Sotuvlar', target: plan.new_sales, actual: kpis.total_orders },
    { code: 'avg_check', name: "O'rtacha chek", target: plan.avg_check, actual: kpis.average_order_value },
    { code: 'leads', name: 'Leadlar', target: plan.leads_target, actual: kpis.total_leads },
    { code: 'conversion', name: 'Konversiya', target: plan.conversion_rate, actual: kpis.conversion_rate },
    { code: 'cac', name: 'CAC', target: plan.cac, actual: kpis.cac },
    { code: 'roi', name: 'ROI', target: plan.roi, actual: kpis.roi },
    { code: 'ad_spend', name: 'Reklama xarajati', target: plan.ad_costs, actual: kpis.total_marketing_spend },
  ];

  return kpiMap
    .filter(k => k.target && Number(k.target) > 0)
    .map(k => {
      const target = Number(k.target) || 0;
      const actual = Number(k.actual) || 0;
      const percent = target > 0 ? Math.round((actual / target) * 100) : 0;
      return { code: k.code, name: k.name, target: formatNum(target), actual: formatNum(actual), percent };
    });
});

// Oy kunlari jadvali
const dayNames = ['Yak', 'Dush', 'Sesh', 'Chor', 'Pay', 'Jum', 'Shan'];
const monthDays = computed(() => {
  const year = props.targetMonth?.year || new Date().getFullYear();
  const month = (props.targetMonth?.month || new Date().getMonth() + 1) - 1;
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const today = new Date();
  const entries = props.dailyEntries || {};

  const days = [];
  for (let d = 1; d <= daysInMonth; d++) {
    const date = new Date(year, month, d);
    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    const isToday = date.toDateString() === today.toDateString();
    const isFuture = date > today;
    days.push({
      date: dateStr,
      dayNum: d,
      dayName: dayNames[date.getDay()],
      isToday,
      isFuture,
      isWeekend: date.getDay() === 0 || date.getDay() === 6,
      hasData: !!entries[dateStr],
      entry: entries[dateStr] || null,
    });
  }
  return days;
});

// Jadval qatorlari — KPI nomlari chapda, kunlar tepada
const fmtFull = (v) => {
  if (!v) return '0';
  return new Intl.NumberFormat('uz-UZ').format(Number(v));
};
const fmtPct = (v) => v ? v + '%' : '0%';
const fmtPlain = (v) => v || '0';
// fmtShort alias — endi to'liq raqam
const fmtShort = fmtFull;

const filledDays = computed(() => Object.keys(props.dailyEntries || {}).length);

const colorGood = (actual, plan) => {
  if (!actual || !plan) return 'text-gray-900 dark:text-white';
  const pct = (actual / plan) * 100;
  return pct >= 90 ? 'text-emerald-600' : pct >= 60 ? 'text-amber-600' : 'text-red-600';
};
const colorLow = (actual, plan) => { // uchun xarajat — past yaxshi
  if (!actual || !plan) return 'text-gray-900 dark:text-white';
  const pct = (actual / plan) * 100;
  return pct <= 100 ? 'text-emerald-600' : pct <= 130 ? 'text-amber-600' : 'text-red-600';
};

const tableRows = computed(() => {
  const entries = props.dailyEntries || {};
  const plan = props.activePlan || {};
  const daily = plan.daily_breakdown || {};

  const totalRevenue = (plan.new_sales || 0) * Number(plan.avg_check || 0);
  const rows = [
    { key: 'leads_total', label: 'Leadlar', unit: 'ta', monthTargetRaw: plan.leads_target || (daily.total_leads ? daily.total_leads * (plan.working_days || 22) : 0), fmt: fmtPlain, getColor: colorGood },
    { key: 'sales_total', label: 'Sotuvlar', unit: 'ta', monthTargetRaw: plan.new_sales || 0, fmt: fmtPlain, getColor: colorGood },
    { key: 'revenue_total', label: 'Daromad', unit: "so'm", monthTargetRaw: plan.revenue_target || totalRevenue, fmt: fmtFull, getColor: colorGood },
    { key: 'spend_total', label: 'Reklama xarajati', unit: "so'm", monthTargetRaw: plan.ad_costs || (daily.ad_costs ? daily.ad_costs * (plan.working_days || 22) : 0), fmt: fmtFull, getColor: colorLow },
    { key: 'avg_check', label: "O'rtacha chek", unit: "so'm", monthTargetRaw: Number(plan.avg_check || 0), fmt: fmtFull, getColor: colorGood },
    { key: 'conversion_rate', label: 'Konversiya', unit: '%', monthTargetRaw: plan.conversion_rate || daily.conversion_rate || 0, fmt: fmtPct, getColor: colorGood },
  ];

  // Oydagi barcha ish kunlari (yakshanbasiz)
  const workDaysCount = monthDays.value.filter(d => !d.isWeekend).length || plan.working_days || 22;

  return rows.map(row => {
    const isAvg = row.key === 'conversion_rate' || row.key === 'avg_check';
    const monthTarget = row.monthTargetRaw;
    // Kunlik reja: o'rtacha/foiz uchun — oylik qiymatni o'zi; summalanadigan uchun — oylik / ish kunlari
    const dailyTarget = isAvg ? monthTarget : (monthTarget && workDaysCount > 0 ? Math.round(monthTarget / workDaysCount) : 0);

    const vals = Object.values(entries).map(e => Number(e[row.key]) || 0).filter(Boolean);
    const total = isAvg
      ? (vals.length ? Math.round(vals.reduce((a,b) => a+b, 0) / vals.length) : 0)
      : vals.reduce((a,b) => a+b, 0);

    return {
      ...row,
      dailyPlanRaw: dailyTarget,
      dailyPlan: dailyTarget ? fmtFull(dailyTarget) : '',
      monthPlan: monthTarget ? fmtFull(monthTarget) : '—',
      total,
    };
  });
});

const monthTotals = computed(() => {
  const entries = Object.values(props.dailyEntries || {});
  const leads = entries.reduce((s, e) => s + (Number(e.leads_total) || 0), 0);
  const sales = entries.reduce((s, e) => s + (Number(e.sales_total) || 0), 0);
  const revenue = entries.reduce((s, e) => s + (Number(e.revenue_total) || 0), 0);
  const spend = entries.reduce((s, e) => s + (Number(e.spend_total) || 0), 0);
  return { leads, sales, revenue, spend, convRate: leads > 0 ? Math.round((sales / leads) * 100) : 0 };
});

const bestKpis = computed(() => dashboardKpis.value.filter(k => k.percent >= 90).sort((a, b) => b.percent - a.percent).slice(0, 3));
const weakKpis = computed(() => dashboardKpis.value.filter(k => k.percent < 70 && k.percent > 0).sort((a, b) => a.percent - b.percent).slice(0, 3));

// Quick entry
const showQuickEntry = ref(false);
const quickEntryData = ref({});
const isSavingEntry = ref(false);

const saveQuickEntry = async () => {
  isSavingEntry.value = true;
  try {
    router.post('/business/kpi/quick-entry', quickEntryData.value, {
      preserveScroll: true,
      onSuccess: () => { showQuickEntry.value = false; isSavingEntry.value = false; quickEntryData.value = {}; },
      onError: () => { isSavingEntry.value = false; },
    });
  } catch { isSavingEntry.value = false; }
};
</script>
