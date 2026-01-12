<script setup>
import { Head } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import TelegramBroadcastsIndex from '@/components/telegram/TelegramBroadcastsIndex.vue'

defineProps({
  bot: Object,
  broadcasts: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <MarketingLayout :title="`Broadcast - @${bot.username}`">
    <Head :title="`Broadcast - @${bot.username}`" />
    <TelegramBroadcastsIndex :bot="bot" :broadcasts="broadcasts" panel-type="marketing" />
  </MarketingLayout>
</template>
