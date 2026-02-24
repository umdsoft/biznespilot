<template>
    <div style="padding-bottom: 24px">
        <BackButton />

        <!-- Header -->
        <div class="sticky top-0 z-20 header-blur">
            <div style="padding: 12px 16px">
                <h1 style="font-size: 18px; font-weight: 700; color: var(--tg-theme-text-color)">
                    Buyurtma #{{ number }}
                </h1>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="orderStore.loading && !order" class="flex items-center justify-center" style="padding: 80px 0">
            <LoadingSpinner />
        </div>

        <!-- Not found -->
        <div v-else-if="!order" class="empty-state" style="min-height: 40vh">
            <div class="empty-state-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <p class="empty-state-title">Buyurtma topilmadi</p>
        </div>

        <template v-else>
            <div style="padding: 0 16px; display: flex; flex-direction: column; gap: 16px">
                <!-- Status -->
                <div style="padding: 16px; border-radius: 14px; border: 1px solid var(--color-border); background-color: var(--tg-theme-bg-color)">
                    <div class="flex items-center justify-between">
                        <div>
                            <p style="font-size: 13px; color: var(--tg-theme-hint-color)">Holat</p>
                            <p style="font-size: 15px; font-weight: 600; color: var(--tg-theme-text-color); margin-top: 4px">
                                {{ orderStore.getStatusLabel(order.status) }}
                            </p>
                        </div>
                        <span
                            class="rounded-full"
                            style="padding: 4px 12px; font-size: 12px; font-weight: 600"
                            :class="orderStore.getStatusColor(order.status)"
                        >
                            {{ orderStore.getStatusLabel(order.status) }}
                        </span>
                    </div>

                    <!-- Status Timeline -->
                    <div v-if="statusTimeline.length" style="margin-top: 16px">
                        <div class="relative">
                            <div
                                v-for="(step, idx) in statusTimeline"
                                :key="step.status"
                                class="relative flex" style="gap: 12px; padding-bottom: 16px"
                                :style="idx === statusTimeline.length - 1 ? { paddingBottom: 0 } : {}"
                            >
                                <!-- Line -->
                                <div class="flex flex-col items-center">
                                    <div
                                        class="flex shrink-0 items-center justify-center rounded-full"
                                        :style="{
                                            width: '24px',
                                            height: '24px',
                                            backgroundColor: step.completed || step.current
                                                ? 'var(--tg-theme-button-color)'
                                                : 'transparent',
                                            border: step.completed || step.current
                                                ? 'none'
                                                : '2px solid var(--tg-theme-hint-color)',
                                            opacity: step.completed || step.current ? 1 : 0.4,
                                        }"
                                    >
                                        <svg
                                            v-if="step.completed"
                                            style="width: 14px; height: 14px; color: var(--tg-theme-button-text-color)"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <div
                                            v-else-if="step.current"
                                            style="width: 8px; height: 8px; border-radius: 50%; background-color: var(--tg-theme-button-text-color)"
                                        />
                                    </div>
                                    <div
                                        v-if="idx < statusTimeline.length - 1"
                                        class="flex-1"
                                        :style="{
                                            width: '2px',
                                            marginTop: '4px',
                                            backgroundColor: step.completed
                                                ? 'var(--tg-theme-button-color)'
                                                : 'var(--tg-theme-hint-color)',
                                            opacity: step.completed ? 1 : 0.3,
                                        }"
                                    />
                                </div>
                                <!-- Text -->
                                <div style="padding-top: 2px">
                                    <p
                                        :style="{
                                            fontSize: '14px',
                                            color: step.completed || step.current
                                                ? 'var(--tg-theme-text-color)'
                                                : 'var(--tg-theme-hint-color)',
                                            fontWeight: step.current ? '600' : '400',
                                        }"
                                    >
                                        {{ step.label }}
                                    </p>
                                    <p v-if="step.date" style="font-size: 12px; color: var(--tg-theme-hint-color); margin-top: 2px">
                                        {{ formatDate(step.date) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div style="padding: 16px; border-radius: 14px; border: 1px solid var(--color-border); background-color: var(--tg-theme-bg-color)">
                    <h2 class="section-title" style="margin-bottom: 12px">Mahsulotlar</h2>
                    <div style="display: flex; flex-direction: column; gap: 12px">
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex"
                            style="gap: 12px"
                        >
                            <div class="shrink-0 overflow-hidden" style="width: 56px; height: 56px; border-radius: 10px; background-color: var(--tg-theme-secondary-bg-color)">
                                <img
                                    v-if="item.product_image || item.image"
                                    :src="item.product_image || item.image"
                                    :alt="item.product_name || item.name"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center"
                                    style="font-size: 14px"
                                >📦</div>
                            </div>
                            <div class="flex-1">
                                <p style="font-size: 14px; font-weight: 500; line-height: 1.3; color: var(--tg-theme-text-color)">
                                    {{ item.product_name || item.name }}
                                </p>
                                <p v-if="item.variant_name" style="font-size: 13px; color: var(--tg-theme-hint-color); margin-top: 2px">
                                    {{ item.variant_name }}
                                </p>
                                <div class="flex items-center justify-between" style="margin-top: 6px">
                                    <span style="font-size: 13px; color: var(--tg-theme-hint-color)">
                                        {{ item.quantity }} x {{ formatPrice(item.price) }}
                                    </span>
                                    <span style="font-size: 14px; font-weight: 600; color: var(--tg-theme-text-color)">
                                        {{ formatPrice(item.quantity * item.price) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delivery Info -->
                <div style="padding: 16px; border-radius: 14px; border: 1px solid var(--color-border); background-color: var(--tg-theme-bg-color)">
                    <h2 class="section-title" style="margin-bottom: 12px">Yetkazib berish</h2>
                    <div style="display: flex; flex-direction: column; gap: 10px">
                        <div class="flex justify-between" style="font-size: 14px">
                            <span style="color: var(--tg-theme-hint-color)">Usul</span>
                            <span style="color: var(--tg-theme-text-color)">
                                {{ order.delivery_type === 'pickup' ? 'Olib ketish' : 'Yetkazib berish' }}
                            </span>
                        </div>
                        <div v-if="formattedAddress" class="flex justify-between" style="font-size: 14px">
                            <span style="color: var(--tg-theme-hint-color)">Manzil</span>
                            <span class="text-right" style="color: var(--tg-theme-text-color); max-width: 60%">
                                {{ formattedAddress }}
                            </span>
                        </div>
                        <div class="flex justify-between" style="font-size: 14px">
                            <span style="color: var(--tg-theme-hint-color)">To'lov</span>
                            <span style="color: var(--tg-theme-text-color)">{{ paymentLabel }}</span>
                        </div>
                    </div>
                </div>

                <!-- Total -->
                <div class="order-summary">
                    <div class="order-summary-row" style="padding: 6px 0">
                        <span class="label">Mahsulotlar</span>
                        <span class="value">{{ formatPrice(order.subtotal) }}</span>
                    </div>
                    <div v-if="order.discount_amount" class="order-summary-row" style="padding: 6px 0">
                        <span class="label">Chegirma</span>
                        <span style="color: var(--color-success); font-weight: 500">-{{ formatPrice(order.discount_amount) }}</span>
                    </div>
                    <div class="order-summary-row" style="padding: 6px 0">
                        <span class="label">Yetkazib berish</span>
                        <span class="value">
                            {{ order.delivery_fee > 0 ? formatPrice(order.delivery_fee) : 'Bepul' }}
                        </span>
                    </div>
                    <div class="order-summary-total">
                        <span class="label">Jami</span>
                        <span class="value">{{ formatPrice(order.total) }}</span>
                    </div>
                </div>

                <!-- Contact support -->
                <div class="text-center" style="padding-bottom: 8px">
                    <p style="font-size: 13px; color: var(--tg-theme-hint-color)">
                        Savol bo'lsa, do'kon bilan bog'laning
                    </p>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useOrderStore } from '../stores/order'
import BackButton from '../components/BackButton.vue'
import LoadingSpinner from '../components/LoadingSpinner.vue'

const props = defineProps({
    number: { type: String, required: true },
})

const orderStore = useOrderStore()
const order = ref(null)

const allStatuses = [
    { status: 'pending', label: 'Qabul qilindi' },
    { status: 'confirmed', label: 'Tasdiqlangan' },
    { status: 'processing', label: 'Tayyorlanmoqda' },
    { status: 'shipped', label: 'Yetkazilmoqda' },
    { status: 'delivered', label: 'Yetkazildi' },
]

const statusTimeline = computed(() => {
    if (!order.value) return []

    const currentStatus = order.value.status

    if (['cancelled', 'refunded'].includes(currentStatus)) {
        return [
            {
                status: currentStatus,
                label: orderStore.getStatusLabel(currentStatus),
                completed: true,
                current: true,
                date: order.value.updated_at,
            },
        ]
    }

    const currentIdx = allStatuses.findIndex((s) => s.status === currentStatus)
    // status_history is an array of { from_status, to_status, created_at }
    const historyArr = order.value.status_history || []
    const historyMap = {}
    historyArr.forEach((h) => {
        if (h.to_status) historyMap[h.to_status] = h.created_at
    })

    return allStatuses.map((step, idx) => ({
        ...step,
        completed: idx < currentIdx,
        current: idx === currentIdx,
        date: historyMap[step.status] || (idx === currentIdx ? order.value.updated_at : null),
    }))
})

const formattedAddress = computed(() => {
    if (!order.value) return ''
    const addr = order.value.delivery_address
    if (!addr) return ''
    // delivery_address can be string or object
    if (typeof addr === 'string') return addr
    return [addr.city, addr.district, addr.street].filter(Boolean).join(', ')
})

const paymentLabel = computed(() => {
    if (!order.value) return ''
    const labels = {
        cash: 'Naqd pul',
        card: 'Plastik karta',
        click: 'Click',
        payme: 'Payme',
    }
    return labels[order.value.payment_method] || order.value.payment_method
})

function formatPrice(price) {
    if (!price) return "0 so'm"
    return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + " so'm"
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

onMounted(async () => {
    order.value = await orderStore.fetchOrder(props.number)
})
</script>
