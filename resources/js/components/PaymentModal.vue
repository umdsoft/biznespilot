<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" @click.self="close">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="close" />

                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg mx-4 z-10">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                                    <CreditCardIcon class="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">To'lov qabul qilish</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ lead?.name }}</p>
                                </div>
                            </div>
                            <button @click="close" class="p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Content -->
                        <div class="p-6">
                            <!-- Loading state -->
                            <div v-if="loading" class="text-center py-8">
                                <div class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                                <p class="text-gray-500 dark:text-gray-400 mt-4">Yuklanmoqda...</p>
                            </div>

                            <!-- No providers configured -->
                            <div v-else-if="!hasProviders" class="text-center py-8">
                                <ExclamationTriangleIcon class="w-12 h-12 text-yellow-500 mx-auto mb-4" />
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">To'lov tizimlari sozlanmagan</h4>
                                <p class="text-gray-500 dark:text-gray-400 mb-4">Payme yoki Click sozlamalarini sozlash kerak</p>
                                <a :href="route('business.settings.payments')" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                                    <Cog6ToothIcon class="w-4 h-4" />
                                    Sozlamalarga o'tish
                                </a>
                            </div>

                            <!-- Main content -->
                            <div v-else>
                                <!-- Step 1: Amount & Provider Selection -->
                                <div v-if="step === 1">
                                    <!-- Amount input -->
                                    <div class="mb-6">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            To'lov summasi (so'm)
                                        </label>
                                        <div class="relative">
                                            <input
                                                v-model.number="amount"
                                                type="number"
                                                min="1000"
                                                step="1000"
                                                placeholder="100000"
                                                class="w-full px-4 py-3 pl-12 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            />
                                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                                <CurrencyDollarIcon class="w-5 h-5" />
                                            </div>
                                        </div>
                                        <p v-if="lead?.estimated_value" class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                            Taxminiy qiymat: {{ formatCurrency(lead.estimated_value) }}
                                        </p>
                                    </div>

                                    <!-- Provider selection -->
                                    <div class="mb-6">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                            To'lov tizimini tanlang
                                        </label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <!-- Payme -->
                                            <button
                                                v-if="providers.payme"
                                                @click="selectedProvider = 'payme'"
                                                :class="[
                                                    'relative p-4 rounded-xl border-2 transition-all',
                                                    selectedProvider === 'payme'
                                                        ? 'border-cyan-500 bg-cyan-50 dark:bg-cyan-900/20'
                                                        : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                                                ]"
                                            >
                                                <div class="flex flex-col items-center gap-2">
                                                    <div class="w-12 h-12 bg-cyan-500 rounded-xl flex items-center justify-center">
                                                        <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="currentColor">
                                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                                                        </svg>
                                                    </div>
                                                    <span class="font-semibold text-gray-900 dark:text-white">Payme</span>
                                                </div>
                                                <div v-if="selectedProvider === 'payme'" class="absolute top-2 right-2">
                                                    <CheckCircleIcon class="w-5 h-5 text-cyan-500" />
                                                </div>
                                            </button>

                                            <!-- Click -->
                                            <button
                                                v-if="providers.click"
                                                @click="selectedProvider = 'click'"
                                                :class="[
                                                    'relative p-4 rounded-xl border-2 transition-all',
                                                    selectedProvider === 'click'
                                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                                        : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                                                ]"
                                            >
                                                <div class="flex flex-col items-center gap-2">
                                                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                                                        <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="currentColor">
                                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                                                        </svg>
                                                    </div>
                                                    <span class="font-semibold text-gray-900 dark:text-white">Click</span>
                                                </div>
                                                <div v-if="selectedProvider === 'click'" class="absolute top-2 right-2">
                                                    <CheckCircleIcon class="w-5 h-5 text-blue-500" />
                                                </div>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Description (optional) -->
                                    <div class="mb-6">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Tavsif (ixtiyoriy)
                                        </label>
                                        <input
                                            v-model="description"
                                            type="text"
                                            placeholder="Xizmat yoki mahsulot nomi"
                                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        />
                                    </div>
                                </div>

                                <!-- Step 2: Payment Link Generated -->
                                <div v-else-if="step === 2">
                                    <div class="text-center mb-6">
                                        <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <CheckCircleIcon class="w-8 h-8 text-green-600 dark:text-green-400" />
                                        </div>
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">To'lov havolasi tayyor!</h4>
                                        <p class="text-gray-500 dark:text-gray-400">Havolani mijozga yuboring</p>
                                    </div>

                                    <!-- Payment info -->
                                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 mb-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Summa</span>
                                            <span class="font-bold text-green-600 dark:text-green-400">{{ formatCurrency(amount) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">To'lov tizimi</span>
                                            <span class="font-medium text-gray-900 dark:text-white capitalize">{{ selectedProvider }}</span>
                                        </div>
                                        <div v-if="paymentData?.order_id" class="flex items-center justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Buyurtma ID</span>
                                            <span class="font-mono text-sm text-gray-900 dark:text-white">{{ paymentData.order_id }}</span>
                                        </div>
                                    </div>

                                    <!-- Payment link -->
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            To'lov havolasi
                                        </label>
                                        <div class="flex gap-2">
                                            <input
                                                :value="paymentData?.payment_url"
                                                readonly
                                                class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white text-sm font-mono"
                                            />
                                            <button
                                                @click="copyLink"
                                                :class="[
                                                    'px-4 py-3 rounded-xl font-medium transition-colors flex items-center gap-2',
                                                    copied
                                                        ? 'bg-green-600 text-white'
                                                        : 'bg-blue-600 hover:bg-blue-700 text-white'
                                                ]"
                                            >
                                                <ClipboardIcon v-if="!copied" class="w-5 h-5" />
                                                <CheckIcon v-else class="w-5 h-5" />
                                                {{ copied ? 'Nusxalandi!' : 'Nusxalash' }}
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Quick share buttons -->
                                    <div class="flex gap-2">
                                        <a
                                            v-if="lead?.phone"
                                            :href="`https://wa.me/${lead.phone.replace(/\D/g, '')}?text=${encodeURIComponent(getShareMessage())}`"
                                            target="_blank"
                                            class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-medium rounded-xl transition-colors"
                                        >
                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                            </svg>
                                            WhatsApp
                                        </a>
                                        <a
                                            v-if="lead?.phone"
                                            :href="`https://t.me/share/url?url=${encodeURIComponent(paymentData?.payment_url)}&text=${encodeURIComponent('Tolov qilish uchun havola:')}`"
                                            target="_blank"
                                            class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-sky-500 hover:bg-sky-600 text-white font-medium rounded-xl transition-colors"
                                        >
                                            <PaperAirplaneIcon class="w-5 h-5" />
                                            Telegram
                                        </a>
                                    </div>
                                </div>

                                <!-- Error state -->
                                <div v-if="error" class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                                    <div class="flex items-start gap-3">
                                        <ExclamationTriangleIcon class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
                                        <div>
                                            <h4 class="font-medium text-red-800 dark:text-red-300">Xatolik yuz berdi</h4>
                                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ error }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div v-if="hasProviders && !loading" class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 rounded-b-2xl">
                            <button
                                v-if="step === 2"
                                @click="reset"
                                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                            >
                                Yangi to'lov
                            </button>
                            <button
                                @click="close"
                                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                            >
                                {{ step === 2 ? 'Yopish' : 'Bekor qilish' }}
                            </button>
                            <button
                                v-if="step === 1"
                                @click="createPaymentLink"
                                :disabled="!canCreate || creating"
                                class="px-6 py-2 bg-green-600 hover:bg-green-700 disabled:bg-green-400 disabled:cursor-not-allowed text-white font-medium rounded-lg transition-colors flex items-center gap-2"
                            >
                                <div v-if="creating" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                <LinkIcon v-else class="w-4 h-4" />
                                {{ creating ? 'Yaratilmoqda...' : 'Havola yaratish' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import {
    XMarkIcon,
    CreditCardIcon,
    CurrencyDollarIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    Cog6ToothIcon,
    ClipboardIcon,
    CheckIcon,
    LinkIcon,
    PaperAirplaneIcon,
} from '@heroicons/vue/24/outline';

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

const emit = defineEmits(['close', 'created']);

// State
const loading = ref(true);
const creating = ref(false);
const providers = ref({ payme: false, click: false });
const step = ref(1);
const amount = ref(null);
const description = ref('');
const selectedProvider = ref(null);
const paymentData = ref(null);
const error = ref(null);
const copied = ref(false);

// Computed
const hasProviders = computed(() => providers.value.payme || providers.value.click);

const canCreate = computed(() => {
    return amount.value && amount.value >= 1000 && selectedProvider.value;
});

// Methods
const loadProviders = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await fetch(route('business.payments.providers'), {
            headers: { 'Accept': 'application/json' },
        });

        if (response.ok) {
            const data = await response.json();
            providers.value = data.providers || { payme: false, click: false };

            // Auto-select if only one provider
            if (providers.value.payme && !providers.value.click) {
                selectedProvider.value = 'payme';
            } else if (providers.value.click && !providers.value.payme) {
                selectedProvider.value = 'click';
            }
        }
    } catch (err) {
        console.error('Failed to load providers:', err);
        error.value = 'Provayderlarni yuklashda xatolik';
    } finally {
        loading.value = false;
    }
};

const createPaymentLink = async () => {
    if (!canCreate.value) return;

    creating.value = true;
    error.value = null;

    try {
        const response = await fetch(route('business.payments.lead.create-link', props.lead.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                provider: selectedProvider.value,
                amount: amount.value,
                description: description.value || `To'lov - ${props.lead.name}`,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            paymentData.value = data;
            step.value = 2;
            emit('created', data);
        } else {
            error.value = data.error || 'To\'lov havolasini yaratishda xatolik';
        }
    } catch (err) {
        console.error('Failed to create payment link:', err);
        error.value = 'Tarmoq xatosi yuz berdi';
    } finally {
        creating.value = false;
    }
};

const copyLink = async () => {
    if (!paymentData.value?.payment_url) return;

    try {
        await navigator.clipboard.writeText(paymentData.value.payment_url);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};

const getShareMessage = () => {
    return `Hurmatli ${props.lead?.name}!\n\nTo'lov qilish uchun havola:\n${paymentData.value?.payment_url}\n\nSumma: ${formatCurrency(amount.value)}`;
};

const formatCurrency = (value) => {
    if (!value) return "0 so'm";
    return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

const reset = () => {
    step.value = 1;
    paymentData.value = null;
    error.value = null;
    copied.value = false;
};

const close = () => {
    emit('close');
};

// Watch for show changes
watch(() => props.show, (newVal) => {
    if (newVal) {
        loadProviders();
        // Pre-fill amount from estimated_value
        if (props.lead?.estimated_value) {
            amount.value = props.lead.estimated_value;
        }
    } else {
        // Reset on close
        setTimeout(() => {
            reset();
            amount.value = null;
            description.value = '';
        }, 300);
    }
});

// Watch for lead changes
watch(() => props.lead, (newLead) => {
    if (newLead?.estimated_value && !amount.value) {
        amount.value = newLead.estimated_value;
    }
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
