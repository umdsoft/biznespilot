<script setup>
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import TelegramConversationShow from '@/components/telegram/TelegramConversationShow.vue'
import { useI18n } from '@/i18n';

const { t } = useI18n();

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
  <BusinessLayout :title="`Suhbat - ${user.full_name}`">
    <Head :title="`Suhbat - ${user.full_name}`" />
    <TelegramConversationShow :bot="bot" :conversation="conversation" :user="user" :messages="messages" :lead="lead" panel-type="business" />
  </BusinessLayout>
</template>
