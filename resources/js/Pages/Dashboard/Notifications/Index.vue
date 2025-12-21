<template>
    <AppLayout title="Bildirishnomalar">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                    Bildirishnomalar
                </h2>
                <button
                    v-if="stats.unread > 0"
                    @click="markAllAsRead"
                    class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    <CheckIcon class="w-4 h-4 mr-2" />
                    Hammasini o'qilgan deb belgilash
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Stats -->
                <div class="flex items-center space-x-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm px-4 py-2 border border-gray-200 dark:border-gray-700">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Jami: </span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ stats.total }}</span>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/30 rounded-lg shadow-sm px-4 py-2 border border-blue-200 dark:border-blue-800">
                        <span class="text-sm text-blue-600 dark:text-blue-400">O'qilmagan: </span>
                        <span class="font-semibold text-blue-700 dark:text-blue-300">{{ stats.unread }}</span>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap gap-4 mb-6">
                    <select
                        v-model="localFilters.type"
                        @change="applyFilters"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 text-sm"
                    >
                        <option value="all">Barcha turlar</option>
                        <option value="alert">Ogohlantirish</option>
                        <option value="insight">Tavsiya</option>
                        <option value="report">Hisobot</option>
                        <option value="system">Tizim</option>
                        <option value="celebration">Muvaffaqiyat</option>
                    </select>

                    <select
                        v-model="localFilters.status"
                        @change="applyFilters"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 text-sm"
                    >
                        <option value="all">Barcha statuslar</option>
                        <option value="unread">O'qilmagan</option>
                        <option value="read">O'qilgan</option>
                    </select>
                </div>

                <!-- Notifications List -->
                <div class="space-y-2">
                    <div
                        v-for="notification in notifications.data"
                        :key="notification.id"
                        :class="[
                            'bg-white dark:bg-gray-800 rounded-lg shadow-sm border p-4 cursor-pointer transition-all duration-200',
                            !notification.read_at
                                ? 'border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20'
                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                        ]"
                        @click="handleNotificationClick(notification)"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-3">
                                <div :class="['p-2 rounded-lg', getTypeBg(notification.type)]">
                                    <component
                                        :is="getTypeIcon(notification.type)"
                                        :class="['w-5 h-5', getTypeIconColor(notification.type)]"
                                    />
                                </div>

                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span
                                            :class="[
                                                'px-2 py-0.5 rounded text-xs font-medium',
                                                getTypeBadgeClass(notification.type)
                                            ]"
                                        >
                                            {{ getTypeLabel(notification.type) }}
                                        </span>
                                        <span
                                            :class="[
                                                'px-2 py-0.5 rounded text-xs font-medium',
                                                getPriorityBadgeClass(notification.priority)
                                            ]"
                                        >
                                            {{ getPriorityLabel(notification.priority) }}
                                        </span>
                                    </div>

                                    <h4 class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ notification.title }}
                                    </h4>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        {{ notification.message }}
                                    </p>
                                    <p class="mt-2 text-xs text-gray-400">
                                        {{ formatTime(notification.created_at) }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                <span
                                    v-if="!notification.read_at"
                                    class="w-2 h-2 bg-blue-500 rounded-full"
                                />
                                <button
                                    @click.stop="deleteNotification(notification.id)"
                                    class="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                                >
                                    <XMarkIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="notifications.data.length === 0"
                        class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
                    >
                        <BellSlashIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                        <p class="text-gray-500 dark:text-gray-400">Bildirishnomalar topilmadi</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="notifications.last_page > 1" class="mt-6">
                    <Pagination :links="notifications.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import {
    CheckIcon,
    XMarkIcon,
    BellSlashIcon,
    BellAlertIcon,
    LightBulbIcon,
    DocumentChartBarIcon,
    CogIcon,
    TrophyIcon,
} from '@heroicons/vue/24/outline';
import { formatDistanceToNow } from 'date-fns';
import { uz } from 'date-fns/locale';

interface Props {
    notifications: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    stats: {
        total: number;
        unread: number;
    };
    filters: {
        type: string;
        status: string;
    };
}

const props = defineProps<Props>();

const localFilters = reactive({ ...props.filters });

function applyFilters() {
    router.get('/business/notifications', {
        type: localFilters.type !== 'all' ? localFilters.type : undefined,
        status: localFilters.status !== 'all' ? localFilters.status : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function markAllAsRead() {
    router.post('/business/notifications/mark-all-read', {}, {
        preserveScroll: true,
    });
}

function handleNotificationClick(notification: any) {
    if (!notification.read_at) {
        router.post(`/business/notifications/${notification.id}/clicked`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                if (notification.action_url) {
                    router.visit(notification.action_url);
                }
            },
        });
    } else if (notification.action_url) {
        router.visit(notification.action_url);
    }
}

function deleteNotification(id: string) {
    router.delete(`/business/notifications/${id}`, {
        preserveScroll: true,
    });
}

function formatTime(dateString: string): string {
    try {
        return formatDistanceToNow(new Date(dateString), {
            addSuffix: true,
            locale: uz,
        });
    } catch {
        return dateString;
    }
}

function getTypeIcon(type: string) {
    const icons: Record<string, any> = {
        alert: BellAlertIcon,
        insight: LightBulbIcon,
        report: DocumentChartBarIcon,
        system: CogIcon,
        celebration: TrophyIcon,
    };
    return icons[type] || BellAlertIcon;
}

function getTypeBg(type: string): string {
    const bgs: Record<string, string> = {
        alert: 'bg-red-100 dark:bg-red-900',
        insight: 'bg-blue-100 dark:bg-blue-900',
        report: 'bg-purple-100 dark:bg-purple-900',
        system: 'bg-gray-100 dark:bg-gray-700',
        celebration: 'bg-yellow-100 dark:bg-yellow-900',
    };
    return bgs[type] || 'bg-gray-100 dark:bg-gray-700';
}

function getTypeIconColor(type: string): string {
    const colors: Record<string, string> = {
        alert: 'text-red-600 dark:text-red-400',
        insight: 'text-blue-600 dark:text-blue-400',
        report: 'text-purple-600 dark:text-purple-400',
        system: 'text-gray-600 dark:text-gray-400',
        celebration: 'text-yellow-600 dark:text-yellow-400',
    };
    return colors[type] || 'text-gray-600 dark:text-gray-400';
}

function getTypeLabel(type: string): string {
    const labels: Record<string, string> = {
        alert: 'Ogohlantirish',
        insight: 'Tavsiya',
        report: 'Hisobot',
        system: 'Tizim',
        celebration: 'Muvaffaqiyat',
    };
    return labels[type] || type;
}

function getTypeBadgeClass(type: string): string {
    const classes: Record<string, string> = {
        alert: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        insight: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        report: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        system: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        celebration: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
}

function getPriorityLabel(priority: string): string {
    const labels: Record<string, string> = {
        critical: 'Kritik',
        high: 'Yuqori',
        medium: 'O\'rta',
        low: 'Past',
    };
    return labels[priority] || priority;
}

function getPriorityBadgeClass(priority: string): string {
    const classes: Record<string, string> = {
        critical: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        high: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        low: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    };
    return classes[priority] || 'bg-gray-100 text-gray-800';
}
</script>
