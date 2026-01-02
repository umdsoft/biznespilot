<template>
  <div class="bg-white rounded-lg shadow-sm border border-red-200 p-12 text-center">
    <div class="text-6xl mb-4">⚠️</div>
    <h3 class="text-lg font-semibold text-gray-900 mb-2">
      {{ title || 'Xatolik Yuz Berdi' }}
    </h3>
    <p class="text-gray-600 mb-6 max-w-md mx-auto">
      {{ message || 'Ma\'lumotlarni yuklashda xatolik yuz berdi. Iltimos, qaytadan urinib ko\'ring.' }}
    </p>
    <div class="flex items-center justify-center gap-3">
      <button
        @click="$emit('retry')"
        class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
      >
        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Qayta Urinish
      </button>
      <button
        v-if="showSupport"
        @click="$emit('contact-support')"
        class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
      >
        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        Yordam So'rash
      </button>
    </div>

    <!-- Error Details (Development Only) -->
    <div v-if="errorDetails && isDevelopment" class="mt-6 text-left">
      <details class="bg-red-50 rounded-lg p-4 text-sm">
        <summary class="cursor-pointer font-medium text-red-800 mb-2">
          Xatolik Tafsilotlari (Developer Mode)
        </summary>
        <pre class="text-xs text-red-700 overflow-auto">{{ errorDetails }}</pre>
      </details>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: {
    type: String,
    default: null,
  },
  message: {
    type: String,
    default: null,
  },
  errorDetails: {
    type: [String, Object],
    default: null,
  },
  showSupport: {
    type: Boolean,
    default: true,
  },
});

defineEmits(['retry', 'contact-support']);

const isDevelopment = computed(() => {
  return import.meta.env.DEV || import.meta.env.MODE === 'development';
});
</script>
