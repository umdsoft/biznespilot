<template>
    <!-- This component has no visual template. It controls Telegram's native BackButton. -->
    <span v-if="false" />
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useTelegram } from '../composables/useTelegram'

const router = useRouter()
const route = useRoute()
const { showBackButton, hideBackButton, hapticImpact } = useTelegram()

function handleBack() {
    hapticImpact('light')

    if (window.history.length > 1) {
        router.back()
    } else {
        router.push({ name: 'home' })
    }
}

onMounted(() => {
    if (route.name !== 'home') {
        showBackButton(handleBack)
    }
})

onUnmounted(() => {
    hideBackButton()
})
</script>
