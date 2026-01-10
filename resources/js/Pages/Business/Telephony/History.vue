<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import { ref, watch } from 'vue';
import {
    MagnifyingGlassIcon,
    ArrowLeftIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    PhoneArrowUpRightIcon,
    PhoneArrowDownLeftIcon,
    PlayIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    calls: Object,
    filters: {
        type: Object,
        default: () => ({ status: null, direction: null, search: '' }),
    },
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const direction = ref(props.filters.direction || 'all');

const statusOptions = [
    { value: 'all', label: 'Barchasi' },
    { value: 'completed', label: 'Tugallangan' },
    { value: 'answered', label: 'Javob berilgan' },
    { value: 'no_answer', label: 'Javobsiz' },
    { value: 'busy', label: 'Band' },
    { value: 'failed', label: 'Xatolik' },
    { value: 'missed', label: "O'tkazib yuborilgan" },
];

const directionOptions = [
    { value: 'all', label: 'Barchasi' },
    { value: 'outbound', label: 'Chiquvchi' },
    { value: 'inbound', label: 'Kiruvchi' },
];

const getStatusColor = (status) => {
    const colors = {
        initiated: 'bg-gray-500/20 text-gray-400 border-gray-500/30',
        ringing: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
        answered: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        completed: 'bg-green-500/20 text-green-400 border-green-500/30',
        failed: 'bg-red-500/20 text-red-400 border-red-500/30',
        missed: 'bg-orange-500/20 text-orange-400 border-orange-500/30',
        busy: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
        no_answer: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
    };
    return colors[status] || 'bg-slate-500/20 text-slate-400 border-slate-500/30';
};

const getDirectionIcon = (direction) => {
    return direction === 'outbound' ? PhoneArrowUpRightIcon : PhoneArrowDownLeftIcon;
};

const getDirectionColor = (direction) => {
    return direction === 'outbound' ? 'text-blue-400' : 'text-green-400';
};

const getProviderLabel = (provider) => {
    const labels = {
        pbx: 'PBX',
        sipuni: 'SipUni',
    };
    return labels[provider] || provider;
};

const applyFilters = () => {
    router.get(route('business.telephony.history'), {
        status: status.value === 'all' ? null : status.value,
        direction: direction.value === 'all' ? null : direction.value,
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

watch(direction, () => {
    applyFilters();
});

const goToPage = (url) => {
    if (url) {
        router.get(url, {}, { preserveState: true });
    }
};

const playRecording = (url) => {
    if (url) {
        window.open(url, '_blank');
    }
};
</script>

<template>
    <Head title="Qo'ng'iroqlar Tarixi" />

    <BusinessLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('business.settings.telephony')"
                        class="p-2 bg-slate-700 rounded-xl hover:bg-slate-600 transition-colors"
                    >
                        <ArrowLeftIcon class="w-5 h-5 text-white" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Qo'ng'iroqlar Tarixi</h1>
                        <p class="text-slate-400 text-sm">Barcha qo'ng'iroqlar ro'yxati</p>
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
                                placeholder="Qidirish (telefon, ism)..."
                                class="w-full pl-10 pr-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Direction Filter -->
                    <div class="sm:w-40">
                        <select
                            v-model="direction"
                            class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        >
                            <option v-for="option in directionOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="sm:w-48">
                        <select
                            v-model="status"
                            class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        >
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Calls Table -->
            <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-700/30">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                    Yo'nalish
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                    Kontakt
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                    Davomiyligi
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                    Provayder
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                    Sana
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                    Yozuv
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/50">
                            <tr v-if="calls.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <svg class="w-12 h-12 text-slate-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    <p class="text-slate-400">Qo'ng'iroqlar topilmadi</p>
                                </td>
                            </tr>
                            <tr
                                v-for="call in calls.data"
                                :key="call.id"
                                class="hover:bg-slate-700/20 transition-colors"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <component
                                            :is="getDirectionIcon(call.direction)"
                                            :class="['w-5 h-5', getDirectionColor(call.direction)]"
                                        />
                                        <span class="text-sm text-slate-300">{{ call.direction_label }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="text-white font-medium">
                                            <Link v-if="call.lead" :href="route('business.sales.show', call.lead.id)" class="hover:text-blue-400 transition-colors">
                                                {{ call.lead.name }}
                                            </Link>
                                            <span v-else>Noma'lum</span>
                                        </p>
                                        <p class="text-slate-400 text-sm">
                                            {{ call.direction === 'outbound' ? call.to_number : call.from_number }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-lg text-xs font-medium border',
                                        getStatusColor(call.status)
                                    ]">
                                        {{ call.status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-slate-300 text-sm">{{ call.formatted_duration }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-slate-300 text-sm">{{ getProviderLabel(call.provider) }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-slate-400 text-sm">{{ call.created_at }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <button
                                        v-if="call.recording_url"
                                        @click="playRecording(call.recording_url)"
                                        class="p-2 bg-slate-600 rounded-lg hover:bg-slate-500 transition-colors"
                                        title="Yozuvni tinglash"
                                    >
                                        <PlayIcon class="w-4 h-4 text-white" />
                                    </button>
                                    <span v-else class="text-slate-500 text-sm">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="calls.last_page > 1" class="px-6 py-4 border-t border-slate-700/50 flex items-center justify-between">
                    <p class="text-sm text-slate-400">
                        {{ calls.from }}-{{ calls.to }} / {{ calls.total }} ta qo'ng'iroq
                    </p>
                    <div class="flex items-center gap-2">
                        <button
                            @click="goToPage(calls.prev_page_url)"
                            :disabled="!calls.prev_page_url"
                            class="p-2 bg-slate-700 rounded-lg hover:bg-slate-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <ChevronLeftIcon class="w-5 h-5 text-white" />
                        </button>
                        <span class="text-slate-300 px-3">
                            {{ calls.current_page }} / {{ calls.last_page }}
                        </span>
                        <button
                            @click="goToPage(calls.next_page_url)"
                            :disabled="!calls.next_page_url"
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
