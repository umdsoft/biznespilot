<template>
  <Link
    :href="href"
    :class="[
      'flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors',
      active
        ? 'bg-primary-50 dark:bg-blue-900/30 text-primary-700 dark:text-blue-400'
        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
    ]"
  >
    <slot />
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  href: {
    type: String,
    required: true,
  },
  active: {
    type: Boolean,
    default: false,
  },
});
</script>
