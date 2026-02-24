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
            <div class="skeleton" style="height: 16px; width: 100%; border-radius: 8px"></div>
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
                    <span class="text-lg font-bold" style="color: var(--tg-theme-button-color)">{{ formatPrice(service.price) }}</span>
                    <span v-if="service.compare_price" class="text-sm line-through" style="color: var(--tg-theme-hint-color)">{{ formatPrice(service.compare_price) }}</span>
                </div>

                <div v-if="service.duration_minutes" class="flex items-center gap-2 mt-3" style="color: var(--tg-theme-hint-color)">
                    <svg style="width: 16px; height: 16px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm">{{ service.duration_minutes }} daqiqa</span>
                </div>

                <p v-if="service.description" class="text-sm mt-3" style="color: var(--tg-theme-hint-color); line-height: 1.6">{{ service.description }}</p>
            </div>

            <!-- Staff section -->
            <div v-if="staff.length" style="padding: 0 16px 16px">
                <h3 class="text-sm font-semibold mb-3" style="color: var(--tg-theme-text-color)">Mutaxassislar</h3>
                <div class="flex flex-col gap-2">
                    <button
                        v-for="member in staff"
                        :key="member.id"
                        @click="selectStaff(member)"
                        class="flex items-center gap-3 rounded-xl p-3 tap-active"
                        :style="{
                            background: selectedStaffId === member.id ? 'color-mix(in srgb, var(--tg-theme-button-color) 10%, var(--tg-theme-secondary-bg-color))' : 'var(--tg-theme-secondary-bg-color)',
                            border: selectedStaffId === member.id ? '1.5px solid var(--tg-theme-button-color)' : '1.5px solid transparent',
                        }"
                    >
                        <div class="shrink-0 w-12 h-12 rounded-full overflow-hidden" style="background: var(--tg-theme-bg-color)">
                            <img v-if="member.photo_url" :src="member.photo_url" :alt="member.name" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center text-lg">👤</div>
                        </div>
                        <div style="flex: 1; min-width: 0">
                            <p class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">{{ member.name }}</p>
                            <p v-if="member.position" class="text-xs" style="color: var(--tg-theme-hint-color)">{{ member.position }}</p>
                        </div>
                        <div v-if="selectedStaffId === member.id" class="w-6 h-6 rounded-full flex items-center justify-center" style="background: var(--tg-theme-button-color)">
                            <svg style="width: 14px; height: 14px; color: var(--tg-theme-button-text-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                        </div>
                    </button>
                </div>
                <button
                    v-if="selectedStaffId"
                    @click="selectedStaffId = null"
                    class="text-xs mt-2 tap-active"
                    style="color: var(--tg-theme-hint-color)"
                >Istalgan mutaxassis</button>
            </div>

            <!-- Book button -->
            <div class="sticky-bottom-bar">
                <button @click="goToBooking" class="btn-primary">
                    Band qilish — {{ formatPrice(service.price) }}
                </button>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useStoreInfo } from '../../stores/store'
import { useBookingStore } from '../../stores/booking'
import { useTelegram } from '../../composables/useTelegram'
import { formatPrice } from '../../utils/formatters'

const props = defineProps({ slug: String })
const router = useRouter()
const route = useRoute()
const storeInfo = useStoreInfo()
const bookingStore = useBookingStore()
const { hapticImpact } = useTelegram()

const loading = ref(true)
const service = ref(null)
const staff = ref([])
const selectedStaffId = ref(null)

function selectStaff(member) {
    hapticImpact('light')
    selectedStaffId.value = selectedStaffId.value === member.id ? null : member.id
}

function goToBooking() {
    if (!service.value) return
    hapticImpact('medium')
    router.push({
        name: 'booking-form',
        query: {
            service_id: service.value.id,
            service_name: service.value.name,
            service_price: service.value.price,
            duration: service.value.duration_minutes || 30,
            ...(selectedStaffId.value ? { staff_id: selectedStaffId.value } : {}),
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
        if (service.value?.requires_staff !== false) {
            await bookingStore.fetchStaff(service.value?.id)
            staff.value = bookingStore.staffList
        }
    } catch (err) {
        console.error('[ServiceDetail] Load error:', err)
    } finally {
        loading.value = false
    }
})
</script>
