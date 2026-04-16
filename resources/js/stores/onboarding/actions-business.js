import axios from 'axios';

/**
 * Biznes asosiy + batafsil + maturity actionlar
 */
export function createBusinessActions({ state, runAsync, runFetch }) {
    const { progress, maturityScore } = state;

    async function updateBusinessBasic(data) {
        return runAsync(async () => {
            const response = await axios.put('/api/v1/onboarding/business/basic', data);
            progress.value = response.data.data.progress;
            return response.data;
        });
    }

    async function updateBusinessDetails(data) {
        return runAsync(async () => {
            const response = await axios.put('/api/v1/onboarding/business/details', data);
            progress.value = response.data.data.progress;
            return response.data;
        });
    }

    async function updateMaturityAssessment(data) {
        return runAsync(async () => {
            const response = await axios.put('/api/v1/onboarding/business/maturity', data);
            progress.value = response.data.data.progress;
            maturityScore.value = response.data.data.maturity;
            return response.data;
        });
    }

    async function fetchMaturityScore() {
        return runFetch(async () => {
            const response = await axios.get('/api/v1/onboarding/maturity-score');
            maturityScore.value = response.data.data;
            return response.data;
        });
    }

    return {
        updateBusinessBasic,
        updateBusinessDetails,
        updateMaturityAssessment,
        fetchMaturityScore,
    };
}
