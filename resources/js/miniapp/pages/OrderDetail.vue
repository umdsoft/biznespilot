<template>
    <div class="pb-20">
        <BackButton />

        <!-- Header -->
        <div class="sticky top-0 z-10 px-4 py-3" style="background-color: var(--tg-theme-bg-color)">
            <h1 class="text-lg font-semibold" style="color: var(--tg-theme-text-color)">
                Buyurtma #{{ number }}
            </h1>
        </div>

        <!-- Loading -->
        <div v-if="orderStore.loading && !order" class="flex items-center justify-center py-20">
            <LoadingSpinner />
        </div>

        <!-- Not found -->
        <div v-else-if="!order" class="px-4 py-10 text-center">
            <p class="text-sm" style="color: var(--tg-theme-hint-color)">Buyurtma topilmadi</p>
        </div>

        <template v-else>
            <div class="px-4 space-y-4">
                <!-- Status -->
                <div class="rounded-xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs" style="color: var(--tg-theme-hint-color)">Holat</p>
                            <p class="mt-1 text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                                {{ orderStore.getStatusLabel(order.status) }}
                            </p>
                        </div>
                        <span
                            class="rounded-full px-3 py-1 text-xs font-medium"
                            :class="orderStore.getStatusColor(order.status)"
                        >
                            {{ orderStore.getStatusLabel(order.status) }}
                        </span>
                    </div>

                    <!-- Status Timeline -->
                    <div v-if="statusTimeline.length" class="mt-4">
                        <div class="relative">
                            <div
                                v-for="(step, idx) in statusTimeline"
                                :key="step.status"
                                class="relative flex gap-3 pb-4 last:pb-0"
                            >
                                <!-- Line -->
                                <div class="flex flex-col items-center">
                                    <div
                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
                                        :style="{
                                            backgroundColor: step.completed
                                                ? 'var(--tg-theme-button-color)'
                                                : step.current
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
                                            class="h-3.5 w-3.5"
                                            style="color: var(--tg-theme-button-text-color)"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <div
                                            v-else-if="step.current"
                                            class="h-2 w-2 rounded-full"
                                            style="background-color: var(--tg-theme-button-text-color)"
                                        />
                                    </div>
                                    <div
                                        v-if="idx < statusTimeline.length - 1"
                                        class="mt-1 w-0.5 flex-1"
                                        :style="{
                                            backgroundColor: step.completed
                                                ? 'var(--tg-theme-button-color)'
                                                : 'var(--tg-theme-hint-color)',
                                            opacity: step.completed ? 1 : 0.3,
                                        }"
                                    />
                                </div>
                                <!-- Text -->
                                <div class="pt-0.5">
                                    <p
                                        class="text-sm"
                                        :style="{
                                            color: step.completed || step.current
                                                ? 'var(--tg-theme-text-color)'
                                                : 'var(--tg-theme-hint-color)',
                                            fontWeight: step.current ? '600' : '400',
                                        }"
                                    >
                                        {{ step.label }}
                                    </p>
                                    <p v-if="step.date" class="mt-0.5 text-xs" style="color: var(--tg-theme-hint-color)">
                                        {{ formatDate(step.date) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="rounded-xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                    <h2 class="mb-3 text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                        Mahsulotlar
                    </h2>
                    <div class="space-y-3">
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex gap-3"
                        >
                            <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg">
                                <img
                                    v-if="item.image"
                                    :src="item.image"
                                    :alt="item.name"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center"
                                    style="background-color: var(--tg-theme-bg-color)"
                                >
                                    <span class="text-sm">📦</span>
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

                <!-- Delivery Info -->
                <div class="rounded-xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                    <h2 class="mb-3 text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                        Yetkazib berish
                    </h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span style="color: var(--tg-theme-hint-color)">Usul</span>
                            <span style="color: var(--tg-theme-text-color)">
                                {{ order.delivery_type === 'pickup' ? 'Olib ketish' : 'Yetkazib berish' }}
                            </span>
                        </div>
                        <div v-if="order.address" class="flex justify-between">
                            <span style="color: var(--tg-theme-hint-color)">Manzil</span>
                            <span class="max-w-[60%] text-right" style="color: var(--tg-theme-text-color)">
                                {{ order.address }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span style="color: var(--tg-theme-hint-color)">To'lov</span>
                            <span style="color: var(--tg-theme-text-color)">{{ paymentLabel }}</span>
                        </div>
                    </div>
                </div>

                <!-- Total -->
                <div class="rounded-xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between">
                            <span style="color: var(--tg-theme-hint-color)">Mahsulotlar</span>
                            <span style="color: var(--tg-theme-text-color)">{{ formatPrice(order.subtotal) }}</span>
                        </div>
                        <div v-if="order.discount" class="flex justify-between">
                            <span style="color: var(--tg-theme-hint-color)">Chegirma</span>
                            <span class="text-green-600">-{{ formatPrice(order.discount) }}</span>
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
                    </div>
                </div>

                <!-- Contact support -->
                <div class="text-center pb-4">
                    <p class="text-xs" style="color: var(--tg-theme-hint-color)">
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
    { status: 'preparing', label: 'Tayyorlanmoqda' },
    { status: 'shipping', label: 'Yetkazilmoqda' },
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
    const history = order.value.status_history || {}

    return allStatuses.map((step, idx) => ({
        ...step,
        completed: idx < currentIdx,
        current: idx === currentIdx,
        date: history[step.status] || (idx === currentIdx ? order.value.updated_at : null),
    }))
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
