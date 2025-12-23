<template>
  <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
    <!-- Header -->
    <div class="p-6 bg-gradient-to-r from-indigo-50 to-purple-50 border-b border-gray-100">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
          <Squares2X2Icon class="w-6 h-6 text-indigo-600" />
        </div>
        <div>
          <h3 class="font-semibold text-gray-900">SWOT Tahlili</h3>
          <p class="text-sm text-gray-500">Kuchli/zaif tomonlar, imkoniyatlar va xavflar</p>
        </div>
      </div>
    </div>

    <!-- SWOT Grid -->
    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Strengths -->
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-5 border border-green-200">
          <h4 class="font-medium text-green-900 mb-4 flex items-center gap-2">
            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
              <CheckCircleIcon class="w-5 h-5 text-green-600" />
            </div>
            Kuchli tomonlar (S)
          </h4>
          <ul v-if="swot.strengths?.length" class="space-y-2">
            <li v-for="(item, i) in swot.strengths" :key="i" class="flex items-start gap-2 text-sm text-green-800">
              <span class="text-green-400 mt-1">+</span>
              {{ item }}
            </li>
          </ul>
          <p v-else class="text-sm text-green-600">Ma'lumot yo'q</p>
        </div>

        <!-- Weaknesses -->
        <div class="bg-gradient-to-br from-red-50 to-orange-50 rounded-xl p-5 border border-red-200">
          <h4 class="font-medium text-red-900 mb-4 flex items-center gap-2">
            <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
              <XCircleIcon class="w-5 h-5 text-red-600" />
            </div>
            Zaif tomonlar (W)
          </h4>
          <ul v-if="swot.weaknesses?.length" class="space-y-2">
            <li v-for="(item, i) in swot.weaknesses" :key="i" class="flex items-start gap-2 text-sm text-red-800">
              <span class="text-red-400 mt-1">-</span>
              {{ item }}
            </li>
          </ul>
          <p v-else class="text-sm text-red-600">Ma'lumot yo'q</p>
        </div>

        <!-- Opportunities -->
        <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-5 border border-blue-200">
          <h4 class="font-medium text-blue-900 mb-4 flex items-center gap-2">
            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
              <LightBulbIcon class="w-5 h-5 text-blue-600" />
            </div>
            Imkoniyatlar (O)
          </h4>
          <ul v-if="swot.opportunities?.length" class="space-y-2">
            <li v-for="(item, i) in swot.opportunities" :key="i" class="flex items-start gap-2 text-sm text-blue-800">
              <span class="text-blue-400 mt-1">→</span>
              {{ item }}
            </li>
          </ul>
          <p v-else class="text-sm text-blue-600">Ma'lumot yo'q</p>
        </div>

        <!-- Threats -->
        <div class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-xl p-5 border border-yellow-200">
          <h4 class="font-medium text-yellow-900 mb-4 flex items-center gap-2">
            <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
              <ExclamationTriangleIcon class="w-5 h-5 text-yellow-600" />
            </div>
            Xavflar (T)
          </h4>
          <ul v-if="swot.threats?.length" class="space-y-2">
            <li v-for="(item, i) in swot.threats" :key="i" class="flex items-start gap-2 text-sm text-yellow-800">
              <span class="text-yellow-400 mt-1">!</span>
              {{ item }}
            </li>
          </ul>
          <p v-else class="text-sm text-yellow-600">Ma'lumot yo'q</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  Squares2X2Icon,
  CheckCircleIcon,
  XCircleIcon,
  LightBulbIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

defineProps({
  swot: {
    type: Object,
    required: true,
    default: () => ({
      strengths: [],
      weaknesses: [],
      opportunities: [],
      threats: [],
    }),
  },
});
</script>
