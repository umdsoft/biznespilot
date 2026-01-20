<script setup>
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import TelegramBotShow from '@/components/telegram/TelegramBotShow.vue'
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
  bot: Object,
  funnels: {
    type: Array,
    default: () => []
  },
  recentStats: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <BusinessLayout :title="bot.first_name">
    <Head :title="bot.first_name" />
    <TelegramBotShow :bot="bot" :funnels="funnels" :recent-stats="recentStats" panel-type="business" />
  </BusinessLayout>
</template>
