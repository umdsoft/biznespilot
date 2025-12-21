import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

interface Alert {
    id: string;
    type: string;
    severity: 'critical' | 'high' | 'medium' | 'low' | 'info';
    title: string;
    message: string;
    metric: string | null;
    current_value: number | null;
    threshold_value: number | null;
    status: 'active' | 'acknowledged' | 'resolved' | 'snoozed' | 'dismissed';
    triggered_at: string;
    acknowledged_at: string | null;
    resolved_at: string | null;
    snoozed_until: string | null;
}

interface AlertRule {
    id: string;
    name: string;
    type: string;
    metric: string;
    condition: string;
    threshold_value: number;
    severity: string;
    is_active: boolean;
    notification_channels: string[];
}

interface AlertStats {
    total: number;
    active: number;
    acknowledged: number;
    resolved: number;
    dismissed: number;
    by_severity: Record<string, number>;
    avg_resolution_time: number | null;
}

export const useAlertStore = defineStore('alerts', () => {
    // State
    const alerts = ref<Alert[]>([]);
    const activeAlerts = ref<Alert[]>([]);
    const rules = ref<AlertRule[]>([]);
    const stats = ref<AlertStats | null>(null);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    // Pagination
    const currentPage = ref(1);
    const totalPages = ref(1);
    const perPage = ref(20);

    // Filters
    const filters = ref({
        status: 'all',
        severity: 'all',
    });

    // Computed
    const criticalAlerts = computed(() =>
        activeAlerts.value.filter(a => a.severity === 'critical')
    );

    const highPriorityAlerts = computed(() =>
        activeAlerts.value.filter(a => ['critical', 'high'].includes(a.severity))
    );

    const unreadCount = computed(() => activeAlerts.value.length);

    // Actions
    async function fetchAlerts(page: number = 1) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/business/alerts', {
                params: {
                    page,
                    status: filters.value.status !== 'all' ? filters.value.status : undefined,
                    severity: filters.value.severity !== 'all' ? filters.value.severity : undefined,
                }
            });

            alerts.value = response.data.alerts.data;
            currentPage.value = response.data.alerts.current_page;
            totalPages.value = response.data.alerts.last_page;
            stats.value = response.data.stats;
        } catch (e: any) {
            error.value = e.response?.data?.message || 'Ogohlantirishlarni yuklashda xatolik';
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchActiveAlerts() {
        try {
            const response = await axios.get('/business/alerts/active');
            activeAlerts.value = response.data.alerts;
        } catch (e: any) {
            console.error('Active alerts fetch error:', e);
        }
    }

    async function fetchRules() {
        try {
            const response = await axios.get('/business/alerts/rules');
            rules.value = response.data.rules;
        } catch (e: any) {
            console.error('Rules fetch error:', e);
        }
    }

    async function acknowledgeAlert(alertId: string) {
        try {
            const response = await axios.post(`/business/alerts/${alertId}/acknowledge`);
            updateAlertInList(response.data.alert);
            return response.data.alert;
        } catch (e: any) {
            throw e;
        }
    }

    async function resolveAlert(alertId: string, resolution?: string) {
        try {
            const response = await axios.post(`/business/alerts/${alertId}/resolve`, {
                resolution
            });
            updateAlertInList(response.data.alert);
            removeFromActiveAlerts(alertId);
            return response.data.alert;
        } catch (e: any) {
            throw e;
        }
    }

    async function snoozeAlert(alertId: string, hours: number = 24) {
        try {
            const response = await axios.post(`/business/alerts/${alertId}/snooze`, {
                hours
            });
            updateAlertInList(response.data.alert);
            removeFromActiveAlerts(alertId);
            return response.data.alert;
        } catch (e: any) {
            throw e;
        }
    }

    async function dismissAlert(alertId: string) {
        try {
            await axios.post(`/business/alerts/${alertId}/dismiss`);
            removeFromActiveAlerts(alertId);
            removeAlertFromList(alertId);
        } catch (e: any) {
            throw e;
        }
    }

    async function updateRule(ruleId: string, data: Partial<AlertRule>) {
        try {
            const response = await axios.put(`/business/alerts/rules/${ruleId}`, data);
            const index = rules.value.findIndex(r => r.id === ruleId);
            if (index !== -1) {
                rules.value[index] = response.data.rule;
            }
            return response.data.rule;
        } catch (e: any) {
            throw e;
        }
    }

    async function createRule(data: Partial<AlertRule>) {
        try {
            const response = await axios.post('/business/alerts/rules', data);
            rules.value.push(response.data.rule);
            return response.data.rule;
        } catch (e: any) {
            throw e;
        }
    }

    async function deleteRule(ruleId: string) {
        try {
            await axios.delete(`/business/alerts/rules/${ruleId}`);
            rules.value = rules.value.filter(r => r.id !== ruleId);
        } catch (e: any) {
            throw e;
        }
    }

    function updateAlertInList(alert: Alert) {
        const index = alerts.value.findIndex(a => a.id === alert.id);
        if (index !== -1) {
            alerts.value[index] = alert;
        }
    }

    function removeAlertFromList(alertId: string) {
        alerts.value = alerts.value.filter(a => a.id !== alertId);
    }

    function removeFromActiveAlerts(alertId: string) {
        activeAlerts.value = activeAlerts.value.filter(a => a.id !== alertId);
    }

    function setFilters(newFilters: { status?: string; severity?: string }) {
        filters.value = { ...filters.value, ...newFilters };
    }

    // Handle real-time alert
    function handleNewAlert(alert: Alert) {
        activeAlerts.value = [alert, ...activeAlerts.value];
        if (filters.value.status === 'all' || filters.value.status === 'active') {
            alerts.value = [alert, ...alerts.value];
        }
    }

    return {
        // State
        alerts,
        activeAlerts,
        rules,
        stats,
        isLoading,
        error,
        currentPage,
        totalPages,
        perPage,
        filters,

        // Computed
        criticalAlerts,
        highPriorityAlerts,
        unreadCount,

        // Actions
        fetchAlerts,
        fetchActiveAlerts,
        fetchRules,
        acknowledgeAlert,
        resolveAlert,
        snoozeAlert,
        dismissAlert,
        updateRule,
        createRule,
        deleteRule,
        setFilters,
        handleNewAlert,
    };
});
