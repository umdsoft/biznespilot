import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useOnboardingStore = defineStore('onboarding', () => {
  // State
  const progress = ref(null);
  const steps = ref([]);
  const industries = ref([]);
  const currentStep = ref(null);
  const loading = ref(false);
  const error = ref(null);

  // Problems, Dream Buyer, Competitors, Hypotheses
  const problems = ref([]);
  const dreamBuyer = ref(null);
  const competitors = ref([]);
  const hypotheses = ref([]);

  // Maturity
  const maturityScore = ref(null);

  // Computed
  const overallPercent = computed(() => progress.value?.overall_percent || 0);
  const currentPhase = computed(() => progress.value?.current_phase || 1);
  const canStartPhase2 = computed(() => progress.value?.can_start_phase_2 || false);
  const isLaunched = computed(() => progress.value?.is_launched || false);

  const phase1Status = computed(() => progress.value?.phase_1?.status || 'pending');
  const phase1Percent = computed(() => progress.value?.phase_1?.percent || 0);

  const categoriesProgress = computed(() => progress.value?.categories || {});

  const stepsGroupedByCategory = computed(() => {
    const grouped = {
      profile: [],
      integration: [],
      framework: []
    };

    if (progress.value?.steps) {
      progress.value.steps.forEach(step => {
        if (grouped[step.category]) {
          grouped[step.category].push(step);
        }
      });
    }

    return grouped;
  });

  // Actions
  async function fetchProgress() {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.get('/api/v1/onboarding/progress');
      progress.value = response.data.data;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function initializeOnboarding() {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.post('/api/v1/onboarding/initialize');
      progress.value = response.data.data;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchSteps() {
    try {
      const response = await axios.get('/api/v1/onboarding/steps');
      steps.value = response.data.data;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    }
  }

  async function fetchStepDetail(stepCode) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.get(`/api/v1/onboarding/steps/${stepCode}`);
      currentStep.value = response.data.data;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchIndustries() {
    try {
      const response = await axios.get('/api/v1/industries');
      industries.value = response.data.data;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    }
  }

  // Business Basic (Step 1)
  async function updateBusinessBasic(data) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.put('/api/v1/onboarding/business/basic', data);
      progress.value = response.data.data.progress;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  // Business Details (Step 2)
  async function updateBusinessDetails(data) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.put('/api/v1/onboarding/business/details', data);
      progress.value = response.data.data.progress;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  // Maturity Assessment (Step 3)
  async function updateMaturityAssessment(data) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.put('/api/v1/onboarding/business/maturity', data);
      progress.value = response.data.data.progress;
      maturityScore.value = response.data.data.maturity;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchMaturityScore() {
    try {
      const response = await axios.get('/api/v1/onboarding/maturity-score');
      maturityScore.value = response.data.data;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    }
  }

  // Problems (Step 6)
  async function fetchProblems() {
    try {
      const response = await axios.get('/api/v1/onboarding/problems');
      problems.value = response.data.data;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    }
  }

  async function storeProblem(data) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.post('/api/v1/onboarding/problems', data);
      problems.value.push(response.data.data.problem);
      progress.value = response.data.data.progress;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateProblem(id, data) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.put(`/api/v1/onboarding/problems/${id}`, data);
      const index = problems.value.findIndex(p => p.id === id);
      if (index !== -1) {
        problems.value[index] = response.data.data.problem;
      }
      progress.value = response.data.data.progress;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function deleteProblem(id) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.delete(`/api/v1/onboarding/problems/${id}`);
      problems.value = problems.value.filter(p => p.id !== id);
      progress.value = response.data.data.progress;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  // Dream Buyer (Step 7)
  async function fetchDreamBuyer() {
    try {
      const response = await axios.get('/api/v1/onboarding/dream-buyer');
      dreamBuyer.value = response.data.data;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    }
  }

  async function updateDreamBuyer(data) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.put('/api/v1/onboarding/dream-buyer', data);
      dreamBuyer.value = response.data.data.dream_buyer;
      progress.value = response.data.data.progress;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  // Competitors (Step 8)
  async function fetchCompetitors() {
    try {
      const response = await axios.get('/api/v1/onboarding/competitors');
      competitors.value = response.data.data;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    }
  }

  async function storeCompetitor(data) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.post('/api/v1/onboarding/competitors', data);
      competitors.value.push(response.data.data.competitor);
      progress.value = response.data.data.progress;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateCompetitor(id, data) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.put(`/api/v1/onboarding/competitors/${id}`, data);
      const index = competitors.value.findIndex(c => c.id === id);
      if (index !== -1) {
        competitors.value[index] = response.data.data.competitor;
      }
      progress.value = response.data.data.progress;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function deleteCompetitor(id) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.delete(`/api/v1/onboarding/competitors/${id}`);
      competitors.value = competitors.value.filter(c => c.id !== id);
      progress.value = response.data.data.progress;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  // Hypotheses (Step 9)
  async function fetchHypotheses() {
    try {
      const response = await axios.get('/api/v1/onboarding/hypotheses');
      hypotheses.value = response.data.data;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    }
  }

  async function storeHypothesis(data) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.post('/api/v1/onboarding/hypotheses', data);
      hypotheses.value.push(response.data.data.hypothesis);
      progress.value = response.data.data.progress;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateHypothesis(id, data) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.put(`/api/v1/onboarding/hypotheses/${id}`, data);
      const index = hypotheses.value.findIndex(h => h.id === id);
      if (index !== -1) {
        hypotheses.value[index] = response.data.data.hypothesis;
      }
      progress.value = response.data.data.progress;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function deleteHypothesis(id) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.delete(`/api/v1/onboarding/hypotheses/${id}`);
      hypotheses.value = hypotheses.value.filter(h => h.id !== id);
      progress.value = response.data.data.progress;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  // Start Phase 2
  async function startPhase2() {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.post('/api/v1/onboarding/start-phase-2');
      progress.value = response.data.data;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Xatolik yuz berdi';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  // Reset
  function reset() {
    progress.value = null;
    steps.value = [];
    currentStep.value = null;
    problems.value = [];
    dreamBuyer.value = null;
    competitors.value = [];
    hypotheses.value = [];
    maturityScore.value = null;
    error.value = null;
  }

  return {
    // State
    progress,
    steps,
    industries,
    currentStep,
    loading,
    error,
    problems,
    dreamBuyer,
    competitors,
    hypotheses,
    maturityScore,

    // Computed
    overallPercent,
    currentPhase,
    canStartPhase2,
    isLaunched,
    phase1Status,
    phase1Percent,
    categoriesProgress,
    stepsGroupedByCategory,

    // Actions
    fetchProgress,
    initializeOnboarding,
    fetchSteps,
    fetchStepDetail,
    fetchIndustries,
    updateBusinessBasic,
    updateBusinessDetails,
    updateMaturityAssessment,
    fetchMaturityScore,
    fetchProblems,
    storeProblem,
    updateProblem,
    deleteProblem,
    fetchDreamBuyer,
    updateDreamBuyer,
    fetchCompetitors,
    storeCompetitor,
    updateCompetitor,
    deleteCompetitor,
    fetchHypotheses,
    storeHypothesis,
    updateHypothesis,
    deleteHypothesis,
    startPhase2,
    reset,
  };
});
