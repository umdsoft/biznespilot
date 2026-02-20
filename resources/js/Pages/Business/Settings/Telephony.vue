<script setup>
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { ref, computed } from 'vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    pbxAccount: Object,
    sipuniAccount: Object,
    moiZvonkiAccount: Object,
    utelAccount: Object,
    stats: Object,
    webhookUrls: Object,
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

// Get provider from URL params
const getProviderFromUrl = () => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('provider');
};

const urlProvider = getProviderFromUrl();

const getInitialTab = () => {
    if (urlProvider === 'sipuni') return 'sipuni';
    if (urlProvider === 'pbx') return 'pbx';
    if (urlProvider === 'moizvonki') return 'moizvonki';
    if (urlProvider === 'utel') return 'utel';
    if (props.utelAccount) return 'utel';
    if (props.moiZvonkiAccount) return 'moizvonki';
    if (props.pbxAccount) return 'pbx';
    return 'utel'; // Default to UTEL (O'zbekiston)
};

const activeTab = ref(getInitialTab());
const singleProviderMode = computed(() => urlProvider === 'pbx' || urlProvider === 'sipuni' || urlProvider === 'moizvonki' || urlProvider === 'utel');

const isConnecting = ref(false);
const isDisconnecting = ref(false);
const isRefreshingBalance = ref(false);

const hasActiveProvider = computed(() => props.pbxAccount || props.sipuniAccount || props.moiZvonkiAccount || props.utelAccount);

// Check if currently selected tab's provider is connected
const currentTabConnected = computed(() => {
    switch (activeTab.value) {
        case 'pbx':
            return !!props.pbxAccount;
        case 'sipuni':
            return !!props.sipuniAccount;
        case 'moizvonki':
            return !!props.moiZvonkiAccount;
        case 'utel':
            return !!props.utelAccount;
        default:
            return false;
    }
});

// PBX Connect form
const pbxForm = useForm({
    api_url: '',
    api_key: '',
    api_secret: '',
    caller_id: '',
    extension: '',
});

// SipUni Connect form
const sipuniForm = useForm({
    api_key: '',
    api_secret: '',
    caller_id: '',
    extension: '',
});

// MoiZvonki Connect form
const moiZvonkiForm = useForm({
    email: '',
    api_url: '',
    api_key: '',
});

// UTEL Connect form (O'zbekiston)
const utelForm = useForm({
    subdomain: '',
    email: '',
    password: '',
});

const isSyncing = ref(false);
const isRefreshingUtelBalance = ref(false);
const isConfiguringWebhook = ref(false);
const webhookConfigured = ref(false);

const connectPbx = () => {
    isConnecting.value = true;
    pbxForm.post(route('integrations.telephony.pbx.connect'), {
        onFinish: () => {
            isConnecting.value = false;
            if (!pbxForm.hasErrors) {
                pbxForm.reset();
            }
        },
    });
};

const disconnectPbx = () => {
    if (!confirm('PBX integratsiyasini o\'chirmoqchimisiz?')) return;

    isDisconnecting.value = true;
    router.post(route('integrations.telephony.pbx.disconnect'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isDisconnecting.value = false;
        },
    });
};

const connectSipuni = () => {
    isConnecting.value = true;
    sipuniForm.post(route('integrations.telephony.sipuni.connect'), {
        onFinish: () => {
            isConnecting.value = false;
            if (!sipuniForm.hasErrors) {
                sipuniForm.reset();
            }
        },
    });
};

const disconnectSipuni = () => {
    if (!confirm('SipUni integratsiyasini o\'chirmoqchimisiz?')) return;

    isDisconnecting.value = true;
    router.post(route('integrations.telephony.sipuni.disconnect'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isDisconnecting.value = false;
        },
    });
};

const connectMoiZvonki = () => {
    isConnecting.value = true;
    moiZvonkiForm.post(route('integrations.telephony.moizvonki.connect'), {
        onFinish: () => {
            isConnecting.value = false;
            if (!moiZvonkiForm.hasErrors) {
                moiZvonkiForm.reset();
            }
        },
    });
};

const disconnectMoiZvonki = () => {
    if (!confirm('MoiZvonki integratsiyasini o\'chirmoqchimisiz?')) return;

    isDisconnecting.value = true;
    router.post(route('integrations.telephony.moizvonki.disconnect'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isDisconnecting.value = false;
        },
    });
};

const syncMoiZvonki = async () => {
    isSyncing.value = true;
    try {
        const response = await fetch(route('integrations.telephony.moizvonki.sync'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });
        const data = await response.json();
        if (data.success) {
            router.reload({ only: ['moiZvonkiAccount', 'stats'] });
        }
    } catch (error) {
        console.error('Failed to sync MoiZvonki:', error);
    } finally {
        isSyncing.value = false;
    }
};

// UTEL Methods
const connectUtel = () => {
    isConnecting.value = true;
    router.post(route('integrations.telephony.utel.connect'), {
        subdomain: utelForm.subdomain,
        email: utelForm.email,
        password: utelForm.password,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            utelForm.reset();
        },
        onError: (errors) => {
            utelForm.errors = errors;
        },
        onFinish: () => {
            isConnecting.value = false;
        },
    });
};

const disconnectUtel = () => {
    if (!confirm('UTEL integratsiyasini o\'chirmoqchimisiz?')) return;

    isDisconnecting.value = true;
    router.post(route('integrations.telephony.utel.disconnect'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isDisconnecting.value = false;
        },
    });
};

const syncUtel = async () => {
    isSyncing.value = true;
    try {
        const response = await fetch(route('integrations.telephony.utel.sync'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });
        const data = await response.json();
        if (data.success) {
            router.reload({ only: ['utelAccount', 'stats'] });
        }
    } catch (error) {
        console.error('Failed to sync UTEL:', error);
    } finally {
        isSyncing.value = false;
    }
};

const refreshUtelBalance = async () => {
    isRefreshingUtelBalance.value = true;
    try {
        const response = await fetch(route('integrations.telephony.utel.refresh-balance'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });
        const data = await response.json();
        if (data.success) {
            router.reload({ only: ['utelAccount'] });
        }
    } catch (error) {
        console.error('Failed to refresh UTEL balance:', error);
    } finally {
        isRefreshingUtelBalance.value = false;
    }
};

const configureUtelWebhook = async () => {
    isConfiguringWebhook.value = true;
    webhookConfigured.value = false;
    try {
        const response = await fetch(route('integrations.telephony.utel.configure-webhook'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });
        const data = await response.json();
        if (data.success) {
            webhookConfigured.value = true;
            setTimeout(() => {
                webhookConfigured.value = false;
            }, 5000);
        } else {
            console.error('Failed to configure webhook:', data.error);
            alert(data.error || 'Webhook sozlashda xatolik');
        }
    } catch (error) {
        console.error('Failed to configure UTEL webhook:', error);
        alert('Webhook sozlashda xatolik yuz berdi');
    } finally {
        isConfiguringWebhook.value = false;
    }
};

const copiedUrl = ref(null);

const copyWebhookUrl = (url) => {
    navigator.clipboard.writeText(url);
    copiedUrl.value = url;
    setTimeout(() => {
        copiedUrl.value = null;
    }, 2000);
};

const refreshBalance = async () => {
    isRefreshingBalance.value = true;
    try {
        const response = await fetch(route('integrations.telephony.refresh-balance'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });
        const data = await response.json();
        if (data.balance !== undefined) {
            router.reload({ only: ['pbxAccount', 'sipuniAccount'] });
        }
    } catch (error) {
        console.error('Failed to refresh balance:', error);
    } finally {
        isRefreshingBalance.value = false;
    }
};

const formatDate = (date) => {
    if (!date) return '-';
    return date;
};

const formatDuration = (seconds) => {
    if (!seconds) return '0:00';
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    return `${minutes}:${secs.toString().padStart(2, '0')}`;
};

const formatUtelBalance = (balance, currency = 'UZS') => {
    if (balance === null || balance === undefined) return '—';
    const formatted = new Intl.NumberFormat('uz-UZ').format(balance);
    return `${formatted} ${currency}`;
};
</script>

<template>
    <Head title="Telefoniya Sozlamalari" />

    <BusinessLayout>
        <div class="py-6">
            <div class="max-w-3xl mx-auto px-4 sm:px-6">
                <!-- Back Button -->
                <Link
                    :href="route('business.settings.index')"
                    class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-6 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>Sozlamalarga qaytish</span>
                </Link>

                <!-- Flash Messages -->
                <div v-if="flash.success" class="mb-6 p-4 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg flex items-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ flash.success }}</p>
                </div>

                <div v-if="flash.error" class="mb-6 p-4 bg-gray-50 dark:bg-gray-800/50 border border-red-200 dark:border-red-500/30 rounded-lg flex items-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm text-red-700 dark:text-red-400">{{ flash.error }}</p>
                </div>

                <!-- Header -->
                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                                {{ activeTab === 'pbx' ? 'OnlinePBX' : activeTab === 'moizvonki' ? 'Moi Zvonki' : activeTab === 'utel' ? 'UTEL' : 'SipUni' }} Telefoniya
                            </h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ activeTab === 'pbx' ? 'OnlinePBX orqali qo\'ng\'iroqlar' : activeTab === 'moizvonki' ? 'Smartfon qo\'ng\'iroqlarini yozib olish' : activeTab === 'utel' ? 'O\'zbekiston VoIP provayderi' : 'SipUni orqali qo\'ng\'iroqlar' }}
                            </p>
                        </div>
                        <div v-if="currentTabConnected" class="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            Ulangan
                        </div>
                    </div>
                </div>

                <!-- Provider Tabs -->
                <div v-if="!singleProviderMode" class="flex gap-4 border-b border-gray-200 dark:border-gray-700 mb-6 overflow-x-auto">
                    <button
                        @click="activeTab = 'pbx'"
                        :class="[
                            'pb-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors',
                            activeTab === 'pbx'
                                ? 'border-gray-900 dark:border-white text-gray-900 dark:text-white'
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                        ]"
                    >
                        OnlinePBX
                        <span v-if="pbxAccount" class="ml-1.5 w-1.5 h-1.5 bg-green-500 rounded-full inline-block"></span>
                    </button>
                    <button
                        @click="activeTab = 'sipuni'"
                        :class="[
                            'pb-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors',
                            activeTab === 'sipuni'
                                ? 'border-gray-900 dark:border-white text-gray-900 dark:text-white'
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                        ]"
                    >
                        SipUni
                        <span v-if="sipuniAccount" class="ml-1.5 w-1.5 h-1.5 bg-green-500 rounded-full inline-block"></span>
                    </button>
                    <button
                        @click="activeTab = 'moizvonki'"
                        :class="[
                            'pb-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors',
                            activeTab === 'moizvonki'
                                ? 'border-gray-900 dark:border-white text-gray-900 dark:text-white'
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                        ]"
                    >
                        Moi Zvonki
                        <span v-if="moiZvonkiAccount" class="ml-1.5 w-1.5 h-1.5 bg-green-500 rounded-full inline-block"></span>
                    </button>
                    <button
                        @click="activeTab = 'utel'"
                        :class="[
                            'pb-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors',
                            activeTab === 'utel'
                                ? 'border-gray-900 dark:border-white text-gray-900 dark:text-white'
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                        ]"
                    >
                        UTEL
                        <span v-if="utelAccount" class="ml-1.5 w-1.5 h-1.5 bg-green-500 rounded-full inline-block"></span>
                    </button>
                </div>

                <!-- Provider Content -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <!-- PBX Tab Content -->
                    <div v-if="activeTab === 'pbx'" class="p-5">
                        <!-- PBX Not Connected -->
                        <div v-if="!pbxAccount">
                            <!-- OnlinePBX Benefits -->
                            <div class="mb-6 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">OnlinePBX afzalliklari</h4>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1.5">
                                    <li class="flex items-center gap-2">
                                        <span class="w-1 h-1 bg-gray-400 dark:bg-gray-500 rounded-full"></span>
                                        Kiruvchi qo'ng'iroqlardan avtomatik Lead yaratish
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="w-1 h-1 bg-gray-400 dark:bg-gray-500 rounded-full"></span>
                                        Lead kartadan bir bosishda qo'ng'iroq qilish
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="w-1 h-1 bg-gray-400 dark:bg-gray-500 rounded-full"></span>
                                        Qo'ng'iroq tarixi va yozuvlarni saqlash
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="w-1 h-1 bg-gray-400 dark:bg-gray-500 rounded-full"></span>
                                        O'zbekiston uchun arzon tariflar
                                    </li>
                                </ul>
                            </div>

                            <!-- Step-by-step Instructions -->
                            <div class="mb-6">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">OnlinePBX ulash yo'riqnomasi</h3>
                                <div class="space-y-4">
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-6 h-6 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded text-xs font-medium flex items-center justify-center">1</div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">OnlinePBX kabinetiga kiring</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                <a href="https://panel.onlinepbx.ru" target="_blank" class="text-gray-900 dark:text-white underline">panel.onlinepbx.ru</a>
                                                sahifasiga kiring va hisobingizga kiring
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                Hisobingiz yo'qmi? <a href="https://onlinepbx.ru" target="_blank" class="underline">onlinepbx.ru</a> da ro'yxatdan o'ting
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-6 h-6 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded text-xs font-medium flex items-center justify-center">2</div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">API kalitini oling</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Kabinetda <strong class="text-gray-700 dark:text-gray-300">Integratsiya</strong> → <strong class="text-gray-700 dark:text-gray-300">API</strong> bo'limiga o'ting
                                            </p>
                                            <div class="mt-2 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-xs font-mono space-y-1">
                                                <div><span class="text-gray-500">Domain:</span> <span class="text-gray-900 dark:text-white">sizning_domain.onpbx.ru</span></div>
                                                <div><span class="text-gray-500">API Key:</span> <span class="text-gray-900 dark:text-white">xxxxxxxxxxxxxxxx</span></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-6 h-6 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded text-xs font-medium flex items-center justify-center">3</div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">Webhook URL ni sozlang</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                                <strong class="text-gray-700 dark:text-gray-300">Integratsiya</strong> → <strong class="text-gray-700 dark:text-gray-300">Webhooks</strong> bo'limiga o'ting va quyidagi URL ni qo'shing:
                                            </p>
                                            <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                                <div class="flex items-center gap-2">
                                                    <code class="flex-1 text-xs text-gray-900 dark:text-white font-mono overflow-x-auto">{{ webhookUrls?.onlinepbx || 'https://sizning-domain.com/api/webhooks/pbx/onlinepbx' }}</code>
                                                    <button
                                                        v-if="webhookUrls?.onlinepbx"
                                                        @click="copyWebhookUrl(webhookUrls.onlinepbx)"
                                                        class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                                                    >
                                                        {{ copiedUrl === webhookUrls.onlinepbx ? 'Nusxalandi' : 'Nusxalash' }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-6 h-6 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded text-xs font-medium flex items-center justify-center">4</div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">Ma'lumotlarni quyidagi formaga kiriting</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Barcha ma'lumotlarni to'g'ri kiritganingizdan so'ng "Ulanish" tugmasini bosing
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PBX Connect Form -->
                            <form @submit.prevent="connectPbx" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        API URL <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="pbxForm.api_url"
                                        type="url"
                                        required
                                        placeholder="https://sizning_domain.onpbx.ru"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Misol: https://mycompany.onpbx.ru</p>
                                    <p v-if="pbxForm.errors.api_url" class="mt-1 text-sm text-red-500">{{ pbxForm.errors.api_url }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        API Key <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="pbxForm.api_key"
                                        type="text"
                                        required
                                        placeholder="API kalitingizni kiriting"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white font-mono"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">OnlinePBX kabinetidan olingan API kalit</p>
                                    <p v-if="pbxForm.errors.api_key" class="mt-1 text-sm text-red-500">{{ pbxForm.errors.api_key }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        API Secret <span class="text-gray-400 font-normal ml-1">(ixtiyoriy)</span>
                                    </label>
                                    <input
                                        v-model="pbxForm.api_secret"
                                        type="password"
                                        placeholder="Agar mavjud bo'lsa"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p v-if="pbxForm.errors.api_secret" class="mt-1 text-sm text-red-500">{{ pbxForm.errors.api_secret }}</p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                            Chiquvchi raqam <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="pbxForm.caller_id"
                                            type="text"
                                            required
                                            placeholder="+998901234567"
                                            class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                        />
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Caller ID</p>
                                        <p v-if="pbxForm.errors.caller_id" class="mt-1 text-sm text-red-500">{{ pbxForm.errors.caller_id }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                            Extension <span class="text-gray-400 font-normal ml-1">(ichki)</span>
                                        </label>
                                        <input
                                            v-model="pbxForm.extension"
                                            type="text"
                                            placeholder="101"
                                            class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                        />
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ichki raqam</p>
                                        <p v-if="pbxForm.errors.extension" class="mt-1 text-sm text-red-500">{{ pbxForm.errors.extension }}</p>
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="isConnecting || pbxForm.processing"
                                    class="w-full py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isConnecting || pbxForm.processing" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Ulanish tekshirilmoqda...
                                    </span>
                                    <span v-else>Ulanish</span>
                                </button>
                            </form>

                            <div class="mt-6 text-center">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Muammo bo'lsa?
                                    <a href="https://onlinepbx.ru/support" target="_blank" class="underline">OnlinePBX qo'llab-quvvatlash</a>
                                    yoki
                                    <a href="mailto:support@biznespilot.uz" class="underline">bizga yozing</a>
                                </p>
                            </div>
                        </div>

                        <!-- PBX Connected -->
                        <div v-else>
                            <!-- Connection Status -->
                            <div class="flex items-center justify-between mb-5">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">OnlinePBX Ulangan</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ pbxAccount.name || pbxAccount.api_url }}</p>
                                </div>
                                <button
                                    @click="disconnectPbx"
                                    :disabled="isDisconnecting"
                                    class="text-sm text-red-600 dark:text-red-400 hover:underline disabled:opacity-50"
                                >
                                    <span v-if="isDisconnecting">Uzilmoqda...</span>
                                    <span v-else>Uzish</span>
                                </button>
                            </div>

                            <!-- Account Details Grid -->
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Chiquvchi raqam</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white font-mono">{{ pbxAccount.caller_id }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Ichki raqam</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white font-mono">{{ pbxAccount.extension || '—' }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Balans</p>
                                        <button
                                            @click="refreshBalance"
                                            :disabled="isRefreshingBalance"
                                            class="text-gray-400 hover:text-gray-900 dark:hover:text-white disabled:opacity-50 transition-colors"
                                            title="Balansni yangilash"
                                        >
                                            <svg :class="{'animate-spin': isRefreshingBalance}" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ pbxAccount.balance || 0 }} so'm</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Oxirgi sinx</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatDate(pbxAccount.last_sync_at) }}</p>
                                </div>
                            </div>

                            <!-- Webhook URL Info -->
                            <div class="mb-5 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Webhook URL</p>
                                <div class="flex items-center gap-2">
                                    <code class="flex-1 text-xs text-gray-900 dark:text-white font-mono overflow-x-auto">{{ webhookUrls?.onlinepbx || '—' }}</code>
                                    <button
                                        v-if="webhookUrls?.onlinepbx"
                                        @click="copyWebhookUrl(webhookUrls.onlinepbx)"
                                        class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                                    >
                                        {{ copiedUrl === webhookUrls.onlinepbx ? 'Nusxalandi' : 'Nusxalash' }}
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">OnlinePBX kabinetidagi Integratsiya → Webhooks da sozlangan bo'lishi kerak</p>
                            </div>

                            <!-- PBX Statistics -->
                            <div v-if="stats" class="pt-5 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Qo'ng'iroqlar Statistikasi <span class="text-gray-500 dark:text-gray-400 font-normal">(30 kun)</span></h3>
                                    <Link :href="route('integrations.telephony.history')" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                        Batafsil
                                    </Link>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                                    <Link :href="route('integrations.telephony.history')" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center transition-colors hover:border-gray-300 dark:hover:border-gray-600">
                                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.total_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Jami</p>
                                    </Link>
                                    <Link :href="route('integrations.telephony.history', { status: 'completed' })" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center transition-colors hover:border-gray-300 dark:hover:border-gray-600">
                                        <p class="text-2xl font-semibold text-green-600 dark:text-green-400">{{ stats.answered_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Javob berilgan</p>
                                    </Link>
                                    <Link :href="route('integrations.telephony.history', { status: 'missed' })" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center transition-colors hover:border-gray-300 dark:hover:border-gray-600">
                                        <p class="text-2xl font-semibold text-yellow-600 dark:text-yellow-400">{{ stats.missed_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Javobsiz</p>
                                    </Link>
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.answer_rate }}%</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Javob ulushi</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-3">
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ stats.outbound_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Chiquvchi</p>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ stats.inbound_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Kiruvchi</p>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ formatDuration(stats.total_duration) }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Jami vaqt</p>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="pt-5 border-t border-gray-200 dark:border-gray-700">
                                <div class="text-center py-6">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Hali qo'ng'iroqlar statistikasi mavjud emas</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Qo'ng'iroqlar amalga oshirilgandan so'ng bu yerda statistika ko'rinadi</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SipUni Tab Content -->
                    <div v-if="activeTab === 'sipuni'" class="p-5">
                        <div v-if="!sipuniAccount">
                            <div class="mb-6">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">SipUni ulash bo'yicha yo'riqnoma</h3>
                                <div class="space-y-4">
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-6 h-6 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded text-xs font-medium flex items-center justify-center">1</div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">SipUni kabinetiga kiring</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                <a href="https://sipuni.com/login" target="_blank" class="underline">sipuni.com/login</a> sahifasiga kiring
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-6 h-6 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded text-xs font-medium flex items-center justify-center">2</div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">API sozlamalariga o'ting</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                <strong class="text-gray-700 dark:text-gray-300">Sozlamalar</strong> → <strong class="text-gray-700 dark:text-gray-300">API</strong> bo'limini oching
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-6 h-6 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded text-xs font-medium flex items-center justify-center">3</div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">User ID va Secret ni nusxalang</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                API sahifasida <strong class="text-gray-700 dark:text-gray-300">user</strong> (raqam) va <strong class="text-gray-700 dark:text-gray-300">secret</strong> qiymatlarini ko'rasiz
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wide">SipUni API sahifasidan misol:</p>
                                <div class="font-mono text-xs space-y-1">
                                    <div><span class="text-gray-500">user:</span> <span class="text-gray-900 dark:text-white">123456</span> <span class="text-gray-400 ml-2">-- bu User ID</span></div>
                                    <div><span class="text-gray-500">secret:</span> <span class="text-gray-900 dark:text-white">abc123def456...</span> <span class="text-gray-400 ml-2">-- bu Secret Key</span></div>
                                </div>
                            </div>

                            <form @submit.prevent="connectSipuni" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        User ID <span class="text-gray-400 font-normal ml-1">(user qiymati)</span>
                                    </label>
                                    <input
                                        v-model="sipuniForm.api_key"
                                        type="text"
                                        required
                                        placeholder="123456"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Faqat raqamlar (email emas!)</p>
                                    <p v-if="sipuniForm.errors.api_key" class="mt-1 text-sm text-red-500">{{ sipuniForm.errors.api_key }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        Secret Key <span class="text-gray-400 font-normal ml-1">(secret qiymati)</span>
                                    </label>
                                    <input
                                        v-model="sipuniForm.api_secret"
                                        type="password"
                                        required
                                        placeholder="abc123def456..."
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Sayt parolingiz emas! API secret kalit</p>
                                    <p v-if="sipuniForm.errors.api_secret" class="mt-1 text-sm text-red-500">{{ sipuniForm.errors.api_secret }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        Chiquvchi raqam <span class="text-gray-400 font-normal ml-1">(Caller ID)</span>
                                    </label>
                                    <input
                                        v-model="sipuniForm.caller_id"
                                        type="text"
                                        required
                                        placeholder="+998901234567"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">SipUni da ro'yxatdan o'tgan telefon raqamingiz</p>
                                    <p v-if="sipuniForm.errors.caller_id" class="mt-1 text-sm text-red-500">{{ sipuniForm.errors.caller_id }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        Ichki raqam <span class="text-gray-400 font-normal ml-1">(sipnumber - ixtiyoriy)</span>
                                    </label>
                                    <input
                                        v-model="sipuniForm.extension"
                                        type="text"
                                        placeholder="100"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">SipUni dagi ichki raqamingiz (qo'ng'iroq boshlash uchun)</p>
                                    <p v-if="sipuniForm.errors.extension" class="mt-1 text-sm text-red-500">{{ sipuniForm.errors.extension }}</p>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="isConnecting || sipuniForm.processing"
                                    class="w-full py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isConnecting || sipuniForm.processing" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Tekshirilmoqda...
                                    </span>
                                    <span v-else>Ulanish</span>
                                </button>
                            </form>

                            <div class="mt-6 text-center">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    SipUni hisobingiz yo'qmi?
                                    <a href="https://sipuni.com/register" target="_blank" class="underline ml-1">Ro'yxatdan o'tish</a>
                                </p>
                            </div>
                        </div>

                        <!-- SipUni Connected -->
                        <div v-else>
                            <div class="flex items-center justify-between mb-5">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">SipUni Ulangan</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ sipuniAccount.name }}</p>
                                </div>
                                <button
                                    @click="disconnectSipuni"
                                    :disabled="isDisconnecting"
                                    class="text-sm text-red-600 dark:text-red-400 hover:underline disabled:opacity-50"
                                >
                                    <span v-if="isDisconnecting">Uzilmoqda...</span>
                                    <span v-else>Uzish</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Caller ID</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ sipuniAccount.caller_id }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Ichki raqam</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ sipuniAccount.extension || '100' }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Balans</p>
                                        <button @click="refreshBalance" :disabled="isRefreshingBalance" class="text-gray-400 hover:text-gray-900 dark:hover:text-white disabled:opacity-50 transition-colors">
                                            <svg :class="{'animate-spin': isRefreshingBalance}" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ sipuniAccount.balance || 0 }} so'm</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Oxirgi yangilanish</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatDate(sipuniAccount.last_sync_at) }}</p>
                                </div>
                            </div>

                            <!-- SipUni Statistics -->
                            <div v-if="stats" class="mt-5 pt-5 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Qo'ng'iroqlar Statistikasi <span class="text-gray-500 dark:text-gray-400 font-normal">(30 kun)</span></h3>
                                    <Link :href="route('integrations.telephony.history')" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                        Batafsil
                                    </Link>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                                    <Link :href="route('integrations.telephony.history')" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center transition-colors hover:border-gray-300 dark:hover:border-gray-600">
                                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.total_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Jami</p>
                                    </Link>
                                    <Link :href="route('integrations.telephony.history', { status: 'completed' })" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center transition-colors hover:border-gray-300 dark:hover:border-gray-600">
                                        <p class="text-2xl font-semibold text-green-600 dark:text-green-400">{{ stats.answered_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Javob berilgan</p>
                                    </Link>
                                    <Link :href="route('integrations.telephony.history', { status: 'missed' })" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center transition-colors hover:border-gray-300 dark:hover:border-gray-600">
                                        <p class="text-2xl font-semibold text-yellow-600 dark:text-yellow-400">{{ stats.missed_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Javobsiz</p>
                                    </Link>
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.answer_rate }}%</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Javob %</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ stats.outbound_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Chiquvchi</p>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ stats.inbound_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Kiruvchi</p>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ formatDuration(stats.total_duration) }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Jami vaqt</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MoiZvonki Tab Content -->
                    <div v-if="activeTab === 'moizvonki'" class="p-5">
                        <div v-if="!moiZvonkiAccount">
                            <!-- MoiZvonki Benefits -->
                            <div class="mb-6 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Moi Zvonki afzalliklari</h4>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1.5">
                                    <li class="flex items-center gap-2">
                                        <span class="w-1 h-1 bg-gray-400 dark:bg-gray-500 rounded-full"></span>
                                        Android smartfonlardan qo'ng'iroqlarni avtomatik yozib olish
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="w-1 h-1 bg-gray-400 dark:bg-gray-500 rounded-full"></span>
                                        Kiruvchi/chiquvchi qo'ng'iroqlar tarixi
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="w-1 h-1 bg-gray-400 dark:bg-gray-500 rounded-full"></span>
                                        Qo'ng'iroq yozuvlarini tinglash
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="w-1 h-1 bg-gray-400 dark:bg-gray-500 rounded-full"></span>
                                        Oyiga 150-200 rubl (har bir foydalanuvchi)
                                    </li>
                                </ul>
                            </div>

                            <!-- Step-by-step Instructions -->
                            <div class="mb-6">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Moi Zvonki ulash yo'riqnomasi</h3>
                                <div class="space-y-4">
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-6 h-6 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded text-xs font-medium flex items-center justify-center">1</div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">MoiZvonki hisobini yarating</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                <a href="https://moizvonki.ru" target="_blank" class="underline">moizvonki.ru</a> saytida ro'yxatdan o'ting
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-6 h-6 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded text-xs font-medium flex items-center justify-center">2</div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">Android ilovasini o'rnating</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Play Store dan <strong class="text-gray-700 dark:text-gray-300">Moi Zvonki</strong> ilovasini yuklab, smartfoningizga o'rnating
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-6 h-6 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded text-xs font-medium flex items-center justify-center">3</div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">API kalitini oling</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                                MoiZvonki kabinetida <strong class="text-gray-700 dark:text-gray-300">Nastroyki</strong> → <strong class="text-gray-700 dark:text-gray-300">API</strong> bo'limidan API kalitini nusxalang
                                            </p>
                                            <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-xs font-mono space-y-1">
                                                <div><span class="text-gray-500">URL:</span> <span class="text-gray-900 dark:text-white">sizning_domen.moizvonki.ru</span></div>
                                                <div><span class="text-gray-500">API Key:</span> <span class="text-gray-900 dark:text-white">xxxxxxxxxxxxxxxx</span></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-6 h-6 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded text-xs font-medium flex items-center justify-center">4</div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">Webhook URL ni sozlang</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">MoiZvonki kabinetida quyidagi webhook URL ni qo'shing:</p>
                                            <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                                <div class="flex items-center gap-2">
                                                    <code class="flex-1 text-xs text-gray-900 dark:text-white font-mono overflow-x-auto">{{ webhookUrls?.moizvonki || 'https://sizning-domain.com/api/webhooks/moizvonki' }}</code>
                                                    <button
                                                        v-if="webhookUrls?.moizvonki"
                                                        @click="copyWebhookUrl(webhookUrls.moizvonki)"
                                                        class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                                                    >
                                                        Nusxalash
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- MoiZvonki Connect Form -->
                            <form @submit.prevent="connectMoiZvonki" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        Email <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="moiZvonkiForm.email"
                                        type="email"
                                        required
                                        placeholder="email@example.com"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">MoiZvonki kabinetiga kirish emailingiz</p>
                                    <p v-if="moiZvonkiForm.errors.email" class="mt-1 text-sm text-red-500">{{ moiZvonkiForm.errors.email }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        API URL <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="moiZvonkiForm.api_url"
                                        type="text"
                                        required
                                        placeholder="mycompany.moizvonki.ru"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Sizning MoiZvonki domeningiz</p>
                                    <p v-if="moiZvonkiForm.errors.api_url" class="mt-1 text-sm text-red-500">{{ moiZvonkiForm.errors.api_url }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        API Key <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="moiZvonkiForm.api_key"
                                        type="text"
                                        required
                                        placeholder="API kalitingizni kiriting"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white font-mono"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">MoiZvonki kabinetidan olingan API kalit</p>
                                    <p v-if="moiZvonkiForm.errors.api_key" class="mt-1 text-sm text-red-500">{{ moiZvonkiForm.errors.api_key }}</p>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="isConnecting || moiZvonkiForm.processing"
                                    class="w-full py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isConnecting || moiZvonkiForm.processing" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Ulanish tekshirilmoqda...
                                    </span>
                                    <span v-else>Ulanish</span>
                                </button>
                            </form>

                            <div class="mt-6 text-center">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Yordam kerakmi?
                                    <a href="https://moizvonki.ru/help" target="_blank" class="underline ml-1">MoiZvonki qo'llab-quvvatlash</a>
                                </p>
                            </div>
                        </div>

                        <!-- MoiZvonki Connected -->
                        <div v-else>
                            <div class="flex items-center justify-between mb-5">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">MoiZvonki Ulangan</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ moiZvonkiAccount.name || moiZvonkiAccount.api_url }}</p>
                                </div>
                                <div class="flex items-center gap-4">
                                    <button
                                        @click="syncMoiZvonki"
                                        :disabled="isSyncing"
                                        class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors disabled:opacity-50 flex items-center gap-1.5"
                                    >
                                        <svg :class="{'animate-spin': isSyncing}" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        <span v-if="isSyncing">Sinxronlanmoqda...</span>
                                        <span v-else>Sinxronlash</span>
                                    </button>
                                    <button
                                        @click="disconnectMoiZvonki"
                                        :disabled="isDisconnecting"
                                        class="text-sm text-red-600 dark:text-red-400 hover:underline disabled:opacity-50"
                                    >
                                        <span v-if="isDisconnecting">Uzilmoqda...</span>
                                        <span v-else>Uzish</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5">
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">API URL</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ moiZvonkiAccount.api_url }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Email</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ moiZvonkiAccount.email }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Oxirgi sinx</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatDate(moiZvonkiAccount.last_sync_at) }}</p>
                                </div>
                            </div>

                            <!-- Webhook URL Info -->
                            <div class="mb-5 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Webhook URL</p>
                                <div class="flex items-center gap-2">
                                    <code class="flex-1 text-xs text-gray-900 dark:text-white font-mono overflow-x-auto">{{ webhookUrls?.moizvonki || '—' }}</code>
                                    <button
                                        v-if="webhookUrls?.moizvonki"
                                        @click="copyWebhookUrl(webhookUrls.moizvonki)"
                                        class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                                    >
                                        {{ copiedUrl === webhookUrls.moizvonki ? 'Nusxalandi' : 'Nusxalash' }}
                                    </button>
                                </div>
                            </div>

                            <!-- MoiZvonki Statistics -->
                            <div v-if="stats" class="pt-5 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Qo'ng'iroqlar Statistikasi <span class="text-gray-500 dark:text-gray-400 font-normal">(30 kun)</span></h3>
                                    <Link :href="route('integrations.telephony.history')" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                        Batafsil
                                    </Link>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                                    <Link :href="route('integrations.telephony.history')" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center transition-colors hover:border-gray-300 dark:hover:border-gray-600">
                                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.total_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Jami</p>
                                    </Link>
                                    <Link :href="route('integrations.telephony.history', { status: 'completed' })" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center transition-colors hover:border-gray-300 dark:hover:border-gray-600">
                                        <p class="text-2xl font-semibold text-green-600 dark:text-green-400">{{ stats.answered_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Javob berilgan</p>
                                    </Link>
                                    <Link :href="route('integrations.telephony.history', { status: 'missed' })" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center transition-colors hover:border-gray-300 dark:hover:border-gray-600">
                                        <p class="text-2xl font-semibold text-yellow-600 dark:text-yellow-400">{{ stats.missed_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Javobsiz</p>
                                    </Link>
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.answer_rate }}%</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Javob ulushi</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-3">
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ stats.outbound_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Chiquvchi</p>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ stats.inbound_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Kiruvchi</p>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ formatDuration(stats.total_duration) }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Jami vaqt</p>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="pt-5 border-t border-gray-200 dark:border-gray-700">
                                <div class="text-center py-6">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Hali qo'ng'iroqlar statistikasi mavjud emas</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Qo'ng'iroqlar amalga oshirilgandan so'ng bu yerda statistika ko'rinadi</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- UTEL Tab Content -->
                    <div v-if="activeTab === 'utel'" class="p-5">
                        <div v-if="!utelAccount">
                            <!-- UTEL Benefits -->
                            <div class="mb-6 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">UTEL afzalliklari</h4>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1.5">
                                    <li class="flex items-center gap-2">
                                        <span class="w-1 h-1 bg-gray-400 dark:bg-gray-500 rounded-full"></span>
                                        O'zbekiston VoIP provayderi - mahalliy narxlar
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="w-1 h-1 bg-gray-400 dark:bg-gray-500 rounded-full"></span>
                                        CRM dan bir klik bilan qo'ng'iroq qilish
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="w-1 h-1 bg-gray-400 dark:bg-gray-500 rounded-full"></span>
                                        Kiruvchi/chiquvchi qo'ng'iroqlar tarixi
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="w-1 h-1 bg-gray-400 dark:bg-gray-500 rounded-full"></span>
                                        Click/Payme orqali to'lov
                                    </li>
                                </ul>
                            </div>

                            <!-- Step-by-step Instructions -->
                            <div class="mb-6">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">UTEL ulash yo'riqnomasi</h3>
                                <div class="space-y-4">
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-6 h-6 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded text-xs font-medium flex items-center justify-center">1</div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">UTEL hisobini yarating</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                <a href="https://utel.uz" target="_blank" class="underline">utel.uz</a> da ro'yxatdan o'ting
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-6 h-6 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded text-xs font-medium flex items-center justify-center">2</div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">Virtual raqam oling</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">UTEL kabinetidan telefon raqam sotib oling</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-6 h-6 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded text-xs font-medium flex items-center justify-center">3</div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">Ma'lumotlarni kiriting</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Quyidagi formani to'ldiring</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- UTEL Connect Form -->
                            <form @submit.prevent="connectUtel" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        Subdomain <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-500 dark:text-gray-400 mr-2">https://api.</span>
                                        <input
                                            v-model="utelForm.subdomain"
                                            type="text"
                                            required
                                            placeholder="cc279"
                                            class="flex-1 px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                        />
                                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">.utel.uz</span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">UTEL kabinet URL dan (masalan: cc279.utel.uz → <strong>cc279</strong>)</p>
                                    <p v-if="utelForm.errors.subdomain" class="mt-1 text-sm text-red-500">{{ utelForm.errors.subdomain }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        Email <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="utelForm.email"
                                        type="email"
                                        required
                                        placeholder="email@example.com"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">UTEL kabinetiga kirish emailingiz</p>
                                    <p v-if="utelForm.errors.email" class="mt-1 text-sm text-red-500">{{ utelForm.errors.email }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        Parol <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="utelForm.password"
                                        type="password"
                                        required
                                        placeholder="UTEL parolingiz"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">UTEL kabinetiga kirish parolingiz (xavfsiz saqlanadi)</p>
                                    <p v-if="utelForm.errors.password" class="mt-1 text-sm text-red-500">{{ utelForm.errors.password }}</p>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="isConnecting || utelForm.processing"
                                    class="w-full py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isConnecting || utelForm.processing" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Ulanish tekshirilmoqda...
                                    </span>
                                    <span v-else>Ulanish</span>
                                </button>
                            </form>

                            <div class="mt-6 text-center">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Yordam kerakmi?
                                    <a href="https://utel.uz/support" target="_blank" class="underline ml-1">UTEL qo'llab-quvvatlash</a>
                                </p>
                            </div>
                        </div>

                        <!-- UTEL Connected -->
                        <div v-else>
                            <div class="flex items-center justify-between mb-5">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">UTEL Ulangan</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ utelAccount.name || utelAccount.email }}</p>
                                </div>
                                <div class="flex items-center gap-4">
                                    <button
                                        @click="syncUtel"
                                        :disabled="isSyncing"
                                        class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors disabled:opacity-50 flex items-center gap-1.5"
                                    >
                                        <svg :class="{'animate-spin': isSyncing}" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        <span v-if="isSyncing">Sinxronlanmoqda...</span>
                                        <span v-else>Sinxronlash</span>
                                    </button>
                                    <button
                                        @click="disconnectUtel"
                                        :disabled="isDisconnecting"
                                        class="text-sm text-red-600 dark:text-red-400 hover:underline disabled:opacity-50"
                                    >
                                        <span v-if="isDisconnecting">Uzilmoqda...</span>
                                        <span v-else>Uzish</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Email</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ utelAccount.email }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Caller ID</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ utelAccount.caller_id || '—' }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Balans</p>
                                        <button
                                            @click="refreshUtelBalance"
                                            :disabled="isRefreshingUtelBalance"
                                            class="text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors disabled:opacity-50"
                                            title="Balansni yangilash"
                                        >
                                            <svg :class="{'animate-spin': isRefreshingUtelBalance}" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatUtelBalance(utelAccount.balance, utelAccount.currency) }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Oxirgi sinx</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatDate(utelAccount.last_sync_at) }}</p>
                                </div>
                            </div>

                            <!-- Auto Sync Info -->
                            <div class="mb-5 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Real-time sinxronlash</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                    Qo'ng'iroqlar har 5 daqiqada avtomatik sinxronlanadi. Real-time sinxronlash uchun webhook'ni UTEL'da sozlang yoki quyidagi tugmani bosing.
                                </p>
                                <button
                                    @click="configureUtelWebhook"
                                    :disabled="isConfiguringWebhook"
                                    class="px-3 py-1.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-xs font-medium rounded-lg transition-colors disabled:opacity-50 inline-flex items-center gap-1.5"
                                >
                                    <svg v-if="isConfiguringWebhook" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span v-if="isConfiguringWebhook">Sozlanmoqda...</span>
                                    <span v-else>Webhook avtomatik sozlash</span>
                                </button>
                                <p v-if="webhookConfigured" class="text-xs text-green-600 dark:text-green-400 mt-2">Webhook muvaffaqiyatli sozlandi!</p>
                            </div>

                            <!-- Webhook URL Info -->
                            <div class="mb-5 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Webhook URL <span class="font-normal text-gray-500">(qo'lda sozlash uchun)</span></p>
                                <div class="flex items-center gap-2">
                                    <code class="flex-1 text-xs text-gray-900 dark:text-white font-mono overflow-x-auto">{{ webhookUrls?.utel || '—' }}</code>
                                    <button
                                        v-if="webhookUrls?.utel"
                                        @click="copyWebhookUrl(webhookUrls.utel)"
                                        class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                                    >
                                        {{ copiedUrl === webhookUrls.utel ? 'Nusxalandi' : 'Nusxalash' }}
                                    </button>
                                </div>
                            </div>

                            <!-- UTEL Statistics -->
                            <div v-if="stats" class="pt-5 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Qo'ng'iroqlar Statistikasi <span class="text-gray-500 dark:text-gray-400 font-normal">(30 kun)</span></h3>
                                    <Link :href="route('integrations.telephony.history')" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                        Batafsil
                                    </Link>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                                    <Link :href="route('integrations.telephony.history')" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center transition-colors hover:border-gray-300 dark:hover:border-gray-600">
                                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.total_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Jami</p>
                                    </Link>
                                    <Link :href="route('integrations.telephony.history', { status: 'completed' })" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center transition-colors hover:border-gray-300 dark:hover:border-gray-600">
                                        <p class="text-2xl font-semibold text-green-600 dark:text-green-400">{{ stats.answered_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Javob berilgan</p>
                                    </Link>
                                    <Link :href="route('integrations.telephony.history', { status: 'missed' })" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center transition-colors hover:border-gray-300 dark:hover:border-gray-600">
                                        <p class="text-2xl font-semibold text-yellow-600 dark:text-yellow-400">{{ stats.missed_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Javobsiz</p>
                                    </Link>
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.answer_rate }}%</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Javob ulushi</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-3">
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ stats.outbound_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Chiquvchi</p>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ stats.inbound_calls }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Kiruvchi</p>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ formatDuration(stats.total_duration) }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Jami vaqt</p>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="pt-5 border-t border-gray-200 dark:border-gray-700">
                                <div class="text-center py-6">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Hali qo'ng'iroqlar statistikasi mavjud emas</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Qo'ng'iroqlar amalga oshirilgandan so'ng bu yerda statistika ko'rinadi</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </BusinessLayout>
</template>
