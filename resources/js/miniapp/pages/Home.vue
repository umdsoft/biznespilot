<template>
    <div class="pb-20">
        <!-- Header -->
        <div class="sticky top-0 z-10 px-4 py-2.5" style="background-color: var(--tg-theme-bg-color)">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <img
                        v-if="storeInfo.logo"
                        :src="storeInfo.logo"
                        :alt="storeInfo.name"
                        class="h-8 w-8 rounded-full object-cover"
                    />
                    <h1 class="text-base font-bold" style="color: var(--tg-theme-text-color)">
                        {{ storeInfo.name }}
                    </h1>
                </div>
                <button
                    @click="goToOrders"
                    class="flex h-9 w-9 items-center justify-center rounded-full tap-active"
                    style="background-color: var(--tg-theme-secondary-bg-color)"
                >
                    <svg class="h-[18px] w-[18px]" style="color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </button>
            </div>

            <!-- Search bar -->
            <button
                @click="goToSearch"
                class="mt-2.5 flex w-full items-center gap-2.5 rounded-xl px-3.5 py-2.5 tap-active"
                style="background-color: var(--tg-theme-secondary-bg-color)"
            >
                <svg class="h-4 w-4 shrink-0" style="color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span class="text-[13px]" style="color: var(--tg-theme-hint-color)">Mahsulot qidirish...</span>
            </button>
        </div>

        <!-- Loading skeleton -->
        <div v-if="storeInfo.loading" class="px-4 pt-3 space-y-5">
            <SkeletonLoader type="banner" :count="1" />
            <SkeletonLoader type="category" :count="4" />
            <SkeletonLoader type="card" :count="4" />
        </div>

        <!-- Error -->
        <div v-else-if="storeInfo.error" class="px-4 py-12 text-center">
            <p class="text-sm" style="color: var(--tg-theme-hint-color)">{{ storeInfo.error }}</p>
            <button
                @click="reload"
                class="mt-4 rounded-xl px-5 py-2.5 text-sm font-medium tap-active"
                style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
            >
                Qayta yuklash
            </button>
        </div>

        <template v-else>
            <!-- Banners -->
            <div
                v-if="storeInfo.banners.length"
                class="px-4 pt-2 pb-4"
                @touchstart="onBannerTouchStart"
                @touchmove="onBannerTouchMove"
                @touchend="onBannerTouchEnd"
            >
                <div class="relative overflow-hidden rounded-2xl" style="box-shadow: 0 2px 8px rgba(0,0,0,0.08)">
                    <div
                        class="flex transition-transform duration-300"
                        :style="{ transform: `translateX(calc(-${currentBanner * 100}% + ${swipeOffset}px))` }"
                    >
                        <div
                            v-for="banner in storeInfo.banners"
                            :key="banner.id"
                            class="w-full shrink-0"
                        >
                            <img
                                :src="banner.image"
                                :alt="banner.title || ''"
                                class="aspect-[2/1] w-full object-cover"
                            />
                        </div>
                    </div>
                    <!-- Dots -->
                    <div v-if="storeInfo.banners.length > 1" class="absolute bottom-2.5 left-0 right-0 flex justify-center gap-1.5">
                        <span
                            v-for="(_, i) in storeInfo.banners"
                            :key="i"
                            class="h-1.5 rounded-full transition-all duration-200"
                            :class="i === currentBanner ? 'w-4' : 'w-1.5 opacity-50'"
                            style="background-color: #fff"
                        />
                    </div>
                </div>
            </div>

            <!-- Categories -->
            <div v-if="storeInfo.categories.length" class="pb-5">
                <h2 class="px-4 mb-3 text-[15px] font-bold" style="color: var(--tg-theme-text-color)">
                    Kategoriyalar
                </h2>
                <!-- Horizontal scroll for 8+ categories -->
                <div v-if="storeInfo.categories.length >= 8" class="overflow-x-auto no-scrollbar px-4">
                    <div class="flex gap-4" style="width: max-content">
                        <button
                            v-for="cat in storeInfo.categories"
                            :key="cat.id"
                            @click="goToCategory(cat.id)"
                            class="flex flex-col items-center gap-2 tap-active"
                            style="width: 68px"
                        >
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl"
                                style="background-color: var(--tg-theme-secondary-bg-color)"
                            >
                                <img v-if="cat.icon" :src="cat.icon" :alt="cat.name" class="h-7 w-7 object-contain" />
                                <span v-else class="text-2xl">{{ cat.emoji || '📦' }}</span>
                            </div>
                            <span class="line-clamp-2 text-center text-[11px] font-medium leading-tight" style="color: var(--tg-theme-text-color)">
                                {{ cat.name }}
                            </span>
                        </button>
                    </div>
                </div>
                <!-- Grid for < 8 categories -->
                <div v-else class="px-4" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
                    <button
                        v-for="cat in storeInfo.categories"
                        :key="cat.id"
                        @click="goToCategory(cat.id)"
                        class="flex flex-col items-center gap-2 tap-active"
                    >
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-2xl"
                            style="background-color: var(--tg-theme-secondary-bg-color)"
                        >
                            <img v-if="cat.icon" :src="cat.icon" :alt="cat.name" class="h-7 w-7 object-contain" />
                            <span v-else class="text-2xl">{{ cat.emoji || '📦' }}</span>
                        </div>
                        <span class="line-clamp-2 text-center text-[11px] font-medium leading-tight" style="color: var(--tg-theme-text-color)">
                            {{ cat.name }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Featured Products -->
            <div v-if="storeInfo.featuredProducts.length" class="px-4 pb-5">
                <h2 class="mb-3 text-[15px] font-bold" style="color: var(--tg-theme-text-color)">
                    Tavsiya etilgan
                </h2>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                    <ProductCard
                        v-for="product in storeInfo.featuredProducts"
                        :key="product.id"
                        :product="product"
                    />
                </div>
            </div>

            <!-- Category product rows -->
            <div
                v-for="cat in topCategories"
                :key="'row-' + cat.id"
                class="pb-5"
            >
                <div class="flex items-center justify-between px-4 mb-3">
                    <h2 class="text-[15px] font-bold" style="color: var(--tg-theme-text-color)">
                        {{ cat.name }}
                    </h2>
                    <button
                        @click="goToCategory(cat.id)"
                        class="text-xs font-semibold tap-active"
                        style="color: var(--tg-theme-button-color)"
                    >
                        Hammasi →
                    </button>
                </div>
                <!-- Horizontal scroll product row -->
                <div class="overflow-x-auto no-scrollbar pl-4">
                    <div class="flex gap-2.5 pr-4" style="width: max-content">
                        <div v-for="product in cat.products" :key="product.id" class="w-[150px] shrink-0">
                            <ProductCard :product="product" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-if="!storeInfo.categories.length && !storeInfo.featuredProducts.length && !storeInfo.banners.length"
                class="flex flex-col items-center justify-center px-6 py-20 text-center"
            >
                <div
                    class="mb-5 flex h-20 w-20 items-center justify-center rounded-2xl"
                    style="background-color: var(--tg-theme-secondary-bg-color)"
                >
                    <svg class="h-10 w-10" style="color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <h3 class="mb-1.5 text-base font-bold" style="color: var(--tg-theme-text-color)">
                    Do'kon tayyorlanmoqda
                </h3>
                <p class="text-sm leading-relaxed" style="color: var(--tg-theme-hint-color)">
                    Tez orada mahsulotlar qo'shiladi
                </p>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useStoreInfo } from '../stores/store'
import { useUserStore } from '../stores/user'
import { useTelegram } from '../composables/useTelegram'
import ProductCard from '../components/ProductCard.vue'
import SkeletonLoader from '../components/SkeletonLoader.vue'

const router = useRouter()
const storeInfo = useStoreInfo()
const userStore = useUserStore()
const { hapticImpact, hideBackButton } = useTelegram()

const currentBanner = ref(0)
let bannerInterval = null

// Touch swipe for banners
const swipeOffset = ref(0)
let touchStartX = 0
let touchStartY = 0
let isSwiping = false

function onBannerTouchStart(e) {
    touchStartX = e.touches[0].clientX
    touchStartY = e.touches[0].clientY
    isSwiping = false
    if (bannerInterval) clearInterval(bannerInterval)
}

function onBannerTouchMove(e) {
    const dx = e.touches[0].clientX - touchStartX
    const dy = e.touches[0].clientY - touchStartY
    if (!isSwiping && Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 10) {
        isSwiping = true
    }
    if (isSwiping) {
        swipeOffset.value = dx * 0.4
    }
}

function onBannerTouchEnd() {
    if (isSwiping) {
        if (swipeOffset.value < -40 && currentBanner.value < storeInfo.banners.length - 1) {
            currentBanner.value++
        } else if (swipeOffset.value > 40 && currentBanner.value > 0) {
            currentBanner.value--
        }
    }
    swipeOffset.value = 0
    isSwiping = false
    startBannerRotation()
}

// Category product rows — first 3 categories with products
const topCategories = ref([])

async function loadCategoryRows() {
    const cats = storeInfo.categories.slice(0, 3)
    for (const cat of cats) {
        try {
            const data = await storeInfo.fetchCategoryProducts(cat.id, 1)
            const products = (data.products || data.data || []).slice(0, 6)
            if (products.length > 0) {
                topCategories.value.push({ ...cat, products })
            }
        } catch {
            // skip
        }
    }
}

function goToSearch() {
    hapticImpact('light')
    router.push({ name: 'search' })
}

function goToOrders() {
    hapticImpact('light')
    router.push({ name: 'orders' })
}

function goToCategory(id) {
    hapticImpact('light')
    router.push({ name: 'category', params: { id } })
}

function reload() {
    const el = document.getElementById('miniapp')
    const slug = el?.dataset?.storeSlug
    if (slug) storeInfo.fetchStore(slug)
}

function startBannerRotation() {
    if (bannerInterval) clearInterval(bannerInterval)
    if (storeInfo.banners.length > 1) {
        bannerInterval = setInterval(() => {
            currentBanner.value = (currentBanner.value + 1) % storeInfo.banners.length
        }, 4000)
    }
}

onMounted(() => {
    hideBackButton()
    userStore.fetchProfile()
    startBannerRotation()

    // Load category rows after store data is ready
    if (storeInfo.categories.length > 0) {
        loadCategoryRows()
    }
})

onUnmounted(() => {
    if (bannerInterval) clearInterval(bannerInterval)
})
</script>
