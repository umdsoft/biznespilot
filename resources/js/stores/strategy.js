import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export const useStrategyStore = defineStore('strategy', () => {
    // State
    const loading = ref(false);
    const error = ref(null);
    const currentYear = ref(new Date().getFullYear());

    // Strategy data
    const annualStrategy = ref(null);
    const quarterlyPlan = ref(null);
    const monthlyPlan = ref(null);
    const weeklyPlan = ref(null);

    // Wizard state
    const wizardStep = ref(1);
    const wizardData = ref({
        year: new Date().getFullYear(),
        useAI: true,
        template: null,
        vision: '',
        revenueTarget: null,
        annualBudget: null,
        strategicGoals: [],
        focusAreas: [],
        primaryChannels: ['instagram', 'telegram'],
    });

    // Computed
    const hasAnnualStrategy = computed(() => annualStrategy.value !== null);
    const currentQuarter = computed(() => Math.ceil((new Date().getMonth() + 1) / 3));
    const currentMonth = computed(() => new Date().getMonth() + 1);

    // Actions
    async function fetchDashboard(year = null) {
        loading.value = true;
        error.value = null;

        try {
            router.get('/business/strategy', { year: year || currentYear.value }, {
                preserveState: true,
                onSuccess: () => {
                    loading.value = false;
                },
                onError: (errors) => {
                    error.value = errors;
                    loading.value = false;
                },
            });
        } catch (e) {
            error.value = e.message;
            loading.value = false;
        }
    }

    async function createAnnualStrategy(data) {
        loading.value = true;
        error.value = null;

        return new Promise((resolve, reject) => {
            router.post('/business/strategy/annual', data, {
                onSuccess: () => {
                    loading.value = false;
                    resolve(true);
                },
                onError: (errors) => {
                    error.value = errors;
                    loading.value = false;
                    reject(errors);
                },
            });
        });
    }

    async function updateAnnualStrategy(id, data) {
        loading.value = true;

        return new Promise((resolve, reject) => {
            router.put(`/business/strategy/annual/${id}`, data, {
                preserveScroll: true,
                onSuccess: () => {
                    loading.value = false;
                    resolve(true);
                },
                onError: (errors) => {
                    error.value = errors;
                    loading.value = false;
                    reject(errors);
                },
            });
        });
    }

    async function generateQuarters(annualId) {
        loading.value = true;

        return new Promise((resolve, reject) => {
            router.post(`/business/strategy/annual/${annualId}/generate-quarters`, {}, {
                onSuccess: () => {
                    loading.value = false;
                    resolve(true);
                },
                onError: (errors) => {
                    error.value = errors;
                    loading.value = false;
                    reject(errors);
                },
            });
        });
    }

    async function updateQuarterlyPlan(id, data) {
        loading.value = true;

        return new Promise((resolve, reject) => {
            router.put(`/business/strategy/quarterly/${id}`, data, {
                preserveScroll: true,
                onSuccess: () => {
                    loading.value = false;
                    resolve(true);
                },
                onError: (errors) => {
                    error.value = errors;
                    loading.value = false;
                    reject(errors);
                },
            });
        });
    }

    async function generateMonths(quarterlyId) {
        loading.value = true;

        return new Promise((resolve, reject) => {
            router.post(`/business/strategy/quarterly/${quarterlyId}/generate-months`, {}, {
                onSuccess: () => {
                    loading.value = false;
                    resolve(true);
                },
                onError: (errors) => {
                    error.value = errors;
                    loading.value = false;
                    reject(errors);
                },
            });
        });
    }

    async function updateMonthlyPlan(id, data) {
        loading.value = true;

        return new Promise((resolve, reject) => {
            router.put(`/business/strategy/monthly/${id}`, data, {
                preserveScroll: true,
                onSuccess: () => {
                    loading.value = false;
                    resolve(true);
                },
                onError: (errors) => {
                    error.value = errors;
                    loading.value = false;
                    reject(errors);
                },
            });
        });
    }

    async function generateWeeks(monthlyId) {
        loading.value = true;

        return new Promise((resolve, reject) => {
            router.post(`/business/strategy/monthly/${monthlyId}/generate-weeks`, {}, {
                onSuccess: () => {
                    loading.value = false;
                    resolve(true);
                },
                onError: (errors) => {
                    error.value = errors;
                    loading.value = false;
                    reject(errors);
                },
            });
        });
    }

    async function updateWeeklyPlan(id, data) {
        loading.value = true;

        return new Promise((resolve, reject) => {
            router.put(`/business/strategy/weekly/${id}`, data, {
                preserveScroll: true,
                onSuccess: () => {
                    loading.value = false;
                    resolve(true);
                },
                onError: (errors) => {
                    error.value = errors;
                    loading.value = false;
                    reject(errors);
                },
            });
        });
    }

    async function addTask(weeklyId, task) {
        return new Promise((resolve, reject) => {
            router.post(`/business/strategy/weekly/${weeklyId}/tasks`, task, {
                preserveScroll: true,
                onSuccess: () => resolve(true),
                onError: (errors) => reject(errors),
            });
        });
    }

    async function completeTask(weeklyId, taskId) {
        return new Promise((resolve, reject) => {
            router.post(`/business/strategy/weekly/${weeklyId}/tasks/${taskId}/complete`, {}, {
                preserveScroll: true,
                onSuccess: () => resolve(true),
                onError: (errors) => reject(errors),
            });
        });
    }

    async function approvePlan(type, id) {
        loading.value = true;

        return new Promise((resolve, reject) => {
            router.post(`/business/strategy/${type}/${id}/approve`, {}, {
                onSuccess: () => {
                    loading.value = false;
                    resolve(true);
                },
                onError: (errors) => {
                    error.value = errors;
                    loading.value = false;
                    reject(errors);
                },
            });
        });
    }

    async function completePlan(type, id, actualResults = {}) {
        loading.value = true;

        return new Promise((resolve, reject) => {
            router.post(`/business/strategy/${type}/${id}/complete`, { actual_results: actualResults }, {
                onSuccess: () => {
                    loading.value = false;
                    resolve(true);
                },
                onError: (errors) => {
                    error.value = errors;
                    loading.value = false;
                    reject(errors);
                },
            });
        });
    }

    async function updateKPI(kpiId, value) {
        return new Promise((resolve, reject) => {
            router.put(`/business/strategy/kpi/${kpiId}`, { current_value: value }, {
                preserveScroll: true,
                onSuccess: () => resolve(true),
                onError: (errors) => reject(errors),
            });
        });
    }

    async function recordSpending(allocationId, amount, description = null) {
        return new Promise((resolve, reject) => {
            router.post(`/business/strategy/budget/${allocationId}/spending`, { amount, description }, {
                preserveScroll: true,
                onSuccess: () => resolve(true),
                onError: (errors) => reject(errors),
            });
        });
    }

    async function buildCompleteStrategy(year) {
        loading.value = true;

        return new Promise((resolve, reject) => {
            router.post('/business/strategy/build-complete', { year }, {
                onSuccess: () => {
                    loading.value = false;
                    resolve(true);
                },
                onError: (errors) => {
                    error.value = errors;
                    loading.value = false;
                    reject(errors);
                },
            });
        });
    }

    // Wizard actions
    function setWizardStep(step) {
        wizardStep.value = step;
    }

    function updateWizardData(data) {
        wizardData.value = { ...wizardData.value, ...data };
    }

    function resetWizard() {
        wizardStep.value = 1;
        wizardData.value = {
            year: new Date().getFullYear(),
            useAI: true,
            template: null,
            vision: '',
            revenueTarget: null,
            annualBudget: null,
            strategicGoals: [],
            focusAreas: [],
            primaryChannels: ['instagram', 'telegram'],
        };
    }

    // Set data from page props
    function setAnnualStrategy(data) {
        annualStrategy.value = data;
    }

    function setQuarterlyPlan(data) {
        quarterlyPlan.value = data;
    }

    function setMonthlyPlan(data) {
        monthlyPlan.value = data;
    }

    function setWeeklyPlan(data) {
        weeklyPlan.value = data;
    }

    return {
        // State
        loading,
        error,
        currentYear,
        annualStrategy,
        quarterlyPlan,
        monthlyPlan,
        weeklyPlan,
        wizardStep,
        wizardData,

        // Computed
        hasAnnualStrategy,
        currentQuarter,
        currentMonth,

        // Actions
        fetchDashboard,
        createAnnualStrategy,
        updateAnnualStrategy,
        generateQuarters,
        updateQuarterlyPlan,
        generateMonths,
        updateMonthlyPlan,
        generateWeeks,
        updateWeeklyPlan,
        addTask,
        completeTask,
        approvePlan,
        completePlan,
        updateKPI,
        recordSpending,
        buildCompleteStrategy,
        setWizardStep,
        updateWizardData,
        resetWizard,
        setAnnualStrategy,
        setQuarterlyPlan,
        setMonthlyPlan,
        setWeeklyPlan,
    };
});
