import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useApi } from '../composables/useApi'

export const useStoreInfo = defineStore('storeInfo', () => {
    const { get, post } = useApi()

    const name = ref('')
    const slug = ref('')
    const logo = ref('')
    const description = ref('')
    const phone = ref('')
    const address = ref('')
    const workingHours = ref('')
    const currency = ref("so'm")
    const deliveryInfo = ref('')
    const minOrderAmount = ref(0)
    const categories = ref([])
    const featuredProducts = ref([])
    const banners = ref([])
    const storeType = ref('')
    const loading = ref(false)
    const error = ref(null)

    const hasLoaded = computed(() => !!name.value)

    async function fetchStore(storeSlug) {
        loading.value = true
        error.value = null
        try {
            const data = await get('/store')
            name.value = data.name || ''
            slug.value = storeSlug
            logo.value = data.logo || ''
            description.value = data.description || ''
            phone.value = data.phone || ''
            address.value = data.address || ''
            workingHours.value = data.working_hours || ''
            currency.value = data.currency || "so'm"
            deliveryInfo.value = data.delivery_info || ''
            minOrderAmount.value = data.min_order_amount || 0
            categories.value = data.categories || []
            featuredProducts.value = data.featured_products || []
            banners.value = data.banners || []
            storeType.value = data.store_type || 'ecommerce'
        } catch (err) {
            error.value = err.message || "Do'kon ma'lumotlarini yuklashda xatolik"
            console.error('[MiniApp] Store fetch error:', err)
        } finally {
            loading.value = false
        }
    }

    async function fetchCategories() {
        try {
            const data = await get('/categories')
            categories.value = data.categories || data || []
        } catch (err) {
            console.error('[MiniApp] Categories fetch error:', err)
        }
    }

    async function fetchCategoryProducts(categoryId, page = 1) {
        try {
            const data = await get(`/categories/${categoryId}/products`, { page })
            return data
        } catch (err) {
            console.error('[MiniApp] Category products fetch error:', err)
            return { products: [], has_more: false }
        }
    }

    async function fetchProduct(productSlug) {
        try {
            return await get(`/products/${productSlug}`)
        } catch (err) {
            console.error('[MiniApp] Product fetch error:', err)
            return null
        }
    }

    async function searchProducts(query, page = 1) {
        try {
            return await get('/products/search', { q: query, page })
        } catch (err) {
            console.error('[MiniApp] Search error:', err)
            return { products: [], has_more: false }
        }
    }

    async function fetchCatalogItems(params = {}) {
        try {
            return await get('/catalog', params)
        } catch (err) {
            console.error('[MiniApp] Catalog fetch error:', err)
            return { items: [], has_more: false }
        }
    }

    async function fetchCatalogItem(slug) {
        try {
            return await get(`/catalog/${slug}`)
        } catch (err) {
            console.error('[MiniApp] Catalog item fetch error:', err)
            return null
        }
    }

    async function searchCatalog(query, page = 1) {
        try {
            return await get('/catalog/search', { q: query, page })
        } catch (err) {
            console.error('[MiniApp] Catalog search error:', err)
            return { items: [], has_more: false }
        }
    }

    // ========== ADMIN API METHODS ==========
    async function fetchAdminDashboard() {
        try {
            return await get('/admin/dashboard')
        } catch (err) {
            console.error('[MiniApp] Admin dashboard error:', err)
            throw err
        }
    }

    async function fetchAdminOrders(params = {}) {
        try {
            return await get('/admin/orders', params)
        } catch (err) {
            console.error('[MiniApp] Admin orders error:', err)
            throw err
        }
    }

    async function fetchAdminOrder(orderId) {
        try {
            return await get(`/admin/orders/${orderId}`)
        } catch (err) {
            console.error('[MiniApp] Admin order detail error:', err)
            throw err
        }
    }

    async function updateOrderStatus(orderId, status) {
        try {
            return await post(`/admin/orders/${orderId}/status`, { status })
        } catch (err) {
            console.error('[MiniApp] Admin order status error:', err)
            throw err
        }
    }

    async function fetchAdminCatalog() {
        try {
            return await get('/admin/catalog')
        } catch (err) {
            console.error('[MiniApp] Admin catalog error:', err)
            throw err
        }
    }

    async function toggleAdminCatalogItem(itemId) {
        try {
            return await post(`/admin/catalog/${itemId}/toggle`)
        } catch (err) {
            console.error('[MiniApp] Admin catalog toggle error:', err)
            throw err
        }
    }

    async function fetchAdminStats() {
        try {
            return await get('/admin/stats')
        } catch (err) {
            console.error('[MiniApp] Admin stats error:', err)
            throw err
        }
    }

    return {
        name,
        slug,
        logo,
        description,
        phone,
        address,
        workingHours,
        currency,
        deliveryInfo,
        minOrderAmount,
        categories,
        featuredProducts,
        banners,
        storeType,
        loading,
        error,
        hasLoaded,
        fetchStore,
        fetchCategories,
        fetchCategoryProducts,
        fetchProduct,
        searchProducts,
        fetchCatalogItems,
        fetchCatalogItem,
        searchCatalog,
        // Admin methods
        fetchAdminDashboard,
        fetchAdminOrders,
        fetchAdminOrder,
        updateOrderStatus,
        fetchAdminCatalog,
        toggleAdminCatalogItem,
        fetchAdminStats,
    }
})
