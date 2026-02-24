<template>
    <div class="pb-nav">
        <!-- Search header -->
        <div class="sticky top-0 z-20 header-blur">
            <div style="padding: 12px 16px">
                <div
                    class="flex items-center"
                    style="height: 44px; padding: 0 16px 0 40px; border-radius: 12px; background-color: var(--color-bg-tertiary); position: relative"
                >
                    <svg style="width: 18px; height: 18px; color: var(--tg-theme-hint-color); position: absolute; left: 14px; top: 50%; transform: translateY(-50%)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        ref="searchInput"
                        v-model="query"
                        type="text"
                        placeholder="Mahsulot qidirish..."
                        class="w-full bg-transparent outline-none"
                        style="font-size: 14px; color: var(--tg-theme-text-color)"
                        @input="onSearchInput"
                    />
                    <button
                        v-if="query"
                        @click="clearSearch"
                        class="shrink-0 flex items-center justify-center tap-active"
                        style="width: 28px; height: 28px; border-radius: 50%; background-color: var(--tg-theme-secondary-bg-color)"
                    >
                        <svg style="width: 14px; height: 14px; color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading && products.length === 0" style="padding: 8px 16px 0">
            <SkeletonLoader type="card" :count="4" />
        </div>

        <!-- Results -->
        <div v-else style="padding: 8px 16px 0">
            <!-- Empty query — show categories + recent searches -->
            <div v-if="!query" style="padding-top: 8px">
                <!-- Recent searches -->
                <div v-if="recentSearches.length" style="margin-bottom: 24px">
                    <div class="flex items-center justify-between" style="margin-bottom: 12px">
                        <p class="section-title" style="font-size: 15px">So'nggi qidiruvlar</p>
                        <button @click="clearRecentSearches" class="btn-ghost" style="font-size: 13px; color: var(--tg-theme-hint-color); padding: 4px 0">Tozalash</button>
                    </div>
                    <div class="flex flex-wrap" style="gap: 8px">
                        <button
                            v-for="term in recentSearches"
                            :key="term"
                            @click="searchFromRecent(term)"
                            class="flex items-center tap-active"
                            style="gap: 6px; padding: 8px 14px; border-radius: 12px; background-color: var(--tg-theme-secondary-bg-color); font-size: 13px; font-weight: 500; color: var(--tg-theme-text-color)"
                        >
                            <svg style="width: 14px; height: 14px; color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ term }}
                        </button>
                    </div>
                </div>

                <!-- Browse categories -->
                <div v-if="storeInfo.categories.length">
                    <p class="section-title" style="font-size: 15px; margin-bottom: 12px">Kategoriyalar</p>
                    <div>
                        <button
                            v-for="(cat, idx) in storeInfo.categories"
                            :key="cat.id"
                            @click="goToCategory(cat.id)"
                            class="flex w-full items-center tap-active"
                            style="padding: 14px 0; gap: 14px"
                            :style="idx < storeInfo.categories.length - 1 ? { borderBottom: '1px solid var(--color-divider)' } : {}"
                        >
                            <div class="flex items-center justify-center shrink-0" style="width: 40px; height: 40px; border-radius: 12px; background-color: var(--tg-theme-secondary-bg-color)">
                                <img v-if="cat.icon" :src="cat.icon" :alt="cat.name" style="width: 22px; height: 22px; object-fit: contain" />
                                <span v-else style="font-size: 18px">{{ cat.emoji || '📦' }}</span>
                            </div>
                            <span class="flex-1 text-left" style="font-size: 15px; font-weight: 500; color: var(--tg-theme-text-color)">{{ cat.name }}</span>
                            <svg style="width: 18px; height: 18px; color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- No categories, no recent -->
                <div v-if="!recentSearches.length && !storeInfo.categories.length" class="empty-state" style="min-height: 40vh">
                    <div class="empty-state-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <p class="empty-state-title">Mahsulot nomini yozing</p>
                </div>
            </div>

            <!-- No results -->
            <div v-else-if="searched && products.length === 0 && !loading" class="empty-state" style="min-height: 40vh">
                <div class="empty-state-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <p class="empty-state-title">Hech narsa topilmadi</p>
                <p class="empty-state-desc">"{{ query }}" bo'yicha natija yo'q</p>
            </div>

            <!-- Products grid -->
            <div v-else-if="products.length" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px">
                <ProductCard
                    v-for="product in products"
                    :key="product.id"
                    :product="product"
                />
            </div>

            <!-- Load more -->
            <div v-if="hasMore" ref="loadMoreRef" class="flex justify-center" style="padding: 24px 0">
                <LoadingSpinner v-if="loading" size="sm" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useStoreInfo } from '../stores/store'
import { useTelegram } from '../composables/useTelegram'
import ProductCard from '../components/ProductCard.vue'
import SkeletonLoader from '../components/SkeletonLoader.vue'
import LoadingSpinner from '../components/LoadingSpinner.vue'

const router = useRouter()
const storeInfo = useStoreInfo()
const { hapticImpact } = useTelegram()

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

function goToCategory(id) {
    hapticImpact('light')
    router.push({ name: 'category', params: { id } })
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
