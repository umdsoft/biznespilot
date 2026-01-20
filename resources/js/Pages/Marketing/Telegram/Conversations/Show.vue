<script setup>
import { Head } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import TelegramConversationShow from '@/components/telegram/TelegramConversationShow.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

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
  <MarketingLayout :title="`${t('telegram.conversation')} - ${user.full_name}`">
    <Head :title="`${t('telegram.conversation')} - ${user.full_name}`" />
    <TelegramConversationShow :bot="bot" :conversation="conversation" :user="user" :messages="messages" :lead="lead" panel-type="marketing" />
  </MarketingLayout>
</template>
