<template>
    <div class="pb-20">
        <BackButton />

        <!-- Search header -->
        <div class="sticky top-0 z-10 px-4 py-3" style="background-color: var(--tg-theme-bg-color)">
            <div
                class="flex items-center gap-2 rounded-xl px-3 py-2.5"
                style="background-color: var(--tg-theme-secondary-bg-color)"
            >
                <svg class="h-5 w-5 shrink-0" style="color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    ref="searchInput"
                    v-model="query"
                    type="text"
                    placeholder="Mahsulot qidirish..."
                    class="w-full bg-transparent text-sm outline-none"
                    style="color: var(--tg-theme-text-color)"
                    @input="onSearchInput"
                />
                <button
                    v-if="query"
                    @click="clearSearch"
                    class="shrink-0"
                >
                    <svg class="h-5 w-5" style="color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading && products.length === 0" class="flex items-center justify-center py-20">
            <LoadingSpinner />
        </div>

        <!-- Results -->
        <div v-else class="px-4 pt-2">
            <!-- Empty query state -->
            <div v-if="!query" class="py-10 text-center">
                <svg class="mx-auto h-12 w-12" style="color: var(--tg-theme-hint-color); opacity: 0.3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <p class="mt-3 text-sm" style="color: var(--tg-theme-hint-color)">
                    Mahsulot nomini yozing
                </p>
            </div>

            <!-- No results -->
            <div v-else-if="searched && products.length === 0 && !loading" class="py-10 text-center">
                <p class="text-sm" style="color: var(--tg-theme-hint-color)">
                    "{{ query }}" bo'yicha hech narsa topilmadi
                </p>
            </div>

            <!-- Products grid -->
            <div v-else-if="products.length" class="grid grid-cols-2 gap-3">
                <ProductCard
                    v-for="product in products"
                    :key="product.id"
                    :product="product"
                />
            </div>

            <!-- Load more -->
            <div v-if="hasMore" ref="loadMoreRef" class="flex justify-center py-6">
                <LoadingSpinner v-if="loading" size="sm" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useStoreInfo } from '../stores/store'
import ProductCard from '../components/ProductCard.vue'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import BackButton from '../components/BackButton.vue'

const storeInfo = useStoreInfo()

const searchInput = ref(null)
const query = ref('')
const products = ref([])
const loading = ref(false)
const searched = ref(false)
const hasMore = ref(false)
const page = ref(1)
const loadMoreRef = ref(null)

let debounceTimer = null
let observer = null

function onSearchInput() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        search(true)
    }, 400)
}

async function search(reset = false) {
    if (!query.value.trim()) {
        products.value = []
        searched.value = false
        return
    }

    if (reset) {
        page.value = 1
        products.value = []
    }

    loading.value = true
    searched.value = true

    try {
        const data = await storeInfo.searchProducts(query.value.trim(), page.value)
        const newProducts = data.products || data.data || []

        if (reset) {
            products.value = newProducts
        } else {
            products.value.push(...newProducts)
        }

        hasMore.value = data.has_more || (data.meta?.last_page > page.value)
        page.value++
    } catch (err) {
        console.error('[MiniApp] Search error:', err)
    } finally {
        loading.value = false
    }
}

function clearSearch() {
    query.value = ''
    products.value = []
    searched.value = false
    searchInput.value?.focus()
}

function setupInfiniteScroll() {
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0].isIntersecting && hasMore.value && !loading.value) {
                search(false)
            }
        },
        { rootMargin: '200px' }
    )

    if (loadMoreRef.value) {
        observer.observe(loadMoreRef.value)
    }
}

onMounted(() => {
    searchInput.value?.focus()
    setupInfiniteScroll()
})

onUnmounted(() => {
    clearTimeout(debounceTimer)
    observer?.disconnect()
})
</script>
