import axios from 'axios';

/**
 * Progress, steps, industries bilan bog'liq actionlar
 */
export function createProgressActions({ state, runAsync, runFetch }) {
    const { progress, steps, industries, currentStep } = state;

    async function fetchProgress() {
        return runAsync(async () => {
            const response = await axios.get('/api/v1/onboarding/progress');
            progress.value = response.data.data;
            return response.data;
        });
    }

    async function initializeOnboarding() {
        return runAsync(async () => {
            const response = await axios.post('/api/v1/onboarding/initialize');
            progress.value = response.data.data;
            return response.data;
        });
    }

    async function fetchSteps() {
        return runFetch(async () => {
            const response = await axios.get('/api/v1/onboarding/steps');
            steps.value = response.data.data;
            return response.data;
        });
    }

    async function fetchStepDetail(stepCode) {
        return runAsync(async () => {
            const response = await axios.get(`/api/v1/onboarding/steps/${stepCode}`);
            currentStep.value = response.data.data;
            return response.data;
        });
    }

    async function fetchIndustries() {
        return runFetch(async () => {
            const response = await axios.get('/api/v1/industries');
            industries.value = response.data.data;
            return response.data;
        });
    }

    async function startPhase2() {
        return runAsync(async () => {
            const response = await axios.post('/api/v1/onboarding/start-phase-2');
            progress.value = response.data.data;
            return response.data;
        });
    }

    return {
        fetchProgress,
        initializeOnboarding,
        fetchSteps,
        fetchStepDetail,
        fetchIndustries,
        startPhase2,
    };
}
