<template>
    <AdminLayout :title="t('admin.businesses.title')">
        <div class="min-h-screen">
            <!-- Clean Header -->
            <div class="border-b border-gray-200 dark:border-gray-700/50 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm sticky top-0 z-10">
                <div class="max-w-[1600px] mx-auto px-6 py-5">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                            {{ t('admin.businesses.title') }}
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ stats.total }} {{ t('admin.businesses.total_businesses').toLowerCase() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="max-w-[1600px] mx-auto px-6 py-6">
                <!-- Compact Stats -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700/50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-50 dark:bg-blue-500/10 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.total }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.businesses.total_businesses') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700/50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-emerald-50 dark:bg-emerald-500/10 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.active }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.businesses.active_businesses') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700/50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-amber-50 dark:bg-amber-500/10 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.inactive }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.businesses.inactive_businesses') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700/50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-violet-50 dark:bg-violet-500/10 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.this_month }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.businesses.new_this_month') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inline Filters -->
                <div class="flex flex-col sm:flex-row gap-3 mb-6">
                    <div class="relative flex-1">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="filters.search"
                            type="text"
                            :placeholder="t('admin.businesses.search_placeholder')"
                            class="w-full pl-10 pr-4 py-2.5 text-sm bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 rounded-lg focus:ring-2 focus:ring-gray-900/10 dark:focus:ring-white/10 focus:border-gray-300 dark:focus:border-gray-600 text-gray-900 dark:text-white placeholder-gray-400"
                        />
                    </div>
                    <select
                        v-model="filters.status"
                        class="px-4 py-2.5 text-sm bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 rounded-lg focus:ring-2 focus:ring-gray-900/10 dark:focus:ring-white/10 text-gray-900 dark:text-white min-w-[140px]"
                    >
                        <option value="">{{ t('common.status') }}: {{ t('admin.common.all') }}</option>
                        <option value="active">{{ t('admin.common.active') }}</option>
                        <option value="inactive">{{ t('admin.common.inactive') }}</option>
                    </select>
                    <select
                        v-model="filters.sort"
                        class="px-4 py-2.5 text-sm bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 rounded-lg focus:ring-2 focus:ring-gray-900/10 dark:focus:ring-white/10 text-gray-900 dark:text-white min-w-[140px]"
                    >
                        <option value="newest">{{ t('admin.common.newest') }}</option>
                        <option value="oldest">{{ t('admin.common.oldest') }}</option>
                        <option value="name">{{ t('admin.common.by_name') }}</option>
                        <option value="customers">{{ t('admin.businesses.by_customers') }}</option>
                    </select>
                </div>

                <!-- Clean Table -->
                <div class="bg-white dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">
                                    {{ t('admin.businesses.business') }}
                                </th>
                                <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden md:table-cell">
                                    {{ t('admin.businesses.owner') }}
                                </th>
                                <th class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden lg:table-cell">
                                    {{ t('admin.businesses.customers') }}
                                </th>
                                <th class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden lg:table-cell">
                                    {{ t('admin.businesses.campaigns') }}
                                </th>
                                <th class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">
                                    {{ t('common.status') }}
                                </th>
                                <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden sm:table-cell">
                                    {{ t('admin.common.created') }}
                                </th>
                                <th class="w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/30">
                            <tr
                                v-for="business in filteredBusinesses"
                                :key="business.id"
                                class="group hover:bg-gray-50/50 dark:hover:bg-gray-700/20 transition-colors"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-lg flex items-center justify-center text-sm font-medium text-gray-600 dark:text-gray-300">
                                            {{ business.name?.charAt(0).toUpperCase() || 'B' }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ business.name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ business.industry || '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 hidden md:table-cell">
                                    <p class="text-sm text-gray-900 dark:text-white">{{ business.owner_name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ business.owner_email }}</p>
                                </td>
                                <td class="px-5 py-4 text-center hidden lg:table-cell">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ business.customers_count }}</span>
                                </td>
                                <td class="px-5 py-4 text-center hidden lg:table-cell">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ business.campaigns_count }}</span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span :class="getStatusBadgeClass(business.status)" class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md">
                                        {{ business.status === 'active' ? t('admin.common.active') : t('admin.common.inactive') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 hidden sm:table-cell">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ business.created_at }}</span>
                                </td>
                                <td class="px-3 py-4">
                                    <div class="relative" v-click-outside="() => closeMenu(business.id)">
                                        <button
                                            @click="toggleMenu(business.id)"
                                            class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors opacity-0 group-hover:opacity-100"
                                        >
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                            </svg>
                                        </button>
                                        <Transition
                                            enter-active-class="transition ease-out duration-100"
                                            enter-from-class="transform opacity-0 scale-95"
                                            enter-to-class="transform opacity-100 scale-100"
                                            leave-active-class="transition ease-in duration-75"
                                            leave-from-class="transform opacity-100 scale-100"
                                            leave-to-class="transform opacity-0 scale-95"
                                        >
                                            <div
                                                v-if="openMenuId === business.id"
                                                class="absolute right-0 mt-1 w-40 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-100 dark:border-gray-700 py-1 z-20"
                                            >
                                                <button
                                                    @click="viewBusiness(business.id)"
                                                    class="w-full px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    {{ t('common.view') }}
                                                </button>
                                                <button
                                                    @click="toggleStatus(business)"
                                                    class="w-full px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
                                                >
                                                    <svg v-if="business.status === 'active'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                                                    </svg>
                                                    <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                                                    </svg>
                                                    {{ business.status === 'active' ? t('admin.businesses.deactivate') : t('admin.businesses.activate') }}
                                                </button>
                                                <button
                                                    @click="confirmDelete(business)"
                                                    class="w-full px-3 py-2 text-left text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                    </svg>
                                                    {{ t('common.delete') }}
                                                </button>
                                            </div>
                                        </Transition>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div v-if="filteredBusinesses.length === 0" class="py-12 text-center">
                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('admin.businesses.not_found') }}</p>
                    </div>

                    <!-- Footer -->
                    <div v-if="filteredBusinesses.length > 0" class="px-5 py-3 border-t border-gray-100 dark:border-gray-700/50 bg-gray-50/50 dark:bg-gray-800/30">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ filteredBusinesses.length }} / {{ businesses.length }} {{ t('admin.common.results') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Delete Modal -->
            <Teleport to="body">
                <Transition
                    enter-active-class="transition-opacity duration-150"
                    leave-active-class="transition-opacity duration-100"
                    enter-from-class="opacity-0"
                    leave-to-class="opacity-0"
                >
                    <div v-if="showDeleteModal" @click="showDeleteModal = false" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                        <Transition
                            enter-active-class="transition-all duration-150"
                            leave-active-class="transition-all duration-100"
                            enter-from-class="opacity-0 scale-95"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div v-if="showDeleteModal" @click.stop class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full p-5 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 bg-red-100 dark:bg-red-500/20 rounded-full flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ t('admin.businesses.delete_business') }}</h3>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ t('admin.businesses.delete_confirm') }} <strong class="text-gray-900 dark:text-white">{{ businessToDelete?.name }}</strong>?
                                        </p>
                                        <p class="mt-2 text-xs text-red-600 dark:text-red-400">
                                            {{ t('admin.businesses.delete_warning') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-2 mt-5">
                                    <button
                                        @click="showDeleteModal = false"
                                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                    >
                                        {{ t('common.cancel') }}
                                    </button>
                                    <button
                                        @click="deleteBusiness"
                                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors"
                                    >
                                        {{ t('common.delete') }}
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </Transition>
            </Teleport>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
    businesses: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            inactive: 0,
            this_month: 0
        })
    },
})

const filters = ref({
    search: '',
    status: '',
    sort: 'newest',
})

const showDeleteModal = ref(false)
const businessToDelete = ref(null)
const openMenuId = ref(null)

const filteredBusinesses = computed(() => {
    let result = [...props.businesses]

    if (filters.value.search) {
        const searchLower = filters.value.search.toLowerCase()
        result = result.filter(business =>
            business.name?.toLowerCase().includes(searchLower) ||
            business.owner_name?.toLowerCase().includes(searchLower) ||
            business.owner_email?.toLowerCase().includes(searchLower)
        )
    }

    if (filters.value.status) {
        result = result.filter(business => business.status === filters.value.status)
    }

    switch (filters.value.sort) {
        case 'newest':
            result.sort((a, b) => new Date(b.created_at_raw) - new Date(a.created_at_raw))
            break
        case 'oldest':
            result.sort((a, b) => new Date(a.created_at_raw) - new Date(b.created_at_raw))
            break
        case 'name':
            result.sort((a, b) => (a.name || '').localeCompare(b.name || ''))
            break
        case 'customers':
            result.sort((a, b) => b.customers_count - a.customers_count)
            break
    }

    return result
})

const getStatusBadgeClass = (status) => {
    return status === 'active'
        ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-600/10 dark:ring-emerald-400/20'
        : 'bg-gray-50 dark:bg-gray-500/10 text-gray-700 dark:text-gray-400 ring-1 ring-gray-600/10 dark:ring-gray-400/20'
}

const toggleMenu = (businessId) => {
    openMenuId.value = openMenuId.value === businessId ? null : businessId
}

const closeMenu = (businessId) => {
    if (openMenuId.value === businessId) {
        openMenuId.value = null
    }
}

const viewBusiness = (businessId) => {
    openMenuId.value = null
    router.visit(`/admin/businesses/${businessId}`)
}

const toggleStatus = (business) => {
    openMenuId.value = null
    const newStatus = business.status === 'active' ? 'inactive' : 'active'

    router.put(`/admin/businesses/${business.id}/status`, {
        status: newStatus
    }, {
        preserveScroll: true
    })
}

const confirmDelete = (business) => {
    openMenuId.value = null
    businessToDelete.value = business
    showDeleteModal.value = true
}

const deleteBusiness = () => {
    if (!businessToDelete.value) return

    router.delete(`/admin/businesses/${businessToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            businessToDelete.value = null
        }
    })
}

// Custom directive for click outside
const vClickOutside = {
    mounted(el, binding) {
        el._clickOutside = (event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value(event)
            }
        }
        document.addEventListener('click', el._clickOutside)
    },
    unmounted(el) {
        document.removeEventListener('click', el._clickOutside)
    }
}
</script>
