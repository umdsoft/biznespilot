<template>
    <div class="pb-nav">
        <!-- Header -->
        <div class="header-blur sticky top-0 z-30">
            <div style="padding: 12px 16px">
                <h1 style="font-size: 22px; font-weight: 700; color: var(--tg-theme-text-color)">Bandlovlar</h1>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="bookingStore.loading && bookingStore.bookings.length === 0" style="padding: 16px">
            <div v-for="i in 3" :key="i" class="skeleton" style="height: 90px; border-radius: 14px; margin-bottom: 12px"></div>
        </div>

        <!-- Empty -->
        <div v-else-if="bookingStore.bookings.length === 0" class="empty-state" style="padding: 64px 20px">
            <div class="empty-state-icon">📅</div>
            <p class="empty-state-title">Bandlovlar yo'q</p>
            <p class="empty-state-subtitle">Xizmat tanlab band qiling</p>
            <button @click="goHome" class="btn-primary" style="margin-top: 16px; max-width: 200px">Xizmatlarga o'tish</button>
        </div>

        <!-- Bookings list -->
        <div v-else style="padding: 0 16px">
            <!-- Active bookings -->
            <div v-if="bookingStore.activeBookings.length" style="margin-bottom: 24px">
                <h2 class="text-xs font-semibold uppercase tracking-wide mb-3" style="color: var(--tg-theme-hint-color)">Faol bandlovlar</h2>
                <div class="flex flex-col gap-3">
                    <button
                        v-for="booking in bookingStore.activeBookings"
                        :key="booking.id"
                        @click="goToBooking(booking.id)"
                        class="w-full text-left tap-active rounded-2xl p-4"
                        style="background: var(--tg-theme-secondary-bg-color)"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                                {{ booking.service_name || booking.bookable?.name || 'Xizmat' }}
                            </span>
                            <span
                                class="text-xs font-medium px-2.5 py-1 rounded-full"
                                :style="{ background: bookingStore.getStatusBg(booking.status), color: bookingStore.getStatusColor(booking.status) }"
                            >{{ bookingStore.getStatusLabel(booking.status) }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-1.5" style="color: var(--tg-theme-hint-color)">
                                <svg style="width: 14px; height: 14px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                                </svg>
                                <span class="text-xs">{{ formatBookingDate(booking.booked_at) }}</span>
                            </div>
                            <div class="flex items-center gap-1.5" style="color: var(--tg-theme-hint-color)">
                                <svg style="width: 14px; height: 14px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-xs">{{ formatBookingTime(booking.booked_at) }}</span>
                            </div>
                        </div>
                        <div v-if="booking.staff" class="flex items-center gap-2 mt-2">
                            <div class="w-5 h-5 rounded-full overflow-hidden shrink-0" style="background: var(--tg-theme-bg-color)">
                                <img v-if="booking.staff.photo_url" :src="booking.staff.photo_url" class="w-full h-full object-cover" />
                                <span v-else class="flex w-full h-full items-center justify-center text-[10px]">👤</span>
                            </div>
                            <span class="text-xs" style="color: var(--tg-theme-hint-color)">{{ booking.staff.name }}</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Past bookings -->
            <div v-if="bookingStore.pastBookings.length">
                <h2 class="text-xs font-semibold uppercase tracking-wide mb-3" style="color: var(--tg-theme-hint-color)">O'tgan bandlovlar</h2>
                <div class="flex flex-col gap-3">
                    <button
                        v-for="booking in bookingStore.pastBookings"
                        :key="booking.id"
                        @click="goToBooking(booking.id)"
                        class="w-full text-left tap-active rounded-2xl p-4"
                        style="background: var(--tg-theme-secondary-bg-color)"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                                {{ booking.service_name || booking.bookable?.name || 'Xizmat' }}
                            </span>
                            <span
                                class="text-xs font-medium px-2.5 py-1 rounded-full"
                                :style="{ background: bookingStore.getStatusBg(booking.status), color: bookingStore.getStatusColor(booking.status) }"
                            >{{ bookingStore.getStatusLabel(booking.status) }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-xs" style="color: var(--tg-theme-hint-color)">{{ formatBookingDate(booking.booked_at) }}</span>
                            <span class="text-xs" style="color: var(--tg-theme-hint-color)">{{ formatBookingTime(booking.booked_at) }}</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Load more -->
            <div v-if="bookingStore.hasMore" class="flex justify-center" style="padding: 24px 0">
                <div v-if="bookingStore.loading" class="skeleton" style="width: 24px; height: 24px; border-radius: 50%"></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useBookingStore } from '../../stores/booking'
import { useTelegram } from '../../composables/useTelegram'

const router = useRouter()
const bookingStore = useBookingStore()
const { hapticImpact } = useTelegram()

function formatBookingDate(dateStr) {
    if (!dateStr) return '—'
    const d = new Date(dateStr)
    return d.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short' })
}

function formatBookingTime(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    return d.toLocaleTimeString('uz-UZ', { hour: '2-digit', minute: '2-digit' })
}

function goToBooking(id) {
    hapticImpact('light')
    router.push({ name: 'booking-detail', params: { id } })
}

function goHome() {
    hapticImpact('light')
    router.push({ name: 'home' })
}

onMounted(() => {
    bookingStore.fetchBookings(true)
})
</script>
