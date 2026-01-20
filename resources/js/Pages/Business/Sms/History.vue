<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { ref, computed, watch } from 'vue';
import { useI18n } from '@/i18n';
import {
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowLeftIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    messages: Object,
    filters: {
        type: Object,
        default: () => ({ status: null, search: '' }),
    },
});

const { t } = useI18n();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');

const statusOptions = [
    { value: 'all', label: 'Barchasi' },
    { value: 'pending', label: 'Kutilmoqda' },
    { value: 'sent', label: 'Yuborildi' },
    { value: 'delivered', label: 'Yetkazildi' },
    { value: 'failed', label: 'Xatolik' },
];

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
        sent: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        delivered: 'bg-green-500/20 text-green-400 border-green-500/30',
        failed: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return colors[status] || 'bg-slate-500/20 text-slate-400 border-slate-500/30';
};

const getStatusLabel = (status) => {
    const labels = {
        pending: 'Kutilmoqda',
        sent: 'Yuborildi',
        delivered: 'Yetkazildi',
        failed: 'Xatolik',
    };
    return labels[status] || status;
};

const getProviderLabel = (provider) => {
    const labels = {
        eskiz: 'Eskiz',
        playmobile: 'PlayMobile',
    };
    return labels[provider] || provider;
};

const applyFilters = () => {
    router.get(route('business.sms.history'), {
        status: status.value === 'all' ? null : status.value,
        search: search.value || null,
    }, {
        preserveState: true,
        replace: true,
    });
};

// Debounce search
let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
});

watch(status, () => {
    applyFilters();
});

const goToPage = (url) => {
    if (url) {
        router.get(url, {}, { preserveState: true });
    }
};
</script>

<template>
    <Head title="SMS Tarixi" />

    <BusinessLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('business.settings.sms')"
                        class="p-2 bg-slate-700 rounded-xl hover:bg-slate-600 transition-colors"
                    >
                        <ArrowLeftIcon class="w-5 h-5 text-white" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-white">SMS Tarixi</h1>
                        <p class="text-slate-400 text-sm">Barcha yuborilgan SMS xabarlar</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl p-6 mb-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <!-- Search -->
                    <div class="flex-1">
                        <div class="relative">
                            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Qidirish (telefon, xabar, ism)..."
                                class="w-full pl-10 pr-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                            />
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="sm:w-48">
                        <select
                            v-model="status"
                            class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                        >
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Messages Table -->
            <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-700/30">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                    Qabul qiluvchi
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                    Xabar
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                    Provayder
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                    Qismlar
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                    Sana
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/50">
                            <tr v-if="messages.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <svg class="w-12 h-12 text-slate-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                    </svg>
                                    <p class="text-slate-400">SMS xabarlar topilmadi</p>
                                </td>
                            </tr>
                            <tr
                                v-for="msg in messages.data"
                                :key="msg.id"
                                class="hover:bg-slate-700/20 transition-colors"
                            >
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="text-white font-medium">{{ msg.lead?.name || 'Noma\'lum' }}</p>
                                        <p class="text-slate-400 text-sm">{{ msg.phone }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-slate-300 text-sm max-w-xs truncate" :title="msg.message">
                                        {{ msg.message }}
                                    </p>
                                    <p v-if="msg.error_message" class="text-red-400 text-xs mt-1">
                                        {{ msg.error_message }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-lg text-xs font-medium border',
                                        getStatusColor(msg.status)
                                    ]">
                                        {{ getStatusLabel(msg.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-slate-300 text-sm">{{ getProviderLabel(msg.provider) }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-slate-300 text-sm">{{ msg.parts_count }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-slate-400 text-sm">{{ msg.created_at }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="messages.last_page > 1" class="px-6 py-4 border-t border-slate-700/50 flex items-center justify-between">
                    <p class="text-sm text-slate-400">
                        {{ messages.from }}-{{ messages.to }} / {{ messages.total }} ta xabar
                    </p>
                    <div class="flex items-center gap-2">
                        <button
                            @click="goToPage(messages.prev_page_url)"
                            :disabled="!messages.prev_page_url"
                            class="p-2 bg-slate-700 rounded-lg hover:bg-slate-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <ChevronLeftIcon class="w-5 h-5 text-white" />
                        </button>
                        <span class="text-slate-300 px-3">
                            {{ messages.current_page }} / {{ messages.last_page }}
                        </span>
                        <button
                            @click="goToPage(messages.next_page_url)"
                            :disabled="!messages.next_page_url"
                            class="p-2 bg-slate-700 rounded-lg hover:bg-slate-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <ChevronRightIcon class="w-5 h-5 text-white" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
