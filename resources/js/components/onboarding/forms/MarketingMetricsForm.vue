<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="text-center">
      <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-teal-400 to-cyan-400 flex items-center justify-center">
        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
        </svg>
      </div>
      <h3 class="text-xl font-bold text-gray-900 mb-2">{{ t('onboarding.marketing.title') }}</h3>
      <p class="text-gray-600">{{ t('onboarding.marketing.description') }}</p>
    </div>

    <!-- Loading -->
    <div v-if="initialLoading" class="flex items-center justify-center py-12">
      <div class="w-10 h-10 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin"></div>
    </div>

    <template v-else>
      <!-- Marketing Budget -->
      <div class="bg-gradient-to-r from-teal-50 to-cyan-50 rounded-xl p-6">
        <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          {{ t('onboarding.marketing.budget_title') }}
        </h4>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('onboarding.marketing.monthly_budget_label') }}
            </label>
            <input
              v-model="form.monthly_budget"
              type="text"
              class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
              :placeholder="t('onboarding.marketing.monthly_budget_placeholder')"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('onboarding.marketing.ad_spend_label') }}
            </label>
            <input
              v-model="form.ad_spend"
              type="text"
              class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
              :placeholder="t('onboarding.marketing.ad_spend_placeholder')"
            />
          </div>
        </div>
      </div>

      <!-- Website Purpose -->
      <div class="bg-gradient-to-r from-cyan-50 to-blue-50 rounded-xl p-6">
        <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
          </svg>
          {{ t('onboarding.marketing.website_title') }}
        </h4>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">
              {{ t('onboarding.marketing.website_purpose_label') }}
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <div
                v-for="purpose in websitePurposes"
                :key="purpose.value"
                @click="form.website_purpose = purpose.value"
                :class="[
                  'flex items-start gap-3 p-4 rounded-lg border cursor-pointer transition-all',
                  form.website_purpose === purpose.value
                    ? 'border-cyan-500 bg-cyan-50 ring-2 ring-cyan-100'
                    : 'border-gray-200 hover:border-gray-300 bg-white'
                ]"
              >
                <div :class="[
                  'w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0 mt-0.5',
                  form.website_purpose === purpose.value ? 'border-cyan-600' : 'border-gray-300'
                ]">
                  <div v-if="form.website_purpose === purpose.value" class="w-2 h-2 rounded-full bg-cyan-600"></div>
                </div>
                <div>
                  <span class="font-medium text-gray-900 text-sm">{{ t(`onboarding.marketing.website_purpose.${purpose.value}.label`) }}</span>
                  <p class="text-xs text-gray-500 mt-0.5">{{ t(`onboarding.marketing.website_purpose.${purpose.value}.description`) }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Show website stats only if they have a website -->
          <div v-if="form.website_purpose && form.website_purpose !== 'no_website'" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-cyan-100">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('onboarding.marketing.monthly_visits_label') }}
              </label>
              <input
                v-model="form.monthly_visits"
                type="number"
                min="0"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                :placeholder="t('onboarding.marketing.monthly_visits_placeholder')"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('onboarding.marketing.website_conversion_label') }}
              </label>
              <input
                v-model="form.website_conversion"
                type="number"
                min="0"
                max="100"
                step="0.1"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                :placeholder="t('onboarding.marketing.website_conversion_placeholder')"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Active Channels - CHECKBOX GROUP -->
      <div>
        <label class="block text-sm font-medium text-gray-900 mb-1">
          {{ t('onboarding.marketing.active_channels_label') }}
        </label>
        <p class="text-sm text-gray-500 mb-3">{{ t('onboarding.marketing.active_channels_hint') }}</p>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
          <div
            v-for="channel in marketingChannels"
            :key="channel.value"
            @click="toggleChannel(channel.value)"
            :class="[
              'flex flex-col items-center p-3 rounded-lg border-2 cursor-pointer transition-all text-center',
              isChannelSelected(channel.value)
                ? 'border-teal-500 bg-teal-50'
                : 'border-gray-200 hover:border-gray-300'
            ]"
          >
            <span class="text-sm font-medium text-gray-900">{{ t(`onboarding.marketing.channels.${channel.value}.label`) }}</span>
            <span class="text-xs text-gray-500 mt-1">{{ t(`onboarding.marketing.channels.${channel.value}.description`) }}</span>
          </div>
        </div>
      </div>

      <!-- Channel Performance -->
      <div class="bg-gray-50 rounded-xl p-6">
        <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          {{ t('onboarding.marketing.channel_performance_title') }}
        </h4>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('onboarding.marketing.best_channel_label') }}
            </label>
            <select
              v-model="form.best_channel"
              class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
            >
              <option value="">{{ t('common.select') }}</option>
              <option v-for="channel in marketingChannels" :key="channel.value" :value="channel.value">
                {{ t(`onboarding.marketing.channels.${channel.value}.label`) }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('onboarding.marketing.top_lead_channel_label') }}
            </label>
            <select
              v-model="form.top_lead_channel"
              class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
            >
              <option value="">{{ t('common.select') }}</option>
              <option v-for="channel in marketingChannels" :key="channel.value" :value="channel.value">
                {{ t(`onboarding.marketing.channels.${channel.value}.label`) }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Social Media Stats -->
      <div class="bg-gray-50 rounded-xl p-6">
        <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
          </svg>
          {{ t('onboarding.marketing.social_media_title') }}
        </h4>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('onboarding.marketing.instagram_followers_label') }}
            </label>
            <input
              v-model="form.instagram_followers"
              type="number"
              min="0"
              class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
              :placeholder="t('onboarding.marketing.followers_placeholder')"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('onboarding.marketing.telegram_subscribers_label') }}
            </label>
            <input
              v-model="form.telegram_subscribers"
              type="number"
              min="0"
              class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
              :placeholder="t('onboarding.marketing.subscribers_placeholder')"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('onboarding.marketing.facebook_followers_label') }}
            </label>
            <input
              v-model="form.facebook_followers"
              type="number"
              min="0"
              class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
              :placeholder="t('onboarding.marketing.followers_placeholder')"
            />
          </div>
        </div>
      </div>

      <!-- Marketing Effectiveness - FIXED: Using @click instead of v-model radio -->
      <div class="bg-gray-50 rounded-xl p-6">
        <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          {{ t('onboarding.marketing.effectiveness_title') }}
        </h4>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">
              {{ t('onboarding.marketing.roi_tracking_label') }}
            </label>
            <div class="grid grid-cols-3 gap-2">
              <div
                v-for="option in roiTrackingOptions"
                :key="option.value"
                @click="form.roi_tracking_level = option.value"
                :class="[
                  'flex flex-col items-center justify-center p-4 rounded-lg border-2 cursor-pointer transition-all text-center',
                  form.roi_tracking_level === option.value
                    ? 'border-teal-500 bg-teal-50'
                    : 'border-gray-200 hover:border-gray-300'
                ]"
              >
                <span class="text-2xl mb-1">{{ option.emoji }}</span>
                <span class="font-medium text-gray-900 text-sm">{{ t(`onboarding.marketing.roi_tracking.${option.value}.label`) }}</span>
                <span class="text-xs text-gray-500">{{ t(`onboarding.marketing.roi_tracking.${option.value}.description`) }}</span>
              </div>
            </div>
          </div>

          <div v-if="form.roi_tracking_level === 'yes'" class="pt-4 border-t border-gray-200">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('onboarding.marketing.marketing_roi_label') }}
            </label>
            <input
              v-model="form.marketing_roi"
              type="number"
              step="0.1"
              class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
              :placeholder="t('onboarding.marketing.marketing_roi_placeholder')"
            />
          </div>
        </div>
      </div>

      <!-- Content Marketing - CHECKBOX GROUP -->
      <div>
        <label class="block text-sm font-medium text-gray-900 mb-1">
          {{ t('onboarding.marketing.content_label') }}
        </label>
        <p class="text-sm text-gray-500 mb-3">{{ t('onboarding.marketing.content_hint') }}</p>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
          <div
            v-for="activity in contentActivities"
            :key="activity.value"
            @click="toggleActivity(activity.value)"
            :class="[
              'flex flex-col items-center p-3 rounded-lg border-2 cursor-pointer transition-all text-center',
              isActivitySelected(activity.value)
                ? 'border-teal-500 bg-teal-50'
                : 'border-gray-200 hover:border-gray-300'
            ]"
          >
            <span class="text-sm font-medium text-gray-900">{{ t(`onboarding.marketing.content_activities.${activity.value}.label`) }}</span>
            <span class="text-xs text-gray-500 mt-1">{{ t(`onboarding.marketing.content_activities.${activity.value}.description`) }}</span>
          </div>
        </div>
      </div>

      <!-- Marketing Challenges -->
      <div>
        <label class="block text-sm font-medium text-gray-900 mb-1">
          {{ t('onboarding.marketing.challenges_label') }}
        </label>
        <p class="text-sm text-gray-500 mb-2">{{ t('onboarding.marketing.challenges_hint') }}</p>
        <textarea
          v-model="form.marketing_challenges"
          rows="3"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
          :placeholder="t('onboarding.marketing.challenges_placeholder')"
        ></textarea>
      </div>

      <!-- Info text -->
      <p class="text-sm text-gray-500 text-center">
        {{ t('onboarding.marketing.info_text') }}
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
            class="px-6 py-3 rounded-lg bg-teal-600 text-white font-medium hover:bg-teal-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <svg v-if="loading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ t('common.save') }}
          </button>
        </div>
      </div>
    </template>
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

// Form data - using reactive() for consistency
const form = reactive({
  monthly_budget: '',
  ad_spend: '',
  website_purpose: '',
  monthly_visits: '',
  website_conversion: '',
  active_channels: [],
  best_channel: '',
  top_lead_channel: '',
  instagram_followers: '',
  telegram_subscribers: '',
  facebook_followers: '',
  roi_tracking_level: '',
  marketing_roi: '',
  content_activities: [],
  marketing_challenges: ''
});

// Simple toggle methods for checkbox arrays
function isChannelSelected(value) {
  return Array.isArray(form.active_channels) && form.active_channels.includes(value);
}

function toggleChannel(value) {
  if (!Array.isArray(form.active_channels)) {
    form.active_channels = [];
  }
  const index = form.active_channels.indexOf(value);
  if (index === -1) {
    form.active_channels.push(value);
  } else {
    form.active_channels.splice(index, 1);
  }
}

function isActivitySelected(value) {
  return Array.isArray(form.content_activities) && form.content_activities.includes(value);
}

function toggleActivity(value) {
  if (!Array.isArray(form.content_activities)) {
    form.content_activities = [];
  }
  const index = form.content_activities.indexOf(value);
  if (index === -1) {
    form.content_activities.push(value);
  } else {
    form.content_activities.splice(index, 1);
  }
}

// Load existing data on mount
onMounted(async () => {
  try {
    const response = await store.fetchMarketingMetrics();
    if (response?.data) {
      const data = response.data;
      form.monthly_budget = data.monthly_budget || '';
      form.ad_spend = data.ad_spend || '';
      form.website_purpose = data.website_purpose || '';
      form.monthly_visits = data.monthly_visits || '';
      form.website_conversion = data.website_conversion || '';
      form.active_channels = Array.isArray(data.active_channels) ? [...data.active_channels] : [];
      form.best_channel = data.best_channel || '';
      form.top_lead_channel = data.top_lead_channel || '';
      form.instagram_followers = data.instagram_followers || '';
      form.telegram_subscribers = data.telegram_subscribers || '';
      form.facebook_followers = data.facebook_followers || '';
      form.roi_tracking_level = data.roi_tracking_level || '';
      form.marketing_roi = data.marketing_roi || '';
      form.content_activities = Array.isArray(data.content_activities) ? [...data.content_activities] : [];
      form.marketing_challenges = data.marketing_challenges || '';
    }
  } catch (err) {
    console.error('Failed to load marketing metrics:', err);
  } finally {
    initialLoading.value = false;
  }
});

const websitePurposes = [
  { value: 'lead_generation' },
  { value: 'ecommerce' },
  { value: 'info_brand' },
  { value: 'no_website' }
];

const roiTrackingOptions = [
  { value: 'yes', emoji: '📊' },
  { value: 'partially', emoji: '🤔' },
  { value: 'no', emoji: '❓' }
];

const marketingChannels = [
  { value: 'instagram' },
  { value: 'telegram' },
  { value: 'facebook' },
  { value: 'google_ads' },
  { value: 'seo' },
  { value: 'email' },
  { value: 'sms' },
  { value: 'content' },
  { value: 'influencer' },
  { value: 'offline' },
  { value: 'youtube' },
  { value: 'tiktok' }
];

const contentActivities = [
  { value: 'blog' },
  { value: 'videos' },
  { value: 'reels' },
  { value: 'podcast' },
  { value: 'webinars' },
  { value: 'ebooks' }
];

async function handleSubmit() {
  loading.value = true;

  try {
    await store.updateMarketingMetrics({ ...form });
    toast.success(t('common.success'), t('onboarding.marketing.saved_message'));
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
