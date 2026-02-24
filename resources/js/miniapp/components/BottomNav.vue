<template>
    <div class="fixed bottom-0 left-0 right-0 z-40 safe-area-bottom"
         style="background-color: var(--tg-theme-bg-color); box-shadow: 0 -2px 10px rgba(0,0,0,0.04)">
        <div style="border-top: 1px solid var(--color-border)">
            <nav class="flex items-stretch" style="height: var(--bottom-nav-height)">
                <button
                    v-for="tab in tabs"
                    :key="tab.name"
                    @click="navigate(tab)"
                    class="flex flex-1 flex-col items-center justify-center gap-1 tap-active"
                    style="padding: 8px 0"
                >
                    <div class="relative">
                        <component :is="iconMap[tab.icon] || iconMap.home" :active="isActive(tab)" />
                        <!-- Cart badge -->
                        <span
                            v-if="tab.icon === 'cart' && hasCart && cart.itemCount > 0"
                            :key="cart.itemCount"
                            class="absolute -top-1.5 -right-3 flex items-center justify-center rounded-full px-1 text-[11px] font-semibold badge-pulse"
                            style="background-color: var(--color-error); color: #fff; min-width: 18px; height: 18px"
                        >
                            {{ cart.itemCount > 99 ? '99+' : cart.itemCount }}
                        </span>
                    </div>
                    <span
                        class="text-[11px] font-medium leading-none"
                        :style="{ color: isActive(tab) ? 'var(--tg-theme-button-color)' : 'var(--tg-theme-hint-color)' }"
                    >
                        {{ tab.label }}
                    </span>
                </button>
            </nav>
        </div>
    </div>
</template>

<script setup>
import { h, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useTelegram } from '../composables/useTelegram'
import { useBotType } from '../composables/useBotType'
import { botActiveRouteGroups } from '../utils/botConfig'

const router = useRouter()
const route = useRoute()
const cart = useCartStore()
const { hapticImpact } = useTelegram()
const { bottomTabs, hasCart } = useBotType()

const tabs = computed(() => bottomTabs.value)

// SVG icon components — 22px size per spec
const IconHome = (props, { attrs }) => {
    const color = attrs.active ? 'var(--tg-theme-button-color)' : 'var(--tg-theme-hint-color)'
    return h('svg', { style: { color, width: '22px', height: '22px' }, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': attrs.active ? '2.2' : '1.8' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25' })
    ])
}

const IconSearch = (props, { attrs }) => {
    const color = attrs.active ? 'var(--tg-theme-button-color)' : 'var(--tg-theme-hint-color)'
    return h('svg', { style: { color, width: '22px', height: '22px' }, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': attrs.active ? '2.2' : '1.8' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z' })
    ])
}

const IconCart = (props, { attrs }) => {
    const color = attrs.active ? 'var(--tg-theme-button-color)' : 'var(--tg-theme-hint-color)'
    return h('svg', { style: { color, width: '22px', height: '22px' }, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': attrs.active ? '2.2' : '1.8' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z' })
    ])
}

const IconOrders = (props, { attrs }) => {
    const color = attrs.active ? 'var(--tg-theme-button-color)' : 'var(--tg-theme-hint-color)'
    return h('svg', { style: { color, width: '22px', height: '22px' }, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': attrs.active ? '2.2' : '1.8' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z' })
    ])
}

const IconCalendar = (props, { attrs }) => {
    const color = attrs.active ? 'var(--tg-theme-button-color)' : 'var(--tg-theme-hint-color)'
    return h('svg', { style: { color, width: '22px', height: '22px' }, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': attrs.active ? '2.2' : '1.8' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z' })
    ])
}

const iconMap = {
    home: IconHome,
    search: IconSearch,
    cart: IconCart,
    orders: IconOrders,
    calendar: IconCalendar,
}

function isActive(tab) {
    const group = botActiveRouteGroups[tab.name]
    if (group) return group.includes(route.name)
    return route.name === tab.route
}

function navigate(tab) {
    if (route.name === tab.route) return
    hapticImpact('light')
    router.push({ name: tab.route })
}
</script>
