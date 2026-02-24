import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useApi } from '../composables/useApi'

export const useServiceRequestStore = defineStore('serviceRequest', () => {
    const { get, post } = useApi()

    const requests = ref([])
    const currentRequest = ref(null)
    const mastersList = ref([])
    const loading = ref(false)
    const creating = ref(false)
    const error = ref(null)
    const hasMore = ref(false)
    const page = ref(1)

    const activeRequests = computed(() =>
        requests.value.filter(r => ['pending', 'accepted', 'in_progress'].includes(r.status))
    )

    const pastRequests = computed(() =>
        requests.value.filter(r => ['completed', 'cancelled', 'rejected'].includes(r.status))
    )

    async function fetchRequests(reset = false) {
        if (reset) {
            page.value = 1
            requests.value = []
        }
        loading.value = true
        error.value = null
        try {
            const data = await get('/service-requests', { page: page.value })
            const items = data.requests || data.data || []
            if (reset) {
                requests.value = items
            } else {
                requests.value.push(...items)
            }
            hasMore.value = data.has_more || (data.meta?.last_page > page.value)
            page.value++
        } catch (err) {
            error.value = "So'rovlarni yuklashda xatolik"
            console.error('[MiniApp] Service requests fetch error:', err)
        } finally {
            loading.value = false
        }
    }

    async function fetchRequest(id) {
        loading.value = true
        error.value = null
        try {
            const data = await get(`/service-requests/${id}`)
            currentRequest.value = data.data || data.request || data
            return currentRequest.value
        } catch (err) {
            error.value = "So'rov topilmadi"
            console.error('[MiniApp] Service request fetch error:', err)
            return null
        } finally {
            loading.value = false
        }
    }

    async function createRequest(formData) {
        creating.value = true
        error.value = null
        try {
            // FormData supports file uploads
            const data = await post('/service-requests', formData)
            const request = data.data?.request || data.request || data
            currentRequest.value = request
            return request
        } catch (err) {
            if (err.validationErrors) {
                error.value = Object.values(err.validationErrors).flat().join(', ')
            } else {
                error.value = err.response?.data?.message || "So'rov yaratishda xatolik"
            }
            console.error('[MiniApp] Create service request error:', err)
            return null
        } finally {
            creating.value = false
        }
    }

    async function cancelRequest(id) {
        loading.value = true
        error.value = null
        try {
            const data = await post(`/service-requests/${id}/cancel`)
            if (currentRequest.value?.id === id) {
                currentRequest.value = data.data || data.request || { ...currentRequest.value, status: 'cancelled' }
            }
            const idx = requests.value.findIndex(r => r.id === id)
            if (idx > -1) requests.value[idx].status = 'cancelled'
            return true
        } catch (err) {
            error.value = err.response?.data?.message || "Bekor qilishda xatolik"
            console.error('[MiniApp] Cancel service request error:', err)
            return false
        } finally {
            loading.value = false
        }
    }

    async function fetchMasters(serviceId = null) {
        try {
            const params = serviceId ? { service_id: serviceId } : {}
            const data = await get('/masters', params)
            mastersList.value = data.masters || data.data || []
            return mastersList.value
        } catch (err) {
            console.error('[MiniApp] Masters fetch error:', err)
            mastersList.value = []
            return []
        }
    }

    async function fetchMaster(id) {
        try {
            const data = await get(`/masters/${id}`)
            return data.data || data.master || data
        } catch (err) {
            console.error('[MiniApp] Master fetch error:', err)
            return null
        }
    }

    function getStatusLabel(status) {
        const labels = {
            pending: 'Kutilmoqda',
            accepted: 'Qabul qilindi',
            in_progress: 'Bajarilmoqda',
            completed: 'Yakunlandi',
            cancelled: 'Bekor qilindi',
            rejected: 'Rad etildi',
        }
        return labels[status] || status
    }

    function getStatusColor(status) {
        const map = {
            pending: '#F59E0B',
            accepted: '#3B82F6',
            in_progress: '#8B5CF6',
            completed: '#10B981',
            cancelled: '#EF4444',
            rejected: '#6B7280',
        }
        return map[status] || '#6B7280'
    }

    function getStatusBg(status) {
        const map = {
            pending: '#FEF3C7',
            accepted: '#DBEAFE',
            in_progress: '#EDE9FE',
            completed: '#D1FAE5',
            cancelled: '#FEE2E2',
            rejected: '#F3F4F6',
        }
        return map[status] || '#F3F4F6'
    }

    function getPricingLabel(type) {
        const labels = {
            fixed: 'Belgilangan narx',
            hourly: 'Soatlik',
            per_unit: 'Birlik uchun',
            quote: 'Kelishilgan narx',
        }
        return labels[type] || type
    }

    return {
        requests,
        currentRequest,
        mastersList,
        loading,
        creating,
        error,
        hasMore,
        activeRequests,
        pastRequests,
        fetchRequests,
        fetchRequest,
        createRequest,
        cancelRequest,
        fetchMasters,
        fetchMaster,
        getStatusLabel,
        getStatusColor,
        getStatusBg,
        getPricingLabel,
    }
})
