<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
    <!-- Header -->
    <header class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <Link :href="isAdditionalBusiness ? '/business' : '/welcome'" class="flex items-center space-x-3 group">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-blue-500/25 transition-shadow">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <span class="text-xl font-bold text-gray-900 dark:text-white">BiznesPilot AI</span>
          </Link>
          <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-600 dark:text-gray-300">{{ $page.props.auth?.user?.name }}</span>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="py-8 md:py-12">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <Link
          :href="isAdditionalBusiness ? '/business' : '/welcome'"
          class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-6 transition-colors"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          {{ t('common.back') }}
        </Link>

        <!-- Form Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl dark:shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <!-- Header -->
          <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 md:px-8 py-6">
            <div class="flex items-center space-x-3">
              <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>
              <div>
                <h1 class="text-2xl font-bold text-white">{{ t('create_business.title') }}</h1>
                <p class="text-blue-100 mt-0.5">{{ t('create_business.subtitle') }}</p>
              </div>
            </div>
          </div>

          <!-- Form -->
          <form @submit.prevent="submit" class="p-6 md:p-8 space-y-6">
            <!-- Error Messages -->
            <div v-if="Object.keys(form.errors).length > 0" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
              <div class="flex items-start">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-red-800 dark:text-red-300">
                  <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
                </div>
              </div>
            </div>

            <!-- Business Name -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                {{ t('create_business.name') }} <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.name"
                type="text"
                :placeholder="t('create_business.name_placeholder')"
                class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                :class="{ 'border-red-300 dark:border-red-600': form.errors.name }"
                required
              />
            </div>

            <!-- Business Category -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                {{ t('create_business.category') }} <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.category"
                class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                :class="{ 'border-red-300 dark:border-red-600': form.errors.category }"
                required
              >
                <option value="" class="dark:bg-gray-900">{{ t('create_business.category_select') }}</option>
                <option v-for="category in businessCategories" :key="category.value" :value="category.value" class="dark:bg-gray-900">
                  {{ category.label }}
                </option>
              </select>
              <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ t('create_business.category_hint') }}</p>
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                {{ t('create_business.description') }}
              </label>
              <textarea
                v-model="form.description"
                rows="3"
                :placeholder="t('create_business.description_placeholder')"
                class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"
              ></textarea>
            </div>

            <!-- Region & Website -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                  {{ t('create_business.region') }} <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.region"
                  class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                  :class="{ 'border-red-300 dark:border-red-600': form.errors.region }"
                  required
                >
                  <option value="" class="dark:bg-gray-900">{{ t('create_business.region_select') }}</option>
                  <option v-for="region in regions" :key="region.value" :value="region.value" class="dark:bg-gray-900">
                    {{ region.label }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                  {{ t('create_business.website') }}
                </label>
                <input
                  v-model="form.website"
                  type="url"
                  :placeholder="t('create_business.website_placeholder')"
                  class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                />
              </div>
            </div>

            <!-- Business Size -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                  {{ t('create_business.employee_count') }}
                </label>
                <select
                  v-model="form.employee_count"
                  class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                >
                  <option value="" class="dark:bg-gray-900">{{ t('create_business.select') }}</option>
                  <option value="1" class="dark:bg-gray-900">{{ t('create_business.employee_1') }}</option>
                  <option value="2-5" class="dark:bg-gray-900">{{ t('create_business.employee_2_5') }}</option>
                  <option value="6-10" class="dark:bg-gray-900">{{ t('create_business.employee_6_10') }}</option>
                  <option value="11-50" class="dark:bg-gray-900">{{ t('create_business.employee_11_50') }}</option>
                  <option value="51-200" class="dark:bg-gray-900">{{ t('create_business.employee_51_200') }}</option>
                  <option value="200+" class="dark:bg-gray-900">{{ t('create_business.employee_200_plus') }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                  {{ t('create_business.monthly_revenue') }}
                </label>
                <select
                  v-model="form.monthly_revenue"
                  class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                >
                  <option value="" class="dark:bg-gray-900">{{ t('create_business.select') }}</option>
                  <option value="0-10m" class="dark:bg-gray-900">{{ t('create_business.revenue_0_10m') }}</option>
                  <option value="10-50m" class="dark:bg-gray-900">{{ t('create_business.revenue_10_50m') }}</option>
                  <option value="50-100m" class="dark:bg-gray-900">{{ t('create_business.revenue_50_100m') }}</option>
                  <option value="100-500m" class="dark:bg-gray-900">{{ t('create_business.revenue_100_500m') }}</option>
                  <option value="500m+" class="dark:bg-gray-900">{{ t('create_business.revenue_500m_plus') }}</option>
                </select>
              </div>
            </div>

            <!-- Target Audience -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                {{ t('create_business.target_audience') }}
              </label>
              <textarea
                v-model="form.target_audience"
                rows="2"
                :placeholder="t('create_business.target_audience_placeholder')"
                class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"
              ></textarea>
            </div>

            <!-- Main Goals -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                {{ t('create_business.main_goals') }} <span class="text-red-500">*</span>
              </label>
              <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">{{ t('create_business.main_goals_hint') }}</p>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label
                  v-for="goal in availableGoals"
                  :key="goal.value"
                  class="flex items-center p-4 border rounded-xl cursor-pointer transition-all"
                  :class="[
                    form.main_goals.includes(goal.value)
                      ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-500'
                      : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 bg-white dark:bg-gray-900',
                    form.errors.main_goals ? 'border-red-300 dark:border-red-600' : ''
                  ]"
                >
                  <input
                    type="checkbox"
                    :value="goal.value"
                    v-model="form.main_goals"
                    class="w-4 h-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 bg-white dark:bg-gray-800"
                  />
                  <div class="ml-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ goal.label }}</span>
                  </div>
                </label>
              </div>
              <p v-if="form.errors.main_goals" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.main_goals }}</p>
            </div>

            <!-- Submit Button -->
            <div class="pt-4">
              <button
                type="submit"
                :disabled="form.processing"
                class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold py-4 px-6 rounded-xl hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transform transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl hover:shadow-blue-500/25"
              >
                <span v-if="!form.processing" class="flex items-center justify-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                  </svg>
                  {{ t('create_business.submit') }}
                </span>
                <span v-else class="flex items-center justify-center">
                  <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ t('create_business.creating') }}
                </span>
              </button>
            </div>

            <!-- Help Text -->
            <p class="text-center text-xs text-gray-500 dark:text-gray-400 pt-2">
              {{ t('create_business.help_text') }}
            </p>
          </form>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
  isAdditionalBusiness: {
    type: Boolean,
    default: false,
  },
});

const form = useForm({
  name: '',
  category: '',
  description: '',
  website: '',
  region: '',
  employee_count: '',
  monthly_revenue: '',
  target_audience: '',
  main_goals: [],
});

// Biznes kategoriyalari
const businessCategories = [
  { value: 'retail', label: 'Chakana savdo (Do\'konlar, supermarketlar)' },
  { value: 'wholesale', label: 'Ulgurji savdo' },
  { value: 'ecommerce', label: 'Onlayn savdo (E-commerce)' },
  { value: 'food_service', label: 'Oziq-ovqat xizmati (Restoranlar, kafelar)' },
  { value: 'manufacturing', label: 'Ishlab chiqarish' },
  { value: 'construction', label: 'Qurilish va ta\'mirlash' },
  { value: 'it_services', label: 'IT xizmatlari va dasturlash' },
  { value: 'education', label: 'Ta\'lim va o\'quv markazlari' },
  { value: 'healthcare', label: 'Sog\'liqni saqlash va tibbiyot' },
  { value: 'beauty_wellness', label: 'Go\'zallik va salomatlik (Salonlar, SPA)' },
  { value: 'real_estate', label: 'Ko\'chmas mulk' },
  { value: 'transportation', label: 'Transport va logistika' },
  { value: 'agriculture', label: 'Qishloq xo\'jaligi' },
  { value: 'tourism', label: 'Turizm va mehmonxonalar' },
  { value: 'finance', label: 'Moliya va sug\'urta' },
  { value: 'consulting', label: 'Konsalting va biznes xizmatlari' },
  { value: 'marketing_agency', label: 'Marketing va reklama agentligi' },
  { value: 'media', label: 'Media va ko\'ngilochar sanoat' },
  { value: 'fitness', label: 'Sport va fitness' },
  { value: 'automotive', label: 'Avtomobil xizmatlari' },
  { value: 'textile', label: 'To\'qimachilik va kiyim-kechak' },
  { value: 'furniture', label: 'Mebel ishlab chiqarish va savdosi' },
  { value: 'electronics', label: 'Elektronika va texnika' },
  { value: 'cleaning', label: 'Tozalash xizmatlari' },
  { value: 'event_services', label: 'Tadbirlar va to\'yxonalar' },
  { value: 'legal', label: 'Yuridik xizmatlar' },
  { value: 'other', label: 'Boshqa' },
];

// O'zbekiston viloyatlari
const regions = [
  { value: 'toshkent_shahar', label: 'Toshkent shahri' },
  { value: 'toshkent_viloyati', label: 'Toshkent viloyati' },
  { value: 'andijon', label: 'Andijon viloyati' },
  { value: 'buxoro', label: 'Buxoro viloyati' },
  { value: 'fargona', label: 'Farg\'ona viloyati' },
  { value: 'jizzax', label: 'Jizzax viloyati' },
  { value: 'xorazm', label: 'Xorazm viloyati' },
  { value: 'namangan', label: 'Namangan viloyati' },
  { value: 'navoiy', label: 'Navoiy viloyati' },
  { value: 'qashqadaryo', label: 'Qashqadaryo viloyati' },
  { value: 'samarqand', label: 'Samarqand viloyati' },
  { value: 'sirdaryo', label: 'Sirdaryo viloyati' },
  { value: 'surxondaryo', label: 'Surxondaryo viloyati' },
  { value: 'qoraqalpogiston', label: 'Qoraqalpog\'iston Respublikasi' },
];

const availableGoals = [
  { value: 'increase_sales', label: 'Sotuvlarni oshirish' },
  { value: 'get_customers', label: 'Yangi mijozlar topish' },
  { value: 'brand_awareness', label: 'Brend taniqliligini oshirish' },
  { value: 'automate_marketing', label: 'Marketingni avtomatlashtirish' },
  { value: 'improve_service', label: 'Mijozlarga xizmatni yaxshilash' },
  { value: 'expand_market', label: 'Yangi bozorlarga chiqish' },
  { value: 'reduce_costs', label: 'Xarajatlarni kamaytirish' },
  { value: 'analyze_competitors', label: 'Raqobatchilarni tahlil qilish' },
];

const submit = () => {
  // Validate main_goals before submit
  if (form.main_goals.length === 0) {
    form.setError('main_goals', 'Kamida bitta maqsad tanlang');
    return;
  }
  // Use different route based on whether it's first or additional business
  const url = props.isAdditionalBusiness ? '/new-business' : '/welcome/create-business';
  form.post(url);
};
</script>
