<template>
    <div class="pb-20">
        <BackButton />

        <!-- Header -->
        <div class="sticky top-0 z-10 px-4 py-3" style="background-color: var(--tg-theme-bg-color)">
            <h1 class="text-lg font-semibold" style="color: var(--tg-theme-text-color)">
                <template v-if="order">Buyurtma #{{ order.order_number }}</template>
                <template v-else>Buyurtma</template>
            </h1>
        </div>

        <!-- Loading -->
        <div v-if="loading && !order" class="flex items-center justify-center py-20">
            <LoadingSpinner />
        </div>

        <!-- Not found -->
        <div v-else-if="!order" class="px-4 py-10 text-center">
            <p class="text-sm" style="color: var(--tg-theme-hint-color)">Buyurtma topilmadi</p>
        </div>

        <template v-else>
            <div class="px-4 space-y-4">
                <!-- Status Card -->
                <div class="rounded-xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs" style="color: var(--tg-theme-hint-color)">Holat</p>
                            <p class="mt-1 text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                                {{ getStatusLabel(order.status) }}
                            </p>
                        </div>
                        <span
                            class="rounded-full px-3 py-1 text-xs font-medium"
                            :class="getStatusColor(order.status)"
                        >
                            {{ getStatusLabel(order.status) }}
                        </span>
                    </div>

                    <!-- Status Actions -->
                    <div v-if="availableTransitions.length > 0" class="mt-4 flex flex-wrap gap-2">
                        <button
                            v-for="transition in availableTransitions"
                            :key="transition.status"
                            @click="changeStatus(transition.status)"
                            :disabled="updatingStatus"
                            class="flex-1 rounded-lg px-3 py-2.5 text-xs font-semibold transition-opacity"
                            :class="{ 'opacity-50': updatingStatus }"
                            :style="{
                                backgroundColor: transition.color,
                                color: '#ffffff',
                                minWidth: '100px',
                            }"
                        >
                            {{ transition.label }}
                        </button>
                    </div>
                </div>

                <!-- Customer Info -->
                <div v-if="order.customer" class="rounded-xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                    <h2 class="mb-3 text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                        Mijoz ma'lumotlari
                    </h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span style="color: var(--tg-theme-hint-color)">Ism</span>
                            <span style="color: var(--tg-theme-text-color)">{{ order.customer.name || 'Noma\'lum' }}</span>
                        </div>
                        <div v-if="order.customer.phone" class="flex justify-between">
                            <span style="color: var(--tg-theme-hint-color)">Telefon</span>
                            <a
                                :href="'tel:' + order.customer.phone"
                                style="color: var(--tg-theme-link-color)"
                            >
                                {{ order.customer.phone }}
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Delivery Address -->
                <div v-if="order.delivery_address" class="rounded-xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                    <h2 class="mb-3 text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                        Yetkazib berish manzili
                    </h2>
                    <p class="text-sm" style="color: var(--tg-theme-text-color)">
                        <template v-if="typeof order.delivery_address === 'object'">
                            {{ formatAddress(order.delivery_address) }}
                        </template>
                        <template v-else>
                            {{ order.delivery_address }}
                        </template>
                    </p>
                </div>

                <!-- Order Items -->
                <div class="rounded-xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                    <h2 class="mb-3 text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                        Mahsulotlar ({{ order.items?.length || 0 }})
                    </h2>
                    <div class="space-y-3">
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex gap-3"
                        >
                            <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg">
                                <img
                                    v-if="item.image"
                                    :src="item.image"
                                    :alt="item.name"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center text-xs"
                                    style="background-color: var(--tg-theme-bg-color); color: var(--tg-theme-hint-color)"
                                >
                                    {{ (item.quantity || 1) }}x
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium leading-tight" style="color: var(--tg-theme-text-color)">
                                    {{ item.name }}
                                </p>
                                <p v-if="item.variant_name" class="mt-0.5 text-xs" style="color: var(--tg-theme-hint-color)">
                                    {{ item.variant_name }}
                                </p>
                                <div class="mt-1 flex items-center justify-between">
                                    <span class="text-xs" style="color: var(--tg-theme-hint-color)">
                                        {{ item.quantity }} x {{ formatPrice(item.price) }}
                                    </span>
                                    <span class="text-sm font-medium" style="color: var(--tg-theme-text-color)">
                                        {{ formatPrice(item.quantity * item.price) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Summary -->
                <div class="rounded-xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between">
                            <span style="color: var(--tg-theme-hint-color)">Mahsulotlar</span>
                            <span style="color: var(--tg-theme-text-color)">{{ formatPrice(order.subtotal) }}</span>
                        </div>
                        <div v-if="order.discount_amount > 0" class="flex justify-between">
                            <span style="color: var(--tg-theme-hint-color)">Chegirma</span>
                            <span class="text-green-600">-{{ formatPrice(order.discount_amount) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span style="color: var(--tg-theme-hint-color)">Yetkazib berish</span>
                            <span style="color: var(--tg-theme-text-color)">
                                {{ order.delivery_fee > 0 ? formatPrice(order.delivery_fee) : 'Bepul' }}
                            </span>
                        </div>
                        <div class="border-t pt-2 mt-2" style="border-color: var(--tg-theme-bg-color)">
                            <div class="flex justify-between">
                                <span class="font-semibold" style="color: var(--tg-theme-text-color)">Jami</span>
                                <span class="text-base font-bold" style="color: var(--tg-theme-text-color)">
                                    {{ formatPrice(order.total) }}
                                </span>
                            </div>
                        </div>
                        <div class="flex justify-between pt-1">
                            <span style="color: var(--tg-theme-hint-color)">To'lov holati</span>
                            <span :style="{ color: order.payment_status === 'paid' ? '#16a34a' : '#ef4444' }">
                                {{ order.payment_status === 'paid' ? 'To\'langan' : 'To\'lanmagan' }}
                            </span>
                        </div>
                        <div v-if="order.payment_method" class="flex justify-between">
                            <span style="color: var(--tg-theme-hint-color)">To'lov usuli</span>
                            <span style="color: var(--tg-theme-text-color)">{{ getPaymentLabel(order.payment_method) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Status History -->
                <div v-if="order.status_history?.length" class="rounded-xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                    <h2 class="mb-3 text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                        Status tarixi
                    </h2>
                    <div class="space-y-2">
                        <div
                            v-for="history in order.status_history"
                            :key="history.id"
                            class="flex items-center gap-3 text-xs"
                        >
                            <div
                                class="h-2 w-2 shrink-0 rounded-full"
                                style="background-color: var(--tg-theme-button-color)"
                            />
                            <div class="flex-1">
                                <span style="color: var(--tg-theme-text-color)">
                                    {{ getStatusLabel(history.from_status) }} -> {{ getStatusLabel(history.to_status) }}
                                </span>
                                <span v-if="history.changed_by" style="color: var(--tg-theme-hint-color)">
                                    ({{ history.changed_by }})
                                </span>
                            </div>
                            <span style="color: var(--tg-theme-hint-color)">
                                {{ formatDate(history.created_at) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div v-if="order.notes" class="rounded-xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                    <h2 class="mb-2 text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                        Izoh
                    </h2>
                    <p class="text-sm" style="color: var(--tg-theme-hint-color)">{{ order.notes }}</p>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useApi } from '../composables/useApi'
import { useTelegram } from '../composables/useTelegram'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import BackButton from '../components/BackButton.vue'

const props = defineProps({
    id: { type: String, required: true },
})

const { get, post } = useApi()
const { hapticImpact, hapticNotification, showConfirm } = useTelegram()

const order = ref(null)
const loading = ref(false)
const updatingStatus = ref(false)

// Status transitions map matching the backend
const statusTransitions = {
    pending: ['confirmed', 'cancelled'],
    confirmed: ['processing', 'cancelled'],
    processing: ['shipped', 'cancelled'],
    shipped: ['delivered'],
    delivered: ['refunded'],
}

const transitionConfig = {
    confirmed: { label: 'Tasdiqlash', color: '#2563eb' },
    processing: { label: 'Tayyorlashni boshlash', color: '#4f46e5' },
    shipped: { label: 'Yetkazishga berish', color: '#7c3aed' },
    delivered: { label: 'Yetkazildi', color: '#16a34a' },
    cancelled: { label: 'Bekor qilish', color: '#dc2626' },
    refunded: { label: 'Qaytarish', color: '#6b7280' },
}

const availableTransitions = computed(() => {
    if (!order.value) return []
    const allowed = statusTransitions[order.value.status] || []
    return allowed.map(status => ({
        status,
        label: transitionConfig[status]?.label || status,
        color: transitionConfig[status]?.color || '#6b7280',
    }))
})

function getStatusLabel(status) {
    const labels = {
        pending: 'Kutilmoqda',
        confirmed: 'Tasdiqlangan',
        processing: 'Tayyorlanmoqda',
        shipped: 'Yetkazilmoqda',
        delivered: 'Yetkazildi',
        cancelled: 'Bekor qilingan',
        refunded: 'Qaytarilgan',
    }
    return labels[status] || status
}

function getStatusColor(status) {
    const colors = {
        pending: 'text-yellow-600 bg-yellow-50',
        confirmed: 'text-blue-600 bg-blue-50',
        processing: 'text-indigo-600 bg-indigo-50',
        shipped: 'text-purple-600 bg-purple-50',
        delivered: 'text-green-600 bg-green-50',
        cancelled: 'text-red-600 bg-red-50',
        refunded: 'text-gray-600 bg-gray-50',
    }
    return colors[status] || 'text-gray-600 bg-gray-50'
}

function getPaymentLabel(method) {
    const labels = {
        cash: 'Naqd pul',
        card: 'Plastik karta',
        click: 'Click',
        payme: 'Payme',
    }
    return labels[method] || method
}

function formatPrice(price) {
    if (!price) return "0 so'm"
    return Math.round(parseFloat(price)).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + " so'm"
}

function formatDate(dateStr) {
    if (!dateStr) return ''
    const date = new Date(dateStr)
    return date.toLocaleDateString('uz-Latn', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    })
}

function formatAddress(addr) {
    if (!addr) return ''
    const parts = []
    if (addr.address || addr.street) parts.push(addr.address || addr.street)
    if (addr.apartment) parts.push('kv. ' + addr.apartment)
    if (addr.city) parts.push(addr.city)
    if (addr.comment) parts.push('(' + addr.comment + ')')
    return parts.join(', ') || JSON.stringify(addr)
}

async function changeStatus(newStatus) {
    const label = transitionConfig[newStatus]?.label || newStatus
    const confirmed = await showConfirm(`Buyurtma holatini "${label}" ga o'zgartirmoqchimisiz?`)
    if (!confirmed) return

    hapticImpact('medium')
    updatingStatus.value = true
    try {
        const data = await post(`/admin/orders/${props.id}/status`, { status: newStatus })
        if (data.success && data.order) {
            order.value = data.order
            hapticNotification('success')
        }
    } catch (err) {
        hapticNotification('error')
        const message = err.response?.data?.error || 'Statusni yangilashda xatolik'
        console.error('[MiniApp Admin] Status update error:', message, err)
    } finally {
        updatingStatus.value = false
    }
}

async function fetchOrder() {
    loading.value = true
    try {
        const data = await get(`/admin/orders/${props.id}`)
        order.value = data
    } catch (err) {
        console.error('[MiniApp Admin] Order fetch error:', err)
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    fetchOrder()
})
</script>
