<script setup>
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    eskizAccount: Object,
    playmobileAccount: Object,
    templates: {
        type: Array,
        default: () => [],
    },
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
    if (urlProvider === 'playmobile') return 'playmobile';
    if (urlProvider === 'eskiz') return 'eskiz';
    // Default: show connected provider or eskiz
    return props.eskizAccount ? 'eskiz' : (props.playmobileAccount ? 'playmobile' : 'eskiz');
};

// Active provider tab
const activeTab = ref(getInitialTab());

// Check if single provider mode (no tabs)
const singleProviderMode = computed(() => urlProvider === 'eskiz' || urlProvider === 'playmobile');

const isConnecting = ref(false);
const isDisconnecting = ref(false);
const isRefreshingBalance = ref(false);
const showTemplateModal = ref(false);
const editingTemplate = ref(null);

// Check if any provider is connected
const hasActiveProvider = computed(() => props.eskizAccount || props.playmobileAccount);

// Eskiz Connect form
const eskizForm = useForm({
    email: '',
    password: '',
    sender_name: '',
});

// PlayMobile Connect form
const playmobileForm = useForm({
    login: '',
    password: '',
    originator: '3700',
});

// Template form
const templateForm = useForm({
    name: '',
    content: '',
    category: 'sales',
});

const connectEskiz = () => {
    isConnecting.value = true;
    eskizForm.post(route('business.settings.sms.connect'), {
        onFinish: () => {
            isConnecting.value = false;
            if (!eskizForm.hasErrors) {
                eskizForm.reset();
            }
        },
    });
};

const disconnectEskiz = () => {
    if (!confirm('Eskiz SMS integratsiyasini o\'chirmoqchimisiz?')) return;

    isDisconnecting.value = true;
    router.post(route('business.settings.sms.disconnect'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isDisconnecting.value = false;
        },
    });
};

const connectPlaymobile = () => {
    isConnecting.value = true;
    playmobileForm.post(route('business.settings.sms.playmobile.connect'), {
        onFinish: () => {
            isConnecting.value = false;
            if (!playmobileForm.hasErrors) {
                playmobileForm.reset();
            }
        },
    });
};

const disconnectPlaymobile = () => {
    if (!confirm('PlayMobile SMS integratsiyasini o\'chirmoqchimisiz?')) return;

    isDisconnecting.value = true;
    router.post(route('business.settings.sms.playmobile.disconnect'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isDisconnecting.value = false;
        },
    });
};

const refreshBalance = async () => {
    isRefreshingBalance.value = true;
    try {
        const response = await fetch(route('business.settings.sms.refresh-balance'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });
        const data = await response.json();
        if (data.balance !== undefined) {
            router.reload({ only: ['eskizAccount'] });
        }
    } catch (error) {
        console.error('Failed to refresh balance:', error);
    } finally {
        isRefreshingBalance.value = false;
    }
};

const openTemplateModal = (template = null) => {
    if (template) {
        editingTemplate.value = template;
        templateForm.name = template.name;
        templateForm.content = template.content;
        templateForm.category = template.category || 'sales';
    } else {
        editingTemplate.value = null;
        templateForm.reset();
    }
    showTemplateModal.value = true;
};

const saveTemplate = async () => {
    const url = editingTemplate.value
        ? route('business.sms.templates.update', editingTemplate.value.id)
        : route('business.sms.templates.store');

    const method = editingTemplate.value ? 'PUT' : 'POST';

    try {
        const response = await fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                name: templateForm.name,
                content: templateForm.content,
                category: templateForm.category,
            }),
        });

        if (response.ok) {
            showTemplateModal.value = false;
            templateForm.reset();
            router.reload({ only: ['templates'] });
        }
    } catch (error) {
        console.error('Failed to save template:', error);
    }
};

const deleteTemplate = async (template) => {
    if (!confirm(`"${template.name}" shablonini o'chirmoqchimisiz?`)) return;

    try {
        const response = await fetch(route('business.sms.templates.destroy', template.id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            router.reload({ only: ['templates'] });
        }
    } catch (error) {
        console.error('Failed to delete template:', error);
    }
};

const categories = [
    { value: 'sales', label: 'Sotuv' },
    { value: 'support', label: 'Qo\'llab-quvvatlash' },
    { value: 'notification', label: 'Bildirishnoma' },
    { value: 'marketing', label: 'Marketing' },
];

const getCategoryLabel = (value) => {
    return categories.find(c => c.value === value)?.label || value;
};

const formatDate = (date) => {
    if (!date) return '-';
    return date;
};
</script>

<template>
    <Head title="SMS Sozlamalari" />

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
                            activeTab === 'eskiz'
                                ? 'bg-gradient-to-br from-teal-500 to-cyan-600'
                                : 'bg-gradient-to-br from-orange-500 to-amber-600'
                        ]">
                            <span v-if="activeTab === 'eskiz'" class="text-white font-black text-4xl">E</span>
                            <span v-else class="text-white font-black text-2xl">PM</span>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">
                                {{ activeTab === 'eskiz' ? 'Eskiz.uz' : 'PlayMobile' }} SMS
                            </h1>
                            <p class="text-slate-400 mt-2">
                                {{ activeTab === 'eskiz' ? 'Eskiz.uz orqali SMS yuborish' : 'PlayMobile orqali SMS yuborish' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Provider Tabs (only show if not in single provider mode) -->
                <div v-if="!singleProviderMode" class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl overflow-hidden mb-8">
                    <!-- Tab Headers -->
                    <div class="flex border-b border-slate-700/50">
                        <button
                            @click="activeTab = 'eskiz'"
                            :class="[
                                'flex-1 px-6 py-4 text-center font-medium transition-colors',
                                activeTab === 'eskiz'
                                    ? 'text-teal-400 border-b-2 border-teal-400 bg-slate-700/30'
                                    : 'text-slate-400 hover:text-white'
                            ]"
                        >
                            <div class="flex items-center justify-center gap-3">
                                <div :class="[
                                    'w-7 h-7 rounded-lg flex items-center justify-center',
                                    activeTab === 'eskiz' ? 'bg-teal-500' : 'bg-slate-600'
                                ]">
                                    <span class="text-white font-bold text-sm">E</span>
                                </div>
                                <span>Eskiz.uz</span>
                                <span v-if="eskizAccount" class="w-2 h-2 bg-green-400 rounded-full"></span>
                            </div>
                        </button>
                        <button
                            @click="activeTab = 'playmobile'"
                            :class="[
                                'flex-1 px-6 py-4 text-center font-medium transition-colors',
                                activeTab === 'playmobile'
                                    ? 'text-orange-400 border-b-2 border-orange-400 bg-slate-700/30'
                                    : 'text-slate-400 hover:text-white'
                            ]"
                        >
                            <div class="flex items-center justify-center gap-3">
                                <div :class="[
                                    'w-7 h-7 rounded-lg flex items-center justify-center',
                                    activeTab === 'playmobile' ? 'bg-orange-500' : 'bg-slate-600'
                                ]">
                                    <span class="text-white font-bold text-[10px]">PM</span>
                                </div>
                                <span>PlayMobile</span>
                                <span v-if="playmobileAccount" class="w-2 h-2 bg-green-400 rounded-full"></span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Provider Content -->
                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl overflow-hidden mb-8">
                    <!-- Eskiz Tab Content -->
                    <div v-if="activeTab === 'eskiz'" class="p-8">
                        <!-- Eskiz Not Connected -->
                        <div v-if="!eskizAccount" class="text-center py-8">
                            <div class="w-20 h-20 bg-slate-700/50 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-white mb-2">Eskiz ulanmagan</h3>
                            <p class="text-slate-400 mb-8 max-w-md mx-auto">
                                Eskiz.uz - O'zbekistondagi eng mashhur SMS gateway xizmati.
                            </p>

                            <!-- Eskiz Connect Form -->
                            <form @submit.prevent="connectEskiz" class="max-w-md mx-auto text-left space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">Email</label>
                                    <input
                                        v-model="eskizForm.email"
                                        type="email"
                                        required
                                        placeholder="email@example.com"
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                                    />
                                    <p v-if="eskizForm.errors.email" class="mt-1 text-sm text-red-400">{{ eskizForm.errors.email }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                        API Kalit (Secret Key)
                                        <span class="text-slate-500 font-normal ml-1">- sayt paroli emas!</span>
                                    </label>
                                    <input
                                        v-model="eskizForm.password"
                                        type="password"
                                        required
                                        placeholder="API kalitingizni kiriting"
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                                    />
                                    <p class="mt-1 text-xs text-slate-500">
                                        <a href="https://my.eskiz.uz/settings/api" target="_blank" class="text-teal-400 hover:text-teal-300">Eskiz kabinetingiz</a>
                                        dan API kalitini oling
                                    </p>
                                    <p v-if="eskizForm.errors.password" class="mt-1 text-sm text-red-400">{{ eskizForm.errors.password }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                        Yuboruvchi nomi (Sender ID)
                                    </label>
                                    <input
                                        v-model="eskizForm.sender_name"
                                        type="text"
                                        required
                                        maxlength="11"
                                        placeholder="KOMPANIYA"
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 uppercase"
                                    />
                                    <p class="mt-1 text-xs text-slate-500">Maks 11 belgi, faqat lotin harflari va raqamlar</p>
                                    <p v-if="eskizForm.errors.sender_name" class="mt-1 text-sm text-red-400">{{ eskizForm.errors.sender_name }}</p>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="isConnecting || eskizForm.processing"
                                    class="w-full py-3 bg-gradient-to-r from-teal-500 to-cyan-600 text-white font-semibold rounded-xl hover:from-teal-600 hover:to-cyan-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isConnecting || eskizForm.processing" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Ulanmoqda...
                                    </span>
                                    <span v-else>Eskiz'ga Ulanish</span>
                                </button>
                            </form>

                            <p class="mt-6 text-sm text-slate-500">
                                <a href="https://eskiz.uz" target="_blank" class="text-teal-400 hover:text-teal-300">eskiz.uz</a>
                                da ro'yxatdan o'ting
                            </p>
                        </div>

                        <!-- Eskiz Connected -->
                        <div v-else>
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center mr-4">
                                        <svg class="w-6 h-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-lg font-semibold text-white">Eskiz Ulangan</p>
                                        <p class="text-sm text-slate-400">{{ eskizAccount.email }}</p>
                                    </div>
                                </div>
                                <button
                                    @click="disconnectEskiz"
                                    :disabled="isDisconnecting"
                                    class="px-4 py-2 bg-red-500/20 text-red-400 rounded-xl hover:bg-red-500/30 transition-colors disabled:opacity-50"
                                >
                                    <span v-if="isDisconnecting">Uzilmoqda...</span>
                                    <span v-else>Uzish</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-slate-700/30 rounded-xl p-4">
                                    <p class="text-sm text-slate-400 mb-1">Yuboruvchi</p>
                                    <p class="text-lg font-semibold text-white">{{ eskizAccount.sender_name }}</p>
                                </div>
                                <div class="bg-slate-700/30 rounded-xl p-4">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm text-slate-400 mb-1">Balans</p>
                                        <button @click="refreshBalance" :disabled="isRefreshingBalance" class="text-teal-400 hover:text-teal-300 disabled:opacity-50">
                                            <svg :class="{'animate-spin': isRefreshingBalance}" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-lg font-semibold text-teal-400">{{ eskizAccount.balance }} SMS</p>
                                </div>
                                <div class="bg-slate-700/30 rounded-xl p-4">
                                    <p class="text-sm text-slate-400 mb-1">Oxirgi yangilanish</p>
                                    <p class="text-lg font-semibold text-white">{{ formatDate(eskizAccount.last_sync_at) }}</p>
                                </div>
                            </div>

                            <div v-if="!eskizAccount.token_valid" class="mt-4 p-4 bg-yellow-500/20 border border-yellow-500/30 rounded-xl">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-yellow-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <p class="text-yellow-400">Token muddati tugagan. Iltimos, qayta ulaning.</p>
                                </div>
                            </div>

                            <!-- Eskiz Statistics -->
                            <div v-if="stats" class="mt-6 pt-6 border-t border-slate-700/50">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-white">SMS Statistikasi (30 kun)</h3>
                                    <Link :href="route('business.sms.history')" class="text-teal-400 hover:text-teal-300 text-sm">
                                        Batafsil ko'rish
                                    </Link>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <Link
                                        :href="route('business.sms.history')"
                                        class="bg-slate-700/30 rounded-xl p-4 text-center hover:bg-slate-700/50 transition-colors"
                                    >
                                        <p class="text-2xl font-bold text-white">{{ stats.total_sent }}</p>
                                        <p class="text-sm text-slate-400">Yuborilgan</p>
                                    </Link>
                                    <Link
                                        :href="route('business.sms.history', { status: 'delivered' })"
                                        class="bg-slate-700/30 rounded-xl p-4 text-center hover:bg-slate-700/50 transition-colors"
                                    >
                                        <p class="text-2xl font-bold text-green-400">{{ stats.delivered }}</p>
                                        <p class="text-sm text-slate-400">Yetkazilgan</p>
                                    </Link>
                                    <Link
                                        :href="route('business.sms.history', { status: 'failed' })"
                                        class="bg-slate-700/30 rounded-xl p-4 text-center hover:bg-slate-700/50 transition-colors"
                                    >
                                        <p class="text-2xl font-bold text-red-400">{{ stats.failed }}</p>
                                        <p class="text-sm text-slate-400">Xatolik</p>
                                    </Link>
                                    <div class="bg-slate-700/30 rounded-xl p-4 text-center">
                                        <p class="text-2xl font-bold text-teal-400">{{ stats.delivery_rate }}%</p>
                                        <p class="text-sm text-slate-400">Yetkazish %</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PlayMobile Tab Content -->
                    <div v-if="activeTab === 'playmobile'" class="p-8">
                        <!-- PlayMobile Not Connected -->
                        <div v-if="!playmobileAccount" class="text-center py-8">
                            <div class="w-20 h-20 bg-slate-700/50 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-white mb-2">PlayMobile ulanmagan</h3>
                            <p class="text-slate-400 mb-8 max-w-md mx-auto">
                                PlayMobile (smsxabar.uz) - O'zbekiston bo'ylab SMS jo'natish xizmati.
                            </p>

                            <!-- PlayMobile Connect Form -->
                            <form @submit.prevent="connectPlaymobile" class="max-w-md mx-auto text-left space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">Login</label>
                                    <input
                                        v-model="playmobileForm.login"
                                        type="text"
                                        required
                                        placeholder="Login"
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                                    />
                                    <p v-if="playmobileForm.errors.login" class="mt-1 text-sm text-red-400">{{ playmobileForm.errors.login }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">Parol</label>
                                    <input
                                        v-model="playmobileForm.password"
                                        type="password"
                                        required
                                        placeholder="Parol"
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                                    />
                                    <p v-if="playmobileForm.errors.password" class="mt-1 text-sm text-red-400">{{ playmobileForm.errors.password }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                        Originator (Yuboruvchi raqami)
                                    </label>
                                    <input
                                        v-model="playmobileForm.originator"
                                        type="text"
                                        required
                                        maxlength="20"
                                        placeholder="3700"
                                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                                    />
                                    <p class="mt-1 text-xs text-slate-500">PlayMobile dan berilgan originator</p>
                                    <p v-if="playmobileForm.errors.originator" class="mt-1 text-sm text-red-400">{{ playmobileForm.errors.originator }}</p>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="isConnecting || playmobileForm.processing"
                                    class="w-full py-3 bg-gradient-to-r from-orange-500 to-amber-600 text-white font-semibold rounded-xl hover:from-orange-600 hover:to-amber-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isConnecting || playmobileForm.processing" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Ulanmoqda...
                                    </span>
                                    <span v-else>PlayMobile'ga Ulanish</span>
                                </button>
                            </form>

                            <p class="mt-6 text-sm text-slate-500">
                                <a href="https://playmobile.uz" target="_blank" class="text-orange-400 hover:text-orange-300">playmobile.uz</a>
                                da ro'yxatdan o'ting
                            </p>
                        </div>

                        <!-- PlayMobile Connected -->
                        <div v-else>
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center mr-4">
                                        <svg class="w-6 h-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-lg font-semibold text-white">PlayMobile Ulangan</p>
                                        <p class="text-sm text-slate-400">{{ playmobileAccount.login }}</p>
                                    </div>
                                </div>
                                <button
                                    @click="disconnectPlaymobile"
                                    :disabled="isDisconnecting"
                                    class="px-4 py-2 bg-red-500/20 text-red-400 rounded-xl hover:bg-red-500/30 transition-colors disabled:opacity-50"
                                >
                                    <span v-if="isDisconnecting">Uzilmoqda...</span>
                                    <span v-else>Uzish</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-slate-700/30 rounded-xl p-4">
                                    <p class="text-sm text-slate-400 mb-1">Originator</p>
                                    <p class="text-lg font-semibold text-white">{{ playmobileAccount.originator }}</p>
                                </div>
                                <div class="bg-slate-700/30 rounded-xl p-4">
                                    <p class="text-sm text-slate-400 mb-1">Oxirgi yangilanish</p>
                                    <p class="text-lg font-semibold text-white">{{ formatDate(playmobileAccount.last_sync_at) }}</p>
                                </div>
                            </div>

                            <!-- PlayMobile Statistics -->
                            <div v-if="stats" class="mt-6 pt-6 border-t border-slate-700/50">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-white">SMS Statistikasi (30 kun)</h3>
                                    <Link :href="route('business.sms.history')" class="text-orange-400 hover:text-orange-300 text-sm">
                                        Batafsil ko'rish
                                    </Link>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <Link
                                        :href="route('business.sms.history')"
                                        class="bg-slate-700/30 rounded-xl p-4 text-center hover:bg-slate-700/50 transition-colors"
                                    >
                                        <p class="text-2xl font-bold text-white">{{ stats.total_sent }}</p>
                                        <p class="text-sm text-slate-400">Yuborilgan</p>
                                    </Link>
                                    <Link
                                        :href="route('business.sms.history', { status: 'delivered' })"
                                        class="bg-slate-700/30 rounded-xl p-4 text-center hover:bg-slate-700/50 transition-colors"
                                    >
                                        <p class="text-2xl font-bold text-green-400">{{ stats.delivered }}</p>
                                        <p class="text-sm text-slate-400">Yetkazilgan</p>
                                    </Link>
                                    <Link
                                        :href="route('business.sms.history', { status: 'failed' })"
                                        class="bg-slate-700/30 rounded-xl p-4 text-center hover:bg-slate-700/50 transition-colors"
                                    >
                                        <p class="text-2xl font-bold text-red-400">{{ stats.failed }}</p>
                                        <p class="text-sm text-slate-400">Xatolik</p>
                                    </Link>
                                    <div class="bg-slate-700/30 rounded-xl p-4 text-center">
                                        <p class="text-2xl font-bold text-orange-400">{{ stats.delivery_rate }}%</p>
                                        <p class="text-sm text-slate-400">Yetkazish %</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Templates Section -->
                <div v-if="hasActiveProvider" class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl overflow-hidden">
                    <div class="px-8 py-6 border-b border-slate-700/50 flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-white">SMS Shablonlari</h2>
                        <button
                            @click="openTemplateModal()"
                            class="px-4 py-2 bg-teal-500 text-white rounded-xl hover:bg-teal-600 transition-colors text-sm font-medium"
                        >
                            + Yangi shablon
                        </button>
                    </div>

                    <div class="p-8">
                        <div v-if="templates.length === 0" class="text-center py-8">
                            <svg class="w-12 h-12 text-slate-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-slate-400">Hali shablonlar yaratilmagan</p>
                        </div>

                        <div v-else class="space-y-4">
                            <div v-for="template in templates" :key="template.id" class="bg-slate-700/30 rounded-xl p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <h4 class="font-semibold text-white">{{ template.name }}</h4>
                                            <span class="px-2 py-0.5 bg-slate-600 rounded text-xs text-slate-300">
                                                {{ getCategoryLabel(template.category) }}
                                            </span>
                                            <span class="text-xs text-slate-500">{{ template.usage_count }} marta</span>
                                        </div>
                                        <p class="text-sm text-slate-400">{{ template.content }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 ml-4">
                                        <button @click="openTemplateModal(template)" class="p-2 text-slate-400 hover:text-white transition-colors">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <button @click="deleteTemplate(template)" class="p-2 text-slate-400 hover:text-red-400 transition-colors">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Placeholder variables info -->
                <div v-if="hasActiveProvider" class="mt-8 bg-slate-800/30 rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-slate-300 mb-3">Shablon o'zgaruvchilari:</h3>
                    <div class="flex flex-wrap gap-2">
                        <code class="px-2 py-1 bg-slate-700 rounded text-teal-400 text-sm">{name}</code>
                        <span class="text-slate-500 text-sm">- Lead ismi</span>
                        <code class="px-2 py-1 bg-slate-700 rounded text-teal-400 text-sm">{phone}</code>
                        <span class="text-slate-500 text-sm">- Telefon</span>
                        <code class="px-2 py-1 bg-slate-700 rounded text-teal-400 text-sm">{company}</code>
                        <span class="text-slate-500 text-sm">- Kompaniya</span>
                        <code class="px-2 py-1 bg-slate-700 rounded text-teal-400 text-sm">{email}</code>
                        <span class="text-slate-500 text-sm">- Email</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Template Modal -->
        <div v-if="showTemplateModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-slate-900/75" @click="showTemplateModal = false"></div>

                <div class="relative inline-block px-6 py-6 overflow-hidden text-left align-bottom transition-all transform bg-slate-800 rounded-2xl shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-700">
                    <h3 class="text-xl font-semibold text-white mb-6">
                        {{ editingTemplate ? 'Shablonni tahrirlash' : 'Yangi shablon' }}
                    </h3>

                    <form @submit.prevent="saveTemplate" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Nomi</label>
                            <input
                                v-model="templateForm.name"
                                type="text"
                                required
                                placeholder="Shablon nomi"
                                class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Kategoriya</label>
                            <select
                                v-model="templateForm.category"
                                class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                            >
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Matn</label>
                            <textarea
                                v-model="templateForm.content"
                                required
                                rows="4"
                                maxlength="1600"
                                placeholder="SMS matni..."
                                class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 resize-none"
                            ></textarea>
                            <p class="mt-1 text-xs text-slate-500">{{ templateForm.content.length }} / 1600</p>
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" @click="showTemplateModal = false" class="px-4 py-2 bg-slate-700 text-slate-300 rounded-xl hover:bg-slate-600 transition-colors">
                                Bekor qilish
                            </button>
                            <button type="submit" class="px-4 py-2 bg-teal-500 text-white rounded-xl hover:bg-teal-600 transition-colors">
                                {{ editingTemplate ? 'Saqlash' : 'Yaratish' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
