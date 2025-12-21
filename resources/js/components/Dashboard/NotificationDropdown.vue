<template>
    <div class="relative">
        <button
            @click="isOpen = !isOpen"
            class="relative p-2 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-full"
        >
            <BellIcon class="w-6 h-6" />
            <span
                v-if="unreadCount > 0"
                class="absolute top-0 right-0 block h-4 w-4 transform translate-x-1 -translate-y-1 rounded-full bg-red-500 text-white text-xs font-bold flex items-center justify-center"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown -->
        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                v-click-outside="() => isOpen = false"
                class="absolute right-0 mt-2 w-80 md:w-96 bg-white dark:bg-gray-800 rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Bildirishnomalar</h3>
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllAsRead"
                        class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400"
                    >
                        Hammasini o'qilgan deb belgilash
                    </button>
                </div>

                <!-- Notifications list -->
                <div class="max-h-96 overflow-y-auto">
                    <template v-if="notifications.length > 0">
                        <div
                            v-for="notification in notifications"
                            :key="notification.id"
                            :class="[
                                'px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0',
                                !notification.read_at ? 'bg-blue-50 dark:bg-blue-900/20' : ''
                            ]"
                            @click="handleClick(notification)"
                        >
                            <div class="flex items-start space-x-3">
                                <div :class="['p-2 rounded-lg', getTypeBg(notification.type)]">
                                    <component
                                        :is="getTypeIcon(notification.type)"
                                        :class="['w-4 h-4', getTypeIconColor(notification.type)]"
                                    />
                                </div>

                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ notification.title }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                                        {{ notification.message }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-400">
                                        {{ formatTime(notification.created_at) }}
                                    </p>
                                </div>

                                <span
                                    v-if="!notification.read_at"
                                    class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0"
                                />
                            </div>
                        </div>
                    </template>

                    <div
                        v-else
                        class="px-4 py-8 text-center text-gray-500 dark:text-gray-400"
                    >
                        <BellSlashIcon class="w-8 h-8 mx-auto mb-2 opacity-50" />
                        <p class="text-sm">Bildirishnomalar yo'q</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    <Link
                        href="/business/notifications"
                        class="block text-center text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400"
                    >
                        Barcha bildirishnomalarni ko'rish
                    </Link>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useNotificationStore } from '@/stores/notificationStore';
import {
    BellIcon,
    BellSlashIcon,
    BellAlertIcon,
    LightBulbIcon,
    DocumentChartBarIcon,
    CogIcon,
    TrophyIcon,
} from '@heroicons/vue/24/outline';
import { formatDistanceToNow } from 'date-fns';
import { uz } from 'date-fns/locale';

interface Notification {
    id: string;
    type: string;
    title: string;
    message: string;
    action_url: string | null;
    read_at: string | null;
    created_at: string;
}

const notificationStore = useNotificationStore();

const isOpen = ref(false);
const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);

onMounted(async () => {
    await fetchNotifications();
    // Start polling for new notifications
    const interval = setInterval(fetchNotifications, 30000);
    onUnmounted(() => clearInterval(interval));
});

async function fetchNotifications() {
    await notificationStore.fetchUnread();
    notifications.value = notificationStore.unreadNotifications;
    unreadCount.value = notificationStore.unreadCount;
}

async function markAllAsRead() {
    await notificationStore.markAllAsRead();
    notifications.value = notifications.value.map(n => ({
        ...n,
        read_at: new Date().toISOString(),
    }));
    unreadCount.value = 0;
}

async function handleClick(notification: Notification) {
    if (!notification.read_at) {
        await notificationStore.markAsClicked(notification.id);
    }

    if (notification.action_url) {
        isOpen.value = false;
        router.visit(notification.action_url);
    }
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
    return icons[type] || BellIcon;
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

// Click outside directive
const vClickOutside = {
    mounted(el: HTMLElement, binding: any) {
        el._clickOutside = (event: MouseEvent) => {
            if (!(el === event.target || el.contains(event.target as Node))) {
                binding.value();
            }
        };
        document.addEventListener('click', el._clickOutside);
    },
    unmounted(el: HTMLElement) {
        document.removeEventListener('click', el._clickOutside);
    },
};
</script>
