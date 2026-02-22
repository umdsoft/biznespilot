<template>
    <button
        v-if="cart.itemCount > 0"
        @click="goToCart"
        class="fixed bottom-4 right-4 z-30 flex items-center gap-2 rounded-full px-4 py-3 shadow-lg"
        style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
    >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
        </svg>
        <span class="text-sm font-semibold">{{ formatPrice(cart.total) }}</span>
        <span
            class="flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[10px] font-bold"
            style="background-color: var(--tg-theme-button-text-color); color: var(--tg-theme-button-color)"
        >
            {{ cart.itemCount }}
        </span>
    </button>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useTelegram } from '../composables/useTelegram'

const router = useRouter()
const cart = useCartStore()
const { hapticImpact } = useTelegram()

function formatPrice(price) {
    if (!price) return "0 so'm"
    return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + " so'm"
}

function goToCart() {
    hapticImpact('medium')
    router.push({ name: 'cart' })
}
</script>
