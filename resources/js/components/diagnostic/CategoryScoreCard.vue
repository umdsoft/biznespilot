<template>
  <div
    class="bg-white rounded-lg border p-4 transition-all hover:shadow-md"
    :class="borderColorClass"
  >
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center space-x-2">
        <div
          class="w-10 h-10 rounded-lg flex items-center justify-center"
          :class="bgColorClass"
        >
          <component :is="icon" class="w-5 h-5" :class="iconColorClass" />
        </div>
        <span class="font-medium text-gray-900">{{ label }}</span>
      </div>
      <span class="text-2xl font-bold" :class="textColorClass">{{ score }}</span>
    </div>

    <!-- Progress bar -->
    <div class="relative h-2 bg-gray-100 rounded-full overflow-hidden">
      <div
        class="absolute left-0 top-0 h-full rounded-full transition-all duration-500"
        :class="progressColorClass"
        :style="{ width: `${score}%` }"
      />
    </div>

    <!-- Status and trend -->
    <div class="flex items-center justify-between mt-2">
      <span class="text-sm" :class="textColorClass">{{ statusLabel }}</span>
      <div v-if="trend" class="flex items-center text-xs" :class="trendColorClass">
        <component :is="trendIcon" class="w-3 h-3 mr-1" />
        {{ trend.change > 0 ? '+' : '' }}{{ trend.change }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  ChartBarIcon,
  CurrencyDollarIcon,
  DocumentTextIcon,
  FunnelIcon,
  ChartPieIcon,
  ArrowUpIcon,
  ArrowDownIcon,
  MinusIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  category: {
    type: String,
    required: true,
  },
  score: {
    type: Number,
    default: 0,
  },
  trend: {
    type: Object,
    default: null,
  },
});

const categoryConfig = {
  marketing: {
    label: 'Marketing',
    icon: ChartBarIcon,
  },
  sales: {
    label: 'Sotuvlar',
    icon: CurrencyDollarIcon,
  },
  content: {
    label: 'Kontent',
    icon: DocumentTextIcon,
  },
  funnel: {
    label: 'Funnel',
    icon: FunnelIcon,
  },
  analytics: {
    label: 'Analitika',
    icon: ChartPieIcon,
  },
};

const config = computed(() => categoryConfig[props.category] || categoryConfig.marketing);
const label = computed(() => config.value.label);
const icon = computed(() => config.value.icon);

// Colors based on score
const status = computed(() => {
  if (props.score >= 80) return 'excellent';
  if (props.score >= 60) return 'good';
  if (props.score >= 40) return 'average';
  return 'poor';
});

const statusLabel = computed(() => {
  const labels = {
    excellent: 'Ajoyib',
    good: 'Yaxshi',
    average: "O'rtacha",
    poor: 'Zaif',
  };
  return labels[status.value];
});

const borderColorClass = computed(() => {
  const colors = {
    excellent: 'border-blue-200',
    good: 'border-green-200',
    average: 'border-yellow-200',
    poor: 'border-red-200',
  };
  return colors[status.value];
});

const bgColorClass = computed(() => {
  const colors = {
    excellent: 'bg-blue-100',
    good: 'bg-green-100',
    average: 'bg-yellow-100',
    poor: 'bg-red-100',
  };
  return colors[status.value];
});

const iconColorClass = computed(() => {
  const colors = {
    excellent: 'text-blue-600',
    good: 'text-green-600',
    average: 'text-yellow-600',
    poor: 'text-red-600',
  };
  return colors[status.value];
});

const textColorClass = computed(() => {
  const colors = {
    excellent: 'text-blue-600',
    good: 'text-green-600',
    average: 'text-yellow-600',
    poor: 'text-red-600',
  };
  return colors[status.value];
});

const progressColorClass = computed(() => {
  const colors = {
    excellent: 'bg-blue-500',
    good: 'bg-green-500',
    average: 'bg-yellow-500',
    poor: 'bg-red-500',
  };
  return colors[status.value];
});

const trendIcon = computed(() => {
  if (!props.trend) return MinusIcon;
  if (props.trend.trend === 'up') return ArrowUpIcon;
  if (props.trend.trend === 'down') return ArrowDownIcon;
  return MinusIcon;
});

const trendColorClass = computed(() => {
  if (!props.trend) return 'text-gray-500';
  if (props.trend.trend === 'up') return 'text-green-500';
  if (props.trend.trend === 'down') return 'text-red-500';
  return 'text-gray-500';
});
</script>
