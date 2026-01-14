<template>
    <AdminLayout title="Bizneslar Boshqaruvi">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900">Bizneslar Boshqaruvi</h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Platformadagi barcha bizneslarni ko'ring va boshqaring
                        </p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button
                            @click="exportData"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Jami Bizneslar</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Faol Bizneslar</p>
                                <p class="text-2xl font-bold text-green-600">{{ stats.active }}</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Nofaol Bizneslar</p>
                                <p class="text-2xl font-bold text-orange-600">{{ stats.inactive }}</p>
                            </div>
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Shu Oy Yangi</p>
                                <p class="text-2xl font-bold text-purple-600">{{ stats.this_month }}</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters & Search -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Qidirish</label>
                            <div class="relative">
                                <input
                                    v-model="filters.search"
                                    @input="applyFilters"
                                    type="text"
                                    placeholder="Biznes nomi yoki owner qidirish..."
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select
                                v-model="filters.status"
                                @change="applyFilters"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">Barchasi</option>
                                <option value="active">Faol</option>
                                <option value="inactive">Nofaol</option>
                            </select>
                        </div>

                        <!-- Sort By -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tartiblash</label>
                            <select
                                v-model="filters.sort"
                                @change="applyFilters"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="newest">Eng yangi</option>
                                <option value="oldest">Eng eski</option>
                                <option value="name">Nom bo'yicha</option>
                                <option value="customers">Mijozlar soni</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Businesses Table -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Biznes
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Owner
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Mijozlar
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Kampaniyalar
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Yaratilgan
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Harakatlar
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="business in filteredBusinesses" :key="business.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center text-white font-bold">
                                                {{ business.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ business.name }}</div>
                                                <div class="text-sm text-gray-500">{{ business.industry || 'N/A' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ business.owner_name }}</div>
                                        <div class="text-sm text-gray-500">{{ business.owner_email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-3 py-1 bg-purple-100 text-purple-800 text-sm font-semibold rounded-full">
                                            {{ business.customers_count }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm font-semibold rounded-full">
                                            {{ business.campaigns_count }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span
                                            class="px-3 py-1 text-xs font-semibold rounded-full"
                                            :class="{
                                                'bg-green-100 text-green-800': business.status === 'active',
                                                'bg-gray-100 text-gray-800': business.status === 'inactive'
                                            }"
                                        >
                                            {{ business.status === 'active' ? 'Faol' : 'Nofaol' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ business.created_at }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <button
                                                @click="viewBusiness(business.id)"
                                                class="text-blue-600 hover:text-blue-900"
                                                title="Ko'rish"
                                            >
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="toggleStatus(business)"
                                                class="hover:text-yellow-900"
                                                :class="business.status === 'active' ? 'text-orange-600' : 'text-green-600'"
                                                :title="business.status === 'active' ? 'O\'chirish' : 'Yoqish'"
                                            >
                                                <svg v-if="business.status === 'active'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="confirmDelete(business)"
                                                class="text-red-600 hover:text-red-900"
                                                title="O'chirish"
                                            >
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t border-gray-200">
                        <div class="text-sm text-gray-700">
                            Ko'rsatilmoqda <span class="font-medium">{{ filteredBusinesses.length }}</span> dan <span class="font-medium">{{ businesses.length }}</span> ta natija
                        </div>
                    </div>
                </div>

                <!-- Delete Confirmation Modal -->
                <div v-if="showDeleteModal" @click="showDeleteModal = false" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div @click.stop class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900">Biznesni O'chirish</h3>
                            <button @click="showDeleteModal = false" class="text-gray-500 hover:text-gray-700">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="mb-6">
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <p class="text-center text-gray-700">
                                Haqiqatan ham <strong>{{ businessToDelete?.name }}</strong> biznesni o'chirmoqchimisiz?
                            </p>
                            <p class="text-center text-sm text-red-600 mt-2">
                                Bu amal qaytarilmaydi va barcha ma'lumotlar o'chiriladi!
                            </p>
                        </div>

                        <div class="flex space-x-3">
                            <button
                                @click="showDeleteModal = false"
                                class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300"
                            >
                                Bekor qilish
                            </button>
                            <button
                                @click="deleteBusiness"
                                class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                            >
                                O'chirish
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    businesses: Array,
    stats: Object,
})

const filters = ref({
    search: '',
    status: '',
    sort: 'newest',
})

const showDeleteModal = ref(false)
const businessToDelete = ref(null)

const filteredBusinesses = computed(() => {
    let result = [...props.businesses]

    // Search filter
    if (filters.value.search) {
        const searchLower = filters.value.search.toLowerCase()
        result = result.filter(business =>
            business.name.toLowerCase().includes(searchLower) ||
            business.owner_name.toLowerCase().includes(searchLower) ||
            business.owner_email.toLowerCase().includes(searchLower)
        )
    }

    // Status filter
    if (filters.value.status) {
        result = result.filter(business => business.status === filters.value.status)
    }

    // Sorting
    switch (filters.value.sort) {
        case 'newest':
            result.sort((a, b) => new Date(b.created_at_raw) - new Date(a.created_at_raw))
            break
        case 'oldest':
            result.sort((a, b) => new Date(a.created_at_raw) - new Date(b.created_at_raw))
            break
        case 'name':
            result.sort((a, b) => a.name.localeCompare(b.name))
            break
        case 'customers':
            result.sort((a, b) => b.customers_count - a.customers_count)
            break
    }

    return result
})

const applyFilters = () => {
    // Filters are reactive, so this is just a placeholder
}

const viewBusiness = (businessId) => {
    router.visit(`/admin/businesses/${businessId}`)
}

const toggleStatus = (business) => {
    const newStatus = business.status === 'active' ? 'inactive' : 'active'

    router.put(`/admin/businesses/${business.id}/status`, {
        status: newStatus
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Success feedback
        }
    })
}

const confirmDelete = (business) => {
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

const exportData = () => {
    // Export functionality
    alert('Export funksiyasi tez orada qo\'shiladi')
}
</script>
