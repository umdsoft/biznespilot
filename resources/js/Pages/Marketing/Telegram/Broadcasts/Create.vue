<script setup>
import { Head } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import TelegramBroadcastCreate from '@/components/telegram/TelegramBroadcastCreate.vue'

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
  <MarketingLayout :title="`Yangi Broadcast - @${bot.username}`">
    <Head :title="`Yangi Broadcast - @${bot.username}`" />
    <TelegramBroadcastCreate :bot="bot" :available-tags="availableTags" :total-users="totalUsers" panel-type="marketing" />
  </MarketingLayout>
</template>
