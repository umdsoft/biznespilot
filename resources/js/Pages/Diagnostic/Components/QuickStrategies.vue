<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 bg-indigo-500 rounded-xl flex items-center justify-center">
        <RocketLaunchIcon class="w-6 h-6 text-white" />
      </div>
      <div>
        <h3 class="font-bold text-gray-900">Qisqa Strategiyalar</h3>
        <p class="text-sm text-gray-500">Marketing, Sotuvlar va Reklama bo'yicha tavsiyalar</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <!-- Marketing -->
      <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="p-4 bg-blue-50 border-b border-blue-100">
          <div class="flex items-center gap-2">
            <MegaphoneIcon class="w-5 h-5 text-blue-600" />
            <h4 class="font-bold text-gray-900">Marketing</h4>
          </div>
        </div>
        <div class="p-4 space-y-4">
          <div>
            <p class="text-xs text-gray-500 mb-1">Target auditoriya</p>
            <p class="text-sm text-gray-700">{{ data?.marketing?.target_audience || 'Ma\'lumot yo\'q' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-2">Kontent chastotasi</p>
            <div class="flex flex-wrap gap-2">
              <span class="px-2 py-1 bg-pink-100 text-pink-700 rounded text-xs">
                IG: {{ data?.marketing?.content_frequency?.instagram_posts || 0 }}/hafta
              </span>
              <span class="px-2 py-1 bg-pink-100 text-pink-700 rounded text-xs">
                Stories: {{ data?.marketing?.content_frequency?.instagram_stories || 0 }}/kun
              </span>
              <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">
                TG: {{ data?.marketing?.content_frequency?.telegram_posts || 0 }}/kun
              </span>
            </div>
          </div>
          <div v-if="data?.marketing?.best_times?.length">
            <p class="text-xs text-gray-500 mb-1">Eng yaxshi vaqtlar</p>
            <div class="flex gap-2">
              <span
                v-for="time in data.marketing.best_times"
                :key="time"
                class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs"
              >
                {{ time }}
              </span>
            </div>
          </div>
          <div class="pt-2 border-t">
            <p class="text-xs text-gray-500 mb-1">Kutilayotgan natija</p>
            <div class="flex items-center gap-2">
              <ArrowTrendingUpIcon class="w-4 h-4 text-green-500" />
              <span class="text-sm font-medium text-green-600">
                {{ data?.marketing?.expected_results?.leads_increase || '+0%' }} leadlar
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Sales -->
      <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="p-4 bg-green-50 border-b border-green-100">
          <div class="flex items-center gap-2">
            <CurrencyDollarIcon class="w-5 h-5 text-green-600" />
            <h4 class="font-bold text-gray-900">Sotuvlar</h4>
          </div>
        </div>
        <div class="p-4 space-y-4">
          <div>
            <p class="text-xs text-gray-500 mb-1">Konversiya maqsadi</p>
            <div class="flex items-center gap-2">
              <span class="text-red-500 font-medium">{{ data?.sales?.current_conversion || 0 }}%</span>
              <ArrowRightIcon class="w-4 h-4 text-gray-400" />
              <span class="text-green-600 font-bold">{{ data?.sales?.target_conversion || 0 }}%</span>
            </div>
          </div>
          <div v-if="data?.sales?.pricing_recommendation">
            <p class="text-xs text-gray-500 mb-2">Tavsiya etilgan narxlash</p>
            <div class="space-y-1">
              <div
                v-for="(tier, key) in data.sales.pricing_recommendation"
                :key="key"
                class="flex justify-between text-xs"
              >
                <span class="capitalize text-gray-600">{{ key }}</span>
                <span class="font-medium">{{ formatMoney(tier.price) }} ({{ tier.target_percent }}%)</span>
              </div>
            </div>
          </div>
          <div v-if="data?.sales?.top_objections?.[0]" class="pt-2 border-t">
            <p class="text-xs text-gray-500 mb-1">Top e'tiroz</p>
            <p class="text-sm text-gray-700 italic">"{{ data.sales.top_objections[0].objection }}"</p>
            <p class="text-xs text-gray-500 mt-1">Javob:</p>
            <p class="text-sm text-gray-600">{{ data.sales.top_objections[0].response }}</p>
            <p class="text-xs text-green-600 mt-1">
              <CheckCircleIcon class="w-3 h-3 inline" />
              {{ data.sales.top_objections[0].success_rate }}% muvaffaqiyat
            </p>
          </div>
        </div>
      </div>

      <!-- Advertising -->
      <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="p-4 bg-orange-50 border-b border-orange-100">
          <div class="flex items-center gap-2">
            <SpeakerWaveIcon class="w-5 h-5 text-orange-600" />
            <h4 class="font-bold text-gray-900">Reklama</h4>
          </div>
        </div>
        <div class="p-4 space-y-4">
          <div>
            <p class="text-xs text-gray-500 mb-1">Oylik byudjet</p>
            <p class="text-lg font-bold text-gray-900">{{ formatMoney(data?.advertising?.monthly_budget) }}</p>
          </div>
          <div v-if="data?.advertising?.channel_split">
            <p class="text-xs text-gray-500 mb-2">Kanal taqsimoti</p>
            <div class="space-y-2">
              <div
                v-for="(channel, key) in data.advertising.channel_split"
                :key="key"
                class="flex items-center gap-2"
              >
                <div class="flex-1">
                  <div class="flex justify-between text-xs mb-1">
                    <span class="capitalize text-gray-600">{{ key }}</span>
                    <span>{{ channel.percent }}%</span>
                  </div>
                  <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div
                      class="h-full bg-orange-500 rounded-full"
                      :style="{ width: channel.percent + '%' }"
                    ></div>
                  </div>
                </div>
                <span class="text-xs text-gray-500">{{ channel.expected_leads }} lead</span>
              </div>
            </div>
          </div>
          <div class="pt-2 border-t">
            <p class="text-xs text-gray-500 mb-1">Kutilayotgan ROAS</p>
            <p class="text-lg font-bold text-green-600">{{ data?.advertising?.expected_roas || 0 }}%</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  RocketLaunchIcon,
  MegaphoneIcon,
  CurrencyDollarIcon,
  SpeakerWaveIcon,
  ArrowTrendingUpIcon,
  ArrowRightIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  data: {
    type: Object,
    default: () => ({})
  }
});

function formatMoney(amount) {
  if (!amount) return '0';
  if (amount >= 1000000) return (amount / 1000000).toFixed(1) + 'M';
  return new Intl.NumberFormat('uz-UZ').format(amount);
}
</script>
