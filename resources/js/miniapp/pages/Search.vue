<template>
    <div class="pb-24">
        <BackButton />

        <!-- Search header -->
        <div class="sticky top-0 z-10 px-4 py-2.5" style="background-color: var(--tg-theme-bg-color)">
            <div
                class="flex items-center gap-2.5 rounded-xl px-3.5 py-2.5"
                style="background-color: var(--tg-theme-secondary-bg-color)"
            >
                <svg class="h-[18px] w-[18px] shrink-0" style="color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    ref="searchInput"
                    v-model="query"
                    type="text"
                    placeholder="Mahsulot qidirish..."
                    class="w-full bg-transparent text-[13px] outline-none"
                    style="color: var(--tg-theme-text-color)"
                    @input="onSearchInput"
                />
                <button
                    v-if="query"
                    @click="clearSearch"
                    class="shrink-0 flex h-6 w-6 items-center justify-center rounded-full tap-active"
                    style="background-color: var(--tg-theme-bg-color)"
                >
                    <svg class="h-3.5 w-3.5" style="color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading && products.length === 0" class="px-4 pt-2">
            <SkeletonLoader type="card" :count="4" />
        </div>

        <!-- Results -->
        <div v-else class="px-4 pt-2">
            <!-- Empty query — show recent searches -->
            <div v-if="!query" class="py-6">
                <!-- Recent searches -->
                <div v-if="recentSearches.length">
                    <div class="flex items-center justify-between mb-2.5">
                        <p class="text-xs font-semibold" style="color: var(--tg-theme-hint-color)">So'nggi qidiruvlar</p>
                        <button @click="clearRecentSearches" class="text-xs tap-active" style="color: var(--tg-theme-hint-color)">Tozalash</button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="term in recentSearches"
                            :key="term"
                            @click="searchFromRecent(term)"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium tap-active"
                            style="background-color: var(--tg-theme-secondary-bg-color); color: var(--tg-theme-text-color)"
                        >
                            {{ term }}
                        </button>
                    </div>
                </div>
                <div v-else class="text-center pt-6">
                    <svg class="mx-auto h-12 w-12" style="color: var(--tg-theme-hint-color); opacity: 0.3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <p class="mt-3 text-sm" style="color: var(--tg-theme-hint-color)">
                        Mahsulot nomini yozing
                    </p>
                </div>
            </div>

            <!-- No results -->
            <div v-else-if="searched && products.length === 0 && !loading" class="py-10 text-center">
                <p class="text-sm" style="color: var(--tg-theme-hint-color)">
                    "{{ query }}" bo'yicha hech narsa topilmadi
                </p>
            </div>

            <!-- Products grid -->
            <div v-else-if="products.length" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
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
import SkeletonLoader from '../components/SkeletonLoader.vue'
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

const RECENT_KEY = 'miniapp_recent_searches'
const recentSearches = ref([])

// Load recent searches from localStorage
try {
    const saved = localStorage.getItem(RECENT_KEY)
    if (saved) recentSearches.value = JSON.parse(saved)
} catch { /* ignore */ }

function saveRecentSearch(term) {
    const trimmed = term.trim()
    if (!trimmed) return
    recentSearches.value = [trimmed, ...recentSearches.value.filter(s => s !== trimmed)].slice(0, 5)
    localStorage.setItem(RECENT_KEY, JSON.stringify(recentSearches.value))
}

function clearRecentSearches() {
    recentSearches.value = []
    localStorage.removeItem(RECENT_KEY)
}

function searchFromRecent(term) {
    query.value = term
    search(true)
}

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

    if (reset) saveRecentSearch(query.value)

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
