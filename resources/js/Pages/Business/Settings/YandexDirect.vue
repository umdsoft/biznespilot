<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { ref, computed } from 'vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

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
    if (confirm('Yandex Direct integratsiyasini o\'chirmoqchimisiz?')) {
        isDisconnecting.value = true;
        router.post(route('business.settings.yandex-direct.disconnect'), {}, {
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
    <Head title="Yandex Direct Integration" />

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
                        <div class="w-20 h-20 bg-gradient-to-br from-yellow-400 to-red-500 rounded-2xl flex items-center justify-center mr-6 shadow-lg">
                            <svg class="w-12 h-12 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1.5 15.5v-7l6 3.5-6 3.5z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">Yandex Direct</h1>
                            <p class="text-slate-400 mt-2">Yandex reklama kampaniyalarini ulang va boshqaring</p>
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
                                        <p class="text-slate-400 text-sm">{{ integration.account_name || 'Yandex Direct hisobi' }}</p>
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
                            <h3 class="text-xl font-semibold text-white mb-2">Yandex Direct ulanmagan</h3>
                            <p class="text-slate-400 mb-8 max-w-md mx-auto">
                                Yandex Direct hisobingizni ulang va reklama kampaniyalaringizni to'g'ridan-to'g'ri BiznesPilot orqali boshqaring
                            </p>
                            <button
                                @click="connect"
                                :disabled="isConnecting"
                                class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-yellow-500 to-red-500 text-white font-semibold rounded-xl hover:from-yellow-600 hover:to-red-600 transition-all transform hover:-translate-y-0.5 shadow-lg disabled:opacity-50"
                            >
                                <svg class="w-6 h-6 mr-3" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1.5 15.5v-7l6 3.5-6 3.5z"/>
                                </svg>
                                <span v-if="isConnecting">Ulanmoqda...</span>
                                <span v-else>Yandex Direct bilan ulash</span>
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
                                <div class="w-10 h-10 bg-yellow-500/20 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                    <svg class="w-5 h-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold">Kampaniya statistikasi</h3>
                                    <p class="text-slate-400 text-sm mt-1">Ko'rishlar, kliklar, CTR, konversiyalar</p>
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
                                    <p class="text-slate-400 text-sm mt-1">Klik narxi, konversiya narxi, ROI</p>
                                </div>
                            </div>

                            <div class="flex items-start p-4 bg-slate-700/20 rounded-xl">
                                <div class="w-10 h-10 bg-red-500/20 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                    <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold">Qidiruv so'zlari</h3>
                                    <p class="text-slate-400 text-sm mt-1">Kalit so'zlar samaradorligi va pozitsiyalari</p>
                                </div>
                            </div>

                            <div class="flex items-start p-4 bg-slate-700/20 rounded-xl">
                                <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                    <svg class="w-5 h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold">Raqobatchilar tahlili</h3>
                                    <p class="text-slate-400 text-sm mt-1">Bozordagi pozitsiya va raqobat</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Setup Instructions -->
                <div class="mt-8 p-6 bg-yellow-500/10 border border-yellow-500/30 rounded-xl">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-yellow-400 mr-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <h3 class="text-yellow-400 font-semibold mb-2">Ulash uchun keraklilar</h3>
                            <ul class="text-slate-300 text-sm space-y-1">
                                <li>Yandex Direct hisobi</li>
                                <li>Yandex hisobida reklama ma'lumotlariga kirish huquqi</li>
                                <li>BiznesPilot ilovasiga ruxsat berish</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
