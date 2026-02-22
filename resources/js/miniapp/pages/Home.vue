<template>
    <div class="pb-20">
        <!-- Header -->
        <div class="sticky top-0 z-10 px-4 py-3" style="background-color: var(--tg-theme-bg-color)">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img
                        v-if="storeInfo.logo"
                        :src="storeInfo.logo"
                        :alt="storeInfo.name"
                        class="h-8 w-8 rounded-full object-cover"
                    />
                    <div>
                        <h1 class="text-base font-semibold" style="color: var(--tg-theme-text-color)">
                            {{ storeInfo.name }}
                        </h1>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="goToSearch"
                        class="flex h-9 w-9 items-center justify-center rounded-full"
                        style="background-color: var(--tg-theme-secondary-bg-color)"
                    >
                        <svg class="h-5 w-5" style="color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                    <button
                        @click="goToOrders"
                        class="flex h-9 w-9 items-center justify-center rounded-full"
                        style="background-color: var(--tg-theme-secondary-bg-color)"
                    >
                        <svg class="h-5 w-5" style="color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="storeInfo.loading" class="flex items-center justify-center py-20">
            <LoadingSpinner />
        </div>

        <!-- Error -->
        <div v-else-if="storeInfo.error" class="px-4 py-10 text-center">
            <p class="text-sm" style="color: var(--tg-theme-hint-color)">{{ storeInfo.error }}</p>
            <button
                @click="reload"
                class="mt-3 rounded-lg px-4 py-2 text-sm font-medium"
                style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
            >
                Qayta yuklash
            </button>
        </div>

        <template v-else>
            <!-- Banners -->
            <div v-if="storeInfo.banners.length" class="px-4 pt-2 pb-4">
                <div class="relative overflow-hidden rounded-xl">
                    <div
                        class="flex transition-transform duration-300"
                        :style="{ transform: `translateX(-${currentBanner * 100}%)` }"
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
                    <div v-if="storeInfo.banners.length > 1" class="absolute bottom-2 left-0 right-0 flex justify-center gap-1.5">
                        <span
                            v-for="(_, i) in storeInfo.banners"
                            :key="i"
                            class="h-1.5 rounded-full transition-all duration-200"
                            :class="i === currentBanner ? 'w-4' : 'w-1.5 opacity-50'"
                            style="background-color: var(--tg-theme-button-text-color)"
                        />
                    </div>
                </div>
            </div>

            <!-- Categories -->
            <div v-if="storeInfo.categories.length" class="px-4 pb-4">
                <h2 class="mb-3 text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                    Kategoriyalar
                </h2>
                <div class="grid grid-cols-4 gap-3">
                    <button
                        v-for="cat in storeInfo.categories"
                        :key="cat.id"
                        @click="goToCategory(cat.id)"
                        class="flex flex-col items-center gap-1.5"
                    >
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-2xl"
                            style="background-color: var(--tg-theme-secondary-bg-color)"
                        >
                            <img
                                v-if="cat.icon"
                                :src="cat.icon"
                                :alt="cat.name"
                                class="h-7 w-7 object-contain"
                            />
                            <span v-else class="text-xl">{{ cat.emoji || '📦' }}</span>
                        </div>
                        <span
                            class="line-clamp-2 text-center text-[11px] leading-tight"
                            style="color: var(--tg-theme-text-color)"
                        >
                            {{ cat.name }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Featured Products -->
            <div v-if="storeInfo.featuredProducts.length" class="px-4 pb-4">
                <h2 class="mb-3 text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                    Tavsiya etilgan
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <ProductCard
                        v-for="product in storeInfo.featuredProducts"
                        :key="product.id"
                        :product="product"
                    />
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-if="!storeInfo.categories.length && !storeInfo.featuredProducts.length && !storeInfo.banners.length"
                class="px-4 py-10 text-center"
            >
                <p class="text-sm" style="color: var(--tg-theme-hint-color)">
                    Do'konda hozircha mahsulotlar yo'q
                </p>
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
import LoadingSpinner from '../components/LoadingSpinner.vue'

const router = useRouter()
const storeInfo = useStoreInfo()
const userStore = useUserStore()
const { hapticImpact, hideBackButton } = useTelegram()

const currentBanner = ref(0)
let bannerInterval = null

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
})

onUnmounted(() => {
    if (bannerInterval) clearInterval(bannerInterval)
})
</script>
