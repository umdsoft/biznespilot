import axios from 'axios';

/**
 * Sales va Marketing metrikalar actionlari
 */
export function createMetricsActions({ state, runAsync, runFetch }) {
    const { progress } = state;

    // ── Sales ──
    async function fetchSalesMetrics() {
        return runFetch(async () => {
            const response = await axios.get('/api/v1/onboarding/sales-metrics');
            return response.data;
        });
    }

    async function updateSalesMetrics(data) {
        return runAsync(async () => {
            const response = await axios.put('/api/v1/onboarding/sales-metrics', data);
            progress.value = response.data.data.progress;
            return response.data;
        });
    }

    async function fetchSalesMetricsHistory() {
        return runFetch(async () => {
            const response = await axios.get('/api/v1/onboarding/sales-metrics/history');
            return response.data;
        });
    }

    // ── Marketing ──
    async function fetchMarketingMetrics() {
        return runFetch(async () => {
            const response = await axios.get('/api/v1/onboarding/marketing-metrics');
            return response.data;
        });
    }

    async function updateMarketingMetrics(data) {
        return runAsync(async () => {
            const response = await axios.put('/api/v1/onboarding/marketing-metrics', data);
            progress.value = response.data.data.progress;
            return response.data;
        });
    }

    async function fetchMarketingMetricsHistory() {
        return runFetch(async () => {
            const response = await axios.get('/api/v1/onboarding/marketing-metrics/history');
            return response.data;
        });
    }

    return {
        fetchSalesMetrics,
        updateSalesMetrics,
        fetchSalesMetricsHistory,
        fetchMarketingMetrics,
        updateMarketingMetrics,
        fetchMarketingMetricsHistory,
    };
}
