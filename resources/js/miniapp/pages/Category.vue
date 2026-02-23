<template>
    <div class="pb-24">
        <BackButton />

        <!-- Header -->
        <div class="sticky top-0 z-10 px-4 py-3" style="background-color: var(--tg-theme-bg-color)">
            <h1 class="text-lg font-bold" style="color: var(--tg-theme-text-color)">
                {{ categoryName }}
                <span v-if="totalCount > 0" class="text-sm font-normal" style="color: var(--tg-theme-hint-color)">
                    ({{ totalCount }})
                </span>
            </h1>

            <!-- Sort & Filter buttons -->
            <div class="mt-2.5 flex gap-2">
                <button
                    @click="showSort = true"
                    class="flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold tap-active"
                    :style="{
                        backgroundColor: currentSort !== 'default' ? 'var(--tg-theme-button-color)' : 'var(--tg-theme-secondary-bg-color)',
                        color: currentSort !== 'default' ? 'var(--tg-theme-button-text-color)' : 'var(--tg-theme-text-color)',
                    }"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M6 12h12M9 17h6" />
                    </svg>
                    Saralash
                </button>
                <button
                    @click="showFilter = true"
                    class="flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold tap-active"
                    :style="{
                        backgroundColor: activeFilterCount > 0 ? 'var(--tg-theme-button-color)' : 'var(--tg-theme-secondary-bg-color)',
                        color: activeFilterCount > 0 ? 'var(--tg-theme-button-text-color)' : 'var(--tg-theme-text-color)',
                    }"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                    Filtr
                    <span
                        v-if="activeFilterCount > 0"
                        class="flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-[9px] font-bold"
                        style="background-color: var(--tg-theme-button-text-color); color: var(--tg-theme-button-color)"
                    >
                        {{ activeFilterCount }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loading && products.length === 0" class="px-4 pt-2">
            <SkeletonLoader type="card" :count="6" />
        </div>

        <!-- Products grid -->
        <div v-else class="px-4">
            <div v-if="products.length" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                <ProductCard
                    v-for="product in products"
                    :key="product.id"
                    :product="product"
                />
            </div>

            <!-- Empty -->
            <div v-if="!loading && products.length === 0" class="py-16 text-center">
                <div class="text-3xl mb-3">🔍</div>
                <p class="text-sm font-medium" style="color: var(--tg-theme-text-color)">
                    Mahsulot topilmadi
                </p>
                <p class="text-xs mt-1" style="color: var(--tg-theme-hint-color)">
                    Filtr parametrlarini o'zgartirib ko'ring
                </p>
                <button
                    v-if="activeFilterCount > 0 || currentSort !== 'default'"
                    @click="resetFilters"
                    class="mt-4 rounded-xl px-5 py-2.5 text-sm font-semibold tap-active"
                    style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
                >
                    Filtrlarni tozalash
                </button>
            </div>

            <!-- Load more -->
            <div v-if="hasMore" ref="loadMoreRef" class="flex justify-center py-6">
                <LoadingSpinner v-if="loading" size="sm" />
            </div>
        </div>

        <!-- Bottom sheets -->
        <SortSheet v-model="showSort" :current="currentSort" @select="onSortChange" />
        <FilterSheet v-model="showFilter" :filters="currentFilters" @apply="onFilterApply" />
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, computed, watch } from 'vue'
import { useStoreInfo } from '../stores/store'
import ProductCard from '../components/ProductCard.vue'
import SkeletonLoader from '../components/SkeletonLoader.vue'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import BackButton from '../components/BackButton.vue'
import SortSheet from '../components/SortSheet.vue'
import FilterSheet from '../components/FilterSheet.vue'

const props = defineProps({
    id: { type: [String, Number], required: true },
})

const storeInfo = useStoreInfo()

const products = ref([])
const loading = ref(false)
const hasMore = ref(false)
const page = ref(1)
const totalCount = ref(0)
const loadMoreRef = ref(null)

// Sort & Filter state
const showSort = ref(false)
const showFilter = ref(false)
const currentSort = ref('default')
const currentFilters = reactive({
    min_price: undefined,
    max_price: undefined,
    in_stock: undefined,
})

const categoryName = computed(() => {
    const cat = storeInfo.categories.find((c) => String(c.id) === String(props.id))
    return cat?.name || 'Kategoriya'
})

const activeFilterCount = computed(() => {
    let count = 0
    if (currentFilters.min_price) count++
    if (currentFilters.max_price) count++
    if (currentFilters.in_stock) count++
    return count
})

let observer = null

async function loadProducts(reset = false) {
    if (loading.value) return
    if (reset) {
        products.value = []
        page.value = 1
        hasMore.value = false
    }

    loading.value = true
    try {
        const filters = {
            sort: currentSort.value,
            ...currentFilters,
        }
        const data = await storeInfo.fetchCategoryProducts(props.id, page.value, filters)
        const newProducts = data.products || data.data || []
        products.value.push(...newProducts)
        hasMore.value = data.has_more || (data.meta?.last_page > page.value)
        totalCount.value = data.total || data.meta?.total || products.value.length
        page.value++
    } catch (err) {
        console.error('[MiniApp] Category products error:', err)
    } finally {
        loading.value = false
    }
}

function onSortChange(sort) {
    currentSort.value = sort
    loadProducts(true)
}

function onFilterApply(filters) {
    Object.assign(currentFilters, filters)
    loadProducts(true)
}

function resetFilters() {
    currentSort.value = 'default'
    currentFilters.min_price = undefined
    currentFilters.max_price = undefined
    currentFilters.in_stock = undefined
    loadProducts(true)
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

watch(loadMoreRef, (el) => {
    if (el && observer) observer.observe(el)
})

onMounted(async () => {
    await loadProducts()
    setupInfiniteScroll()
})

onUnmounted(() => {
    observer?.disconnect()
})
</script>
