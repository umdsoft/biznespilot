<script setup>
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import TelegramBotCreate from '@/components/telegram/TelegramBotCreate.vue'
import { useI18n } from '@/i18n';

const { t } = useI18n();
</script>

<template>
  <BusinessLayout :title="t('telegram.add_bot')">
    <Head :title="t('telegram.add_bot')" />
    <TelegramBotCreate panel-type="business" />
  </BusinessLayout>
</template>
