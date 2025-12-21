<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <Link href="/welcome" class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <span class="text-xl font-bold text-gray-900">BiznesPilot AI</span>
          </Link>
          <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-600">{{ $page.props.auth?.user?.name }}</span>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="py-12">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <Link
          href="/welcome"
          class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Orqaga
        </Link>

        <!-- Form Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
          <!-- Header -->
          <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-6">
            <h1 class="text-2xl font-bold text-white">Yangi biznes yaratish</h1>
            <p class="text-blue-100 mt-1">Biznesingiz haqida asosiy ma'lumotlarni kiriting</p>
          </div>

          <!-- Form -->
          <form @submit.prevent="submit" class="p-8 space-y-6">
            <!-- Error Messages -->
            <div v-if="Object.keys(form.errors).length > 0" class="bg-red-50 border border-red-200 rounded-lg p-4">
              <div class="flex items-start">
                <svg class="w-5 h-5 text-red-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-red-800">
                  <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
                </div>
              </div>
            </div>

            <!-- Business Name -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Biznes nomi <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.name"
                type="text"
                placeholder="Masalan: TechStart"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                :class="{ 'border-red-300': form.errors.name }"
                required
              />
            </div>

            <!-- Business Category -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Biznes kategoriyasi <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.category"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                :class="{ 'border-red-300': form.errors.category }"
                required
              >
                <option value="">Kategoriyani tanlang...</option>
                <option v-for="category in businessCategories" :key="category.value" :value="category.value">
                  {{ category.label }}
                </option>
              </select>
              <p class="mt-1 text-xs text-gray-500">AI sizning kategoriyangizga mos tavsiyalar beradi</p>
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Biznes haqida qisqacha
              </label>
              <textarea
                v-model="form.description"
                rows="3"
                placeholder="Biznesingiz nima bilan shug'ullanadi?"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"
              ></textarea>
            </div>

            <!-- Region & Website -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Qaysi viloyatdansiz? <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.region"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                  :class="{ 'border-red-300': form.errors.region }"
                  required
                >
                  <option value="">Viloyatni tanlang...</option>
                  <option v-for="region in regions" :key="region.value" :value="region.value">
                    {{ region.label }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Veb-sayt
                </label>
                <input
                  v-model="form.website"
                  type="url"
                  placeholder="https://biznes.uz"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                />
              </div>
            </div>

            <!-- Business Size -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Xodimlar soni
                </label>
                <select
                  v-model="form.employee_count"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                >
                  <option value="">Tanlang...</option>
                  <option value="1">Faqat men</option>
                  <option value="2-5">2-5 kishi</option>
                  <option value="6-10">6-10 kishi</option>
                  <option value="11-50">11-50 kishi</option>
                  <option value="51-200">51-200 kishi</option>
                  <option value="200+">200+ kishi</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Oylik daromad (taxminan)
                </label>
                <select
                  v-model="form.monthly_revenue"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                >
                  <option value="">Tanlang...</option>
                  <option value="0-10m">0 - 10 mln so'm</option>
                  <option value="10-50m">10 - 50 mln so'm</option>
                  <option value="50-100m">50 - 100 mln so'm</option>
                  <option value="100-500m">100 - 500 mln so'm</option>
                  <option value="500m+">500 mln+ so'm</option>
                </select>
              </div>
            </div>

            <!-- Target Audience -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Maqsadli auditoriya
              </label>
              <textarea
                v-model="form.target_audience"
                rows="2"
                placeholder="Kimlar sizning mijozlaringiz? (Masalan: 25-45 yoshli tadbirkorlar)"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"
              ></textarea>
            </div>

            <!-- Main Goals -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Asosiy maqsadlaringiz <span class="text-red-500">*</span>
              </label>
              <p class="text-xs text-gray-500 mb-3">Kamida bitta maqsad tanlang (bir nechta tanlash mumkin)</p>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label
                  v-for="goal in availableGoals"
                  :key="goal.value"
                  class="flex items-center p-3 border rounded-lg cursor-pointer transition-all"
                  :class="[
                    form.main_goals.includes(goal.value) ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300',
                    form.errors.main_goals ? 'border-red-300' : ''
                  ]"
                >
                  <input
                    type="checkbox"
                    :value="goal.value"
                    v-model="form.main_goals"
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                  />
                  <span class="ml-3 text-sm text-gray-700">{{ goal.label }}</span>
                </label>
              </div>
              <p v-if="form.errors.main_goals" class="mt-2 text-sm text-red-600">{{ form.errors.main_goals }}</p>
            </div>

            <!-- Submit Button -->
            <div class="pt-4">
              <button
                type="submit"
                :disabled="form.processing"
                class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold py-4 px-6 rounded-xl hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl"
              >
                <span v-if="!form.processing" class="flex items-center justify-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                  </svg>
                  Biznesni yaratish
                </span>
                <span v-else class="flex items-center justify-center">
                  <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Yaratilmoqda...
                </span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';

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
  form.post('/welcome/create-business');
};
</script>
