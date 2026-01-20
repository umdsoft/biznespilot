<template>
    <BusinessLayout :title="t('notifications.title')">
        <div class="space-y-6">
            <!-- Hero Header -->
            <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-700 rounded-2xl p-6 md:p-8">
                <div class="absolute inset-0 bg-grid-white/10 [mask-image:linear-gradient(0deg,transparent,white)]"></div>
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-24 h-24 bg-white/10 rounded-full blur-xl"></div>

                <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0 w-14 h-14 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-white">{{ t('notifications.title') }}</h1>
                            <p class="mt-1 text-blue-100 text-sm md:text-base">{{ t('notifications.subtitle') }}</p>
                        </div>
                    </div>

                    <button
                        v-if="stats.unread > 0"
                        @click="markAllAsRead"
                        class="inline-flex items-center px-5 py-2.5 bg-white/20 backdrop-blur-sm text-white rounded-xl text-sm font-medium hover:bg-white/30 transition-all duration-200 border border-white/20"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ t('notifications.mark_all_read') }}
                    </button>
                </div>

                <!-- Stats inside hero -->
                <div class="relative mt-6 grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-white">{{ stats.total }}</p>
                                <p class="text-xs text-blue-200">{{ t('notifications.stats.total') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-blue-400/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-white">{{ stats.unread }}</p>
                                <p class="text-xs text-blue-200">{{ t('notifications.stats.unread') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-green-400/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-white">{{ stats.total - stats.unread }}</p>
                                <p class="text-xs text-blue-200">{{ t('notifications.stats.read') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-purple-400/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-white">{{ todayCount }}</p>
                                <p class="text-xs text-blue-200">{{ t('notifications.stats.today') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span class="font-medium">{{ t('notifications.filter') }}:</span>
                    </div>

                    <select
                        v-model="localFilters.type"
                        @change="applyFilters"
                        class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border-0 rounded-xl text-sm text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500 cursor-pointer"
                    >
                        <option value="all">{{ t('notifications.filter.all_types') }}</option>
                        <option value="alert">{{ t('notifications.filter.alert') }}</option>
                        <option value="insight">{{ t('notifications.filter.insight') }}</option>
                        <option value="report">{{ t('notifications.filter.report') }}</option>
                        <option value="system">{{ t('notifications.filter.system') }}</option>
                        <option value="celebration">{{ t('notifications.filter.celebration') }}</option>
                        <option value="update">{{ t('notifications.filter.update') }}</option>
                        <option value="announcement">{{ t('notifications.filter.announcement') }}</option>
                    </select>

                    <select
                        v-model="localFilters.status"
                        @change="applyFilters"
                        class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border-0 rounded-xl text-sm text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500 cursor-pointer"
                    >
                        <option value="all">{{ t('notifications.filter.all_statuses') }}</option>
                        <option value="unread">{{ t('notifications.filter.unread') }}</option>
                        <option value="read">{{ t('notifications.filter.read') }}</option>
                    </select>

                    <div v-if="localFilters.type !== 'all' || localFilters.status !== 'all'" class="ml-auto">
                        <button
                            @click="clearFilters"
                            class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 flex items-center space-x-1"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span>{{ t('notifications.filter.clear') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="space-y-3">
                <TransitionGroup name="notification-list">
                    <div
                        v-for="(notification, index) in notifications.data"
                        :key="notification.id"
                        :style="{ animationDelay: `${index * 50}ms` }"
                        :class="[
                            'group bg-white dark:bg-gray-800 rounded-2xl shadow-sm border p-5 cursor-pointer transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5',
                            !notification.is_read
                                ? 'border-blue-200 dark:border-blue-800 bg-gradient-to-r from-blue-50/50 to-indigo-50/30 dark:from-blue-900/20 dark:to-indigo-900/10'
                                : 'border-gray-100 dark:border-gray-700 hover:border-gray-200 dark:hover:border-gray-600'
                        ]"
                        @click="handleNotificationClick(notification)"
                    >
                        <div class="flex items-start gap-4">
                            <!-- Icon with pulse animation for unread -->
                            <div class="relative flex-shrink-0">
                                <div :class="['w-12 h-12 rounded-xl flex items-center justify-center transition-transform group-hover:scale-110', getTypeBg(notification.type)]">
                                    <svg class="w-6 h-6" :class="getTypeIconColor(notification.type)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getTypeIconPath(notification.type)" />
                                    </svg>
                                </div>
                                <span
                                    v-if="!notification.is_read"
                                    class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-blue-500 rounded-full border-2 border-white dark:border-gray-800 animate-pulse"
                                ></span>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center flex-wrap gap-2 mb-2">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold',
                                            getTypeBadgeClass(notification.type)
                                        ]"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="getTypeDotColor(notification.type)"></span>
                                        {{ getTypeLabel(notification.type) }}
                                    </span>
                                    <span v-if="!notification.is_read" class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                        {{ t('notifications.new') }}
                                    </span>
                                </div>

                                <h4 class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {{ notification.title }}
                                </h4>
                                <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-400 line-clamp-2 leading-relaxed">
                                    {{ notification.message }}
                                </p>

                                <div class="mt-3 flex items-center justify-between">
                                    <div class="flex items-center text-xs text-gray-400 dark:text-gray-500">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ formatTime(notification.created_at) }}
                                    </div>

                                    <div v-if="notification.action_url" class="flex items-center text-xs text-blue-600 dark:text-blue-400 font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span>{{ t('notifications.view') }}</span>
                                        <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                    @click.stop="deleteNotification(notification.id)"
                                    class="p-2 text-gray-400 hover:text-red-500 dark:hover:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-all"
                                    :title="t('notifications.delete')"
                                >
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </TransitionGroup>

                <!-- Empty State -->
                <div
                    v-if="notifications.data.length === 0"
                    class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 py-16 px-8"
                >
                    <div class="absolute inset-0 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-900"></div>
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-blue-100/50 dark:bg-blue-900/20 rounded-full blur-3xl"></div>

                    <div class="relative text-center">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/50 dark:to-indigo-900/50 rounded-2xl mb-6">
                            <svg class="w-10 h-10 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">{{ t('notifications.empty') }}</h3>
                        <p class="text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                            {{ t('notifications.empty_desc') }}
                        </p>

                        <div v-if="localFilters.type !== 'all' || localFilters.status !== 'all'" class="mt-6">
                            <button
                                @click="clearFilters"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition-colors"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ t('notifications.clear_filter') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="notifications.last_page > 1" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 px-5 py-4">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ t('notifications.pagination.showing', { total: notifications.total, from: notifications.from, to: notifications.to }) }}
                    </p>
                    <div class="flex items-center space-x-2">
                        <button
                            @click="goToPage(notifications.current_page - 1)"
                            :disabled="!notifications.prev_page_url"
                            :class="[
                                'inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium transition-all',
                                notifications.prev_page_url
                                    ? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                    : 'bg-gray-50 dark:bg-gray-800 text-gray-300 dark:text-gray-600 cursor-not-allowed'
                            ]"
                        >
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            {{ t('notifications.pagination.previous') }}
                        </button>

                        <div class="hidden sm:flex items-center space-x-1">
                            <span class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ notifications.current_page }} / {{ notifications.last_page }}
                            </span>
                        </div>

                        <button
                            @click="goToPage(notifications.current_page + 1)"
                            :disabled="!notifications.next_page_url"
                            :class="[
                                'inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium transition-all',
                                notifications.next_page_url
                                    ? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                    : 'bg-gray-50 dark:bg-gray-800 text-gray-300 dark:text-gray-600 cursor-not-allowed'
                            ]"
                        >
                            {{ t('notifications.pagination.next') }}
                            <svg class="w-4 h-4 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import axios from 'axios';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    notifications: Object,
    stats: Object,
    filters: Object,
});

const localFilters = reactive({ ...props.filters });

// Calculate today's notifications
const todayCount = computed(() => {
    const today = new Date().toDateString();
    return props.notifications.data.filter(n => {
        return new Date(n.created_at).toDateString() === today;
    }).length;
});

function clearFilters() {
    localFilters.type = 'all';
    localFilters.status = 'all';
    applyFilters();
}

function applyFilters() {
    router.get('/business/notifications', {
        type: localFilters.type !== 'all' ? localFilters.type : undefined,
        status: localFilters.status !== 'all' ? localFilters.status : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function goToPage(page) {
    router.get('/business/notifications', {
        page,
        type: localFilters.type !== 'all' ? localFilters.type : undefined,
        status: localFilters.status !== 'all' ? localFilters.status : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

async function markAllAsRead() {
    try {
        await axios.post('/business/notifications/mark-all-read');
        router.reload();
    } catch (error) {
        console.error('Failed to mark all as read:', error);
    }
}

async function handleNotificationClick(notification) {
    if (!notification.is_read) {
        try {
            await axios.post(`/business/notifications/${notification.id}/read`);
            notification.is_read = true;
            notification.read_at = new Date().toISOString();
        } catch (error) {
            console.error('Failed to mark as read:', error);
        }
    }

    if (notification.action_url) {
        router.visit(notification.action_url);
    }
}

async function deleteNotification(id) {
    if (!confirm(t('notifications.delete_confirm'))) return;

    try {
        await axios.delete(`/business/notifications/${id}`);
        router.reload();
    } catch (error) {
        console.error('Failed to delete:', error);
    }
}

function formatTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return t('notifications.time.just_now');
    if (diffMins < 60) return t('notifications.time.minutes_ago', { count: diffMins });
    if (diffHours < 24) return t('notifications.time.hours_ago', { count: diffHours });
    if (diffDays === 1) return t('notifications.time.yesterday');
    if (diffDays < 7) return t('notifications.time.days_ago', { count: diffDays });

    return date.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'long', year: 'numeric' });
}

function getTypeIconPath(type) {
    const icons = {
        alert: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        insight: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
        report: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        system: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        celebration: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
        update: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
        announcement: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
    };
    return icons[type] || icons.system;
}

function getTypeBg(type) {
    const bgs = {
        alert: 'bg-red-100 dark:bg-red-900/40',
        insight: 'bg-amber-100 dark:bg-amber-900/40',
        report: 'bg-purple-100 dark:bg-purple-900/40',
        system: 'bg-slate-100 dark:bg-slate-700',
        celebration: 'bg-yellow-100 dark:bg-yellow-900/40',
        update: 'bg-emerald-100 dark:bg-emerald-900/40',
        announcement: 'bg-indigo-100 dark:bg-indigo-900/40',
    };
    return bgs[type] || 'bg-slate-100 dark:bg-slate-700';
}

function getTypeIconColor(type) {
    const colors = {
        alert: 'text-red-600 dark:text-red-400',
        insight: 'text-amber-600 dark:text-amber-400',
        report: 'text-purple-600 dark:text-purple-400',
        system: 'text-slate-600 dark:text-slate-400',
        celebration: 'text-yellow-600 dark:text-yellow-400',
        update: 'text-emerald-600 dark:text-emerald-400',
        announcement: 'text-indigo-600 dark:text-indigo-400',
    };
    return colors[type] || 'text-slate-600 dark:text-slate-400';
}

function getTypeLabel(type) {
    const typeKey = `notifications.type.${type}`;
    return t(typeKey) || type;
}

function getTypeBadgeClass(type) {
    const classes = {
        alert: 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300',
        insight: 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        report: 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
        system: 'bg-slate-50 text-slate-700 dark:bg-slate-700/50 dark:text-slate-300',
        celebration: 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
        update: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        announcement: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
    };
    return classes[type] || 'bg-slate-50 text-slate-700';
}

function getTypeDotColor(type) {
    const colors = {
        alert: 'bg-red-500',
        insight: 'bg-amber-500',
        report: 'bg-purple-500',
        system: 'bg-slate-500',
        celebration: 'bg-yellow-500',
        update: 'bg-emerald-500',
        announcement: 'bg-indigo-500',
    };
    return colors[type] || 'bg-slate-500';
}
</script>

<style scoped>
.notification-list-enter-active {
    animation: slideIn 0.3s ease-out forwards;
}

.notification-list-leave-active {
    animation: slideOut 0.2s ease-in forwards;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideOut {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateX(20px);
    }
}

.bg-grid-white\/10 {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32' width='32' height='32' fill='none' stroke='rgb(255 255 255 / 0.1)'%3e%3cpath d='M0 .5H31.5V32'/%3e%3c/svg%3e");
}
</style>
