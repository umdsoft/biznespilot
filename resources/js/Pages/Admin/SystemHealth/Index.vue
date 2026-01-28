<template>
    <AdminLayout :title="t('admin.system_health.title')">
        <div class="py-6">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Compact Header -->
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            {{ t('admin.system_health.title') }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ t('admin.system_health.subtitle') }}
                        </p>
                    </div>
                    <button
                        @click="checkHealth"
                        :disabled="loading"
                        class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors text-sm font-medium disabled:opacity-50"
                    >
                        <svg :class="['w-4 h-4 mr-2', loading ? 'animate-spin' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Qayta tekshirish
                    </button>
                </div>

                <!-- Overall Health Status - Compact -->
                <div class="mb-6">
                    <div
                        class="rounded-lg p-4 border"
                        :class="getOverallStatusClass()"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div
                                    class="w-10 h-10 rounded-lg flex items-center justify-center mr-3"
                                    :class="getOverallIconClass()"
                                >
                                    <svg v-if="health.overall?.status === 'healthy'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <svg v-else-if="health.overall?.status === 'warning'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold" :class="getOverallTextClass()">
                                        {{ getOverallStatusText() }}
                                    </h3>
                                    <p class="text-sm" :class="getOverallSubtextClass()">
                                        {{ health.overall?.percentage || 0 }}% komponentlar sog'lom
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Oxirgi tekshirish</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ lastChecked }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Health Checks Grid - Compact -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <!-- Database -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <div class="w-9 h-9 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Ma'lumotlar bazasi</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">MySQL ulanish</p>
                                </div>
                            </div>
                            <span
                                class="px-2 py-1 text-xs font-medium rounded-full"
                                :class="getStatusBadgeClass(health.database?.status)"
                            >
                                {{ getStatusLabel(health.database?.status) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ health.database?.message || 'Tekshirilmoqda...' }}</p>
                    </div>

                    <!-- Storage -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <div class="w-9 h-9 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Saqlash joyi</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Disk maydoni</p>
                                </div>
                            </div>
                            <span
                                class="px-2 py-1 text-xs font-medium rounded-full"
                                :class="getStatusBadgeClass(health.storage?.status)"
                            >
                                {{ getStatusLabel(health.storage?.status) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ health.storage?.message || 'Tekshirilmoqda...' }}</p>
                        <div v-if="health.storage?.used" class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                            <div
                                class="h-1.5 rounded-full transition-all duration-300"
                                :class="health.storage?.used > 90 ? 'bg-red-500' : health.storage?.used > 70 ? 'bg-yellow-500' : 'bg-emerald-500'"
                                :style="`width: ${health.storage?.used}%`"
                            ></div>
                        </div>
                    </div>

                    <!-- Cache -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <div class="w-9 h-9 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Kesh tizimi</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Redis/File cache</p>
                                </div>
                            </div>
                            <span
                                class="px-2 py-1 text-xs font-medium rounded-full"
                                :class="getStatusBadgeClass(health.cache?.status)"
                            >
                                {{ getStatusLabel(health.cache?.status) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ health.cache?.message || 'Tekshirilmoqda...' }}</p>
                    </div>

                    <!-- Queue -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <div class="w-9 h-9 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Navbat tizimi</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Background jobs</p>
                                </div>
                            </div>
                            <span
                                class="px-2 py-1 text-xs font-medium rounded-full"
                                :class="getStatusBadgeClass(health.queue?.status)"
                            >
                                {{ getStatusLabel(health.queue?.status) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ health.queue?.message || 'Tekshirilmoqda...' }}</p>
                    </div>
                </div>

                <!-- System Info - Compact -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">Tizim ma'lumotlari</h3>
                    </div>
                    <div class="p-4">
                        <dl class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">PHP versiya</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ systemInfo.php_version }}</dd>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Laravel versiya</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ systemInfo.laravel_version }}</dd>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Server vaqti</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ systemInfo.server_time }}</dd>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Muhit</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white capitalize">{{ systemInfo.environment }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import axios from 'axios'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
    initialHealth: {
        type: Object,
        default: () => ({})
    },
    systemInfo: {
        type: Object,
        default: () => ({
            php_version: '-',
            laravel_version: '-',
            server_time: '-',
            environment: '-'
        })
    }
})

const loading = ref(false)
const lastChecked = ref('Hozir')

const health = ref({
    overall: props.initialHealth.overall || { status: 'checking', percentage: 0 },
    database: props.initialHealth.health?.database || { status: 'checking', message: 'Tekshirilmoqda...' },
    storage: props.initialHealth.health?.storage || { status: 'checking', message: 'Tekshirilmoqda...' },
    cache: props.initialHealth.health?.cache || { status: 'checking', message: 'Tekshirilmoqda...' },
    queue: props.initialHealth.health?.queue || { status: 'checking', message: 'Tekshirilmoqda...' },
})

const checkHealth = async () => {
    loading.value = true
    try {
        const response = await axios.get('/admin/system-health')
        health.value = {
            overall: response.data.overall,
            database: response.data.health.database,
            storage: response.data.health.storage,
            cache: response.data.health.cache,
            queue: response.data.health.queue,
        }
        lastChecked.value = new Date().toLocaleTimeString('uz-UZ')
    } catch (error) {
        console.error('Health check failed:', error)
    } finally {
        loading.value = false
    }
}

const getOverallStatusClass = () => {
    const status = health.value.overall?.status
    if (status === 'healthy') return 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800'
    if (status === 'warning') return 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800'
    return 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'
}

const getOverallIconClass = () => {
    const status = health.value.overall?.status
    if (status === 'healthy') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400'
    if (status === 'warning') return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400'
    return 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400'
}

const getOverallTextClass = () => {
    const status = health.value.overall?.status
    if (status === 'healthy') return 'text-emerald-700 dark:text-emerald-400'
    if (status === 'warning') return 'text-yellow-700 dark:text-yellow-400'
    return 'text-red-700 dark:text-red-400'
}

const getOverallSubtextClass = () => {
    const status = health.value.overall?.status
    if (status === 'healthy') return 'text-emerald-600 dark:text-emerald-500'
    if (status === 'warning') return 'text-yellow-600 dark:text-yellow-500'
    return 'text-red-600 dark:text-red-500'
}

const getOverallStatusText = () => {
    const status = health.value.overall?.status
    if (status === 'healthy') return "Tizim sog'lom"
    if (status === 'warning') return 'Ogohlantirish'
    if (status === 'unhealthy') return 'Muammo aniqlandi'
    return 'Tekshirilmoqda...'
}

const getStatusBadgeClass = (status) => {
    if (status === 'healthy') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400'
    if (status === 'warning') return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400'
    if (status === 'unhealthy') return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'
    return 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
}

const getStatusLabel = (status) => {
    if (status === 'healthy') return "Sog'lom"
    if (status === 'warning') return 'Ogohlantirish'
    if (status === 'unhealthy') return "Sog'lom emas"
    return 'Tekshirilmoqda'
}

onMounted(() => {
    checkHealth()
})
</script>
