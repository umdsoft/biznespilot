import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useApi } from '../composables/useApi'
import { useTelegram } from '../composables/useTelegram'

export const useUserStore = defineStore('user', () => {
    const { get, post, put } = useApi()
    const { userId, firstName, lastName, username, initData } = useTelegram()

    const customer = ref(null)
    const loading = ref(false)
    const error = ref(null)

    const isAuthenticated = computed(() => !!initData.value && !!userId.value)
    const fullName = computed(() => {
        if (customer.value?.name) return customer.value.name
        return [firstName.value, lastName.value].filter(Boolean).join(' ')
    })
    const phone = computed(() => customer.value?.phone || '')
    const savedAddresses = computed(() => customer.value?.addresses || [])

    async function fetchProfile() {
        if (!isAuthenticated.value) return

        loading.value = true
        error.value = null
        try {
            const data = await get('/profile')
            customer.value = data.customer || data
        } catch (err) {
            if (err.response?.status !== 401) {
                error.value = "Profil ma'lumotlarini yuklashda xatolik"
            }
        } finally {
            loading.value = false
        }
    }

    async function updateProfile(profileData) {
        loading.value = true
        error.value = null
        try {
            const data = await put('/profile', profileData)
            customer.value = data.customer || data
            return true
        } catch (err) {
            error.value = err.response?.data?.message || "Profilni yangilashda xatolik"
            return false
        } finally {
            loading.value = false
        }
    }

    async function saveAddress(address) {
        try {
            const data = await post('/profile/addresses', address)
            if (customer.value) {
                customer.value.addresses = data.addresses || []
            }
            return true
        } catch (err) {
            console.error('[MiniApp] Save address error:', err)
            return false
        }
    }

    return {
        customer,
        loading,
        error,
        isAuthenticated,
        fullName,
        phone,
        savedAddresses,
        fetchProfile,
        updateProfile,
        saveAddress,
    }
})
