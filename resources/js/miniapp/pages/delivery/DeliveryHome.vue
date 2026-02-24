<template>
    <div class="pb-nav">
        <!-- Header -->
        <div class="header-blur sticky top-0 z-30">
            <div style="padding: 12px 16px">
                <div class="flex items-center gap-3">
                    <img v-if="storeInfo.logo" :src="storeInfo.logo" class="h-10 w-10 rounded-xl object-cover" />
                    <div v-else class="flex h-10 w-10 items-center justify-center rounded-xl" style="background: var(--tg-theme-button-color)">
                        <span style="font-size: 20px">🍽️</span>
                    </div>
                    <div style="flex: 1; min-width: 0">
                        <h1 class="text-base font-bold truncate" style="color: var(--tg-theme-text-color)">{{ storeInfo.name }}</h1>
                        <p v-if="storeInfo.deliveryInfo" class="text-xs truncate" style="color: var(--tg-theme-hint-color)">{{ storeInfo.deliveryInfo }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories horizontal scroll -->
        <div v-if="storeInfo.categories.length" style="padding: 8px 0">
            <div class="flex overflow-x-auto gap-2 px-4 no-scrollbar">
                <button
                    v-for="cat in allCategories"
                    :key="cat.id"
                    @click="scrollToCategory(cat.id)"
                    class="shrink-0 rounded-full px-4 py-2 text-sm font-medium tap-active whitespace-nowrap"
                    :style="{
                        background: activeCategory === cat.id ? 'var(--tg-theme-button-color)' : 'var(--tg-theme-secondary-bg-color)',
                        color: activeCategory === cat.id ? 'var(--tg-theme-button-text-color)' : 'var(--tg-theme-text-color)',
                    }"
                >
                    {{ cat.name }}
                </button>
            </div>
        </div>

        <!-- Menu sections by category -->
        <div style="padding: 0 16px 16px">
            <div v-if="loading" class="flex flex-col gap-4 pt-4">
                <div v-for="i in 4" :key="i" class="skeleton" style="height: 80px; border-radius: 12px"></div>
            </div>

            <template v-else>
                <div
                    v-for="cat in categoriesWithItems"
                    :key="cat.id"
                    :ref="el => { if (el) categoryRefs[cat.id] = el }"
                    style="margin-bottom: 24px"
                >
                    <h2 class="text-base font-bold mb-3" style="color: var(--tg-theme-text-color)">{{ cat.name }}</h2>

                    <div class="flex flex-col gap-3">
                        <button
                            v-for="item in cat.items"
                            :key="item.id"
                            @click="goToItem(item)"
                            class="flex gap-3 rounded-xl p-3 tap-active text-left"
                            style="background: var(--tg-theme-secondary-bg-color)"
                        >
                            <div class="shrink-0 w-20 h-20 rounded-lg overflow-hidden">
                                <img v-if="item.image" :src="item.image" :alt="item.name" class="w-full h-full object-cover" loading="lazy" />
                                <div v-else class="w-full h-full flex items-center justify-center" style="background: var(--tg-theme-bg-color)">
                                    <span style="font-size: 28px">🍔</span>
                                </div>
                            </div>
                            <div style="flex: 1; min-width: 0">
                                <p class="text-sm font-semibold line-clamp-1" style="color: var(--tg-theme-text-color)">{{ item.name }}</p>
                                <p v-if="item.description" class="text-xs line-clamp-2 mt-0.5" style="color: var(--tg-theme-hint-color)">{{ item.description }}</p>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="text-sm font-bold" style="color: var(--tg-theme-text-color)">{{ formatPrice(item.price) }}</span>
                                    <span
                                        class="flex items-center justify-center w-7 h-7 rounded-full"
                                        style="background: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color); font-size: 18px; font-weight: 300"
                                    >+</span>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="!categoriesWithItems.length && !loading" class="empty-state" style="padding: 48px 0">
                    <div class="empty-state-icon">🍽️</div>
                    <p class="empty-state-title">Menyu hozircha bo'sh</p>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useStoreInfo } from '../../stores/store'
import { useTelegram } from '../../composables/useTelegram'
import { formatPrice } from '../../utils/formatters'

const router = useRouter()
const storeInfo = useStoreInfo()
const { hapticImpact } = useTelegram()

const loading = ref(true)
const menuItems = ref([])
const activeCategory = ref(null)
const categoryRefs = reactive({})

const allCategories = computed(() => storeInfo.categories)

const categoriesWithItems = computed(() => {
    if (!allCategories.value.length) {
        return menuItems.value.length ? [{ id: 'all', name: 'Menyu', items: menuItems.value }] : []
    }
    return allCategories.value
        .map(cat => ({
            ...cat,
            items: menuItems.value.filter(item => item.category_id === cat.id),
        }))
        .filter(cat => cat.items.length > 0)
})

function scrollToCategory(catId) {
    activeCategory.value = catId
    hapticImpact('light')
    const el = categoryRefs[catId]
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
}

function goToItem(item) {
    hapticImpact('light')
    router.push({ name: 'menu-item', params: { slug: item.slug } })
}

onMounted(async () => {
    loading.value = true
    try {
        const data = await storeInfo.fetchCatalogItems({ per_page: 100 })
        menuItems.value = data.items || data.data || []
        if (allCategories.value.length) {
            activeCategory.value = allCategories.value[0].id
        }
    } catch (err) {
        console.error('[DeliveryHome] Load error:', err)
    } finally {
        loading.value = false
    }
})
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
