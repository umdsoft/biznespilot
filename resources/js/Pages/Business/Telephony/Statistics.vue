<script setup>
import { Head, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    stats: Object,
    provider: String,
});

const formatDuration = (seconds) => {
    if (!seconds) return '0:00';
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    if (hours > 0) {
        return `${hours} soat ${minutes} daqiqa`;
    }
    return `${minutes} daqiqa ${secs} soniya`;
};

const getProviderLabel = () => {
    const labels = {
        pbx: 'PBX',
        sipuni: 'SipUni',
    };
    return labels[props.provider] || 'Telefoniya';
};
</script>

<template>
    <Head title="Qo'ng'iroqlar Statistikasi" />

    <BusinessLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('integrations.telephony.settings')"
                        class="p-2 bg-slate-700 rounded-xl hover:bg-slate-600 transition-colors"
                    >
                        <ArrowLeftIcon class="w-5 h-5 text-white" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Qo'ng'iroqlar Statistikasi</h1>
                        <p class="text-slate-400 text-sm">{{ getProviderLabel() }} - 30 kunlik tahlil</p>
                    </div>
                </div>
            </div>

            <!-- Main Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-white mb-1">{{ stats?.total_calls || 0 }}</p>
                    <p class="text-sm text-slate-400">Jami qo'ng'iroqlar</p>
                </div>

                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-green-400 mb-1">{{ stats?.answered_calls || 0 }}</p>
                    <p class="text-sm text-slate-400">Javob berilgan</p>
                </div>

                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-yellow-500/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-yellow-400 mb-1">{{ stats?.missed_calls || 0 }}</p>
                    <p class="text-sm text-slate-400">Javobsiz</p>
                </div>

                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-purple-400 mb-1">{{ stats?.answer_rate || 0 }}%</p>
                    <p class="text-sm text-slate-400">Javob berish %</p>
                </div>
            </div>

            <!-- Direction Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold text-white mb-6">Yo'nalish bo'yicha</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </div>
                                <span class="text-slate-300">Chiquvchi</span>
                            </div>
                            <span class="text-xl font-bold text-white">{{ stats?.outbound_calls || 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-green-500/20 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18" />
                                    </svg>
                                </div>
                                <span class="text-slate-300">Kiruvchi</span>
                            </div>
                            <span class="text-xl font-bold text-white">{{ stats?.inbound_calls || 0 }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold text-white mb-6">Vaqt bo'yicha</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-teal-500/20 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <span class="text-slate-300">Jami vaqt</span>
                            </div>
                            <span class="text-xl font-bold text-white">{{ formatDuration(stats?.total_duration || 0) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-orange-500/20 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <span class="text-slate-300">O'rtacha davomiylik</span>
                            </div>
                            <span class="text-xl font-bold text-white">
                                {{ stats?.answered_calls > 0 ? Math.round((stats?.total_duration || 0) / stats.answered_calls) : 0 }} sek
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Breakdown -->
            <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-white mb-6">Status bo'yicha taqsimot</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Link
                        :href="route('integrations.telephony.history', { status: 'completed' })"
                        class="bg-slate-700/30 rounded-xl p-4 text-center hover:bg-slate-700/50 transition-colors"
                    >
                        <p class="text-2xl font-bold text-green-400">{{ stats?.answered_calls || 0 }}</p>
                        <p class="text-sm text-slate-400">Tugallangan</p>
                    </Link>
                    <Link
                        :href="route('integrations.telephony.history', { status: 'missed' })"
                        class="bg-slate-700/30 rounded-xl p-4 text-center hover:bg-slate-700/50 transition-colors"
                    >
                        <p class="text-2xl font-bold text-yellow-400">{{ stats?.missed_calls || 0 }}</p>
                        <p class="text-sm text-slate-400">Javobsiz</p>
                    </Link>
                    <Link
                        :href="route('integrations.telephony.history', { status: 'failed' })"
                        class="bg-slate-700/30 rounded-xl p-4 text-center hover:bg-slate-700/50 transition-colors"
                    >
                        <p class="text-2xl font-bold text-red-400">{{ stats?.failed_calls || 0 }}</p>
                        <p class="text-sm text-slate-400">Xatolik</p>
                    </Link>
                    <div class="bg-slate-700/30 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-blue-400">{{ stats?.answer_rate || 0 }}%</p>
                        <p class="text-sm text-slate-400">Konversiya</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-8 flex justify-center gap-4">
                <Link
                    :href="route('integrations.telephony.history')"
                    class="px-6 py-3 bg-slate-700 text-white rounded-xl hover:bg-slate-600 transition-colors"
                >
                    Barcha qo'ng'iroqlarni ko'rish
                </Link>
                <Link
                    :href="route('integrations.telephony.settings')"
                    class="px-6 py-3 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition-colors"
                >
                    Sozlamalarga o'tish
                </Link>
            </div>
        </div>
    </BusinessLayout>
</template>
