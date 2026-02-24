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
                <h1 class="text-base font-bold" style="color: var(--tg-theme-text-color)">Bandlov tafsiloti</h1>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" style="padding: 16px">
            <div class="skeleton" style="height: 100px; border-radius: 16px; margin-bottom: 16px"></div>
            <div class="skeleton" style="height: 200px; border-radius: 16px"></div>
        </div>

        <template v-else-if="booking">
            <!-- Status card -->
            <div style="padding: 16px">
                <div class="rounded-2xl p-4" style="background: var(--tg-theme-secondary-bg-color)">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                            {{ bookingStore.getStatusLabel(booking.status) }}
                        </span>
                        <span
                            class="text-xs font-medium px-2.5 py-1 rounded-full"
                            :style="{ background: bookingStore.getStatusBg(booking.status), color: bookingStore.getStatusColor(booking.status) }"
                        >{{ bookingStore.getStatusLabel(booking.status) }}</span>
                    </div>

                    <!-- Progress steps -->
                    <div class="flex gap-1.5 mb-3">
                        <div
                            v-for="(step, i) in bookingSteps"
                            :key="i"
                            class="h-1 rounded-full"
                            style="flex: 1"
                            :style="{ background: i <= currentStepIndex ? 'var(--tg-theme-button-color)' : 'var(--color-border)' }"
                        ></div>
                    </div>
                    <div class="flex justify-between">
                        <div
                            v-for="(step, i) in bookingSteps"
                            :key="i"
                            class="flex flex-col items-center"
                            :style="{ opacity: i <= currentStepIndex ? 1 : 0.4 }"
                        >
                            <span style="font-size: 18px; margin-bottom: 2px">{{ step.icon }}</span>
                            <span class="text-[10px] font-medium" style="color: var(--tg-theme-hint-color)">{{ step.label }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking info -->
            <div style="padding: 0 16px 16px">
                <div class="rounded-2xl p-4" style="background: var(--tg-theme-secondary-bg-color)">
                    <h3 class="text-sm font-semibold mb-3" style="color: var(--tg-theme-text-color)">Ma'lumotlar</h3>

                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm" style="color: var(--tg-theme-hint-color)">Xizmat</span>
                            <span class="text-sm font-medium" style="color: var(--tg-theme-text-color)">{{ booking.service_name || booking.bookable?.name || '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm" style="color: var(--tg-theme-hint-color)">Sana</span>
                            <span class="text-sm font-medium" style="color: var(--tg-theme-text-color)">{{ formatDate(booking.booked_at) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm" style="color: var(--tg-theme-hint-color)">Vaqt</span>
                            <span class="text-sm font-medium" style="color: var(--tg-theme-text-color)">{{ formatTime(booking.booked_at) }}{{ booking.ends_at ? ' — ' + formatTime(booking.ends_at) : '' }}</span>
                        </div>
                        <div v-if="booking.staff" class="flex items-center justify-between">
                            <span class="text-sm" style="color: var(--tg-theme-hint-color)">Mutaxassis</span>
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full overflow-hidden shrink-0" style="background: var(--tg-theme-bg-color)">
                                    <img v-if="booking.staff.photo_url" :src="booking.staff.photo_url" class="w-full h-full object-cover" />
                                    <span v-else class="flex w-full h-full items-center justify-center text-xs">👤</span>
                                </div>
                                <span class="text-sm font-medium" style="color: var(--tg-theme-text-color)">{{ booking.staff.name }}</span>
                            </div>
                        </div>
                        <div v-if="booking.notes" class="flex items-start justify-between">
                            <span class="text-sm shrink-0" style="color: var(--tg-theme-hint-color)">Izoh</span>
                            <span class="text-sm text-right ml-4" style="color: var(--tg-theme-text-color)">{{ booking.notes }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cancel button -->
            <div v-if="canCancel" style="padding: 0 16px 24px">
                <button @click="handleCancel" class="w-full text-center py-3 rounded-xl text-sm font-medium" style="color: var(--color-error); background: var(--tg-theme-secondary-bg-color)">
                    Bandlovni bekor qilish
                </button>
            </div>

            <!-- Created at -->
            <div style="padding: 0 16px 24px">
                <p class="text-xs" style="color: var(--tg-theme-hint-color)">
                    Yaratilgan: {{ formatDateTime(booking.created_at) }}
                </p>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useBookingStore } from '../../stores/booking'
import { useTelegram } from '../../composables/useTelegram'

const props = defineProps({ id: String })
const router = useRouter()
const route = useRoute()
const bookingStore = useBookingStore()
const { hapticImpact, showConfirm, hapticNotification } = useTelegram()

const loading = ref(true)
const booking = ref(null)

const bookingSteps = [
    { key: 'pending', icon: '📋', label: 'Kutish' },
    { key: 'confirmed', icon: '✅', label: 'Tasdiqlandi' },
    { key: 'in_progress', icon: '⏳', label: 'Jarayonda' },
    { key: 'completed', icon: '🎉', label: 'Yakunlandi' },
]

const currentStepIndex = computed(() => {
    if (!booking.value) return -1
    const idx = bookingSteps.findIndex(s => s.key === booking.value.status)
    return idx >= 0 ? idx : -1
})

const canCancel = computed(() =>
    booking.value && ['pending', 'confirmed'].includes(booking.value.status)
)

function formatDate(dateStr) {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleDateString('uz-UZ', { day: 'numeric', month: 'long', year: 'numeric' })
}

function formatTime(dateStr) {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleTimeString('uz-UZ', { hour: '2-digit', minute: '2-digit' })
}

function formatDateTime(dateStr) {
    if (!dateStr) return ''
    return new Date(dateStr).toLocaleString('uz-UZ', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function handleCancel() {
    const confirmed = await showConfirm('Bandlovni bekor qilmoqchimisiz?')
    if (!confirmed) return
    hapticImpact('medium')
    const success = await bookingStore.cancelBooking(booking.value.id)
    if (success) {
        hapticNotification('success')
        booking.value = { ...booking.value, status: 'cancelled' }
    }
}

function goBack() {
    hapticImpact('light')
    router.back()
}

onMounted(async () => {
    loading.value = true
    try {
        const bookingId = props.id || route.params.id
        booking.value = await bookingStore.fetchBooking(bookingId)
    } catch (err) {
        console.error('[BookingDetail] Load error:', err)
    } finally {
        loading.value = false
    }
})
</script>
