import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

import { createAsyncWrapper, createFetchWrapper } from './onboarding/shared.js';
import { createProgressActions } from './onboarding/actions-progress.js';
import { createBusinessActions } from './onboarding/actions-business.js';
import {
    createProblemsActions,
    createCompetitorsActions,
    createHypothesesActions,
} from './onboarding/actions-crud.js';
import { createDreamBuyerActions } from './onboarding/actions-dream-buyer.js';
import { createMetricsActions } from './onboarding/actions-metrics.js';

/**
 * Onboarding store — Pinia store fasadi
 *
 * Kod domainlar bo'yicha onboarding/ papkada ajratilgan:
 *   - shared.js           — async/fetch wrapper'lar
 *   - actions-progress.js — progress, steps, industries, phase
 *   - actions-business.js — business basic/details/maturity
 *   - actions-crud.js     — problems/competitors/hypotheses CRUD
 *   - actions-dream-buyer.js — ideal mijoz
 *   - actions-metrics.js  — sales va marketing metrikalar
 *
 * Backward compatibility: eski `useOnboardingStore()` API o'zgarmagan.
 */
export const useOnboardingStore = defineStore('onboarding', () => {
    // ═══ STATE ═══
    const progress = ref(null);
    const steps = ref([]);
    const industries = ref([]);
    const currentStep = ref(null);
    const loading = ref(false);
    const error = ref(null);

    const problems = ref([]);
    const dreamBuyer = ref(null);
    const competitors = ref([]);
    const hypotheses = ref([]);
    const maturityScore = ref(null);

    const state = {
        progress, steps, industries, currentStep,
        problems, dreamBuyer, competitors, hypotheses,
        maturityScore,
    };

    // ═══ COMPUTED ═══
    const overallPercent = computed(() => progress.value?.overall_percent || 0);
    const currentPhase = computed(() => progress.value?.current_phase || 1);
    const canStartPhase2 = computed(() => progress.value?.can_start_phase_2 || false);
    const isLaunched = computed(() => progress.value?.is_launched || false);
    const phase1Status = computed(() => progress.value?.phase_1?.status || 'pending');
    const phase1Percent = computed(() => progress.value?.phase_1?.percent || 0);
    const categoriesProgress = computed(() => progress.value?.categories || {});

    const stepsGroupedByCategory = computed(() => {
        const grouped = { profile: [], integration: [], framework: [] };
        if (progress.value?.steps) {
            progress.value.steps.forEach(step => {
                if (grouped[step.category]) {
                    grouped[step.category].push(step);
                }
            });
        }
        return grouped;
    });

    // ═══ ACTIONS ═══
    const runAsync = createAsyncWrapper(loading, error);
    const runFetch = createFetchWrapper(error);
    const ctx = { state, runAsync, runFetch };

    const progressActions = createProgressActions(ctx);
    const businessActions = createBusinessActions(ctx);
    const problemsActions = createProblemsActions(ctx);
    const dreamBuyerActions = createDreamBuyerActions(ctx);
    const competitorsActions = createCompetitorsActions(ctx);
    const hypothesesActions = createHypothesesActions(ctx);
    const metricsActions = createMetricsActions(ctx);

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
        progress, steps, industries, currentStep, loading, error,
        problems, dreamBuyer, competitors, hypotheses, maturityScore,

        // Computed
        overallPercent, currentPhase, canStartPhase2, isLaunched,
        phase1Status, phase1Percent, categoriesProgress, stepsGroupedByCategory,

        // Actions (domainlar bo'yicha)
        ...progressActions,
        ...businessActions,
        ...problemsActions,
        ...dreamBuyerActions,
        ...competitorsActions,
        ...hypothesesActions,
        ...metricsActions,
        reset,
    };
});
