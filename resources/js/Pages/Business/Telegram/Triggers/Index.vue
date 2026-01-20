<script setup>
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import TelegramTriggersIndex from '@/components/telegram/TelegramTriggersIndex.vue'
import { useI18n } from '@/i18n';

const { t } = useI18n();

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
  <BusinessLayout :title="`${bot.first_name} - Triggerlar`">
    <Head :title="`${bot.first_name} - Triggerlar`" />
    <TelegramTriggersIndex :bot="bot" :triggers="triggers" :funnels="funnels" panel-type="business" />
  </BusinessLayout>
</template>
