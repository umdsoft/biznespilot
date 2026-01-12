<script setup>
import { Head } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import TelegramConversationShow from '@/components/telegram/TelegramConversationShow.vue'

defineProps({
  bot: Object,
  conversation: Object,
  user: Object,
  messages: {
    type: Array,
    default: () => []
  },
  lead: Object
})
</script>

<template>
  <MarketingLayout :title="`Suhbat - ${user.full_name}`">
    <Head :title="`Suhbat - ${user.full_name}`" />
    <TelegramConversationShow :bot="bot" :conversation="conversation" :user="user" :messages="messages" :lead="lead" panel-type="marketing" />
  </MarketingLayout>
</template>
