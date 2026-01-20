<script setup>
import { Head } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import TelegramConversationsIndex from '@/components/telegram/TelegramConversationsIndex.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

defineProps({
  bot: Object,
  conversations: Object,
  filters: {
    type: Object,
    default: () => ({})
  }
})
</script>

<template>
  <MarketingLayout :title="`${t('telegram.conversations')} - @${bot.username}`">
    <Head :title="`${t('telegram.conversations')} - @${bot.username}`" />
    <TelegramConversationsIndex :bot="bot" :conversations="conversations" :filters="filters" panel-type="marketing" />
  </MarketingLayout>
</template>
