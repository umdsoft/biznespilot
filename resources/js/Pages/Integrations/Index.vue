<template>
  <BusinessLayout title="Integratsiyalar">
    <div class="max-w-[1200px] mx-auto">
      <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Integratsiyalar</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Biznesingizni turli platformalar bilan birlashtiring</p>
      </div>

      <!-- Shared integrations content -->
      <IntegrationsContent />
    </div>
  </BusinessLayout>
</template>

<script setup>
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import IntegrationsContent from '@/components/IntegrationsContent.vue';
</script>
