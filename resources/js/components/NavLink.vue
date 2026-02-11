<template>
  <Link
    :href="href"
    :class="[
      'flex items-center px-3 py-2 text-sm rounded-lg transition-colors',
      active
        ? 'bg-blue-100 text-blue-700 font-semibold dark:bg-blue-900/30 dark:text-blue-400'
        : 'text-gray-600 dark:text-gray-300 font-medium hover:bg-gray-100 dark:hover:bg-gray-700'
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
