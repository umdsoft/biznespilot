<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-50 to-primary-100 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-primary-900">BiznesPilot AI</h1>
        <p class="mt-2 text-gray-600">O'zbekiston startaplari uchun Marketing Intelligence</p>
      </div>

      <Card :padding="false">
        <div class="px-8 py-6">
          <RouterView />
        </div>
      </Card>

      <p class="mt-6 text-center text-sm text-gray-600">
        &copy; {{ new Date().getFullYear() }} BiznesPilot AI. Barcha huquqlar himoyalangan.
      </p>
    </div>
  </div>
</template>

<script setup>
import { RouterView } from 'vue-router';
import Card from '@/components/Card.vue';
</script>
