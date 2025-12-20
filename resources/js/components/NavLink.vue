<template>
  <Link
    :href="href"
    :class="[
      'flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors',
      active
        ? 'bg-primary-50 text-primary-700'
        : 'text-gray-700 hover:bg-gray-50'
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
