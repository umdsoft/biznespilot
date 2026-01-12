<script setup>
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
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
  <BusinessLayout :title="`Yangi Broadcast - @${bot.username}`">
    <Head :title="`Yangi Broadcast - @${bot.username}`" />
    <TelegramBroadcastCreate :bot="bot" :available-tags="availableTags" :total-users="totalUsers" panel-type="business" />
  </BusinessLayout>
</template>
