<template>
    <div class="min-h-screen" :style="themeStyles">
        <router-view v-slot="{ Component, route }">
            <transition :name="transitionName" mode="out-in">
                <component :is="Component" :key="route.path" />
            </transition>
        </router-view>

        <BottomNav v-if="showBottomNav" />
        <ToastContainer />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useTelegram } from './composables/useTelegram'
import { useBotType } from './composables/useBotType'
import { useStoreInfo } from './stores/store'
import BottomNav from './components/BottomNav.vue'
import ToastContainer from './components/ToastContainer.vue'

const route = useRoute()
const { ready, expand, themeParams } = useTelegram()
const { bottomNavRouteNames, accentColor, bottomTabs } = useBotType()
const storeInfo = useStoreInfo()

const transitionName = ref('fade')

const showBottomNav = computed(() => {
    if (route.name?.startsWith('admin-')) return false
    return bottomNavRouteNames.value.includes(route.name)
})

// Theme priority chain (eng yuqoridan pastga):
//   1. Store admin sozlamalardan tanlagan rang (storeInfo.theme.*)
//   2. Bot turi bo'yicha default accent (useBotType — fallback)
//   3. Telegram WebApp ranglari (themeParams)
//   4. Hardcoded fallback rang
//
// Bu BiznesPilot dashboard "Sozlamalar > Dizayn" da rang o'zgartirilganda
// MiniApp avtomat o'zgarishini ta'minlaydi (cache yo'q — har sahifa
// yuklanishda fetchStore qaytadan o'qiydi).
const themeStyles = computed(() => {
    const adminPrimary = storeInfo.theme?.primary_color
    const adminSecondary = storeInfo.theme?.secondary_color
    const adminBg = storeInfo.theme?.background_color
    const adminText = storeInfo.theme?.text_color
    const fallbackAccent = accentColor.value

    return {
        '--tg-theme-bg-color': adminBg || themeParams.value.bg_color || '#ffffff',
        '--tg-theme-text-color': adminText || themeParams.value.text_color || '#000000',
        '--tg-theme-hint-color': themeParams.value.hint_color || '#999999',
        '--tg-theme-link-color': adminPrimary || themeParams.value.link_color || '#2563eb',
        '--tg-theme-button-color': adminPrimary || fallbackAccent || themeParams.value.button_color || '#2563eb',
        '--tg-theme-button-text-color': themeParams.value.button_text_color || '#ffffff',
        '--tg-theme-secondary-bg-color': adminSecondary || themeParams.value.secondary_bg_color || '#f1f5f9',
    }
})

watch(route, (to, from) => {
    if (!from) return
    const tabRouteNames = bottomTabs.value.map(t => t.route)
    if (tabRouteNames.includes(to.name) && tabRouteNames.includes(from?.name)) {
        transitionName.value = 'fade'
    } else {
        const toDepth = to.path.split('/').length
        const fromDepth = from.path.split('/').length
        transitionName.value = toDepth >= fromDepth ? 'slide-left' : 'slide-right'
    }
})

onMounted(async () => {
    expand()
    ready()

    const el = document.getElementById('miniapp')
    const storeSlug = el?.dataset?.storeSlug
    if (storeSlug) {
        await storeInfo.fetchStore(storeSlug)
    }
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
    transition: transform 0.2s ease, opacity 0.2s ease;
}
.slide-left-enter-from {
    transform: translateX(30px);
    opacity: 0;
}
.slide-left-leave-to {
    transform: translateX(-10px);
    opacity: 0;
}
.slide-right-enter-from {
    transform: translateX(-30px);
    opacity: 0;
}
.slide-right-leave-to {
    transform: translateX(10px);
    opacity: 0;
}
</style>
