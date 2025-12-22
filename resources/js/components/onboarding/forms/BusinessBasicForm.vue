<template>
  <form @submit.prevent="handleSubmit" class="space-y-5">
    <!-- Row 1: Business Name -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">
        Biznes nomi
      </label>
      <input
        v-model="form.name"
        type="text"
        class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
        placeholder="Masalan: TechnoMarket"
        :class="{ 'border-red-500': errors.name }"
      />
      <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name }}</p>
    </div>

    <!-- Row 2: Category -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">
        Biznes kategoriyasi
      </label>
      <select
        v-model="form.category"
        class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
        :class="{ 'border-red-500': errors.category }"
      >
        <option value="">Kategoriyani tanlang</option>
        <option
          v-for="cat in businessCategories"
          :key="cat.value"
          :value="cat.value"
        >
          {{ cat.label }}
        </option>
      </select>
      <p v-if="errors.category" class="mt-1 text-xs text-red-500">{{ errors.category }}</p>
    </div>

    <!-- Row 3: Description -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">
        Qisqacha tavsif
      </label>
      <textarea
        v-model="form.description"
        rows="2"
        class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm resize-none"
        placeholder="Biznesingiz nima bilan shug'ullanadi?"
      ></textarea>
    </div>

    <!-- Row 4: Business Type -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Biznes turi
      </label>
      <div class="grid grid-cols-4 gap-2">
        <label
          v-for="type in businessTypes"
          :key="type.value"
          :class="[
            'relative flex flex-col items-center p-3 rounded-lg border-2 cursor-pointer transition-all text-center',
            form.business_type === type.value
              ? 'border-indigo-500 bg-indigo-50'
              : 'border-gray-200 hover:border-gray-300'
          ]"
        >
          <input type="radio" v-model="form.business_type" :value="type.value" class="sr-only" />
          <span class="text-sm font-semibold text-gray-900">{{ type.label }}</span>
          <span class="text-xs text-gray-500">{{ type.description }}</span>
        </label>
      </div>
      <p v-if="errors.business_type" class="mt-1 text-xs text-red-500">{{ errors.business_type }}</p>
    </div>

    <!-- Row 5: Business Model -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Biznes modeli
      </label>
      <div class="grid grid-cols-5 gap-2">
        <label
          v-for="model in businessModels"
          :key="model.value"
          :class="[
            'relative flex flex-col items-center p-3 rounded-lg border-2 cursor-pointer transition-all text-center',
            form.business_model === model.value
              ? 'border-indigo-500 bg-indigo-50'
              : 'border-gray-200 hover:border-gray-300'
          ]"
        >
          <input type="radio" v-model="form.business_model" :value="model.value" class="sr-only" />
          <span class="text-sm font-semibold text-gray-900">{{ model.label }}</span>
          <span class="text-xs text-gray-500">{{ model.description }}</span>
        </label>
      </div>
      <p v-if="errors.business_model" class="mt-1 text-xs text-red-500">{{ errors.business_model }}</p>
    </div>

    <!-- Info text -->
    <p class="text-sm text-gray-500 text-center">
      Barcha maydonlar ixtiyoriy. Keyinroq to'ldirishingiz mumkin.
    </p>

    <!-- Submit -->
    <div class="flex justify-between gap-3 pt-2">
      <button
        type="button"
        @click="handleSkip"
        class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors"
      >
        O'tkazib yuborish
      </button>
      <div class="flex gap-3">
        <button
          type="button"
          @click="$emit('cancel')"
          class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors"
        >
          Bekor qilish
        </button>
        <button
          type="submit"
          :disabled="loading"
          class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <svg v-if="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Saqlash
        </button>
      </div>
    </div>
  </form>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { useOnboardingStore } from '@/stores/onboarding';
import { useToastStore } from '@/stores/toast';

const store = useOnboardingStore();
const toast = useToastStore();

const props = defineProps({
  business: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['submit', 'cancel', 'skip']);

const loading = ref(false);
const errors = reactive({});

// Biznes kategoriyalari - CreateBusiness.vue bilan bir xil
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

const businessTypes = [
  { value: 'b2b', label: 'B2B', description: 'Biznesga' },
  { value: 'b2c', label: 'B2C', description: 'Mijozga' },
  { value: 'b2b2c', label: 'B2B2C', description: 'Ikkalasiga' },
  { value: 'd2c', label: 'D2C', description: 'To\'g\'ri' }
];

const businessModels = [
  { value: 'product', label: 'Mahsulot', description: 'Tovar' },
  { value: 'service', label: 'Xizmat', description: 'Xizmat' },
  { value: 'marketplace', label: 'Marketplace', description: 'Platforma' },
  { value: 'subscription', label: 'Obuna', description: 'Oylik' },
  { value: 'hybrid', label: 'Aralash', description: 'Ko\'p' }
];

const form = reactive({
  name: '',
  category: '',
  business_type: '',
  business_model: '',
  description: ''
});

// Initialize form when component mounts or props change
function initializeForm() {
  if (props.business) {
    form.name = props.business.name || '';
    form.category = props.business.category || '';
    form.description = props.business.description || '';
    form.business_type = props.business.business_type || '';
    form.business_model = props.business.business_model || '';
  }
}

// Watch for business prop changes
watch(() => props.business, () => {
  initializeForm();
}, { immediate: true, deep: true });

async function handleSubmit() {
  // Clear errors
  Object.keys(errors).forEach(key => delete errors[key]);

  // Barcha maydonlar ixtiyoriy - validatsiya yo'q
  loading.value = true;

  try {
    await store.updateBusinessBasic(form);
    toast.success('Muvaffaqiyatli saqlandi', 'Biznes ma\'lumotlari yangilandi');
    emit('submit');
  } catch (err) {
    if (err.response?.data?.errors) {
      Object.assign(errors, err.response.data.errors);
    }
    const errorMessage = err.response?.data?.message || 'Ma\'lumotlarni saqlashda xatolik yuz berdi';
    toast.error('Xatolik', errorMessage);
  } finally {
    loading.value = false;
  }
}

function handleSkip() {
  emit('skip');
}
</script>
