<template>
    <div class="pb-24">
        <BackButton />

        <!-- Header -->
        <div class="sticky top-0 z-10 px-4 py-3" style="background-color: var(--tg-theme-bg-color)">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-bold" style="color: var(--tg-theme-text-color)">
                    Savat
                </h1>
                <button
                    v-if="!cart.isEmpty"
                    @click="clearCartConfirm"
                    class="text-sm"
                    style="color: var(--tg-theme-hint-color)"
                >
                    Tozalash
                </button>
            </div>
        </div>

        <!-- Empty cart -->
        <div v-if="cart.isEmpty" class="px-4 py-16 text-center">
            <svg class="mx-auto h-16 w-16" style="color: var(--tg-theme-hint-color); opacity: 0.3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
            </svg>
            <p class="mt-4 text-sm font-medium" style="color: var(--tg-theme-text-color)">
                Savat bo'sh
            </p>
            <p class="mt-1 text-xs" style="color: var(--tg-theme-hint-color)">
                Mahsulotlarni ko'rib chiqing va savatga qo'shing
            </p>
            <button
                @click="goHome"
                class="mt-4 rounded-xl px-6 py-2.5 text-sm font-medium"
                style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
            >
                Xarid qilish
            </button>
        </div>

        <!-- Cart items -->
        <div v-else class="px-4">
            <div class="space-y-2.5">
                <div
                    v-for="item in cart.items"
                    :key="`${item.product_id}-${item.variant_id}`"
                    class="flex gap-3 rounded-2xl p-3"
                    :style="{ backgroundColor: 'var(--tg-theme-secondary-bg-color)', boxShadow: '0 1px 3px rgba(0,0,0,0.06)' }"
                >
                    <!-- Image -->
                    <div
                        class="h-[76px] w-[76px] shrink-0 overflow-hidden rounded-xl"
                        @click="goToProduct(item.slug)"
                    >
                        <img
                            v-if="item.image"
                            :src="item.image"
                            :alt="item.name"
                            class="h-full w-full object-cover"
                        />
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center text-2xl"
                            style="background-color: var(--tg-theme-bg-color)"
                        >
                            📦
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="flex flex-1 flex-col justify-between">
                        <div>
                            <p
                                class="line-clamp-2 text-sm font-medium leading-tight flex items-start gap-0.5"
                                style="color: var(--tg-theme-text-color)"
                                @click="goToProduct(item.slug)"
                            >
                                <span class="flex-1">{{ item.name }}</span>
                                <svg class="h-4 w-4 shrink-0 mt-0.5" style="color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </p>
                            <p v-if="item.variant_name" class="mt-0.5 text-xs" style="color: var(--tg-theme-hint-color)">
                                {{ item.variant_name }}
                            </p>
                        </div>

                        <div class="mt-2 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold" style="color: var(--tg-theme-text-color)">
                                    {{ formatPrice((item.sale_price || item.price) * item.quantity) }}
                                </span>
                                <span
                                    v-if="item.sale_price"
                                    class="text-xs line-through"
                                    style="color: var(--tg-theme-hint-color)"
                                >
                                    {{ formatPrice(item.price * item.quantity) }}
                                </span>
                            </div>

                            <!-- Quantity -->
                            <div class="flex items-center gap-1.5 rounded-xl px-1.5 py-1" style="background-color: var(--tg-theme-bg-color)">
                                <button
                                    @click="cart.decrementQuantity(item.product_id, item.variant_id)"
                                    class="flex h-7 w-7 items-center justify-center rounded-md text-sm font-bold"
                                    style="color: var(--tg-theme-text-color)"
                                >
                                    {{ item.quantity === 1 ? '🗑' : '-' }}
                                </button>
                                <span class="min-w-[18px] text-center text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                                    {{ item.quantity }}
                                </span>
                                <button
                                    @click="cart.incrementQuantity(item.product_id, item.variant_id)"
                                    class="flex h-7 w-7 items-center justify-center rounded-md text-sm font-bold"
                                    style="color: var(--tg-theme-text-color)"
                                >
                                    +
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Promo code -->
            <div class="mt-4">
                <div
                    class="flex items-center gap-2 rounded-xl px-3 py-2.5"
                    style="background-color: var(--tg-theme-secondary-bg-color)"
                >
                    <input
                        v-model="promoInput"
                        type="text"
                        placeholder="Promokod"
                        class="w-full bg-transparent text-sm outline-none"
                        style="color: var(--tg-theme-text-color)"
                        :disabled="cart.promoApplied"
                    />
                    <button
                        v-if="!cart.promoApplied"
                        @click="applyPromo"
                        :disabled="!promoInput.trim() || cart.loading"
                        class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-medium disabled:opacity-50"
                        style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
                    >
                        Qo'llash
                    </button>
                    <button
                        v-else
                        @click="removePromo"
                        class="shrink-0 text-xs font-medium text-red-500"
                    >
                        Bekor qilish
                    </button>
                </div>
                <p v-if="cart.promoError" class="mt-1 px-1 text-xs text-red-500">
                    {{ cart.promoError }}
                </p>
                <p v-if="cart.promoApplied" class="mt-1 px-1 text-xs text-green-600">
                    Promokod qo'llandi: -{{ formatPrice(cart.discountAmount) }}
                </p>
            </div>

            <!-- Summary -->
            <div class="mt-4 rounded-2xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                <div class="flex justify-between text-sm">
                    <span style="color: var(--tg-theme-hint-color)">Mahsulotlar ({{ cart.itemCount }})</span>
                    <span style="color: var(--tg-theme-text-color)">{{ formatPrice(cart.subtotal) }}</span>
                </div>
                <div v-if="cart.promoApplied" class="mt-2 flex justify-between text-sm">
                    <span style="color: var(--tg-theme-hint-color)">Chegirma</span>
                    <span class="text-green-600">-{{ formatPrice(cart.discountAmount) }}</span>
                </div>
                <div class="mt-2 border-t pt-2" style="border-color: var(--tg-theme-bg-color)">
                    <div class="flex justify-between">
                        <span class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">Jami</span>
                        <span class="text-base font-bold" style="color: var(--tg-theme-text-color)">{{ formatPrice(cart.total) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Checkout button -->
        <div
            v-if="!cart.isEmpty"
            class="fixed bottom-0 left-0 right-0 z-20 border-t px-4 py-3 safe-area-bottom"
            style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-secondary-bg-color)"
        >
            <button
                @click="goToCheckout"
                class="w-full rounded-xl py-3.5 text-center text-sm font-semibold tap-active"
                style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
            >
                Buyurtma berish — {{ formatPrice(cart.total) }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useTelegram } from '../composables/useTelegram'
import BackButton from '../components/BackButton.vue'
import { formatPrice } from '../utils/formatters'

const router = useRouter()
const cart = useCartStore()
const { hapticImpact, showConfirm } = useTelegram()

const promoInput = ref(cart.promoCode || '')

function goHome() {
    hapticImpact('light')
    router.push({ name: 'home' })
}

function goToProduct(slug) {
    hapticImpact('light')
    router.push({ name: 'product', params: { slug } })
}

function goToCheckout() {
    hapticImpact('medium')
    router.push({ name: 'checkout' })
}

async function clearCartConfirm() {
    const confirmed = await showConfirm("Savatni tozalashni xohlaysizmi?")
    if (confirmed) {
        cart.clearCart()
        hapticImpact('medium')
    }
}

function applyPromo() {
    cart.applyPromo(promoInput.value)
}

function removePromo() {
    cart.clearPromo()
    promoInput.value = ''
}
</script>
