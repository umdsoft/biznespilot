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
                <h1 class="text-base font-bold" style="color: var(--tg-theme-text-color)">Vaqt tanlash</h1>
            </div>
        </div>

        <div style="padding: 16px">
            <!-- Service info -->
            <div class="rounded-2xl p-4 mb-4" style="background: var(--tg-theme-secondary-bg-color)">
                <p class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">{{ serviceName }}</p>
                <div class="flex items-center gap-3 mt-1">
                    <span class="text-xs" style="color: var(--tg-theme-hint-color)">{{ duration }} daqiqa</span>
                    <span class="text-sm font-bold" style="color: var(--tg-theme-button-color)">{{ formatPrice(servicePrice) }}</span>
                </div>
            </div>

            <!-- Date picker -->
            <h3 class="text-sm font-semibold mb-3" style="color: var(--tg-theme-text-color)">Kunni tanlang</h3>
            <div class="flex overflow-x-auto gap-2 mb-4 no-scrollbar">
                <button
                    v-for="day in availableDays"
                    :key="day.date"
                    @click="selectDate(day.date)"
                    class="shrink-0 flex flex-col items-center rounded-xl px-4 py-3 tap-active"
                    :style="{
                        background: selectedDate === day.date ? 'var(--tg-theme-button-color)' : 'var(--tg-theme-secondary-bg-color)',
                        color: selectedDate === day.date ? 'var(--tg-theme-button-text-color)' : 'var(--tg-theme-text-color)',
                        minWidth: '64px',
                    }"
                >
                    <span class="text-[10px] font-medium" :style="{ opacity: selectedDate === day.date ? 1 : 0.6 }">{{ day.weekday }}</span>
                    <span class="text-lg font-bold" style="line-height: 1.3">{{ day.dayNum }}</span>
                    <span class="text-[10px]" :style="{ opacity: selectedDate === day.date ? 1 : 0.6 }">{{ day.month }}</span>
                </button>
            </div>

            <!-- Time slots -->
            <h3 class="text-sm font-semibold mb-3" style="color: var(--tg-theme-text-color)">Vaqtni tanlang</h3>
            <div v-if="loadingSlots" class="flex gap-2 flex-wrap">
                <div v-for="i in 8" :key="i" class="skeleton" style="width: 72px; height: 40px; border-radius: 10px"></div>
            </div>
            <div v-else-if="slots.length" class="flex flex-wrap gap-2 mb-4">
                <button
                    v-for="slot in slots"
                    :key="slot.time"
                    @click="selectTime(slot)"
                    :disabled="!slot.available"
                    class="rounded-xl px-4 py-2.5 text-sm font-medium tap-active"
                    :style="{
                        background: selectedTime === slot.time ? 'var(--tg-theme-button-color)' : 'var(--tg-theme-secondary-bg-color)',
                        color: selectedTime === slot.time ? 'var(--tg-theme-button-text-color)' : (slot.available ? 'var(--tg-theme-text-color)' : 'var(--tg-theme-hint-color)'),
                        opacity: slot.available ? 1 : 0.4,
                    }"
                >
                    {{ slot.time }}
                </button>
            </div>
            <p v-else class="text-sm mb-4" style="color: var(--tg-theme-hint-color)">Bu kunga bo'sh vaqt yo'q</p>

            <!-- Notes -->
            <h3 class="text-sm font-semibold mb-2" style="color: var(--tg-theme-text-color)">Izoh (ixtiyoriy)</h3>
            <textarea
                v-model="notes"
                placeholder="Qo'shimcha ma'lumot..."
                class="form-input"
                style="min-height: 80px; resize: none"
            ></textarea>
        </div>

        <!-- Bottom bar -->
        <div class="sticky-bottom-bar">
            <button
                @click="confirmBooking"
                :disabled="!canBook || bookingStore.creating"
                class="btn-primary"
            >
                <span v-if="bookingStore.creating">Yuborilmoqda...</span>
                <span v-else>Tasdiqlash</span>
            </button>
            <p v-if="bookingStore.error" class="form-error mt-2 text-center">{{ bookingStore.error }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useBookingStore } from '../../stores/booking'
import { useTelegram } from '../../composables/useTelegram'
import { formatPrice } from '../../utils/formatters'

const router = useRouter()
const route = useRoute()
const bookingStore = useBookingStore()
const { hapticImpact, hapticNotification } = useTelegram()

const serviceId = route.query.service_id
const serviceName = route.query.service_name || 'Xizmat'
const servicePrice = Number(route.query.service_price) || 0
const duration = Number(route.query.duration) || 30
const staffId = route.query.staff_id || null

const selectedDate = ref('')
const selectedTime = ref('')
const notes = ref('')
const slots = ref([])
const loadingSlots = ref(false)

const weekdays = ['Yak', 'Dush', 'Sesh', 'Chor', 'Pay', 'Jum', 'Shan']
const months = ['Yan', 'Fev', 'Mar', 'Apr', 'May', 'Iyun', 'Iyul', 'Avg', 'Sen', 'Okt', 'Noy', 'Dek']

const availableDays = computed(() => {
    const days = []
    const today = new Date()
    for (let i = 0; i < 14; i++) {
        const d = new Date(today)
        d.setDate(d.getDate() + i)
        const yyyy = d.getFullYear()
        const mm = String(d.getMonth() + 1).padStart(2, '0')
        const dd = String(d.getDate()).padStart(2, '0')
        days.push({
            date: `${yyyy}-${mm}-${dd}`,
            weekday: i === 0 ? 'Bugun' : weekdays[d.getDay()],
            dayNum: d.getDate(),
            month: months[d.getMonth()],
        })
    }
    return days
})

const canBook = computed(() => selectedDate.value && selectedTime.value)

function selectDate(date) {
    hapticImpact('light')
    selectedDate.value = date
    selectedTime.value = ''
}

function selectTime(slot) {
    if (!slot.available) return
    hapticImpact('light')
    selectedTime.value = slot.time
}

async function loadSlots() {
    if (!selectedDate.value || !serviceId) return
    loadingSlots.value = true
    slots.value = []
    try {
        const data = await bookingStore.fetchSlots(serviceId, selectedDate.value, staffId)
        slots.value = data.length ? data : generateFallbackSlots()
    } catch {
        slots.value = generateFallbackSlots()
    } finally {
        loadingSlots.value = false
    }
}

function generateFallbackSlots() {
    const result = []
    for (let h = 9; h < 18; h++) {
        result.push({ time: `${String(h).padStart(2, '0')}:00`, available: true })
        if (duration <= 30) {
            result.push({ time: `${String(h).padStart(2, '0')}:30`, available: true })
        }
    }
    return result
}

watch(() => selectedDate.value, () => { if (selectedDate.value) loadSlots() })

async function confirmBooking() {
    if (!canBook.value) return
    hapticImpact('medium')

    const booking = await bookingStore.createBooking({
        service_id: serviceId,
        staff_id: staffId,
        date: selectedDate.value,
        time: selectedTime.value,
        notes: notes.value || undefined,
    })

    if (booking) {
        hapticNotification('success')
        router.replace({ name: 'booking-confirm', query: { booking_id: booking.id } })
    }
}

function goBack() {
    hapticImpact('light')
    router.back()
}

onMounted(() => {
    if (availableDays.value.length) {
        selectedDate.value = availableDays.value[0].date
    }
})
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
