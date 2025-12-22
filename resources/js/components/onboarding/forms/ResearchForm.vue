<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="text-center">
      <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-indigo-400 to-purple-400 flex items-center justify-center">
        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
      <h3 class="text-xl font-bold text-gray-900 mb-2">Bozor Tadqiqoti</h3>
      <p class="text-gray-600">Bozorni o'rganish va mijozlarni tushunish</p>
    </div>

    <!-- Research Methods -->
    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-6">
      <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
        </svg>
        Tadqiqot usullari
      </h4>
      <p class="text-sm text-gray-600 mb-4">Quyidagi tadqiqot usullarini qo'llang va natijalarni belgilang:</p>

      <div class="space-y-3">
        <div
          v-for="method in researchMethods"
          :key="method.value"
          @click="toggleMethod(method.value)"
          :class="[
            'flex items-start gap-3 p-4 rounded-lg bg-white border cursor-pointer transition-all',
            isMethodSelected(method.value)
              ? 'border-indigo-500 ring-2 ring-indigo-100'
              : 'border-gray-200 hover:border-gray-300'
          ]"
        >
          <div :class="[
            'w-5 h-5 rounded border-2 flex items-center justify-center flex-shrink-0 mt-0.5',
            isMethodSelected(method.value)
              ? 'bg-indigo-600 border-indigo-600'
              : 'border-gray-300'
          ]">
            <svg v-if="isMethodSelected(method.value)" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="flex-1">
            <span class="font-medium text-gray-900">{{ method.label }}</span>
            <p class="text-sm text-gray-500 mt-1">{{ method.description }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Research Progress -->
    <div class="p-4 bg-indigo-50 rounded-xl">
      <div class="flex justify-between text-sm mb-2">
        <span class="font-medium text-indigo-900">Tadqiqot usullari bajarildi</span>
        <span class="font-bold text-indigo-600">{{ selectedMethodsCount }}/{{ researchMethods.length }}</span>
      </div>
      <div class="h-2 bg-indigo-200 rounded-full overflow-hidden">
        <div
          class="h-full bg-indigo-600 rounded-full transition-all"
          :style="{ width: `${(selectedMethodsCount / researchMethods.length) * 100}%` }"
        ></div>
      </div>
    </div>

    <!-- Key Findings -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Asosiy topilmalar (ixtiyoriy)
      </label>
      <textarea
        v-model="form.key_findings"
        rows="4"
        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
        placeholder="Tadqiqot natijasida nimalarni aniqladingiz? Qanday muhim ma'lumotlar topildi?"
      ></textarea>
    </div>

    <!-- Target Market Notes -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Maqsadli bozor haqida eslatmalar (ixtiyoriy)
      </label>
      <textarea
        v-model="form.target_market_notes"
        rows="3"
        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
        placeholder="Maqsadli bozor haqida qanday xulosalar chiqardingiz?"
      ></textarea>
    </div>

    <!-- Resources Section -->
    <div class="bg-gray-50 rounded-xl p-6">
      <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
        Foydali manbalar
      </h4>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <a
          v-for="resource in resources"
          :key="resource.url"
          :href="resource.url"
          target="_blank"
          class="flex items-center gap-3 p-3 bg-white rounded-lg border border-gray-200 hover:border-indigo-300 hover:shadow-sm transition-all"
        >
          <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
          </div>
          <div>
            <span class="font-medium text-gray-900 text-sm">{{ resource.name }}</span>
            <p class="text-xs text-gray-500">{{ resource.description }}</p>
          </div>
        </a>
      </div>
    </div>

    <!-- Tips Section -->
    <div class="border border-amber-200 bg-amber-50 rounded-xl p-4">
      <div class="flex gap-3">
        <svg class="w-6 h-6 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
        </svg>
        <div>
          <h5 class="font-medium text-amber-900 mb-1">Maslahat</h5>
          <p class="text-sm text-amber-800">
            Tadqiqot - bu doimiy jarayon. Hozircha asosiy ma'lumotlarni yig'ing,
            keyinchalik chuqurroq tahlil qilishingiz mumkin. Hatto kichik ma'lumotlar ham qimmatli!
          </p>
        </div>
      </div>
    </div>

    <!-- Info text -->
    <p class="text-sm text-gray-500 text-center">
      Bu bosqich ixtiyoriy. Keyinroq qaytib to'ldirishingiz mumkin.
    </p>

    <!-- Action Buttons -->
    <div class="flex justify-between gap-3 pt-4">
      <button
        type="button"
        @click="$emit('skip')"
        class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors"
      >
        O'tkazib yuborish
      </button>
      <div class="flex gap-3">
        <button
          type="button"
          @click="$emit('cancel')"
          class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors"
        >
          Bekor qilish
        </button>
        <button
          @click="handleSubmit"
          :disabled="loading"
          class="px-6 py-3 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <svg v-if="loading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Saqlash
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useToastStore } from '@/stores/toast';

const toast = useToastStore();
const emit = defineEmits(['submit', 'cancel', 'skip']);

const loading = ref(false);

const form = reactive({
  completed_methods: [],
  key_findings: '',
  target_market_notes: ''
});

// Simple toggle functions - NO computed getters!
function isMethodSelected(value) {
  return Array.isArray(form.completed_methods) && form.completed_methods.includes(value);
}

function toggleMethod(value) {
  if (!Array.isArray(form.completed_methods)) {
    form.completed_methods = [];
  }
  const index = form.completed_methods.indexOf(value);
  if (index === -1) {
    form.completed_methods.push(value);
  } else {
    form.completed_methods.splice(index, 1);
  }
}

// Computed property for count only (safe - no setter)
const selectedMethodsCount = computed(() => {
  return Array.isArray(form.completed_methods) ? form.completed_methods.length : 0;
});

const researchMethods = [
  {
    value: 'customer_interviews',
    label: 'Mijozlar bilan suhbat',
    description: 'Hozirgi yoki potentsial mijozlar bilan to\'g\'ridan-to\'g\'ri gaplashing'
  },
  {
    value: 'surveys',
    label: 'So\'rovnomalar',
    description: 'Telegram, Google Forms orqali so\'rovnoma o\'tkazing'
  },
  {
    value: 'competitor_analysis',
    label: 'Raqobatchilar tahlili',
    description: 'Raqobatchilarning veb-saytlari, ijtimoiy tarmoqlarini o\'rganing'
  },
  {
    value: 'social_media_research',
    label: 'Ijtimoiy tarmoq tadqiqoti',
    description: 'Telegram guruhlar, Instagram, Facebook\'da auditoriyani o\'rganing'
  },
  {
    value: 'market_data',
    label: 'Bozor statistikasi',
    description: 'Sohangiz bo\'yicha statistik ma\'lumotlarni yig\'ing'
  },
  {
    value: 'trend_analysis',
    label: 'Trendlar tahlili',
    description: 'Google Trends, sohaviy hisobotlarni o\'rganing'
  }
];

const resources = [
  {
    name: 'Google Trends',
    url: 'https://trends.google.com',
    description: 'Qidiruv trendlari'
  },
  {
    name: 'Statista',
    url: 'https://www.statista.com',
    description: 'Bozor statistikasi'
  },
  {
    name: 'SimilarWeb',
    url: 'https://www.similarweb.com',
    description: 'Raqobatchi tahlili'
  },
  {
    name: 'Google Forms',
    url: 'https://forms.google.com',
    description: 'So\'rovnomalar yaratish'
  }
];

async function handleSubmit() {
  loading.value = true;

  try {
    // Since there's no backend API for research yet,
    // we just emit submit to close the modal
    // In the future, this could save to localStorage or a backend API
    toast.success('Muvaffaqiyatli saqlandi', 'Tadqiqot ma\'lumotlari yangilandi');
    emit('submit');
  } catch (err) {
    console.error(err);
    const errorMessage = err.response?.data?.message || 'Ma\'lumotlarni saqlashda xatolik yuz berdi';
    toast.error('Xatolik', errorMessage);
  } finally {
    loading.value = false;
  }
}
</script>
