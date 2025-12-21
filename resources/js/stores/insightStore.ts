import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

interface Insight {
    id: string;
    type: 'trend' | 'anomaly' | 'recommendation' | 'opportunity' | 'warning' | 'celebration';
    category: string | null;
    priority: 'critical' | 'high' | 'medium' | 'low';
    title: string;
    summary: string;
    details: Record<string, any> | null;
    recommendations: string[] | null;
    confidence_score: number;
    is_viewed: boolean;
    is_acted: boolean;
    action_taken: string | null;
    expires_at: string | null;
    created_at: string;
}

interface InsightStats {
    total: number;
    active: number;
    by_type: Record<string, number>;
}

export const useInsightStore = defineStore('insights', () => {
    // State
    const insights = ref<Insight[]>([]);
    const activeInsights = ref<Insight[]>([]);
    const stats = ref<InsightStats | null>(null);
    const isLoading = ref(false);
    const isRegenerating = ref(false);
    const error = ref<string | null>(null);

    // Pagination
    const currentPage = ref(1);
    const totalPages = ref(1);

    // Filters
    const filters = ref({
        type: 'all',
        category: 'all',
        active_only: true,
    });

    // Computed
    const unviewedCount = computed(() =>
        activeInsights.value.filter(i => !i.is_viewed).length
    );

    const highPriorityInsights = computed(() =>
        activeInsights.value.filter(i => ['critical', 'high'].includes(i.priority))
    );

    const insightsByType = computed(() => {
        const grouped: Record<string, Insight[]> = {};
        activeInsights.value.forEach(insight => {
            if (!grouped[insight.type]) {
                grouped[insight.type] = [];
            }
            grouped[insight.type].push(insight);
        });
        return grouped;
    });

    const recommendations = computed(() =>
        activeInsights.value.filter(i => i.type === 'recommendation')
    );

    const celebrations = computed(() =>
        activeInsights.value.filter(i => i.type === 'celebration')
    );

    // Actions
    async function fetchInsights(page: number = 1) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/business/insights', {
                params: {
                    page,
                    type: filters.value.type !== 'all' ? filters.value.type : undefined,
                    category: filters.value.category !== 'all' ? filters.value.category : undefined,
                    active_only: filters.value.active_only,
                }
            });

            insights.value = response.data.insights.data;
            currentPage.value = response.data.insights.current_page;
            totalPages.value = response.data.insights.last_page;
            stats.value = response.data.stats;
        } catch (e: any) {
            error.value = e.response?.data?.message || 'Tavsiyalarni yuklashda xatolik';
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchActiveInsights() {
        try {
            const response = await axios.get('/business/insights/active');
            activeInsights.value = response.data.insights;
        } catch (e: any) {
            console.error('Active insights fetch error:', e);
        }
    }

    async function regenerateInsights() {
        isRegenerating.value = true;

        try {
            const response = await axios.post('/business/insights/regenerate');
            activeInsights.value = [...response.data.insights, ...activeInsights.value];
            return response.data;
        } catch (e: any) {
            throw e;
        } finally {
            isRegenerating.value = false;
        }
    }

    async function markAsViewed(insightId: string) {
        try {
            await axios.post(`/business/insights/${insightId}/viewed`);
            updateInsightInList(insightId, { is_viewed: true });
        } catch (e: any) {
            console.error('Mark viewed error:', e);
        }
    }

    async function markAsActed(insightId: string, actionTaken?: string) {
        try {
            const response = await axios.post(`/business/insights/${insightId}/acted`, {
                action_taken: actionTaken
            });
            const index = insights.value.findIndex(i => i.id === insightId);
            if (index !== -1) {
                insights.value[index] = response.data.insight;
            }
            return response.data.insight;
        } catch (e: any) {
            throw e;
        }
    }

    async function dismissInsight(insightId: string) {
        try {
            await axios.post(`/business/insights/${insightId}/dismiss`);
            removeInsightFromList(insightId);
            removeFromActiveInsights(insightId);
        } catch (e: any) {
            throw e;
        }
    }

    async function fetchByCategory(category: string) {
        try {
            const response = await axios.get('/business/insights/category', {
                params: { category }
            });
            return response.data.insights;
        } catch (e: any) {
            console.error('Category insights fetch error:', e);
            return [];
        }
    }

    function updateInsightInList(insightId: string, updates: Partial<Insight>) {
        const index = insights.value.findIndex(i => i.id === insightId);
        if (index !== -1) {
            insights.value[index] = { ...insights.value[index], ...updates };
        }
        const activeIndex = activeInsights.value.findIndex(i => i.id === insightId);
        if (activeIndex !== -1) {
            activeInsights.value[activeIndex] = { ...activeInsights.value[activeIndex], ...updates };
        }
    }

    function removeInsightFromList(insightId: string) {
        insights.value = insights.value.filter(i => i.id !== insightId);
    }

    function removeFromActiveInsights(insightId: string) {
        activeInsights.value = activeInsights.value.filter(i => i.id !== insightId);
    }

    function setFilters(newFilters: { type?: string; category?: string; active_only?: boolean }) {
        filters.value = { ...filters.value, ...newFilters };
    }

    // Handle real-time insight
    function handleNewInsight(insight: Insight) {
        activeInsights.value = [insight, ...activeInsights.value];
        if (filters.value.active_only) {
            insights.value = [insight, ...insights.value];
        }
    }

    function getTypeIcon(type: string): string {
        const icons: Record<string, string> = {
            trend: 'chart-line',
            anomaly: 'exclamation-triangle',
            recommendation: 'lightbulb',
            opportunity: 'rocket',
            warning: 'shield-exclamation',
            celebration: 'trophy',
        };
        return icons[type] || 'info-circle';
    }

    function getTypeColor(type: string): string {
        const colors: Record<string, string> = {
            trend: 'blue',
            anomaly: 'orange',
            recommendation: 'purple',
            opportunity: 'green',
            warning: 'red',
            celebration: 'yellow',
        };
        return colors[type] || 'gray';
    }

    return {
        // State
        insights,
        activeInsights,
        stats,
        isLoading,
        isRegenerating,
        error,
        currentPage,
        totalPages,
        filters,

        // Computed
        unviewedCount,
        highPriorityInsights,
        insightsByType,
        recommendations,
        celebrations,

        // Actions
        fetchInsights,
        fetchActiveInsights,
        regenerateInsights,
        markAsViewed,
        markAsActed,
        dismissInsight,
        fetchByCategory,
        setFilters,
        handleNewInsight,
        getTypeIcon,
        getTypeColor,
    };
});
