import axios from 'axios';

/**
 * Ideal mijoz (Dream Buyer) actionlari
 */
export function createDreamBuyerActions({ state, runAsync, runFetch }) {
    const { dreamBuyer, progress } = state;

    async function fetchDreamBuyer() {
        return runFetch(async () => {
            const response = await axios.get('/api/v1/onboarding/dream-buyer');
            dreamBuyer.value = response.data.data;
            return response.data;
        });
    }

    async function updateDreamBuyer(data) {
        return runAsync(async () => {
            const response = await axios.put('/api/v1/onboarding/dream-buyer', data);
            dreamBuyer.value = response.data.data.dream_buyer;
            progress.value = response.data.data.progress;
            return response.data;
        });
    }

    return {
        fetchDreamBuyer,
        updateDreamBuyer,
    };
}
