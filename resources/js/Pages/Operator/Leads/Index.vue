<script setup>
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    MagnifyingGlassIcon,
    FunnelIcon,
    PhoneIcon,
    EnvelopeIcon,
    ChatBubbleLeftIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    leads: Array,
    stats: Object,
    filters: Object,
});

const searchQuery = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');

const statuses = [
    { value: 'all', label: 'Barchasi' },
    { value: 'new', label: 'Yangi' },
    { value: 'contacted', label: 'Bog\'lanildi' },
    { value: 'qualified', label: 'Kvalifikatsiya' },
    { value: 'lost', label: 'Yo\'qotilgan' },
];

const getStatusClass = (status) => {
    const classes = {
        new: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        contacted: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        qualified: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        lost: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-700';
};

const getStatusLabel = (status) => {
    const labels = {
        new: 'Yangi',
        contacted: 'Bog\'lanildi',
        qualified: 'Kvalifikatsiya',
        lost: 'Yo\'qotilgan',
    };
    return labels[status] || status;
};

const getPriorityClass = (priority) => {
    const classes = {
        high: 'text-red-600 dark:text-red-400',
        medium: 'text-yellow-600 dark:text-yellow-400',
        low: 'text-green-600 dark:text-green-400',
    };
    return classes[priority] || 'text-gray-600';
};

const applyFilters = () => {
    router.get('/operator/leads', {
        search: searchQuery.value,
        status: statusFilter.value !== 'all' ? statusFilter.value : null,
    }, { preserveState: true });
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('uz-UZ', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <OperatorLayout title="Leadlar">
        <Head title="Mening Leadlarim" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mening Leadlarim</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sizga tayinlangan barcha leadlar</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-blue-100 dark:border-blue-900/30">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats?.total || 0 }}</p>
                    <p class="text-sm text-gray-500">Jami</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-blue-100 dark:border-blue-900/30">
                    <p class="text-2xl font-bold text-blue-600">{{ stats?.new || 0 }}</p>
                    <p class="text-sm text-gray-500">Yangi</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-blue-100 dark:border-blue-900/30">
                    <p class="text-2xl font-bold text-yellow-600">{{ stats?.contacted || 0 }}</p>
                    <p class="text-sm text-gray-500">Bog'lanildi</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-blue-100 dark:border-blue-900/30">
                    <p class="text-2xl font-bold text-green-600">{{ stats?.qualified || 0 }}</p>
                    <p class="text-sm text-gray-500">Kvalifikatsiya</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-blue-100 dark:border-blue-900/30">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1 relative">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Lead qidirish..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                    <select
                        v-model="statusFilter"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                    <button
                        @click="applyFilters"
                        class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors"
                    >
                        <FunnelIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Leads List -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-blue-100 dark:border-blue-900/30 overflow-hidden">
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <Link
                        v-for="lead in leads"
                        :key="lead.id"
                        :href="`/operator/leads/${lead.id}`"
                        class="block p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ lead.name }}</h3>
                                    <span :class="['px-2.5 py-1 text-xs font-medium rounded-full', getStatusClass(lead.status)]">
                                        {{ getStatusLabel(lead.status) }}
                                    </span>
                                    <span v-if="lead.priority" :class="['text-xs font-medium', getPriorityClass(lead.priority)]">
                                        {{ lead.priority === 'high' ? 'Yuqori' : lead.priority === 'medium' ? 'O\'rta' : 'Past' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center gap-1">
                                        <PhoneIcon class="w-4 h-4" />
                                        {{ lead.phone }}
                                    </span>
                                    <span v-if="lead.email" class="flex items-center gap-1">
                                        <EnvelopeIcon class="w-4 h-4" />
                                        {{ lead.email }}
                                    </span>
                                </div>
                                <p v-if="lead.last_contact" class="text-sm text-gray-400 mt-2">
                                    Oxirgi aloqa: {{ formatDate(lead.last_contact) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p v-if="lead.source" class="text-sm text-gray-500">{{ lead.source }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ formatDate(lead.created_at) }}</p>
                            </div>
                        </div>
                        <div v-if="lead.notes" class="mt-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="flex items-start gap-2">
                                <ChatBubbleLeftIcon class="w-4 h-4 text-gray-400 mt-0.5" />
                                <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2">{{ lead.notes }}</p>
                            </div>
                        </div>
                    </Link>
                </div>

                <div v-if="!leads?.length" class="p-12 text-center">
                    <UserGroupIcon class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto" />
                    <p class="text-gray-500 dark:text-gray-400 mt-4">Hozircha sizga tayinlangan leadlar yo'q</p>
                </div>
            </div>
        </div>
    </OperatorLayout>
</template>

<script>
import { UserGroupIcon } from '@heroicons/vue/24/outline';
export default { components: { UserGroupIcon } };
</script>
