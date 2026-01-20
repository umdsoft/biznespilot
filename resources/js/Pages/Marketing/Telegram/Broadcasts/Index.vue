<script setup>
import { Head } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import TelegramBroadcastsIndex from '@/components/telegram/TelegramBroadcastsIndex.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

defineProps({
  bot: Object,
  broadcasts: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <MarketingLayout :title="`${t('telegram.broadcast')} - @${bot.username}`">
    <Head :title="`${t('telegram.broadcast')} - @${bot.username}`" />
    <TelegramBroadcastsIndex :bot="bot" :broadcasts="broadcasts" panel-type="marketing" />
  </MarketingLayout>
</template>
