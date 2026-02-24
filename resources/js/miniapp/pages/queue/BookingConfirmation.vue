<template>
    <div class="pb-safe">
        <div style="padding: 48px 24px; text-align: center">
            <!-- Success icon -->
            <div class="flex items-center justify-center mx-auto mb-4" style="width: 80px; height: 80px; border-radius: 50%; background: color-mix(in srgb, var(--tg-theme-button-color) 12%, var(--tg-theme-bg-color))">
                <svg style="width: 40px; height: 40px; color: var(--tg-theme-button-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <h1 class="text-xl font-bold mb-2" style="color: var(--tg-theme-text-color)">Bandlov qabul qilindi!</h1>
            <p class="text-sm mb-6" style="color: var(--tg-theme-hint-color)">Tez orada tasdiqlanadi</p>

            <!-- Booking details -->
            <div v-if="booking" class="rounded-2xl p-4 text-left mb-6" style="background: var(--tg-theme-secondary-bg-color)">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm" style="color: var(--tg-theme-hint-color)">Xizmat</span>
                    <span class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">{{ booking.service_name || booking.bookable?.name || '—' }}</span>
                </div>
                <div v-if="booking.staff" class="flex items-center justify-between mb-3">
                    <span class="text-sm" style="color: var(--tg-theme-hint-color)">Mutaxassis</span>
                    <span class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">{{ booking.staff.name }}</span>
                </div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm" style="color: var(--tg-theme-hint-color)">Sana</span>
                    <span class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">{{ formatBookingDate(booking.booked_at) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm" style="color: var(--tg-theme-hint-color)">Vaqt</span>
                    <span class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">{{ formatBookingTime(booking.booked_at) }}</span>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <button @click="goToBooking" class="btn-primary">Bandlovni ko'rish</button>
                <button @click="goHome" class="btn-ghost">Bosh sahifaga qaytish</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useBookingStore } from '../../stores/booking'
import { useTelegram } from '../../composables/useTelegram'

const router = useRouter()
const route = useRoute()
const bookingStore = useBookingStore()
const { hapticNotification } = useTelegram()

const booking = ref(null)

function formatBookingDate(dateStr) {
    if (!dateStr) return '—'
    const d = new Date(dateStr)
    return d.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'long', year: 'numeric' })
}

function formatBookingTime(dateStr) {
    if (!dateStr) return '—'
    const d = new Date(dateStr)
    return d.toLocaleTimeString('uz-UZ', { hour: '2-digit', minute: '2-digit' })
}

function goToBooking() {
    if (booking.value?.id) {
        router.replace({ name: 'booking-detail', params: { id: booking.value.id } })
    } else {
        router.replace({ name: 'my-bookings' })
    }
}

function goHome() {
    router.replace({ name: 'home' })
}

onMounted(async () => {
    hapticNotification('success')
    const bookingId = route.query.booking_id
    if (bookingId) {
        booking.value = await bookingStore.fetchBooking(bookingId)
    } else if (bookingStore.currentBooking) {
        booking.value = bookingStore.currentBooking
    }
})
</script>
