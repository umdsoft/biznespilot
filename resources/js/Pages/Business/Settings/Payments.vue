<script setup>
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { ref, computed } from 'vue';
import { useI18n } from '@/i18n';
import { useConfirm } from '@/composables/useConfirm';

const { t } = useI18n();
const { confirm } = useConfirm();

const props = defineProps({
    paymeAccount: Object,
    clickAccount: Object,
    recentTransactions: Array,
    stats: Object,
    webhookUrls: Object,
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

const activeTab = ref(props.paymeAccount ? 'payme' : (props.clickAccount ? 'click' : 'payme'));
const isConnecting = ref(false);
const isDisconnecting = ref(false);
const copiedUrl = ref(null);

// Payme Connect form
const paymeForm = useForm({
    merchant_id: '',
    merchant_key: '',
    is_test_mode: false,
});

// Click Connect form
const clickForm = useForm({
    service_id: '',
    merchant_user_id: '',
    secret_key: '',
    is_test_mode: false,
});

const connectPayme = () => {
    isConnecting.value = true;
    paymeForm.post(route('business.settings.payments.payme.connect'), {
        onFinish: () => {
            isConnecting.value = false;
            if (!paymeForm.hasErrors) {
                paymeForm.reset();
            }
        },
    });
};

const disconnectPayme = async () => {
    if (!await confirm({ title: 'Payme uzish', message: 'Payme integratsiyasini o\'chirmoqchimisiz?', type: 'danger', confirmText: 'O\'chirish' })) return;

    isDisconnecting.value = true;
    router.post(route('business.settings.payments.payme.disconnect'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isDisconnecting.value = false;
        },
    });
};

const connectClick = () => {
    isConnecting.value = true;
    clickForm.post(route('business.settings.payments.click.connect'), {
        onFinish: () => {
            isConnecting.value = false;
            if (!clickForm.hasErrors) {
                clickForm.reset();
            }
        },
    });
};

const disconnectClick = async () => {
    if (!await confirm({ title: 'Click uzish', message: 'Click integratsiyasini o\'chirmoqchimisiz?', type: 'danger', confirmText: 'O\'chirish' })) return;

    isDisconnecting.value = true;
    router.post(route('business.settings.payments.click.disconnect'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isDisconnecting.value = false;
        },
    });
};

const copyWebhookUrl = (type) => {
    const url = props.webhookUrls[type];
    navigator.clipboard.writeText(url);
    copiedUrl.value = type;
    setTimeout(() => {
        copiedUrl.value = null;
    }, 2000);
};

const formatAmount = (amount) => {
    return new Intl.NumberFormat('uz-UZ').format(amount) + ' UZS';
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-400/10',
        processing: 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-400/10',
        completed: 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-400/10',
        cancelled: 'text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-400/10',
        failed: 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-400/10',
        refunded: 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-400/10',
    };
    return colors[status] || colors.pending;
};
</script>

<template>
    <Head title="To'lov Tizimlari" />

    <BusinessLayout>
        <div class="py-6 lg:py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6">
                <!-- Back Button -->
                <Link
                    :href="route('business.settings.index')"
                    class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-6 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Sozlamalarga qaytish
                </Link>

                <!-- Flash Messages -->
                <div v-if="flash.success" class="mb-4 p-3 bg-green-50 dark:bg-green-400/10 border border-green-200 dark:border-green-400/20 rounded-lg flex items-center">
                    <svg class="w-4 h-4 text-green-600 dark:text-green-400 mr-2.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <p class="text-sm text-green-700 dark:text-green-400">{{ flash.success }}</p>
                </div>

                <div v-if="flash.error" class="mb-4 p-3 bg-red-50 dark:bg-red-400/10 border border-red-200 dark:border-red-400/20 rounded-lg flex items-center">
                    <svg class="w-4 h-4 text-red-600 dark:text-red-400 mr-2.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm text-red-700 dark:text-red-400">{{ flash.error }}</p>
                </div>

                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">To'lov Tizimlari</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Payme va Click orqali to'lovlarni qabul qiling</p>
                </div>

                <!-- Statistics -->
                <div v-if="stats" class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3.5">
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ stats.total_transactions }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Jami tranzaksiyalar</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3.5">
                        <p class="text-lg font-semibold text-green-600 dark:text-green-400">{{ stats.completed_transactions }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">To'langan</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3.5">
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ formatAmount(stats.total_revenue) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Jami daromad</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3.5">
                        <p class="text-lg font-semibold text-yellow-600 dark:text-yellow-400">{{ formatAmount(stats.pending_amount) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kutilmoqda</p>
                    </div>
                </div>

                <!-- Provider Tabs -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg mb-6">
                    <div class="flex gap-6 border-b border-gray-200 dark:border-gray-700 px-6">
                        <button
                            @click="activeTab = 'payme'"
                            :class="[
                                'py-3 text-sm font-medium border-b-2 transition-colors -mb-px',
                                activeTab === 'payme'
                                    ? 'text-gray-900 dark:text-white border-gray-900 dark:border-white'
                                    : 'text-gray-500 dark:text-gray-400 border-transparent hover:text-gray-900 dark:hover:text-white'
                            ]"
                        >
                            <span class="flex items-center gap-2">
                                Payme
                                <span v-if="paymeAccount?.is_active" class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                            </span>
                        </button>
                        <button
                            @click="activeTab = 'click'"
                            :class="[
                                'py-3 text-sm font-medium border-b-2 transition-colors -mb-px',
                                activeTab === 'click'
                                    ? 'text-gray-900 dark:text-white border-gray-900 dark:border-white'
                                    : 'text-gray-500 dark:text-gray-400 border-transparent hover:text-gray-900 dark:hover:text-white'
                            ]"
                        >
                            <span class="flex items-center gap-2">
                                Click
                                <span v-if="clickAccount?.is_active" class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                            </span>
                        </button>
                    </div>

                    <!-- Payme Tab Content -->
                    <div v-if="activeTab === 'payme'" class="p-6">
                        <!-- Payme Not Connected -->
                        <div v-if="!paymeAccount || !paymeAccount.is_active">
                            <div class="mb-6">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Payme ulash</h3>
                                <div class="space-y-4">
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0 w-6 h-6 bg-gray-900 dark:bg-white rounded-full flex items-center justify-center text-white dark:text-gray-900 font-semibold text-xs">1</div>
                                        <div class="flex-1">
                                            <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">Payme Business kabinetiga kiring</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                <a href="https://business.payme.uz" target="_blank" class="text-gray-900 dark:text-white hover:underline">business.payme.uz</a>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0 w-6 h-6 bg-gray-900 dark:bg-white rounded-full flex items-center justify-center text-white dark:text-gray-900 font-semibold text-xs">2</div>
                                        <div class="flex-1">
                                            <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">Merchant ID va Key ni oling</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Sozlamalar bo'limidan API kalitlarini nusxalang</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0 w-6 h-6 bg-gray-900 dark:bg-white rounded-full flex items-center justify-center text-white dark:text-gray-900 font-semibold text-xs">3</div>
                                        <div class="flex-1">
                                            <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">Webhook URL ni sozlang</p>
                                            <div class="mt-1.5 flex items-center gap-2">
                                                <code class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 px-2.5 py-1 rounded-lg text-gray-700 dark:text-gray-300 text-xs flex-1 overflow-x-auto">{{ webhookUrls.payme }}</code>
                                                <button @click="copyWebhookUrl('payme')" class="px-2.5 py-1 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs transition-colors">
                                                    {{ copiedUrl === 'payme' ? 'Nusxalandi!' : 'Nusxalash' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form @submit.prevent="connectPayme" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Merchant ID</label>
                                    <input
                                        v-model="paymeForm.merchant_id"
                                        type="text"
                                        required
                                        placeholder="5e730e8e0b852a417aa49ceb"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p v-if="paymeForm.errors.merchant_id" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ paymeForm.errors.merchant_id }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Merchant Key (Secret)</label>
                                    <input
                                        v-model="paymeForm.merchant_key"
                                        type="password"
                                        required
                                        placeholder="Payme secret key"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p v-if="paymeForm.errors.merchant_key" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ paymeForm.errors.merchant_key }}</p>
                                </div>

                                <div class="flex items-center gap-2.5">
                                    <input
                                        v-model="paymeForm.is_test_mode"
                                        type="checkbox"
                                        id="payme_test_mode"
                                        class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <label for="payme_test_mode" class="text-sm text-gray-700 dark:text-gray-300">Test rejimi (sandbox)</label>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="isConnecting || paymeForm.processing"
                                    class="w-full py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isConnecting || paymeForm.processing" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Ulanmoqda...
                                    </span>
                                    <span v-else>Ulash</span>
                                </button>
                            </form>
                        </div>

                        <!-- Payme Connected -->
                        <div v-else>
                            <div class="flex items-center justify-between mb-5">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Payme Ulangan</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ paymeAccount.is_test_mode ? 'Test rejimi' : 'Production rejimi' }}</p>
                                    </div>
                                </div>
                                <button
                                    @click="disconnectPayme"
                                    :disabled="isDisconnecting"
                                    class="text-sm text-red-600 dark:text-red-400 hover:underline disabled:opacity-50"
                                >
                                    <span v-if="isDisconnecting">Uzilmoqda...</span>
                                    <span v-else>Uzish</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3.5">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Merchant ID</p>
                                    <p class="text-sm font-mono text-gray-900 dark:text-white">{{ paymeAccount.merchant_id }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3.5">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Oxirgi tranzaksiya</p>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ paymeAccount.last_transaction_at || '-' }}</p>
                                </div>
                            </div>

                            <div class="mt-3 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3.5">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Webhook URL</p>
                                <div class="flex items-center gap-2">
                                    <code class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-2.5 py-1.5 rounded-lg text-gray-700 dark:text-gray-300 text-xs flex-1 overflow-x-auto">{{ webhookUrls.payme }}</code>
                                    <button @click="copyWebhookUrl('payme')" class="px-2.5 py-1.5 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs whitespace-nowrap transition-colors">
                                        {{ copiedUrl === 'payme' ? 'Nusxalandi!' : 'Nusxalash' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Click Tab Content -->
                    <div v-if="activeTab === 'click'" class="p-6">
                        <!-- Click Not Connected -->
                        <div v-if="!clickAccount || !clickAccount.is_active">
                            <div class="mb-6">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Click ulash</h3>
                                <div class="space-y-4">
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0 w-6 h-6 bg-gray-900 dark:bg-white rounded-full flex items-center justify-center text-white dark:text-gray-900 font-semibold text-xs">1</div>
                                        <div class="flex-1">
                                            <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">Click Merchant kabinetiga kiring</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                <a href="https://merchant.click.uz" target="_blank" class="text-gray-900 dark:text-white hover:underline">merchant.click.uz</a>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0 w-6 h-6 bg-gray-900 dark:bg-white rounded-full flex items-center justify-center text-white dark:text-gray-900 font-semibold text-xs">2</div>
                                        <div class="flex-1">
                                            <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">Service ID, User ID va Secret Key ni oling</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Sozlamalar > API bo'limidan</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0 w-6 h-6 bg-gray-900 dark:bg-white rounded-full flex items-center justify-center text-white dark:text-gray-900 font-semibold text-xs">3</div>
                                        <div class="flex-1">
                                            <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">Webhook URL ni sozlang</p>
                                            <div class="mt-1.5 flex items-center gap-2">
                                                <code class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 px-2.5 py-1 rounded-lg text-gray-700 dark:text-gray-300 text-xs flex-1 overflow-x-auto">{{ webhookUrls.click }}</code>
                                                <button @click="copyWebhookUrl('click')" class="px-2.5 py-1 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs transition-colors">
                                                    {{ copiedUrl === 'click' ? 'Nusxalandi!' : 'Nusxalash' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form @submit.prevent="connectClick" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Service ID</label>
                                    <input
                                        v-model="clickForm.service_id"
                                        type="text"
                                        required
                                        placeholder="12345"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p v-if="clickForm.errors.service_id" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ clickForm.errors.service_id }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Merchant User ID</label>
                                    <input
                                        v-model="clickForm.merchant_user_id"
                                        type="text"
                                        required
                                        placeholder="12345"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p v-if="clickForm.errors.merchant_user_id" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ clickForm.errors.merchant_user_id }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Secret Key</label>
                                    <input
                                        v-model="clickForm.secret_key"
                                        type="password"
                                        required
                                        placeholder="Click secret key"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 text-sm focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p v-if="clickForm.errors.secret_key" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ clickForm.errors.secret_key }}</p>
                                </div>

                                <div class="flex items-center gap-2.5">
                                    <input
                                        v-model="clickForm.is_test_mode"
                                        type="checkbox"
                                        id="click_test_mode"
                                        class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <label for="click_test_mode" class="text-sm text-gray-700 dark:text-gray-300">Test rejimi</label>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="isConnecting || clickForm.processing"
                                    class="w-full py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isConnecting || clickForm.processing" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Ulanmoqda...
                                    </span>
                                    <span v-else>Ulash</span>
                                </button>
                            </form>
                        </div>

                        <!-- Click Connected -->
                        <div v-else>
                            <div class="flex items-center justify-between mb-5">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Click Ulangan</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ clickAccount.is_test_mode ? 'Test rejimi' : 'Production rejimi' }}</p>
                                    </div>
                                </div>
                                <button
                                    @click="disconnectClick"
                                    :disabled="isDisconnecting"
                                    class="text-sm text-red-600 dark:text-red-400 hover:underline disabled:opacity-50"
                                >
                                    <span v-if="isDisconnecting">Uzilmoqda...</span>
                                    <span v-else>Uzish</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3.5">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Service ID</p>
                                    <p class="text-sm font-mono text-gray-900 dark:text-white">{{ clickAccount.service_id }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3.5">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">User ID</p>
                                    <p class="text-sm font-mono text-gray-900 dark:text-white">{{ clickAccount.merchant_user_id }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3.5">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Oxirgi tranzaksiya</p>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ clickAccount.last_transaction_at || '-' }}</p>
                                </div>
                            </div>

                            <div class="mt-3 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3.5">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Webhook URL (SHOP-API)</p>
                                <div class="flex items-center gap-2">
                                    <code class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-2.5 py-1.5 rounded-lg text-gray-700 dark:text-gray-300 text-xs flex-1 overflow-x-auto">{{ webhookUrls.click }}</code>
                                    <button @click="copyWebhookUrl('click')" class="px-2.5 py-1.5 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs whitespace-nowrap transition-colors">
                                        {{ copiedUrl === 'click' ? 'Nusxalandi!' : 'Nusxalash' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div v-if="recentTransactions && recentTransactions.length > 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Oxirgi tranzaksiyalar</h3>
                        <Link :href="route('business.payments.transactions')" class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                            Barchasini ko'rish
                        </Link>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div
                            v-for="transaction in recentTransactions"
                            :key="transaction.id"
                            class="px-5 py-3.5 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 w-6">
                                    {{ transaction.provider === 'payme' ? 'PM' : 'CL' }}
                                </span>
                                <div>
                                    <p class="text-sm text-gray-900 dark:text-white font-medium">{{ transaction.lead?.name || transaction.order_id }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ transaction.created_at }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-900 dark:text-white font-medium">{{ transaction.formatted_amount }}</p>
                                <span :class="['text-xs px-1.5 py-0.5 rounded', getStatusColor(transaction.status)]">
                                    {{ transaction.status_label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
