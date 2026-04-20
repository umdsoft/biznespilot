<script setup>
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { useI18n } from '@/i18n';
import { useConfirm } from '@/composables/useConfirm';

const { t } = useI18n();
const { confirm } = useConfirm();

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

const disconnectEskiz = async () => {
    if (!await confirm({ title: 'Eskiz SMS uzish', message: 'Eskiz SMS integratsiyasini o\'chirmoqchimisiz?', type: 'danger', confirmText: 'O\'chirish' })) return;

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

const disconnectPlaymobile = async () => {
    if (!await confirm({ title: 'PlayMobile SMS uzish', message: 'PlayMobile SMS integratsiyasini o\'chirmoqchimisiz?', type: 'danger', confirmText: 'O\'chirish' })) return;

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
    if (!await confirm({ title: 'Shablonni o\'chirish', message: `"${template.name}" shablonini o'chirmoqchimisiz?`, type: 'danger', confirmText: 'O\'chirish' })) return;

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
        <div class="py-6 lg:py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6">
                <!-- Back Button -->
                <Link
                    :href="route('business.settings.index')"
                    class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-6"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Sozlamalarga qaytish
                </Link>

                <!-- Flash Messages -->
                <div v-if="flash.success" class="mb-6 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg flex items-center">
                    <svg class="w-4 h-4 text-green-600 dark:text-green-400 mr-2.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <p class="text-sm text-green-700 dark:text-green-400">{{ flash.success }}</p>
                </div>

                <div v-if="flash.error" class="mb-6 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg flex items-center">
                    <svg class="w-4 h-4 text-red-600 dark:text-red-400 mr-2.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm text-red-700 dark:text-red-400">{{ flash.error }}</p>
                </div>

                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ activeTab === 'eskiz' ? 'Eskiz.uz' : 'PlayMobile' }} SMS
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ activeTab === 'eskiz' ? 'Eskiz.uz orqali SMS yuborish' : 'PlayMobile orqali SMS yuborish' }}
                    </p>
                </div>

                <!-- Provider Tabs (only show if not in single provider mode) -->
                <div v-if="!singleProviderMode" class="flex gap-6 border-b border-gray-200 dark:border-gray-700 mb-6">
                    <button
                        @click="activeTab = 'eskiz'"
                        :class="[
                            'pb-3 text-sm font-medium transition-colors',
                            activeTab === 'eskiz'
                                ? 'border-b-2 border-gray-900 dark:border-white text-gray-900 dark:text-white'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                        ]"
                    >
                        Eskiz.uz
                        <span v-if="eskizAccount" class="ml-1.5 w-1.5 h-1.5 bg-green-500 rounded-full inline-block"></span>
                    </button>
                    <button
                        @click="activeTab = 'playmobile'"
                        :class="[
                            'pb-3 text-sm font-medium transition-colors',
                            activeTab === 'playmobile'
                                ? 'border-b-2 border-gray-900 dark:border-white text-gray-900 dark:text-white'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                        ]"
                    >
                        PlayMobile
                        <span v-if="playmobileAccount" class="ml-1.5 w-1.5 h-1.5 bg-green-500 rounded-full inline-block"></span>
                    </button>
                </div>

                <!-- Provider Content -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-6">
                    <!-- Eskiz Tab Content -->
                    <div v-if="activeTab === 'eskiz'" class="p-6">
                        <!-- Eskiz Not Connected -->
                        <div v-if="!eskizAccount" class="text-center py-6">
                            <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center mx-auto mb-4">
                                <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Eskiz ulanmagan</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
                                Eskiz.uz - O'zbekistondagi eng mashhur SMS gateway xizmati.
                            </p>

                            <!-- Eskiz Connect Form -->
                            <form @submit.prevent="connectEskiz" class="max-w-md mx-auto text-left space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                                    <input
                                        v-model="eskizForm.email"
                                        type="email"
                                        required
                                        placeholder="email@example.com"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p v-if="eskizForm.errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ eskizForm.errors.email }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        API Kalit (Secret Key)
                                        <span class="text-gray-400 dark:text-gray-500 font-normal ml-1">- sayt paroli emas!</span>
                                    </label>
                                    <input
                                        v-model="eskizForm.password"
                                        type="password"
                                        required
                                        placeholder="API kalitingizni kiriting"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        <a href="https://my.eskiz.uz/settings/api" target="_blank" class="text-gray-900 dark:text-white underline hover:no-underline">Eskiz kabinetingiz</a>
                                        dan API kalitini oling
                                    </p>
                                    <p v-if="eskizForm.errors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ eskizForm.errors.password }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        Yuboruvchi nomi (Sender ID)
                                    </label>
                                    <input
                                        v-model="eskizForm.sender_name"
                                        type="text"
                                        required
                                        maxlength="11"
                                        placeholder="KOMPANIYA"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white uppercase"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Maks 11 belgi, faqat lotin harflari va raqamlar</p>
                                    <p v-if="eskizForm.errors.sender_name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ eskizForm.errors.sender_name }}</p>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="isConnecting || eskizForm.processing"
                                    class="w-full py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isConnecting || eskizForm.processing" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Ulanmoqda...
                                    </span>
                                    <span v-else>Eskiz'ga Ulanish</span>
                                </button>
                            </form>

                            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                                <a href="https://eskiz.uz" target="_blank" class="text-gray-900 dark:text-white underline hover:no-underline">eskiz.uz</a>
                                da ro'yxatdan o'ting
                            </p>
                        </div>

                        <!-- Eskiz Connected -->
                        <div v-else>
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-3">
                                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Eskiz Ulangan</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ eskizAccount.email }}</p>
                                    </div>
                                </div>
                                <button
                                    @click="disconnectEskiz"
                                    :disabled="isDisconnecting"
                                    class="text-sm text-red-600 dark:text-red-400 hover:underline disabled:opacity-50"
                                >
                                    <span v-if="isDisconnecting">Uzilmoqda...</span>
                                    <span v-else>Uzish</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Yuboruvchi</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ eskizAccount.sender_name }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Balans</p>
                                        <button @click="refreshBalance" :disabled="isRefreshingBalance" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white disabled:opacity-50">
                                            <svg :class="{'animate-spin': isRefreshingBalance}" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ eskizAccount.balance }} SMS</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Oxirgi yangilanish</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatDate(eskizAccount.last_sync_at) }}</p>
                                </div>
                            </div>

                            <div v-if="!eskizAccount.token_valid" class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400 mr-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <p class="text-sm text-yellow-700 dark:text-yellow-400">Token muddati tugagan. Iltimos, qayta ulaning.</p>
                                </div>
                            </div>

                            <!-- Eskiz Statistics -->
                            <div v-if="stats" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">SMS Statistikasi (30 kun)</h3>
                                    <Link :href="route('business.sms.history')" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                                        Batafsil ko'rish
                                    </Link>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <Link
                                        :href="route('business.sms.history')"
                                        class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-center hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors"
                                    >
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ stats.total_sent }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Yuborilgan</p>
                                    </Link>
                                    <Link
                                        :href="route('business.sms.history', { status: 'delivered' })"
                                        class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-center hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors"
                                    >
                                        <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ stats.delivered }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Yetkazilgan</p>
                                    </Link>
                                    <Link
                                        :href="route('business.sms.history', { status: 'failed' })"
                                        class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-center hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors"
                                    >
                                        <p class="text-lg font-bold text-red-600 dark:text-red-400">{{ stats.failed }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Xatolik</p>
                                    </Link>
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-center">
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ stats.delivery_rate }}%</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Yetkazish %</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PlayMobile Tab Content -->
                    <div v-if="activeTab === 'playmobile'" class="p-6">
                        <!-- PlayMobile Not Connected -->
                        <div v-if="!playmobileAccount" class="text-center py-6">
                            <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center mx-auto mb-4">
                                <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">PlayMobile ulanmagan</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
                                PlayMobile (smsxabar.uz) - O'zbekiston bo'ylab SMS jo'natish xizmati.
                            </p>

                            <!-- PlayMobile Connect Form -->
                            <form @submit.prevent="connectPlaymobile" class="max-w-md mx-auto text-left space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Login</label>
                                    <input
                                        v-model="playmobileForm.login"
                                        type="text"
                                        required
                                        placeholder="Login"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p v-if="playmobileForm.errors.login" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ playmobileForm.errors.login }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Parol</label>
                                    <input
                                        v-model="playmobileForm.password"
                                        type="password"
                                        required
                                        placeholder="Parol"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p v-if="playmobileForm.errors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ playmobileForm.errors.password }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        Originator (Yuboruvchi raqami)
                                    </label>
                                    <input
                                        v-model="playmobileForm.originator"
                                        type="text"
                                        required
                                        maxlength="20"
                                        placeholder="3700"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PlayMobile dan berilgan originator</p>
                                    <p v-if="playmobileForm.errors.originator" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ playmobileForm.errors.originator }}</p>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="isConnecting || playmobileForm.processing"
                                    class="w-full py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isConnecting || playmobileForm.processing" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Ulanmoqda...
                                    </span>
                                    <span v-else>PlayMobile'ga Ulanish</span>
                                </button>
                            </form>

                            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                                <a href="https://playmobile.uz" target="_blank" class="text-gray-900 dark:text-white underline hover:no-underline">playmobile.uz</a>
                                da ro'yxatdan o'ting
                            </p>
                        </div>

                        <!-- PlayMobile Connected -->
                        <div v-else>
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-3">
                                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">PlayMobile Ulangan</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ playmobileAccount.login }}</p>
                                    </div>
                                </div>
                                <button
                                    @click="disconnectPlaymobile"
                                    :disabled="isDisconnecting"
                                    class="text-sm text-red-600 dark:text-red-400 hover:underline disabled:opacity-50"
                                >
                                    <span v-if="isDisconnecting">Uzilmoqda...</span>
                                    <span v-else>Uzish</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Originator</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ playmobileAccount.originator }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Oxirgi yangilanish</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatDate(playmobileAccount.last_sync_at) }}</p>
                                </div>
                            </div>

                            <!-- PlayMobile Statistics -->
                            <div v-if="stats" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">SMS Statistikasi (30 kun)</h3>
                                    <Link :href="route('business.sms.history')" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                                        Batafsil ko'rish
                                    </Link>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <Link
                                        :href="route('business.sms.history')"
                                        class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-center hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors"
                                    >
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ stats.total_sent }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Yuborilgan</p>
                                    </Link>
                                    <Link
                                        :href="route('business.sms.history', { status: 'delivered' })"
                                        class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-center hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors"
                                    >
                                        <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ stats.delivered }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Yetkazilgan</p>
                                    </Link>
                                    <Link
                                        :href="route('business.sms.history', { status: 'failed' })"
                                        class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-center hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors"
                                    >
                                        <p class="text-lg font-bold text-red-600 dark:text-red-400">{{ stats.failed }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Xatolik</p>
                                    </Link>
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-center">
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ stats.delivery_rate }}%</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Yetkazish %</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Templates Section -->
                <div v-if="hasActiveProvider" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">SMS Shablonlari</h2>
                        <button
                            @click="openTemplateModal()"
                            class="px-3 py-1.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors text-sm font-medium"
                        >
                            + Yangi shablon
                        </button>
                    </div>

                    <div class="p-6">
                        <div v-if="templates.length === 0" class="text-center py-6">
                            <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Hali shablonlar yaratilmagan</p>
                        </div>

                        <div v-else class="space-y-3">
                            <div v-for="template in templates" :key="template.id" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1.5">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ template.name }}</h4>
                                            <span class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs text-gray-600 dark:text-gray-300">
                                                {{ getCategoryLabel(template.category) }}
                                            </span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ template.usage_count }} marta</span>
                                        </div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ template.content }}</p>
                                    </div>
                                    <div class="flex items-center gap-1 ml-4">
                                        <button @click="openTemplateModal(template)" class="p-1.5 text-gray-400 dark:text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <button @click="deleteTemplate(template)" class="p-1.5 text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 transition-colors">
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
                <div v-if="hasActiveProvider" class="mt-6 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <h3 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Shablon o'zgaruvchilari:</h3>
                    <div class="flex flex-wrap gap-2">
                        <code class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs text-gray-700 dark:text-gray-300">{name}</code>
                        <span class="text-gray-500 dark:text-gray-400 text-xs">- Lead ismi</span>
                        <code class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs text-gray-700 dark:text-gray-300">{phone}</code>
                        <span class="text-gray-500 dark:text-gray-400 text-xs">- Telefon</span>
                        <code class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs text-gray-700 dark:text-gray-300">{company}</code>
                        <span class="text-gray-500 dark:text-gray-400 text-xs">- Kompaniya</span>
                        <code class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs text-gray-700 dark:text-gray-300">{email}</code>
                        <span class="text-gray-500 dark:text-gray-400 text-xs">- Email</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Template Modal -->
        <div v-if="showTemplateModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/50 dark:bg-black/60" @click="showTemplateModal = false"></div>

                <div class="relative inline-block px-6 py-6 overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-gray-800 rounded-lg shadow-lg sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-5">
                        {{ editingTemplate ? 'Shablonni tahrirlash' : 'Yangi shablon' }}
                    </h3>

                    <form @submit.prevent="saveTemplate" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nomi</label>
                            <input
                                v-model="templateForm.name"
                                type="text"
                                required
                                placeholder="Shablon nomi"
                                class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kategoriya</label>
                            <select
                                v-model="templateForm.category"
                                class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white"
                            >
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Matn</label>
                            <textarea
                                v-model="templateForm.content"
                                required
                                rows="4"
                                maxlength="1600"
                                placeholder="SMS matni..."
                                class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-gray-900 dark:focus:border-white focus:ring-1 focus:ring-gray-900 dark:focus:ring-white resize-none"
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ templateForm.content.length }} / 1600</p>
                        </div>

                        <div class="flex justify-end gap-3 pt-3">
                            <button type="button" @click="showTemplateModal = false" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm">
                                Bekor qilish
                            </button>
                            <button type="submit" class="px-3 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors text-sm font-medium">
                                {{ editingTemplate ? 'Saqlash' : 'Yaratish' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
