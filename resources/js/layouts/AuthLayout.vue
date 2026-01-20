<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-50 to-primary-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-primary-900 dark:text-primary-100">BiznesPilot AI</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">{{ t('layout.marketing_intelligence') }}</p>
      </div>

      <Card :padding="false">
        <div class="px-8 py-6">
          <RouterView />
        </div>
      </Card>

      <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
        &copy; {{ new Date().getFullYear() }} BiznesPilot AI. {{ t('layout.all_rights_reserved') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { RouterView } from 'vue-router';
import Card from '@/components/Card.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();
</script>
