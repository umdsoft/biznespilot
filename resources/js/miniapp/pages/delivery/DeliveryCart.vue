<template>
    <div class="pb-nav">
        <!-- Header -->
        <div class="header-blur sticky top-0 z-30">
            <div class="flex items-center justify-between" style="padding: 12px 16px">
                <h1 class="text-lg font-bold" style="color: var(--tg-theme-text-color)">Savat</h1>
                <button v-if="!cart.isEmpty" @click="clearCart" class="text-xs tap-active" style="color: var(--color-error)">Tozalash</button>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="cart.isEmpty" class="empty-state" style="padding: 64px 20px">
            <div class="empty-state-icon">🛒</div>
            <p class="empty-state-title">Savat bo'sh</p>
            <p class="empty-state-subtitle">Menyudan taom tanlab, savatga qo'shing</p>
            <button @click="goHome" class="btn-primary" style="margin-top: 16px; max-width: 200px">Menyuga qaytish</button>
        </div>

        <!-- Cart items -->
        <div v-else style="padding: 0 16px 120px">
            <div class="flex flex-col gap-3" style="margin-bottom: 16px">
                <div
                    v-for="(item, idx) in cart.items"
                    :key="idx"
                    class="flex gap-3 rounded-xl p-3"
                    style="background: var(--tg-theme-secondary-bg-color)"
                >
                    <div class="shrink-0 w-16 h-16 rounded-lg overflow-hidden">
                        <img v-if="item.image" :src="item.image" class="w-full h-full object-cover" />
                        <div v-else class="w-full h-full flex items-center justify-center" style="background: var(--tg-theme-bg-color)">🍔</div>
                    </div>
                    <div style="flex: 1; min-width: 0">
                        <p class="text-sm font-semibold line-clamp-1" style="color: var(--tg-theme-text-color)">{{ item.name }}</p>
                        <p v-if="item.variant_name" class="text-xs" style="color: var(--tg-theme-hint-color)">{{ item.variant_name }}</p>
                        <p v-if="item.modifiers?.length" class="text-xs mt-0.5" style="color: var(--tg-theme-hint-color)">
                            {{ item.modifiers.map(m => m.option_name).join(', ') }}
                        </p>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-sm font-bold" style="color: var(--tg-theme-text-color)">{{ formatPrice(cart.getItemUnitPrice(item) * item.quantity) }}</span>
                            <div class="flex items-center gap-2">
                                <button @click="cart.decrementQuantity(item.product_id, item.variant_id, item.modifiers)" class="w-7 h-7 rounded-full flex items-center justify-center tap-active" style="background: var(--tg-theme-bg-color); font-size: 14px; color: var(--tg-theme-text-color)">-</button>
                                <span class="text-sm font-semibold" style="min-width: 18px; text-align: center; color: var(--tg-theme-text-color)">{{ item.quantity }}</span>
                                <button @click="cart.incrementQuantity(item.product_id, item.variant_id, item.modifiers)" class="w-7 h-7 rounded-full flex items-center justify-center tap-active" style="background: var(--tg-theme-button-color); font-size: 14px; color: var(--tg-theme-button-text-color)">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Promo code -->
            <div class="flex gap-2 mb-4">
                <input
                    v-model="promoInput"
                    type="text"
                    placeholder="Promokod"
                    class="form-input"
                    style="flex: 1"
                    :disabled="cart.promoApplied"
                />
                <button
                    v-if="!cart.promoApplied"
                    @click="cart.applyPromo(promoInput)"
                    :disabled="!promoInput.trim() || cart.loading"
                    class="btn-primary"
                    style="padding: 0 16px; white-space: nowrap"
                >
                    Qo'llash
                </button>
                <button v-else @click="cart.clearPromo(); promoInput = ''" class="btn-ghost" style="color: var(--color-error)">X</button>
            </div>
            <p v-if="cart.promoError" class="form-error" style="margin-bottom: 12px">{{ cart.promoError }}</p>
            <p v-if="cart.promoApplied" class="text-xs mb-3" style="color: var(--color-success)">Promokod qo'llanildi: -{{ formatPrice(cart.discountAmount) }}</p>
        </div>

        <!-- Bottom bar -->
        <div v-if="!cart.isEmpty" class="sticky-bottom-bar with-nav">
            <div class="order-summary" style="margin-bottom: 12px">
                <div class="order-summary-row">
                    <span>Jami ({{ cart.itemCount }} ta)</span>
                    <span>{{ formatPrice(cart.subtotal) }}</span>
                </div>
                <div v-if="cart.discountAmount > 0" class="order-summary-row" style="color: var(--color-success)">
                    <span>Chegirma</span>
                    <span>-{{ formatPrice(cart.discountAmount) }}</span>
                </div>
                <div class="order-summary-total">
                    <span>Jami</span>
                    <span>{{ formatPrice(cart.total) }}</span>
                </div>
            </div>
            <button @click="goCheckout" class="btn-primary">
                Buyurtma berish — {{ formatPrice(cart.total) }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../../stores/cart'
import { useTelegram } from '../../composables/useTelegram'
import { formatPrice } from '../../utils/formatters'

const router = useRouter()
const cart = useCartStore()
const { hapticImpact, showConfirm } = useTelegram()

const promoInput = ref(cart.promoCode || '')

function goHome() {
    router.push({ name: 'home' })
}

function goCheckout() {
    hapticImpact('medium')
    router.push({ name: 'checkout' })
}

async function clearCart() {
    const confirmed = await showConfirm('Savatni tozalamoqchimisiz?')
    if (confirmed) {
        cart.clearCart()
    }
}
</script>
