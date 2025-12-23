<template>
  <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
    <!-- Header -->
    <div class="p-6 bg-gradient-to-r from-red-50 to-orange-50 border-b border-gray-100">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
            <CurrencyDollarIcon class="w-6 h-6 text-red-600" />
          </div>
          <div>
            <h3 class="font-semibold text-gray-900">Pul Yo'qotish Tahlili</h3>
            <p class="text-sm text-gray-500">Biznesingiz nima yo'qotmoqda</p>
          </div>
        </div>
        <div class="text-right">
          <p class="text-sm text-gray-500">Oylik yo'qotish</p>
          <p class="text-2xl font-bold text-red-600">
            {{ formatCurrency(moneyLoss.monthly_loss) }}
          </p>
        </div>
      </div>
    </div>

    <!-- Main Stats -->
    <div class="p-6 border-b border-gray-100">
      <div class="grid grid-cols-3 gap-4">
        <div class="text-center p-4 bg-red-50 rounded-xl">
          <p class="text-xs text-gray-500 mb-1">Kunlik</p>
          <p class="text-xl font-bold text-red-600">
            {{ formatCurrency(moneyLoss.daily_loss) }}
          </p>
          <p class="text-xs text-red-500 mt-1">har kuni yo'qotmoqdasiz</p>
        </div>
        <div class="text-center p-4 bg-orange-50 rounded-xl">
          <p class="text-xs text-gray-500 mb-1">Oylik</p>
          <p class="text-xl font-bold text-orange-600">
            {{ formatCurrency(moneyLoss.monthly_loss) }}
          </p>
          <p class="text-xs text-orange-500 mt-1">har oyda yo'qotmoqdasiz</p>
        </div>
        <div class="text-center p-4 bg-yellow-50 rounded-xl">
          <p class="text-xs text-gray-500 mb-1">Yillik</p>
          <p class="text-xl font-bold text-yellow-600">
            {{ formatCurrency(moneyLoss.yearly_loss) }}
          </p>
          <p class="text-xs text-yellow-500 mt-1">har yilda yo'qotmoqdasiz</p>
        </div>
      </div>
    </div>

    <!-- Loss Breakdown -->
    <div class="p-6">
      <h4 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
        <ChartBarIcon class="w-5 h-5 text-gray-400" />
        Yo'qotishlar tafsiloti
      </h4>

      <div class="space-y-4">
        <div
          v-for="(item, index) in moneyLoss.breakdown"
          :key="index"
          class="bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition-colors"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <span
                  class="px-2 py-0.5 text-xs rounded-full font-medium"
                  :class="getCategoryClass(item.category)"
                >
                  {{ getCategoryLabel(item.category) }}
                </span>
              </div>
              <h5 class="font-medium text-gray-900">{{ item.problem }}</h5>
              <p v-if="item.solution_title" class="text-sm text-gray-500 mt-1">
                Yechim: {{ item.solution_title }}
              </p>
            </div>
            <div class="text-right flex-shrink-0">
              <p class="text-lg font-bold text-red-600">
                -{{ formatCurrency(item.amount) }}
              </p>
              <p class="text-xs text-gray-500">oylik</p>
            </div>
          </div>

          <!-- Progress bar showing share of total loss -->
          <div class="mt-3">
            <div class="flex items-center justify-between text-xs mb-1">
              <span class="text-gray-400">Ulushi</span>
              <span class="text-gray-600">{{ getPercentage(item.amount) }}%</span>
            </div>
            <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all"
                :class="getCategoryBarClass(item.category)"
                :style="{ width: `${getPercentage(item.amount)}%` }"
              ></div>
            </div>
          </div>

          <!-- Solution link -->
          <div v-if="item.solution_module" class="mt-3 pt-3 border-t border-gray-200">
            <button
              @click="$emit('navigate', item.solution_module)"
              class="text-sm text-indigo-600 hover:text-indigo-700 font-medium flex items-center gap-1"
            >
              <ArrowRightIcon class="w-4 h-4" />
              Muammoni hal qilish
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!moneyLoss.breakdown?.length" class="text-center py-8">
        <CurrencyDollarIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
        <p class="text-gray-500">Yo'qotishlar tahlili mavjud emas</p>
      </div>
    </div>

    <!-- Summary Alert -->
    <div class="p-4 bg-gradient-to-r from-red-100 to-orange-100 border-t border-red-200">
      <div class="flex items-center gap-3">
        <ExclamationTriangleIcon class="w-6 h-6 text-red-600 flex-shrink-0" />
        <p class="text-sm text-red-800">
          <strong>Diqqat!</strong> Har o'tgan kun sizga
          <span class="font-bold">{{ formatCurrency(moneyLoss.daily_loss) }}</span>
          turmoqda. Quyidagi qadamlarni bajaring!
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  CurrencyDollarIcon,
  ChartBarIcon,
  ArrowRightIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  moneyLoss: {
    type: Object,
    required: true,
    default: () => ({
      monthly_loss: 0,
      yearly_loss: 0,
      daily_loss: 0,
      breakdown: [],
    }),
  },
});

defineEmits(['navigate']);

const categoryLabels = {
  marketing: 'Marketing',
  sales: 'Sotuvlar',
  content: 'Kontent',
  funnel: 'Funnel',
  automation: 'Avtomatlashtirish',
};

const categoryClasses = {
  marketing: 'bg-purple-100 text-purple-700',
  sales: 'bg-green-100 text-green-700',
  content: 'bg-blue-100 text-blue-700',
  funnel: 'bg-orange-100 text-orange-700',
  automation: 'bg-indigo-100 text-indigo-700',
};

const categoryBarClasses = {
  marketing: 'bg-purple-500',
  sales: 'bg-green-500',
  content: 'bg-blue-500',
  funnel: 'bg-orange-500',
  automation: 'bg-indigo-500',
};

function getCategoryLabel(category) {
  return categoryLabels[category] || category;
}

function getCategoryClass(category) {
  return categoryClasses[category] || 'bg-gray-100 text-gray-700';
}

function getCategoryBarClass(category) {
  return categoryBarClasses[category] || 'bg-gray-500';
}

function getPercentage(amount) {
  if (!props.moneyLoss.monthly_loss || !amount) return 0;
  return Math.round((amount / props.moneyLoss.monthly_loss) * 100);
}

function formatCurrency(amount) {
  if (!amount) return '0 UZS';
  return new Intl.NumberFormat('uz-UZ').format(amount) + ' UZS';
}
</script>
