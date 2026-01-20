<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import {
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    UserIcon,
    CalendarIcon,
    DocumentTextIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    requests: { type: Array, default: () => [] },
});

const showApproveModal = ref(false);
const showRejectModal = ref(false);
const selectedRequest = ref(null);

const approveForm = useForm({
    comments: '',
});

const rejectForm = useForm({
    reason: '',
    comments: '',
});

const openApproveModal = (request) => {
    selectedRequest.value = request;
    showApproveModal.value = true;
    approveForm.reset();
};

const openRejectModal = (request) => {
    selectedRequest.value = request;
    showRejectModal.value = true;
    rejectForm.reset();
};

const approveRequest = () => {
    if (!selectedRequest.value) return;

    approveForm.post(route('hr.leave.approve', selectedRequest.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showApproveModal.value = false;
            selectedRequest.value = null;
            approveForm.reset();
        },
    });
};

const rejectRequest = () => {
    if (!selectedRequest.value) return;

    rejectForm.post(route('hr.leave.reject', selectedRequest.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showRejectModal.value = false;
            selectedRequest.value = null;
            rejectForm.reset();
        },
    });
};
</script>

<template>
    <HRLayout :title="t('hr.leave_approvals')">
        <Head :title="t('hr.leave_approvals')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ t('hr.leave_approvals') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ t('hr.leave_requests') }} ({{ requests.length }})
                    </p>
                </div>
            </div>

            <!-- Pending Requests -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div v-if="requests.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="request in requests"
                        :key="request.id"
                        class="p-6 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
                    >
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                            <!-- Request Info -->
                            <div class="flex-1 space-y-4">
                                <!-- User & Leave Type -->
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                                        <UserIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ request.user.name }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ request.leave_type.name }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Dates & Duration -->
                                <div class="flex items-center gap-6 text-sm">
                                    <div class="flex items-center gap-2">
                                        <CalendarIcon class="w-4 h-4 text-gray-400" />
                                        <span class="text-gray-600 dark:text-gray-300">
                                            {{ request.start_date_formatted }} - {{ request.end_date_formatted }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <ClockIcon class="w-4 h-4 text-gray-400" />
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ request.total_days }} kun
                                        </span>
                                    </div>
                                </div>

                                <!-- Reason -->
                                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                                    <div class="flex items-start gap-2">
                                        <DocumentTextIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sababi:</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ request.reason }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Emergency Contact -->
                                <div v-if="request.emergency_contact || request.emergency_phone" class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="font-medium">Favqulodda aloqa:</span>
                                    <span v-if="request.emergency_contact">{{ request.emergency_contact }}</span>
                                    <span v-if="request.emergency_phone">{{ request.emergency_phone }}</span>
                                </div>

                                <!-- Request Date -->
                                <div class="text-xs text-gray-400">
                                    So'rov yuborilgan: {{ request.created_at }}
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex lg:flex-col gap-3">
                                <button
                                    @click="openApproveModal(request)"
                                    class="flex-1 lg:flex-none px-6 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center gap-2"
                                >
                                    <CheckCircleIcon class="w-5 h-5" />
                                    <span>Tasdiqlash</span>
                                </button>
                                <button
                                    @click="openRejectModal(request)"
                                    class="flex-1 lg:flex-none px-6 py-2.5 bg-white dark:bg-gray-700 text-red-600 dark:text-red-400 text-sm font-medium rounded-lg border border-red-300 dark:border-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors flex items-center justify-center gap-2"
                                >
                                    <XCircleIcon class="w-5 h-5" />
                                    <span>Rad etish</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="p-12 text-center text-gray-500 dark:text-gray-400">
                    <ClockIcon class="w-12 h-12 mx-auto mb-4 opacity-50" />
                    <p class="text-lg font-medium mb-2">Kutilayotgan so'rovlar yo'q</p>
                    <p class="text-sm">Barcha ta'til so'rovlari ko'rib chiqilgan</p>
                </div>
            </div>
        </div>

        <!-- Approve Modal -->
        <div
            v-if="showApproveModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="showApproveModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                            <CheckCircleIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Ta'tilni Tasdiqlash</h3>
                    </div>
                </div>

                <form @submit.prevent="approveRequest" class="p-6 space-y-4">
                    <div v-if="selectedRequest" class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Xodim:</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ selectedRequest.user.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Sana:</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                {{ selectedRequest.start_date_formatted }} - {{ selectedRequest.end_date_formatted }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Kunlar:</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ selectedRequest.total_days }} kun</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Izoh (ixtiyoriy)
                        </label>
                        <textarea
                            v-model="approveForm.comments"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500"
                            placeholder="Qo'shimcha izoh yozing..."
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button
                            type="button"
                            @click="showApproveModal = false"
                            class="px-6 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            Bekor qilish
                        </button>
                        <button
                            type="submit"
                            :disabled="approveForm.processing"
                            class="px-6 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="approveForm.processing">Yuklanmoqda...</span>
                            <span v-else>Tasdiqlash</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reject Modal -->
        <div
            v-if="showRejectModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="showRejectModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                            <XCircleIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Ta'tilni Rad Etish</h3>
                    </div>
                </div>

                <form @submit.prevent="rejectRequest" class="p-6 space-y-4">
                    <div v-if="selectedRequest" class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Xodim:</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ selectedRequest.user.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Sana:</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                {{ selectedRequest.start_date_formatted }} - {{ selectedRequest.end_date_formatted }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Rad etish sababi *
                        </label>
                        <textarea
                            v-model="rejectForm.reason"
                            required
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500"
                            placeholder="Nima uchun rad etyapsiz..."
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Qo'shimcha izoh (ixtiyoriy)
                        </label>
                        <textarea
                            v-model="rejectForm.comments"
                            rows="2"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500"
                            placeholder="Qo'shimcha izoh..."
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button
                            type="button"
                            @click="showRejectModal = false"
                            class="px-6 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            Bekor qilish
                        </button>
                        <button
                            type="submit"
                            :disabled="rejectForm.processing"
                            class="px-6 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="rejectForm.processing">Yuklanmoqda...</span>
                            <span v-else>Rad Etish</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </HRLayout>
</template>
