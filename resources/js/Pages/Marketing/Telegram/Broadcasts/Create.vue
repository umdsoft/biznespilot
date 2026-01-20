<script setup>
import { Head } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import TelegramBroadcastCreate from '@/components/telegram/TelegramBroadcastCreate.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

defineProps({
  bot: Object,
  availableTags: {
    type: Array,
    default: () => []
  },
  totalUsers: {
    type: Number,
    default: 0
  }
})
</script>

<template>
  <MarketingLayout :title="`${t('telegram.new_broadcast')} - @${bot.username}`">
    <Head :title="`${t('telegram.new_broadcast')} - @${bot.username}`" />
    <TelegramBroadcastCreate :bot="bot" :available-tags="availableTags" :total-users="totalUsers" panel-type="marketing" />
  </MarketingLayout>
</template>
