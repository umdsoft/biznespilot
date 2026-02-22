import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useApi } from '../composables/useApi'

export const useOrderStore = defineStore('order', () => {
    const { get, post } = useApi()

    const orders = ref([])
    const currentOrder = ref(null)
    const loading = ref(false)
    const creating = ref(false)
    const error = ref(null)
    const hasMore = ref(false)
    const page = ref(1)

    const activeOrders = computed(() => {
        return orders.value.filter((o) =>
            !['delivered', 'cancelled', 'refunded'].includes(o.status)
        )
    })

    const completedOrders = computed(() => {
        return orders.value.filter((o) =>
            ['delivered', 'cancelled', 'refunded'].includes(o.status)
        )
    })

    async function fetchOrders(reset = false) {
        if (reset) {
            page.value = 1
            orders.value = []
        }

        loading.value = true
        error.value = null
        try {
            const data = await get('/orders', { page: page.value })
            const newOrders = data.orders || data.data || []

            if (reset) {
                orders.value = newOrders
            } else {
                orders.value.push(...newOrders)
            }

            hasMore.value = data.has_more || (data.meta?.last_page > page.value)
            page.value++
        } catch (err) {
            error.value = "Buyurtmalarni yuklashda xatolik"
            console.error('[MiniApp] Orders fetch error:', err)
        } finally {
            loading.value = false
        }
    }

    async function fetchOrder(orderNumber) {
        loading.value = true
        error.value = null
        try {
            const data = await get(`/orders/${orderNumber}`)
            currentOrder.value = data.order || data
            return currentOrder.value
        } catch (err) {
            error.value = "Buyurtma topilmadi"
            console.error('[MiniApp] Order fetch error:', err)
            return null
        } finally {
            loading.value = false
        }
    }

    async function createOrder(orderData) {
        creating.value = true
        error.value = null
        try {
            const data = await post('/orders', orderData)
            currentOrder.value = data.order || data
            return currentOrder.value
        } catch (err) {
            error.value = err.response?.data?.message || "Buyurtma yaratishda xatolik"
            if (err.validationErrors) {
                error.value = Object.values(err.validationErrors).flat().join(', ')
            }
            console.error('[MiniApp] Create order error:', err)
            return null
        } finally {
            creating.value = false
        }
    }

    function getStatusLabel(status) {
        const labels = {
            pending: 'Kutilmoqda',
            confirmed: 'Tasdiqlangan',
            preparing: 'Tayyorlanmoqda',
            shipping: 'Yetkazilmoqda',
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
            preparing: 'text-indigo-600 bg-indigo-50',
            shipping: 'text-purple-600 bg-purple-50',
            delivered: 'text-green-600 bg-green-50',
            cancelled: 'text-red-600 bg-red-50',
            refunded: 'text-gray-600 bg-gray-50',
        }
        return colors[status] || 'text-gray-600 bg-gray-50'
    }

    return {
        orders,
        currentOrder,
        loading,
        creating,
        error,
        hasMore,
        activeOrders,
        completedOrders,
        fetchOrders,
        fetchOrder,
        createOrder,
        getStatusLabel,
        getStatusColor,
    }
})
