<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    leads: { type: Array, default: () => [] },
    showAvatar: { type: Boolean, default: false },
    showDate: { type: Boolean, default: false },
    basePath: { type: String, default: '/operator/leads' },
    emptyText: { type: String, default: 'Hozircha leadlar yo\'q' },
});

const getStatusClass = (status) => {
    const classes = {
        new: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        contacted: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        qualified: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        proposal: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        negotiation: 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400',
        won: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        lost: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400';
};

const getStatusLabel = (status) => {
    const labels = {
        new: 'Yangi',
        contacted: "Bog'lanildi",
        qualified: 'Kvalifikatsiya',
        proposal: 'Taklif',
        negotiation: 'Muzokara',
        won: 'Yutildi',
        lost: "Yo'qotildi",
    };
    return labels[status] || status;
};

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('uz-UZ', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <div>
        <template v-if="leads?.length">
            <Link
                v-for="lead in leads"
                :key="lead.id"
                :href="`${basePath}/${lead.id}`"
                class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            v-if="showAvatar"
                            class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center"
                        >
                            <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                {{ getInitials(lead.name) }}
                            </span>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white">{{ lead.name }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ lead.phone || lead.email }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span :class="['px-2.5 py-1 text-xs font-medium rounded-full', getStatusClass(lead.status)]">
                            {{ getStatusLabel(lead.status) }}
                        </span>
                        <p v-if="showDate" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ formatDate(lead.created_at) }}
                        </p>
                    </div>
                </div>
            </Link>
        </template>
        <div v-else class="p-8 text-center text-gray-500 dark:text-gray-400">
            {{ emptyText }}
        </div>
    </div>
</template>
