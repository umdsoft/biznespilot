<template>
    <div class="pb-nav">
        <!-- Header -->
        <div class="sticky top-0 z-20 header-blur">
            <div style="padding: 12px 16px">
                <div class="flex items-center justify-between">
                    <div class="flex items-center" style="gap: 10px">
                        <img
                            v-if="storeInfo.logo"
                            :src="storeInfo.logo"
                            :alt="storeInfo.name"
                            class="object-cover card-shadow"
                            style="width: 36px; height: 36px; border-radius: 50%"
                        />
                        <div
                            v-else
                            class="flex items-center justify-center"
                            style="width: 36px; height: 36px; border-radius: 50%; background-color: var(--tg-theme-button-color)"
                        >
                            <svg style="width: 16px; height: 16px; color: var(--tg-theme-button-text-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.15c0 .415.336.75.75.75z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 style="font-size: 18px; font-weight: 700; line-height: 1.2; color: var(--tg-theme-text-color)">
                                {{ storeInfo.name }}
                            </h1>
                            <p v-if="storeInfo.description" class="line-clamp-1" style="font-size: 12px; line-height: 1.2; color: var(--tg-theme-hint-color); margin-top: 1px">
                                {{ storeInfo.description }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Search bar -->
                <button
                    @click="goToSearch"
                    class="flex w-full items-center tap-active"
                    style="margin-top: 12px; height: 44px; padding: 0 16px 0 40px; border-radius: 12px; background-color: var(--color-bg-tertiary); border: none; position: relative"
                >
                    <svg style="width: 18px; height: 18px; color: var(--tg-theme-hint-color); position: absolute; left: 14px; top: 50%; transform: translateY(-50%)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span style="font-size: 14px; color: var(--tg-theme-hint-color)">Mahsulot qidirish...</span>
                </button>
            </div>
        </div>

        <!-- Loading skeleton -->
        <div v-if="storeInfo.loading" class="space-y-5" style="padding: 12px 16px 0">
            <SkeletonLoader type="banner" :count="1" />
            <SkeletonLoader type="category" :count="4" />
            <SkeletonLoader type="card" :count="4" />
        </div>

        <!-- Error -->
        <div v-else-if="storeInfo.error" class="empty-state">
            <div class="empty-state-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
            </div>
            <p class="empty-state-title">Xatolik yuz berdi</p>
            <p class="empty-state-desc">{{ storeInfo.error }}</p>
            <div class="empty-state-action">
                <button @click="reload" class="btn-primary" style="width: auto; height: 44px; padding: 0 24px; font-size: 14px">
                    Qayta yuklash
                </button>
            </div>
        </div>

        <template v-else>
            <!-- Banners -->
            <div
                v-if="storeInfo.banners.length"
                style="padding: 8px 16px 4px"
                @touchstart="onBannerTouchStart"
                @touchmove="onBannerTouchMove"
                @touchend="onBannerTouchEnd"
            >
                <div class="relative overflow-hidden" style="border-radius: 16px; box-shadow: var(--shadow-md)">
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
                            class="rounded-full transition-all duration-200"
                            :style="{ height: '6px', width: i === currentBanner ? '16px' : '6px', opacity: i === currentBanner ? 1 : 0.5, backgroundColor: '#fff' }"
                        />
                    </div>
                </div>
            </div>

            <!-- Categories -->
            <div v-if="storeInfo.categories.length" style="padding-top: 16px; padding-bottom: 4px">
                <div class="flex items-center justify-between" style="padding: 0 16px; margin-bottom: 12px">
                    <h2 class="section-title">Kategoriyalar</h2>
                </div>
                <!-- Horizontal scroll for 8+ categories -->
                <div v-if="storeInfo.categories.length >= 8" class="overflow-x-auto no-scrollbar" style="padding: 0 16px">
                    <div class="flex" style="width: max-content; gap: 16px">
                        <button
                            v-for="cat in storeInfo.categories"
                            :key="cat.id"
                            @click="goToCategory(cat.id)"
                            class="flex flex-col items-center tap-active"
                            style="width: 64px; text-align: center"
                        >
                            <div
                                class="flex items-center justify-center"
                                style="width: 52px; height: 52px; border-radius: 16px; background-color: var(--tg-theme-secondary-bg-color)"
                            >
                                <img v-if="cat.icon" :src="cat.icon" :alt="cat.name" style="width: 28px; height: 28px; object-fit: contain" />
                                <span v-else style="font-size: 24px">{{ cat.emoji || '📦' }}</span>
                            </div>
                            <span class="line-clamp-2" style="font-size: 12px; font-weight: 500; margin-top: 6px; color: var(--tg-theme-hint-color); line-height: 1.2">
                                {{ cat.name }}
                            </span>
                        </button>
                    </div>
                </div>
                <!-- Grid for < 8 categories -->
                <div v-else style="padding: 0 16px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px">
                    <button
                        v-for="cat in storeInfo.categories"
                        :key="cat.id"
                        @click="goToCategory(cat.id)"
                        class="flex flex-col items-center tap-active"
                        style="text-align: center"
                    >
                        <div
                            class="flex items-center justify-center"
                            style="width: 52px; height: 52px; border-radius: 16px; background-color: var(--tg-theme-secondary-bg-color)"
                        >
                            <img v-if="cat.icon" :src="cat.icon" :alt="cat.name" style="width: 28px; height: 28px; object-fit: contain" />
                            <span v-else style="font-size: 24px">{{ cat.emoji || '📦' }}</span>
                        </div>
                        <span class="line-clamp-2" style="font-size: 12px; font-weight: 500; margin-top: 6px; color: var(--tg-theme-hint-color); line-height: 1.2">
                            {{ cat.name }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Featured Products -->
            <div v-if="storeInfo.featuredProducts.length" style="padding: 16px 16px 4px">
                <h2 class="section-title" style="margin-bottom: 12px">Tavsiya etilgan</h2>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px">
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
                style="padding-top: 16px; padding-bottom: 4px"
            >
                <div class="flex items-center justify-between" style="padding: 0 16px; margin-bottom: 12px">
                    <h2 class="section-title">{{ cat.name }}</h2>
                    <button @click="goToCategory(cat.id)" class="section-action tap-active">
                        Barchasi
                    </button>
                </div>
                <!-- Horizontal scroll product row -->
                <div class="overflow-x-auto no-scrollbar" style="padding-left: 16px">
                    <div class="flex" style="width: max-content; gap: 12px; padding-right: 16px">
                        <div v-for="product in cat.products" :key="product.id" style="width: 156px; flex-shrink: 0">
                            <ProductCard :product="product" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-if="!storeInfo.categories.length && !storeInfo.featuredProducts.length && !storeInfo.banners.length"
                class="empty-state"
            >
                <div class="empty-state-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <p class="empty-state-title">Do'kon tayyorlanmoqda</p>
                <p class="empty-state-desc">Tez orada mahsulotlar qo'shiladi</p>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
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

    if (storeInfo.categories.length > 0) {
        loadCategoryRows()
    }
})

onUnmounted(() => {
    if (bannerInterval) clearInterval(bannerInterval)
})
</script>
