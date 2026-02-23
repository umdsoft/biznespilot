<script setup>
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import TelegramBotShow from '@/components/telegram/TelegramBotShow.vue'

defineProps({
  bot: Object,
  funnels: {
    type: Array,
    default: () => []
  },
  recentStats: {
    type: Array,
    default: () => []
  },
  store: {
    type: Object,
    default: null
  }
})
</script>

<template>
  <BusinessLayout :title="bot.first_name">
    <Head :title="bot.first_name" />
    <TelegramBotShow :bot="bot" :funnels="funnels" :recent-stats="recentStats" :store="store" panel-type="business" />
  </BusinessLayout>
</template>
