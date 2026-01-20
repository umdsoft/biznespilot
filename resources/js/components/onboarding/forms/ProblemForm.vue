<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">{{ t('onboarding.problems.title') }}</h3>
        <p class="text-sm text-gray-500">{{ t('onboarding.problems.description') }}</p>
      </div>
      <button
        @click="showAddForm = true"
        v-if="!showAddForm"
        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ t('onboarding.problems.add_problem') }}
      </button>
    </div>

    <!-- Add/Edit Form -->
    <div v-if="showAddForm || editingProblem" class="bg-gray-50 rounded-xl p-6 space-y-4">
      <h4 class="font-medium text-gray-900">
        {{ editingProblem ? t('onboarding.problems.edit_problem') : t('onboarding.problems.new_problem') }}
      </h4>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          {{ t('onboarding.problems.name_label') }} *
        </label>
        <input
          v-model="form.title"
          type="text"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          :placeholder="t('onboarding.problems.name_placeholder')"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          {{ t('onboarding.problems.description_label') }}
        </label>
        <textarea
          v-model="form.description"
          rows="3"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          :placeholder="t('onboarding.problems.description_placeholder')"
        ></textarea>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('onboarding.problems.impact_label') }}
          </label>
          <select
            v-model="form.impact_level"
            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="low">{{ t('onboarding.problems.impact.low') }}</option>
            <option value="medium">{{ t('onboarding.problems.impact.medium') }}</option>
            <option value="high">{{ t('onboarding.problems.impact.high') }}</option>
            <option value="critical">{{ t('onboarding.problems.impact.critical') }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('onboarding.problems.category_label') }}
          </label>
          <select
            v-model="form.category"
            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="marketing">{{ t('onboarding.problems.category.marketing') }}</option>
            <option value="sales">{{ t('onboarding.problems.category.sales') }}</option>
            <option value="operations">{{ t('onboarding.problems.category.operations') }}</option>
            <option value="finance">{{ t('onboarding.problems.category.finance') }}</option>
            <option value="hr">{{ t('onboarding.problems.category.hr') }}</option>
            <option value="technology">{{ t('onboarding.problems.category.technology') }}</option>
            <option value="product">{{ t('onboarding.problems.category.product') }}</option>
            <option value="customer_service">{{ t('onboarding.problems.category.customer_service') }}</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          {{ t('onboarding.problems.desired_outcome_label') }}
        </label>
        <textarea
          v-model="form.desired_outcome"
          rows="2"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          :placeholder="t('onboarding.problems.desired_outcome_placeholder')"
        ></textarea>
      </div>

      <div class="flex justify-end gap-3 pt-4">
        <button
          type="button"
          @click="cancelForm"
          class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors"
        >
          {{ t('common.cancel') }}
        </button>
        <button
          @click="saveProblem"
          :disabled="!form.title || saving"
          class="px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <svg v-if="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ editingProblem ? t('common.save') : t('common.add') }}
        </button>
      </div>
    </div>

    <!-- Problems List -->
    <div v-if="problems.length > 0" class="space-y-3">
      <div
        v-for="problem in problems"
        :key="problem.id"
        class="bg-white border border-gray-200 rounded-xl p-4 hover:shadow-md transition-shadow"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <h4 class="font-medium text-gray-900">{{ problem.title }}</h4>
              <span
                :class="[
                  'px-2 py-0.5 text-xs rounded-full',
                  severityColors[problem.impact_level] || 'bg-gray-100 text-gray-600'
                ]"
              >
                {{ t(`onboarding.problems.impact.${problem.impact_level}`) }}
              </span>
              <span class="px-2 py-0.5 text-xs rounded-full bg-indigo-100 text-indigo-600">
                {{ t(`onboarding.problems.category.${problem.category}`) }}
              </span>
            </div>
            <p v-if="problem.description" class="text-sm text-gray-600 mb-2">
              {{ problem.description }}
            </p>
            <p v-if="problem.desired_outcome" class="text-xs text-gray-500">
              <span class="font-medium">{{ t('onboarding.problems.desired_outcome_display') }}:</span> {{ problem.desired_outcome }}
            </p>
          </div>
          <div class="flex items-center gap-2 ml-4">
            <button
              @click="editProblem(problem)"
              class="p-2 text-gray-400 hover:text-indigo-600 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button
              @click="deleteProblem(problem.id)"
              class="p-2 text-gray-400 hover:text-red-600 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!showAddForm" class="text-center py-12 bg-gray-50 rounded-xl">
      <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <h4 class="text-lg font-medium text-gray-900 mb-2">{{ t('onboarding.problems.empty_title') }}</h4>
      <p class="text-gray-500 mb-4">{{ t('onboarding.problems.empty_description') }}</p>
      <button
        @click="showAddForm = true"
        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
      >
        {{ t('onboarding.problems.add_first') }}
      </button>
    </div>

    <!-- Info text -->
    <p class="text-sm text-gray-500 text-center">
      {{ t('onboarding.problems.info_text') }}
    </p>

    <!-- Action Buttons -->
    <div class="flex justify-between gap-3 pt-4">
      <button
        type="button"
        @click="$emit('skip')"
        class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors"
      >
        {{ t('common.skip') }}
      </button>
      <div class="flex gap-3">
        <button
          type="button"
          @click="$emit('cancel')"
          class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors"
        >
          {{ t('common.cancel') }}
        </button>
        <button
          @click="$emit('submit')"
          class="px-6 py-3 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors"
        >
          {{ t('common.continue') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useOnboardingStore } from '@/stores/onboarding';
import { useToastStore } from '@/stores/toast';
import { useI18n } from '@/i18n';

const { t } = useI18n();
const store = useOnboardingStore();
const toast = useToastStore();

const emit = defineEmits(['submit', 'cancel', 'skip']);

const problems = ref([]);
const showAddForm = ref(false);
const editingProblem = ref(null);
const saving = ref(false);

const form = reactive({
  title: '',
  description: '',
  impact_level: 'medium',
  category: 'marketing',
  desired_outcome: ''
});

const severityColors = {
  low: 'bg-green-100 text-green-600',
  medium: 'bg-yellow-100 text-yellow-600',
  high: 'bg-orange-100 text-orange-600',
  critical: 'bg-red-100 text-red-600'
};

onMounted(async () => {
  await loadProblems();
});

async function loadProblems() {
  try {
    await store.fetchProblems();
    problems.value = store.problems || [];
  } catch (err) {
    console.error('Failed to load problems:', err);
  }
}

function resetForm() {
  form.title = '';
  form.description = '';
  form.impact_level = 'medium';
  form.category = 'marketing';
  form.desired_outcome = '';
}

function cancelForm() {
  showAddForm.value = false;
  editingProblem.value = null;
  resetForm();
}

function editProblem(problem) {
  editingProblem.value = problem;
  form.title = problem.title;
  form.description = problem.description || '';
  form.impact_level = problem.impact_level || 'medium';
  form.category = problem.category || 'marketing';
  form.desired_outcome = problem.desired_outcome || '';
  showAddForm.value = false;
}

async function saveProblem() {
  if (!form.title) return;

  saving.value = true;

  try {
    if (editingProblem.value) {
      await store.updateProblem(editingProblem.value.id, { ...form });
      toast.success(t('common.success'), t('onboarding.problems.updated_message'));
    } else {
      await store.storeProblem({ ...form });
      toast.success(t('common.success'), t('onboarding.problems.added_message'));
    }

    await loadProblems();
    cancelForm();
  } catch (err) {
    console.error('Failed to save problem:', err);
    const errorMessage = err.response?.data?.message || t('common.save_error');
    toast.error(t('common.error'), errorMessage);
  } finally {
    saving.value = false;
  }
}

async function deleteProblem(id) {
  if (!confirm(t('onboarding.problems.delete_confirm'))) return;

  try {
    await store.deleteProblem(id);
    await loadProblems();
    toast.success(t('common.deleted'), t('onboarding.problems.deleted_message'));
  } catch (err) {
    console.error('Failed to delete problem:', err);
    toast.error(t('common.error'), t('onboarding.problems.delete_error'));
  }
}
</script>
