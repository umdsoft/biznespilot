<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="text-center">
      <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-emerald-400 to-teal-400 flex items-center justify-center">
        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <h3 class="text-xl font-bold text-gray-900 mb-2">{{ t('onboarding.sales.title') }}</h3>
      <p class="text-gray-600">{{ t('onboarding.sales.description') }}</p>
    </div>

    <!-- Lead Volume -->
    <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl p-6">
      <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        {{ t('onboarding.sales.leads_title') }}
      </h4>

      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-3">
            {{ t('onboarding.sales.monthly_leads_label') }}
          </label>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <div
              v-for="range in leadVolumeRanges"
              :key="range.value"
              @click="form.monthly_lead_volume = range.value"
              :class="[
                'flex flex-col items-center justify-center p-4 rounded-lg border-2 cursor-pointer transition-all text-center',
                form.monthly_lead_volume === range.value
                  ? 'border-emerald-500 bg-emerald-50'
                  : 'border-gray-200 hover:border-gray-300'
              ]"
            >
              <span class="font-bold text-emerald-600 text-lg">{{ range.label }}</span>
              <span class="text-xs text-gray-500">{{ t(`onboarding.sales.volume.${range.value}`) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Lead Sources -->
    <div>
      <label class="block text-sm font-medium text-gray-900 mb-1">
        {{ t('onboarding.sales.lead_sources_label') }}
      </label>
      <p class="text-sm text-gray-500 mb-3">{{ t('onboarding.sales.lead_sources_hint') }}</p>
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <div
          v-for="source in leadSources"
          :key="source.value"
          @click="toggleLeadSource(source.value)"
          :class="[
            'flex items-start gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all',
            isLeadSourceSelected(source.value)
              ? 'border-emerald-500 bg-emerald-50'
              : 'border-gray-200 hover:border-gray-300'
          ]"
        >
          <div :class="[
            'w-4 h-4 mt-0.5 rounded border-2 flex items-center justify-center flex-shrink-0',
            isLeadSourceSelected(source.value)
              ? 'bg-emerald-600 border-emerald-600'
              : 'border-gray-300'
          ]">
            <svg v-if="isLeadSourceSelected(source.value)" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-900 block">{{ t(`onboarding.sales.sources.${source.value}.label`) }}</span>
            <span class="text-xs text-gray-500">{{ t(`onboarding.sales.sources.${source.value}.description`) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Lead Quality -->
    <div>
      <label class="block text-sm font-medium text-gray-900 mb-1">
        {{ t('onboarding.sales.lead_quality_label') }}
      </label>
      <p class="text-sm text-gray-500 mb-3">{{ t('onboarding.sales.lead_quality_hint') }}</p>
      <div class="grid grid-cols-3 gap-3">
        <div
          v-for="quality in leadQualityOptions"
          :key="quality.value"
          @click="form.lead_quality = quality.value"
          :class="[
            'flex flex-col items-center justify-center p-4 rounded-lg border-2 cursor-pointer transition-all text-center',
            form.lead_quality === quality.value
              ? 'border-emerald-500 bg-emerald-50'
              : 'border-gray-200 hover:border-gray-300'
          ]"
        >
          <span class="text-2xl mb-1">{{ quality.emoji }}</span>
          <span class="font-medium text-gray-900">{{ t(`onboarding.sales.quality.${quality.value}.label`) }}</span>
          <span class="text-xs text-gray-500">{{ t(`onboarding.sales.quality.${quality.value}.description`) }}</span>
        </div>
      </div>
    </div>

    <!-- Conversion & Sales -->
    <div class="bg-gray-50 rounded-xl p-6">
      <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
        </svg>
        {{ t('onboarding.sales.results_title') }}
      </h4>

      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-3">
            {{ t('onboarding.sales.monthly_sales_label') }}
          </label>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <div
              v-for="range in salesVolumeRanges"
              :key="range.value"
              @click="form.monthly_sales_volume = range.value"
              :class="[
                'flex flex-col items-center justify-center p-4 rounded-lg border-2 cursor-pointer transition-all text-center',
                form.monthly_sales_volume === range.value
                  ? 'border-emerald-500 bg-emerald-50'
                  : 'border-gray-200 hover:border-gray-300'
              ]"
            >
              <span class="font-bold text-emerald-600 text-lg">{{ range.label }}</span>
              <span class="text-xs text-gray-500">{{ t(`onboarding.sales.sales_volume.${range.value}`) }}</span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-gray-200">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('onboarding.sales.avg_deal_label') }}
            </label>
            <input
              v-model="form.avg_deal_size"
              type="text"
              class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
              :placeholder="t('onboarding.sales.avg_deal_placeholder')"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('onboarding.sales.sales_cycle_label') }}
            </label>
            <select
              v-model="form.sales_cycle"
              class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
            >
              <option value="">{{ t('common.select') }}</option>
              <option value="same_day">{{ t('onboarding.sales.cycle.same_day') }}</option>
              <option value="1_3_days">{{ t('onboarding.sales.cycle.1_3_days') }}</option>
              <option value="1_week">{{ t('onboarding.sales.cycle.1_week') }}</option>
              <option value="2_weeks">{{ t('onboarding.sales.cycle.2_weeks') }}</option>
              <option value="1_month">{{ t('onboarding.sales.cycle.1_month') }}</option>
              <option value="more_month">{{ t('onboarding.sales.cycle.more_month') }}</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Sales Team -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-3">
        {{ t('onboarding.sales.team_label') }}
      </label>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        <div
          v-for="option in salesTeamOptions"
          :key="option.value"
          @click="form.sales_team_type = option.value"
          :class="[
            'flex items-start gap-3 p-4 rounded-lg border cursor-pointer transition-all',
            form.sales_team_type === option.value
              ? 'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-100'
              : 'border-gray-200 hover:border-gray-300'
          ]"
        >
          <div :class="[
            'w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0 mt-0.5',
            form.sales_team_type === option.value
              ? 'border-emerald-600'
              : 'border-gray-300'
          ]">
            <div v-if="form.sales_team_type === option.value" class="w-2 h-2 rounded-full bg-emerald-600"></div>
          </div>
          <div>
            <span class="font-medium text-gray-900 text-sm">{{ t(`onboarding.sales.team.${option.value}.label`) }}</span>
            <p class="text-xs text-gray-500 mt-0.5">{{ t(`onboarding.sales.team.${option.value}.description`) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Sales Tools -->
    <div>
      <label class="block text-sm font-medium text-gray-900 mb-1">
        {{ t('onboarding.sales.tools_label') }}
      </label>
      <p class="text-sm text-gray-500 mb-3">{{ t('onboarding.sales.tools_hint') }}</p>
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <div
          v-for="tool in salesTools"
          :key="tool.value"
          @click="toggleSalesTool(tool.value)"
          :class="[
            'flex items-start gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all',
            isSalesToolSelected(tool.value)
              ? 'border-emerald-500 bg-emerald-50'
              : 'border-gray-200 hover:border-gray-300'
          ]"
        >
          <div :class="[
            'w-4 h-4 mt-0.5 rounded border-2 flex items-center justify-center flex-shrink-0',
            isSalesToolSelected(tool.value)
              ? 'bg-emerald-600 border-emerald-600'
              : 'border-gray-300'
          ]">
            <svg v-if="isSalesToolSelected(tool.value)" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-900 block">{{ t(`onboarding.sales.tools.${tool.value}.label`) }}</span>
            <span class="text-xs text-gray-500">{{ t(`onboarding.sales.tools.${tool.value}.description`) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Challenges -->
    <div>
      <label class="block text-sm font-medium text-gray-900 mb-1">
        {{ t('onboarding.sales.challenges_label') }}
      </label>
      <p class="text-sm text-gray-500 mb-2">{{ t('onboarding.sales.challenges_hint') }}</p>
      <textarea
        v-model="form.sales_challenges"
        rows="2"
        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
        :placeholder="t('onboarding.sales.challenges_placeholder')"
      ></textarea>
    </div>

    <!-- Info text -->
    <p class="text-sm text-gray-500 text-center">
      {{ t('onboarding.sales.info_text') }}
    </p>

    <!-- Action Buttons -->
    <div class="flex justify-between gap-3 pt-4">
      <button
        type="button"
        @click="$emit('skip')"
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
          @click="handleSubmit"
          :disabled="loading"
          class="px-6 py-3 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <svg v-if="loading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ t('common.save') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useOnboardingStore } from '@/stores/onboarding';
import { useToastStore } from '@/stores/toast';
import { useI18n } from '@/i18n';

const { t } = useI18n();
const store = useOnboardingStore();
const toast = useToastStore();
const emit = defineEmits(['submit', 'cancel', 'skip']);

const loading = ref(false);
const initialLoading = ref(true);

const form = reactive({
  monthly_lead_volume: '',
  lead_sources: [],
  lead_quality: '',
  monthly_sales_volume: '',
  avg_deal_size: '',
  sales_cycle: '',
  sales_team_type: '',
  sales_tools: [],
  sales_challenges: ''
});

// Simple toggle functions - NO computed getters!
function isLeadSourceSelected(value) {
  return Array.isArray(form.lead_sources) && form.lead_sources.includes(value);
}

function toggleLeadSource(value) {
  if (!Array.isArray(form.lead_sources)) {
    form.lead_sources = [];
  }
  const index = form.lead_sources.indexOf(value);
  if (index === -1) {
    form.lead_sources.push(value);
  } else {
    form.lead_sources.splice(index, 1);
  }
}

function isSalesToolSelected(value) {
  return Array.isArray(form.sales_tools) && form.sales_tools.includes(value);
}

function toggleSalesTool(value) {
  if (!Array.isArray(form.sales_tools)) {
    form.sales_tools = [];
  }
  const index = form.sales_tools.indexOf(value);
  if (index === -1) {
    form.sales_tools.push(value);
  } else {
    form.sales_tools.splice(index, 1);
  }
}

// Load existing data on mount
onMounted(async () => {
  try {
    const response = await store.fetchSalesMetrics();
    if (response?.data) {
      const data = response.data;
      form.monthly_lead_volume = data.monthly_lead_volume || '';
      form.lead_sources = Array.isArray(data.lead_sources) ? [...data.lead_sources] : [];
      form.lead_quality = data.lead_quality || '';
      form.monthly_sales_volume = data.monthly_sales_volume || '';
      form.avg_deal_size = data.avg_deal_size || '';
      form.sales_cycle = data.sales_cycle || '';
      form.sales_team_type = data.sales_team_type || '';
      form.sales_tools = Array.isArray(data.sales_tools) ? [...data.sales_tools] : [];
      form.sales_challenges = data.sales_challenges || '';
    }
  } catch (err) {
    console.error('Failed to load sales metrics:', err);
  } finally {
    initialLoading.value = false;
  }
});

const leadVolumeRanges = [
  { value: '0_10', label: '0-10' },
  { value: '10_50', label: '10-50' },
  { value: '50_200', label: '50-200' },
  { value: '200_plus', label: '200+' }
];

const salesVolumeRanges = [
  { value: '0_10', label: '0-10' },
  { value: '10_50', label: '10-50' },
  { value: '50_100', label: '50-100' },
  { value: '100_plus', label: '100+' }
];

const leadSources = [
  { value: 'instagram' },
  { value: 'telegram' },
  { value: 'facebook' },
  { value: 'website' },
  { value: 'referral' },
  { value: 'cold_calls' },
  { value: 'ads' },
  { value: 'offline' },
  { value: 'other' }
];

const leadQualityOptions = [
  { value: 'low', emoji: '😕' },
  { value: 'medium', emoji: '🙂' },
  { value: 'high', emoji: '😊' }
];

const salesTeamOptions = [
  { value: 'owner_only' },
  { value: 'small_team' },
  { value: 'medium_team' },
  { value: 'large_team' }
];

const salesTools = [
  { value: 'excel' },
  { value: 'crm' },
  { value: 'telegram_bot' },
  { value: 'whatsapp' },
  { value: 'phone' },
  { value: 'none' }
];

async function handleSubmit() {
  loading.value = true;

  try {
    await store.updateSalesMetrics(form);
    toast.success(t('common.success'), t('onboarding.sales.saved_message'));
    emit('submit');
  } catch (err) {
    console.error(err);
    const errorMessage = err.response?.data?.message || t('common.save_error');
    toast.error(t('common.error'), errorMessage);
  } finally {
    loading.value = false;
  }
}
</script>
