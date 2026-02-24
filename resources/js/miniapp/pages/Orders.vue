<template>
    <div class="pb-nav">
        <!-- Header -->
        <div class="sticky top-0 z-20 header-blur">
            <div style="padding: 12px 16px">
                <h1 style="font-size: 22px; font-weight: 700; color: var(--tg-theme-text-color)">
                    Buyurtmalar
                </h1>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="orderStore.loading && orderStore.orders.length === 0" style="padding: 8px 16px 0">
            <SkeletonLoader type="order" :count="3" />
        </div>

        <!-- Empty -->
        <div v-else-if="orderStore.orders.length === 0" class="empty-state">
            <div class="empty-state-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                </svg>
            </div>
            <p class="empty-state-title">Buyurtmalar yo'q</p>
            <p class="empty-state-desc">Birinchi buyurtmangizni bering</p>
            <div class="empty-state-action">
                <button @click="goHome" class="btn-secondary">Xarid qilish</button>
            </div>
        </div>

        <!-- Orders list -->
        <div v-else style="padding: 0 16px">
            <!-- Active orders -->
            <div v-if="orderStore.activeOrders.length" style="margin-bottom: 24px">
                <h2 style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--tg-theme-hint-color); margin-bottom: 12px">
                    Faol buyurtmalar
                </h2>
                <div style="display: flex; flex-direction: column; gap: 12px">
                    <button
                        v-for="order in orderStore.activeOrders"
                        :key="order.id"
                        @click="goToOrder(order.order_number)"
                        class="w-full text-left tap-active"
                        style="padding: 16px; border-radius: 14px; border: 1px solid var(--color-border); background-color: var(--tg-theme-bg-color)"
                    >
                        <div class="flex items-center justify-between">
                            <span style="font-size: 15px; font-weight: 700; color: var(--tg-theme-text-color)">
                                #{{ order.order_number }}
                            </span>
                            <span
                                class="rounded-full"
                                style="padding: 3px 10px; font-size: 11px; font-weight: 600"
                                :class="orderStore.getStatusColor(order.status)"
                            >
                                {{ orderStore.getStatusLabel(order.status) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between" style="margin-top: 10px">
                            <span style="font-size: 13px; color: var(--tg-theme-hint-color)">
                                {{ formatDate(order.created_at) }}
                            </span>
                            <span style="font-size: 15px; font-weight: 700; color: var(--tg-theme-text-color)">
                                {{ formatPrice(order.total) }}
                            </span>
                        </div>
                        <!-- Product thumbnails -->
                        <div v-if="order.items?.length" class="flex items-center" style="margin-top: 12px; gap: 6px">
                            <div
                                v-for="(item, idx) in order.items.slice(0, 3)"
                                :key="idx"
                                class="overflow-hidden shrink-0"
                                style="width: 36px; height: 36px; border-radius: 8px; background-color: var(--tg-theme-secondary-bg-color)"
                            >
                                <img v-if="item.product_image || item.image" :src="item.product_image || item.image" :alt="item.product_name || item.name" class="h-full w-full object-cover" />
                                <span v-else class="flex h-full w-full items-center justify-center" style="font-size: 12px">📦</span>
                            </div>
                            <span
                                v-if="order.items.length > 3"
                                style="font-size: 12px; font-weight: 500; color: var(--tg-theme-hint-color)"
                            >
                                +{{ order.items.length - 3 }}
                            </span>
                        </div>
                        <div v-else-if="order.items_count" style="margin-top: 6px">
                            <span style="font-size: 13px; color: var(--tg-theme-hint-color)">
                                {{ order.items_count }} ta mahsulot
                            </span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Completed orders -->
            <div v-if="orderStore.completedOrders.length">
                <h2 style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--tg-theme-hint-color); margin-bottom: 12px">
                    Yakunlangan
                </h2>
                <div style="display: flex; flex-direction: column; gap: 12px">
                    <button
                        v-for="order in orderStore.completedOrders"
                        :key="order.id"
                        @click="goToOrder(order.order_number)"
                        class="w-full text-left tap-active"
                        style="padding: 16px; border-radius: 14px; border: 1px solid var(--color-border); background-color: var(--tg-theme-bg-color)"
                    >
                        <div class="flex items-center justify-between">
                            <span style="font-size: 15px; font-weight: 700; color: var(--tg-theme-text-color)">
                                #{{ order.order_number }}
                            </span>
                            <span
                                class="rounded-full"
                                style="padding: 3px 10px; font-size: 11px; font-weight: 600"
                                :class="orderStore.getStatusColor(order.status)"
                            >
                                {{ orderStore.getStatusLabel(order.status) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between" style="margin-top: 10px">
                            <span style="font-size: 13px; color: var(--tg-theme-hint-color)">
                                {{ formatDate(order.created_at) }}
                            </span>
                            <span style="font-size: 15px; font-weight: 700; color: var(--tg-theme-text-color)">
                                {{ formatPrice(order.total) }}
                            </span>
                        </div>
                        <div v-if="order.items?.length" class="flex items-center" style="margin-top: 12px; gap: 6px">
                            <div
                                v-for="(item, idx) in order.items.slice(0, 3)"
                                :key="idx"
                                class="overflow-hidden shrink-0"
                                style="width: 36px; height: 36px; border-radius: 8px; background-color: var(--tg-theme-secondary-bg-color)"
                            >
                                <img v-if="item.product_image || item.image" :src="item.product_image || item.image" :alt="item.product_name || item.name" class="h-full w-full object-cover" />
                                <span v-else class="flex h-full w-full items-center justify-center" style="font-size: 12px">📦</span>
                            </div>
                            <span
                                v-if="order.items.length > 3"
                                style="font-size: 12px; font-weight: 500; color: var(--tg-theme-hint-color)"
                            >
                                +{{ order.items.length - 3 }}
                            </span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Load more -->
            <div v-if="orderStore.hasMore" ref="loadMoreRef" class="flex justify-center" style="padding: 24px 0">
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
import SkeletonLoader from '../components/SkeletonLoader.vue'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import { formatPrice } from '../utils/formatters'

const router = useRouter()
const orderStore = useOrderStore()
const { hapticImpact, hideBackButton } = useTelegram()

const loadMoreRef = ref(null)
let observer = null

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
    hideBackButton()
    orderStore.fetchOrders(true)
    setupInfiniteScroll()
})

onUnmounted(() => {
    observer?.disconnect()
})
</script>
