<template>
  <div
    class="bg-white rounded-lg border p-4 transition-all"
    :class="{ 'border-green-200 bg-green-50': question.answer }"
  >
    <div class="flex items-start space-x-3">
      <!-- Question number -->
      <div
        class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
        :class="question.answer ? 'bg-green-500 text-white' : 'bg-indigo-100 text-indigo-600'"
      >
        <CheckIcon v-if="question.answer" class="w-5 h-5" />
        <span v-else class="font-medium">{{ index + 1 }}</span>
      </div>

      <div class="flex-1">
        <!-- Category badge -->
        <div class="flex items-center space-x-2 mb-2">
          <span
            class="px-2 py-0.5 text-xs rounded-full"
            :class="categoryBadgeClass"
          >
            {{ categoryLabel }}
          </span>
          <span
            v-if="question.priority === 'high'"
            class="px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-700"
          >
            Muhim
          </span>
        </div>

        <!-- Question text -->
        <p class="text-gray-900 font-medium">{{ question.question }}</p>

        <!-- Answer section -->
        <div v-if="question.answer" class="mt-3">
          <div class="p-3 bg-white rounded border border-green-200">
            <p class="text-gray-700 text-sm">{{ question.answer }}</p>
            <p class="text-xs text-gray-400 mt-2">
              Javob berildi: {{ question.answered_at }}
            </p>
          </div>
          <button
            @click="$emit('edit')"
            class="mt-2 text-sm text-indigo-600 hover:text-indigo-700"
          >
            Javobni tahrirlash
          </button>
        </div>

        <!-- Answer input -->
        <div v-else class="mt-3">
          <textarea
            v-model="answerText"
            rows="3"
            class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="Javobingizni yozing..."
            :disabled="loading"
          ></textarea>
          <div class="flex justify-end mt-2">
            <button
              @click="submitAnswer"
              :disabled="!answerText.trim() || loading"
              class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
            >
              <span v-if="loading" class="mr-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </span>
              Javob berish
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { CheckIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
  question: {
    type: Object,
    required: true,
  },
  index: {
    type: Number,
    default: 0,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['answer', 'edit']);

const answerText = ref('');

const categoryLabels = {
  marketing: 'Marketing',
  sales: 'Sotuvlar',
  operations: 'Operatsiyalar',
  finance: 'Moliya',
  strategy: 'Strategiya',
  general: 'Umumiy',
};

const categoryLabel = computed(() => {
  return categoryLabels[props.question.category] || props.question.category;
});

const categoryBadgeClass = computed(() => {
  const classes = {
    marketing: 'bg-purple-100 text-purple-700',
    sales: 'bg-blue-100 text-blue-700',
    operations: 'bg-orange-100 text-orange-700',
    finance: 'bg-green-100 text-green-700',
    strategy: 'bg-indigo-100 text-indigo-700',
    general: 'bg-gray-100 text-gray-700',
  };
  return classes[props.question.category] || classes.general;
});

function submitAnswer() {
  if (answerText.value.trim()) {
    emit('answer', {
      questionId: props.question.id,
      answer: answerText.value.trim(),
    });
  }
}
</script>
