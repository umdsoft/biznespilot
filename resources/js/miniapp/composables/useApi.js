import axios from 'axios'
import { useTelegram } from './useTelegram'

let apiInstance = null

function getApiBaseUrl() {
    const el = document.getElementById('miniapp')
    return el?.dataset?.apiUrl || '/api/miniapp/v1'
}

function getStoreSlug() {
    const el = document.getElementById('miniapp')
    return el?.dataset?.storeSlug || ''
}

export function useApi() {
    if (!apiInstance) {
        // apiUrl allaqachon slug ni o'z ichiga oladi (blade dan keladi)
        const baseURL = getApiBaseUrl()

        apiInstance = axios.create({
            baseURL,
            timeout: 15000,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        })

        // Request interceptor: attach Telegram initData as Authorization header
        apiInstance.interceptors.request.use((config) => {
            const { initData } = useTelegram()
            if (initData.value) {
                config.headers['Authorization'] = `tma ${initData.value}`
            }
            return config
        })

        // Response interceptor: handle errors
        apiInstance.interceptors.response.use(
            (response) => response,
            (error) => {
                const status = error.response?.status

                if (status === 401) {
                    console.warn('[MiniApp] Avtorizatsiya xatosi — initData yaroqsiz')
                }

                if (status === 404) {
                    console.warn('[MiniApp] Resurs topilmadi:', error.config?.url)
                }

                if (status === 422) {
                    const errors = error.response?.data?.errors
                    if (errors) {
                        error.validationErrors = errors
                    }
                }

                if (status === 429) {
                    console.warn('[MiniApp] So\'rovlar limiti oshib ketdi')
                }

                if (status >= 500) {
                    console.error('[MiniApp] Server xatosi:', status)
                }

                return Promise.reject(error)
            }
        )
    }

    const api = apiInstance

    // Convenience methods
    async function get(url, params = {}) {
        const response = await api.get(url, { params })
        return response.data
    }

    async function post(url, data = {}) {
        const response = await api.post(url, data)
        return response.data
    }

    async function put(url, data = {}) {
        const response = await api.put(url, data)
        return response.data
    }

    async function del(url) {
        const response = await api.delete(url)
        return response.data
    }

    return {
        api,
        get,
        post,
        put,
        del,
        getStoreSlug,
    }
}
