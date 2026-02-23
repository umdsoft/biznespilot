<template>
    <transition name="cart-slide">
        <div
            v-if="cart.itemCount > 0"
            class="fixed bottom-0 left-0 right-0 z-30 safe-area-bottom"
            style="background-color: var(--tg-theme-bg-color)"
        >
            <button
                @click="goToCart"
                class="mx-4 mb-3 flex w-[calc(100%-32px)] items-center justify-between rounded-2xl px-4 py-3.5 tap-active"
                style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
            >
                <div class="flex items-center gap-2.5">
                    <div class="relative">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                        </svg>
                        <span
                            :key="cart.itemCount"
                            class="absolute -top-1.5 -right-1.5 flex h-4 min-w-4 items-center justify-center rounded-full px-0.5 text-[9px] font-bold badge-pulse"
                            style="background-color: var(--tg-theme-button-text-color); color: var(--tg-theme-button-color)"
                        >
                            {{ cart.itemCount }}
                        </span>
                    </div>
                    <span class="text-sm font-semibold">Savatcha</span>
                </div>

                <span class="text-sm font-bold">{{ formatPrice(cart.total) }}</span>
            </button>
        </div>
    </transition>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useTelegram } from '../composables/useTelegram'
import { formatPrice } from '../utils/formatters'

const router = useRouter()
const cart = useCartStore()
const { hapticImpact } = useTelegram()

function goToCart() {
    hapticImpact('medium')
    router.push({ name: 'cart' })
}
</script>

<style scoped>
.cart-slide-enter-active,
.cart-slide-leave-active {
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s ease;
}
.cart-slide-enter-from,
.cart-slide-leave-to {
    transform: translateY(100%);
    opacity: 0;
}
</style>
