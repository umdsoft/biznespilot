<template>
    <div class="p-6 space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Raqobatchilar</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Raqobatchilaringizni kuzating va tahlil qiling</p>
            </div>
            <div class="flex gap-3">
                <Link
                    :href="getRoute('dashboard')"
                    class="inline-flex items-center px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Dashboard
                </Link>
                <button
                    @click="openAddModal"
                    class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-sm font-medium text-white transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Yangi Raqobatchi
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ totalCompetitors }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Jami</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ activeCount }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Faol</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ criticalCount }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Kritik tahdid</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ highCount }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Yuqori tahdid</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Nomi yoki tavsif bo'yicha qidiring..."
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        />
                    </div>
                </div>
                <div>
                    <select
                        v-model="filters.threat_level"
                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="">Barcha tahdidlar</option>
                        <option value="low">Past tahdid</option>
                        <option value="medium">O'rta tahdid</option>
                        <option value="high">Yuqori tahdid</option>
                        <option value="critical">Kritik tahdid</option>
                    </select>
                </div>
                <div>
                    <select
                        v-model="filters.status"
                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="">Barcha statuslar</option>
                        <option value="active">Faol</option>
                        <option value="inactive">Nofaol</option>
                        <option value="archived">Arxivlangan</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="filteredCompetitors.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
            <div class="mx-auto w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-1">Raqobatchilar topilmadi</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Yangi raqobatchi qo'shish uchun yuqoridagi tugmani bosing.</p>
            <button
                @click="openAddModal"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-sm font-medium text-white transition-colors"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Raqobatchi qo'shish
            </button>
        </div>

        <!-- Competitors Table -->
        <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Raqobatchi</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Instagram</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Telegram</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Tahdid</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Amallar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr
                            v-for="competitor in filteredCompetitors"
                            :key="competitor.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm">
                                        {{ competitor.name.substring(0, 2).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <Link
                                            :href="getRoute('show', competitor.id)"
                                            class="font-medium text-gray-900 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400 truncate block"
                                        >
                                            {{ competitor.name }}
                                        </Link>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            {{ competitor.industry || competitor.location || 'Ma\'lumot yo\'q' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div v-if="competitor.instagram_handle" class="flex flex-col items-center">
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ formatNumber(getLatestMetric(competitor, 'instagram_followers')) }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ competitor.instagram_handle }}</span>
                                </div>
                                <span v-else class="text-gray-300 dark:text-gray-600">—</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div v-if="competitor.telegram_handle" class="flex flex-col items-center">
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ formatNumber(getLatestMetric(competitor, 'telegram_members')) }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ competitor.telegram_handle }}</span>
                                </div>
                                <span v-else class="text-gray-300 dark:text-gray-600">—</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    :class="getThreatBadgeClass(competitor.threat_level)"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full"
                                >
                                    {{ getThreatLevelText(competitor.threat_level) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    :class="getStatusBadgeClass(competitor.status)"
                                    class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md"
                                >
                                    {{ getStatusText(competitor.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <Link
                                        :href="getRoute('show', competitor.id)"
                                        class="p-1.5 text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors"
                                        title="Ko'rish"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                    <button
                                        @click="$emit('edit', competitor)"
                                        class="p-1.5 text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                        title="Tahrirlash"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="$emit('delete', competitor)"
                                        class="p-1.5 text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                        title="O'chirish"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400">
                Jami {{ filteredCompetitors.length }} ta raqobatchi
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    competitors: { type: [Array, Object], default: () => [] },
    stats: { type: Object, default: () => ({}) },
    currentBusiness: { type: Object, default: null },
    panelType: {
        type: String,
        required: true,
        validator: (v) => ['business', 'marketing', 'finance', 'operator', 'saleshead'].includes(v),
    },
});

const emit = defineEmits(['add', 'edit', 'delete']);

const filters = ref({
    search: '',
    threat_level: '',
    status: '',
});

// Route helper based on panel type
const getRoute = (action, id = null) => {
    const prefix = props.panelType;
    const routes = {
        dashboard: `/${prefix}/competitors/dashboard`,
        index: `/${prefix}/competitors`,
        show: `/${prefix}/competitors/${id}`,
        create: `/${prefix}/competitors/create`,
    };
    return routes[action] || routes.index;
};

const competitorsList = computed(() => {
    return Array.isArray(props.competitors)
        ? props.competitors
        : (props.competitors?.data || []);
});

const totalCompetitors = computed(() => competitorsList.value.length);
const activeCount = computed(() => competitorsList.value.filter(c => c.status === 'active').length);
const criticalCount = computed(() => competitorsList.value.filter(c => c.threat_level === 'critical').length);
const highCount = computed(() => competitorsList.value.filter(c => c.threat_level === 'high').length);

const filteredCompetitors = computed(() => {
    let result = competitorsList.value;
    if (filters.value.search) {
        const search = filters.value.search.toLowerCase();
        result = result.filter(c =>
            c.name.toLowerCase().includes(search) ||
            (c.description && c.description.toLowerCase().includes(search))
        );
    }
    if (filters.value.threat_level) {
        result = result.filter(c => c.threat_level === filters.value.threat_level);
    }
    if (filters.value.status) {
        result = result.filter(c => c.status === filters.value.status);
    }
    return result;
});

const openAddModal = () => emit('add');

const getThreatLevelText = (level) => {
    const levels = { low: 'Past', medium: "O'rta", high: 'Yuqori', critical: 'Kritik' };
    return levels[level] || level;
};

const getThreatBadgeClass = (level) => {
    const classes = {
        low: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        medium: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
        high: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
        critical: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
    };
    return classes[level] || classes.medium;
};

const getStatusText = (status) => {
    const statuses = { active: 'Faol', inactive: 'Nofaol', archived: 'Arxivlangan' };
    return statuses[status] || status;
};

const getStatusBadgeClass = (status) => {
    const classes = {
        active: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        inactive: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
        archived: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    };
    return classes[status] || classes.inactive;
};

const formatNumber = (num) => {
    if (num === null || num === undefined) return '—';
    if (num === 0) return '0';
    if (num >= 1000000) return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
    return num.toString();
};

const getLatestMetric = (competitor, field) => {
    const latestMetric = competitor.metrics?.[0];
    if (!latestMetric) return null;
    return latestMetric[field] ?? null;
};
</script>
