<template>
    <div class="pb-nav">
        <!-- Header -->
        <div class="header-blur sticky top-0 z-30">
            <div style="padding: 12px 16px">
                <div class="flex items-center gap-3">
                    <img v-if="storeInfo.logo" :src="storeInfo.logo" class="h-10 w-10 rounded-xl object-cover" />
                    <div v-else class="flex h-10 w-10 items-center justify-center rounded-xl" style="background: var(--tg-theme-button-color)">
                        <span style="font-size: 20px">💈</span>
                    </div>
                    <div style="flex: 1; min-width: 0">
                        <h1 class="text-base font-bold truncate" style="color: var(--tg-theme-text-color)">{{ storeInfo.name }}</h1>
                        <p v-if="storeInfo.workingHours" class="text-xs truncate" style="color: var(--tg-theme-hint-color)">{{ storeInfo.workingHours }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories -->
        <div v-if="storeInfo.categories.length > 1" style="padding: 8px 0">
            <div class="flex overflow-x-auto gap-2 px-4 no-scrollbar">
                <button
                    v-for="cat in [{ id: 'all', name: 'Barchasi' }, ...storeInfo.categories]"
                    :key="cat.id"
                    @click="activeCategory = cat.id"
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

        <!-- Loading -->
        <div v-if="loading" style="padding: 16px">
            <div v-for="i in 4" :key="i" class="skeleton" style="height: 100px; border-radius: 16px; margin-bottom: 12px"></div>
        </div>

        <!-- Services list -->
        <div v-else style="padding: 0 16px 16px">
            <div v-if="filteredServices.length" class="flex flex-col gap-3">
                <button
                    v-for="service in filteredServices"
                    :key="service.id"
                    @click="goToService(service)"
                    class="flex gap-3 rounded-2xl p-3 tap-active text-left"
                    style="background: var(--tg-theme-secondary-bg-color)"
                >
                    <div class="shrink-0 w-20 h-20 rounded-xl overflow-hidden">
                        <img v-if="service.image || service.image_url" :src="service.image || service.image_url" :alt="service.name" class="w-full h-full object-cover" loading="lazy" />
                        <div v-else class="w-full h-full flex items-center justify-center" style="background: var(--tg-theme-bg-color)">
                            <span style="font-size: 28px">✨</span>
                        </div>
                    </div>
                    <div style="flex: 1; min-width: 0">
                        <p class="text-sm font-semibold line-clamp-1" style="color: var(--tg-theme-text-color)">{{ service.name }}</p>
                        <p v-if="service.description" class="text-xs line-clamp-2 mt-0.5" style="color: var(--tg-theme-hint-color)">{{ service.description }}</p>
                        <div class="flex items-center justify-between mt-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold" style="color: var(--tg-theme-text-color)">{{ formatPrice(service.price) }}</span>
                                <span v-if="service.duration_minutes" class="text-xs" style="color: var(--tg-theme-hint-color)">· {{ service.duration_minutes }} min</span>
                            </div>
                            <span
                                class="flex items-center justify-center rounded-full text-xs font-medium px-3 py-1"
                                style="background: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
                            >Band qilish</span>
                        </div>
                    </div>
                </button>
            </div>

            <!-- Empty -->
            <div v-else class="empty-state" style="padding: 48px 0">
                <div class="empty-state-icon">✨</div>
                <p class="empty-state-title">Xizmatlar hozircha yo'q</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useStoreInfo } from '../../stores/store'
import { useTelegram } from '../../composables/useTelegram'
import { formatPrice } from '../../utils/formatters'

const router = useRouter()
const storeInfo = useStoreInfo()
const { hapticImpact } = useTelegram()

const loading = ref(true)
const services = ref([])
const activeCategory = ref('all')

const filteredServices = computed(() => {
    if (activeCategory.value === 'all') return services.value
    return services.value.filter(s => s.category_id === activeCategory.value)
})

function goToService(service) {
    hapticImpact('light')
    router.push({ name: 'service-detail', params: { slug: service.slug } })
}

onMounted(async () => {
    loading.value = true
    try {
        const data = await storeInfo.fetchCatalogItems({ per_page: 100 })
        services.value = data.items || data.data || []
    } catch (err) {
        console.error('[QueueHome] Load error:', err)
    } finally {
        loading.value = false
    }
})
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
