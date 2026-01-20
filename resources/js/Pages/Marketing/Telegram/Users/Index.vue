<script setup>
import { Head } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import TelegramUsersIndex from '@/components/telegram/TelegramUsersIndex.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

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
  <MarketingLayout :title="`${t('telegram.users')} - @${bot.username}`">
    <Head :title="`${t('telegram.users')} - @${bot.username}`" />
    <TelegramUsersIndex :bot="bot" :users="users" :all-tags="allTags" :stats="stats" :filters="filters" panel-type="marketing" />
  </MarketingLayout>
</template>
