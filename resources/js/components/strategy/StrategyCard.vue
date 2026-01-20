<template>
  <div
    class="bg-white rounded-lg border p-5 hover:shadow-md transition-shadow cursor-pointer"
    :class="{ 'ring-2 ring-indigo-500': isActive }"
    @click="$emit('click')"
  >
    <!-- Header -->
    <div class="flex items-start justify-between mb-4">
      <div class="flex items-center space-x-3">
        <div
          class="w-10 h-10 rounded-lg flex items-center justify-center"
          :class="iconBgClass"
        >
          <component :is="icon" class="w-5 h-5" :class="iconColorClass" />
        </div>
        <div>
          <h3 class="font-semibold text-gray-900">{{ title }}</h3>
          <p class="text-sm text-gray-500">{{ subtitle }}</p>
        </div>
      </div>

      <span
        class="px-2.5 py-1 text-xs font-medium rounded-full"
        :class="statusClass"
      >
        {{ statusLabel }}
      </span>
    </div>

    <!-- Progress -->
    <div v-if="showProgress" class="mb-4">
      <div class="flex items-center justify-between text-sm mb-1">
        <span class="text-gray-600">Progress</span>
        <span class="font-medium" :class="progressTextClass">{{ progress }}%</span>
      </div>
      <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
        <div
          class="h-full rounded-full transition-all duration-500"
          :class="progressBarClass"
          :style="{ width: `${progress}%` }"
        ></div>
      </div>
    </div>

    <!-- Stats -->
    <div v-if="stats && stats.length" class="grid grid-cols-3 gap-3 mb-4">
      <div
        v-for="stat in stats"
        :key="stat.label"
        class="text-center"
      >
        <div class="text-lg font-semibold" :class="stat.color || 'text-gray-900'">
          {{ stat.value }}
        </div>
        <div class="text-xs text-gray-500">{{ stat.label }}</div>
      </div>
    </div>

    <!-- Actions -->
    <div v-if="$slots.actions" class="flex items-center justify-end space-x-2 pt-3 border-t">
      <slot name="actions"></slot>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  CalendarIcon,
  ChartBarIcon,
  ClipboardDocumentListIcon,
  DocumentTextIcon,
} from '@heroicons/vue/24/outline';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    default: '',
  },
  status: {
    type: String,
    default: 'draft',
  },
  progress: {
    type: Number,
    default: 0,
  },
  showProgress: {
    type: Boolean,
    default: true,
  },
  type: {
    type: String,
    default: 'annual', // annual, quarterly, monthly, weekly
  },
  stats: {
    type: Array,
    default: () => [],
  },
  isActive: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['click']);

const icon = computed(() => {
  const icons = {
    annual: CalendarIcon,
    quarterly: ChartBarIcon,
    monthly: ClipboardDocumentListIcon,
    weekly: DocumentTextIcon,
  };
  return icons[props.type] || DocumentTextIcon;
});

const iconBgClass = computed(() => {
  const classes = {
    annual: 'bg-indigo-100',
    quarterly: 'bg-blue-100',
    monthly: 'bg-green-100',
    weekly: 'bg-purple-100',
  };
  return classes[props.type] || 'bg-gray-100';
});

const iconColorClass = computed(() => {
  const classes = {
    annual: 'text-indigo-600',
    quarterly: 'text-blue-600',
    monthly: 'text-green-600',
    weekly: 'text-purple-600',
  };
  return classes[props.type] || 'text-gray-600';
});

const statusLabel = computed(() => {
  const labels = {
    draft: t('strategy.status.draft'),
    active: t('strategy.status.active'),
    completed: t('strategy.status.completed'),
    archived: t('strategy.status.archived'),
  };
  return labels[props.status] || props.status;
});

const statusClass = computed(() => {
  const classes = {
    draft: 'bg-gray-100 text-gray-700',
    active: 'bg-green-100 text-green-700',
    completed: 'bg-blue-100 text-blue-700',
    archived: 'bg-gray-100 text-gray-500',
  };
  return classes[props.status] || 'bg-gray-100 text-gray-700';
});

const progressBarClass = computed(() => {
  if (props.progress >= 100) return 'bg-green-500';
  if (props.progress >= 75) return 'bg-blue-500';
  if (props.progress >= 50) return 'bg-yellow-500';
  if (props.progress >= 25) return 'bg-orange-500';
  return 'bg-gray-300';
});

const progressTextClass = computed(() => {
  if (props.progress >= 100) return 'text-green-600';
  if (props.progress >= 75) return 'text-blue-600';
  if (props.progress >= 50) return 'text-yellow-600';
  if (props.progress >= 25) return 'text-orange-600';
  return 'text-gray-600';
});
</script>
