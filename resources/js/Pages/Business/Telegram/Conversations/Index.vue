<script setup>
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import TelegramConversationsIndex from '@/components/telegram/TelegramConversationsIndex.vue'

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
  <BusinessLayout :title="`Suhbatlar - @${bot.username}`">
    <Head :title="`Suhbatlar - @${bot.username}`" />
    <TelegramConversationsIndex :bot="bot" :conversations="conversations" :filters="filters" panel-type="business" />
  </BusinessLayout>
</template>
