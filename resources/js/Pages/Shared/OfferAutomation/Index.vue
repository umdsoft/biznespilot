<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import {
    PaperAirplaneIcon,
    ChartBarIcon,
    GiftIcon,
    CurrencyDollarIcon,
    UserGroupIcon,
    CheckCircleIcon,
    ClockIcon,
    EyeIcon,
    ArrowPathIcon,
    PlusIcon,
    FunnelIcon,
    MagnifyingGlassIcon,
    ChatBubbleLeftRightIcon,
    DevicePhoneMobileIcon,
    EnvelopeIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { SparklesIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    panelType: String,
    offers: Array,
    recentAssignments: Array,
    stats: Object,
    channelStats: Array,
    statusLabels: Object,
    channelLabels: Object,
});

const layoutComponent = computed(() => {
    const layouts = {
        business: BusinessLayout,
        saleshead: SalesHeadLayout,
        operator: OperatorLayout,
    };
    return layouts[props.panelType] || BaseLayout;
});

const getRoutePrefix = () => {
    const prefixMap = {
        saleshead: 'sales-head',
        business: 'business',
        operator: 'operator',
    };
    return prefixMap[props.panelType] || props.panelType;
};

const searchQuery = ref('');
const statusFilter = ref('');

const filteredAssignments = computed(() => {
    let result = props.recentAssignments;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(a =>
            a.lead?.name?.toLowerCase().includes(query) ||
            a.offer?.name?.toLowerCase().includes(query)
        );
    }

    if (statusFilter.value) {
        result = result.filter(a => a.status === statusFilter.value);
    }

    return result;
});

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-gray-100 text-gray-700',
        sent: 'bg-blue-100 text-blue-700',
        delivered: 'bg-indigo-100 text-indigo-700',
        viewed: 'bg-purple-100 text-purple-700',
        clicked: 'bg-pink-100 text-pink-700',
        interested: 'bg-yellow-100 text-yellow-700',
        converted: 'bg-green-100 text-green-700',
        rejected: 'bg-red-100 text-red-700',
        expired: 'bg-gray-100 text-gray-500',
    };
    return colors[status] || 'bg-gray-100 text-gray-700';
};

const getChannelIcon = (channel) => {
    const icons = {
        telegram: ChatBubbleLeftRightIcon,
        sms: DevicePhoneMobileIcon,
        email: EnvelopeIcon,
        whatsapp: ChatBubbleLeftRightIcon,
        manual: UserGroupIcon,
    };
    return icons[channel] || ChatBubbleLeftRightIcon;
};

const formatPrice = (price) => {
    if (!price) return '0';
    return new Intl.NumberFormat('uz-UZ').format(price);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('uz-UZ', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const resendOffer = (id) => {
    router.post(route(`${getRoutePrefix()}.offer-automation.resend`, id));
};

const markConverted = (id) => {
    if (confirm('Konversiya sifatida belgilaysizmi?')) {
        router.post(route(`${getRoutePrefix()}.offer-automation.convert`, id));
    }
};
</script>

<template>
    <component :is="layoutComponent" title="Taklif Avtomatizatsiya">
        <Head title="Taklif Avtomatizatsiya" />

        <div class="py-6 min-h-screen bg-gradient-to-br from-gray-50 via-white to-purple-50/30 dark:from-gray-900 dark:via-gray-800 dark:to-purple-900/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                                <PaperAirplaneIcon class="w-6 h-6 text-white" />
                            </div>
                            Taklif Avtomatizatsiya
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400 mt-2">
                            Takliflarni avtomatik yuborish va konversiyani kuzatish
                        </p>
                    </div>

                    <Link
                        :href="route(`${getRoutePrefix()}.offer-automation.create`)"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg"
                    >
                        <PlusIcon class="w-5 h-5" />
                        Taklif Yuborish
                    </Link>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/50 rounded-lg flex items-center justify-center">
                                <GiftIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Faol Takliflar</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_offers }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center">
                                <PaperAirplaneIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Yuborildi</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_sent }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/50 rounded-lg flex items-center justify-center">
                                <CheckCircleIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Konversiya</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_conversions }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/50 rounded-lg flex items-center justify-center">
                                <CurrencyDollarIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Jami Daromad</p>
                                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatPrice(stats.total_revenue) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center">
                                <ChartBarIcon class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">O'rtacha CR</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.avg_conversion_rate?.toFixed(1) }}%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Channel Stats -->
                <div v-if="channelStats.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <div
                        v-for="channel in channelStats"
                        :key="channel.channel"
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 border border-gray-100 dark:border-gray-700"
                    >
                        <div class="flex items-center gap-3 mb-3">
                            <component :is="getChannelIcon(channel.channel)" class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                            <span class="font-medium text-gray-900 dark:text-white">{{ channel.label }}</span>
                        </div>
                        <div class="flex items-end justify-between">
                            <div>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ channel.total }}</p>
                                <p class="text-xs text-gray-500">yuborildi</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-green-600">{{ channel.conversion_rate }}%</p>
                                <p class="text-xs text-gray-500">konversiya</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Offers -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden mb-8">
                    <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 border-b border-purple-100 dark:border-purple-800">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <SparklesIcon class="w-5 h-5 text-purple-600" />
                            Eng Yaxshi Takliflar
                        </h2>
                    </div>
                    <div class="p-6">
                        <div v-if="offers.length === 0" class="text-center py-8 text-gray-500">
                            Hozircha faol takliflar yo'q
                        </div>
                        <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div
                                v-for="offer in offers.slice(0, 6)"
                                :key="offer.id"
                                class="p-4 border-2 border-gray-100 dark:border-gray-700 rounded-xl hover:border-purple-300 dark:hover:border-purple-600 transition-all"
                            >
                                <h3 class="font-bold text-gray-900 dark:text-white mb-2">{{ offer.name }}</h3>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Yuborildi: {{ offer.total_sent }}</span>
                                    <span class="text-green-600 font-semibold">{{ offer.conversion_rate }}% CR</span>
                                </div>
                                <div class="mt-3 flex items-center justify-between">
                                    <span class="text-lg font-bold text-purple-600">{{ formatPrice(offer.pricing) }} so'm</span>
                                    <span class="text-sm text-gray-500">{{ offer.conversions }} sotildi</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Assignments -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-700/50 dark:to-slate-700/50 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <ClockIcon class="w-5 h-5 text-gray-600" />
                                So'nggi Yuborilganlar
                            </h2>

                            <div class="flex items-center gap-3">
                                <!-- Search -->
                                <div class="relative">
                                    <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Qidirish..."
                                        class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm w-48"
                                    />
                                </div>

                                <!-- Status Filter -->
                                <select
                                    v-model="statusFilter"
                                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                                >
                                    <option value="">Barcha holatlar</option>
                                    <option v-for="(label, key) in statusLabels" :key="key" :value="key">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Lead</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Taklif</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Kanal</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Holat</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Ko'rildi</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Sana</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Amallar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr
                                    v-for="assignment in filteredAssignments"
                                    :key="assignment.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                >
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ assignment.lead?.name || '-' }}</div>
                                        <div class="text-sm text-gray-500">{{ assignment.lead?.phone }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ assignment.offer?.name }}</div>
                                        <div class="text-sm text-purple-600">{{ formatPrice(assignment.offered_price) }} so'm</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <component :is="getChannelIcon(assignment.channel)" class="w-4 h-4 text-gray-500" />
                                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ channelLabels[assignment.channel] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="[getStatusColor(assignment.status), 'px-2.5 py-1 rounded-full text-xs font-semibold']">
                                            {{ statusLabels[assignment.status] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-1 text-sm">
                                            <EyeIcon class="w-4 h-4 text-gray-400" />
                                            <span class="text-gray-600 dark:text-gray-400">{{ assignment.view_count }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ formatDate(assignment.sent_at || assignment.created_at) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            <Link
                                                :href="route(`${getRoutePrefix()}.offer-automation.show`, assignment.id)"
                                                class="p-2 text-gray-400 hover:text-purple-600 transition-colors"
                                                title="Ko'rish"
                                            >
                                                <EyeIcon class="w-5 h-5" />
                                            </Link>
                                            <button
                                                v-if="!['converted', 'rejected', 'expired'].includes(assignment.status)"
                                                @click="resendOffer(assignment.id)"
                                                class="p-2 text-gray-400 hover:text-blue-600 transition-colors"
                                                title="Qayta yuborish"
                                            >
                                                <ArrowPathIcon class="w-5 h-5" />
                                            </button>
                                            <button
                                                v-if="!['converted', 'rejected', 'expired'].includes(assignment.status)"
                                                @click="markConverted(assignment.id)"
                                                class="p-2 text-gray-400 hover:text-green-600 transition-colors"
                                                title="Konversiya"
                                            >
                                                <CheckCircleIcon class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredAssignments.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        <PaperAirplaneIcon class="w-12 h-12 mx-auto text-gray-300 mb-4" />
                                        <p>Hozircha yuborilgan takliflar yo'q</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
