import { ref, computed } from 'vue';
import axios from 'axios';

/**
 * Composable for managing call analysis functionality
 * Used in Lead card to display calls and trigger AI analysis
 */
export function useCallAnalysis() {
    // State
    const calls = ref([]);
    const selectedCalls = ref([]);
    const loading = ref(false);
    const analyzing = ref(false);
    const error = ref(null);
    const pagination = ref({
        currentPage: 1,
        lastPage: 1,
        perPage: 20,
        total: 0
    });

    // Current analysis details
    const currentAnalysis = ref(null);
    const analysisModalOpen = ref(false);

    // Computed
    const selectedCount = computed(() => selectedCalls.value.length);

    const pendingCalls = computed(() =>
        calls.value.filter(c => c.analysis_status === 'pending' && c.can_be_analyzed)
    );

    const completedCalls = computed(() =>
        calls.value.filter(c => c.analysis_status === 'completed')
    );

    const analyzingCalls = computed(() =>
        calls.value.filter(c => ['queued', 'transcribing', 'analyzing'].includes(c.analysis_status))
    );

    // Estimated cost for selected calls (166 so'm per call average)
    const estimatedCost = computed(() => {
        const totalDuration = selectedCalls.value.reduce((sum, id) => {
            const call = calls.value.find(c => c.id === id);
            return sum + (call?.duration || 0);
        }, 0);

        // STT: ~40 so'm per minute, Analysis: ~100 so'm per call
        const sttCost = Math.ceil(totalDuration / 60) * 40;
        const analysisCost = selectedCalls.value.length * 100;

        return {
            total: sttCost + analysisCost,
            formatted: new Intl.NumberFormat('uz-UZ').format(sttCost + analysisCost) + ' so\'m'
        };
    });

    // Fetch calls for a lead
    async function fetchCalls(leadId, filters = {}) {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams({
                lead_id: leadId,
                per_page: filters.perPage || 20,
                ...filters
            });

            const response = await axios.get(`/api/v1/call-center/calls?${params}`);

            if (response.data.success) {
                calls.value = response.data.data;
                pagination.value = response.data.pagination;
            } else {
                throw new Error(response.data.error || 'Failed to fetch calls');
            }
        } catch (err) {
            error.value = err.response?.data?.error || err.message;
            console.error('Error fetching calls:', err);
        } finally {
            loading.value = false;
        }
    }

    // Fetch single call details
    async function fetchCall(callId) {
        try {
            const response = await axios.get(`/api/v1/call-center/calls/${callId}`);

            if (response.data.success) {
                return response.data.data;
            } else {
                throw new Error(response.data.error);
            }
        } catch (err) {
            error.value = err.response?.data?.error || err.message;
            return null;
        }
    }

    // Fetch analysis for a call
    async function fetchAnalysis(callId) {
        try {
            const response = await axios.get(`/api/v1/call-center/calls/${callId}/analysis`);

            if (response.data.success) {
                currentAnalysis.value = response.data.data;
                return response.data.data;
            } else {
                throw new Error(response.data.error);
            }
        } catch (err) {
            error.value = err.response?.data?.error || err.message;
            return null;
        }
    }

    // Analyze single call
    async function analyzeCall(callId) {
        analyzing.value = true;
        error.value = null;

        try {
            const response = await axios.post(`/api/v1/call-center/calls/${callId}/analyze`);

            if (response.data.success) {
                // Update call status in list
                const index = calls.value.findIndex(c => c.id === callId);
                if (index !== -1) {
                    calls.value[index].analysis_status = 'queued';
                    calls.value[index].analysis_status_label = 'Navbatda';
                }
                return response.data;
            } else {
                throw new Error(response.data.error);
            }
        } catch (err) {
            error.value = err.response?.data?.error || err.message;
            return null;
        } finally {
            analyzing.value = false;
        }
    }

    // Analyze multiple calls (bulk)
    async function analyzeBulk(callIds = null) {
        const ids = callIds || selectedCalls.value;

        if (ids.length === 0) {
            error.value = 'Qo\'ng\'iroq tanlanmagan';
            return null;
        }

        analyzing.value = true;
        error.value = null;

        try {
            const response = await axios.post('/api/v1/call-center/calls/analyze-bulk', {
                call_ids: ids
            });

            if (response.data.success) {
                // Update call statuses in list
                response.data.queued.forEach(({ id }) => {
                    const index = calls.value.findIndex(c => c.id === id);
                    if (index !== -1) {
                        calls.value[index].analysis_status = 'queued';
                        calls.value[index].analysis_status_label = 'Navbatda';
                    }
                });

                // Clear selection
                selectedCalls.value = [];

                return response.data;
            } else {
                throw new Error(response.data.error);
            }
        } catch (err) {
            error.value = err.response?.data?.error || err.message;
            return null;
        } finally {
            analyzing.value = false;
        }
    }

    // Estimate cost for calls
    async function estimateCostApi(callIds) {
        try {
            const response = await axios.post('/api/v1/call-center/calls/estimate-cost', {
                call_ids: callIds
            });

            if (response.data.success) {
                return response.data.data;
            }
        } catch (err) {
            console.error('Error estimating cost:', err);
        }
        return null;
    }

    // Get analysis stats
    async function fetchStats(dateFrom = null, dateTo = null) {
        try {
            const params = new URLSearchParams();
            if (dateFrom) params.append('date_from', dateFrom);
            if (dateTo) params.append('date_to', dateTo);

            const response = await axios.get(`/api/v1/call-center/stats?${params}`);

            if (response.data.success) {
                return response.data.data;
            }
        } catch (err) {
            console.error('Error fetching stats:', err);
        }
        return null;
    }

    // Toggle call selection
    function toggleSelection(callId) {
        const index = selectedCalls.value.indexOf(callId);
        if (index === -1) {
            selectedCalls.value.push(callId);
        } else {
            selectedCalls.value.splice(index, 1);
        }
    }

    // Select all analyzable calls
    function selectAllAnalyzable() {
        selectedCalls.value = pendingCalls.value.map(c => c.id);
    }

    // Clear selection
    function clearSelection() {
        selectedCalls.value = [];
    }

    // Open analysis modal
    async function openAnalysisModal(callId) {
        const analysis = await fetchAnalysis(callId);
        if (analysis) {
            analysisModalOpen.value = true;
        }
    }

    // Close analysis modal
    function closeAnalysisModal() {
        analysisModalOpen.value = false;
        currentAnalysis.value = null;
    }

    // Format duration (mm:ss)
    function formatDuration(seconds) {
        if (!seconds) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    // Get status color
    function getStatusColor(status) {
        const colors = {
            pending: 'gray',
            queued: 'blue',
            transcribing: 'blue',
            analyzing: 'blue',
            completed: 'green',
            failed: 'red'
        };
        return colors[status] || 'gray';
    }

    // Get score color
    function getScoreColor(score) {
        if (score === null || score === undefined) return 'gray';
        if (score >= 80) return 'green';
        if (score >= 60) return 'yellow';
        if (score >= 40) return 'orange';
        return 'red';
    }

    return {
        // State
        calls,
        selectedCalls,
        loading,
        analyzing,
        error,
        pagination,
        currentAnalysis,
        analysisModalOpen,

        // Computed
        selectedCount,
        pendingCalls,
        completedCalls,
        analyzingCalls,
        estimatedCost,

        // Methods
        fetchCalls,
        fetchCall,
        fetchAnalysis,
        analyzeCall,
        analyzeBulk,
        estimateCostApi,
        fetchStats,
        toggleSelection,
        selectAllAnalyzable,
        clearSelection,
        openAnalysisModal,
        closeAnalysisModal,
        formatDuration,
        getStatusColor,
        getScoreColor
    };
}
