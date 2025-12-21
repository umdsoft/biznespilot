<template>
  <div class="bg-white rounded-lg border p-4">
    <div class="flex items-start justify-between mb-3">
      <div>
        <h4 class="font-medium text-gray-900">{{ categoryLabel }}</h4>
        <p v-if="allocation.channel" class="text-sm text-gray-500">
          {{ channelLabel }}
        </p>
      </div>
      <span
        class="px-2 py-1 text-xs font-medium rounded-full"
        :class="statusClass"
      >
        {{ statusLabel }}
      </span>
    </div>

    <!-- Budget progress -->
    <div class="mb-3">
      <div class="flex items-center justify-between text-sm mb-1">
        <span class="text-gray-600">
          {{ formatMoney(allocation.spent_amount) }} / {{ formatMoney(allocation.planned_budget) }}
        </span>
        <span class="font-medium" :class="spentPercentClass">{{ spentPercent.toFixed(1) }}%</span>
      </div>
      <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
        <div
          class="h-full rounded-full transition-all"
          :class="progressBarClass"
          :style="{ width: `${Math.min(spentPercent, 100)}%` }"
        ></div>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 gap-3 text-sm">
      <div>
        <span class="text-gray-500">Qoldi</span>
        <p class="font-medium" :class="allocation.remaining_amount < 0 ? 'text-red-600' : 'text-gray-900'">
          {{ formatMoney(allocation.remaining_amount) }}
        </p>
      </div>
      <div v-if="allocation.actual_leads > 0">
        <span class="text-gray-500">Lidlar</span>
        <p class="font-medium text-gray-900">{{ allocation.actual_leads }}</p>
      </div>
      <div v-if="allocation.cost_per_lead > 0">
        <span class="text-gray-500">Lid narxi</span>
        <p class="font-medium text-gray-900">{{ formatMoney(allocation.cost_per_lead) }}</p>
      </div>
      <div v-if="allocation.actual_roi !== null">
        <span class="text-gray-500">ROI</span>
        <p class="font-medium" :class="roiColor">
          {{ allocation.actual_roi?.toFixed(1) }}%
        </p>
      </div>
    </div>

    <!-- Overspend warning -->
    <div
      v-if="allocation.overspend_alert"
      class="mt-3 flex items-center space-x-2 text-red-600 text-sm"
    >
      <ExclamationTriangleIcon class="w-4 h-4" />
      <span>Byudjet oshib ketdi!</span>
    </div>

    <!-- Actions -->
    <div v-if="editable" class="mt-3 pt-3 border-t">
      <button
        @click="$emit('add-spending')"
        class="text-indigo-600 hover:text-indigo-800 text-sm"
      >
        Xarajat qo'shish
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  allocation: {
    type: Object,
    required: true,
  },
  editable: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['add-spending']);

const categoryLabel = computed(() => {
  const labels = {
    marketing: 'Marketing',
    advertising: 'Reklama',
    content: 'Kontent',
    tools: 'Asboblar',
    team: 'Jamoa',
    events: 'Tadbirlar',
    pr: 'PR',
    other: 'Boshqa',
  };
  return labels[props.allocation.category] || props.allocation.category;
});

const channelLabel = computed(() => {
  const labels = {
    instagram: 'Instagram',
    telegram: 'Telegram',
    facebook: 'Facebook',
    google: 'Google Ads',
    tiktok: 'TikTok',
    youtube: 'YouTube',
  };
  return labels[props.allocation.channel] || props.allocation.channel;
});

const statusLabel = computed(() => {
  const labels = {
    planned: 'Rejalashtirilgan',
    approved: 'Tasdiqlangan',
    active: 'Faol',
    paused: 'To\'xtatilgan',
    completed: 'Tugallangan',
    cancelled: 'Bekor qilingan',
  };
  return labels[props.allocation.status] || props.allocation.status;
});

const statusClass = computed(() => {
  const classes = {
    planned: 'bg-gray-100 text-gray-700',
    approved: 'bg-blue-100 text-blue-700',
    active: 'bg-green-100 text-green-700',
    paused: 'bg-yellow-100 text-yellow-700',
    completed: 'bg-emerald-100 text-emerald-700',
    cancelled: 'bg-red-100 text-red-700',
  };
  return classes[props.allocation.status] || 'bg-gray-100 text-gray-700';
});

const spentPercent = computed(() => {
  const budget = props.allocation.allocated_budget || props.allocation.planned_budget;
  if (!budget || budget === 0) return 0;
  return (props.allocation.spent_amount / budget) * 100;
});

const progressBarClass = computed(() => {
  if (spentPercent.value >= 100) return 'bg-red-500';
  if (spentPercent.value >= 80) return 'bg-yellow-500';
  if (spentPercent.value >= 50) return 'bg-blue-500';
  return 'bg-green-500';
});

const spentPercentClass = computed(() => {
  if (spentPercent.value >= 100) return 'text-red-600';
  if (spentPercent.value >= 80) return 'text-yellow-600';
  return 'text-gray-600';
});

const roiColor = computed(() => {
  const roi = props.allocation.actual_roi || 0;
  if (roi >= 200) return 'text-emerald-600';
  if (roi >= 100) return 'text-green-600';
  if (roi >= 50) return 'text-blue-600';
  if (roi >= 0) return 'text-yellow-600';
  return 'text-red-600';
});

function formatMoney(value) {
  if (value === null || value === undefined) return '-';

  const absValue = Math.abs(value);
  const sign = value < 0 ? '-' : '';

  if (absValue >= 1000000) {
    return `${sign}${(absValue / 1000000).toFixed(1)}M`;
  }
  if (absValue >= 1000) {
    return `${sign}${(absValue / 1000).toFixed(1)}K`;
  }
  return `${sign}${absValue.toLocaleString()}`;
}
</script>
