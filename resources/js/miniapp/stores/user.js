import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useApi } from '../composables/useApi'
import { useTelegram } from '../composables/useTelegram'

export const useUserStore = defineStore('user', () => {
    const { get, post, put, del } = useApi()
    const { userId, firstName, lastName, username, initData } = useTelegram()

    const customer = ref(null)
    const loading = ref(false)
    const error = ref(null)

    // Regions cache
    const regionsCache = ref(null)
    const districtsCache = ref({})

    const isAuthenticated = computed(() => !!initData.value && !!userId.value)
    const fullName = computed(() => {
        if (customer.value?.name) return customer.value.name
        return [firstName.value, lastName.value].filter(Boolean).join(' ')
    })
    const phone = computed(() => customer.value?.phone || '')
    const savedAddresses = computed(() => customer.value?.addresses || [])
    const defaultAddress = computed(() => savedAddresses.value.find(a => a.is_default) || savedAddresses.value[0] || null)

    async function fetchProfile() {
        if (!isAuthenticated.value) return

        loading.value = true
        error.value = null
        try {
            const data = await get('/profile')
            customer.value = data.data?.customer || data.customer || data
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
            return { success: true }
        } catch (err) {
            console.error('[MiniApp] Save address error:', err)
            const msg = err.response?.data?.message
                || (err.response?.data?.errors ? Object.values(err.response.data.errors).flat().join(', ') : null)
                || 'Manzilni saqlashda xatolik'
            return { success: false, error: msg }
        }
    }

    async function deleteAddress(addressId) {
        try {
            await del(`/profile/addresses/${addressId}`)
            if (customer.value?.addresses) {
                customer.value.addresses = customer.value.addresses.filter(a => a.id !== addressId)
            }
            return true
        } catch (err) {
            console.error('[MiniApp] Delete address error:', err)
            return false
        }
    }

    async function setDefaultAddress(addressId) {
        try {
            await put(`/profile/addresses/${addressId}/default`)
            if (customer.value?.addresses) {
                customer.value.addresses = customer.value.addresses.map(a => ({
                    ...a,
                    is_default: a.id === addressId,
                }))
            }
            return true
        } catch (err) {
            console.error('[MiniApp] Set default address error:', err)
            return false
        }
    }

    async function fetchRegions() {
        if (regionsCache.value) return regionsCache.value
        try {
            const data = await get('/regions')
            regionsCache.value = data.data || []
            return regionsCache.value
        } catch (err) {
            console.error('[MiniApp] Fetch regions error:', err)
            return []
        }
    }

    async function fetchDistricts(regionKey) {
        if (districtsCache.value[regionKey]?.length) return districtsCache.value[regionKey]
        try {
            const data = await get(`/regions/${regionKey}/districts`)
            const list = data.data || []
            if (list.length) districtsCache.value[regionKey] = list
            return list
        } catch (err) {
            console.error('[MiniApp] Fetch districts error:', err)
            return []
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
        defaultAddress,
        fetchProfile,
        updateProfile,
        saveAddress,
        deleteAddress,
        setDefaultAddress,
        fetchRegions,
        fetchDistricts,
    }
})
