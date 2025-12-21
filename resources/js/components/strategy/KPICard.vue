<template>
  <div class="bg-white rounded-lg border p-4">
    <div class="flex items-start justify-between mb-3">
      <div>
        <h4 class="font-medium text-gray-900">{{ kpi.kpi_name }}</h4>
        <p class="text-sm text-gray-500">{{ categoryLabel }}</p>
      </div>
      <span
        class="px-2 py-1 text-xs font-medium rounded-full"
        :class="statusClass"
      >
        {{ statusLabel }}
      </span>
    </div>

    <!-- Progress bar -->
    <div class="mb-3">
      <div class="flex items-center justify-between text-sm mb-1">
        <span class="text-gray-600">{{ formattedCurrent }} / {{ formattedTarget }}</span>
        <span class="font-medium" :class="progressTextClass">{{ kpi.progress_percent?.toFixed(1) }}%</span>
      </div>
      <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
        <div
          class="h-full rounded-full transition-all"
          :class="progressBarClass"
          :style="{ width: `${Math.min(kpi.progress_percent || 0, 100)}%` }"
        ></div>
      </div>
    </div>

    <!-- Trend -->
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-1">
        <component
          :is="trendIcon"
          class="w-4 h-4"
          :class="trendColor"
        />
        <span class="text-sm" :class="trendColor">
          {{ kpi.change_percent ? `${kpi.change_percent > 0 ? '+' : ''}${kpi.change_percent?.toFixed(1)}%` : '-' }}
        </span>
      </div>

      <button
        v-if="editable"
        @click="$emit('update')"
        class="text-indigo-600 hover:text-indigo-800 text-sm"
      >
        Yangilash
      </button>
    </div>

    <!-- Alert badge -->
    <div
      v-if="kpi.alert_triggered"
      class="mt-3 flex items-center space-x-2 text-amber-600 text-sm"
    >
      <ExclamationTriangleIcon class="w-4 h-4" />
      <span>Maqsadga yetish xavf ostida</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  ArrowUpIcon,
  ArrowDownIcon,
  MinusIcon,
  QuestionMarkCircleIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  kpi: {
    type: Object,
    required: true,
  },
  editable: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['update']);

const categoryLabel = computed(() => {
  const labels = {
    revenue: 'Daromad',
    marketing: 'Marketing',
    sales: 'Savdo',
    content: 'Kontent',
    customer: 'Mijozlar',
    operational: 'Operatsion',
  };
  return labels[props.kpi.category] || props.kpi.category;
});

const statusLabel = computed(() => {
  const labels = {
    not_started: 'Boshlanmagan',
    on_track: 'Rejada',
    at_risk: 'Xavf ostida',
    behind: 'Orqada',
    achieved: 'Erishildi',
    exceeded: 'Oshib ketdi',
  };
  return labels[props.kpi.status] || props.kpi.status;
});

const statusClass = computed(() => {
  const classes = {
    not_started: 'bg-gray-100 text-gray-700',
    on_track: 'bg-blue-100 text-blue-700',
    at_risk: 'bg-yellow-100 text-yellow-700',
    behind: 'bg-red-100 text-red-700',
    achieved: 'bg-green-100 text-green-700',
    exceeded: 'bg-emerald-100 text-emerald-700',
  };
  return classes[props.kpi.status] || 'bg-gray-100 text-gray-700';
});

const progressBarClass = computed(() => {
  const status = props.kpi.status;
  const classes = {
    not_started: 'bg-gray-300',
    on_track: 'bg-blue-500',
    at_risk: 'bg-yellow-500',
    behind: 'bg-red-500',
    achieved: 'bg-green-500',
    exceeded: 'bg-emerald-500',
  };
  return classes[status] || 'bg-gray-300';
});

const progressTextClass = computed(() => {
  const status = props.kpi.status;
  const classes = {
    not_started: 'text-gray-600',
    on_track: 'text-blue-600',
    at_risk: 'text-yellow-600',
    behind: 'text-red-600',
    achieved: 'text-green-600',
    exceeded: 'text-emerald-600',
  };
  return classes[status] || 'text-gray-600';
});

const trendIcon = computed(() => {
  const icons = {
    up: ArrowUpIcon,
    down: ArrowDownIcon,
    stable: MinusIcon,
  };
  return icons[props.kpi.trend] || QuestionMarkCircleIcon;
});

const trendColor = computed(() => {
  const colors = {
    up: 'text-green-600',
    down: 'text-red-600',
    stable: 'text-gray-600',
  };
  return colors[props.kpi.trend] || 'text-gray-400';
});

const formattedCurrent = computed(() => {
  return formatValue(props.kpi.current_value);
});

const formattedTarget = computed(() => {
  return formatValue(props.kpi.target_value);
});

function formatValue(value) {
  if (value === null || value === undefined) return '-';

  const unit = props.kpi.unit;

  if (unit === '%') {
    return `${Number(value).toFixed(1)}%`;
  }

  if (unit === 'sum' || ['revenue', 'profit', 'budget', 'spend'].includes(props.kpi.kpi_key)) {
    if (value >= 1000000) {
      return `${(value / 1000000).toFixed(1)}M`;
    }
    if (value >= 1000) {
      return `${(value / 1000).toFixed(1)}K`;
    }
    return Number(value).toLocaleString();
  }

  return Number(value).toLocaleString();
}
</script>
