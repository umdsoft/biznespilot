<script setup>
import { Head } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import TelegramTriggersIndex from '@/components/telegram/TelegramTriggersIndex.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

defineProps({
  bot: Object,
  triggers: {
    type: Array,
    default: () => []
  },
  funnels: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <MarketingLayout :title="`${t('telegram.triggers')} - @${bot.username}`">
    <Head :title="`${t('telegram.triggers')} - @${bot.username}`" />
    <TelegramTriggersIndex :bot="bot" :triggers="triggers" :funnels="funnels" panel-type="marketing" />
  </MarketingLayout>
</template>
