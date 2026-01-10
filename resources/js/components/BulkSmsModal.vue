<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    leads: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'sent']);

const message = ref('');
const selectedTemplate = ref(null);
const templates = ref([]);
const isLoading = ref(false);
const isSending = ref(false);
const error = ref(null);
const result = ref(null);

// Get leads with phone numbers
const leadsWithPhone = computed(() => props.leads.filter(lead => lead.phone));
const leadsWithoutPhone = computed(() => props.leads.filter(lead => !lead.phone));

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

// Total SMS to be sent
const totalSms = computed(() => leadsWithPhone.value.length * smsInfo.value.parts);

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

// Apply template
const applyTemplate = (template) => {
    selectedTemplate.value = template.id;
    message.value = template.content;
};

// Send bulk SMS
const sendBulkSms = async () => {
    if (!message.value.trim()) {
        error.value = 'Xabar matnini kiriting';
        return;
    }

    if (leadsWithPhone.value.length === 0) {
        error.value = 'Telefon raqamli lidlar topilmadi';
        return;
    }

    isSending.value = true;
    error.value = null;
    result.value = null;

    try {
        const response = await fetch(route('business.sms.bulk-send'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                lead_ids: leadsWithPhone.value.map(l => l.id),
                message: message.value,
                template_id: selectedTemplate.value,
            }),
        });

        const data = await response.json();

        if (response.ok) {
            result.value = data;
            emit('sent', data);

            // Auto close after 3 seconds if successful
            if (data.failed === 0) {
                setTimeout(() => {
                    close();
                }, 3000);
            }
        } else {
            error.value = data.error || 'SMS yuborishda xatolik yuz berdi';
        }
    } catch (err) {
        error.value = 'Tarmoq xatosi. Qaytadan urinib ko\'ring.';
        console.error('Bulk SMS send error:', err);
    } finally {
        isSending.value = false;
    }
};

// Close modal
const close = () => {
    message.value = '';
    selectedTemplate.value = null;
    error.value = null;
    result.value = null;
    emit('close');
};

// Watch for modal open
watch(() => props.show, async (newVal) => {
    if (newVal) {
        isLoading.value = true;
        await fetchTemplates();
        isLoading.value = false;
    }
});
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <!-- Backdrop -->
                <div class="fixed inset-0 transition-opacity bg-slate-900/75" @click="close"></div>

                <!-- Modal -->
                <div class="relative inline-block w-full max-w-2xl px-6 py-6 overflow-hidden text-left align-bottom transition-all transform bg-slate-800 rounded-2xl shadow-xl sm:my-8 sm:align-middle border border-slate-700">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-white">Ommaviy SMS Yuborish</h3>
                                <p class="text-sm text-slate-400">{{ leadsWithPhone.length }} ta lidga SMS yuborish</p>
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
                        <p class="mt-2 text-slate-400">Yuklanmoqda...</p>
                    </div>

                    <div v-else>
                        <!-- Result -->
                        <div v-if="result" class="mb-6">
                            <div :class="[
                                'p-4 rounded-xl',
                                result.failed === 0 ? 'bg-green-500/20 border border-green-500/30' : 'bg-yellow-500/20 border border-yellow-500/30'
                            ]">
                                <div class="flex items-center mb-3">
                                    <svg v-if="result.failed === 0" class="w-6 h-6 text-green-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg v-else class="w-6 h-6 text-yellow-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span :class="result.failed === 0 ? 'text-green-400' : 'text-yellow-400'" class="font-semibold">
                                        {{ result.message }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-3 gap-4 text-center">
                                    <div class="bg-slate-700/50 rounded-lg p-3">
                                        <p class="text-2xl font-bold text-white">{{ result.total }}</p>
                                        <p class="text-xs text-slate-400">Jami</p>
                                    </div>
                                    <div class="bg-slate-700/50 rounded-lg p-3">
                                        <p class="text-2xl font-bold text-green-400">{{ result.sent }}</p>
                                        <p class="text-xs text-slate-400">Yuborildi</p>
                                    </div>
                                    <div class="bg-slate-700/50 rounded-lg p-3">
                                        <p class="text-2xl font-bold text-red-400">{{ result.failed }}</p>
                                        <p class="text-xs text-slate-400">Xatolik</p>
                                    </div>
                                </div>
                                <!-- Errors list -->
                                <div v-if="result.errors && result.errors.length > 0" class="mt-4">
                                    <p class="text-sm font-medium text-slate-300 mb-2">Xatoliklar:</p>
                                    <div class="max-h-32 overflow-y-auto space-y-1">
                                        <div v-for="err in result.errors" :key="err.lead_id" class="text-xs text-red-400 bg-red-500/10 rounded px-2 py-1">
                                            {{ err.name }}: {{ err.error }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <button @click="close" class="px-6 py-2 bg-slate-700 text-white rounded-xl hover:bg-slate-600 transition-colors">
                                    Yopish
                                </button>
                            </div>
                        </div>

                        <div v-else>
                            <!-- Warning if some leads don't have phone -->
                            <div v-if="leadsWithoutPhone.length > 0" class="mb-4 p-3 bg-yellow-500/20 border border-yellow-500/30 rounded-xl">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <div>
                                        <p class="text-sm text-yellow-400">
                                            {{ leadsWithoutPhone.length }} ta lidda telefon raqami yo'q
                                        </p>
                                        <p class="text-xs text-yellow-400/70 mt-1">
                                            Faqat telefon raqamli {{ leadsWithPhone.length }} ta lidga SMS yuboriladi
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Error Message -->
                            <div v-if="error" class="mb-4 p-3 bg-red-500/20 border border-red-500/30 rounded-xl flex items-center">
                                <svg class="w-5 h-5 text-red-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-red-400 text-sm">{{ error }}</p>
                            </div>

                            <!-- Recipients preview -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-slate-300 mb-2">Qabul qiluvchilar ({{ leadsWithPhone.length }})</label>
                                <div class="flex flex-wrap gap-2 max-h-24 overflow-y-auto p-3 bg-slate-700/30 rounded-xl">
                                    <span
                                        v-for="lead in leadsWithPhone.slice(0, 20)"
                                        :key="lead.id"
                                        class="inline-flex items-center px-2 py-1 bg-slate-600 rounded-lg text-xs text-slate-300"
                                    >
                                        {{ lead.name }}
                                    </span>
                                    <span v-if="leadsWithPhone.length > 20" class="inline-flex items-center px-2 py-1 bg-slate-600 rounded-lg text-xs text-slate-400">
                                        +{{ leadsWithPhone.length - 20 }} ta
                                    </span>
                                </div>
                            </div>

                            <!-- Templates -->
                            <div v-if="templates.length > 0" class="mb-4">
                                <label class="block text-sm font-medium text-slate-300 mb-2">Shablon tanlash</label>
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
                                <label class="block text-sm font-medium text-slate-300 mb-2">Xabar matni</label>
                                <textarea
                                    v-model="message"
                                    rows="4"
                                    placeholder="SMS xabarini yozing... ({name}, {phone}, {company}, {email} o'zgaruvchilarini ishlatishingiz mumkin)"
                                    class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 resize-none"
                                ></textarea>
                                <div class="flex justify-between mt-2 text-xs">
                                    <div class="flex items-center gap-3">
                                        <span :class="smsInfo.isUnicode ? 'text-yellow-400' : 'text-slate-500'">
                                            {{ smsInfo.isUnicode ? 'Unicode (kirill)' : 'Latin' }}
                                        </span>
                                        <span class="text-slate-500">
                                            {{ smsInfo.chars }} belgi | {{ smsInfo.parts }} qism
                                        </span>
                                    </div>
                                    <span class="text-teal-400 font-medium">
                                        Jami: ~{{ totalSms }} SMS
                                    </span>
                                </div>
                            </div>

                            <!-- Placeholders info -->
                            <div class="mb-6 p-3 bg-slate-700/30 rounded-xl">
                                <p class="text-xs text-slate-400 mb-2">O'zgaruvchilar:</p>
                                <div class="flex flex-wrap gap-2">
                                    <code class="px-2 py-0.5 bg-slate-600 rounded text-teal-400 text-xs">{name}</code>
                                    <code class="px-2 py-0.5 bg-slate-600 rounded text-teal-400 text-xs">{phone}</code>
                                    <code class="px-2 py-0.5 bg-slate-600 rounded text-teal-400 text-xs">{company}</code>
                                    <code class="px-2 py-0.5 bg-slate-600 rounded text-teal-400 text-xs">{email}</code>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-end gap-3">
                                <button
                                    @click="close"
                                    class="px-4 py-2 bg-slate-700 text-slate-300 rounded-xl hover:bg-slate-600 transition-colors"
                                >
                                    Bekor qilish
                                </button>
                                <button
                                    @click="sendBulkSms"
                                    :disabled="isSending || !message.trim() || leadsWithPhone.length === 0"
                                    class="px-6 py-2 bg-gradient-to-r from-teal-500 to-cyan-600 text-white font-medium rounded-xl hover:from-teal-600 hover:to-cyan-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isSending" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Yuborilmoqda...
                                    </span>
                                    <span v-else>{{ leadsWithPhone.length }} ta lidga SMS yuborish</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
