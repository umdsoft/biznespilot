<template>
  <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
    <!-- Header -->
    <div class="p-6 bg-gradient-to-r from-orange-50 to-yellow-50 border-b border-gray-100">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
            <GiftIcon class="w-6 h-6 text-orange-600" />
          </div>
          <div>
            <h3 class="font-semibold text-gray-900">Taklif Kuchi ($100M Offers)</h3>
            <p class="text-sm text-gray-500">Alex Hormozi metodologiyasi</p>
          </div>
        </div>
        <ScoreCircle :score="normalizedOffer.score" :size="80" label="ball" />
      </div>
    </div>

    <!-- Score Cards -->
    <div class="p-6 border-b border-gray-100">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-4 border border-indigo-100">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-indigo-600 uppercase tracking-wide">
              {{ normalizedOffer.labels.value }}
            </span>
            <span class="text-xs text-gray-400">/10</span>
          </div>
          <p class="text-3xl font-bold text-indigo-600">{{ normalizedOffer.valueScore }}</p>
          <div class="mt-2 h-1.5 bg-indigo-100 rounded-full overflow-hidden">
            <div
              class="h-full bg-indigo-500 rounded-full"
              :style="{ width: `${normalizedOffer.valueScore * 10}%` }"
            ></div>
          </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border border-green-100">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-green-600 uppercase tracking-wide">
              {{ normalizedOffer.labels.uniqueness }}
            </span>
            <span class="text-xs text-gray-400">/10</span>
          </div>
          <p class="text-3xl font-bold text-green-600">{{ normalizedOffer.uniquenessScore }}</p>
          <div class="mt-2 h-1.5 bg-green-100 rounded-full overflow-hidden">
            <div
              class="h-full bg-green-500 rounded-full"
              :style="{ width: `${normalizedOffer.uniquenessScore * 10}%` }"
            ></div>
          </div>
        </div>

        <div class="bg-gradient-to-br from-orange-50 to-amber-50 rounded-xl p-4 border border-orange-100">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-orange-600 uppercase tracking-wide">
              {{ normalizedOffer.labels.urgency }}
            </span>
            <span class="text-xs text-gray-400">/10</span>
          </div>
          <p class="text-3xl font-bold text-orange-600">{{ normalizedOffer.urgencyScore }}</p>
          <div class="mt-2 h-1.5 bg-orange-100 rounded-full overflow-hidden">
            <div
              class="h-full bg-orange-500 rounded-full"
              :style="{ width: `${normalizedOffer.urgencyScore * 10}%` }"
            ></div>
          </div>
        </div>

        <div class="bg-gradient-to-br from-pink-50 to-rose-50 rounded-xl p-4 border border-pink-100">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-pink-600 uppercase tracking-wide">
              {{ normalizedOffer.labels.guarantee }}
            </span>
            <span class="text-xs text-gray-400">/10</span>
          </div>
          <p class="text-3xl font-bold text-pink-600">{{ normalizedOffer.guaranteeScore }}</p>
          <div class="mt-2 h-1.5 bg-pink-100 rounded-full overflow-hidden">
            <div
              class="h-full bg-pink-500 rounded-full"
              :style="{ width: `${normalizedOffer.guaranteeScore * 10}%` }"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Formula -->
    <div v-if="normalizedOffer.calculatedValue" class="p-6 border-b border-gray-100 bg-gray-50">
      <h4 class="font-medium text-gray-900 mb-2 flex items-center gap-2">
        <SparklesIcon class="w-4 h-4 text-indigo-600" />
        Taklif Qiymati Formulasi
      </h4>
      <p class="text-sm text-gray-600 mb-2">
        Qiymat = (Orzu natijasi × Ishonch darajasi) / (Vaqt sarfi × Harakat sarfi)
      </p>
      <p class="text-lg font-semibold text-indigo-600">
        Hisoblangan qiymat: {{ normalizedOffer.calculatedValue }}
      </p>
    </div>

    <!-- Improvements -->
    <div v-if="normalizedOffer.improvements?.length" class="p-6">
      <h4 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
        <LightBulbIcon class="w-5 h-5 text-yellow-600" />
        Yaxshilash tavsiyalari
      </h4>
      <div class="space-y-3">
        <div
          v-for="(imp, i) in normalizedOffer.improvements"
          :key="i"
          class="flex items-start gap-3 bg-yellow-50 rounded-xl p-4"
        >
          <span class="flex-shrink-0 w-6 h-6 bg-yellow-200 rounded-full flex items-center justify-center text-xs font-medium text-yellow-800">
            {{ i + 1 }}
          </span>
          <span class="text-sm text-yellow-800">{{ imp }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import ScoreCircle from './ScoreCircle.vue';
import { GiftIcon, SparklesIcon, LightBulbIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  offer: {
    type: Object,
    required: true,
  },
});

const normalizedOffer = computed(() => {
  const offer = props.offer;
  const isOldFormat = 'dream_outcome' in offer || 'perceived_likelihood' in offer;

  if (isOldFormat) {
    return {
      score: offer.score || 0,
      valueScore: offer.dream_outcome || 0,
      uniquenessScore: offer.perceived_likelihood || 0,
      urgencyScore: offer.time_delay || 0,
      guaranteeScore: offer.effort_required || 0,
      calculatedValue: offer.calculated_value || 0,
      improvements: (offer.recommendations || []).map(r =>
        typeof r === 'string' ? r : `${r.element}: ${r.advice}`
      ),
      labels: {
        value: 'Orzu natijasi',
        uniqueness: 'Ishonch',
        urgency: 'Vaqt sarfi',
        guarantee: 'Harakat sarfi',
      },
    };
  }

  return {
    score: offer.score || 0,
    valueScore: offer.value_score || 0,
    uniquenessScore: offer.uniqueness_score || 0,
    urgencyScore: offer.urgency_score || 0,
    guaranteeScore: offer.guarantee_score || 0,
    calculatedValue: 0,
    improvements: offer.improvements || [],
    labels: {
      value: 'Qiymat',
      uniqueness: 'Noyoblik',
      urgency: 'Shoshilinchlik',
      guarantee: 'Kafolat',
    },
  };
});
</script>
