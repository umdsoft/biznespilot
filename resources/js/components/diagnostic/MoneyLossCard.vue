<template>
  <div class="bg-gradient-to-br from-red-50 via-orange-50 to-amber-50 rounded-2xl border border-red-200/50 p-6 overflow-hidden relative">
    <!-- Background decoration -->
    <div class="absolute top-0 right-0 w-32 h-32 bg-red-500/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>

    <div class="relative">
      <!-- Header -->
      <div class="flex items-center gap-3 mb-4">
        <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
          <BanknotesIcon class="w-6 h-6 text-red-600" />
        </div>
        <div>
          <h3 class="font-semibold text-gray-900">Yo'qotilayotgan daromad</h3>
          <p class="text-sm text-gray-500">Muammolar tufayli</p>
        </div>
      </div>

      <!-- Main amounts -->
      <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="text-center">
          <p class="text-sm text-gray-500 mb-1">Kunlik</p>
          <p class="text-xl font-bold text-red-600">{{ formatCurrency(dailyLoss) }}</p>
        </div>
        <div class="text-center border-x border-red-200/50">
          <p class="text-sm text-gray-500 mb-1">Oylik</p>
          <p class="text-2xl font-bold text-red-600">{{ formatCurrency(monthlyLoss) }}</p>
        </div>
        <div class="text-center">
          <p class="text-sm text-gray-500 mb-1">Yillik</p>
          <p class="text-xl font-bold text-red-600">{{ formatCurrency(yearlyLoss) }}</p>
        </div>
      </div>

      <!-- Breakdown -->
      <div v-if="breakdown?.length" class="space-y-3">
        <h4 class="text-sm font-medium text-gray-700">Yo'qotish sabablari:</h4>
        <div v-for="(item, index) in breakdown" :key="index" class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full" :class="categoryColor(item.category)"></span>
            <span class="text-sm text-gray-600">{{ item.reason || item.problem || 'Noma\'lum sabab' }}</span>
          </div>
          <span class="text-sm font-medium text-red-600">{{ formatCurrency(item.amount) }}</span>
        </div>
      </div>

      <!-- Urgency message -->
      <div class="mt-4 p-3 bg-red-100/50 rounded-xl">
        <p class="text-sm text-red-700">
          <ExclamationTriangleIcon class="w-4 h-4 inline mr-1" />
          Har kuni <strong>{{ formatCurrency(dailyLoss) }}</strong> yo'qotilmoqda!
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { BanknotesIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  moneyLoss: {
    type: Object,
    default: () => ({}),
  },
});

const dailyLoss = computed(() => props.moneyLoss?.daily_loss || 0);
const monthlyLoss = computed(() => props.moneyLoss?.monthly_loss || 0);
const yearlyLoss = computed(() => props.moneyLoss?.yearly_loss || 0);
const breakdown = computed(() => props.moneyLoss?.breakdown || []);

function formatCurrency(amount) {
  return new Intl.NumberFormat('uz-UZ', {
    style: 'decimal',
    maximumFractionDigits: 0,
  }).format(amount) + ' UZS';
}

function categoryColor(category) {
  const colors = {
    marketing: 'bg-purple-500',
    sales: 'bg-green-500',
    content: 'bg-blue-500',
    funnel: 'bg-orange-500',
    default: 'bg-gray-500',
  };
  return colors[category] || colors.default;
}
</script>
