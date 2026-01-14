<script setup>
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    pbxAccount: Object,
    sipuniAccount: Object,
    stats: Object,
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
    return props.pbxAccount ? 'pbx' : (props.sipuniAccount ? 'sipuni' : 'pbx');
};

const activeTab = ref(getInitialTab());
const singleProviderMode = computed(() => urlProvider === 'pbx' || urlProvider === 'sipuni');

const isConnecting = ref(false);
const isDisconnecting = ref(false);
const isRefreshingBalance = ref(false);

const hasActiveProvider = computed(() => props.pbxAccount || props.sipuniAccount);

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
</script>

<template>
    <Head title="Telefoniya Sozlamalari" />

    <BusinessLayout>
        <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
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
                        <div :class="[
                            'w-20 h-20 rounded-2xl flex items-center justify-center mr-6 shadow-lg',
                            activeTab === 'pbx'
                                ? 'bg-gradient-to-br from-blue-500 to-indigo-600'
                                : 'bg-gradient-to-br from-purple-500 to-violet-600'
                        ]">
                            <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">
                                {{ activeTab === 'pbx' ? 'PBX' : 'SipUni' }} Telefoniya
                            </h1>
                            <p class="text-slate-400 mt-2">
                                {{ activeTab === 'pbx' ? 'PBX orqali qo\'ng\'iroqlar' : 'SipUni orqali qo\'ng\'iroqlar' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Provider Tabs -->
                <div v-if="!singleProviderMode" class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl overflow-hidden mb-8">
                    <div class="flex border-b border-slate-700/50">
                        <button
                            @click="activeTab = 'pbx'"
                            :class="[
                                'flex-1 px-6 py-4 text-center font-medium transition-colors',
                                activeTab === 'pbx'
                                    ? 'text-blue-400 border-b-2 border-blue-400 bg-slate-700/30'
                                    : 'text-slate-400 hover:text-white'
                            ]"
                        >
                            <div class="flex items-center justify-center gap-3">
                                <div :class="[
                                    'w-7 h-7 rounded-lg flex items-center justify-center',
                                    activeTab === 'pbx' ? 'bg-blue-500' : 'bg-slate-600'
                                ]">
                                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <span>PBX</span>
                                <span v-if="pbxAccount" class="w-2 h-2 bg-green-400 rounded-full"></span>
                            </div>
                        </button>
                        <button
                            @click="activeTab = 'sipuni'"
                            :class="[
                                'flex-1 px-6 py-4 text-center font-medium transition-colors',
                                activeTab === 'sipuni'
                                    ? 'text-purple-400 border-b-2 border-purple-400 bg-slate-700/30'
                                    : 'text-slate-400 hover:text-white'
                            ]"
                        >
                            <div class="flex items-center justify-center gap-3">
                                <div :class="[
                                    'w-7 h-7 rounded-lg flex items-center justify-center',
                                    activeTab === 'sipuni' ? 'bg-purple-500' : 'bg-slate-600'
                                ]">
                                    <span class="text-white font-bold text-[10px]">SU</span>
                                </div>
                                <span>SipUni</span>
                                <span v-if="sipuniAccount" class="w-2 h-2 bg-green-400 rounded-full"></span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Provider Content -->
                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl overflow-hidden mb-8">
                    <!-- PBX Tab Content -->
                    <div v-if="activeTab === 'pbx'" class="p-8">
                        <!-- PBX Not Connected -->
                        <div v-if="!pbxAccount">
                            <!-- Step-by-step Instructions -->
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-white mb-4">PBX ulash bo'yicha yo'riqnoma</h3>
                                <div class="space-y-4">
                                    <!-- Step 1 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-sm">1</div>
                                        <div class="flex-1">
                                            <p class="text-slate-300 font-medium">PBX admin paneliga kiring</p>
                                            <p class="text-slate-500 text-sm mt-1">
                                                PBX tizimingiz admin panelidan API sozlamalarini oching
                                            </p>
                                        </div>
                                    </div>
                                    <!-- Step 2 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-sm">2</div>
                                        <div class="flex-1">
                                            <p class="text-slate-300 font-medium">API kalitlarini oling</p>
                                            <p class="text-slate-500 text-sm mt-1">
                                                <strong class="text-slate-400">API URL</strong>, <strong class="text-slate-400">API Key</strong> va <strong class="text-slate-400">Secret</strong> qiymatlarini nusxalang
                                            </p>
                                        </div>
                                    </div>
                                    <!-- Step 3 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-sm">3</div>
                                        <div class="flex-1">
                                            <p class="text-slate-300 font-medium">Qo'ng'iroq raqamini kiriting</p>
                                            <p class="text-slate-500 text-sm mt-1">
                                                Chiquvchi qo'ng'iroqlarda ko'rinadigan raqam (Caller ID)
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Supported PBX Info -->
                            <div class="mb-8 p-4 bg-blue-500/10 border border-blue-500/30 rounded-xl">
                                <p class="text-xs text-blue-300 mb-2">
                                    <strong>Qo'llab-quvvatlanadigan PBX tizimlari:</strong>
                                </p>
                                <p class="text-slate-400 text-sm">
                                    Asterisk, FreePBX, 3CX, Yeastar va boshqa API qo'llab-quvvatlaydigan tizimlar
                                </p>
                            </div>

                            <!-- PBX Connect Form -->
                            <form @submit.prevent="connectPbx" class="max-w-md mx-auto text-left space-y-5">
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                        API URL
                                        <span class="text-slate-500 font-normal ml-1">(PBX manzili)</span>
                                    </label>
                                    <input
                                        v-model="pbxForm.api_url"
                                        type="url"
                                        required
                                        placeholder="https://pbx.example.com/api"
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                    <p class="mt-1 text-xs text-slate-500">
                                        PBX tizimingizning API endpoint manzili
                                    </p>
                                    <p v-if="pbxForm.errors.api_url" class="mt-1 text-sm text-red-400">{{ pbxForm.errors.api_url }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                        API Key
                                        <span class="text-slate-500 font-normal ml-1">(foydalanuvchi nomi)</span>
                                    </label>
                                    <input
                                        v-model="pbxForm.api_key"
                                        type="text"
                                        required
                                        placeholder="api_user"
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                    <p class="mt-1 text-xs text-slate-500">
                                        API uchun yaratilgan foydalanuvchi nomi yoki kalit
                                    </p>
                                    <p v-if="pbxForm.errors.api_key" class="mt-1 text-sm text-red-400">{{ pbxForm.errors.api_key }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                        API Secret
                                        <span class="text-slate-500 font-normal ml-1">(ixtiyoriy)</span>
                                    </label>
                                    <input
                                        v-model="pbxForm.api_secret"
                                        type="password"
                                        placeholder="API secret kalit"
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                    <p class="mt-1 text-xs text-slate-500">
                                        Agar tizimda secret talab qilinsa
                                    </p>
                                    <p v-if="pbxForm.errors.api_secret" class="mt-1 text-sm text-red-400">{{ pbxForm.errors.api_secret }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                        Chiquvchi raqam
                                        <span class="text-slate-500 font-normal ml-1">(Caller ID)</span>
                                    </label>
                                    <input
                                        v-model="pbxForm.caller_id"
                                        type="text"
                                        required
                                        placeholder="+998901234567"
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                    <p class="mt-1 text-xs text-slate-500">
                                        Lidlarga qo'ng'iroq qilganda ko'rinadigan raqam
                                    </p>
                                    <p v-if="pbxForm.errors.caller_id" class="mt-1 text-sm text-red-400">{{ pbxForm.errors.caller_id }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                        Extension
                                        <span class="text-slate-500 font-normal ml-1">(ichki raqam - ixtiyoriy)</span>
                                    </label>
                                    <input
                                        v-model="pbxForm.extension"
                                        type="text"
                                        placeholder="101"
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                    <p class="mt-1 text-xs text-slate-500">
                                        PBX dagi ichki raqamingiz (agar kerak bo'lsa)
                                    </p>
                                    <p v-if="pbxForm.errors.extension" class="mt-1 text-sm text-red-400">{{ pbxForm.errors.extension }}</p>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="isConnecting || pbxForm.processing"
                                    class="w-full py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-600 hover:to-indigo-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isConnecting || pbxForm.processing" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Tekshirilmoqda...
                                    </span>
                                    <span v-else>Ulanish</span>
                                </button>
                            </form>
                        </div>

                        <!-- PBX Connected -->
                        <div v-else>
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center mr-4">
                                        <svg class="w-6 h-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-lg font-semibold text-white">PBX Ulangan</p>
                                        <p class="text-sm text-slate-400">{{ pbxAccount.name }}</p>
                                    </div>
                                </div>
                                <button
                                    @click="disconnectPbx"
                                    :disabled="isDisconnecting"
                                    class="px-4 py-2 bg-red-500/20 text-red-400 rounded-xl hover:bg-red-500/30 transition-colors disabled:opacity-50"
                                >
                                    <span v-if="isDisconnecting">Uzilmoqda...</span>
                                    <span v-else>Uzish</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-slate-700/30 rounded-xl p-4">
                                    <p class="text-sm text-slate-400 mb-1">Caller ID</p>
                                    <p class="text-lg font-semibold text-white">{{ pbxAccount.caller_id }}</p>
                                </div>
                                <div class="bg-slate-700/30 rounded-xl p-4">
                                    <p class="text-sm text-slate-400 mb-1">Extension</p>
                                    <p class="text-lg font-semibold text-white">{{ pbxAccount.extension || '-' }}</p>
                                </div>
                                <div class="bg-slate-700/30 rounded-xl p-4">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm text-slate-400 mb-1">Balans</p>
                                        <button @click="refreshBalance" :disabled="isRefreshingBalance" class="text-blue-400 hover:text-blue-300 disabled:opacity-50">
                                            <svg :class="{'animate-spin': isRefreshingBalance}" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-lg font-semibold text-blue-400">{{ pbxAccount.balance || 0 }} so'm</p>
                                </div>
                            </div>

                            <!-- PBX Statistics -->
                            <div v-if="stats" class="mt-6 pt-6 border-t border-slate-700/50">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-white">Qo'ng'iroqlar Statistikasi (30 kun)</h3>
                                    <Link :href="route('integrations.telephony.history')" class="text-blue-400 hover:text-blue-300 text-sm">
                                        Batafsil ko'rish
                                    </Link>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <Link
                                        :href="route('integrations.telephony.history')"
                                        class="bg-slate-700/30 rounded-xl p-4 text-center hover:bg-slate-700/50 transition-colors"
                                    >
                                        <p class="text-2xl font-bold text-white">{{ stats.total_calls }}</p>
                                        <p class="text-sm text-slate-400">Jami</p>
                                    </Link>
                                    <Link
                                        :href="route('integrations.telephony.history', { status: 'completed' })"
                                        class="bg-slate-700/30 rounded-xl p-4 text-center hover:bg-slate-700/50 transition-colors"
                                    >
                                        <p class="text-2xl font-bold text-green-400">{{ stats.answered_calls }}</p>
                                        <p class="text-sm text-slate-400">Javob berilgan</p>
                                    </Link>
                                    <Link
                                        :href="route('integrations.telephony.history', { status: 'missed' })"
                                        class="bg-slate-700/30 rounded-xl p-4 text-center hover:bg-slate-700/50 transition-colors"
                                    >
                                        <p class="text-2xl font-bold text-yellow-400">{{ stats.missed_calls }}</p>
                                        <p class="text-sm text-slate-400">Javobsiz</p>
                                    </Link>
                                    <div class="bg-slate-700/30 rounded-xl p-4 text-center">
                                        <p class="text-2xl font-bold text-blue-400">{{ stats.answer_rate }}%</p>
                                        <p class="text-sm text-slate-400">Javob %</p>
                                    </div>
                                </div>
                                <div class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <div class="bg-slate-700/30 rounded-xl p-4 text-center">
                                        <p class="text-xl font-bold text-white">{{ stats.outbound_calls }}</p>
                                        <p class="text-sm text-slate-400">Chiquvchi</p>
                                    </div>
                                    <div class="bg-slate-700/30 rounded-xl p-4 text-center">
                                        <p class="text-xl font-bold text-white">{{ stats.inbound_calls }}</p>
                                        <p class="text-sm text-slate-400">Kiruvchi</p>
                                    </div>
                                    <div class="bg-slate-700/30 rounded-xl p-4 text-center">
                                        <p class="text-xl font-bold text-white">{{ formatDuration(stats.total_duration) }}</p>
                                        <p class="text-sm text-slate-400">Jami vaqt</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SipUni Tab Content -->
                    <div v-if="activeTab === 'sipuni'" class="p-8">
                        <!-- SipUni Not Connected -->
                        <div v-if="!sipuniAccount">
                            <!-- Step-by-step Instructions -->
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-white mb-4">SipUni ulash bo'yicha yo'riqnoma</h3>
                                <div class="space-y-4">
                                    <!-- Step 1 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold text-sm">1</div>
                                        <div class="flex-1">
                                            <p class="text-slate-300 font-medium">SipUni kabinetiga kiring</p>
                                            <p class="text-slate-500 text-sm mt-1">
                                                <a href="https://sipuni.com/login" target="_blank" class="text-purple-400 hover:text-purple-300 underline">sipuni.com/login</a>
                                                sahifasiga kiring
                                            </p>
                                        </div>
                                    </div>
                                    <!-- Step 2 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold text-sm">2</div>
                                        <div class="flex-1">
                                            <p class="text-slate-300 font-medium">API sozlamalariga o'ting</p>
                                            <p class="text-slate-500 text-sm mt-1">
                                                <strong class="text-slate-400">Sozlamalar</strong> → <strong class="text-slate-400">API</strong> bo'limini oching
                                            </p>
                                        </div>
                                    </div>
                                    <!-- Step 3 -->
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold text-sm">3</div>
                                        <div class="flex-1">
                                            <p class="text-slate-300 font-medium">User ID va Secret ni nusxalang</p>
                                            <p class="text-slate-500 text-sm mt-1">
                                                API sahifasida <strong class="text-purple-400">user</strong> (raqam) va <strong class="text-purple-400">secret</strong> qiymatlarini ko'rasiz
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Visual Example -->
                            <div class="mb-8 p-4 bg-slate-700/30 rounded-xl border border-slate-600/50">
                                <p class="text-xs text-slate-400 mb-3 uppercase tracking-wide">SipUni API sahifasidan misol:</p>
                                <div class="bg-slate-800 rounded-lg p-4 font-mono text-sm">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-slate-500">user:</span>
                                        <span class="text-purple-400 bg-purple-500/10 px-2 py-0.5 rounded">123456</span>
                                        <span class="text-slate-600 text-xs">← bu User ID</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-slate-500">secret:</span>
                                        <span class="text-purple-400 bg-purple-500/10 px-2 py-0.5 rounded">abc123def456...</span>
                                        <span class="text-slate-600 text-xs">← bu Secret Key</span>
                                    </div>
                                </div>
                            </div>

                            <!-- SipUni Connect Form -->
                            <form @submit.prevent="connectSipuni" class="max-w-md mx-auto text-left space-y-5">
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                        User ID
                                        <span class="text-slate-500 font-normal ml-1">(user qiymati)</span>
                                    </label>
                                    <input
                                        v-model="sipuniForm.api_key"
                                        type="text"
                                        required
                                        placeholder="123456"
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-purple-500 focus:ring-1 focus:ring-purple-500"
                                    />
                                    <p class="mt-1 text-xs text-slate-500">
                                        Faqat raqamlar (email emas!)
                                    </p>
                                    <p v-if="sipuniForm.errors.api_key" class="mt-1 text-sm text-red-400">{{ sipuniForm.errors.api_key }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                        Secret Key
                                        <span class="text-slate-500 font-normal ml-1">(secret qiymati)</span>
                                    </label>
                                    <input
                                        v-model="sipuniForm.api_secret"
                                        type="password"
                                        required
                                        placeholder="abc123def456..."
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-purple-500 focus:ring-1 focus:ring-purple-500"
                                    />
                                    <p class="mt-1 text-xs text-slate-500">
                                        Sayt parolingiz emas! API secret kalit
                                    </p>
                                    <p v-if="sipuniForm.errors.api_secret" class="mt-1 text-sm text-red-400">{{ sipuniForm.errors.api_secret }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                        Chiquvchi raqam
                                        <span class="text-slate-500 font-normal ml-1">(Caller ID)</span>
                                    </label>
                                    <input
                                        v-model="sipuniForm.caller_id"
                                        type="text"
                                        required
                                        placeholder="+998901234567"
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-purple-500 focus:ring-1 focus:ring-purple-500"
                                    />
                                    <p class="mt-1 text-xs text-slate-500">
                                        SipUni da ro'yxatdan o'tgan telefon raqamingiz
                                    </p>
                                    <p v-if="sipuniForm.errors.caller_id" class="mt-1 text-sm text-red-400">{{ sipuniForm.errors.caller_id }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                        Ichki raqam
                                        <span class="text-slate-500 font-normal ml-1">(sipnumber - ixtiyoriy)</span>
                                    </label>
                                    <input
                                        v-model="sipuniForm.extension"
                                        type="text"
                                        placeholder="100"
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-purple-500 focus:ring-1 focus:ring-purple-500"
                                    />
                                    <p class="mt-1 text-xs text-slate-500">
                                        SipUni dagi ichki raqamingiz (qo'ng'iroq boshlash uchun)
                                    </p>
                                    <p v-if="sipuniForm.errors.extension" class="mt-1 text-sm text-red-400">{{ sipuniForm.errors.extension }}</p>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="isConnecting || sipuniForm.processing"
                                    class="w-full py-3 bg-gradient-to-r from-purple-500 to-violet-600 text-white font-semibold rounded-xl hover:from-purple-600 hover:to-violet-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isConnecting || sipuniForm.processing" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Tekshirilmoqda...
                                    </span>
                                    <span v-else>Ulanish</span>
                                </button>
                            </form>

                            <div class="mt-8 text-center">
                                <p class="text-sm text-slate-500">
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
                                        <p class="text-lg font-semibold text-white">SipUni Ulangan</p>
                                        <p class="text-sm text-slate-400">{{ sipuniAccount.name }}</p>
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
                                <div class="bg-slate-700/30 rounded-xl p-4">
                                    <p class="text-sm text-slate-400 mb-1">Caller ID</p>
                                    <p class="text-lg font-semibold text-white">{{ sipuniAccount.caller_id }}</p>
                                </div>
                                <div class="bg-slate-700/30 rounded-xl p-4">
                                    <p class="text-sm text-slate-400 mb-1">Ichki raqam</p>
                                    <p class="text-lg font-semibold text-white">{{ sipuniAccount.extension || '100' }}</p>
                                </div>
                                <div class="bg-slate-700/30 rounded-xl p-4">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm text-slate-400 mb-1">Balans</p>
                                        <button @click="refreshBalance" :disabled="isRefreshingBalance" class="text-purple-400 hover:text-purple-300 disabled:opacity-50">
                                            <svg :class="{'animate-spin': isRefreshingBalance}" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-lg font-semibold text-purple-400">{{ sipuniAccount.balance || 0 }} so'm</p>
                                </div>
                                <div class="bg-slate-700/30 rounded-xl p-4">
                                    <p class="text-sm text-slate-400 mb-1">Oxirgi yangilanish</p>
                                    <p class="text-lg font-semibold text-white">{{ formatDate(sipuniAccount.last_sync_at) }}</p>
                                </div>
                            </div>

                            <!-- SipUni Statistics -->
                            <div v-if="stats" class="mt-6 pt-6 border-t border-slate-700/50">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-white">Qo'ng'iroqlar Statistikasi (30 kun)</h3>
                                    <Link :href="route('integrations.telephony.history')" class="text-purple-400 hover:text-purple-300 text-sm">
                                        Batafsil ko'rish
                                    </Link>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <Link
                                        :href="route('integrations.telephony.history')"
                                        class="bg-slate-700/30 rounded-xl p-4 text-center hover:bg-slate-700/50 transition-colors"
                                    >
                                        <p class="text-2xl font-bold text-white">{{ stats.total_calls }}</p>
                                        <p class="text-sm text-slate-400">Jami</p>
                                    </Link>
                                    <Link
                                        :href="route('integrations.telephony.history', { status: 'completed' })"
                                        class="bg-slate-700/30 rounded-xl p-4 text-center hover:bg-slate-700/50 transition-colors"
                                    >
                                        <p class="text-2xl font-bold text-green-400">{{ stats.answered_calls }}</p>
                                        <p class="text-sm text-slate-400">Javob berilgan</p>
                                    </Link>
                                    <Link
                                        :href="route('integrations.telephony.history', { status: 'missed' })"
                                        class="bg-slate-700/30 rounded-xl p-4 text-center hover:bg-slate-700/50 transition-colors"
                                    >
                                        <p class="text-2xl font-bold text-yellow-400">{{ stats.missed_calls }}</p>
                                        <p class="text-sm text-slate-400">Javobsiz</p>
                                    </Link>
                                    <div class="bg-slate-700/30 rounded-xl p-4 text-center">
                                        <p class="text-2xl font-bold text-purple-400">{{ stats.answer_rate }}%</p>
                                        <p class="text-sm text-slate-400">Javob %</p>
                                    </div>
                                </div>
                                <div class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <div class="bg-slate-700/30 rounded-xl p-4 text-center">
                                        <p class="text-xl font-bold text-white">{{ stats.outbound_calls }}</p>
                                        <p class="text-sm text-slate-400">Chiquvchi</p>
                                    </div>
                                    <div class="bg-slate-700/30 rounded-xl p-4 text-center">
                                        <p class="text-xl font-bold text-white">{{ stats.inbound_calls }}</p>
                                        <p class="text-sm text-slate-400">Kiruvchi</p>
                                    </div>
                                    <div class="bg-slate-700/30 rounded-xl p-4 text-center">
                                        <p class="text-xl font-bold text-white">{{ formatDuration(stats.total_duration) }}</p>
                                        <p class="text-sm text-slate-400">Jami vaqt</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div v-if="hasActiveProvider" class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Tezkor harakatlar</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Link
                            :href="route('integrations.telephony.history')"
                            class="flex items-center gap-4 p-4 bg-slate-700/30 rounded-xl hover:bg-slate-700/50 transition-colors"
                        >
                            <div class="w-12 h-12 bg-slate-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-white">Qo'ng'iroqlar tarixi</p>
                                <p class="text-sm text-slate-400">Barcha qo'ng'iroqlarni ko'ring</p>
                            </div>
                        </Link>
                        <Link
                            :href="route('integrations.telephony.statistics')"
                            class="flex items-center gap-4 p-4 bg-slate-700/30 rounded-xl hover:bg-slate-700/50 transition-colors"
                        >
                            <div class="w-12 h-12 bg-slate-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-white">Statistika</p>
                                <p class="text-sm text-slate-400">Batafsil tahlil</p>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
