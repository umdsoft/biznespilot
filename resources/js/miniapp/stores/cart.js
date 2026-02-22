import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'
import { useApi } from '../composables/useApi'
import { useTelegram } from '../composables/useTelegram'

export const useCartStore = defineStore('cart', () => {
    const { post } = useApi()
    const { hapticImpact, hapticNotification } = useTelegram()

    const items = ref([])
    const promoCode = ref('')
    const promoDiscount = ref(0)
    const promoError = ref('')
    const promoApplied = ref(false)
    const loading = ref(false)

    // Restore cart from localStorage
    const savedCart = localStorage.getItem('miniapp_cart')
    if (savedCart) {
        try {
            items.value = JSON.parse(savedCart)
        } catch {
            items.value = []
        }
    }

    // Persist cart
    watch(items, (val) => {
        localStorage.setItem('miniapp_cart', JSON.stringify(val))
    }, { deep: true })

    const itemCount = computed(() => {
        return items.value.reduce((sum, item) => sum + item.quantity, 0)
    })

    const subtotal = computed(() => {
        return items.value.reduce((sum, item) => {
            const price = item.sale_price || item.price
            return sum + (price * item.quantity)
        }, 0)
    })

    const discountAmount = computed(() => {
        if (!promoApplied.value) return 0
        return promoDiscount.value
    })

    const total = computed(() => {
        return Math.max(0, subtotal.value - discountAmount.value)
    })

    const isEmpty = computed(() => items.value.length === 0)

    function findItem(productId, variantId = null) {
        return items.value.find(
            (item) => item.product_id === productId && item.variant_id === variantId
        )
    }

    function addItem(product, quantity = 1, variant = null) {
        const existing = findItem(product.id, variant?.id || null)

        if (existing) {
            existing.quantity += quantity
            hapticImpact('light')
        } else {
            items.value.push({
                product_id: product.id,
                variant_id: variant?.id || null,
                name: product.name,
                variant_name: variant?.name || null,
                image: product.image || product.images?.[0] || '',
                price: product.price,
                sale_price: product.sale_price || null,
                quantity,
                max_quantity: product.stock || 99,
                slug: product.slug,
            })
            hapticImpact('medium')
        }

        // Clear promo if cart changed
        if (promoApplied.value) {
            clearPromo()
        }
    }

    function removeItem(productId, variantId = null) {
        const index = items.value.findIndex(
            (item) => item.product_id === productId && item.variant_id === variantId
        )
        if (index > -1) {
            items.value.splice(index, 1)
            hapticImpact('light')
        }

        if (promoApplied.value) {
            clearPromo()
        }
    }

    function updateQuantity(productId, variantId, quantity) {
        const item = findItem(productId, variantId)
        if (!item) return

        if (quantity <= 0) {
            removeItem(productId, variantId)
            return
        }

        item.quantity = Math.min(quantity, item.max_quantity)
        hapticImpact('light')

        if (promoApplied.value) {
            clearPromo()
        }
    }

    function incrementQuantity(productId, variantId = null) {
        const item = findItem(productId, variantId)
        if (item && item.quantity < item.max_quantity) {
            item.quantity++
            hapticImpact('light')
        }
    }

    function decrementQuantity(productId, variantId = null) {
        const item = findItem(productId, variantId)
        if (!item) return

        if (item.quantity <= 1) {
            removeItem(productId, variantId)
        } else {
            item.quantity--
            hapticImpact('light')
        }
    }

    async function applyPromo(code) {
        if (!code.trim()) return

        loading.value = true
        promoError.value = ''
        try {
            const data = await post('/cart/promo', {
                code: code.trim(),
                items: items.value.map((item) => ({
                    product_id: item.product_id,
                    variant_id: item.variant_id,
                    quantity: item.quantity,
                })),
            })

            promoCode.value = code.trim()
            promoDiscount.value = data.discount || 0
            promoApplied.value = true
            hapticNotification('success')
        } catch (err) {
            promoError.value = err.response?.data?.message || 'Promokod yaroqsiz'
            promoApplied.value = false
            promoDiscount.value = 0
            hapticNotification('error')
        } finally {
            loading.value = false
        }
    }

    function clearPromo() {
        promoCode.value = ''
        promoDiscount.value = 0
        promoApplied.value = false
        promoError.value = ''
    }

    function clearCart() {
        items.value = []
        clearPromo()
        localStorage.removeItem('miniapp_cart')
    }

    function getCartPayload() {
        return {
            items: items.value.map((item) => ({
                product_id: item.product_id,
                variant_id: item.variant_id,
                quantity: item.quantity,
            })),
            promo_code: promoApplied.value ? promoCode.value : null,
        }
    }

    return {
        items,
        promoCode,
        promoDiscount,
        promoError,
        promoApplied,
        loading,
        itemCount,
        subtotal,
        discountAmount,
        total,
        isEmpty,
        addItem,
        removeItem,
        updateQuantity,
        incrementQuantity,
        decrementQuantity,
        applyPromo,
        clearPromo,
        clearCart,
        getCartPayload,
    }
})
