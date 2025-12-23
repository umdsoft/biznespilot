<template>
  <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden mb-6">
    <!-- Hero Section -->
    <div class="p-6 md:p-8" :class="statusBgClass">
      <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
        <!-- Status Info -->
        <div class="flex items-center gap-4 md:gap-6">
          <div class="text-5xl md:text-6xl">{{ statusEmoji }}</div>
          <div>
            <h2 class="text-xl md:text-2xl font-bold" :class="statusTextClass">
              {{ statusLabel }}
            </h2>
            <p v-if="diagnostic.status_message" class="text-gray-600 mt-1 max-w-lg text-sm md:text-base">
              {{ diagnostic.status_message }}
            </p>
            <div class="flex items-center gap-4 mt-3">
              <div class="flex items-center gap-1 text-sm text-gray-500">
                <CalendarIcon class="w-4 h-4" />
                {{ formattedDate }}
              </div>
              <div v-if="diagnostic.industry_avg_score" class="flex items-center gap-1 text-sm text-gray-500">
                <ChartBarIcon class="w-4 h-4" />
                Soha o'rtachasi: {{ diagnostic.industry_avg_score }}
              </div>
            </div>
          </div>
        </div>

        <!-- Main Score -->
        <ScoreCircle
          :score="diagnostic.overall_score || 0"
          :size="160"
          label="ball"
          :animate="true"
        />
      </div>
    </div>

    <!-- Category Scores -->
    <div class="p-4 md:p-6 border-t border-gray-100">
      <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-4">
        Kategoriya Ballari
      </h3>
      <div class="grid grid-cols-2 md:grid-cols-5 gap-3 md:gap-4">
        <div
          v-for="(score, category) in diagnostic.category_scores"
          :key="category"
          class="bg-gray-50 rounded-xl p-3 md:p-4 hover:bg-gray-100 transition-colors"
        >
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-gray-500 uppercase">
              {{ categoryLabels[category] || category }}
            </span>
            <component
              :is="categoryIcons[category]"
              class="w-4 h-4 text-gray-400"
            />
          </div>
          <p class="text-2xl font-bold" :class="getScoreColorClass(score)">
            {{ score }}
          </p>
          <div class="mt-2 h-1.5 bg-gray-200 rounded-full overflow-hidden">
            <div
              class="h-full rounded-full transition-all duration-500"
              :class="getScoreBarClass(score)"
              :style="{ width: `${score}%` }"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div v-if="diagnostic.money_loss_analysis || diagnostic.action_plan" class="p-4 md:p-6 border-t border-gray-100 bg-gradient-to-r from-gray-50 to-white">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Monthly Loss -->
        <div v-if="diagnostic.money_loss_analysis?.monthly_loss" class="text-center p-3">
          <p class="text-xs text-gray-500 mb-1">Oylik yo'qotish</p>
          <p class="text-lg md:text-xl font-bold text-red-600">
            {{ formatCurrency(diagnostic.money_loss_analysis.monthly_loss) }}
          </p>
        </div>

        <!-- Daily Loss -->
        <div v-if="diagnostic.money_loss_analysis?.daily_loss" class="text-center p-3">
          <p class="text-xs text-gray-500 mb-1">Kunlik yo'qotish</p>
          <p class="text-lg md:text-xl font-bold text-orange-600">
            {{ formatCurrency(diagnostic.money_loss_analysis.daily_loss) }}
          </p>
        </div>

        <!-- Action Plan Time -->
        <div v-if="diagnostic.action_plan?.total_time_hours" class="text-center p-3">
          <p class="text-xs text-gray-500 mb-1">Jami vaqt</p>
          <p class="text-lg md:text-xl font-bold text-indigo-600">
            {{ diagnostic.action_plan.total_time_hours }} soat
          </p>
        </div>

        <!-- Potential Savings -->
        <div v-if="diagnostic.action_plan?.total_potential_savings" class="text-center p-3">
          <p class="text-xs text-gray-500 mb-1">Tejash mumkin</p>
          <p class="text-lg md:text-xl font-bold text-green-600">
            {{ formatCurrency(diagnostic.action_plan.total_potential_savings) }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import ScoreCircle from './ScoreCircle.vue';
import {
  CalendarIcon,
  ChartBarIcon,
  MegaphoneIcon,
  ShoppingCartIcon,
  DocumentTextIcon,
  FunnelIcon,
  CogIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  diagnostic: {
    type: Object,
    required: true,
  },
});

const categoryLabels = {
  marketing: 'Marketing',
  sales: 'Sotuvlar',
  content: 'Kontent',
  funnel: 'Funnel',
  automation: 'Avtomatlashtirish',
};

const categoryIcons = {
  marketing: MegaphoneIcon,
  sales: ShoppingCartIcon,
  content: DocumentTextIcon,
  funnel: FunnelIcon,
  automation: CogIcon,
};

const statusConfig = {
  critical: {
    label: 'Xavfli holat',
    emoji: '😰',
    bg: 'bg-gradient-to-br from-red-50 to-orange-50',
    text: 'text-red-800',
  },
  weak: {
    label: 'Zaif holat',
    emoji: '😐',
    bg: 'bg-gradient-to-br from-orange-50 to-yellow-50',
    text: 'text-orange-800',
  },
  medium: {
    label: "O'rtacha holat",
    emoji: '🙂',
    bg: 'bg-gradient-to-br from-yellow-50 to-green-50',
    text: 'text-yellow-800',
  },
  good: {
    label: 'Yaxshi holat',
    emoji: '😊',
    bg: 'bg-gradient-to-br from-green-50 to-emerald-50',
    text: 'text-green-800',
  },
  excellent: {
    label: "Zo'r holat",
    emoji: '🚀',
    bg: 'bg-gradient-to-br from-blue-50 to-indigo-50',
    text: 'text-blue-800',
  },
};

const currentStatus = computed(() => {
  return statusConfig[props.diagnostic.status_level] || statusConfig.medium;
});

const statusLabel = computed(() => currentStatus.value.label);
const statusEmoji = computed(() => currentStatus.value.emoji);
const statusBgClass = computed(() => currentStatus.value.bg);
const statusTextClass = computed(() => currentStatus.value.text);

const formattedDate = computed(() => {
  if (!props.diagnostic.completed_at) return '';
  return props.diagnostic.completed_at;
});

function getScoreColorClass(score) {
  if (score >= 80) return 'text-blue-600';
  if (score >= 60) return 'text-green-600';
  if (score >= 40) return 'text-yellow-600';
  return 'text-red-600';
}

function getScoreBarClass(score) {
  if (score >= 80) return 'bg-blue-500';
  if (score >= 60) return 'bg-green-500';
  if (score >= 40) return 'bg-yellow-500';
  return 'bg-red-500';
}

function formatCurrency(amount) {
  if (!amount) return '0 UZS';
  return new Intl.NumberFormat('uz-UZ').format(amount) + ' UZS';
}
</script>
