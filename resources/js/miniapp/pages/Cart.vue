<template>
    <div :class="cart.isEmpty ? 'pb-nav' : 'pb-nav-sticky'">
        <!-- Header -->
        <div class="sticky top-0 z-20 header-blur">
            <div style="padding: 12px 16px">
                <div class="flex items-center justify-between">
                    <h1 style="font-size: 22px; font-weight: 700; color: var(--tg-theme-text-color)">
                        🛒 Savat
                        <span v-if="!cart.isEmpty" style="font-size: 15px; font-weight: 400; color: var(--tg-theme-hint-color)">
                            ({{ cart.itemCount }})
                        </span>
                    </h1>
                    <button
                        v-if="!cart.isEmpty"
                        @click="clearCartConfirm"
                        class="btn-ghost"
                        style="color: var(--color-error); font-size: 14px"
                    >
                        🗑️ Tozalash
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty cart -->
        <div v-if="cart.isEmpty" class="empty-state">
            <div class="empty-state-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                </svg>
            </div>
            <p class="empty-state-title">Savat bo'sh</p>
            <p class="empty-state-desc">Mahsulotlarni ko'rib chiqing va savatga qo'shing 🛍️</p>
            <div class="empty-state-action">
                <button @click="goHome" class="btn-secondary">Xarid qilish</button>
            </div>
        </div>

        <!-- Cart content -->
        <template v-else>
            <!-- Order summary (TOP) -->
            <div style="padding: 0 16px 12px">
                <div class="order-summary">
                    <h3 class="section-title" style="margin-bottom: 2px">📋 Buyurtma xulosasi</h3>
                    <div class="order-summary-row">
                        <span class="label">🛒 Mahsulotlar ({{ cart.itemCount }})</span>
                        <span class="value">{{ formatPrice(cart.subtotal) }}</span>
                    </div>
                    <div v-if="cart.promoApplied" class="order-summary-row">
                        <span class="label">🏷️ Chegirma</span>
                        <span style="color: var(--color-success); font-weight: 500">-{{ formatPrice(cart.discountAmount) }}</span>
                    </div>
                    <div class="order-summary-total">
                        <span class="label">💰 Jami</span>
                        <span class="value">{{ formatPrice(cart.total) }}</span>
                    </div>
                </div>
            </div>

            <!-- Cart items -->
            <div style="padding: 0 16px">
                <h3 style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--tg-theme-hint-color); margin-bottom: 12px">
                    📦 Mahsulotlar
                </h3>
                <div style="display: flex; flex-direction: column; gap: 12px">
                    <div
                        v-for="item in cart.items"
                        :key="`${item.product_id}-${item.variant_id}`"
                        class="flex"
                        style="gap: 14px; padding: 16px; border-radius: 14px; border: 1px solid var(--color-border); background-color: var(--tg-theme-bg-color)"
                    >
                        <!-- Image -->
                        <div
                            @click="goToProduct(item.slug)"
                            class="shrink-0 overflow-hidden"
                            style="width: 72px; height: 72px; border-radius: 10px"
                        >
                            <img
                                v-if="item.image"
                                :src="item.image"
                                :alt="item.name"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center"
                                style="background-color: var(--tg-theme-secondary-bg-color); font-size: 24px"
                            >
                                📦
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="flex flex-1 flex-col justify-between min-w-0">
                            <div>
                                <p
                                    class="line-clamp-2"
                                    style="font-size: 15px; font-weight: 600; line-height: 1.3; color: var(--tg-theme-text-color)"
                                    @click="goToProduct(item.slug)"
                                >
                                    {{ item.name }}
                                </p>
                                <p v-if="item.variant_name" style="font-size: 13px; color: var(--tg-theme-hint-color); margin-top: 2px">
                                    {{ item.variant_name }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between" style="margin-top: 8px">
                                <span style="font-size: 16px; font-weight: 700; color: var(--tg-theme-text-color)">
                                    {{ formatPrice((item.sale_price || item.price) * item.quantity) }}
                                </span>

                                <!-- Quantity -->
                                <div class="flex items-center" style="gap: 4px">
                                    <button
                                        @click="cart.decrementQuantity(item.product_id, item.variant_id)"
                                        class="qty-btn"
                                    >
                                        <span v-if="item.quantity === 1" style="font-size: 14px">🗑</span>
                                        <span v-else>−</span>
                                    </button>
                                    <span class="qty-value">{{ item.quantity }}</span>
                                    <button
                                        @click="cart.incrementQuantity(item.product_id, item.variant_id)"
                                        class="qty-btn"
                                        style="color: var(--tg-theme-button-color)"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sticky checkout button (above bottom nav) -->
            <div class="sticky-bottom-bar with-nav">
                <button @click="goToCheckout" class="btn-primary" style="gap: 8px">
                    Buyurtma berish — {{ formatPrice(cart.total) }}
                    <svg style="width: 18px; height: 18px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </button>
            </div>
        </template>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useTelegram } from '../composables/useTelegram'
import { formatPrice } from '../utils/formatters'

const router = useRouter()
const cart = useCartStore()
const { hapticImpact, showConfirm } = useTelegram()

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
</script>
