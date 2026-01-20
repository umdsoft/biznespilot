<script setup>
import { Head } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import TelegramBotCreate from '@/components/telegram/TelegramBotCreate.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()
</script>

<template>
  <MarketingLayout :title="t('telegram.add_bot')">
    <Head :title="t('telegram.add_bot')" />
    <TelegramBotCreate panel-type="marketing" />
  </MarketingLayout>
</template>
