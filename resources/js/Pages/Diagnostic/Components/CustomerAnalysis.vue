<template>
  <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
    <!-- Header -->
    <div class="p-6 bg-gradient-to-r from-purple-50 to-pink-50 border-b border-gray-100">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
            <UserGroupIcon class="w-6 h-6 text-purple-600" />
          </div>
          <div>
            <h3 class="font-semibold text-gray-900">Ideal Mijoz Tahlili</h3>
            <p class="text-sm text-gray-500">Ideal Mijoz metodologiyasi</p>
          </div>
        </div>
        <div class="flex items-center gap-4">
          <div class="text-right">
            <p class="text-sm text-gray-500">To'liqlik</p>
            <p class="text-xl font-bold text-purple-600">
              {{ customer.completeness_percent || 0 }}%
            </p>
          </div>
          <ScoreCircle :score="customer.score || 0" :size="80" label="ball" />
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Demographics -->
        <div class="bg-purple-50 rounded-xl p-4">
          <h4 class="font-medium text-purple-900 mb-3 flex items-center gap-2">
            <UserIcon class="w-4 h-4" />
            Demografiya
          </h4>
          <p class="text-purple-700 text-sm">{{ formatDemographics(customer.demographics) }}</p>
        </div>

        <!-- Pain Points -->
        <div class="bg-red-50 rounded-xl p-4">
          <h4 class="font-medium text-red-900 mb-3 flex items-center gap-2">
            <ExclamationTriangleIcon class="w-4 h-4" />
            Og'riq nuqtalari
          </h4>
          <ul v-if="customer.pain_points?.length" class="text-red-700 text-sm space-y-1">
            <li v-for="(point, i) in customer.pain_points" :key="i" class="flex items-start gap-2">
              <span class="text-red-400">•</span>
              {{ point }}
            </li>
          </ul>
          <p v-else class="text-red-600 text-sm">Ma'lumot yo'q</p>
        </div>

        <!-- Desires -->
        <div class="bg-green-50 rounded-xl p-4">
          <h4 class="font-medium text-green-900 mb-3 flex items-center gap-2">
            <HeartIcon class="w-4 h-4" />
            Istaklar
          </h4>
          <ul v-if="customer.desires?.length" class="text-green-700 text-sm space-y-1">
            <li v-for="(desire, i) in customer.desires" :key="i" class="flex items-start gap-2">
              <span class="text-green-400">•</span>
              {{ desire }}
            </li>
          </ul>
          <p v-else class="text-green-600 text-sm">Ma'lumot yo'q</p>
        </div>

        <!-- Behavior -->
        <div class="bg-blue-50 rounded-xl p-4">
          <h4 class="font-medium text-blue-900 mb-3 flex items-center gap-2">
            <EyeIcon class="w-4 h-4" />
            Xulq-atvor
          </h4>
          <p class="text-blue-700 text-sm">{{ customer.behavior || "Ma'lumot yo'q" }}</p>
        </div>
      </div>

      <!-- Channels Distribution -->
      <div v-if="customer.channels" class="mt-6">
        <h4 class="font-medium text-gray-900 mb-4">Faol kanallar</h4>
        <div class="flex gap-4">
          <div
            v-for="(percent, channel) in customer.channels"
            :key="channel"
            class="flex-1 bg-gray-50 rounded-xl p-4 text-center"
          >
            <div
              class="w-10 h-10 rounded-lg flex items-center justify-center mx-auto mb-2"
              :class="getChannelClass(channel)"
            >
              <span class="text-white font-bold">{{ channel.charAt(0).toUpperCase() }}</span>
            </div>
            <p class="text-sm font-medium text-gray-900 capitalize">{{ channel }}</p>
            <p class="text-lg font-bold text-gray-700">{{ percent }}%</p>
          </div>
        </div>
      </div>

      <!-- Missing Fields -->
      <div v-if="customer.missing_fields?.length" class="mt-6 bg-yellow-50 rounded-xl p-4">
        <h4 class="font-medium text-yellow-900 mb-3 flex items-center gap-2">
          <ExclamationCircleIcon class="w-4 h-4" />
          To'ldirilmagan maydonlar
        </h4>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="(field, i) in customer.missing_fields"
            :key="i"
            class="px-3 py-1 bg-yellow-100 text-yellow-700 text-sm rounded-full"
          >
            {{ field }}
          </span>
        </div>
      </div>

      <!-- Recommendation -->
      <div v-if="customer.recommendation" class="mt-6 bg-indigo-50 rounded-xl p-4">
        <h4 class="font-medium text-indigo-900 mb-2 flex items-center gap-2">
          <LightBulbIcon class="w-4 h-4" />
          Tavsiya
        </h4>
        <p class="text-indigo-700 text-sm">{{ customer.recommendation }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import ScoreCircle from './ScoreCircle.vue';
import {
  UserGroupIcon,
  UserIcon,
  ExclamationTriangleIcon,
  HeartIcon,
  EyeIcon,
  ExclamationCircleIcon,
  LightBulbIcon,
} from '@heroicons/vue/24/outline';

defineProps({
  customer: {
    type: Object,
    required: true,
  },
});

function formatDemographics(demographics) {
  if (typeof demographics === 'string') return demographics;
  if (typeof demographics === 'object' && demographics !== null) {
    const parts = [];
    if (demographics.age_range) parts.push(`${demographics.age_range} yoshdagi`);
    if (demographics.occupation) parts.push(demographics.occupation.toLowerCase());
    if (demographics.location) parts.push(`${demographics.location} shahrida yashovchi`);
    return parts.join(' ') || JSON.stringify(demographics);
  }
  return "Ma'lumot yo'q";
}

function getChannelClass(channel) {
  const classes = {
    telegram: 'bg-blue-500',
    instagram: 'bg-gradient-to-br from-purple-500 to-pink-500',
    facebook: 'bg-blue-600',
    whatsapp: 'bg-green-500',
    tiktok: 'bg-black',
  };
  return classes[channel.toLowerCase()] || 'bg-gray-500';
}
</script>
