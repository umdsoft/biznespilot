<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

import {
    BugAntIcon,
    LightBulbIcon,
    QuestionMarkCircleIcon,
    ChatBubbleOvalLeftEllipsisIcon,
    FunnelIcon,
    MagnifyingGlassIcon,
    EyeIcon,
    TrashIcon,
    CheckCircleIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    ArrowPathIcon,
    PaperClipIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    feedbacks: Object,
    stats: Object,
    filters: Object,
    types: Object,
    statuses: Object,
    priorities: Object,
});

// State
const search = ref(props.filters?.search || '');
const selectedType = ref(props.filters?.type || 'all');
const selectedStatus = ref(props.filters?.status || 'all');
const selectedPriority = ref(props.filters?.priority || 'all');
const showFilters = ref(false);

// Type icons
const typeIcons = {
    bug: BugAntIcon,
    suggestion: LightBulbIcon,
    question: QuestionMarkCircleIcon,
    other: ChatBubbleOvalLeftEllipsisIcon,
};

// Color classes
const getTypeColorClass = (type) => {
    const colors = {
        bug: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        suggestion: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        question: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        other: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
    };
    return colors[type] || colors.other;
};

const getStatusColorClass = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        in_progress: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        resolved: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        closed: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
    };
    return colors[status] || colors.pending;
};

const getPriorityColorClass = (priority) => {
    const colors = {
        low: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        medium: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        high: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        urgent: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return colors[priority] || colors.medium;
};

// Filter and search
const applyFilters = () => {
    router.get(route('admin.feedback.index'), {
        type: selectedType.value !== 'all' ? selectedType.value : null,
        status: selectedStatus.value !== 'all' ? selectedStatus.value : null,
        priority: selectedPriority.value !== 'all' ? selectedPriority.value : null,
        search: search.value || null,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Debounced search
let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
});

watch([selectedType, selectedStatus, selectedPriority], applyFilters);

// Update status
const updateStatus = async (feedback, newStatus) => {
    try {
        const response = await fetch(route('admin.feedback.update-status', feedback.id), {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ status: newStatus }),
        });
        if (response.ok) {
            router.reload({ preserveScroll: true });
        }
    } catch (error) {
        console.error('Failed to update status:', error);
    }
};

// Delete feedback
const deleteFeedback = async (feedback) => {
    if (!confirm(t('admin.feedback.delete_confirm'))) return;

    try {
        const response = await fetch(route('admin.feedback.destroy', feedback.id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });
        if (response.ok) {
            router.reload({ preserveScroll: true });
        }
    } catch (error) {
        console.error('Failed to delete feedback:', error);
    }
};
</script>

<template>
    <AdminLayout :title="t('admin.feedback.title')">
        <Head :title="t('admin.feedback.title')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('admin.feedback.heading') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ t('admin.feedback.subtitle') }}
                    </p>
                </div>
                <Link
                    :href="route('admin.feedback.analytics')"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors"
                >
                    <ChartBarIcon class="w-5 h-5" />
                    {{ t('admin.feedback.analytics') }}
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                            <ChatBubbleOvalLeftEllipsisIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.feedback.stats.total') }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-yellow-200 dark:border-yellow-800/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                            <ClockIcon class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.pending }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.feedback.stats.pending') }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-blue-200 dark:border-blue-800/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <ArrowPathIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.in_progress }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.feedback.stats.in_progress') }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-green-200 dark:border-green-800/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <CheckCircleIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.resolved }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.feedback.stats.resolved') }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-red-200 dark:border-red-800/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                            <BugAntIcon class="w-5 h-5 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.bugs }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.feedback.stats.bugs') }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-orange-200 dark:border-orange-800/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                            <ExclamationTriangleIcon class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ stats.urgent }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.feedback.stats.urgent') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters & Search -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Search -->
                    <div class="flex-1 relative">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="t('common.search') + '...'"
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white"
                        />
                    </div>

                    <!-- Filters -->
                    <div class="flex flex-wrap gap-3">
                        <select
                            v-model="selectedType"
                            class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white text-sm"
                        >
                            <option value="all">{{ t('admin.feedback.all_types') }}</option>
                            <option v-for="(label, value) in types" :key="value" :value="value">{{ label }}</option>
                        </select>

                        <select
                            v-model="selectedStatus"
                            class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white text-sm"
                        >
                            <option value="all">{{ t('admin.feedback.all_statuses') }}</option>
                            <option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option>
                        </select>

                        <select
                            v-model="selectedPriority"
                            class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white text-sm"
                        >
                            <option value="all">{{ t('admin.feedback.all_priorities') }}</option>
                            <option v-for="(label, value) in priorities" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Feedback List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ t('admin.feedback.type') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ t('admin.feedback.subject') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ t('admin.feedback.user') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ t('common.status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ t('admin.feedback.priority') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ t('common.date') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="feedback in feedbacks.data"
                                :key="feedback.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                            >
                                <td class="px-6 py-4">
                                    <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium', getTypeColorClass(feedback.type)]">
                                        <component :is="typeIcons[feedback.type]" class="w-4 h-4" />
                                        {{ feedback.type_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="max-w-xs">
                                        <Link
                                            :href="route('admin.feedback.show', feedback.id)"
                                            class="font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400"
                                        >
                                            {{ feedback.title }}
                                        </Link>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                            {{ feedback.description.substring(0, 60) }}...
                                        </p>
                                        <div v-if="feedback.attachments.length > 0" class="flex items-center gap-1 mt-1 text-xs text-gray-400">
                                            <PaperClipIcon class="w-3.5 h-3.5" />
                                            {{ feedback.attachments.length }} {{ t('admin.feedback.files') }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="feedback.user">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ feedback.user.name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ feedback.user.email }}</p>
                                    </div>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4">
                                    <select
                                        :value="feedback.status"
                                        @change="updateStatus(feedback, $event.target.value)"
                                        :class="['text-xs font-medium rounded-lg px-2.5 py-1 border-0', getStatusColorClass(feedback.status)]"
                                    >
                                        <option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['px-2.5 py-1 rounded-lg text-xs font-medium', getPriorityColorClass(feedback.priority)]">
                                        {{ feedback.priority_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ feedback.created_at }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link
                                            :href="route('admin.feedback.show', feedback.id)"
                                            class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                        >
                                            <EyeIcon class="w-5 h-5" />
                                        </Link>
                                        <button
                                            @click="deleteFeedback(feedback)"
                                            class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                        >
                                            <TrashIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="feedbacks.data.length === 0" class="text-center py-12">
                    <ChatBubbleOvalLeftEllipsisIcon class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ t('admin.feedback.not_found') }}</h3>
                    <p class="text-gray-500 dark:text-gray-400">{{ t('admin.feedback.no_messages') }}</p>
                </div>

                <!-- Pagination -->
                <div v-if="feedbacks.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ feedbacks.from }}-{{ feedbacks.to }} / {{ feedbacks.total }}
                        </p>
                        <div class="flex gap-2">
                            <Link
                                v-if="feedbacks.prev_page_url"
                                :href="feedbacks.prev_page_url"
                                class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm"
                            >
                                {{ t('common.previous') }}
                            </Link>
                            <Link
                                v-if="feedbacks.next_page_url"
                                :href="feedbacks.next_page_url"
                                class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm"
                            >
                                {{ t('common.next') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
