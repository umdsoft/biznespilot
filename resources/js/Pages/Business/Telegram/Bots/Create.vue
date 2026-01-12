<script setup>
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/Layouts/BusinessLayout.vue'
import TelegramBotCreate from '@/components/telegram/TelegramBotCreate.vue'
</script>

<template>
  <BusinessLayout title="Yangi Bot Qo'shish">
    <Head title="Yangi Bot Qo'shish" />
    <TelegramBotCreate panel-type="business" />
  </BusinessLayout>
</template>
