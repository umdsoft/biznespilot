<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useI18n } from '@/i18n';
import { refreshCsrfToken, isCsrfError } from '@/utils/csrf';

const { t } = useI18n();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    lead: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close', 'sent']);

const message = ref('');
const selectedTemplate = ref(null);
const templates = ref([]);
const isLoading = ref(false);
const isSending = ref(false);
const smsHistory = ref([]);
const showHistory = ref(false);
const error = ref(null);
const success = ref(null);

// Calculate SMS parts
const smsInfo = computed(() => {
    const text = message.value;
    const length = text.length;

    // Check for unicode (Cyrillic, etc.)
    const isUnicode = /[^\x00-\x7F]/.test(text);

    let parts = 1;
    if (isUnicode) {
        if (length <= 70) parts = 1;
        else parts = Math.ceil(length / 67);
    } else {
        if (length <= 160) parts = 1;
        else parts = Math.ceil(length / 153);
    }

    const maxChars = isUnicode ? (parts === 1 ? 70 : 67 * parts) : (parts === 1 ? 160 : 153 * parts);

    return {
        chars: length,
        parts,
        isUnicode,
        maxChars,
    };
});

// Fetch templates
const fetchTemplates = async () => {
    try {
        const response = await fetch(route('business.sms.templates'), {
            headers: {
                'Accept': 'application/json',
            },
        });
        if (response.ok) {
            templates.value = await response.json();
        }
    } catch (err) {
        console.error('Failed to fetch templates:', err);
    }
};

// Fetch SMS history for this lead
const fetchHistory = async () => {
    if (!props.lead?.id) return;

    try {
        const response = await fetch(route('business.sms.lead.history', props.lead.id), {
            headers: {
                'Accept': 'application/json',
            },
        });
        if (response.ok) {
            smsHistory.value = await response.json();
        }
    } catch (err) {
        console.error('Failed to fetch SMS history:', err);
    }
};

// Apply template
const applyTemplate = (template) => {
    selectedTemplate.value = template.id;

    // Replace placeholders
    let text = template.content;
    text = text.replace('{name}', props.lead?.name || '');
    text = text.replace('{phone}', props.lead?.phone || '');
    text = text.replace('{company}', props.lead?.company || '');
    text = text.replace('{email}', props.lead?.email || '');

    message.value = text;
};

// Send SMS
const sendSms = async () => {
    if (!message.value.trim()) {
        error.value = t('components.sms.enter_message');
        return;
    }

    isSending.value = true;
    error.value = null;
    success.value = null;

    try {
        // Refresh CSRF token before request
        await refreshCsrfToken();

        const response = await window.axios.post(route('business.sms.send', props.lead.id), {
            message: message.value,
            template_id: selectedTemplate.value,
        });

        success.value = t('components.sms.success', { parts: response.data.parts_count });
        message.value = '';
        selectedTemplate.value = null;
        emit('sent');

        // Refresh history
        await fetchHistory();

        // Auto close after 2 seconds
        setTimeout(() => {
            emit('close');
        }, 2000);
    } catch (err) {
        console.error('SMS send error:', err);

        // Handle 419 CSRF error
        if (isCsrfError(err)) {
            error.value = t('common.session_expired');
            await refreshCsrfToken();
            return;
        }

        if (err.response?.data?.error) {
            error.value = err.response.data.error;
        } else {
            error.value = t('common.network_error_retry');
        }
    } finally {
        isSending.value = false;
    }
};

// Close modal
const close = () => {
    message.value = '';
    selectedTemplate.value = null;
    error.value = null;
    success.value = null;
    showHistory.value = false;
    emit('close');
};

// Watch for modal open
watch(() => props.show, async (newVal) => {
    if (newVal) {
        isLoading.value = true;
        await Promise.all([fetchTemplates(), fetchHistory()]);
        isLoading.value = false;
    }
});

// Get status color
const getStatusColor = (status) => {
    const colors = {
        pending: 'text-yellow-400',
        sent: 'text-blue-400',
        delivered: 'text-green-400',
        failed: 'text-red-400',
    };
    return colors[status] || 'text-slate-400';
};

const getStatusLabel = (status) => {
    const labels = {
        pending: t('components.sms.status_pending'),
        sent: t('components.sms.status_sent'),
        delivered: t('components.sms.status_delivered'),
        failed: t('components.sms.status_failed'),
    };
    return labels[status] || status;
};
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <!-- Backdrop -->
                <div class="fixed inset-0 transition-opacity bg-slate-900/75" @click="close"></div>

                <!-- Modal -->
                <div class="relative inline-block w-full max-w-lg px-6 py-6 overflow-hidden text-left align-bottom transition-all transform bg-slate-800 rounded-2xl shadow-xl sm:my-8 sm:align-middle border border-slate-700">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">{{ t('components.sms.title') }}</h3>
                                <p class="text-sm text-slate-400">{{ lead.name }} - {{ lead.phone }}</p>
                            </div>
                        </div>
                        <button @click="close" class="text-slate-400 hover:text-white">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Loading -->
                    <div v-if="isLoading" class="py-12 text-center">
                        <svg class="animate-spin h-8 w-8 mx-auto text-teal-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="mt-2 text-slate-400">{{ t('common.loading') }}</p>
                    </div>

                    <div v-else>
                        <!-- Tabs -->
                        <div class="flex gap-2 mb-4">
                            <button
                                @click="showHistory = false"
                                :class="[
                                    'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                                    !showHistory ? 'bg-teal-500 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'
                                ]"
                            >
                                {{ t('components.sms.new_sms') }}
                            </button>
                            <button
                                @click="showHistory = true"
                                :class="[
                                    'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                                    showHistory ? 'bg-teal-500 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'
                                ]"
                            >
                                {{ t('components.sms.history') }} ({{ smsHistory.length }})
                            </button>
                        </div>

                        <!-- Send SMS Tab -->
                        <div v-if="!showHistory">
                            <!-- Success/Error Messages -->
                            <div v-if="success" class="mb-4 p-3 bg-green-500/20 border border-green-500/30 rounded-xl flex items-center">
                                <svg class="w-5 h-5 text-green-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <p class="text-green-400 text-sm">{{ success }}</p>
                            </div>

                            <div v-if="error" class="mb-4 p-3 bg-red-500/20 border border-red-500/30 rounded-xl flex items-center">
                                <svg class="w-5 h-5 text-red-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-red-400 text-sm">{{ error }}</p>
                            </div>

                            <!-- Templates -->
                            <div v-if="templates.length > 0" class="mb-4">
                                <label class="block text-sm font-medium text-slate-300 mb-2">{{ t('components.sms.select_template') }}</label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="template in templates"
                                        :key="template.id"
                                        @click="applyTemplate(template)"
                                        :class="[
                                            'px-3 py-1.5 rounded-lg text-sm transition-colors',
                                            selectedTemplate === template.id
                                                ? 'bg-teal-500 text-white'
                                                : 'bg-slate-700 text-slate-300 hover:bg-slate-600'
                                        ]"
                                    >
                                        {{ template.name }}
                                    </button>
                                </div>
                            </div>

                            <!-- Message Input -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-slate-300 mb-2">{{ t('components.sms.message_text') }}</label>
                                <textarea
                                    v-model="message"
                                    rows="4"
                                    :placeholder="t('components.sms.write_message')"
                                    class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 resize-none"
                                ></textarea>
                                <div class="flex justify-between mt-1 text-xs">
                                    <span :class="smsInfo.isUnicode ? 'text-yellow-400' : 'text-slate-500'">
                                        {{ smsInfo.isUnicode ? t('components.sms.unicode') : t('components.sms.latin') }}
                                    </span>
                                    <span class="text-slate-500">
                                        {{ smsInfo.chars }} {{ t('components.sms.chars') }} | {{ smsInfo.parts }} {{ t('components.sms.parts') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Send Button -->
                            <div class="flex justify-end gap-3">
                                <button
                                    @click="close"
                                    class="px-4 py-2 bg-slate-700 text-slate-300 rounded-xl hover:bg-slate-600 transition-colors"
                                >
                                    {{ t('common.cancel') }}
                                </button>
                                <button
                                    @click="sendSms"
                                    :disabled="isSending || !message.trim()"
                                    class="px-6 py-2 bg-gradient-to-r from-teal-500 to-cyan-600 text-white font-medium rounded-xl hover:from-teal-600 hover:to-cyan-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isSending" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ t('components.sms.sending') }}
                                    </span>
                                    <span v-else>{{ t('components.sms.send') }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- History Tab -->
                        <div v-else>
                            <div v-if="smsHistory.length === 0" class="py-8 text-center">
                                <svg class="w-12 h-12 text-slate-500 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                                <p class="text-slate-400">{{ t('components.sms.no_sms_sent') }}</p>
                            </div>

                            <div v-else class="space-y-3 max-h-80 overflow-y-auto">
                                <div
                                    v-for="sms in smsHistory"
                                    :key="sms.id"
                                    class="bg-slate-700/30 rounded-xl p-3"
                                >
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs text-slate-500">{{ sms.created_at }}</span>
                                        <span :class="['text-xs font-medium', getStatusColor(sms.status)]">
                                            {{ getStatusLabel(sms.status) }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-slate-300">{{ sms.message }}</p>
                                    <div class="flex items-center justify-between mt-2 text-xs text-slate-500">
                                        <span>{{ sms.sender }}</span>
                                        <span>{{ sms.parts_count }} qism</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
