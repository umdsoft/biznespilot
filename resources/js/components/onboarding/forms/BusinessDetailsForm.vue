<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Team Size -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        {{ t('onboarding.forms.team_size') }}
      </label>
      <div class="grid grid-cols-3 sm:grid-cols-6 gap-3">
        <label
          v-for="size in teamSizes"
          :key="size.value"
          :class="[
            'relative flex items-center justify-center p-3 rounded-lg border-2 cursor-pointer transition-all',
            form.team_size === size.value
              ? 'border-indigo-500 bg-indigo-50'
              : 'border-gray-200 hover:border-gray-300'
          ]"
        >
          <input
            type="radio"
            v-model="form.team_size"
            :value="size.value"
            class="sr-only"
          />
          <span class="font-medium text-gray-900">{{ size.label }}</span>
        </label>
      </div>
      <p v-if="errors.team_size" class="mt-1 text-sm text-red-500">{{ errors.team_size }}</p>
    </div>

    <!-- Business Stage -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        {{ t('onboarding.forms.business_stage') }}
      </label>
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <label
          v-for="stage in businessStages"
          :key="stage.value"
          :class="[
            'relative flex flex-col items-center justify-center p-4 rounded-lg border-2 cursor-pointer transition-all',
            form.business_stage === stage.value
              ? 'border-indigo-500 bg-indigo-50'
              : 'border-gray-200 hover:border-gray-300'
          ]"
        >
          <input
            type="radio"
            v-model="form.business_stage"
            :value="stage.value"
            class="sr-only"
          />
          <span class="font-medium text-gray-900">{{ t(`onboarding.forms.stages.${stage.value}`) }}</span>
          <span class="text-xs text-gray-500 text-center mt-1">{{ t(`onboarding.forms.stages.${stage.value}_desc`) }}</span>
        </label>
      </div>
      <p v-if="errors.business_stage" class="mt-1 text-sm text-red-500">{{ errors.business_stage }}</p>
    </div>

    <!-- City -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          {{ t('onboarding.forms.city') }}
        </label>
        <select
          v-model="form.city"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          :class="{ 'border-red-500': errors.city }"
        >
          <option value="">{{ t('onboarding.forms.select_city') }}</option>
          <option v-for="city in cities" :key="city" :value="city">{{ city }}</option>
        </select>
        <p v-if="errors.city" class="mt-1 text-sm text-red-500">{{ errors.city }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          {{ t('onboarding.forms.country') }}
        </label>
        <input
          v-model="form.country"
          type="text"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          :placeholder="t('onboarding.forms.country_placeholder')"
        />
      </div>
    </div>

    <!-- Founding Date -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        {{ t('onboarding.forms.founding_date') }}
      </label>
      <input
        v-model="form.founding_date"
        type="date"
        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
      />
    </div>

    <!-- Contact Info -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          {{ t('onboarding.forms.website') }}
        </label>
        <input
          v-model="form.website"
          type="url"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="https://example.com"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          {{ t('onboarding.forms.phone') }}
        </label>
        <input
          v-model="form.phone"
          type="tel"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="+998 90 123 45 67"
        />
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        {{ t('onboarding.forms.email') }}
      </label>
      <input
        v-model="form.email"
        type="email"
        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
        placeholder="info@example.com"
      />
    </div>

    <!-- Address -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        {{ t('onboarding.forms.address') }}
      </label>
      <textarea
        v-model="form.address"
        rows="2"
        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
        :placeholder="t('onboarding.forms.address_placeholder')"
      ></textarea>
    </div>

    <!-- Info text -->
    <p class="text-sm text-gray-500 text-center">
      {{ t('onboarding.forms.optional_fields') }}
    </p>

    <!-- Submit -->
    <div class="flex justify-between gap-3 pt-4">
      <button
        type="button"
        @click="handleSkip"
        class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors"
      >
        {{ t('common.skip') }}
      </button>
      <div class="flex gap-3">
        <button
          type="button"
          @click="$emit('cancel')"
          class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors"
        >
          {{ t('common.cancel') }}
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
          {{ t('common.save') }}
        </button>
      </div>
    </div>
  </form>
</template>

<script setup>
import { ref, reactive } from 'vue';
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

const form = reactive({
  team_size: props.business?.team_size || '',
  business_stage: props.business?.business_stage || '',
  city: props.business?.city || '',
  country: props.business?.country || 'O\'zbekiston',
  founding_date: props.business?.founding_date || '',
  website: props.business?.website || '',
  phone: props.business?.phone || '',
  email: props.business?.email || '',
  address: props.business?.address || ''
});

const teamSizes = [
  { value: '1', label: '1' },
  { value: '2-5', label: '2-5' },
  { value: '6-10', label: '6-10' },
  { value: '11-25', label: '11-25' },
  { value: '26-50', label: '26-50' },
  { value: '50+', label: '50+' }
];

const businessStages = [
  { value: 'idea' },
  { value: 'startup' },
  { value: 'growth' },
  { value: 'established' },
  { value: 'scaling' }
];

const cities = [
  'Toshkent',
  'Samarqand',
  'Buxoro',
  'Farg\'ona',
  'Andijon',
  'Namangan',
  'Qarshi',
  'Nukus',
  'Jizzax',
  'Termiz',
  'Navoiy',
  'Urganch',
  'Guliston',
  'Boshqa'
];

async function handleSubmit() {
  // Clear errors
  Object.keys(errors).forEach(key => delete errors[key]);

  // Barcha maydonlar ixtiyoriy - validatsiya yo'q
  loading.value = true;

  try {
    await store.updateBusinessDetails(form);
    toast.success(t('common.success'), t('onboarding.forms.details_updated'));
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
