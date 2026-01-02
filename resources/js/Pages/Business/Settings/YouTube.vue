<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
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
    if (confirm('YouTube integratsiyasini o\'chirmoqchimisiz?')) {
        isDisconnecting.value = true;
        router.post(route('business.settings.youtube.disconnect'), {}, {
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
    <Head title="YouTube Analytics Integration" />

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
                        <div class="w-20 h-20 bg-gradient-to-br from-red-500 to-red-600 rounded-2xl flex items-center justify-center mr-6 shadow-lg">
                            <svg class="w-12 h-12 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">YouTube Analytics</h1>
                            <p class="text-slate-400 mt-2">YouTube kanalingiz statistikasini ko'ring va tahlil qiling</p>
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
                                        <p class="text-slate-400 text-sm">{{ integration.account_name || 'YouTube kanali' }}</p>
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
                                    <p class="text-slate-400 text-sm">Kanal ID</p>
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
                            <h3 class="text-xl font-semibold text-white mb-2">YouTube ulanmagan</h3>
                            <p class="text-slate-400 mb-8 max-w-md mx-auto">
                                YouTube kanalingizni ulang va video statistikalaringizni to'g'ridan-to'g'ri BiznesPilot orqali ko'ring
                            </p>
                            <button
                                @click="connect"
                                :disabled="isConnecting"
                                class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-red-500 to-red-600 text-white font-semibold rounded-xl hover:from-red-600 hover:to-red-700 transition-all transform hover:-translate-y-0.5 shadow-lg disabled:opacity-50"
                            >
                                <svg class="w-6 h-6 mr-3" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                                <span v-if="isConnecting">Ulanmoqda...</span>
                                <span v-else>YouTube bilan ulash</span>
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
                                <div class="w-10 h-10 bg-red-500/20 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                    <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold">Ko'rishlar statistikasi</h3>
                                    <p class="text-slate-400 text-sm mt-1">Videolar bo'yicha ko'rishlar soni va dinamikasi</p>
                                </div>
                            </div>

                            <div class="flex items-start p-4 bg-slate-700/20 rounded-xl">
                                <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                    <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold">Obunachilar tahlili</h3>
                                    <p class="text-slate-400 text-sm mt-1">Obunachilar soni, o'sish dinamikasi</p>
                                </div>
                            </div>

                            <div class="flex items-start p-4 bg-slate-700/20 rounded-xl">
                                <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                    <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold">Tomosha vaqti</h3>
                                    <p class="text-slate-400 text-sm mt-1">O'rtacha tomosha vaqti va retention rate</p>
                                </div>
                            </div>

                            <div class="flex items-start p-4 bg-slate-700/20 rounded-xl">
                                <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                    <svg class="w-5 h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold">Daromad tahlili</h3>
                                    <p class="text-slate-400 text-sm mt-1">Monetizatsiya va daromad statistikasi</p>
                                </div>
                            </div>

                            <div class="flex items-start p-4 bg-slate-700/20 rounded-xl">
                                <div class="w-10 h-10 bg-yellow-500/20 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                    <svg class="w-5 h-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold">Engagement</h3>
                                    <p class="text-slate-400 text-sm mt-1">Layklar, izohlar, ulashishlar</p>
                                </div>
                            </div>

                            <div class="flex items-start p-4 bg-slate-700/20 rounded-xl">
                                <div class="w-10 h-10 bg-orange-500/20 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                    <svg class="w-5 h-5 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold">Demografiya</h3>
                                    <p class="text-slate-400 text-sm mt-1">Tomoshabinlar geografiyasi va yoshi</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Setup Instructions -->
                <div class="mt-8 p-6 bg-red-500/10 border border-red-500/30 rounded-xl">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-red-400 mr-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <h3 class="text-red-400 font-semibold mb-2">Ulash uchun keraklilar</h3>
                            <ul class="text-slate-300 text-sm space-y-1">
                                <li>YouTube kanali (Google hisobiga ulangan)</li>
                                <li>YouTube Analytics ma'lumotlariga kirish huquqi</li>
                                <li>BiznesPilot ilovasiga ruxsat berish</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
