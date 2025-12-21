import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

interface Notification {
    id: string;
    type: 'alert' | 'insight' | 'report' | 'system' | 'celebration';
    channel: string;
    title: string;
    message: string;
    action_url: string | null;
    action_text: string | null;
    priority: 'critical' | 'high' | 'medium' | 'low';
    read_at: string | null;
    clicked_at: string | null;
    created_at: string;
}

export const useNotificationStore = defineStore('notifications', () => {
    // State
    const notifications = ref<Notification[]>([]);
    const unreadNotifications = ref<Notification[]>([]);
    const unreadCount = ref(0);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    // Pagination
    const currentPage = ref(1);
    const totalPages = ref(1);

    // Filters
    const filters = ref({
        type: 'all',
        status: 'all',
    });

    // Computed
    const hasUnread = computed(() => unreadCount.value > 0);

    const highPriorityUnread = computed(() =>
        unreadNotifications.value.filter(n => ['critical', 'high'].includes(n.priority))
    );

    const groupedByDate = computed(() => {
        const grouped: Record<string, Notification[]> = {};

        notifications.value.forEach(notification => {
            const date = new Date(notification.created_at).toLocaleDateString('uz-UZ');
            if (!grouped[date]) {
                grouped[date] = [];
            }
            grouped[date].push(notification);
        });

        return grouped;
    });

    // Actions
    async function fetchNotifications(page: number = 1) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/business/notifications', {
                params: {
                    page,
                    type: filters.value.type !== 'all' ? filters.value.type : undefined,
                    status: filters.value.status !== 'all' ? filters.value.status : undefined,
                }
            });

            notifications.value = response.data.notifications.data;
            currentPage.value = response.data.notifications.current_page;
            totalPages.value = response.data.notifications.last_page;
        } catch (e: any) {
            error.value = e.response?.data?.message || 'Bildirishnomalarni yuklashda xatolik';
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchUnread() {
        try {
            const response = await axios.get('/business/notifications/unread');
            unreadNotifications.value = response.data.notifications;
            unreadCount.value = response.data.count;
        } catch (e: any) {
            console.error('Unread notifications fetch error:', e);
        }
    }

    async function fetchCount() {
        try {
            const response = await axios.get('/business/notifications/count');
            unreadCount.value = response.data.count;
        } catch (e: any) {
            console.error('Notification count fetch error:', e);
        }
    }

    async function markAsRead(notificationId: string) {
        try {
            await axios.post(`/business/notifications/${notificationId}/read`);
            updateNotificationInList(notificationId, { read_at: new Date().toISOString() });
            removeFromUnread(notificationId);
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        } catch (e: any) {
            console.error('Mark read error:', e);
        }
    }

    async function markAsClicked(notificationId: string) {
        try {
            await axios.post(`/business/notifications/${notificationId}/clicked`);
            const now = new Date().toISOString();
            updateNotificationInList(notificationId, {
                read_at: now,
                clicked_at: now,
            });
            removeFromUnread(notificationId);
        } catch (e: any) {
            console.error('Mark clicked error:', e);
        }
    }

    async function markAllAsRead() {
        try {
            const response = await axios.post('/business/notifications/mark-all-read');
            // Update all notifications as read
            notifications.value = notifications.value.map(n => ({
                ...n,
                read_at: n.read_at || new Date().toISOString(),
            }));
            unreadNotifications.value = [];
            unreadCount.value = 0;
            return response.data.count;
        } catch (e: any) {
            throw e;
        }
    }

    async function deleteNotification(notificationId: string) {
        try {
            await axios.delete(`/business/notifications/${notificationId}`);
            removeNotificationFromList(notificationId);
            removeFromUnread(notificationId);
        } catch (e: any) {
            throw e;
        }
    }

    function updateNotificationInList(notificationId: string, updates: Partial<Notification>) {
        const index = notifications.value.findIndex(n => n.id === notificationId);
        if (index !== -1) {
            notifications.value[index] = { ...notifications.value[index], ...updates };
        }
    }

    function removeNotificationFromList(notificationId: string) {
        notifications.value = notifications.value.filter(n => n.id !== notificationId);
    }

    function removeFromUnread(notificationId: string) {
        unreadNotifications.value = unreadNotifications.value.filter(n => n.id !== notificationId);
    }

    function setFilters(newFilters: { type?: string; status?: string }) {
        filters.value = { ...filters.value, ...newFilters };
    }

    // Handle real-time notification
    function handleNewNotification(notification: Notification) {
        unreadNotifications.value = [notification, ...unreadNotifications.value];
        notifications.value = [notification, ...notifications.value];
        unreadCount.value++;
    }

    function getTypeIcon(type: string): string {
        const icons: Record<string, string> = {
            alert: 'bell-alert',
            insight: 'lightbulb',
            report: 'document-chart-bar',
            system: 'cog',
            celebration: 'trophy',
        };
        return icons[type] || 'bell';
    }

    function getTypeColor(type: string): string {
        const colors: Record<string, string> = {
            alert: 'red',
            insight: 'blue',
            report: 'purple',
            system: 'gray',
            celebration: 'yellow',
        };
        return colors[type] || 'gray';
    }

    function getPriorityColor(priority: string): string {
        const colors: Record<string, string> = {
            critical: 'red',
            high: 'orange',
            medium: 'yellow',
            low: 'gray',
        };
        return colors[priority] || 'gray';
    }

    return {
        // State
        notifications,
        unreadNotifications,
        unreadCount,
        isLoading,
        error,
        currentPage,
        totalPages,
        filters,

        // Computed
        hasUnread,
        highPriorityUnread,
        groupedByDate,

        // Actions
        fetchNotifications,
        fetchUnread,
        fetchCount,
        markAsRead,
        markAsClicked,
        markAllAsRead,
        deleteNotification,
        setFilters,
        handleNewNotification,
        getTypeIcon,
        getTypeColor,
        getPriorityColor,
    };
});
