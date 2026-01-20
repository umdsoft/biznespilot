<template>
  <SalesHeadLayout title="KPI Sozlash">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-12">
        <div class="w-20 h-20 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center mx-auto mb-6">
          <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-3">
          KPI Tizimini Sozlash
        </h1>
        <p class="text-gray-600 dark:text-gray-400 max-w-xl mx-auto">
          Sotuv jamoangiz uchun professional KPI, Bonus va Jarima tizimini bir necha daqiqada sozlang.
          Tayyor shablonlardan birini tanlang yoki o'zingizning sozlamalaringizni yarating.
        </p>
      </div>

      <!-- Progress (if exists) -->
      <div v-if="progress && progress.status === 'in_progress'" class="mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 dark:text-white">Sozlash jarayoni</h3>
            <span class="text-sm text-emerald-600 font-medium">{{ progress.progress_percent }}%</span>
          </div>
          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-4">
            <div class="bg-emerald-500 h-2 rounded-full transition-all" :style="{ width: progress.progress_percent + '%' }"></div>
          </div>
          <div class="flex flex-wrap gap-2">
            <span v-for="(step, code) in steps" :key="code"
                  :class="[
                    'px-3 py-1 rounded-full text-xs font-medium',
                    progress.completed_steps?.includes(code)
                      ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                      : progress.current_step === code
                        ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                        : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                  ]">
              {{ step.name }}
            </span>
          </div>
        </div>
      </div>

      <!-- Templates Grid -->
      <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
          Shablonni tanlang
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div v-for="template in templates" :key="template.id"
               @click="selectedTemplate = template.id"
               :class="[
                 'relative bg-white dark:bg-gray-800 rounded-xl border-2 p-6 cursor-pointer transition-all hover:shadow-lg',
                 selectedTemplate === template.id
                   ? 'border-emerald-500 ring-2 ring-emerald-500/20'
                   : 'border-gray-200 dark:border-gray-700 hover:border-emerald-300'
               ]">
            <!-- Featured Badge -->
            <div v-if="template.is_featured" class="absolute -top-2 -right-2">
              <span class="bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg">
                Tavsiya
              </span>
            </div>

            <!-- Selection Indicator -->
            <div v-if="selectedTemplate === template.id" class="absolute top-4 right-4">
              <div class="w-6 h-6 bg-emerald-500 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </div>
            </div>

            <!-- Icon -->
            <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-4 text-2xl"
                 :class="getTemplateIconBg(template.industry)">
              {{ template.icon || getDefaultIcon(template.industry) }}
            </div>

            <!-- Content -->
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
              {{ template.name }}
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
              {{ template.description }}
            </p>

            <!-- Stats -->
            <div class="flex flex-wrap gap-3">
              <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                {{ template.kpi_count }} KPI
              </span>
              <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ template.bonus_count }} Bonus
              </span>
              <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                {{ template.penalty_rules_count }} Qoida
              </span>
            </div>

            <!-- Industry Label -->
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
              <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ template.industry_label }}
              </span>
              <span v-if="template.usage_count > 0" class="text-xs text-gray-400 ml-2">
                • {{ template.usage_count }} marta ishlatilgan
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Apply Button -->
      <div class="flex justify-center">
        <button
          @click="applyTemplate"
          :disabled="!selectedTemplate || isLoading"
          :class="[
            'px-8 py-4 rounded-xl font-semibold text-lg transition-all transform',
            selectedTemplate && !isLoading
              ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white hover:from-emerald-600 hover:to-teal-700 hover:scale-105 shadow-lg hover:shadow-xl'
              : 'bg-gray-200 text-gray-500 cursor-not-allowed dark:bg-gray-700 dark:text-gray-400'
          ]"
        >
          <span v-if="isLoading" class="flex items-center">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Sozlanmoqda...
          </span>
          <span v-else class="flex items-center">
            Shablonni qo'llash
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </span>
        </button>
      </div>

      <!-- Info Box -->
      <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
        <div class="flex">
          <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="ml-4">
            <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-1">Shablonni keyinroq o'zgartirish mumkin</h4>
            <p class="text-sm text-blue-700 dark:text-blue-300">
              Tanlangan shablon sizning biznesingiz uchun boshlang'ich nuqta bo'ladi.
              Barcha KPI sozlamalari, bonus va jarima qoidalarini keyinroq "Sozlamalar" bo'limida o'zgartirishingiz mumkin.
            </p>
          </div>
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
  progress: Object,
  templates: Array,
  steps: Object,
  industries: Object,
  panelType: String,
});

const selectedTemplate = ref(null);
const isLoading = ref(false);

const getTemplateIconBg = (industry) => {
  const colors = {
    it_services: 'bg-blue-100 dark:bg-blue-900/30',
    retail: 'bg-emerald-100 dark:bg-emerald-900/30',
    wholesale: 'bg-purple-100 dark:bg-purple-900/30',
    education: 'bg-yellow-100 dark:bg-yellow-900/30',
    real_estate: 'bg-orange-100 dark:bg-orange-900/30',
    finance: 'bg-green-100 dark:bg-green-900/30',
    healthcare: 'bg-red-100 dark:bg-red-900/30',
    manufacturing: 'bg-gray-100 dark:bg-gray-900/30',
    hospitality: 'bg-pink-100 dark:bg-pink-900/30',
    other: 'bg-indigo-100 dark:bg-indigo-900/30',
  };
  return colors[industry] || colors.other;
};

const getDefaultIcon = (industry) => {
  const icons = {
    it_services: '💻',
    retail: '🛒',
    wholesale: '📦',
    education: '🎓',
    real_estate: '🏠',
    finance: '💰',
    healthcare: '🏥',
    manufacturing: '🏭',
    hospitality: '🏨',
    other: '📊',
  };
  return icons[industry] || icons.other;
};

const applyTemplate = () => {
  if (!selectedTemplate.value) return;

  isLoading.value = true;
  router.post('/sales-head/sales-kpi/apply-template', {
    template_id: selectedTemplate.value,
  }, {
    onFinish: () => {
      isLoading.value = false;
    },
  });
};
</script>
