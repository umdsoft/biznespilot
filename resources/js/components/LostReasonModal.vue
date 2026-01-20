<script setup>
import { ref, computed } from 'vue';
import { useI18n } from '@/i18n';
import {
    XMarkIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    lead: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'confirm']);

const lostReasons = computed(() => [
    { key: 'price', label: t('components.lost_reason.reason_price'), icon: '💰' },
    { key: 'competitor', label: t('components.lost_reason.reason_competitor'), icon: '🏃' },
    { key: 'no_budget', label: t('components.lost_reason.reason_no_budget'), icon: '💸' },
    { key: 'no_need', label: t('components.lost_reason.reason_no_need'), icon: '🚫' },
    { key: 'no_response', label: t('components.lost_reason.reason_no_response'), icon: '📵' },
    { key: 'wrong_contact', label: t('components.lost_reason.reason_wrong_contact'), icon: '❌' },
    { key: 'low_quality', label: t('components.lost_reason.reason_low_quality'), icon: '👎' },
    { key: 'timing', label: t('components.lost_reason.reason_timing'), icon: '⏰' },
    { key: 'other', label: t('components.lost_reason.reason_other'), icon: '📝' },
]);

const selectedReason = ref('');
const details = ref('');
const isSubmitting = ref(false);

const canSubmit = computed(() => {
    return selectedReason.value && !isSubmitting.value;
});

const formatCurrency = (value) => {
    if (!value) return '0';
    return new Intl.NumberFormat('uz-UZ').format(value);
};

const handleClose = () => {
    if (!isSubmitting.value) {
        selectedReason.value = '';
        details.value = '';
        emit('close');
    }
};

const handleConfirm = async () => {
    if (!canSubmit.value) return;

    isSubmitting.value = true;

    emit('confirm', {
        leadId: props.lead?.uuid,
        reason: selectedReason.value,
        details: details.value,
    });
};

const resetForm = () => {
    selectedReason.value = '';
    details.value = '';
    isSubmitting.value = false;
};

defineExpose({ resetForm });
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 overflow-y-auto"
            >
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-black/50 backdrop-blur-sm"
                    @click="handleClose"
                ></div>

                <!-- Modal -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-if="show"
                            class="relative w-full max-w-md transform rounded-2xl bg-white dark:bg-gray-800 shadow-2xl transition-all"
                            @click.stop
                        >
                            <!-- Header -->
                            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                                        <ExclamationTriangleIcon class="w-5 h-5 text-red-600 dark:text-red-400" />
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ t('components.lost_reason.title') }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ t('components.lost_reason.select_reason') }}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    @click="handleClose"
                                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                >
                                    <XMarkIcon class="w-5 h-5" />
                                </button>
                            </div>

                            <!-- Lead Info -->
                            <div v-if="lead" class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ lead.name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ lead.phone }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ formatCurrency(lead.estimated_value) }} so'm
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Body -->
                            <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
                                <!-- Reason Selection -->
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                        {{ t('components.lost_reason.reason_label') }} *
                                    </label>
                                    <div class="grid grid-cols-1 gap-2">
                                        <label
                                            v-for="reason in lostReasons"
                                            :key="reason.key"
                                            :class="[
                                                'flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all',
                                                selectedReason === reason.key
                                                    ? 'border-red-500 bg-red-50 dark:bg-red-900/20 ring-1 ring-red-500'
                                                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700/50'
                                            ]"
                                        >
                                            <input
                                                type="radio"
                                                :value="reason.key"
                                                v-model="selectedReason"
                                                class="sr-only"
                                            />
                                            <span class="text-xl">{{ reason.icon }}</span>
                                            <span :class="[
                                                'font-medium',
                                                selectedReason === reason.key
                                                    ? 'text-red-700 dark:text-red-400'
                                                    : 'text-gray-700 dark:text-gray-300'
                                            ]">
                                                {{ reason.label }}
                                            </span>
                                            <div
                                                v-if="selectedReason === reason.key"
                                                class="ml-auto w-5 h-5 bg-red-500 rounded-full flex items-center justify-center"
                                            >
                                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Details -->
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ t('components.lost_reason.additional_notes') }}
                                    </label>
                                    <textarea
                                        v-model="details"
                                        rows="3"
                                        :placeholder="t('components.lost_reason.notes_placeholder')"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 rounded-b-2xl">
                                <button
                                    @click="handleClose"
                                    :disabled="isSubmitting"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors disabled:opacity-50"
                                >
                                    {{ t('common.cancel') }}
                                </button>
                                <button
                                    @click="handleConfirm"
                                    :disabled="!canSubmit"
                                    :class="[
                                        'px-4 py-2 text-sm font-medium rounded-lg transition-all',
                                        canSubmit
                                            ? 'bg-red-600 text-white hover:bg-red-700 shadow-lg shadow-red-600/25'
                                            : 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                    ]"
                                >
                                    <span v-if="isSubmitting" class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ t('common.saving') }}
                                    </span>
                                    <span v-else>{{ t('components.lost_reason.mark_as_lost') }}</span>
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
