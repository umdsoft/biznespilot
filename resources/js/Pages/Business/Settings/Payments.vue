<script setup>
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { ref, computed } from 'vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

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

const disconnectPayme = () => {
    if (!confirm('Payme integratsiyasini o\'chirmoqchimisiz?')) return;

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

const disconnectClick = () => {
    if (!confirm('Click integratsiyasini o\'chirmoqchimisiz?')) return;

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
        pending: 'text-yellow-500 bg-yellow-500/10',
        processing: 'text-blue-500 bg-blue-500/10',
        completed: 'text-green-500 bg-green-500/10',
        cancelled: 'text-gray-500 bg-gray-500/10',
        failed: 'text-red-500 bg-red-500/10',
        refunded: 'text-purple-500 bg-purple-500/10',
    };
    return colors[status] || colors.pending;
};
</script>

<template>
    <Head title="To'lov Tizimlari" />

    <BusinessLayout>
        <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 py-12">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Back Button -->
                <Link
                    :href="route('business.settings.index')"
                    class="inline-flex items-center text-slate-400 hover:text-white mb-8 transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Sozlamalarga qaytish
                </Link>

                <!-- Flash Messages -->
                <div v-if="flash.success" class="mb-6 p-4 bg-green-500/20 border border-green-500/30 rounded-xl flex items-center">
                    <svg class="w-5 h-5 text-green-400 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <p class="text-green-400">{{ flash.success }}</p>
                </div>

                <div v-if="flash.error" class="mb-6 p-4 bg-red-500/20 border border-red-500/30 rounded-xl flex items-center">
                    <svg class="w-5 h-5 text-red-400 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-red-400">{{ flash.error }}</p>
                </div>

                <!-- Header -->
                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl p-8 mb-8">
                    <div class="flex items-center">
                        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center mr-6 shadow-lg">
                            <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">To'lov Tizimlari</h1>
                            <p class="text-slate-400 mt-2">Payme va Click orqali to'lovlarni qabul qiling</p>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div v-if="stats" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-white">{{ stats.total_transactions }}</p>
                        <p class="text-sm text-slate-400">Jami tranzaksiyalar</p>
                    </div>
                    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-green-400">{{ stats.completed_transactions }}</p>
                        <p class="text-sm text-slate-400">To'langan</p>
                    </div>
                    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-emerald-400">{{ formatAmount(stats.total_revenue) }}</p>
                        <p class="text-sm text-slate-400">Jami daromad</p>
                    </div>
                    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-yellow-400">{{ formatAmount(stats.pending_amount) }}</p>
                        <p class="text-sm text-slate-400">Kutilmoqda</p>
                    </div>
                </div>

                <!-- Provider Tabs -->
                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl overflow-hidden mb-8">
                    <div class="flex border-b border-slate-700/50">
                        <button
                            @click="activeTab = 'payme'"
                            :class="[
                                'flex-1 px-6 py-4 text-center font-medium transition-colors',
                                activeTab === 'payme'
                                    ? 'text-cyan-400 border-b-2 border-cyan-400 bg-slate-700/30'
                                    : 'text-slate-400 hover:text-white'
                            ]"
                        >
                            <div class="flex items-center justify-center gap-3">
                                <div :class="[
                                    'w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs',
                                    activeTab === 'payme' ? 'bg-cyan-500 text-white' : 'bg-slate-600 text-white'
                                ]">
                                    PM
                                </div>
                                <span>Payme</span>
                                <span v-if="paymeAccount?.is_active" class="w-2 h-2 bg-green-400 rounded-full"></span>
                            </div>
                        </button>
                        <button
                            @click="activeTab = 'click'"
                            :class="[
                                'flex-1 px-6 py-4 text-center font-medium transition-colors',
                                activeTab === 'click'
                                    ? 'text-blue-400 border-b-2 border-blue-400 bg-slate-700/30'
                                    : 'text-slate-400 hover:text-white'
                            ]"
                        >
                            <div class="flex items-center justify-center gap-3">
                                <div :class="[
                                    'w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs',
                                    activeTab === 'click' ? 'bg-blue-500 text-white' : 'bg-slate-600 text-white'
                                ]">
                                    CL
                                </div>
                                <span>Click</span>
                                <span v-if="clickAccount?.is_active" class="w-2 h-2 bg-green-400 rounded-full"></span>
                            </div>
                        </button>
                    </div>

                    <!-- Payme Tab Content -->
                    <div v-if="activeTab === 'payme'" class="p-8">
                        <!-- Payme Not Connected -->
                        <div v-if="!paymeAccount || !paymeAccount.is_active">
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-white mb-4">Payme ulash</h3>
                                <div class="space-y-4">
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-cyan-500 rounded-full flex items-center justify-center text-white font-bold text-sm">1</div>
                                        <div class="flex-1">
                                            <p class="text-slate-300 font-medium">Payme Business kabinetiga kiring</p>
                                            <p class="text-slate-500 text-sm mt-1">
                                                <a href="https://business.payme.uz" target="_blank" class="text-cyan-400 hover:text-cyan-300 underline">business.payme.uz</a>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-cyan-500 rounded-full flex items-center justify-center text-white font-bold text-sm">2</div>
                                        <div class="flex-1">
                                            <p class="text-slate-300 font-medium">Merchant ID va Key ni oling</p>
                                            <p class="text-slate-500 text-sm mt-1">Sozlamalar bo'limidan API kalitlarini nusxalang</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-cyan-500 rounded-full flex items-center justify-center text-white font-bold text-sm">3</div>
                                        <div class="flex-1">
                                            <p class="text-slate-300 font-medium">Webhook URL ni sozlang</p>
                                            <div class="mt-2 flex items-center gap-2">
                                                <code class="bg-slate-700/50 px-3 py-1 rounded text-cyan-400 text-sm flex-1">{{ webhookUrls.payme }}</code>
                                                <button @click="copyWebhookUrl('payme')" class="px-3 py-1 bg-slate-600 rounded hover:bg-slate-500 text-white text-sm">
                                                    {{ copiedUrl === 'payme' ? 'Nusxalandi!' : 'Nusxalash' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form @submit.prevent="connectPayme" class="max-w-md mx-auto space-y-5">
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">Merchant ID</label>
                                    <input
                                        v-model="paymeForm.merchant_id"
                                        type="text"
                                        required
                                        placeholder="5e730e8e0b852a417aa49ceb"
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                                    />
                                    <p v-if="paymeForm.errors.merchant_id" class="mt-1 text-sm text-red-400">{{ paymeForm.errors.merchant_id }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">Merchant Key (Secret)</label>
                                    <input
                                        v-model="paymeForm.merchant_key"
                                        type="password"
                                        required
                                        placeholder="Payme secret key"
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                                    />
                                    <p v-if="paymeForm.errors.merchant_key" class="mt-1 text-sm text-red-400">{{ paymeForm.errors.merchant_key }}</p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <input
                                        v-model="paymeForm.is_test_mode"
                                        type="checkbox"
                                        id="payme_test_mode"
                                        class="w-4 h-4 rounded border-slate-600 bg-slate-700/50 text-cyan-500 focus:ring-cyan-500"
                                    />
                                    <label for="payme_test_mode" class="text-sm text-slate-300">Test rejimi (sandbox)</label>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="isConnecting || paymeForm.processing"
                                    class="w-full py-3 bg-gradient-to-r from-cyan-500 to-teal-600 text-white font-semibold rounded-xl hover:from-cyan-600 hover:to-teal-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isConnecting || paymeForm.processing" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
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
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center mr-4">
                                        <svg class="w-6 h-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-lg font-semibold text-white">Payme Ulangan</p>
                                        <p class="text-sm text-slate-400">{{ paymeAccount.is_test_mode ? 'Test rejimi' : 'Production rejimi' }}</p>
                                    </div>
                                </div>
                                <button
                                    @click="disconnectPayme"
                                    :disabled="isDisconnecting"
                                    class="px-4 py-2 bg-red-500/20 text-red-400 rounded-xl hover:bg-red-500/30 transition-colors disabled:opacity-50"
                                >
                                    <span v-if="isDisconnecting">Uzilmoqda...</span>
                                    <span v-else>Uzish</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-slate-700/30 rounded-xl p-4">
                                    <p class="text-sm text-slate-400 mb-1">Merchant ID</p>
                                    <p class="text-lg font-mono text-white">{{ paymeAccount.merchant_id }}</p>
                                </div>
                                <div class="bg-slate-700/30 rounded-xl p-4">
                                    <p class="text-sm text-slate-400 mb-1">Oxirgi tranzaksiya</p>
                                    <p class="text-lg text-white">{{ paymeAccount.last_transaction_at || '-' }}</p>
                                </div>
                            </div>

                            <div class="mt-4 p-4 bg-slate-700/30 rounded-xl">
                                <p class="text-sm text-slate-400 mb-2">Webhook URL</p>
                                <div class="flex items-center gap-2">
                                    <code class="bg-slate-800/50 px-3 py-2 rounded text-cyan-400 text-sm flex-1 overflow-x-auto">{{ webhookUrls.payme }}</code>
                                    <button @click="copyWebhookUrl('payme')" class="px-3 py-2 bg-slate-600 rounded hover:bg-slate-500 text-white text-sm whitespace-nowrap">
                                        {{ copiedUrl === 'payme' ? 'Nusxalandi!' : 'Nusxalash' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Click Tab Content -->
                    <div v-if="activeTab === 'click'" class="p-8">
                        <!-- Click Not Connected -->
                        <div v-if="!clickAccount || !clickAccount.is_active">
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-white mb-4">Click ulash</h3>
                                <div class="space-y-4">
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-sm">1</div>
                                        <div class="flex-1">
                                            <p class="text-slate-300 font-medium">Click Merchant kabinetiga kiring</p>
                                            <p class="text-slate-500 text-sm mt-1">
                                                <a href="https://merchant.click.uz" target="_blank" class="text-blue-400 hover:text-blue-300 underline">merchant.click.uz</a>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-sm">2</div>
                                        <div class="flex-1">
                                            <p class="text-slate-300 font-medium">Service ID, User ID va Secret Key ni oling</p>
                                            <p class="text-slate-500 text-sm mt-1">Sozlamalar > API bo'limidan</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-sm">3</div>
                                        <div class="flex-1">
                                            <p class="text-slate-300 font-medium">Webhook URL ni sozlang</p>
                                            <div class="mt-2 flex items-center gap-2">
                                                <code class="bg-slate-700/50 px-3 py-1 rounded text-blue-400 text-sm flex-1">{{ webhookUrls.click }}</code>
                                                <button @click="copyWebhookUrl('click')" class="px-3 py-1 bg-slate-600 rounded hover:bg-slate-500 text-white text-sm">
                                                    {{ copiedUrl === 'click' ? 'Nusxalandi!' : 'Nusxalash' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form @submit.prevent="connectClick" class="max-w-md mx-auto space-y-5">
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">Service ID</label>
                                    <input
                                        v-model="clickForm.service_id"
                                        type="text"
                                        required
                                        placeholder="12345"
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                    <p v-if="clickForm.errors.service_id" class="mt-1 text-sm text-red-400">{{ clickForm.errors.service_id }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">Merchant User ID</label>
                                    <input
                                        v-model="clickForm.merchant_user_id"
                                        type="text"
                                        required
                                        placeholder="12345"
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                    <p v-if="clickForm.errors.merchant_user_id" class="mt-1 text-sm text-red-400">{{ clickForm.errors.merchant_user_id }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">Secret Key</label>
                                    <input
                                        v-model="clickForm.secret_key"
                                        type="password"
                                        required
                                        placeholder="Click secret key"
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                    <p v-if="clickForm.errors.secret_key" class="mt-1 text-sm text-red-400">{{ clickForm.errors.secret_key }}</p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <input
                                        v-model="clickForm.is_test_mode"
                                        type="checkbox"
                                        id="click_test_mode"
                                        class="w-4 h-4 rounded border-slate-600 bg-slate-700/50 text-blue-500 focus:ring-blue-500"
                                    />
                                    <label for="click_test_mode" class="text-sm text-slate-300">Test rejimi</label>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="isConnecting || clickForm.processing"
                                    class="w-full py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-600 hover:to-indigo-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isConnecting || clickForm.processing" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
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
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center mr-4">
                                        <svg class="w-6 h-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-lg font-semibold text-white">Click Ulangan</p>
                                        <p class="text-sm text-slate-400">{{ clickAccount.is_test_mode ? 'Test rejimi' : 'Production rejimi' }}</p>
                                    </div>
                                </div>
                                <button
                                    @click="disconnectClick"
                                    :disabled="isDisconnecting"
                                    class="px-4 py-2 bg-red-500/20 text-red-400 rounded-xl hover:bg-red-500/30 transition-colors disabled:opacity-50"
                                >
                                    <span v-if="isDisconnecting">Uzilmoqda...</span>
                                    <span v-else>Uzish</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-slate-700/30 rounded-xl p-4">
                                    <p class="text-sm text-slate-400 mb-1">Service ID</p>
                                    <p class="text-lg font-mono text-white">{{ clickAccount.service_id }}</p>
                                </div>
                                <div class="bg-slate-700/30 rounded-xl p-4">
                                    <p class="text-sm text-slate-400 mb-1">User ID</p>
                                    <p class="text-lg font-mono text-white">{{ clickAccount.merchant_user_id }}</p>
                                </div>
                                <div class="bg-slate-700/30 rounded-xl p-4">
                                    <p class="text-sm text-slate-400 mb-1">Oxirgi tranzaksiya</p>
                                    <p class="text-lg text-white">{{ clickAccount.last_transaction_at || '-' }}</p>
                                </div>
                            </div>

                            <div class="mt-4 p-4 bg-slate-700/30 rounded-xl">
                                <p class="text-sm text-slate-400 mb-2">Webhook URL (SHOP-API)</p>
                                <div class="flex items-center gap-2">
                                    <code class="bg-slate-800/50 px-3 py-2 rounded text-blue-400 text-sm flex-1 overflow-x-auto">{{ webhookUrls.click }}</code>
                                    <button @click="copyWebhookUrl('click')" class="px-3 py-2 bg-slate-600 rounded hover:bg-slate-500 text-white text-sm whitespace-nowrap">
                                        {{ copiedUrl === 'click' ? 'Nusxalandi!' : 'Nusxalash' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div v-if="recentTransactions && recentTransactions.length > 0" class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-700/50 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-white">Oxirgi tranzaksiyalar</h3>
                        <Link :href="route('business.payments.transactions')" class="text-sm text-blue-400 hover:text-blue-300">
                            Barchasini ko'rish
                        </Link>
                    </div>
                    <div class="divide-y divide-slate-700/50">
                        <div
                            v-for="transaction in recentTransactions"
                            :key="transaction.id"
                            class="px-6 py-4 flex items-center justify-between hover:bg-slate-700/20 transition-colors"
                        >
                            <div class="flex items-center gap-4">
                                <div :class="[
                                    'w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs',
                                    transaction.provider === 'payme' ? 'bg-cyan-500/20 text-cyan-400' : 'bg-blue-500/20 text-blue-400'
                                ]">
                                    {{ transaction.provider === 'payme' ? 'PM' : 'CL' }}
                                </div>
                                <div>
                                    <p class="text-white font-medium">{{ transaction.lead?.name || transaction.order_id }}</p>
                                    <p class="text-sm text-slate-400">{{ transaction.created_at }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-white font-semibold">{{ transaction.formatted_amount }}</p>
                                <span :class="['text-xs px-2 py-1 rounded-full', getStatusColor(transaction.status)]">
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
