import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useApi } from '../composables/useApi'

export const useBookingStore = defineStore('booking', () => {
    const { get, post } = useApi()

    const bookings = ref([])
    const currentBooking = ref(null)
    const availableSlots = ref([])
    const staffList = ref([])
    const loading = ref(false)
    const creating = ref(false)
    const error = ref(null)
    const hasMore = ref(false)
    const page = ref(1)

    const activeBookings = computed(() =>
        bookings.value.filter(b => ['pending', 'confirmed', 'in_progress'].includes(b.status))
    )

    const pastBookings = computed(() =>
        bookings.value.filter(b => ['completed', 'cancelled', 'no_show'].includes(b.status))
    )

    async function fetchBookings(reset = false) {
        if (reset) {
            page.value = 1
            bookings.value = []
        }
        loading.value = true
        error.value = null
        try {
            const data = await get('/bookings', { page: page.value })
            const items = data.bookings || data.data || []
            if (reset) {
                bookings.value = items
            } else {
                bookings.value.push(...items)
            }
            hasMore.value = data.has_more || (data.meta?.last_page > page.value)
            page.value++
        } catch (err) {
            error.value = "Bandlovlarni yuklashda xatolik"
            console.error('[MiniApp] Bookings fetch error:', err)
        } finally {
            loading.value = false
        }
    }

    async function fetchBooking(id) {
        loading.value = true
        error.value = null
        try {
            const data = await get(`/bookings/${id}`)
            currentBooking.value = data.data || data.booking || data
            return currentBooking.value
        } catch (err) {
            error.value = "Bandlov topilmadi"
            console.error('[MiniApp] Booking fetch error:', err)
            return null
        } finally {
            loading.value = false
        }
    }

    async function createBooking(bookingData) {
        creating.value = true
        error.value = null
        try {
            const data = await post('/bookings', bookingData)
            const booking = data.data?.booking || data.booking || data
            currentBooking.value = booking
            return booking
        } catch (err) {
            if (err.validationErrors) {
                error.value = Object.values(err.validationErrors).flat().join(', ')
            } else {
                error.value = err.response?.data?.message || "Bandlov yaratishda xatolik"
            }
            console.error('[MiniApp] Create booking error:', err)
            return null
        } finally {
            creating.value = false
        }
    }

    async function cancelBooking(id) {
        loading.value = true
        error.value = null
        try {
            const data = await post(`/bookings/${id}/cancel`)
            if (currentBooking.value?.id === id) {
                currentBooking.value = data.data || data.booking || { ...currentBooking.value, status: 'cancelled' }
            }
            const idx = bookings.value.findIndex(b => b.id === id)
            if (idx > -1) bookings.value[idx].status = 'cancelled'
            return true
        } catch (err) {
            error.value = err.response?.data?.message || "Bekor qilishda xatolik"
            console.error('[MiniApp] Cancel booking error:', err)
            return false
        } finally {
            loading.value = false
        }
    }

    async function fetchSlots(serviceId, date, staffId = null) {
        try {
            const params = { service_id: serviceId, date }
            if (staffId) params.staff_id = staffId
            const data = await get('/bookings/slots', params)
            availableSlots.value = data.slots || data.data || []
            return availableSlots.value
        } catch (err) {
            console.error('[MiniApp] Slots fetch error:', err)
            availableSlots.value = []
            return []
        }
    }

    async function fetchStaff(serviceId = null) {
        try {
            const params = serviceId ? { service_id: serviceId } : {}
            const data = await get('/staff', params)
            staffList.value = data.staff || data.data || []
            return staffList.value
        } catch (err) {
            console.error('[MiniApp] Staff fetch error:', err)
            staffList.value = []
            return []
        }
    }

    async function fetchStaffMember(staffId) {
        try {
            const data = await get(`/staff/${staffId}`)
            return data.data || data.staff || data
        } catch (err) {
            console.error('[MiniApp] Staff member fetch error:', err)
            return null
        }
    }

    function getStatusLabel(status) {
        const labels = {
            pending: 'Kutilmoqda',
            confirmed: 'Tasdiqlandi',
            in_progress: 'Jarayonda',
            completed: 'Yakunlandi',
            cancelled: 'Bekor qilindi',
            no_show: 'Kelmadi',
        }
        return labels[status] || status
    }

    function getStatusColor(status) {
        const map = {
            pending: '#F59E0B',
            confirmed: '#3B82F6',
            in_progress: '#8B5CF6',
            completed: '#10B981',
            cancelled: '#EF4444',
            no_show: '#6B7280',
        }
        return map[status] || '#6B7280'
    }

    function getStatusBg(status) {
        const map = {
            pending: '#FEF3C7',
            confirmed: '#DBEAFE',
            in_progress: '#EDE9FE',
            completed: '#D1FAE5',
            cancelled: '#FEE2E2',
            no_show: '#F3F4F6',
        }
        return map[status] || '#F3F4F6'
    }

    return {
        bookings,
        currentBooking,
        availableSlots,
        staffList,
        loading,
        creating,
        error,
        hasMore,
        activeBookings,
        pastBookings,
        fetchBookings,
        fetchBooking,
        createBooking,
        cancelBooking,
        fetchSlots,
        fetchStaff,
        fetchStaffMember,
        getStatusLabel,
        getStatusColor,
        getStatusBg,
    }
})
