<template>
    <div class="pb-safe">
        <!-- Header -->
        <div class="header-blur sticky top-0 z-30">
            <div class="flex items-center gap-3" style="padding: 12px 16px">
                <button @click="goBack" class="tap-active flex items-center justify-center w-9 h-9 rounded-full" style="background: var(--tg-theme-secondary-bg-color)">
                    <svg style="width: 20px; height: 20px; color: var(--tg-theme-text-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                    </svg>
                </button>
                <h1 class="text-base font-bold truncate" style="color: var(--tg-theme-text-color)">{{ service?.name || 'Yuklanmoqda...' }}</h1>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" style="padding: 16px">
            <div class="skeleton" style="width: 100%; aspect-ratio: 16/9; border-radius: 16px; margin-bottom: 16px"></div>
            <div class="skeleton" style="height: 24px; width: 60%; border-radius: 8px; margin-bottom: 12px"></div>
            <div class="skeleton" style="height: 80px; border-radius: 12px"></div>
        </div>

        <template v-else-if="service">
            <!-- Image -->
            <div v-if="service.image || service.image_url" class="w-full aspect-[16/9] overflow-hidden" style="background: var(--tg-theme-secondary-bg-color)">
                <img :src="service.image || service.image_url" :alt="service.name" class="w-full h-full object-cover" />
            </div>

            <!-- Info -->
            <div style="padding: 16px">
                <h2 class="text-xl font-bold" style="color: var(--tg-theme-text-color)">{{ service.name }}</h2>

                <div class="flex items-center gap-3 mt-2">
                    <span class="text-lg font-bold" style="color: var(--tg-theme-button-color)">
                        {{ service.pricing_type === 'quote' ? "Kelishilgan narx" : formatPrice(service.base_price || service.price) }}
                    </span>
                    <span v-if="service.pricing_unit" class="text-sm" style="color: var(--tg-theme-hint-color)">/ {{ service.pricing_unit }}</span>
                </div>

                <!-- Attributes -->
                <div class="flex flex-wrap gap-2 mt-3">
                    <span v-if="service.estimated_minutes" class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-full" style="background: var(--tg-theme-secondary-bg-color); color: var(--tg-theme-hint-color)">
                        <svg style="width: 14px; height: 14px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        ~{{ service.estimated_minutes }} min
                    </span>
                    <span v-if="service.pricing_type && service.pricing_type !== 'fixed'" class="inline-flex items-center text-xs px-3 py-1.5 rounded-full" style="background: var(--tg-theme-secondary-bg-color); color: var(--tg-theme-hint-color)">
                        {{ pricingLabel }}
                    </span>
                </div>

                <p v-if="service.description" class="text-sm mt-4" style="color: var(--tg-theme-hint-color); line-height: 1.6">{{ service.description }}</p>
            </div>

            <!-- Masters section -->
            <div v-if="masters.length" style="padding: 0 16px 16px">
                <h3 class="text-sm font-semibold mb-3" style="color: var(--tg-theme-text-color)">Ustalar</h3>
                <div class="flex flex-col gap-2">
                    <button
                        v-for="master in masters"
                        :key="master.id"
                        @click="goToMaster(master)"
                        class="flex items-center gap-3 rounded-xl p-3 tap-active"
                        style="background: var(--tg-theme-secondary-bg-color)"
                    >
                        <div class="shrink-0 w-12 h-12 rounded-full overflow-hidden" style="background: var(--tg-theme-bg-color)">
                            <img v-if="master.photo_url" :src="master.photo_url" :alt="master.name" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center text-lg">👤</div>
                        </div>
                        <div style="flex: 1; min-width: 0">
                            <p class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">{{ master.name }}</p>
                            <p v-if="master.position" class="text-xs" style="color: var(--tg-theme-hint-color)">{{ master.position }}</p>
                            <div v-if="master.specializations?.length" class="flex flex-wrap gap-1 mt-1">
                                <span
                                    v-for="spec in master.specializations.slice(0, 3)"
                                    :key="spec"
                                    class="text-[10px] px-2 py-0.5 rounded-full"
                                    style="background: var(--tg-theme-bg-color); color: var(--tg-theme-hint-color)"
                                >{{ spec }}</span>
                            </div>
                        </div>
                        <svg style="width: 18px; height: 18px; color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- CTA button -->
            <div class="sticky-bottom-bar">
                <button @click="goToRequest" class="btn-primary">
                    So'rov yuborish
                </button>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useStoreInfo } from '../../stores/store'
import { useServiceRequestStore } from '../../stores/serviceRequest'
import { useTelegram } from '../../composables/useTelegram'
import { formatPrice } from '../../utils/formatters'

const props = defineProps({ slug: String })
const router = useRouter()
const route = useRoute()
const storeInfo = useStoreInfo()
const serviceRequestStore = useServiceRequestStore()
const { hapticImpact } = useTelegram()

const loading = ref(true)
const service = ref(null)
const masters = ref([])

const pricingLabel = computed(() =>
    serviceRequestStore.getPricingLabel(service.value?.pricing_type)
)

function goToMaster(master) {
    hapticImpact('light')
    router.push({ name: 'master-profile', params: { id: master.id } })
}

function goToRequest() {
    if (!service.value) return
    hapticImpact('medium')
    router.push({
        name: 'request-form',
        query: {
            service_id: service.value.id,
            service_name: service.value.name,
            service_price: service.value.base_price || service.value.price || 0,
            pricing_type: service.value.pricing_type || 'fixed',
            requires_address: service.value.requires_address ? '1' : '0',
        },
    })
}

function goBack() {
    hapticImpact('light')
    router.back()
}

onMounted(async () => {
    loading.value = true
    try {
        const itemSlug = props.slug || route.params.slug
        const data = await storeInfo.fetchCatalogItem(itemSlug)
        if (data) {
            service.value = data.item || data.data || data
        }
        await serviceRequestStore.fetchMasters(service.value?.id)
        masters.value = serviceRequestStore.mastersList
    } catch (err) {
        console.error('[ServiceInfo] Load error:', err)
    } finally {
        loading.value = false
    }
})
</script>
