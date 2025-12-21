import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useDiagnosticStore = defineStore('diagnostic', () => {
  // State
  const currentDiagnostic = ref(null);
  const latestDiagnostic = ref(null);
  const history = ref([]);
  const questions = ref([]);
  const kpis = ref(null);
  const loading = ref(false);
  const processing = ref(false);
  const error = ref(null);
  const eligibility = ref(null);

  // Processing state
  const processingStep = ref(null);
  const processingSteps = ref([
    { code: 'aggregating_data', label: "Ma'lumotlar yig'ilmoqda", icon: 'database' },
    { code: 'calculating_kpis', label: 'KPI lar hisoblanmoqda', icon: 'calculator' },
    { code: 'comparing_benchmarks', label: 'Benchmark bilan taqqoslanmoqda', icon: 'chart-bar' },
    { code: 'calculating_scores', label: 'Ballar hisoblanmoqda', icon: 'star' },
    { code: 'ai_analysis', label: 'AI tahlil qilmoqda', icon: 'sparkles' },
    { code: 'generating_recommendations', label: 'Tavsiyalar yaratilmoqda', icon: 'lightbulb' },
    { code: 'saving_results', label: 'Natijalar saqlanmoqda', icon: 'check' },
  ]);

  // Computed
  const hasLatestDiagnostic = computed(() => !!latestDiagnostic.value);
  const canStartDiagnostic = computed(() => eligibility.value?.can_start || false);
  const eligibilityReasons = computed(() => eligibility.value?.reasons || []);

  const overallScore = computed(() => currentDiagnostic.value?.overall_score || latestDiagnostic.value?.overall_score || 0);
  const categoryScores = computed(() => currentDiagnostic.value?.category_scores || latestDiagnostic.value?.category_scores || {});
  const swotAnalysis = computed(() => currentDiagnostic.value?.swot || latestDiagnostic.value?.swot || null);
  const recommendations = computed(() => currentDiagnostic.value?.recommendations || latestDiagnostic.value?.recommendations || []);
  const aiInsights = computed(() => currentDiagnostic.value?.ai_insights || latestDiagnostic.value?.ai_insights || '');
  const benchmarkSummary = computed(() => currentDiagnostic.value?.benchmark_summary || latestDiagnostic.value?.benchmark_summary || null);
  const trendData = computed(() => currentDiagnostic.value?.trend_data || latestDiagnostic.value?.trend_data || null);

  const currentProcessingStepIndex = computed(() => {
    if (!processingStep.value) return -1;
    return processingSteps.value.findIndex(s => s.code === processingStep.value);
  });

  const isProcessing = computed(() => {
    return processing.value || currentDiagnostic.value?.status === 'processing';
  });

  const answeredQuestionsCount = computed(() => {
    return questions.value.filter(q => q.answer).length;
  });

  // Score status helpers
  const getScoreStatus = (score) => {
    if (score >= 80) return { label: 'Ajoyib', color: 'blue' };
    if (score >= 60) return { label: 'Yaxshi', color: 'green' };
    if (score >= 40) return { label: "O'rtacha", color: 'yellow' };
    return { label: 'Zaif', color: 'red' };
  };

  // Actions
  async function checkEligibility() {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.get('/business/diagnostic/check-eligibility');
      eligibility.value = response.data;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function startDiagnostic() {
    loading.value = true;
    processing.value = true;
    error.value = null;
    try {
      const response = await axios.post('/business/diagnostic/start');
      currentDiagnostic.value = {
        id: response.data.diagnostic_id,
        status: 'pending',
      };
      // Start polling for status
      pollStatus(response.data.diagnostic_id);
      return response.data;
    } catch (err) {
      processing.value = false;
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchStatus(diagnosticId) {
    try {
      const response = await axios.get(`/business/diagnostic/${diagnosticId}/status`);
      processingStep.value = response.data.processing_step;

      if (response.data.status === 'completed') {
        processing.value = false;
        await fetchDiagnostic(diagnosticId);
      } else if (response.data.status === 'failed') {
        processing.value = false;
        error.value = response.data.error_message || 'Diagnostika muvaffaqiyatsiz tugadi';
      }

      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    }
  }

  let pollInterval = null;

  function pollStatus(diagnosticId) {
    if (pollInterval) {
      clearInterval(pollInterval);
    }

    pollInterval = setInterval(async () => {
      try {
        const status = await fetchStatus(diagnosticId);
        if (status.status === 'completed' || status.status === 'failed') {
          clearInterval(pollInterval);
          pollInterval = null;
        }
      } catch (err) {
        clearInterval(pollInterval);
        pollInterval = null;
      }
    }, 2000); // Poll every 2 seconds
  }

  function stopPolling() {
    if (pollInterval) {
      clearInterval(pollInterval);
      pollInterval = null;
    }
  }

  async function fetchDiagnostic(diagnosticId) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.get(`/business/diagnostic/${diagnosticId}`);
      // Inertia returns page props, so we extract the diagnostic
      if (response.data.props) {
        currentDiagnostic.value = response.data.props.diagnostic;
        questions.value = response.data.props.questions || [];
        kpis.value = response.data.props.kpis || null;
      }
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchLatest() {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.get('/business/api/diagnostic/latest');
      latestDiagnostic.value = response.data.diagnostic;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchHistory() {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.get('/business/diagnostic/history');
      // Inertia response
      if (response.data.props) {
        history.value = response.data.props.diagnostics || [];
      }
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchQuestions(diagnosticId) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.get(`/business/diagnostic/${diagnosticId}/questions`);
      if (response.data.props) {
        questions.value = response.data.props.questions || [];
      }
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function answerQuestion(questionId, answer) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.post(`/business/diagnostic/questions/${questionId}/answer`, {
        answer,
      });

      // Update local question
      const index = questions.value.findIndex(q => q.id === questionId);
      if (index !== -1) {
        questions.value[index] = {
          ...questions.value[index],
          answer,
          answered_at: new Date().toISOString(),
        };
      }

      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function downloadReport(diagnosticId, type = 'detailed') {
    try {
      const response = await axios.get(`/business/diagnostic/${diagnosticId}/report/${type}`, {
        responseType: 'blob',
      });

      // Create download link
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `diagnostic-report-${diagnosticId}.html`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);

      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    }
  }

  // Set current diagnostic from Inertia props
  function setFromProps(props) {
    if (props.diagnostic) {
      currentDiagnostic.value = props.diagnostic;
    }
    if (props.questions) {
      questions.value = props.questions;
    }
    if (props.kpis) {
      kpis.value = props.kpis;
    }
    if (props.latestDiagnostic) {
      latestDiagnostic.value = props.latestDiagnostic;
    }
    if (props.history) {
      history.value = props.history;
    }
    if (props.canStart !== undefined) {
      eligibility.value = props.canStart;
    }
  }

  // Reset
  function reset() {
    currentDiagnostic.value = null;
    questions.value = [];
    kpis.value = null;
    processingStep.value = null;
    processing.value = false;
    error.value = null;
    stopPolling();
  }

  return {
    // State
    currentDiagnostic,
    latestDiagnostic,
    history,
    questions,
    kpis,
    loading,
    processing,
    error,
    eligibility,
    processingStep,
    processingSteps,

    // Computed
    hasLatestDiagnostic,
    canStartDiagnostic,
    eligibilityReasons,
    overallScore,
    categoryScores,
    swotAnalysis,
    recommendations,
    aiInsights,
    benchmarkSummary,
    trendData,
    currentProcessingStepIndex,
    isProcessing,
    answeredQuestionsCount,

    // Helpers
    getScoreStatus,

    // Actions
    checkEligibility,
    startDiagnostic,
    fetchStatus,
    pollStatus,
    stopPolling,
    fetchDiagnostic,
    fetchLatest,
    fetchHistory,
    fetchQuestions,
    answerQuestion,
    downloadReport,
    setFromProps,
    reset,
  };
});
