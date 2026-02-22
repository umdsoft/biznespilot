<template>
    <div class="flex min-h-screen flex-col items-center justify-center px-4">
        <BackButton />

        <!-- Processing -->
        <div v-if="status === 'processing'" class="text-center">
            <div class="mx-auto mb-4">
                <LoadingSpinner size="lg" />
            </div>
            <h2 class="text-lg font-semibold" style="color: var(--tg-theme-text-color)">
                To'lov amalga oshirilmoqda
            </h2>
            <p class="mt-2 text-sm" style="color: var(--tg-theme-hint-color)">
                Iltimos, kutib turing...
            </p>
        </div>

        <!-- Success -->
        <div v-else-if="status === 'success'" class="text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold" style="color: var(--tg-theme-text-color)">
                To'lov muvaffaqiyatli!
            </h2>
            <p class="mt-2 text-sm" style="color: var(--tg-theme-hint-color)">
                Buyurtma #{{ orderNumber }} qabul qilindi
            </p>
            <button
                @click="viewOrder"
                class="mt-6 rounded-xl px-6 py-3 text-sm font-semibold"
                style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
            >
                Buyurtmani ko'rish
            </button>
        </div>

        <!-- Failed -->
        <div v-else-if="status === 'failed'" class="text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
                <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold" style="color: var(--tg-theme-text-color)">
                To'lov amalga oshmadi
            </h2>
            <p class="mt-2 text-sm" style="color: var(--tg-theme-hint-color)">
                {{ errorMessage || "Iltimos, qaytadan urinib ko'ring" }}
            </p>
            <div class="mt-6 flex gap-3">
                <button
                    @click="retryPayment"
                    class="flex-1 rounded-xl py-3 text-sm font-semibold"
                    style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
                >
                    Qayta to'lash
                </button>
                <button
                    @click="viewOrder"
                    class="flex-1 rounded-xl py-3 text-sm font-semibold"
                    style="background-color: var(--tg-theme-secondary-bg-color); color: var(--tg-theme-text-color)"
                >
                    Buyurtmaga o'tish
                </button>
            </div>
        </div>

        <!-- Waiting for redirect -->
        <div v-else class="text-center">
            <div class="mx-auto mb-4">
                <LoadingSpinner size="lg" />
            </div>
            <h2 class="text-lg font-semibold" style="color: var(--tg-theme-text-color)">
                To'lov sahifasiga o'tkazilmoqda
            </h2>
            <p class="mt-2 text-sm" style="color: var(--tg-theme-hint-color)">
                Iltimos, kutib turing...
            </p>
            <button
                @click="openPaymentLink"
                class="mt-6 rounded-xl px-6 py-3 text-sm font-semibold"
                style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
            >
                To'lov sahifasini ochish
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '../composables/useApi'
import { useTelegram } from '../composables/useTelegram'
import BackButton from '../components/BackButton.vue'
import LoadingSpinner from '../components/LoadingSpinner.vue'

const route = useRoute()
const router = useRouter()
const { get } = useApi()
const { hapticNotification, openLink } = useTelegram()

const status = ref('redirecting')
const orderNumber = ref('')
const paymentUrl = ref('')
const errorMessage = ref('')

let pollInterval = null

function openPaymentLink() {
    if (paymentUrl.value) {
        openLink(paymentUrl.value, { try_instant_view: false })
    }
}

function viewOrder() {
    router.push({ name: 'order-detail', params: { number: orderNumber.value } })
}

function retryPayment() {
    if (paymentUrl.value) {
        status.value = 'redirecting'
        openPaymentLink()
    }
}

async function checkPaymentStatus() {
    if (!orderNumber.value) return

    try {
        const data = await get(`/orders/${orderNumber.value}/payment-status`)
        const paymentStatus = data.status || data.payment_status

        if (paymentStatus === 'paid' || paymentStatus === 'success') {
            status.value = 'success'
            hapticNotification('success')
            stopPolling()
        } else if (paymentStatus === 'failed' || paymentStatus === 'cancelled') {
            status.value = 'failed'
            errorMessage.value = data.message || ''
            hapticNotification('error')
            stopPolling()
        }
        // else keep polling (pending/processing)
    } catch (err) {
        console.error('[MiniApp] Payment status check error:', err)
    }
}

function startPolling() {
    pollInterval = setInterval(checkPaymentStatus, 3000)
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval)
        pollInterval = null
    }
}

onMounted(() => {
    orderNumber.value = route.query.order_number || ''
    paymentUrl.value = route.query.payment_url || ''

    if (paymentUrl.value) {
        // Small delay then open payment link
        setTimeout(() => {
            openPaymentLink()
            status.value = 'processing'
            startPolling()
        }, 500)
    } else if (orderNumber.value) {
        status.value = 'processing'
        startPolling()
    }
})

onUnmounted(() => {
    stopPolling()
})
</script>
