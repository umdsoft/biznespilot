import axios from 'axios';

/**
 * Universal CRUD action factory — problems, competitors, hypotheses uchun
 *
 * @param {string} resource - API resurs nomi (problems, competitors, hypotheses)
 * @param {string} itemKey - response.data.data ichidagi yakka kalit (problem, competitor, hypothesis)
 * @param {object} listRef - Ro'yxat ref (state.problems va h.k.)
 * @param {object} progressRef - Progress ref
 * @param {function} runAsync - async wrapper
 * @param {function} runFetch - fetch wrapper
 */
function createCrudActions(resource, itemKey, listRef, progressRef, runAsync, runFetch) {
    const basePath = `/api/v1/onboarding/${resource}`;

    async function fetchAll() {
        return runFetch(async () => {
            const response = await axios.get(basePath);
            listRef.value = response.data.data;
            return response.data;
        });
    }

    async function store(data) {
        return runAsync(async () => {
            const response = await axios.post(basePath, data);
            listRef.value.push(response.data.data[itemKey]);
            progressRef.value = response.data.data.progress;
            return response.data;
        });
    }

    async function update(id, data) {
        return runAsync(async () => {
            const response = await axios.put(`${basePath}/${id}`, data);
            const index = listRef.value.findIndex(i => i.id === id);
            if (index !== -1) {
                listRef.value[index] = response.data.data[itemKey];
            }
            progressRef.value = response.data.data.progress;
            return response.data;
        });
    }

    async function remove(id) {
        return runAsync(async () => {
            const response = await axios.delete(`${basePath}/${id}`);
            listRef.value = listRef.value.filter(i => i.id !== id);
            progressRef.value = response.data.data.progress;
            return response.data;
        });
    }

    return { fetchAll, store, update, remove };
}

/**
 * Problems CRUD
 */
export function createProblemsActions({ state, runAsync, runFetch }) {
    const crud = createCrudActions('problems', 'problem', state.problems, state.progress, runAsync, runFetch);
    return {
        fetchProblems: crud.fetchAll,
        storeProblem: crud.store,
        updateProblem: crud.update,
        deleteProblem: crud.remove,
    };
}

/**
 * Competitors CRUD
 */
export function createCompetitorsActions({ state, runAsync, runFetch }) {
    const crud = createCrudActions('competitors', 'competitor', state.competitors, state.progress, runAsync, runFetch);
    return {
        fetchCompetitors: crud.fetchAll,
        storeCompetitor: crud.store,
        updateCompetitor: crud.update,
        deleteCompetitor: crud.remove,
    };
}

/**
 * Hypotheses CRUD
 */
export function createHypothesesActions({ state, runAsync, runFetch }) {
    const crud = createCrudActions('hypotheses', 'hypothesis', state.hypotheses, state.progress, runAsync, runFetch);
    return {
        fetchHypotheses: crud.fetchAll,
        storeHypothesis: crud.store,
        updateHypothesis: crud.update,
        deleteHypothesis: crud.remove,
    };
}
