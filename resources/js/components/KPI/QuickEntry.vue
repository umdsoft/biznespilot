<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold">
            {{ hasExistingData ? 'Bugungi ma\'lumotlarni tahrirlash' : 'Bugungi ma\'lumotlarni kiritish' }}
          </h3>
          <p class="text-sm text-blue-100">{{ formattedToday }}</p>
        </div>
        <div class="flex items-center gap-2">
          <span
            v-if="hasExistingData"
            class="px-3 py-1.5 rounded-lg bg-green-500/30 border border-green-400/50 text-green-100 text-sm flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Tahrirlash rejimi
          </span>
          <span
            v-else
            class="px-3 py-1.5 rounded-lg bg-white/20 border border-white/30 text-white text-sm flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Yangi kiritish
          </span>
        </div>
      </div>
    </div>

    <!-- Form -->
    <form @submit.prevent="submitForm" class="p-6">
      <!-- Grid Layout -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

        <!-- LIDLAR Section -->
        <div class="space-y-4">
          <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center">
            <span class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-2">
              <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </span>
            Lidlar
          </h4>

          <div>
            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
              Digital (Instagram, Facebook, Google)
            </label>
            <input
              type="number"
              v-model.number="form.leads_digital"
              min="0"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="0"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
              Offline (Flayer, ko'cha reklama)
            </label>
            <input
              type="number"
              v-model.number="form.leads_offline"
              min="0"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="0"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
              Tavsiya (Referral)
            </label>
            <input
              type="number"
              v-model.number="form.leads_referral"
              min="0"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="0"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
              Organik (Walk-in, qidiruvdan)
            </label>
            <input
              type="number"
              v-model.number="form.leads_organic"
              min="0"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="0"
            />
          </div>

          <!-- Total Leads -->
          <div class="pt-2 border-t border-gray-200 dark:border-gray-600">
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Jami lidlar:</span>
              <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ totalLeads }}</span>
            </div>
          </div>
        </div>

        <!-- XARAJATLAR Section -->
        <div class="space-y-4">
          <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center">
            <span class="w-8 h-8 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center mr-2">
              <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </span>
            Xarajatlar
          </h4>

          <div>
            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
              Digital reklama (so'm)
            </label>
            <input
              type="text"
              :value="formatInputNumber(form.spend_digital)"
              @input="form.spend_digital = parseInputNumber($event.target.value)"
              @blur="$event.target.value = formatInputNumber(form.spend_digital)"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="0"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
              Offline reklama (so'm)
            </label>
            <input
              type="text"
              :value="formatInputNumber(form.spend_offline)"
              @input="form.spend_offline = parseInputNumber($event.target.value)"
              @blur="$event.target.value = formatInputNumber(form.spend_offline)"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="0"
            />
          </div>

          <!-- Total Spend -->
          <div class="pt-2 border-t border-gray-200 dark:border-gray-600 mt-auto">
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Jami xarajat:</span>
              <span class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ formatMoney(totalSpend) }}</span>
            </div>
            <div class="flex justify-between items-center mt-2 text-sm text-gray-500 dark:text-gray-400">
              <span>CPL (1 lidga):</span>
              <span>{{ formatMoney(cpl) }}</span>
            </div>
          </div>
        </div>

        <!-- SOTUVLAR Section -->
        <div class="space-y-4">
          <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center">
            <span class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mr-2">
              <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
              </svg>
            </span>
            Sotuvlar
          </h4>

          <div>
            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
              Yangi mijozlar soni
            </label>
            <input
              type="number"
              v-model.number="form.sales_new"
              min="0"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="0"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
              Takroriy mijozlar soni
            </label>
            <input
              type="number"
              v-model.number="form.sales_repeat"
              min="0"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="0"
            />
          </div>

          <!-- Total Sales -->
          <div class="pt-2 border-t border-gray-200 dark:border-gray-600 mt-auto">
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Jami sotuvlar:</span>
              <span class="text-lg font-bold text-green-600 dark:text-green-400">{{ totalSales }}</span>
            </div>
            <div class="flex justify-between items-center mt-2 text-sm text-gray-500 dark:text-gray-400">
              <span>Konversiya:</span>
              <span>{{ conversionRate }}%</span>
            </div>
          </div>
        </div>

        <!-- DAROMAD Section -->
        <div class="space-y-4">
          <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center">
            <span class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mr-2">
              <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </span>
            Daromad
          </h4>

          <div>
            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
              Yangi mijozlardan (so'm)
            </label>
            <input
              type="text"
              :value="formatInputNumber(form.revenue_new)"
              @input="form.revenue_new = parseInputNumber($event.target.value)"
              @blur="$event.target.value = formatInputNumber(form.revenue_new)"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="0"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
              Takroriy mijozlardan (so'm)
            </label>
            <input
              type="text"
              :value="formatInputNumber(form.revenue_repeat)"
              @input="form.revenue_repeat = parseInputNumber($event.target.value)"
              @blur="$event.target.value = formatInputNumber(form.revenue_repeat)"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="0"
            />
          </div>

          <!-- Total Revenue -->
          <div class="pt-2 border-t border-gray-200 dark:border-gray-600 mt-auto">
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Jami daromad:</span>
              <span class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ formatMoney(totalRevenue) }}</span>
            </div>
            <div class="flex justify-between items-center mt-2 text-sm text-gray-500 dark:text-gray-400">
              <span>O'rtacha chek:</span>
              <span>{{ formatMoney(avgCheck) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Notes -->
      <div class="mt-6">
        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
          Izoh (ixtiyoriy)
        </label>
        <textarea
          v-model="form.notes"
          rows="2"
          class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="Bugungi natijalar haqida izoh..."
        ></textarea>
      </div>

      <!-- Summary Card -->
      <div class="mt-6 p-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 rounded-xl">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Jami lidlar</p>
            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ totalLeads }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Jami sotuvlar</p>
            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ totalSales }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Konversiya</p>
            <p class="text-xl font-bold" :class="conversionRate >= 10 ? 'text-green-600' : 'text-yellow-600'">{{ conversionRate }}%</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">ROI</p>
            <p class="text-xl font-bold" :class="roi >= 100 ? 'text-green-600' : roi >= 0 ? 'text-yellow-600' : 'text-red-600'">{{ roi }}%</p>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="mt-6 flex items-center justify-between">
        <div v-if="hasExistingData" class="flex items-center text-sm text-yellow-600 dark:text-yellow-400">
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          Bu sana uchun ma'lumot mavjud. Yangilash amalga oshiriladi.
        </div>
        <div class="flex gap-3 ml-auto">
          <button
            type="button"
            @click="resetForm"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            Tozalash
          </button>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
          >
            <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ isSubmitting ? 'Saqlanmoqda...' : 'Saqlash' }}
          </button>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  businessId: {
    type: [String, Number],
    required: true
  }
});

const emit = defineEmits(['saved', 'error']);

// State - faqat bugungi kun uchun
const today = new Date().toISOString().split('T')[0];
const isSubmitting = ref(false);
const hasExistingData = ref(false);

// Formatlangan bugungi sana
const formattedToday = computed(() => {
  const date = new Date(today);
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  return date.toLocaleDateString('uz-UZ', options);
});

const form = ref({
  leads_digital: 0,
  leads_offline: 0,
  leads_referral: 0,
  leads_organic: 0,
  spend_digital: 0,
  spend_offline: 0,
  sales_new: 0,
  sales_repeat: 0,
  revenue_new: 0,
  revenue_repeat: 0,
  notes: ''
});

// Helper to ensure number
const toNumber = (val) => {
  if (typeof val === 'number') return val;
  if (typeof val === 'string') return parseFloat(val.replace(/[^\d.-]/g, '')) || 0;
  return 0;
};

// Computed
const totalLeads = computed(() => {
  return toNumber(form.value.leads_digital) +
         toNumber(form.value.leads_offline) +
         toNumber(form.value.leads_referral) +
         toNumber(form.value.leads_organic);
});

const totalSpend = computed(() => {
  return toNumber(form.value.spend_digital) + toNumber(form.value.spend_offline);
});

const totalSales = computed(() => {
  return toNumber(form.value.sales_new) + toNumber(form.value.sales_repeat);
});

const totalRevenue = computed(() => {
  return toNumber(form.value.revenue_new) + toNumber(form.value.revenue_repeat);
});

const cpl = computed(() => {
  if (totalLeads.value === 0 || totalSpend.value === 0) return 0;
  return Math.round(totalSpend.value / totalLeads.value);
});

const conversionRate = computed(() => {
  if (totalLeads.value === 0) return '0.0';
  return ((totalSales.value / totalLeads.value) * 100).toFixed(1);
});

const avgCheck = computed(() => {
  if (totalSales.value === 0) return 0;
  return Math.round(totalRevenue.value / totalSales.value);
});

const roi = computed(() => {
  if (totalSpend.value === 0) {
    return totalRevenue.value > 0 ? '999' : '0';
  }
  return (((totalRevenue.value - totalSpend.value) / totalSpend.value) * 100).toFixed(0);
});

// Methods
const formatMoney = (value) => {
  const num = toNumber(value);
  if (!num || isNaN(num)) return "0 so'm";
  return new Intl.NumberFormat('ru-RU').format(num) + " so'm";
};

const formatInputNumber = (value) => {
  const num = toNumber(value);
  if (!num || num === 0) return '';
  return new Intl.NumberFormat('ru-RU').format(num);
};

const parseInputNumber = (value) => {
  if (!value) return 0;
  // Remove all non-digit characters except minus
  const cleaned = String(value).replace(/[^\d-]/g, '');
  return parseInt(cleaned, 10) || 0;
};

const loadExistingData = async () => {
  if (!props.businessId) {
    console.error('businessId is undefined');
    return;
  }
  try {
    const response = await axios.get(`/api/v1/businesses/${props.businessId}/kpi-entry/quick-entry`, {
      params: { date: today }
    });

    if (response.data.success && response.data.data.entry) {
      const entry = response.data.data.entry;
      hasExistingData.value = true;

      form.value = {
        leads_digital: entry.leads_digital || 0,
        leads_offline: entry.leads_offline || 0,
        leads_referral: entry.leads_referral || 0,
        leads_organic: entry.leads_organic || 0,
        spend_digital: entry.spend_digital || 0,
        spend_offline: entry.spend_offline || 0,
        sales_new: entry.sales_new || 0,
        sales_repeat: entry.sales_repeat || 0,
        revenue_new: entry.revenue_new || 0,
        revenue_repeat: entry.revenue_repeat || 0,
        notes: entry.notes || ''
      };
    } else {
      hasExistingData.value = false;
      resetForm();
    }
  } catch (error) {
    console.error('Error loading data:', error);
  }
};

const resetForm = () => {
  form.value = {
    leads_digital: 0,
    leads_offline: 0,
    leads_referral: 0,
    leads_organic: 0,
    spend_digital: 0,
    spend_offline: 0,
    sales_new: 0,
    sales_repeat: 0,
    revenue_new: 0,
    revenue_repeat: 0,
    notes: ''
  };
};

const submitForm = async () => {
  if (!props.businessId) {
    emit('error', 'Biznes topilmadi');
    return;
  }
  isSubmitting.value = true;

  try {
    // Ensure all values are numbers before sending
    const payload = {
      date: today,
      leads_digital: toNumber(form.value.leads_digital),
      leads_offline: toNumber(form.value.leads_offline),
      leads_referral: toNumber(form.value.leads_referral),
      leads_organic: toNumber(form.value.leads_organic),
      spend_digital: toNumber(form.value.spend_digital),
      spend_offline: toNumber(form.value.spend_offline),
      sales_new: toNumber(form.value.sales_new),
      sales_repeat: toNumber(form.value.sales_repeat),
      revenue_new: toNumber(form.value.revenue_new),
      revenue_repeat: toNumber(form.value.revenue_repeat),
      notes: form.value.notes
    };

    const response = await axios.post(`/api/v1/businesses/${props.businessId}/kpi-entry/quick-entry`, payload);

    if (response.data.success) {
      emit('saved', response.data.data);
      // Saqlangandan keyin formani tozalash va keyingi kunga o'tkazish
      hasExistingData.value = true;
      // Muvaffaqiyatli saqlangandan keyin formani qayta yuklaymiz (yangilangan ma'lumotlarni ko'rsatish uchun)
    }
  } catch (error) {
    console.error('Error saving:', error);
    emit('error', error.response?.data?.message || 'Xatolik yuz berdi');
  } finally {
    isSubmitting.value = false;
  }
};

// Lifecycle
onMounted(() => {
  loadExistingData();
});
</script>
