<template>
    <div class="pb-20">
        <BackButton />

        <!-- Header -->
        <div class="sticky top-0 z-10 px-4 py-3" style="background-color: var(--tg-theme-bg-color)">
            <h1 class="text-lg font-semibold" style="color: var(--tg-theme-text-color)">
                Buyurtmalarni boshqarish
            </h1>
        </div>

        <!-- Status Filters -->
        <div class="px-4 pb-3">
            <div class="flex gap-2 overflow-x-auto no-scrollbar">
                <button
                    v-for="filter in statusFilters"
                    :key="filter.value"
                    @click="selectFilter(filter.value)"
                    class="shrink-0 rounded-full px-3 py-1.5 text-xs font-medium transition-colors"
                    :style="{
                        backgroundColor: activeFilter === filter.value
                            ? 'var(--tg-theme-button-color)'
                            : 'var(--tg-theme-secondary-bg-color)',
                        color: activeFilter === filter.value
                            ? 'var(--tg-theme-button-text-color)'
                            : 'var(--tg-theme-hint-color)',
                    }"
                >
                    {{ filter.label }}
                    <span v-if="filter.count > 0" class="ml-1">{{ filter.count }}</span>
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading && orders.length === 0" class="flex items-center justify-center py-20">
            <LoadingSpinner />
        </div>

        <!-- Empty -->
        <div v-else-if="orders.length === 0" class="px-4 py-16 text-center">
            <svg class="mx-auto h-16 w-16" style="color: var(--tg-theme-hint-color); opacity: 0.3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="mt-4 text-sm font-medium" style="color: var(--tg-theme-text-color)">
                Buyurtmalar topilmadi
            </p>
            <p class="mt-1 text-xs" style="color: var(--tg-theme-hint-color)">
                {{ activeFilter ? 'Bu statusda buyurtmalar yo\'q' : 'Hali buyurtmalar kelmagan' }}
            </p>
        </div>

        <!-- Orders list -->
        <div v-else class="px-4 space-y-2">
            <button
                v-for="order in orders"
                :key="order.id"
                @click="goToOrderDetail(order.id)"
                class="w-full rounded-xl p-3.5 text-left"
                style="background-color: var(--tg-theme-secondary-bg-color)"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                            #{{ order.order_number }}
                        </p>
                        <p v-if="order.customer" class="mt-0.5 text-xs" style="color: var(--tg-theme-hint-color)">
                            {{ order.customer.name || 'Noma\'lum' }}
                            <span v-if="order.customer.phone"> - {{ order.customer.phone }}</span>
                        </p>
                    </div>
                    <span
                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                        :class="getStatusColor(order.status)"
                    >
                        {{ getStatusLabel(order.status) }}
                    </span>
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <span class="text-xs" style="color: var(--tg-theme-hint-color)">
                        {{ formatDate(order.created_at) }}
                    </span>
                    <span class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                        {{ formatPrice(order.total) }}
                    </span>
                </div>
                <!-- Payment status -->
                <div class="mt-1.5 flex items-center gap-2">
                    <span
                        class="inline-flex items-center gap-1 text-xs"
                        :style="{ color: order.payment_status === 'paid' ? '#16a34a' : 'var(--tg-theme-hint-color)' }"
                    >
                        <svg v-if="order.payment_status === 'paid'" class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                        </svg>
                        {{ order.payment_status === 'paid' ? 'To\'langan' : 'To\'lanmagan' }}
                    </span>
                </div>
            </button>

            <!-- Load more -->
            <div v-if="hasMore" class="flex justify-center py-4">
                <button
                    v-if="!loading"
                    @click="loadMore"
                    class="rounded-lg px-4 py-2 text-sm font-medium"
                    style="background-color: var(--tg-theme-secondary-bg-color); color: var(--tg-theme-link-color)"
                >
                    Ko'proq yuklash
                </button>
                <LoadingSpinner v-else size="sm" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../composables/useApi'
import { useTelegram } from '../composables/useTelegram'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import BackButton from '../components/BackButton.vue'

const router = useRouter()
const { get } = useApi()
const { hapticImpact } = useTelegram()

const orders = ref([])
const loading = ref(false)
const hasMore = ref(false)
const totalCount = ref(0)
const page = ref(1)
const activeFilter = ref('')

const statusFilters = ref([
    { value: '', label: 'Barchasi', count: 0 },
    { value: 'pending', label: 'Kutilmoqda', count: 0 },
    { value: 'confirmed', label: 'Tasdiqlangan', count: 0 },
    { value: 'processing', label: 'Tayyorlanmoqda', count: 0 },
    { value: 'shipped', label: 'Yetkazilmoqda', count: 0 },
    { value: 'delivered', label: 'Yetkazildi', count: 0 },
    { value: 'cancelled', label: 'Bekor qilingan', count: 0 },
])

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

function goToOrderDetail(orderId) {
    hapticImpact('light')
    router.push({ name: 'admin-order-detail', params: { id: orderId } })
}

async function selectFilter(status) {
    hapticImpact('light')
    activeFilter.value = status
    page.value = 1
    orders.value = []
    await fetchOrders()
}

async function fetchOrders() {
    loading.value = true
    try {
        const params = { page: page.value }
        if (activeFilter.value) {
            params.status = activeFilter.value
        }
        const data = await get('/admin/orders', params)
        if (page.value === 1) {
            orders.value = data.orders || []
        } else {
            orders.value.push(...(data.orders || []))
        }
        hasMore.value = data.has_more || false
        totalCount.value = data.total || 0
    } catch (err) {
        console.error('[MiniApp Admin] Orders fetch error:', err)
    } finally {
        loading.value = false
    }
}

async function loadMore() {
    page.value++
    await fetchOrders()
}

onMounted(() => {
    fetchOrders()
})
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
