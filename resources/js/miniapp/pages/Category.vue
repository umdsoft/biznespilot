<template>
    <div class="pb-20">
        <BackButton />

        <!-- Header -->
        <div class="sticky top-0 z-10 px-4 py-3" style="background-color: var(--tg-theme-bg-color)">
            <h1 class="text-lg font-semibold" style="color: var(--tg-theme-text-color)">
                {{ categoryName }}
            </h1>
        </div>

        <!-- Loading first page -->
        <div v-if="loading && products.length === 0" class="flex items-center justify-center py-20">
            <LoadingSpinner />
        </div>

        <!-- Products grid -->
        <div v-else class="px-4">
            <div v-if="products.length" class="grid grid-cols-2 gap-3">
                <ProductCard
                    v-for="product in products"
                    :key="product.id"
                    :product="product"
                />
            </div>

            <!-- Empty -->
            <div v-if="!loading && products.length === 0" class="py-10 text-center">
                <p class="text-sm" style="color: var(--tg-theme-hint-color)">
                    Bu kategoriyada mahsulotlar yo'q
                </p>
            </div>

            <!-- Load more -->
            <div v-if="hasMore" ref="loadMoreRef" class="flex justify-center py-6">
                <LoadingSpinner v-if="loading" size="sm" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useStoreInfo } from '../stores/store'
import ProductCard from '../components/ProductCard.vue'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import BackButton from '../components/BackButton.vue'

const props = defineProps({
    id: { type: [String, Number], required: true },
})

const storeInfo = useStoreInfo()

const products = ref([])
const loading = ref(false)
const hasMore = ref(false)
const page = ref(1)
const loadMoreRef = ref(null)

const categoryName = computed(() => {
    const cat = storeInfo.categories.find((c) => String(c.id) === String(props.id))
    return cat?.name || 'Kategoriya'
})

let observer = null

async function loadProducts() {
    if (loading.value) return
    loading.value = true

    try {
        const data = await storeInfo.fetchCategoryProducts(props.id, page.value)
        const newProducts = data.products || data.data || []
        products.value.push(...newProducts)
        hasMore.value = data.has_more || (data.meta?.last_page > page.value)
        page.value++
    } catch (err) {
        console.error('[MiniApp] Category products error:', err)
    } finally {
        loading.value = false
    }
}

function setupInfiniteScroll() {
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0].isIntersecting && hasMore.value && !loading.value) {
                loadProducts()
            }
        },
        { rootMargin: '200px' }
    )

    if (loadMoreRef.value) {
        observer.observe(loadMoreRef.value)
    }
}

onMounted(async () => {
    await loadProducts()
    setupInfiniteScroll()
})

onUnmounted(() => {
    observer?.disconnect()
})
</script>
