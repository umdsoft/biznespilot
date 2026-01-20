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
    email: '',
    password: '',
    caller_id: '',
    extension: '',
});

const isSyncing = ref(false);
const isRefreshingUtelBalance = ref(false);

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
    utelForm.post(route('integrations.telephony.utel.connect'), {
        onFinish: () => {
            isConnecting.value = false;
            if (!utelForm.hasErrors) {
                utelForm.reset();
            }
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

const copyWebhookUrl = (url) => {
    navigator.clipboard.writeText(url);
    alert('Webhook URL nusxalandi!');
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
        <div class="py-6 lg:py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Back Button -->
                <Link
                    :href="route('business.settings.index')"
                    class="inline-flex items-center gap-2 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-900 dark:text-white mb-6 transition-all duration-200 px-3 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 -ml-3"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>Sozlamalarga qaytish</span>
                </Link>

                <!-- Flash Messages -->
                <div v-if="flash.success" class="mb-6 p-4 bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 rounded-xl flex items-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <p class="text-green-700 dark:text-green-400">{{ flash.success }}</p>
                </div>

                <div v-if="flash.error" class="mb-6 p-4 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 rounded-xl flex items-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-red-700 dark:text-red-400">{{ flash.error }}</p>
                </div>

                <!-- Header -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 lg:p-8 mb-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div :class="[
                                'w-16 h-16 lg:w-20 lg:h-20 rounded-2xl flex items-center justify-center mr-5 lg:mr-6 shadow-lg',
                                activeTab === 'pbx'
                                    ? 'bg-gradient-to-br from-blue-500 to-indigo-600'
                                    : activeTab === 'moizvonki'
                                        ? 'bg-gradient-to-br from-orange-500 to-red-600'
                                        : activeTab === 'utel'
                                            ? 'bg-gradient-to-br from-green-500 to-teal-600'
                                            : 'bg-gradient-to-br from-purple-500 to-violet-600'
                            ]">
                                <svg v-if="activeTab === 'moizvonki'" class="w-8 h-8 lg:w-10 lg:h-10 text-gray-900 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <svg v-else class="w-8 h-8 lg:w-10 lg:h-10 text-gray-900 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-gray-900 dark:text-white tracking-tight">
                                    {{ activeTab === 'pbx' ? 'OnlinePBX' : activeTab === 'moizvonki' ? 'Moi Zvonki' : activeTab === 'utel' ? 'UTEL' : 'SipUni' }} Telefoniya
                                </h1>
                                <p class="text-gray-500 dark:text-gray-400 mt-1 lg:mt-2 text-base lg:text-lg">
                                    {{ activeTab === 'pbx' ? 'OnlinePBX orqali qo\'ng\'iroqlar' : activeTab === 'moizvonki' ? 'Smartfon qo\'ng\'iroqlarini yozib olish' : activeTab === 'utel' ? 'O\'zbekiston VoIP provayderi' : 'SipUni orqali qo\'ng\'iroqlar' }}
                                </p>
                            </div>
                        </div>
                        <!-- Status indicator -->
                        <div v-if="hasActiveProvider" class="hidden md:flex items-center gap-3 px-4 py-2 bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 rounded-xl">
                            <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                            <span class="text-green-600 dark:text-green-400 font-medium">Ulangan</span>
                        </div>
                    </div>
                </div>

                <!-- Provider Tabs -->
                <div v-if="!singleProviderMode" class="mb-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <!-- OnlinePBX Tab -->
                        <button
                            @click="activeTab = 'pbx'"
                            :class="[
                                'relative group px-4 py-4 rounded-xl font-medium transition-all duration-200',
                                activeTab === 'pbx'
                                    ? 'bg-blue-50 dark:bg-blue-500/10 border-2 border-blue-500 shadow-sm'
                                    : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-sm'
                            ]"
                        >
                            <div class="flex flex-col items-center gap-2.5">
                                <div :class="[
                                    'w-11 h-11 rounded-xl flex items-center justify-center transition-all duration-200',
                                    activeTab === 'pbx'
                                        ? 'bg-gradient-to-br from-blue-500 to-indigo-600 shadow-md'
                                        : 'bg-gray-100 dark:bg-gray-700 group-hover:bg-gray-200 dark:group-hover:bg-gray-600'
                                ]">
                                    <svg :class="['w-5 h-5', activeTab === 'pbx' ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <div class="text-center">
                                    <span :class="[
                                        'block text-sm font-semibold transition-colors',
                                        activeTab === 'pbx' ? 'text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300'
                                    ]">OnlinePBX</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">Rossiya</span>
                                </div>
                                <span v-if="pbxAccount" class="absolute top-2.5 right-2.5 w-2.5 h-2.5 bg-green-500 rounded-full ring-2 ring-white dark:ring-gray-800"></span>
                            </div>
                        </button>
                        <!-- SipUni Tab -->
                        <button
                            @click="activeTab = 'sipuni'"
                            :class="[
                                'relative group px-4 py-4 rounded-xl font-medium transition-all duration-200',
                                activeTab === 'sipuni'
                                    ? 'bg-purple-50 dark:bg-purple-500/10 border-2 border-purple-500 shadow-sm'
                                    : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-sm'
                            ]"
                        >
                            <div class="flex flex-col items-center gap-2.5">
                                <div :class="[
                                    'w-11 h-11 rounded-xl flex items-center justify-center transition-all duration-200',
                                    activeTab === 'sipuni'
                                        ? 'bg-gradient-to-br from-purple-500 to-violet-600 shadow-md'
                                        : 'bg-gray-100 dark:bg-gray-700 group-hover:bg-gray-200 dark:group-hover:bg-gray-600'
                                ]">
                                    <span :class="['font-bold text-sm', activeTab === 'sipuni' ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400']">SU</span>
                                </div>
                                <div class="text-center">
                                    <span :class="[
                                        'block text-sm font-semibold transition-colors',
                                        activeTab === 'sipuni' ? 'text-purple-600 dark:text-purple-400' : 'text-gray-700 dark:text-gray-300'
                                    ]">SipUni</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">Rossiya</span>
                                </div>
                                <span v-if="sipuniAccount" class="absolute top-2.5 right-2.5 w-2.5 h-2.5 bg-green-500 rounded-full ring-2 ring-white dark:ring-gray-800"></span>
                            </div>
                        </button>
                        <!-- MoiZvonki Tab -->
                        <button
                            @click="activeTab = 'moizvonki'"
                            :class="[
                                'relative group px-4 py-4 rounded-xl font-medium transition-all duration-200',
                                activeTab === 'moizvonki'
                                    ? 'bg-orange-50 dark:bg-orange-500/10 border-2 border-orange-500 shadow-sm'
                                    : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-sm'
                            ]"
                        >
                            <div class="flex flex-col items-center gap-2.5">
                                <div :class="[
                                    'w-11 h-11 rounded-xl flex items-center justify-center transition-all duration-200',
                                    activeTab === 'moizvonki'
                                        ? 'bg-gradient-to-br from-orange-500 to-red-600 shadow-md'
                                        : 'bg-gray-100 dark:bg-gray-700 group-hover:bg-gray-200 dark:group-hover:bg-gray-600'
                                ]">
                                    <svg :class="['w-5 h-5', activeTab === 'moizvonki' ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="text-center">
                                    <span :class="[
                                        'block text-sm font-semibold transition-colors',
                                        activeTab === 'moizvonki' ? 'text-orange-600 dark:text-orange-400' : 'text-gray-700 dark:text-gray-300'
                                    ]">Moi Zvonki</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">Mobil</span>
                                </div>
                                <span v-if="moiZvonkiAccount" class="absolute top-2.5 right-2.5 w-2.5 h-2.5 bg-green-500 rounded-full ring-2 ring-white dark:ring-gray-800"></span>
                            </div>
                        </button>
                        <!-- UTEL Tab (O'zbekiston) -->
                        <button
                            @click="activeTab = 'utel'"
                            :class="[
                                'relative group px-4 py-4 rounded-xl font-medium transition-all duration-200',
                                activeTab === 'utel'
                                    ? 'bg-green-50 dark:bg-green-500/10 border-2 border-green-500 shadow-sm'
                                    : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-sm'
                            ]"
                        >
                            <div class="flex flex-col items-center gap-2.5">
                                <div :class="[
                                    'w-11 h-11 rounded-xl flex items-center justify-center transition-all duration-200',
                                    activeTab === 'utel'
                                        ? 'bg-gradient-to-br from-green-500 to-teal-600 shadow-md'
                                        : 'bg-gray-100 dark:bg-gray-700 group-hover:bg-gray-200 dark:group-hover:bg-gray-600'
                                ]">
                                    <span :class="['font-bold text-sm', activeTab === 'utel' ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400']">UZ</span>
                                </div>
                                <div class="text-center">
                                    <span :class="[
                                        'block text-sm font-semibold transition-colors',
                                        activeTab === 'utel' ? 'text-green-600 dark:text-green-400' : 'text-gray-700 dark:text-gray-300'
                                    ]">UTEL</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">O'zbekiston</span>
                                </div>
                                <span v-if="utelAccount" class="absolute top-2.5 right-2.5 w-2.5 h-2.5 bg-green-500 rounded-full ring-2 ring-white dark:ring-gray-800"></span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Provider Content -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm">
                    <!-- PBX Tab Content -->
                    <div v-if="activeTab === 'pbx'" class="p-6 lg:p-8">
                        <!-- PBX Not Connected -->
                        <div v-if="!pbxAccount">
                            <!-- OnlinePBX Benefits -->
                            <div class="mb-6 p-5 bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/30 rounded-xl">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-gray-900 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-blue-700 dark:text-blue-400 font-semibold mb-2">OnlinePBX afzalliklari</h4>
                                        <ul class="text-gray-600 dark:text-gray-300 text-sm space-y-1">
                                            <li class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                                Kiruvchi qo'ng'iroqlardan avtomatik Lead yaratish
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                                Lead kartadan bir bosishda qo'ng'iroq qilish
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                                Qo'ng'iroq tarixi va yozuvlarni saqlash
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                                O'zbekiston uchun arzon tariflar
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Step-by-step Instructions -->
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white mb-5 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                    OnlinePBX ulash yo'riqnomasi
                                </h3>
                                <div class="space-y-5">
                                    <!-- Step 1 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center text-gray-900 dark:text-white font-bold text-sm shadow">1</div>
                                        </div>
                                        <div class="flex-1 pt-0.5">
                                            <p class="text-gray-900 dark:text-gray-900 dark:text-white font-medium mb-1">OnlinePBX kabinetiga kiring</p>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">
                                                <a href="https://panel.onlinepbx.ru" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1">
                                                    panel.onlinepbx.ru
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                </a>
                                                sahifasiga kiring va hisobingizga kiring
                                            </p>
                                            <div class="bg-gray-100 dark:bg-gray-700/50 rounded-lg p-3 text-xs text-gray-500 dark:text-gray-400">
                                                Hisobingiz yo'qmi? <a href="https://onlinepbx.ru" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">onlinepbx.ru</a> da ro'yxatdan o'ting
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 2 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center text-gray-900 dark:text-white font-bold text-sm shadow">2</div>
                                        </div>
                                        <div class="flex-1 pt-0.5">
                                            <p class="text-gray-900 dark:text-gray-900 dark:text-white font-medium mb-1">API kalitini oling</p>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">
                                                Kabinetda <strong class="text-gray-700 dark:text-gray-300">Integratsiya</strong> → <strong class="text-gray-700 dark:text-gray-300">API</strong> bo'limiga o'ting
                                            </p>
                                            <div class="bg-gray-100 dark:bg-gray-700/50 rounded-lg p-3">
                                                <div class="flex items-center gap-2 text-sm">
                                                    <span class="text-gray-500 dark:text-gray-400">Domain:</span>
                                                    <code class="bg-gray-200 dark:bg-gray-800 px-2 py-0.5 rounded text-blue-600 dark:text-blue-400">sizning_domain.onpbx.ru</code>
                                                </div>
                                                <div class="flex items-center gap-2 text-sm mt-2">
                                                    <span class="text-gray-500 dark:text-gray-400">API Key:</span>
                                                    <code class="bg-gray-200 dark:bg-gray-800 px-2 py-0.5 rounded text-blue-600 dark:text-blue-400">xxxxxxxxxxxxxxxx</code>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 3 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center text-gray-900 dark:text-white font-bold text-sm shadow">3</div>
                                        </div>
                                        <div class="flex-1 pt-0.5">
                                            <p class="text-gray-900 dark:text-gray-900 dark:text-white font-medium mb-1">Webhook URL ni sozlang</p>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">
                                                <strong class="text-gray-700 dark:text-gray-300">Integratsiya</strong> → <strong class="text-gray-700 dark:text-gray-300">Webhooks</strong> bo'limiga o'ting va quyidagi URL ni qo'shing:
                                            </p>
                                            <div class="bg-gray-100 dark:bg-gray-700/50 rounded-lg p-3">
                                                <div class="flex items-center gap-2">
                                                    <code class="flex-1 bg-gray-200 dark:bg-gray-800 px-3 py-2 rounded text-green-600 dark:text-green-400 text-xs overflow-x-auto">{{ webhookUrls?.onlinepbx || 'https://sizning-domain.com/api/webhooks/pbx/onlinepbx' }}</code>
                                                    <button
                                                        v-if="webhookUrls?.onlinepbx"
                                                        @click="copyWebhookUrl(webhookUrls.onlinepbx)"
                                                        class="px-3 py-2 bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 rounded hover:bg-blue-200 dark:hover:bg-blue-500/30 text-xs"
                                                    >
                                                        Nusxalash
                                                    </button>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-2">
                                                    Bu URL orqali kiruvchi qo'ng'iroqlar haqida ma'lumot keladi
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 4 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center text-gray-900 dark:text-white font-bold text-sm shadow">4</div>
                                        </div>
                                        <div class="flex-1 pt-0.5">
                                            <p class="text-gray-900 dark:text-gray-900 dark:text-white font-medium mb-1">Ma'lumotlarni quyidagi formaga kiriting</p>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm">
                                                Barcha ma'lumotlarni to'g'ri kiritganingizdan so'ng "Ulanish" tugmasini bosing
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PBX Connect Form -->
                            <form @submit.prevent="connectPbx" class="max-w-lg mx-auto text-left">
                                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-6 space-y-5">
                                    <h4 class="text-gray-900 dark:text-gray-900 dark:text-white font-medium text-center mb-4">OnlinePBX ma'lumotlarini kiriting</h4>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            API URL
                                            <span class="text-red-400">*</span>
                                        </label>
                                        <input
                                            v-model="pbxForm.api_url"
                                            type="url"
                                            required
                                            placeholder="https://sizning_domain.onpbx.ru"
                                            class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                        />
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Misol: https://mycompany.onpbx.ru
                                        </p>
                                        <p v-if="pbxForm.errors.api_url" class="mt-1 text-sm text-red-400">{{ pbxForm.errors.api_url }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            API Key
                                            <span class="text-red-400">*</span>
                                        </label>
                                        <input
                                            v-model="pbxForm.api_key"
                                            type="text"
                                            required
                                            placeholder="API kalitingizni kiriting"
                                            class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 font-mono"
                                        />
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            OnlinePBX kabinetidan olingan API kalit
                                        </p>
                                        <p v-if="pbxForm.errors.api_key" class="mt-1 text-sm text-red-400">{{ pbxForm.errors.api_key }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            API Secret
                                            <span class="text-gray-500 dark:text-gray-400 font-normal ml-1">(ixtiyoriy)</span>
                                        </label>
                                        <input
                                            v-model="pbxForm.api_secret"
                                            type="password"
                                            placeholder="Agar mavjud bo'lsa"
                                            class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                        />
                                        <p v-if="pbxForm.errors.api_secret" class="mt-1 text-sm text-red-400">{{ pbxForm.errors.api_secret }}</p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Chiquvchi raqam
                                                <span class="text-red-400">*</span>
                                            </label>
                                            <input
                                                v-model="pbxForm.caller_id"
                                                type="text"
                                                required
                                                placeholder="+998901234567"
                                                class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                            />
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Caller ID
                                            </p>
                                            <p v-if="pbxForm.errors.caller_id" class="mt-1 text-sm text-red-400">{{ pbxForm.errors.caller_id }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Extension
                                                <span class="text-gray-500 dark:text-gray-400 font-normal ml-1">(ichki)</span>
                                            </label>
                                            <input
                                                v-model="pbxForm.extension"
                                                type="text"
                                                placeholder="101"
                                                class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                            />
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Ichki raqam
                                            </p>
                                            <p v-if="pbxForm.errors.extension" class="mt-1 text-sm text-red-400">{{ pbxForm.errors.extension }}</p>
                                        </div>
                                    </div>

                                    <button
                                        type="submit"
                                        :disabled="isConnecting || pbxForm.processing"
                                        class="w-full py-3.5 bg-gradient-to-r from-blue-500 to-indigo-600 text-gray-900 dark:text-white font-semibold rounded-xl hover:from-blue-600 hover:to-indigo-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-blue-500/25"
                                    >
                                        <span v-if="isConnecting || pbxForm.processing" class="flex items-center justify-center">
                                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-900 dark:text-white" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Ulanish tekshirilmoqda...
                                        </span>
                                        <span v-else class="flex items-center justify-center gap-2">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                            Ulanish
                                        </span>
                                    </button>
                                </div>
                            </form>

                            <!-- Help Section -->
                            <div class="mt-8 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Muammo bo'lsa?
                                    <a href="https://onlinepbx.ru/support" target="_blank" class="text-blue-400 hover:text-blue-300 ml-1">OnlinePBX qo'llab-quvvatlash</a>
                                    yoki
                                    <a href="mailto:support@biznespilot.uz" class="text-blue-400 hover:text-blue-300 ml-1">bizga yozing</a>
                                </p>
                            </div>
                        </div>

                        <!-- PBX Connected -->
                        <div v-else>
                            <!-- Connection Status Header -->
                            <div class="mb-6 p-5 bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 rounded-xl">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center shadow-lg">
                                            <svg class="w-6 h-6 text-gray-900 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-900 dark:text-white">OnlinePBX Ulangan</h3>
                                                <span class="px-2 py-0.5 bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400 text-xs font-medium rounded-full">Faol</span>
                                            </div>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">{{ pbxAccount.name || pbxAccount.api_url }}</p>
                                        </div>
                                    </div>
                                    <button
                                        @click="disconnectPbx"
                                        :disabled="isDisconnecting"
                                        class="px-4 py-2 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-500/20 transition-colors disabled:opacity-50 flex items-center gap-2"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        <span v-if="isDisconnecting">Uzilmoqda...</span>
                                        <span v-else>Uzish</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Account Details Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                                <!-- Caller ID -->
                                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4 border border-gray-200 dark:border-gray-600/30">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Chiquvchi raqam</p>
                                    </div>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white font-mono">{{ pbxAccount.caller_id }}</p>
                                </div>

                                <!-- Extension -->
                                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4 border border-gray-200 dark:border-gray-600/30">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                        </svg>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Ichki raqam</p>
                                    </div>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white font-mono">{{ pbxAccount.extension || '—' }}</p>
                                </div>

                                <!-- Balance -->
                                <div class="bg-blue-50 dark:bg-blue-500/10 rounded-xl p-4 border border-blue-200 dark:border-blue-500/30">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <p class="text-sm text-blue-600 dark:text-blue-400">Balans</p>
                                        </div>
                                        <button
                                            @click="refreshBalance"
                                            :disabled="isRefreshingBalance"
                                            class="p-1.5 bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-500/30 disabled:opacity-50 transition-colors"
                                            title="Balansni yangilash"
                                        >
                                            <svg :class="{'animate-spin': isRefreshingBalance}" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ pbxAccount.balance || 0 }} <span class="text-sm font-normal">so'm</span></p>
                                </div>

                                <!-- Last Sync -->
                                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4 border border-gray-200 dark:border-gray-600/30">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Oxirgi sinx</p>
                                    </div>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white">{{ formatDate(pbxAccount.last_sync_at) }}</p>
                                </div>
                            </div>

                            <!-- Webhook URL Info -->
                            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/20 rounded-xl border border-gray-200 dark:border-gray-600/30">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-green-100 dark:bg-green-500/20 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Webhook URL (OnlinePBX kabinetida sozlangan bo'lishi kerak)</p>
                                        <div class="flex items-center gap-2">
                                            <code class="flex-1 bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-lg text-green-600 dark:text-green-400 text-xs font-mono overflow-x-auto">{{ webhookUrls?.onlinepbx || '—' }}</code>
                                            <button
                                                v-if="webhookUrls?.onlinepbx"
                                                @click="copyWebhookUrl(webhookUrls.onlinepbx)"
                                                class="px-3 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 text-xs transition-colors"
                                            >
                                                Nusxalash
                                            </button>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                            Bu URL OnlinePBX kabinetidagi Integratsiya → Webhooks bo'limida sozlangan bo'lishi kerak
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- PBX Statistics -->
                            <div v-if="stats" class="pt-6 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center">
                                            <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white">Qo'ng'iroqlar Statistikasi</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Oxirgi 30 kun</p>
                                        </div>
                                    </div>
                                    <Link :href="route('integrations.telephony.history')" class="px-4 py-2 bg-blue-500/10 border border-blue-500/30 text-blue-400 rounded-xl hover:bg-blue-500/20 text-sm transition-colors">
                                        Batafsil ko'rish
                                    </Link>
                                </div>

                                <!-- Main Stats -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
                                    <Link
                                        :href="route('integrations.telephony.history')"
                                        class="group relative bg-gray-50 dark:bg-gray-700/50 rounded-2xl p-5 text-center hover:bg-gray-100 dark:hover:bg-gray-700/70 transition-all duration-300 border border-gray-200 dark:border-gray-600/30 hover:border-gray-300 dark:hover:border-gray-500/50 hover:shadow-lg hover:-translate-y-0.5"
                                    >
                                        <div class="w-10 h-10 bg-gray-200 dark:bg-gray-600/50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:bg-gray-300 dark:group-hover:bg-gray-500/50 transition-colors">
                                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                        </div>
                                        <p class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ stats.total_calls }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Jami qo'ng'iroqlar</p>
                                    </Link>
                                    <Link
                                        :href="route('integrations.telephony.history', { status: 'completed' })"
                                        class="group relative bg-gradient-to-br from-green-500/10 to-emerald-500/10 rounded-2xl p-5 text-center hover:from-green-500/20 hover:to-emerald-500/20 transition-all duration-300 border border-green-500/30 hover:border-green-400/50 hover:shadow-lg hover:shadow-green-900/30 hover:-translate-y-0.5"
                                    >
                                        <div class="w-10 h-10 bg-green-500/20 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:bg-green-500/30 transition-colors">
                                            <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <p class="text-3xl font-bold text-green-400 mb-1">{{ stats.answered_calls }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Javob berilgan</p>
                                    </Link>
                                    <Link
                                        :href="route('integrations.telephony.history', { status: 'missed' })"
                                        class="group relative bg-gradient-to-br from-yellow-500/10 to-amber-500/10 rounded-2xl p-5 text-center hover:from-yellow-500/20 hover:to-amber-500/20 transition-all duration-300 border border-yellow-500/30 hover:border-yellow-400/50 hover:shadow-lg hover:shadow-yellow-900/30 hover:-translate-y-0.5"
                                    >
                                        <div class="w-10 h-10 bg-yellow-500/20 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:bg-yellow-500/30 transition-colors">
                                            <svg class="w-5 h-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        </div>
                                        <p class="text-3xl font-bold text-yellow-400 mb-1">{{ stats.missed_calls }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Javobsiz</p>
                                    </Link>
                                    <div class="group relative bg-gradient-to-br from-blue-500/10 to-indigo-500/10 rounded-2xl p-5 text-center border border-blue-500/30">
                                        <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center mx-auto mb-3">
                                            <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                            </svg>
                                        </div>
                                        <p class="text-3xl font-bold text-blue-400 mb-1">{{ stats.answer_rate }}%</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Javob ulushi</p>
                                    </div>
                                </div>

                                <!-- Secondary Stats -->
                                <div class="grid grid-cols-3 gap-4">
                                    <div class="group bg-gray-100 dark:bg-gray-700/30 rounded-2xl p-4 text-center border border-gray-200 dark:border-gray-600/30 hover:bg-gray-100 dark:bg-gray-700/50 hover:border-gray-300 dark:hover:border-gray-500/40 transition-all duration-200">
                                        <div class="w-9 h-9 bg-blue-500/20 rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:bg-blue-500/30 transition-colors">
                                            <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 17l-4 4m0 0l-4-4m4 4V3" />
                                            </svg>
                                        </div>
                                        <p class="text-xl font-bold text-gray-900 dark:text-white mb-0.5">{{ stats.outbound_calls }}</p>
                                        <span class="text-gray-500 dark:text-gray-400 text-xs">Chiquvchi</span>
                                    </div>
                                    <div class="group bg-gray-100 dark:bg-gray-700/30 rounded-2xl p-4 text-center border border-gray-200 dark:border-gray-600/30 hover:bg-gray-100 dark:bg-gray-700/50 hover:border-gray-300 dark:hover:border-gray-500/40 transition-all duration-200">
                                        <div class="w-9 h-9 bg-green-500/20 rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:bg-green-500/30 transition-colors">
                                            <svg class="w-4 h-4 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7l4-4m0 0l4 4m-4-4v18" />
                                            </svg>
                                        </div>
                                        <p class="text-xl font-bold text-gray-900 dark:text-white mb-0.5">{{ stats.inbound_calls }}</p>
                                        <span class="text-gray-500 dark:text-gray-400 text-xs">Kiruvchi</span>
                                    </div>
                                    <div class="group bg-gray-100 dark:bg-gray-700/30 rounded-2xl p-4 text-center border border-gray-200 dark:border-gray-600/30 hover:bg-gray-100 dark:bg-gray-700/50 hover:border-gray-300 dark:hover:border-gray-500/40 transition-all duration-200">
                                        <div class="w-9 h-9 bg-purple-500/20 rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:bg-purple-500/30 transition-colors">
                                            <svg class="w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-xl font-bold text-gray-900 dark:text-white mb-0.5">{{ formatDuration(stats.total_duration) }}</p>
                                        <span class="text-gray-500 dark:text-gray-400 text-xs">Jami vaqt</span>
                                    </div>
                                </div>
                            </div>

                            <!-- No Stats Yet -->
                            <div v-else class="pt-6 border-t border-gray-200 dark:border-gray-700">
                                <div class="text-center py-8">
                                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700/50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 mb-2">Hali qo'ng'iroqlar statistikasi mavjud emas</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Qo'ng'iroqlar amalga oshirilgandan so'ng bu yerda statistika ko'rinadi</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SipUni Tab Content -->
                    <div v-if="activeTab === 'sipuni'" class="p-6 lg:p-8">
                        <!-- SipUni Not Connected -->
                        <div v-if="!sipuniAccount">
                            <!-- Step-by-step Instructions -->
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white mb-4">SipUni ulash bo'yicha yo'riqnoma</h3>
                                <div class="space-y-4">
                                    <!-- Step 1 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center text-gray-900 dark:text-white font-bold text-sm">1</div>
                                        <div class="flex-1">
                                            <p class="text-gray-600 dark:text-gray-300 font-medium">SipUni kabinetiga kiring</p>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                                                <a href="https://sipuni.com/login" target="_blank" class="text-purple-400 hover:text-purple-300 underline">sipuni.com/login</a>
                                                sahifasiga kiring
                                            </p>
                                        </div>
                                    </div>
                                    <!-- Step 2 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center text-gray-900 dark:text-white font-bold text-sm">2</div>
                                        <div class="flex-1">
                                            <p class="text-gray-600 dark:text-gray-300 font-medium">API sozlamalariga o'ting</p>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                                                <strong class="text-gray-500 dark:text-gray-400">Sozlamalar</strong> → <strong class="text-gray-500 dark:text-gray-400">API</strong> bo'limini oching
                                            </p>
                                        </div>
                                    </div>
                                    <!-- Step 3 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center text-gray-900 dark:text-white font-bold text-sm">3</div>
                                        <div class="flex-1">
                                            <p class="text-gray-600 dark:text-gray-300 font-medium">User ID va Secret ni nusxalang</p>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                                                API sahifasida <strong class="text-purple-400">user</strong> (raqam) va <strong class="text-purple-400">secret</strong> qiymatlarini ko'rasiz
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Visual Example -->
                            <div class="mb-8 p-4 bg-gray-100 dark:bg-gray-700/30 rounded-xl border border-gray-300 dark:border-gray-600/50">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3 uppercase tracking-wide">SipUni API sahifasidan misol:</p>
                                <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4 font-mono text-sm">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-gray-500 dark:text-gray-400">user:</span>
                                        <span class="text-purple-400 bg-purple-500/10 px-2 py-0.5 rounded">123456</span>
                                        <span class="text-gray-500 text-xs">← bu User ID</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-500 dark:text-gray-400">secret:</span>
                                        <span class="text-purple-400 bg-purple-500/10 px-2 py-0.5 rounded">abc123def456...</span>
                                        <span class="text-gray-500 text-xs">← bu Secret Key</span>
                                    </div>
                                </div>
                            </div>

                            <!-- SipUni Connect Form -->
                            <form @submit.prevent="connectSipuni" class="max-w-md mx-auto text-left space-y-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        User ID
                                        <span class="text-gray-500 dark:text-gray-400 font-normal ml-1">(user qiymati)</span>
                                    </label>
                                    <input
                                        v-model="sipuniForm.api_key"
                                        type="text"
                                        required
                                        placeholder="123456"
                                        class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 dark:bg-gray-700"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Faqat raqamlar (email emas!)
                                    </p>
                                    <p v-if="sipuniForm.errors.api_key" class="mt-1 text-sm text-red-400">{{ sipuniForm.errors.api_key }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Secret Key
                                        <span class="text-gray-500 dark:text-gray-400 font-normal ml-1">(secret qiymati)</span>
                                    </label>
                                    <input
                                        v-model="sipuniForm.api_secret"
                                        type="password"
                                        required
                                        placeholder="abc123def456..."
                                        class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 dark:bg-gray-700"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Sayt parolingiz emas! API secret kalit
                                    </p>
                                    <p v-if="sipuniForm.errors.api_secret" class="mt-1 text-sm text-red-400">{{ sipuniForm.errors.api_secret }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Chiquvchi raqam
                                        <span class="text-gray-500 dark:text-gray-400 font-normal ml-1">(Caller ID)</span>
                                    </label>
                                    <input
                                        v-model="sipuniForm.caller_id"
                                        type="text"
                                        required
                                        placeholder="+998901234567"
                                        class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 dark:bg-gray-700"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        SipUni da ro'yxatdan o'tgan telefon raqamingiz
                                    </p>
                                    <p v-if="sipuniForm.errors.caller_id" class="mt-1 text-sm text-red-400">{{ sipuniForm.errors.caller_id }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Ichki raqam
                                        <span class="text-gray-500 dark:text-gray-400 font-normal ml-1">(sipnumber - ixtiyoriy)</span>
                                    </label>
                                    <input
                                        v-model="sipuniForm.extension"
                                        type="text"
                                        placeholder="100"
                                        class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 dark:bg-gray-700"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        SipUni dagi ichki raqamingiz (qo'ng'iroq boshlash uchun)
                                    </p>
                                    <p v-if="sipuniForm.errors.extension" class="mt-1 text-sm text-red-400">{{ sipuniForm.errors.extension }}</p>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="isConnecting || sipuniForm.processing"
                                    class="w-full py-3 bg-gradient-to-r from-purple-500 to-violet-600 text-gray-900 dark:text-white font-semibold rounded-xl hover:from-purple-600 hover:to-violet-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isConnecting || sipuniForm.processing" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-900 dark:text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Tekshirilmoqda...
                                    </span>
                                    <span v-else>Ulanish</span>
                                </button>
                            </form>

                            <div class="mt-8 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    SipUni hisobingiz yo'qmi?
                                    <a href="https://sipuni.com/register" target="_blank" class="text-purple-400 hover:text-purple-300 underline ml-1">Ro'yxatdan o'tish</a>
                                </p>
                            </div>
                        </div>

                        <!-- SipUni Connected -->
                        <div v-else>
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center mr-4">
                                        <svg class="w-6 h-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white">SipUni Ulangan</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ sipuniAccount.name }}</p>
                                    </div>
                                </div>
                                <button
                                    @click="disconnectSipuni"
                                    :disabled="isDisconnecting"
                                    class="px-4 py-2 bg-red-500/20 text-red-400 rounded-xl hover:bg-red-500/30 transition-colors disabled:opacity-50"
                                >
                                    <span v-if="isDisconnecting">Uzilmoqda...</span>
                                    <span v-else>Uzish</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Caller ID</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white">{{ sipuniAccount.caller_id }}</p>
                                </div>
                                <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Ichki raqam</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white">{{ sipuniAccount.extension || '100' }}</p>
                                </div>
                                <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Balans</p>
                                        <button @click="refreshBalance" :disabled="isRefreshingBalance" class="text-purple-400 hover:text-purple-300 disabled:opacity-50">
                                            <svg :class="{'animate-spin': isRefreshingBalance}" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-lg font-semibold text-purple-400">{{ sipuniAccount.balance || 0 }} so'm</p>
                                </div>
                                <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Oxirgi yangilanish</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white">{{ formatDate(sipuniAccount.last_sync_at) }}</p>
                                </div>
                            </div>

                            <!-- SipUni Statistics -->
                            <div v-if="stats" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white">Qo'ng'iroqlar Statistikasi (30 kun)</h3>
                                    <Link :href="route('integrations.telephony.history')" class="text-purple-400 hover:text-purple-300 text-sm">
                                        Batafsil ko'rish
                                    </Link>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <Link
                                        :href="route('integrations.telephony.history')"
                                        class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 text-center hover:bg-gray-100 dark:bg-gray-700/50 transition-colors"
                                    >
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_calls }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Jami</p>
                                    </Link>
                                    <Link
                                        :href="route('integrations.telephony.history', { status: 'completed' })"
                                        class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 text-center hover:bg-gray-100 dark:bg-gray-700/50 transition-colors"
                                    >
                                        <p class="text-2xl font-bold text-green-400">{{ stats.answered_calls }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Javob berilgan</p>
                                    </Link>
                                    <Link
                                        :href="route('integrations.telephony.history', { status: 'missed' })"
                                        class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 text-center hover:bg-gray-100 dark:bg-gray-700/50 transition-colors"
                                    >
                                        <p class="text-2xl font-bold text-yellow-400">{{ stats.missed_calls }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Javobsiz</p>
                                    </Link>
                                    <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 text-center">
                                        <p class="text-2xl font-bold text-purple-400">{{ stats.answer_rate }}%</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Javob %</p>
                                    </div>
                                </div>
                                <div class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 text-center">
                                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ stats.outbound_calls }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Chiquvchi</p>
                                    </div>
                                    <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 text-center">
                                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ stats.inbound_calls }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Kiruvchi</p>
                                    </div>
                                    <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 text-center">
                                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatDuration(stats.total_duration) }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Jami vaqt</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MoiZvonki Tab Content -->
                    <div v-if="activeTab === 'moizvonki'" class="p-6 lg:p-8">
                        <!-- MoiZvonki Not Connected -->
                        <div v-if="!moiZvonkiAccount">
                            <!-- MoiZvonki Benefits -->
                            <div class="mb-8 p-5 bg-gradient-to-r from-orange-500/10 to-red-500/10 border border-orange-500/30 rounded-2xl">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-gray-900 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-orange-400 font-semibold mb-2">Moi Zvonki afzalliklari</h4>
                                        <ul class="text-gray-600 dark:text-gray-300 text-sm space-y-1">
                                            <li class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 bg-orange-400 rounded-full"></span>
                                                Android smartfonlardan qo'ng'iroqlarni avtomatik yozib olish
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 bg-orange-400 rounded-full"></span>
                                                Kiruvchi/chiquvchi qo'ng'iroqlar tarixi
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 bg-orange-400 rounded-full"></span>
                                                Qo'ng'iroq yozuvlarini tinglash
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 bg-orange-400 rounded-full"></span>
                                                Oyiga 150-200 rubl (har bir foydalanuvchi)
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Step-by-step Instructions -->
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                    Moi Zvonki ulash yo'riqnomasi
                                </h3>
                                <div class="space-y-6">
                                    <!-- Step 1 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center text-gray-900 dark:text-white font-bold shadow-lg">1</div>
                                        </div>
                                        <div class="flex-1 pt-1">
                                            <p class="text-gray-900 dark:text-white font-medium mb-1">MoiZvonki hisobini yarating</p>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">
                                                <a href="https://moizvonki.ru" target="_blank" class="text-orange-400 hover:text-orange-300 underline inline-flex items-center gap-1">
                                                    moizvonki.ru
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                </a>
                                                saytida ro'yxatdan o'ting
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Step 2 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center text-gray-900 dark:text-white font-bold shadow-lg">2</div>
                                        </div>
                                        <div class="flex-1 pt-1">
                                            <p class="text-gray-900 dark:text-white font-medium mb-1">Android ilovasini o'rnating</p>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">
                                                Play Store dan <strong class="text-gray-600 dark:text-gray-300">Moi Zvonki</strong> ilovasini yuklab, smartfoningizga o'rnating
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Step 3 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center text-gray-900 dark:text-white font-bold shadow-lg">3</div>
                                        </div>
                                        <div class="flex-1 pt-1">
                                            <p class="text-gray-900 dark:text-white font-medium mb-1">API kalitini oling</p>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">
                                                MoiZvonki kabinetida <strong class="text-gray-600 dark:text-gray-300">Настройки</strong> → <strong class="text-gray-600 dark:text-gray-300">API</strong> bo'limidan API kalitini nusxalang
                                            </p>
                                            <div class="bg-gray-100 dark:bg-gray-700/30 rounded-lg p-3">
                                                <div class="flex items-center gap-2 text-sm">
                                                    <span class="text-gray-500 dark:text-gray-400">URL:</span>
                                                    <code class="bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded text-orange-400">sizning_domen.moizvonki.ru</code>
                                                </div>
                                                <div class="flex items-center gap-2 text-sm mt-2">
                                                    <span class="text-gray-500 dark:text-gray-400">API Key:</span>
                                                    <code class="bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded text-orange-400">xxxxxxxxxxxxxxxx</code>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 4 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center text-gray-900 dark:text-white font-bold shadow-lg">4</div>
                                        </div>
                                        <div class="flex-1 pt-1">
                                            <p class="text-gray-900 dark:text-white font-medium mb-1">Webhook URL ni sozlang</p>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">
                                                MoiZvonki kabinetida quyidagi webhook URL ni qo'shing:
                                            </p>
                                            <div class="bg-gray-100 dark:bg-gray-700/30 rounded-lg p-3">
                                                <div class="flex items-center gap-2">
                                                    <code class="flex-1 bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded text-green-400 text-xs overflow-x-auto">{{ webhookUrls?.moizvonki || 'https://sizning-domain.com/api/webhooks/moizvonki' }}</code>
                                                    <button
                                                        v-if="webhookUrls?.moizvonki"
                                                        @click="copyWebhookUrl(webhookUrls.moizvonki)"
                                                        class="px-3 py-2 bg-orange-500/20 text-orange-400 rounded hover:bg-orange-500/30 text-xs"
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
                            <form @submit.prevent="connectMoiZvonki" class="max-w-lg mx-auto text-left">
                                <div class="bg-gray-50 dark:bg-gray-700/20 rounded-2xl p-6 space-y-5">
                                    <h4 class="text-gray-900 dark:text-white font-medium text-center mb-4">MoiZvonki ma'lumotlarini kiriting</h4>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Email
                                            <span class="text-red-400">*</span>
                                        </label>
                                        <input
                                            v-model="moiZvonkiForm.email"
                                            type="email"
                                            required
                                            placeholder="email@example.com"
                                            class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-orange-500 focus:ring-1 focus:ring-orange-500"
                                        />
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            MoiZvonki kabinetiga kirish emailingiz
                                        </p>
                                        <p v-if="moiZvonkiForm.errors.email" class="mt-1 text-sm text-red-400">{{ moiZvonkiForm.errors.email }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            API URL
                                            <span class="text-red-400">*</span>
                                        </label>
                                        <input
                                            v-model="moiZvonkiForm.api_url"
                                            type="text"
                                            required
                                            placeholder="mycompany.moizvonki.ru"
                                            class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-orange-500 focus:ring-1 focus:ring-orange-500"
                                        />
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Sizning MoiZvonki domeningiz
                                        </p>
                                        <p v-if="moiZvonkiForm.errors.api_url" class="mt-1 text-sm text-red-400">{{ moiZvonkiForm.errors.api_url }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            API Key
                                            <span class="text-red-400">*</span>
                                        </label>
                                        <input
                                            v-model="moiZvonkiForm.api_key"
                                            type="text"
                                            required
                                            placeholder="API kalitingizni kiriting"
                                            class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-orange-500 focus:ring-1 focus:ring-orange-500 font-mono"
                                        />
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            MoiZvonki kabinetidan olingan API kalit
                                        </p>
                                        <p v-if="moiZvonkiForm.errors.api_key" class="mt-1 text-sm text-red-400">{{ moiZvonkiForm.errors.api_key }}</p>
                                    </div>

                                    <button
                                        type="submit"
                                        :disabled="isConnecting || moiZvonkiForm.processing"
                                        class="w-full py-3.5 bg-gradient-to-r from-orange-500 to-red-600 text-gray-900 dark:text-white font-semibold rounded-xl hover:from-orange-600 hover:to-red-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-orange-500/25"
                                    >
                                        <span v-if="isConnecting || moiZvonkiForm.processing" class="flex items-center justify-center">
                                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-900 dark:text-white" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Ulanish tekshirilmoqda...
                                        </span>
                                        <span v-else class="flex items-center justify-center gap-2">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                            Ulanish
                                        </span>
                                    </button>
                                </div>
                            </form>

                            <!-- Help Section -->
                            <div class="mt-8 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Yordam kerakmi?
                                    <a href="https://moizvonki.ru/help" target="_blank" class="text-orange-400 hover:text-orange-300 ml-1">MoiZvonki qo'llab-quvvatlash</a>
                                </p>
                            </div>
                        </div>

                        <!-- MoiZvonki Connected -->
                        <div v-else>
                            <!-- Connection Status Header -->
                            <div class="mb-6 p-5 bg-gradient-to-r from-green-500/10 to-emerald-500/10 border border-green-500/30 rounded-2xl">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="w-14 h-14 bg-green-500 rounded-xl flex items-center justify-center shadow-lg shadow-green-500/25">
                                            <svg class="w-7 h-7 text-gray-900 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">MoiZvonki Ulangan</h3>
                                                <span class="px-2 py-0.5 bg-green-500/20 text-green-400 text-xs font-medium rounded-full">Faol</span>
                                            </div>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">{{ moiZvonkiAccount.name || moiZvonkiAccount.api_url }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button
                                            @click="syncMoiZvonki"
                                            :disabled="isSyncing"
                                            class="px-4 py-2.5 bg-orange-500/10 border border-orange-500/30 text-orange-400 rounded-xl hover:bg-orange-500/20 transition-colors disabled:opacity-50 flex items-center gap-2"
                                        >
                                            <svg :class="{'animate-spin': isSyncing}" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            <span v-if="isSyncing">Sinxronlanmoqda...</span>
                                            <span v-else>Sinxronlash</span>
                                        </button>
                                        <button
                                            @click="disconnectMoiZvonki"
                                            :disabled="isDisconnecting"
                                            class="px-4 py-2.5 bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl hover:bg-red-500/20 transition-colors disabled:opacity-50 flex items-center gap-2"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            <span v-if="isDisconnecting">Uzilmoqda...</span>
                                            <span v-else>Uzish</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Account Details Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                                <!-- API URL -->
                                <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 border border-gray-200 dark:border-gray-600/30">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                        </svg>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">API URL</p>
                                    </div>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white truncate">{{ moiZvonkiAccount.api_url }}</p>
                                </div>

                                <!-- Email -->
                                <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 border border-gray-200 dark:border-gray-600/30">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                                    </div>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white truncate">{{ moiZvonkiAccount.email }}</p>
                                </div>

                                <!-- Last Sync -->
                                <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 border border-gray-200 dark:border-gray-600/30">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Oxirgi sinx</p>
                                    </div>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white">{{ formatDate(moiZvonkiAccount.last_sync_at) }}</p>
                                </div>
                            </div>

                            <!-- Webhook URL Info -->
                            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/20 rounded-xl border border-gray-200 dark:border-gray-600/30">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-green-500/20 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <svg class="w-4 h-4 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Webhook URL (MoiZvonki kabinetida sozlangan bo'lishi kerak)</p>
                                        <div class="flex items-center gap-2">
                                            <code class="flex-1 bg-gray-100 dark:bg-gray-800/50 px-3 py-2 rounded-lg text-green-400 text-xs font-mono overflow-x-auto">{{ webhookUrls?.moizvonki || '—' }}</code>
                                            <button
                                                v-if="webhookUrls?.moizvonki"
                                                @click="copyWebhookUrl(webhookUrls.moizvonki)"
                                                class="px-3 py-2 bg-gray-200 dark:bg-gray-600/50 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 text-xs transition-colors"
                                            >
                                                Nusxalash
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- MoiZvonki Statistics -->
                            <div v-if="stats" class="pt-6 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-orange-500/20 rounded-xl flex items-center justify-center">
                                            <svg class="w-5 h-5 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white">Qo'ng'iroqlar Statistikasi</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Oxirgi 30 kun</p>
                                        </div>
                                    </div>
                                    <Link :href="route('integrations.telephony.history')" class="px-4 py-2 bg-orange-500/10 border border-orange-500/30 text-orange-400 rounded-xl hover:bg-orange-500/20 text-sm transition-colors">
                                        Batafsil ko'rish
                                    </Link>
                                </div>

                                <!-- Main Stats -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                    <Link
                                        :href="route('integrations.telephony.history')"
                                        class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-5 text-center hover:bg-gray-100 dark:hover:bg-gray-700/70 transition-all border border-gray-200 dark:border-gray-600/30"
                                    >
                                        <p class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ stats.total_calls }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Jami qo'ng'iroqlar</p>
                                    </Link>
                                    <Link
                                        :href="route('integrations.telephony.history', { status: 'completed' })"
                                        class="bg-gradient-to-br from-green-500/10 to-emerald-500/10 rounded-xl p-5 text-center hover:from-green-500/20 hover:to-emerald-500/20 transition-all border border-green-500/30"
                                    >
                                        <p class="text-3xl font-bold text-green-400 mb-1">{{ stats.answered_calls }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Javob berilgan</p>
                                    </Link>
                                    <Link
                                        :href="route('integrations.telephony.history', { status: 'missed' })"
                                        class="bg-gradient-to-br from-yellow-500/10 to-amber-500/10 rounded-xl p-5 text-center hover:from-yellow-500/20 hover:to-amber-500/20 transition-all border border-yellow-500/30"
                                    >
                                        <p class="text-3xl font-bold text-yellow-400 mb-1">{{ stats.missed_calls }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Javobsiz</p>
                                    </Link>
                                    <div class="bg-gradient-to-br from-orange-500/10 to-red-500/10 rounded-xl p-5 text-center border border-orange-500/30">
                                        <p class="text-3xl font-bold text-orange-400 mb-1">{{ stats.answer_rate }}%</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Javob ulushi</p>
                                    </div>
                                </div>

                                <!-- Secondary Stats -->
                                <div class="grid grid-cols-3 gap-4">
                                    <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 text-center border border-gray-200 dark:border-gray-600/30">
                                        <div class="flex items-center justify-center gap-2 mb-2">
                                            <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 17l-4 4m0 0l-4-4m4 4V3" />
                                            </svg>
                                            <span class="text-gray-500 dark:text-gray-400 text-xs">Chiquvchi</span>
                                        </div>
                                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ stats.outbound_calls }}</p>
                                    </div>
                                    <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 text-center border border-gray-200 dark:border-gray-600/30">
                                        <div class="flex items-center justify-center gap-2 mb-2">
                                            <svg class="w-4 h-4 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7l4-4m0 0l4 4m-4-4v18" />
                                            </svg>
                                            <span class="text-gray-500 dark:text-gray-400 text-xs">Kiruvchi</span>
                                        </div>
                                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ stats.inbound_calls }}</p>
                                    </div>
                                    <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 text-center border border-gray-200 dark:border-gray-600/30">
                                        <div class="flex items-center justify-center gap-2 mb-2">
                                            <svg class="w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="text-gray-500 dark:text-gray-400 text-xs">Jami vaqt</span>
                                        </div>
                                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatDuration(stats.total_duration) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- No Stats Yet -->
                            <div v-else class="pt-6 border-t border-gray-200 dark:border-gray-700">
                                <div class="text-center py-8">
                                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700/50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 mb-2">Hali qo'ng'iroqlar statistikasi mavjud emas</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Qo'ng'iroqlar amalga oshirilgandan so'ng bu yerda statistika ko'rinadi</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- UTEL Tab Content -->
                    <div v-if="activeTab === 'utel'" class="p-6 lg:p-8">
                        <!-- UTEL Not Connected -->
                        <div v-if="!utelAccount">
                            <!-- UTEL Benefits -->
                            <div class="mb-8 p-5 bg-gradient-to-r from-green-500/10 to-teal-500/10 border border-green-500/30 rounded-2xl">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <span class="text-gray-900 dark:text-white font-bold text-sm">UZ</span>
                                    </div>
                                    <div>
                                        <h4 class="text-green-400 font-semibold mb-2">UTEL afzalliklari</h4>
                                        <ul class="text-gray-600 dark:text-gray-300 text-sm space-y-1">
                                            <li class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 bg-green-400 rounded-full"></span>
                                                O'zbekiston VoIP provayderi - mahalliy narxlar
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 bg-green-400 rounded-full"></span>
                                                CRM dan bir klik bilan qo'ng'iroq qilish
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 bg-green-400 rounded-full"></span>
                                                Kiruvchi/chiquvchi qo'ng'iroqlar tarixi
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 bg-green-400 rounded-full"></span>
                                                Click/Payme orqali to'lov
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Step-by-step Instructions -->
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                    UTEL ulash yo'riqnomasi
                                </h3>
                                <div class="space-y-6">
                                    <!-- Step 1 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-teal-600 rounded-xl flex items-center justify-center text-gray-900 dark:text-white font-bold shadow-lg">1</div>
                                        </div>
                                        <div class="flex-1 pt-1">
                                            <p class="text-gray-900 dark:text-white font-medium mb-1">UTEL hisobini yarating</p>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">
                                                <a href="https://utel.uz" target="_blank" class="text-green-400 hover:text-green-300 underline inline-flex items-center gap-1">
                                                    utel.uz
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                </a>
                                                saytida ro'yxatdan o'ting
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Step 2 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-teal-600 rounded-xl flex items-center justify-center text-gray-900 dark:text-white font-bold shadow-lg">2</div>
                                        </div>
                                        <div class="flex-1 pt-1">
                                            <p class="text-gray-900 dark:text-white font-medium mb-1">Virtual raqam oling</p>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">
                                                UTEL kabinetidan O'zbekiston telefon raqamini sotib oling (71, 78, 90, 91, 93, 94, 97, 99)
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Step 3 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-teal-600 rounded-xl flex items-center justify-center text-gray-900 dark:text-white font-bold shadow-lg">3</div>
                                        </div>
                                        <div class="flex-1 pt-1">
                                            <p class="text-gray-900 dark:text-white font-medium mb-1">Ma'lumotlaringizni kiriting</p>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">
                                                Quyidagi forma orqali UTEL hisobingiz ma'lumotlarini kiriting
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- UTEL Connect Form -->
                            <form @submit.prevent="connectUtel" class="max-w-lg mx-auto text-left">
                                <div class="bg-gray-50 dark:bg-gray-700/20 rounded-2xl p-6 space-y-5">
                                    <h4 class="text-gray-900 dark:text-white font-medium text-center mb-4">UTEL ma'lumotlarini kiriting</h4>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Email
                                            <span class="text-red-400">*</span>
                                        </label>
                                        <input
                                            v-model="utelForm.email"
                                            type="email"
                                            required
                                            placeholder="email@example.com"
                                            class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-green-500 focus:ring-1 focus:ring-green-500"
                                        />
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            UTEL kabinetiga kirish emailingiz
                                        </p>
                                        <p v-if="utelForm.errors.email" class="mt-1 text-sm text-red-400">{{ utelForm.errors.email }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Parol
                                            <span class="text-red-400">*</span>
                                        </label>
                                        <input
                                            v-model="utelForm.password"
                                            type="password"
                                            required
                                            placeholder="UTEL parolingiz"
                                            class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-green-500 focus:ring-1 focus:ring-green-500"
                                        />
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            UTEL kabinetiga kirish parolingiz (xavfsiz saqlanadi)
                                        </p>
                                        <p v-if="utelForm.errors.password" class="mt-1 text-sm text-red-400">{{ utelForm.errors.password }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Caller ID (ixtiyoriy)
                                        </label>
                                        <input
                                            v-model="utelForm.caller_id"
                                            type="text"
                                            placeholder="+998901234567"
                                            class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-green-500 focus:ring-1 focus:ring-green-500"
                                        />
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Chiquvchi qo'ng'iroqlarda ko'rinadigan raqam
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Extension (ixtiyoriy)
                                        </label>
                                        <input
                                            v-model="utelForm.extension"
                                            type="text"
                                            placeholder="101"
                                            class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-green-500 focus:ring-1 focus:ring-green-500"
                                        />
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Ichki raqamingiz (agar PBX ishlatayotgan bo'lsangiz)
                                        </p>
                                    </div>

                                    <button
                                        type="submit"
                                        :disabled="isConnecting || utelForm.processing"
                                        class="w-full py-3.5 bg-gradient-to-r from-green-500 to-teal-600 text-gray-900 dark:text-white font-semibold rounded-xl hover:from-green-600 hover:to-teal-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-green-500/25"
                                    >
                                        <span v-if="isConnecting || utelForm.processing" class="flex items-center justify-center">
                                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-900 dark:text-white" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Ulanish tekshirilmoqda...
                                        </span>
                                        <span v-else class="flex items-center justify-center gap-2">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                            Ulanish
                                        </span>
                                    </button>
                                </div>
                            </form>

                            <!-- Help Section -->
                            <div class="mt-8 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Yordam kerakmi?
                                    <a href="https://utel.uz/support" target="_blank" class="text-green-400 hover:text-green-300 ml-1">UTEL qo'llab-quvvatlash</a>
                                </p>
                            </div>
                        </div>

                        <!-- UTEL Connected -->
                        <div v-else>
                            <!-- Connection Status Header -->
                            <div class="mb-6 p-5 bg-gradient-to-r from-green-500/10 to-emerald-500/10 border border-green-500/30 rounded-2xl">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="w-14 h-14 bg-green-500 rounded-xl flex items-center justify-center shadow-lg shadow-green-500/25">
                                            <svg class="w-7 h-7 text-gray-900 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">UTEL Ulangan</h3>
                                                <span class="px-2 py-0.5 bg-green-500/20 text-green-400 text-xs font-medium rounded-full">Faol</span>
                                            </div>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">{{ utelAccount.name || utelAccount.email }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button
                                            @click="syncUtel"
                                            :disabled="isSyncing"
                                            class="px-4 py-2.5 bg-green-500/10 border border-green-500/30 text-green-400 rounded-xl hover:bg-green-500/20 transition-colors disabled:opacity-50 flex items-center gap-2"
                                        >
                                            <svg :class="{'animate-spin': isSyncing}" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            <span v-if="isSyncing">Sinxronlanmoqda...</span>
                                            <span v-else>Sinxronlash</span>
                                        </button>
                                        <button
                                            @click="disconnectUtel"
                                            :disabled="isDisconnecting"
                                            class="px-4 py-2.5 bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl hover:bg-red-500/20 transition-colors disabled:opacity-50 flex items-center gap-2"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            <span v-if="isDisconnecting">Uzilmoqda...</span>
                                            <span v-else>Uzish</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Account Details Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                                <!-- Email -->
                                <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 border border-gray-200 dark:border-gray-600/30">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                                    </div>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white truncate">{{ utelAccount.email }}</p>
                                </div>

                                <!-- Caller ID -->
                                <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 border border-gray-200 dark:border-gray-600/30">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Caller ID</p>
                                    </div>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white">{{ utelAccount.caller_id || '—' }}</p>
                                </div>

                                <!-- Balance -->
                                <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 border border-gray-200 dark:border-gray-600/30">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Balans</p>
                                        </div>
                                        <button
                                            @click="refreshUtelBalance"
                                            :disabled="isRefreshingUtelBalance"
                                            class="p-1 text-gray-500 dark:text-gray-400 hover:text-green-400 transition-colors disabled:opacity-50"
                                            title="Balansni yangilash"
                                        >
                                            <svg :class="{'animate-spin': isRefreshingUtelBalance}" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-lg font-semibold text-green-400">{{ formatUtelBalance(utelAccount.balance, utelAccount.currency) }}</p>
                                </div>

                                <!-- Last Sync -->
                                <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 border border-gray-200 dark:border-gray-600/30">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Oxirgi sinx</p>
                                    </div>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white">{{ formatDate(utelAccount.last_sync_at) }}</p>
                                </div>
                            </div>

                            <!-- Webhook URL Info -->
                            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/20 rounded-xl border border-gray-200 dark:border-gray-600/30">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-green-500/20 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <svg class="w-4 h-4 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Webhook URL (UTEL kabinetida sozlash kerak)</p>
                                        <div class="flex items-center gap-2">
                                            <code class="flex-1 bg-gray-100 dark:bg-gray-800/50 px-3 py-2 rounded-lg text-green-400 text-xs font-mono overflow-x-auto">{{ webhookUrls?.utel || '—' }}</code>
                                            <button
                                                v-if="webhookUrls?.utel"
                                                @click="copyWebhookUrl(webhookUrls.utel)"
                                                class="px-3 py-2 bg-gray-200 dark:bg-gray-600/50 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 text-xs transition-colors"
                                            >
                                                Nusxalash
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- UTEL Statistics -->
                            <div v-if="stats" class="pt-6 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-green-500/20 rounded-xl flex items-center justify-center">
                                            <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white">Qo'ng'iroqlar Statistikasi</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Oxirgi 30 kun</p>
                                        </div>
                                    </div>
                                    <Link :href="route('integrations.telephony.history')" class="px-4 py-2 bg-green-500/10 border border-green-500/30 text-green-400 rounded-xl hover:bg-green-500/20 text-sm transition-colors">
                                        Batafsil ko'rish
                                    </Link>
                                </div>

                                <!-- Main Stats -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                    <Link
                                        :href="route('integrations.telephony.history')"
                                        class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-5 text-center hover:bg-gray-100 dark:hover:bg-gray-700/70 transition-all border border-gray-200 dark:border-gray-600/30"
                                    >
                                        <p class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ stats.total_calls }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Jami qo'ng'iroqlar</p>
                                    </Link>
                                    <Link
                                        :href="route('integrations.telephony.history', { status: 'completed' })"
                                        class="bg-gradient-to-br from-green-500/10 to-emerald-500/10 rounded-xl p-5 text-center hover:from-green-500/20 hover:to-emerald-500/20 transition-all border border-green-500/30"
                                    >
                                        <p class="text-3xl font-bold text-green-400 mb-1">{{ stats.answered_calls }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Javob berilgan</p>
                                    </Link>
                                    <Link
                                        :href="route('integrations.telephony.history', { status: 'missed' })"
                                        class="bg-gradient-to-br from-yellow-500/10 to-amber-500/10 rounded-xl p-5 text-center hover:from-yellow-500/20 hover:to-amber-500/20 transition-all border border-yellow-500/30"
                                    >
                                        <p class="text-3xl font-bold text-yellow-400 mb-1">{{ stats.missed_calls }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Javobsiz</p>
                                    </Link>
                                    <div class="bg-gradient-to-br from-green-500/10 to-teal-500/10 rounded-xl p-5 text-center border border-green-500/30">
                                        <p class="text-3xl font-bold text-green-400 mb-1">{{ stats.answer_rate }}%</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Javob ulushi</p>
                                    </div>
                                </div>

                                <!-- Secondary Stats -->
                                <div class="grid grid-cols-3 gap-4">
                                    <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 text-center border border-gray-200 dark:border-gray-600/30">
                                        <div class="flex items-center justify-center gap-2 mb-2">
                                            <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 17l-4 4m0 0l-4-4m4 4V3" />
                                            </svg>
                                            <span class="text-gray-500 dark:text-gray-400 text-xs">Chiquvchi</span>
                                        </div>
                                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ stats.outbound_calls }}</p>
                                    </div>
                                    <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 text-center border border-gray-200 dark:border-gray-600/30">
                                        <div class="flex items-center justify-center gap-2 mb-2">
                                            <svg class="w-4 h-4 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7l4-4m0 0l4 4m-4-4v18" />
                                            </svg>
                                            <span class="text-gray-500 dark:text-gray-400 text-xs">Kiruvchi</span>
                                        </div>
                                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ stats.inbound_calls }}</p>
                                    </div>
                                    <div class="bg-gray-100 dark:bg-gray-700/30 rounded-xl p-4 text-center border border-gray-200 dark:border-gray-600/30">
                                        <div class="flex items-center justify-center gap-2 mb-2">
                                            <svg class="w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="text-gray-500 dark:text-gray-400 text-xs">Jami vaqt</span>
                                        </div>
                                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatDuration(stats.total_duration) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- No Stats Yet -->
                            <div v-else class="pt-6 border-t border-gray-200 dark:border-gray-700">
                                <div class="text-center py-8">
                                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700/50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 mb-2">Hali qo'ng'iroqlar statistikasi mavjud emas</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Qo'ng'iroqlar amalga oshirilgandan so'ng bu yerda statistika ko'rinadi</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div v-if="hasActiveProvider" class="bg-gray-100 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-900 dark:text-white mb-4">Tezkor harakatlar</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Link
                            :href="route('integrations.telephony.history')"
                            class="flex items-center gap-4 p-4 bg-gray-100 dark:bg-gray-700/30 rounded-xl hover:bg-gray-100 dark:bg-gray-700/50 transition-colors"
                        >
                            <div class="w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-900 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Qo'ng'iroqlar tarixi</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Barcha qo'ng'iroqlarni ko'ring</p>
                            </div>
                        </Link>
                        <Link
                            :href="route('integrations.telephony.statistics')"
                            class="flex items-center gap-4 p-4 bg-gray-100 dark:bg-gray-700/30 rounded-xl hover:bg-gray-100 dark:bg-gray-700/50 transition-colors"
                        >
                            <div class="w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-900 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Statistika</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Batafsil tahlil</p>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
