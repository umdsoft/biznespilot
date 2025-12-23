<template>
  <div
    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl"
    :class="bgClass"
  >
    <span class="text-2xl">{{ emoji }}</span>
    <div>
      <p class="text-sm font-medium" :class="textClass">{{ label }}</p>
      <p v-if="message" class="text-xs" :class="subtextClass">{{ message }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  level: {
    type: String,
    default: 'medium',
    validator: (v) => ['critical', 'weak', 'medium', 'good', 'excellent'].includes(v),
  },
  message: {
    type: String,
    default: '',
  },
});

const config = computed(() => {
  const configs = {
    critical: {
      label: 'Xavfli holat',
      emoji: '😰',
      bgClass: 'bg-red-100',
      textClass: 'text-red-800',
      subtextClass: 'text-red-600',
    },
    weak: {
      label: 'Zaif holat',
      emoji: '😐',
      bgClass: 'bg-orange-100',
      textClass: 'text-orange-800',
      subtextClass: 'text-orange-600',
    },
    medium: {
      label: "O'rtacha holat",
      emoji: '🙂',
      bgClass: 'bg-yellow-100',
      textClass: 'text-yellow-800',
      subtextClass: 'text-yellow-600',
    },
    good: {
      label: 'Yaxshi holat',
      emoji: '😊',
      bgClass: 'bg-green-100',
      textClass: 'text-green-800',
      subtextClass: 'text-green-600',
    },
    excellent: {
      label: "Zo'r holat",
      emoji: '🚀',
      bgClass: 'bg-blue-100',
      textClass: 'text-blue-800',
      subtextClass: 'text-blue-600',
    },
  };
  return configs[props.level] || configs.medium;
});

const label = computed(() => config.value.label);
const emoji = computed(() => config.value.emoji);
const bgClass = computed(() => config.value.bgClass);
const textClass = computed(() => config.value.textClass);
const subtextClass = computed(() => config.value.subtextClass);
</script>
