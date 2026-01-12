<script setup>
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import TelegramUsersIndex from '@/components/telegram/TelegramUsersIndex.vue'

defineProps({
  bot: Object,
  users: Object,
  allTags: {
    type: Array,
    default: () => []
  },
  stats: {
    type: Object,
    default: () => ({ total: 0, active: 0, blocked: 0 })
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})
</script>

<template>
  <BusinessLayout :title="`Foydalanuvchilar - @${bot.username}`">
    <Head :title="`Foydalanuvchilar - @${bot.username}`" />
    <TelegramUsersIndex :bot="bot" :users="users" :all-tags="allTags" :stats="stats" :filters="filters" panel-type="business" />
  </BusinessLayout>
</template>
