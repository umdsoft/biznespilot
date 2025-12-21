<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Business Name -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Biznes nomi <span class="text-red-500">*</span>
      </label>
      <input
        v-model="form.name"
        type="text"
        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
        placeholder="Masalan: TechnoMarket"
        :class="{ 'border-red-500': errors.name }"
      />
      <p v-if="errors.name" class="mt-1 text-sm text-red-500">{{ errors.name }}</p>
    </div>

    <!-- Industry -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Soha <span class="text-red-500">*</span>
      </label>
      <select
        v-model="form.industry_id"
        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
        :class="{ 'border-red-500': errors.industry_id }"
        @change="onIndustryChange"
      >
        <option value="">Sohani tanlang</option>
        <option
          v-for="industry in industries"
          :key="industry.id"
          :value="industry.id"
        >
          {{ industry.name.uz }}
        </option>
      </select>
      <p v-if="errors.industry_id" class="mt-1 text-sm text-red-500">{{ errors.industry_id }}</p>
    </div>

    <!-- Sub-Industry -->
    <div v-if="subIndustries.length > 0">
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Yo'nalish
      </label>
      <select
        v-model="form.sub_industry_id"
        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
      >
        <option value="">Yo'nalishni tanlang (ixtiyoriy)</option>
        <option
          v-for="sub in subIndustries"
          :key="sub.id"
          :value="sub.id"
        >
          {{ sub.name.uz }}
        </option>
      </select>
    </div>

    <!-- Business Type -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Biznes turi <span class="text-red-500">*</span>
      </label>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <label
          v-for="type in businessTypes"
          :key="type.value"
          :class="[
            'relative flex items-center justify-center p-4 rounded-lg border-2 cursor-pointer transition-all',
            form.business_type === type.value
              ? 'border-indigo-500 bg-indigo-50'
              : 'border-gray-200 hover:border-gray-300'
          ]"
        >
          <input
            type="radio"
            v-model="form.business_type"
            :value="type.value"
            class="sr-only"
          />
          <div class="text-center">
            <span class="block font-medium text-gray-900">{{ type.label }}</span>
            <span class="block text-xs text-gray-500 mt-1">{{ type.description }}</span>
          </div>
        </label>
      </div>
      <p v-if="errors.business_type" class="mt-1 text-sm text-red-500">{{ errors.business_type }}</p>
    </div>

    <!-- Business Model -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Biznes modeli <span class="text-red-500">*</span>
      </label>
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <label
          v-for="model in businessModels"
          :key="model.value"
          :class="[
            'relative flex items-center justify-center p-4 rounded-lg border-2 cursor-pointer transition-all',
            form.business_model === model.value
              ? 'border-indigo-500 bg-indigo-50'
              : 'border-gray-200 hover:border-gray-300'
          ]"
        >
          <input
            type="radio"
            v-model="form.business_model"
            :value="model.value"
            class="sr-only"
          />
          <div class="text-center">
            <span class="block font-medium text-gray-900">{{ model.label }}</span>
            <span class="block text-xs text-gray-500 mt-1">{{ model.description }}</span>
          </div>
        </label>
      </div>
      <p v-if="errors.business_model" class="mt-1 text-sm text-red-500">{{ errors.business_model }}</p>
    </div>

    <!-- Description -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Biznes haqida qisqacha
      </label>
      <textarea
        v-model="form.description"
        rows="3"
        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
        placeholder="Biznesingiz haqida qisqacha ma'lumot..."
      ></textarea>
    </div>

    <!-- Submit -->
    <div class="flex justify-end gap-3 pt-4">
      <button
        type="button"
        @click="$emit('cancel')"
        class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors"
      >
        Bekor qilish
      </button>
      <button
        type="submit"
        :disabled="loading"
        class="px-6 py-3 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
      >
        <svg v-if="loading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Saqlash va davom etish
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useOnboardingStore } from '@/stores/onboarding';

const store = useOnboardingStore();

const props = defineProps({
  business: {
    type: Object,
    default: () => ({})
  },
  industries: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['submit', 'cancel']);

const loading = ref(false);
const errors = reactive({});

const form = reactive({
  name: props.business?.name || '',
  industry_id: props.business?.industry_id || '',
  sub_industry_id: props.business?.sub_industry_id || '',
  business_type: props.business?.business_type || '',
  business_model: props.business?.business_model || '',
  description: props.business?.description || ''
});

const businessTypes = [
  { value: 'b2b', label: 'B2B', description: 'Biznesga' },
  { value: 'b2c', label: 'B2C', description: 'Mijozga' },
  { value: 'b2b2c', label: 'B2B2C', description: 'Ikkalasiga' },
  { value: 'd2c', label: 'D2C', description: 'To\'g\'ridan-to\'g\'ri' }
];

const businessModels = [
  { value: 'product', label: 'Mahsulot', description: 'Tovar sotish' },
  { value: 'service', label: 'Xizmat', description: 'Xizmat ko\'rsatish' },
  { value: 'marketplace', label: 'Marketplace', description: 'Platforma' },
  { value: 'subscription', label: 'Obuna', description: 'Oylik to\'lov' },
  { value: 'hybrid', label: 'Aralash', description: 'Ko\'p model' }
];

const subIndustries = computed(() => {
  if (!form.industry_id) return [];
  const industry = props.industries.find(i => i.id === parseInt(form.industry_id));
  return industry?.children || [];
});

function onIndustryChange() {
  form.sub_industry_id = '';
}

async function handleSubmit() {
  // Clear errors
  Object.keys(errors).forEach(key => delete errors[key]);

  // Validate
  if (!form.name) errors.name = 'Biznes nomi kiritilishi shart';
  if (!form.industry_id) errors.industry_id = 'Soha tanlanishi shart';
  if (!form.business_type) errors.business_type = 'Biznes turi tanlanishi shart';
  if (!form.business_model) errors.business_model = 'Biznes modeli tanlanishi shart';

  if (Object.keys(errors).length > 0) return;

  loading.value = true;

  try {
    await store.updateBusinessBasic(form);
    emit('submit');
  } catch (err) {
    if (err.response?.data?.errors) {
      Object.assign(errors, err.response.data.errors);
    }
  } finally {
    loading.value = false;
  }
}
</script>
