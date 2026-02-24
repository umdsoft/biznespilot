<template>
    <div class="pb-safe">
        <!-- Header -->
        <div class="header-blur sticky top-0 z-30">
            <div class="flex items-center gap-3" style="padding: 12px 16px">
                <button @click="goBack" class="tap-active flex items-center justify-center w-9 h-9 rounded-full" style="background: var(--tg-theme-secondary-bg-color)">
                    <svg style="width: 20px; height: 20px; color: var(--tg-theme-text-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                    </svg>
                </button>
                <h1 class="text-base font-bold" style="color: var(--tg-theme-text-color)">Buyurtma #{{ order?.order_number || '' }}</h1>
            </div>
        </div>

        <div v-if="loading" style="padding: 16px">
            <div class="skeleton" style="height: 80px; border-radius: 12px; margin-bottom: 16px"></div>
            <div class="skeleton" style="height: 120px; border-radius: 12px; margin-bottom: 16px"></div>
            <div class="skeleton" style="height: 200px; border-radius: 12px"></div>
        </div>

        <template v-else-if="order">
            <!-- Delivery Progress -->
            <div style="padding: 16px">
                <div class="rounded-2xl p-4" style="background: var(--tg-theme-secondary-bg-color)">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                            {{ getStatusLabel(order.status) }}
                        </span>
                        <span
                            class="text-xs font-medium px-2.5 py-1 rounded-full"
                            :style="{ background: getStatusBg(order.status), color: getStatusColor(order.status) }"
                        >
                            {{ getStatusLabel(order.status) }}
                        </span>
                    </div>

                    <!-- Progress bar -->
                    <div class="flex gap-1.5 mb-3">
                        <div
                            v-for="(step, i) in deliverySteps"
                            :key="i"
                            class="h-1 rounded-full"
                            style="flex: 1"
                            :style="{ background: i <= currentStepIndex ? 'var(--tg-theme-button-color)' : 'var(--color-border)' }"
                        ></div>
                    </div>

                    <!-- Steps -->
                    <div class="flex justify-between">
                        <div
                            v-for="(step, i) in deliverySteps"
                            :key="i"
                            class="flex flex-col items-center"
                            :style="{ opacity: i <= currentStepIndex ? 1 : 0.4 }"
                        >
                            <span style="font-size: 20px; margin-bottom: 4px">{{ step.icon }}</span>
                            <span class="text-[10px] font-medium" style="color: var(--tg-theme-hint-color)">{{ step.label }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order items -->
            <div style="padding: 0 16px 16px">
                <h3 class="text-sm font-semibold mb-3" style="color: var(--tg-theme-text-color)">Buyurtma tarkibi</h3>
                <div class="flex flex-col gap-2">
                    <div
                        v-for="item in order.items"
                        :key="item.id"
                        class="flex items-center justify-between rounded-xl px-3 py-2.5"
                        style="background: var(--tg-theme-secondary-bg-color)"
                    >
                        <div style="flex: 1">
                            <p class="text-sm" style="color: var(--tg-theme-text-color)">{{ item.product_name }}</p>
                            <p v-if="item.variant_name" class="text-xs" style="color: var(--tg-theme-hint-color)">{{ item.variant_name }}</p>
                        </div>
                        <div class="text-right shrink-0 ml-3">
                            <p class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">{{ formatPrice(item.total) }}</p>
                            <p class="text-xs" style="color: var(--tg-theme-hint-color)">{{ item.quantity }} x {{ formatPrice(item.price) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order summary -->
            <div style="padding: 0 16px 16px">
                <div class="order-summary">
                    <div class="order-summary-row">
                        <span>Mahsulotlar</span>
                        <span>{{ formatPrice(order.subtotal) }}</span>
                    </div>
                    <div v-if="order.delivery_fee > 0" class="order-summary-row">
                        <span>Yetkazib berish</span>
                        <span>{{ formatPrice(order.delivery_fee) }}</span>
                    </div>
                    <div v-if="order.discount_amount > 0" class="order-summary-row" style="color: var(--color-success)">
                        <span>Chegirma</span>
                        <span>-{{ formatPrice(order.discount_amount) }}</span>
                    </div>
                    <div class="order-summary-total">
                        <span>Jami</span>
                        <span>{{ formatPrice(order.total) }}</span>
                    </div>
                </div>
            </div>

            <!-- Delivery address -->
            <div v-if="order.delivery_address" style="padding: 0 16px 16px">
                <h3 class="text-sm font-semibold mb-2" style="color: var(--tg-theme-text-color)">Yetkazib berish manzili</h3>
                <div class="rounded-xl p-3" style="background: var(--tg-theme-secondary-bg-color)">
                    <p class="text-sm" style="color: var(--tg-theme-text-color)">
                        {{ [order.delivery_address.city, order.delivery_address.district, order.delivery_address.street].filter(Boolean).join(', ') }}
                    </p>
                    <p v-if="order.delivery_address.comment" class="text-xs mt-1" style="color: var(--tg-theme-hint-color)">{{ order.delivery_address.comment }}</p>
                </div>
            </div>

            <!-- Order date -->
            <div style="padding: 0 16px 24px">
                <p class="text-xs" style="color: var(--tg-theme-hint-color)">
                    Buyurtma vaqti: {{ formatDateTime(order.created_at) }}
                </p>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useOrderStore } from '../../stores/order'
import { useTelegram } from '../../composables/useTelegram'
import { formatPrice } from '../../utils/formatters'

const props = defineProps({ number: String })
const router = useRouter()
const route = useRoute()
const orderStore = useOrderStore()
const { hapticImpact } = useTelegram()

const loading = ref(true)
const order = ref(null)

const deliverySteps = [
    { key: 'pending', icon: '📋', label: 'Qabul' },
    { key: 'confirmed', icon: '✅', label: 'Tasdiqlandi' },
    { key: 'processing', icon: '👨‍🍳', label: 'Tayyorlanmoqda' },
    { key: 'shipped', icon: '🚚', label: "Yo'lda" },
    { key: 'delivered', icon: '📦', label: 'Yetkazildi' },
]

const currentStepIndex = computed(() => {
    if (!order.value) return -1
    const idx = deliverySteps.findIndex(s => s.key === order.value.status)
    return idx >= 0 ? idx : -1
})

function getStatusLabel(status) {
    const labels = {
        pending: 'Kutilmoqda',
        confirmed: 'Tasdiqlandi',
        processing: 'Tayyorlanmoqda',
        shipped: "Yo'lda",
        delivered: 'Yetkazildi',
        cancelled: 'Bekor qilindi',
        refunded: 'Qaytarildi',
    }
    return labels[status] || status
}

function getStatusColor(status) {
    const map = { pending: '#F59E0B', confirmed: '#3B82F6', processing: '#8B5CF6', shipped: '#F97316', delivered: '#10B981', cancelled: '#EF4444', refunded: '#6B7280' }
    return map[status] || '#6B7280'
}

function getStatusBg(status) {
    const map = { pending: '#FEF3C7', confirmed: '#DBEAFE', processing: '#EDE9FE', shipped: '#FFEDD5', delivered: '#D1FAE5', cancelled: '#FEE2E2', refunded: '#F3F4F6' }
    return map[status] || '#F3F4F6'
}

function formatDateTime(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    return d.toLocaleString('uz-UZ', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function goBack() {
    hapticImpact('light')
    router.back()
}

onMounted(async () => {
    loading.value = true
    try {
        const orderNumber = props.number || route.params.number
        await orderStore.fetchOrder(orderNumber)
        order.value = orderStore.currentOrder
    } catch (err) {
        console.error('[DeliveryOrderDetail] Load error:', err)
    } finally {
        loading.value = false
    }
})
</script>
