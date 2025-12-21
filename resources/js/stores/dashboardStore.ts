import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

interface KPI {
    label: string;
    value: number;
    formatted: string;
    change_day: { value: number; direction: string; is_positive: boolean } | null;
    change_week: { value: number; direction: string; is_positive: boolean } | null;
    icon: string;
    color: string;
}

interface FunnelStage {
    stage: string;
    label: string;
    value: number;
}

interface TrendPoint {
    date: string;
    value: number;
}

interface DashboardData {
    kpis: Record<string, KPI>;
    health_score: number;
    funnel: FunnelStage[];
    alerts: any[];
    insights: any[];
    trends: {
        revenue: TrendPoint[];
        leads: TrendPoint[];
    };
    last_updated: string;
}

interface Widget {
    id: string;
    type: string;
    title: string;
    data_source: string;
    sort_order: number;
    is_visible: boolean;
    settings: Record<string, any>;
}

export const useDashboardStore = defineStore('dashboard', () => {
    // State
    const dashboardData = ref<DashboardData | null>(null);
    const widgets = ref<Widget[]>([]);
    const isLoading = ref(false);
    const isRefreshing = ref(false);
    const lastRefresh = ref<Date | null>(null);
    const error = ref<string | null>(null);

    // Computed
    const kpis = computed(() => dashboardData.value?.kpis || {});
    const healthScore = computed(() => dashboardData.value?.health_score || 0);
    const funnel = computed(() => dashboardData.value?.funnel || []);
    const alerts = computed(() => dashboardData.value?.alerts || []);
    const insights = computed(() => dashboardData.value?.insights || []);
    const trends = computed(() => dashboardData.value?.trends || { revenue: [], leads: [] });

    const healthScoreColor = computed(() => {
        const score = healthScore.value;
        if (score >= 80) return 'green';
        if (score >= 60) return 'yellow';
        if (score >= 40) return 'orange';
        return 'red';
    });

    const healthScoreLabel = computed(() => {
        const score = healthScore.value;
        if (score >= 80) return 'A\'lo';
        if (score >= 60) return 'Yaxshi';
        if (score >= 40) return 'O\'rtacha';
        return 'Yomon';
    });

    // Actions
    async function fetchDashboardData() {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/business/api/dashboard/data');
            dashboardData.value = response.data.data;
            lastRefresh.value = new Date();
        } catch (e: any) {
            error.value = e.response?.data?.message || 'Dashboard ma\'lumotlarini yuklashda xatolik';
            console.error('Dashboard fetch error:', e);
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchKPIs() {
        try {
            const response = await axios.get('/business/api/dashboard/kpis');
            if (dashboardData.value) {
                dashboardData.value.kpis = response.data.kpis;
                dashboardData.value.health_score = response.data.health_score;
            }
        } catch (e: any) {
            console.error('KPI fetch error:', e);
        }
    }

    async function fetchTrends(metric: string = 'revenue_total', days: number = 30) {
        try {
            const response = await axios.get('/business/api/dashboard/trends', {
                params: { metric, days }
            });
            return response.data.trends;
        } catch (e: any) {
            console.error('Trends fetch error:', e);
            return [];
        }
    }

    async function fetchFunnel() {
        try {
            const response = await axios.get('/business/api/dashboard/funnel');
            if (dashboardData.value) {
                dashboardData.value.funnel = response.data.funnel;
            }
            return response.data.funnel;
        } catch (e: any) {
            console.error('Funnel fetch error:', e);
            return [];
        }
    }

    async function fetchChannelComparison() {
        try {
            const response = await axios.get('/business/api/dashboard/channels');
            return response.data.channels;
        } catch (e: any) {
            console.error('Channel comparison fetch error:', e);
            return {};
        }
    }

    async function refreshDashboard() {
        isRefreshing.value = true;

        try {
            const response = await axios.post('/business/api/dashboard/refresh');
            dashboardData.value = response.data.data;
            lastRefresh.value = new Date();
        } catch (e: any) {
            error.value = e.response?.data?.message || 'Dashboard yangilashda xatolik';
        } finally {
            isRefreshing.value = false;
        }
    }

    async function updateWidgets(updatedWidgets: Widget[]) {
        try {
            await axios.post('/business/api/dashboard/widgets', {
                widgets: updatedWidgets.map(w => ({
                    id: w.id,
                    sort_order: w.sort_order,
                    is_visible: w.is_visible,
                    settings: w.settings,
                }))
            });
            widgets.value = updatedWidgets;
        } catch (e: any) {
            console.error('Widget update error:', e);
            throw e;
        }
    }

    function setWidgets(newWidgets: Widget[]) {
        widgets.value = newWidgets;
    }

    // Handle real-time updates
    function handleDashboardUpdate(data: any) {
        if (data.data) {
            dashboardData.value = { ...dashboardData.value, ...data.data };
        }
        lastRefresh.value = new Date(data.updated_at);
    }

    function handleAlertTriggered(alert: any) {
        if (dashboardData.value) {
            dashboardData.value.alerts = [alert, ...dashboardData.value.alerts.slice(0, 4)];
        }
    }

    function handleInsightGenerated(insight: any) {
        if (dashboardData.value) {
            dashboardData.value.insights = [insight, ...dashboardData.value.insights.slice(0, 4)];
        }
    }

    return {
        // State
        dashboardData,
        widgets,
        isLoading,
        isRefreshing,
        lastRefresh,
        error,

        // Computed
        kpis,
        healthScore,
        healthScoreColor,
        healthScoreLabel,
        funnel,
        alerts,
        insights,
        trends,

        // Actions
        fetchDashboardData,
        fetchKPIs,
        fetchTrends,
        fetchFunnel,
        fetchChannelComparison,
        refreshDashboard,
        updateWidgets,
        setWidgets,
        handleDashboardUpdate,
        handleAlertTriggered,
        handleInsightGenerated,
    };
});
