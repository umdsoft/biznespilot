<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import {
    CalendarIcon,
    UserIcon,
    PlusIcon,
    CheckIcon,
    XMarkIcon,
    ClockIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    leaveRequests: Array,
    employees: Array,
    stats: Object,
    currentBusiness: Object,
});

const searchQuery = ref('');
const selectedStatus = ref('');
const showCreateModal = ref(false);

const statusOptions = [
    { value: '', label: 'Barcha holatlar' },
    { value: 'pending', label: 'Kutilmoqda' },
    { value: 'approved', label: 'Tasdiqlangan' },
    { value: 'rejected', label: 'Rad etilgan' },
];

const filteredRequests = computed(() => {
    return props.leaveRequests?.filter(req => {
        const matchesSearch = !searchQuery.value ||
            req.user_name?.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesStatus = !selectedStatus.value || req.status === selectedStatus.value;
        return matchesSearch && matchesStatus;
    }) || [];
});

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        approved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const labels = {
        pending: 'Kutilmoqda',
        approved: 'Tasdiqlangan',
        rejected: 'Rad etilgan',
    };
    return labels[status] || status;
};

const approveRequest = (id) => {
    if (confirm('Ushbu ta\'til so\'rovini tasdiqlaysizmi?')) {
        router.post(`/hr/leave/${id}/approve`);
    }
};

const rejectRequest = (id) => {
    if (confirm('Ushbu ta\'til so\'rovini rad etasizmi?')) {
        router.post(`/hr/leave/${id}/reject`);
    }
};
</script>

<template>
    <HRLayout title="Ta'tillar boshqaruvi">
        <Head title="Ta'tillar boshqaruvi" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Ta'tillar boshqaruvi
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Xodimlar ta'til so'rovlarini boshqarish
                    </p>
                </div>
                <button
                    @click="showCreateModal = true"
                    class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
                >
                    <PlusIcon class="w-5 h-5" />
                    Ta'tilga chiqarish
                </button>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                            <CalendarIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jami so'rovlar</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats?.total_requests || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                            <ClockIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Kutilmoqda</p>
                            <p class="text-2xl font-bold text-yellow-600">{{ stats?.pending || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                            <CheckIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tasdiqlangan</p>
                            <p class="text-2xl font-bold text-green-600">{{ stats?.approved || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                            <XMarkIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Rad etilgan</p>
                            <p class="text-2xl font-bold text-red-600">{{ stats?.rejected || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                            <UserIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Bugun ta'tilda</p>
                            <p class="text-2xl font-bold text-blue-600">{{ stats?.on_leave_today || 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Xodim qidirish..."
                            class="w-full pl-10 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-purple-500"
                        />
                    </div>
                </div>
                <select
                    v-model="selectedStatus"
                    class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg"
                >
                    <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                    </option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Xodim</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ta'til turi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sana</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kunlar</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Holat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sabab</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="request in filteredRequests" :key="request.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                                            <span class="text-sm font-medium text-purple-600 dark:text-purple-400">
                                                {{ request.user_name?.charAt(0) || '?' }}
                                            </span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ request.user_name }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ request.user_email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ request.leave_type }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ request.start_date }} - {{ request.end_date }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ request.total_days }} kun
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-3 py-1 text-xs font-medium rounded-full', getStatusColor(request.status)]">
                                        {{ getStatusLabel(request.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                    {{ request.reason || '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div v-if="request.status === 'pending'" class="flex items-center justify-end gap-2">
                                        <button
                                            @click="approveRequest(request.id)"
                                            class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg"
                                            title="Tasdiqlash"
                                        >
                                            <CheckIcon class="w-5 h-5" />
                                        </button>
                                        <button
                                            @click="rejectRequest(request.id)"
                                            class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg"
                                            title="Rad etish"
                                        >
                                            <XMarkIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                    <span v-else class="text-sm text-gray-400">
                                        {{ request.approver_name ? `${request.approver_name} tomonidan` : '—' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="filteredRequests.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <CalendarIcon class="w-12 h-12 mx-auto opacity-50 mb-3" />
                    <p>Ta'til so'rovlari topilmadi</p>
                </div>
            </div>
        </div>
    </HRLayout>
</template>
