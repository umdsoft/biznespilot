<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    currentBusiness: Object,
    integration: Object,
    oauthUrl: String,
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

const isConnecting = ref(false);
const isDisconnecting = ref(false);

const connect = () => {
    isConnecting.value = true;
    window.location.href = props.oauthUrl;
};

const disconnect = () => {
    if (confirm('Google Ads integratsiyasini o\'chirmoqchimisiz?')) {
        isDisconnecting.value = true;
        router.post(route('business.settings.google-ads.disconnect'), {}, {
            onFinish: () => {
                isDisconnecting.value = false;
            },
        });
    }
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('uz-UZ', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Google Ads Integration" />

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
                        <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center mr-6 shadow-lg">
                            <svg class="w-14 h-14" viewBox="0 0 24 24" fill="none">
                                <path d="M12.545 10.239v3.821h5.445c-.712 2.315-2.647 3.972-5.445 3.972a6.033 6.033 0 110-12.064c1.498 0 2.866.549 3.921 1.453l2.814-2.814A9.969 9.969 0 0012.545 2C7.021 2 2.543 6.477 2.543 12s4.478 10 10.002 10c8.396 0 10.249-7.85 9.426-11.748l-9.426-.013z" fill="#4285F4"/>
                                <path d="M12.545 10.239v3.821h5.445c-.712 2.315-2.647 3.972-5.445 3.972a6.033 6.033 0 01-5.195-2.987l-3.086 2.38A9.969 9.969 0 0012.545 22c5.524 0 10.002-4.477 10.002-10 0-.67-.069-1.325-.199-1.961l-9.803.2z" fill="#34A853"/>
                                <path d="M7.35 14.045a5.97 5.97 0 01-.307-1.888c0-.67.111-1.313.307-1.917L4.263 7.86A9.969 9.969 0 002.543 12c0 1.613.39 3.134 1.082 4.476l3.725-2.431z" fill="#FBBC05"/>
                                <path d="M12.545 5.969c1.665 0 3.157.573 4.334 1.696l2.814-2.814A9.969 9.969 0 0012.545 2a9.969 9.969 0 00-8.282 4.36l3.086 2.38a5.97 5.97 0 015.196-2.771z" fill="#EA4335"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">Google Ads</h1>
                            <p class="text-slate-400 mt-2">Google reklama kampaniyalarini ulang va boshqaring</p>
                        </div>
                    </div>
                </div>

                <!-- Connection Status -->
                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl overflow-hidden mb-8">
                    <div class="px-8 py-6 border-b border-slate-700/50">
                        <h2 class="text-xl font-semibold text-white">Ulanish holati</h2>
                    </div>

                    <div class="p-8">
                        <div v-if="integration && integration.is_connected" class="space-y-6">
                            <!-- Connected State -->
                            <div class="flex items-center justify-between p-6 bg-green-500/10 border border-green-500/30 rounded-xl">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center mr-4">
                                        <svg class="w-6 h-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-green-400 font-semibold text-lg">Ulangan</p>
                                        <p class="text-slate-400 text-sm">{{ integration.account_name || 'Google Ads hisobi' }}</p>
                                    </div>
                                </div>
                                <button
                                    @click="disconnect"
                                    :disabled="isDisconnecting"
                                    class="px-6 py-2.5 bg-red-500/20 border border-red-500/30 text-red-400 rounded-xl hover:bg-red-500/30 transition-colors disabled:opacity-50"
                                >
                                    <span v-if="isDisconnecting">O'chirilmoqda...</span>
                                    <span v-else>Uzish</span>
                                </button>
                            </div>

                            <!-- Account Details -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="p-4 bg-slate-700/30 rounded-xl">
                                    <p class="text-slate-400 text-sm">Hisob ID</p>
                                    <p class="text-white font-medium mt-1">{{ integration.account_id || '-' }}</p>
                                </div>
                                <div class="p-4 bg-slate-700/30 rounded-xl">
                                    <p class="text-slate-400 text-sm">Ulangan sana</p>
                                    <p class="text-white font-medium mt-1">{{ formatDate(integration.connected_at) }}</p>
                                </div>
                                <div class="p-4 bg-slate-700/30 rounded-xl">
                                    <p class="text-slate-400 text-sm">Oxirgi sinxronizatsiya</p>
                                    <p class="text-white font-medium mt-1">{{ formatDate(integration.last_synced_at) }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-12">
                            <!-- Not Connected State -->
                            <div class="w-20 h-20 bg-slate-700/50 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-white mb-2">Google Ads ulanmagan</h3>
                            <p class="text-slate-400 mb-8 max-w-md mx-auto">
                                Google Ads hisobingizni ulang va reklama kampaniyalaringizni to'g'ridan-to'g'ri BiznesPilot orqali boshqaring
                            </p>
                            <button
                                @click="connect"
                                :disabled="isConnecting"
                                class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white font-semibold rounded-xl hover:from-blue-600 hover:to-blue-700 transition-all transform hover:-translate-y-0.5 shadow-lg disabled:opacity-50"
                            >
                                <svg class="w-6 h-6 mr-3" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12.545 10.239v3.821h5.445c-.712 2.315-2.647 3.972-5.445 3.972a6.033 6.033 0 110-12.064c1.498 0 2.866.549 3.921 1.453l2.814-2.814A9.969 9.969 0 0012.545 2C7.021 2 2.543 6.477 2.543 12s4.478 10 10.002 10c8.396 0 10.249-7.85 9.426-11.748l-9.426-.013z"/>
                                </svg>
                                <span v-if="isConnecting">Ulanmoqda...</span>
                                <span v-else>Google Ads bilan ulash</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl overflow-hidden">
                    <div class="px-8 py-6 border-b border-slate-700/50">
                        <h2 class="text-xl font-semibold text-white">Imkoniyatlar</h2>
                    </div>

                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex items-start p-4 bg-slate-700/20 rounded-xl">
                                <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                    <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold">Kampaniya statistikasi</h3>
                                    <p class="text-slate-400 text-sm mt-1">Clicks, impressions, CTR, conversions</p>
                                </div>
                            </div>

                            <div class="flex items-start p-4 bg-slate-700/20 rounded-xl">
                                <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                    <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold">Xarajatlar monitoring</h3>
                                    <p class="text-slate-400 text-sm mt-1">Cost per click, cost per conversion, ROAS</p>
                                </div>
                            </div>

                            <div class="flex items-start p-4 bg-slate-700/20 rounded-xl">
                                <div class="w-10 h-10 bg-yellow-500/20 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                    <svg class="w-5 h-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold">Performance analytics</h3>
                                    <p class="text-slate-400 text-sm mt-1">Kunlik, haftalik, oylik hisobotlar</p>
                                </div>
                            </div>

                            <div class="flex items-start p-4 bg-slate-700/20 rounded-xl">
                                <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                    <svg class="w-5 h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold">Bildirishnomalar</h3>
                                    <p class="text-slate-400 text-sm mt-1">Budget alerts, performance drops</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Setup Instructions -->
                <div class="mt-8 p-6 bg-blue-500/10 border border-blue-500/30 rounded-xl">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-blue-400 mr-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <h3 class="text-blue-400 font-semibold mb-2">Ulash uchun keraklilar</h3>
                            <ul class="text-slate-300 text-sm space-y-1">
                                <li>Google Ads hisobi (Manager yoki oddiy hisob)</li>
                                <li>Google hisobida reklama ma'lumotlariga kirish huquqi</li>
                                <li>BiznesPilot ilovasiga ruxsat berish</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
