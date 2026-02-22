<template>
    <div class="pb-20">
        <BackButton />

        <!-- Header -->
        <div class="sticky top-0 z-10 px-4 py-3" style="background-color: var(--tg-theme-bg-color)">
            <h1 class="text-lg font-semibold" style="color: var(--tg-theme-text-color)">
                Buyurtmalar
            </h1>
        </div>

        <!-- Loading -->
        <div v-if="orderStore.loading && orderStore.orders.length === 0" class="flex items-center justify-center py-20">
            <LoadingSpinner />
        </div>

        <!-- Empty -->
        <div v-else-if="orderStore.orders.length === 0" class="px-4 py-16 text-center">
            <svg class="mx-auto h-16 w-16" style="color: var(--tg-theme-hint-color); opacity: 0.3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="mt-4 text-sm font-medium" style="color: var(--tg-theme-text-color)">
                Buyurtmalar yo'q
            </p>
            <p class="mt-1 text-xs" style="color: var(--tg-theme-hint-color)">
                Birinchi buyurtmangizni bering
            </p>
            <button
                @click="goHome"
                class="mt-4 rounded-xl px-6 py-2.5 text-sm font-medium"
                style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
            >
                Xarid qilish
            </button>
        </div>

        <!-- Orders list -->
        <div v-else class="px-4">
            <!-- Active orders -->
            <div v-if="orderStore.activeOrders.length" class="mb-4">
                <h2 class="mb-2 text-xs font-semibold uppercase tracking-wider" style="color: var(--tg-theme-hint-color)">
                    Faol buyurtmalar
                </h2>
                <div class="space-y-2">
                    <button
                        v-for="order in orderStore.activeOrders"
                        :key="order.id"
                        @click="goToOrder(order.order_number)"
                        class="w-full rounded-xl p-3 text-left"
                        style="background-color: var(--tg-theme-secondary-bg-color)"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                                #{{ order.order_number }}
                            </span>
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="orderStore.getStatusColor(order.status)"
                            >
                                {{ orderStore.getStatusLabel(order.status) }}
                            </span>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-xs" style="color: var(--tg-theme-hint-color)">
                                {{ formatDate(order.created_at) }}
                            </span>
                            <span class="text-sm font-medium" style="color: var(--tg-theme-text-color)">
                                {{ formatPrice(order.total) }}
                            </span>
                        </div>
                        <div v-if="order.items_count" class="mt-1">
                            <span class="text-xs" style="color: var(--tg-theme-hint-color)">
                                {{ order.items_count }} ta mahsulot
                            </span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Completed orders -->
            <div v-if="orderStore.completedOrders.length">
                <h2 class="mb-2 text-xs font-semibold uppercase tracking-wider" style="color: var(--tg-theme-hint-color)">
                    Yakunlangan buyurtmalar
                </h2>
                <div class="space-y-2">
                    <button
                        v-for="order in orderStore.completedOrders"
                        :key="order.id"
                        @click="goToOrder(order.order_number)"
                        class="w-full rounded-xl p-3 text-left"
                        style="background-color: var(--tg-theme-secondary-bg-color)"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                                #{{ order.order_number }}
                            </span>
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="orderStore.getStatusColor(order.status)"
                            >
                                {{ orderStore.getStatusLabel(order.status) }}
                            </span>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-xs" style="color: var(--tg-theme-hint-color)">
                                {{ formatDate(order.created_at) }}
                            </span>
                            <span class="text-sm font-medium" style="color: var(--tg-theme-text-color)">
                                {{ formatPrice(order.total) }}
                            </span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Load more -->
            <div v-if="orderStore.hasMore" ref="loadMoreRef" class="flex justify-center py-6">
                <LoadingSpinner v-if="orderStore.loading" size="sm" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useOrderStore } from '../stores/order'
import { useTelegram } from '../composables/useTelegram'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import BackButton from '../components/BackButton.vue'

const router = useRouter()
const orderStore = useOrderStore()
const { hapticImpact } = useTelegram()

const loadMoreRef = ref(null)
let observer = null

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
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

function goHome() {
    hapticImpact('light')
    router.push({ name: 'home' })
}

function goToOrder(number) {
    hapticImpact('light')
    router.push({ name: 'order-detail', params: { number } })
}

function setupInfiniteScroll() {
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0].isIntersecting && orderStore.hasMore && !orderStore.loading) {
                orderStore.fetchOrders(false)
            }
        },
        { rootMargin: '200px' }
    )

    if (loadMoreRef.value) {
        observer.observe(loadMoreRef.value)
    }
}

onMounted(() => {
    orderStore.fetchOrders(true)
    setupInfiniteScroll()
})

onUnmounted(() => {
    observer?.disconnect()
})
</script>
