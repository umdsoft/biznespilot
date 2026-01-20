<script setup>
import { Head } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import TelegramFunnelsIndex from '@/components/telegram/TelegramFunnelsIndex.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

defineProps({
  bot: Object,
  funnels: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <MarketingLayout :title="`${t('telegram.funnels')} - @${bot.username}`">
    <Head :title="`${t('telegram.funnels')} - @${bot.username}`" />
    <TelegramFunnelsIndex :bot="bot" :funnels="funnels" panel-type="marketing" />
  </MarketingLayout>
</template>
