<template>
  <div :class="cardClasses">
    <div v-if="$slots.header || title" class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
      <slot name="header">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ title }}</h3>
      </slot>
    </div>

    <div :class="bodyClasses">
      <slot />
    </div>

    <div v-if="$slots.footer" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: {
    type: String,
    default: '',
  },
  padding: {
    type: Boolean,
    default: true,
  },
  hover: {
    type: Boolean,
    default: false,
  },
});

const cardClasses = computed(() => {
  const baseClasses = 'bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700';
  const hoverClasses = props.hover ? 'hover:shadow-md transition-shadow cursor-pointer' : '';

  return [baseClasses, hoverClasses].join(' ');
});

const bodyClasses = computed(() => {
  return props.padding ? 'px-6 py-4' : '';
});
</script>
