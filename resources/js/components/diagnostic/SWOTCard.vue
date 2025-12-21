<template>
  <div class="bg-white rounded-lg border overflow-hidden">
    <div class="p-4 border-b bg-gray-50">
      <h3 class="font-semibold text-gray-900">SWOT Tahlili</h3>
      <p class="text-sm text-gray-500 mt-1">Biznesingizning kuchli va zaif tomonlari</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2">
      <!-- Strengths -->
      <div class="p-4 border-b md:border-r">
        <div class="flex items-center space-x-2 mb-3">
          <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
            <CheckCircleIcon class="w-5 h-5 text-green-600" />
          </div>
          <h4 class="font-medium text-green-700">Kuchli tomonlar</h4>
        </div>
        <ul class="space-y-2">
          <li
            v-for="(item, index) in swot?.strengths || []"
            :key="`s-${index}`"
            class="flex items-start text-sm"
          >
            <span class="w-5 h-5 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs mr-2 mt-0.5 flex-shrink-0">
              {{ index + 1 }}
            </span>
            <span class="text-gray-700">{{ item }}</span>
          </li>
          <li v-if="!swot?.strengths?.length" class="text-sm text-gray-400 italic">
            Ma'lumot mavjud emas
          </li>
        </ul>
      </div>

      <!-- Weaknesses -->
      <div class="p-4 border-b">
        <div class="flex items-center space-x-2 mb-3">
          <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
            <ExclamationCircleIcon class="w-5 h-5 text-red-600" />
          </div>
          <h4 class="font-medium text-red-700">Zaif tomonlar</h4>
        </div>
        <ul class="space-y-2">
          <li
            v-for="(item, index) in swot?.weaknesses || []"
            :key="`w-${index}`"
            class="flex items-start text-sm"
          >
            <span class="w-5 h-5 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xs mr-2 mt-0.5 flex-shrink-0">
              {{ index + 1 }}
            </span>
            <span class="text-gray-700">{{ item }}</span>
          </li>
          <li v-if="!swot?.weaknesses?.length" class="text-sm text-gray-400 italic">
            Ma'lumot mavjud emas
          </li>
        </ul>
      </div>

      <!-- Opportunities -->
      <div class="p-4 md:border-r">
        <div class="flex items-center space-x-2 mb-3">
          <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
            <SparklesIcon class="w-5 h-5 text-blue-600" />
          </div>
          <h4 class="font-medium text-blue-700">Imkoniyatlar</h4>
        </div>
        <ul class="space-y-2">
          <li
            v-for="(item, index) in swot?.opportunities || []"
            :key="`o-${index}`"
            class="flex items-start text-sm"
          >
            <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs mr-2 mt-0.5 flex-shrink-0">
              {{ index + 1 }}
            </span>
            <span class="text-gray-700">{{ item }}</span>
          </li>
          <li v-if="!swot?.opportunities?.length" class="text-sm text-gray-400 italic">
            Ma'lumot mavjud emas
          </li>
        </ul>
      </div>

      <!-- Threats -->
      <div class="p-4">
        <div class="flex items-center space-x-2 mb-3">
          <div class="w-8 h-8 rounded-lg bg-yellow-100 flex items-center justify-center">
            <ShieldExclamationIcon class="w-5 h-5 text-yellow-600" />
          </div>
          <h4 class="font-medium text-yellow-700">Tahdidlar</h4>
        </div>
        <ul class="space-y-2">
          <li
            v-for="(item, index) in swot?.threats || []"
            :key="`t-${index}`"
            class="flex items-start text-sm"
          >
            <span class="w-5 h-5 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center text-xs mr-2 mt-0.5 flex-shrink-0">
              {{ index + 1 }}
            </span>
            <span class="text-gray-700">{{ item }}</span>
          </li>
          <li v-if="!swot?.threats?.length" class="text-sm text-gray-400 italic">
            Ma'lumot mavjud emas
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  CheckCircleIcon,
  ExclamationCircleIcon,
  SparklesIcon,
  ShieldExclamationIcon,
} from '@heroicons/vue/24/outline';

defineProps({
  swot: {
    type: Object,
    default: () => ({
      strengths: [],
      weaknesses: [],
      opportunities: [],
      threats: [],
    }),
  },
});
</script>
