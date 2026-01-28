<template>
    <AdminLayout :title="t('admin.activity_logs.title')">
        <div class="py-6">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                            {{ t('admin.activity_logs.title') }}
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ t('admin.activity_logs.subtitle') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="exportLogs"
                            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            {{ t('admin.common.export') }}
                        </button>
                        <button
                            @click="refreshLogs"
                            :disabled="loading"
                            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors disabled:opacity-50"
                        >
                            <svg :class="['w-4 h-4', loading ? 'animate-spin' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ t('admin.analytics.refresh') }}
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-50 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.today }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.activity_logs.today') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-50 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.this_week }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.activity_logs.this_week') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-red-50 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-red-600 dark:text-red-400">{{ stats.errors }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.activity_logs.errors') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-50 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.active_users }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.activity_logs.active_users') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters & Table -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <!-- Inline Filters -->
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex-1 min-w-[200px]">
                                <div class="relative">
                                    <input
                                        v-model="filters.search"
                                        type="text"
                                        :placeholder="t('admin.activity_logs.search_placeholder')"
                                        class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                    />
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>
                            <select
                                v-model="filters.type"
                                class="px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            >
                                <option value="">{{ t('admin.activity_logs.all_types') }}</option>
                                <option value="login">{{ t('admin.activity_logs.type_login') }}</option>
                                <option value="logout">{{ t('admin.activity_logs.type_logout') }}</option>
                                <option value="create">{{ t('admin.activity_logs.type_create') }}</option>
                                <option value="update">{{ t('admin.activity_logs.type_update') }}</option>
                                <option value="delete">{{ t('admin.activity_logs.type_delete') }}</option>
                                <option value="error">{{ t('admin.activity_logs.type_error') }}</option>
                            </select>
                            <input
                                v-model="filters.date"
                                type="date"
                                class="px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            />
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('admin.activity_logs.time') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('admin.activity_logs.user') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('admin.activity_logs.activity') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('admin.activity_logs.type') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('admin.activity_logs.ip') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="log in filteredLogs" :key="log.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        {{ log.created_at }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                                    {{ log.user?.name?.charAt(0).toUpperCase() || '?' }}
                                                </span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ log.user?.name || 'Tizim' }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ log.user?.email || '' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                        {{ log.description }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class="px-2 py-0.5 text-xs font-medium rounded"
                                            :class="getTypeBadgeClass(log.event)"
                                        >
                                            {{ getTypeLabel(log.event) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        {{ log.properties?.ip || '-' }}
                                    </td>
                                </tr>
                                <tr v-if="filteredLogs.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        {{ t('admin.activity_logs.not_found') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer -->
                    <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400">
                        {{ t('admin.common.showing') }}: <span class="font-medium">{{ filteredLogs.length }}</span> {{ t('admin.activity_logs.records') }}
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
    logs: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({
            today: 0,
            this_week: 0,
            errors: 0,
            active_users: 0
        })
    }
})

const loading = ref(false)

const filters = ref({
    search: '',
    type: '',
    date: ''
})

const filteredLogs = computed(() => {
    let result = [...props.logs]

    if (filters.value.search) {
        const searchLower = filters.value.search.toLowerCase()
        result = result.filter(log =>
            log.description?.toLowerCase().includes(searchLower) ||
            log.user?.name?.toLowerCase().includes(searchLower) ||
            log.user?.email?.toLowerCase().includes(searchLower)
        )
    }

    if (filters.value.type) {
        result = result.filter(log => log.event === filters.value.type)
    }

    return result
})

const getTypeBadgeClass = (type) => {
    const classes = {
        login: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        logout: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        create: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        update: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        delete: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        error: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    }
    return classes[type] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'
}

const getTypeLabel = (type) => {
    const labels = {
        login: 'Kirish',
        logout: 'Chiqish',
        create: 'Yaratish',
        update: 'Yangilash',
        delete: "O'chirish",
        error: 'Xato',
    }
    return labels[type] || type
}

const refreshLogs = () => {
    loading.value = true
    window.location.reload()
}

const exportLogs = () => {
    window.location.href = '/dashboard/activity-logs/export'
}
</script>
