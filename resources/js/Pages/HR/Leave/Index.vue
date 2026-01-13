<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    CalendarIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    PlusIcon,
    FunnelIcon,
    BanknotesIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    requests: { type: Array, default: () => [] },
    balances: { type: Array, default: () => [] },
    leaveTypes: { type: Array, default: () => [] },
    selectedUserId: { type: String, default: null },
    selectedStatus: { type: String, default: null },
    selectedYear: { type: Number, default: new Date().getFullYear() },
});

const showRequestModal = ref(false);
const showCancelModal = ref(false);
const selectedRequest = ref(null);

const form = useForm({
    leave_type_id: '',
    start_date: '',
    end_date: '',
    reason: '',
    notes: '',
    emergency_contact: '',
    emergency_phone: '',
});

const requestLeave = () => {
    form.post(route('hr.leave.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showRequestModal.value = false;
            form.reset();
        },
    });
};

const cancelRequest = (request) => {
    if (confirm('Ta\'til so\'rovini bekor qilmoqchimisiz?')) {
        router.post(route('hr.leave.cancel', request.id), {}, {
            preserveScroll: true,
        });
    }
};

const getStatusBgClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
        approved: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        rejected: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
        cancelled: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
    };
    return classes[status] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
};
</script>

<template>
    <HRLayout title="Ta'til">
        <Head title="Ta'til Boshqaruvi" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Ta'til Boshqaruvi</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Ta'til so'rovlari va balanslar</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('hr.leave.calendar')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <CalendarIcon class="w-5 h-5 inline mr-2" />
                        Kalendar
                    </Link>
                    <button
                        @click="showRequestModal = true"
                        class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-2"
                    >
                        <PlusIcon class="w-5 h-5" />
                        Ta'til So'rash
                    </button>
                </div>
            </div>

            <!-- Leave Balances -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div
                    v-for="balance in balances"
                    :key="balance.id"
                    class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700"
                >
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ balance.leave_type.name }}</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                                {{ balance.available_days }}
                            </p>
                        </div>
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                            <BanknotesIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                        </div>
                    </div>
                    <div class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
                        <div class="flex justify-between">
                            <span>Jami:</span>
                            <span class="font-medium">{{ balance.total_days }} kun</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Ishlatilgan:</span>
                            <span class="font-medium text-red-600 dark:text-red-400">{{ balance.used_days }} kun</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Kutilmoqda:</span>
                            <span class="font-medium text-yellow-600 dark:text-yellow-400">{{ balance.pending_days }} kun</span>
                        </div>
                    </div>
                </div>

                <div
                    v-if="balances.length === 0"
                    class="col-span-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-8 text-center"
                >
                    <BanknotesIcon class="w-12 h-12 mx-auto text-gray-400 mb-3" />
                    <p class="text-gray-500 dark:text-gray-400">Ta'til balanslari topilmadi</p>
                </div>
            </div>

            <!-- Leave Requests -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Ta'til So'rovlari</h2>
                </div>

                <div v-if="requests.length > 0" class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Turi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Boshlanish</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tugash</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kunlar</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sabab</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Holat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="request in requests"
                                :key="request.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-900/50"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ request.leave_type.name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ request.start_date_formatted }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ request.end_date_formatted }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ request.total_days }} kun
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                    {{ request.reason }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusBgClass(request.status)]"
                                    >
                                        {{ request.status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button
                                        v-if="request.status === 'pending'"
                                        @click="cancelRequest(request)"
                                        class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
                                    >
                                        Bekor qilish
                                    </button>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="p-12 text-center text-gray-500 dark:text-gray-400">
                    <CalendarIcon class="w-12 h-12 mx-auto mb-4 opacity-50" />
                    <p>Ta'til so'rovlari topilmadi</p>
                </div>
            </div>
        </div>

        <!-- Request Leave Modal -->
        <div
            v-if="showRequestModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="showRequestModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Ta'til So'rash</h3>
                </div>

                <form @submit.prevent="requestLeave" class="p-6 space-y-4">
                    <!-- Leave Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Ta'til Turi *
                        </label>
                        <select
                            v-model="form.leave_type_id"
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                        >
                            <option value="">Tanlang</option>
                            <option
                                v-for="type in leaveTypes"
                                :key="type.id"
                                :value="type.id"
                            >
                                {{ type.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Dates -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Boshlanish Sanasi *
                            </label>
                            <input
                                v-model="form.start_date"
                                type="date"
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tugash Sanasi *
                            </label>
                            <input
                                v-model="form.end_date"
                                type="date"
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                            />
                        </div>
                    </div>

                    <!-- Reason -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Sababi *
                        </label>
                        <textarea
                            v-model="form.reason"
                            required
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                            placeholder="Ta'til sababini yozing..."
                        ></textarea>
                    </div>

                    <!-- Emergency Contact -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Favqulodda Aloqa
                            </label>
                            <input
                                v-model="form.emergency_contact"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                                placeholder="Ism"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Telefon Raqami
                            </label>
                            <input
                                v-model="form.emergency_phone"
                                type="tel"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                                placeholder="+998"
                            />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4">
                        <button
                            type="button"
                            @click="showRequestModal = false"
                            class="px-6 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            Bekor qilish
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing">Yuklanmoqda...</span>
                            <span v-else>Yuborish</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </HRLayout>
</template>
