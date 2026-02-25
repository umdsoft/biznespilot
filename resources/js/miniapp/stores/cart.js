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
            const basePrice = item.sale_price || item.price
            const modifierPrice = (item.modifiers || []).reduce((s, m) => s + (m.price || 0), 0)
            return sum + ((basePrice + modifierPrice) * item.quantity)
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

    /**
     * Generate a hash string from modifiers for matching cart items.
     * Same product with different modifiers = different cart items.
     */
    function modifierHash(modifiers) {
        if (!modifiers || modifiers.length === 0) return ''
        return modifiers
            .map(m => `${m.modifier_id}:${m.option_id}`)
            .sort()
            .join('|')
    }

    function findItem(productId, variantId = null, modifiers = null) {
        const hash = modifierHash(modifiers)
        return items.value.find(
            (item) => item.product_id === productId
                && item.variant_id === variantId
                && modifierHash(item.modifiers) === hash
        )
    }

    function findItemIndex(productId, variantId = null, modifiers = null) {
        const hash = modifierHash(modifiers)
        return items.value.findIndex(
            (item) => item.product_id === productId
                && item.variant_id === variantId
                && modifierHash(item.modifiers) === hash
        )
    }

    /**
     * Get unit price for a cart item (base + modifiers).
     */
    function getItemUnitPrice(item) {
        const basePrice = item.sale_price || item.price
        const modifierPrice = (item.modifiers || []).reduce((s, m) => s + (m.price || 0), 0)
        return basePrice + modifierPrice
    }

    /**
     * Add item to cart.
     * @param {Object} product - { id, name, price, sale_price?, image, slug, stock? }
     * @param {number} quantity
     * @param {Object|null} variant - { id, name }
     * @param {Array|null} modifiers - [{ modifier_id, modifier_name, option_id, option_name, price }]
     */
    function addItem(product, quantity = 1, variant = null, modifiers = null) {
        const mods = modifiers && modifiers.length > 0 ? modifiers : null
        const existing = findItem(product.id, variant?.id || null, mods)

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
                modifiers: mods || [],
            })
            hapticImpact('medium')
        }

        // Clear promo if cart changed
        if (promoApplied.value) {
            clearPromo()
        }
    }

    function removeItem(productId, variantId = null, modifiers = null) {
        const index = findItemIndex(productId, variantId, modifiers)
        if (index > -1) {
            items.value.splice(index, 1)
            hapticImpact('light')
        }

        if (promoApplied.value) {
            clearPromo()
        }
    }

    function updateQuantity(productId, variantId, quantity, modifiers = null) {
        const item = findItem(productId, variantId, modifiers)
        if (!item) return

        if (quantity <= 0) {
            removeItem(productId, variantId, modifiers)
            return
        }

        item.quantity = Math.min(quantity, item.max_quantity)
        hapticImpact('light')

        if (promoApplied.value) {
            clearPromo()
        }
    }

    function incrementQuantity(productId, variantId = null, modifiers = null) {
        const item = findItem(productId, variantId, modifiers)
        if (item && item.quantity < item.max_quantity) {
            item.quantity++
            hapticImpact('light')
        }
    }

    function decrementQuantity(productId, variantId = null, modifiers = null) {
        const item = findItem(productId, variantId, modifiers)
        if (!item) return

        if (item.quantity <= 1) {
            removeItem(productId, variantId, modifiers)
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

            promoCode.value = data.data?.promo_code || code.trim()
            promoDiscount.value = data.data?.discount_amount || 0
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
                selections: item.modifiers && item.modifiers.length > 0
                    ? item.modifiers.map(m => ({
                        modifier_id: m.modifier_id,
                        modifier_name: m.modifier_name,
                        option_id: m.option_id,
                        option_name: m.option_name,
                        price: m.price,
                    }))
                    : null,
            })),
            promo_code: promoApplied.value ? promoCode.value : null,
        }
    }

    async function syncToServer() {
        if (items.value.length === 0) return false
        try {
            await post('/cart/sync', {
                items: items.value.map((item) => ({
                    product_id: item.product_id,
                    variant_id: item.variant_id,
                    quantity: item.quantity,
                    selections: item.modifiers && item.modifiers.length > 0
                        ? item.modifiers.map(m => ({
                            modifier_id: m.modifier_id,
                            modifier_name: m.modifier_name,
                            option_id: m.option_id,
                            option_name: m.option_name,
                            price: m.price,
                        }))
                        : null,
                })),
            })
            return true
        } catch (err) {
            console.error('[MiniApp] Cart sync error:', err)
            return false
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
        getItemUnitPrice,
        addItem,
        removeItem,
        updateQuantity,
        incrementQuantity,
        decrementQuantity,
        applyPromo,
        clearPromo,
        clearCart,
        getCartPayload,
        syncToServer,
    }
})
