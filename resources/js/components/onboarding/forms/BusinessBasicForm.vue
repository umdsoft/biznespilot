<template>
  <form @submit.prevent="handleSubmit" class="space-y-5">
    <!-- Row 1: Business Name -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">
        {{ t('onboarding.forms.business_name') }}
      </label>
      <input
        v-model="form.name"
        type="text"
        class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
        :placeholder="t('onboarding.forms.business_name_placeholder')"
        :class="{ 'border-red-500': errors.name }"
      />
      <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name }}</p>
    </div>

    <!-- Row 2: Category -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">
        {{ t('onboarding.forms.business_category') }}
      </label>
      <select
        v-model="form.category"
        class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
        :class="{ 'border-red-500': errors.category }"
      >
        <option value="">{{ t('onboarding.forms.select_category') }}</option>
        <option
          v-for="cat in businessCategories"
          :key="cat.value"
          :value="cat.value"
        >
          {{ t(`onboarding.forms.categories.${cat.value}`) }}
        </option>
      </select>
      <p v-if="errors.category" class="mt-1 text-xs text-red-500">{{ errors.category }}</p>
    </div>

    <!-- Row 3: Description -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">
        {{ t('onboarding.forms.short_description') }}
      </label>
      <textarea
        v-model="form.description"
        rows="2"
        class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm resize-none"
        :placeholder="t('onboarding.forms.description_placeholder')"
      ></textarea>
    </div>

    <!-- Row 4: Business Type -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        {{ t('onboarding.forms.business_type') }}
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
          <span class="text-sm font-semibold text-gray-900">{{ t(`onboarding.forms.types.${type.value}`) }}</span>
          <span class="text-xs text-gray-500">{{ t(`onboarding.forms.types.${type.value}_desc`) }}</span>
        </label>
      </div>
      <p v-if="errors.business_type" class="mt-1 text-xs text-red-500">{{ errors.business_type }}</p>
    </div>

    <!-- Row 5: Business Model -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        {{ t('onboarding.forms.business_model') }}
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
          <span class="text-sm font-semibold text-gray-900">{{ t(`onboarding.forms.models.${model.value}`) }}</span>
          <span class="text-xs text-gray-500">{{ t(`onboarding.forms.models.${model.value}_desc`) }}</span>
        </label>
      </div>
      <p v-if="errors.business_model" class="mt-1 text-xs text-red-500">{{ errors.business_model }}</p>
    </div>

    <!-- Info text -->
    <p class="text-sm text-gray-500 text-center">
      {{ t('onboarding.forms.optional_fields') }}
    </p>

    <!-- Submit -->
    <div class="flex justify-between gap-3 pt-2">
      <button
        type="button"
        @click="handleSkip"
        class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors"
      >
        {{ t('common.skip') }}
      </button>
      <div class="flex gap-3">
        <button
          type="button"
          @click="$emit('cancel')"
          class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors"
        >
          {{ t('common.cancel') }}
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
          {{ t('common.save') }}
        </button>
      </div>
    </div>
  </form>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { useOnboardingStore } from '@/stores/onboarding';
import { useToastStore } from '@/stores/toast';
import { useI18n } from '@/i18n';

const { t } = useI18n();

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

// Business categories - values only, labels from i18n
const businessCategories = [
  { value: 'retail' },
  { value: 'wholesale' },
  { value: 'ecommerce' },
  { value: 'food_service' },
  { value: 'manufacturing' },
  { value: 'construction' },
  { value: 'it_services' },
  { value: 'education' },
  { value: 'healthcare' },
  { value: 'beauty_wellness' },
  { value: 'real_estate' },
  { value: 'transportation' },
  { value: 'agriculture' },
  { value: 'tourism' },
  { value: 'finance' },
  { value: 'consulting' },
  { value: 'marketing_agency' },
  { value: 'media' },
  { value: 'fitness' },
  { value: 'automotive' },
  { value: 'textile' },
  { value: 'furniture' },
  { value: 'electronics' },
  { value: 'cleaning' },
  { value: 'event_services' },
  { value: 'legal' },
  { value: 'other' },
];

const businessTypes = [
  { value: 'b2b' },
  { value: 'b2c' },
  { value: 'b2b2c' },
  { value: 'd2c' }
];

const businessModels = [
  { value: 'product' },
  { value: 'service' },
  { value: 'marketplace' },
  { value: 'subscription' },
  { value: 'hybrid' }
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
    toast.success(t('common.success'), t('onboarding.forms.business_updated'));
    emit('submit');
  } catch (err) {
    if (err.response?.data?.errors) {
      Object.assign(errors, err.response.data.errors);
    }
    const errorMessage = err.response?.data?.message || t('common.save_error');
    toast.error(t('common.error'), errorMessage);
  } finally {
    loading.value = false;
  }
}

function handleSkip() {
  emit('skip');
}
</script>
